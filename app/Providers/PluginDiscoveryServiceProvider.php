<?php

namespace App\Providers;

use App\Services\Plugin\PluginManifest;
use Composer\Autoload\ClassLoader;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

// Auto-registers every plugin under app/Modules/{kebab-folder}/ — dropping a
// plugin's zip in is enough, no config/app.php edit needed.
//
// module.json is the validity check (not a required ServiceProvider file);
// a ServiceProvider is only registered when module.json names one via
// "provider". Folder names are kebab-case, but PHP namespaces can't contain
// hyphens, so registerModuleAutoloading() maps each module's real folder to
// its PascalCase namespace explicitly — needed even for plugins with no
// ServiceProvider, since their Services/ classes still have to autoload.
class PluginDiscoveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ($this->discoverProviders() as $providerClass) {
            $this->app->register($providerClass);
        }
    }

    protected function discoverProviders(): array
    {
        $modulesPath = app_path('Modules');

        if (!is_dir($modulesPath)) {
            return [];
        }

        $providers = [];

        foreach (glob($modulesPath.'/*', GLOB_ONLYDIR) as $moduleDir) {
            $folderName = basename($moduleDir);

            if (!preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $folderName)) {
                logger()->warning("Plugin discovery skipped invalid module folder name: {$folderName}");
                continue;
            }

            $manifest = PluginManifest::read($folderName);

            if (!$manifest || ($manifest['slug'] ?? null) !== $folderName) {
                logger()->warning("Plugin discovery skipped {$folderName}: module.json missing or its slug does not match the folder.");
                continue;
            }

            $this->registerModuleAutoloading(Str::studly($folderName), $moduleDir);

            if (empty($manifest['provider'])) {
                continue;
            }

            $providerClass = $manifest['provider'];

            if (!class_exists($providerClass) || !is_subclass_of($providerClass, ServiceProvider::class)) {
                logger()->warning("Plugin discovery skipped {$folderName}: {$providerClass} missing or not a ServiceProvider.");
                continue;
            }

            $providers[] = $providerClass;
        }

        return $providers;
    }

    // Points App\Modules\{Slug}\ at this module's real (kebab-case) folder's
    // app/ subdirectory — Composer's default App\ => app/ mapping alone can't
    // reach it. Longest-prefix-wins, so this doesn't disturb the app-wide mapping.
    protected function registerModuleAutoloading(string $slug, string $moduleDir): void
    {
        foreach (spl_autoload_functions() as $function) {
            if (is_array($function) && $function[0] instanceof ClassLoader) {
                $function[0]->addPsr4("App\\Modules\\{$slug}\\", $moduleDir.'/app/');

                return;
            }
        }
    }
}
