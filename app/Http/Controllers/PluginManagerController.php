<?php

namespace App\Http\Controllers;

use App\Models\PaymentConfiguration;
use App\Models\PluginCatalogItem;
use App\Models\PluginLicense;
use App\Models\Setting;
use App\Services\License\LicenseInstaller;
use App\Services\License\MarketplaceCatalogService;
use App\Services\Plugin\PluginManifest;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

class PluginManagerController extends Controller
{
    public function index()
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            return redirect(route('home'))->withErrors([
                'message' => trans("You Don't have enough permissions"),
            ]);
        }

        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        $installedSlugs = [];
        $modules = [];
        // Folder name is the slug (both kebab-case).
        foreach (glob(app_path('Modules').'/*', GLOB_ONLYDIR) as $dir) {
            $slug = basename($dir);
            $installedSlugs[] = $slug;

            $manifest = PluginManifest::read($slug);

            $modules[] = [
                'slug' => $slug,
                'name' => $manifest['name'] ?? $slug, // brand name for display, not the identifier
                'license' => PluginLicense::where('plugin_slug', $slug)->where('domain', $domain)->first(),
            ];
        }

        $notInstalled = $this->notInstalledCatalogItems($installedSlugs);

        $catalogLastFetched = Setting::getValue('plugin_catalog_last_fetched');

        return view('plugin-manager.index', compact('modules', 'domain', 'notInstalled', 'catalogLastFetched'));
    }

    // Catalog entries not present as a local folder — "you could buy this".
    protected function notInstalledCatalogItems(?array $installedSlugs = null)
    {
        if ($installedSlugs === null) {
            $installedSlugs = [];
            foreach (glob(app_path('Modules').'/*', GLOB_ONLYDIR) as $dir) {
                $installedSlugs[] = basename($dir);
            }
        }

        return PluginCatalogItem::whereNotIn('slug', $installedSlugs)->get();
    }

    public function installed()
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            ResponseService::errorResponse(trans("You Don't have enough permissions"));

            return;
        }

        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        $plugins = PluginLicense::where('domain', $domain)
            ->where('is_enabled', true)
            ->where('revoked', false)
            ->get()
            ->map(fn ($license) => [
                'slug' => $license->plugin_slug,
                'type' => $license->type,
                'version' => $license->version,
                'scope' => $license->scope,
                'verified_at' => optional($license->verified_at)->toDateTimeString(),
            ])
            ->values();

        ResponseService::successResponse('Installed plugins fetched successfully.', $plugins);
    }
    
    public function toggleEnabled(string $slug)
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            ResponseService::errorResponse(trans("You Don't have enough permissions"));

            return;
        }

        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $license = PluginLicense::where('plugin_slug', $slug)->where('domain', $domain)->first();

        if (!$license) {
            ResponseService::errorResponse('Plugin license not found.');

            return;
        }

        $license->update(['is_enabled' => !$license->is_enabled]);

        if (!$license->is_enabled && $license->type === 'payment') {
            PaymentConfiguration::where('payment_method', $license->plugin_slug)->update(['status' => 0]);
        }

        ResponseService::successResponse(
            $license->is_enabled ? "Plugin [{$slug}] enabled." : "Plugin [{$slug}] disabled.",
            ['is_enabled' => $license->is_enabled]
        );
    }

    // Manual refresh, throttled at the route (5/hour) — same catalog fetch
    // the daily scheduled job runs, just admin-triggered on demand.
    public function refreshCatalog(MarketplaceCatalogService $service)
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            ResponseService::errorResponse(trans("You Don't have enough permissions"));
        }

        try {
            $result = $service->refresh();
            $notInstalled = $this->notInstalledCatalogItems();

            ResponseService::successResponse($result['changed']
                ? "Catalog refreshed — {$result['count']} plugin(s)."
                : 'Catalog already up to date.', [
                    'notInstalled' => $notInstalled,
                    'lastFetched' => Setting::getValue('plugin_catalog_last_fetched'),
                ]);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'PluginManagerController -> refreshCatalog');
            ResponseService::errorResponse('Catalog refresh failed: '.$e->getMessage());
        }
    }

    // Pre-fills the slug from the catalog item clicked on the Available
    // Plugin tab, or blank for a manual install.
    public function installForm(?string $slug = null)
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            return redirect(route('home'))->withErrors([
                'message' => trans("You Don't have enough permissions"),
            ]);
        }

        $catalogItem = $slug ? PluginCatalogItem::where('slug', $slug)->first() : null;

        // Fall back to the local module.json's name if there's no catalog entry yet.
        $displayName = $catalogItem->name ?? null;
        if (!$displayName && $slug) {
            $displayName = PluginManifest::read($slug)['name'] ?? null;
        }

        return view('plugin-manager.install', compact('slug', 'catalogItem', 'displayName'));
    }

    // Single combined step: verify the purchase code FIRST; only on success
    // do we touch the zip. A failed license check never extracts anything.
    public function install(Request $request, LicenseInstaller $installer)
    {
        if (!Auth::user()->hasRole('Super Admin')) {
            ResponseService::errorResponse(trans("You Don't have enough permissions"));
        }

        $validator = Validator::make($request->all(), [
            'slug' => 'required|string|regex:/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/',
            'purchase_code' => 'required|string',
            'file' => 'required|file|mimes:zip',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        $slug = $request->input('slug');
        $scope = PluginCatalogItem::where('slug', $slug)->first()?->scope;
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        try {
            $license = $installer->install($slug, $request->input('purchase_code'), $domain, $scope);
        } catch (Throwable $e) {
            ResponseService::errorResponse('License verification failed: '.$e->getMessage());
        }

        try {
            $this->extractPluginZip($slug, $request->file('file'));
            $this->applyModuleManifest($license, $slug);

            Artisan::call('optimize:clear');

            ResponseService::successResponse("Plugin [{$slug}] activated and installed.");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'PluginManagerController -> install (extract)');
            ResponseService::errorResponse('License verified, but package extraction failed: '.$e->getMessage());
        }
    }

    // Copies module.json's "type"/"version" onto the license row so
    // PaymentService and the settings page pick the plugin up automatically.
    protected function applyModuleManifest(PluginLicense $license, string $slug): void
    {
        $manifest = PluginManifest::read($slug);

        if (!$manifest) {
            logger()->warning("Plugin install: {$slug}/module.json is missing or not valid JSON, skipping type.");

            return;
        }

        $license->update([
            'type' => $manifest['type'] ?? 'plugin',
            'version' => $manifest['version'] ?? $license->version,
        ]);
    }

    // Install zip: the plugin's slug names the file (cashfree-payment-gateway.zip),
    // and its contents extract straight into app/Modules/{slug}/.
    protected function extractPluginZip(string $slug, $uploadedFile): void
    {
        $tmpPath = storage_path('app/plugin-uploads');
        File::ensureDirectoryExists($tmpPath);

        $fileName = uniqid('plugin_').'.zip';
        $uploadedFile->move($tmpPath, $fileName);
        $filePath = $tmpPath.'/'.$fileName;

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            unlink($filePath);
            throw new \RuntimeException('Unable to open uploaded zip.');
        }

        // Zip-slip guard: reject any entry that would escape the destination
        // (../, absolute paths) before extracting anything.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false || str_contains($entry, '..') || str_starts_with($entry, '/')) {
                $zip->close();
                unlink($filePath);
                throw new \RuntimeException("Unsafe path in zip entry: {$entry}");
            }
        }

        // Flat (module.json at the zip root) or wrapped in a top-level
        // {slug}/ folder — wrapped zips extract one level up to avoid
        // double-nesting into app/Modules/{slug}/{slug}/.
        $isWrapped = $zip->locateName("{$slug}/module.json") !== false;
        $isFlat = $zip->locateName('module.json') !== false;

        if (!$isWrapped && !$isFlat) {
            $zip->close();
            unlink($filePath);
            throw new \RuntimeException("Zip does not contain module.json — not a valid plugin package for slug [{$slug}].");
        }

        $manifestEntry = $isWrapped ? "{$slug}/module.json" : 'module.json';
        $manifestContent = $zip->getFromName($manifestEntry);
        $manifest = $manifestContent !== false ? json_decode($manifestContent, true) : null;

        if (!is_array($manifest)) {
            $zip->close();
            unlink($filePath);
            throw new \RuntimeException("module.json is missing");
        }

        // main_class names the one class this plugin needs to work (its
        // ServiceProvider, or its own service class) — checked for every
        // plugin type, not just payment.
        $mainClass = $manifest['main_class'] ?? null;

        if (!$mainClass) {
            $zip->close();
            unlink($filePath);
            throw new \RuntimeException('module.json must declare "main_class" — the class this plugin requires (its ServiceProvider, or its own service class).');
        }

        $namespacePrefix = 'App\\Modules\\'.Str::studly($slug).'\\';

        if (!str_starts_with($mainClass, $namespacePrefix)) {
            $zip->close();
            unlink($filePath);
            throw new \RuntimeException("main_class [{$mainClass}] is not under this plugin's own namespace [{$namespacePrefix}].");
        }

        $relativeClassPath = str_replace('\\', '/', substr($mainClass, strlen($namespacePrefix))).'.php';
        $requiredFileEntry = ($isWrapped ? "{$slug}/" : '')."app/{$relativeClassPath}";

        if ($zip->locateName($requiredFileEntry) === false) {
            $zip->close();
            unlink($filePath);
            throw new \RuntimeException("Zip is missing app/{$relativeClassPath} — the file declared as main_class in module.json.");
        }

        $destination = $isWrapped ? app_path('Modules') : app_path('Modules/'.$slug);
        $zip->extractTo($destination);
        $zip->close();
        unlink($filePath);
    }
}
