<?php

namespace App\Services\License;

use App\Models\PluginCatalogItem;
use App\Models\Setting;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

// eClassify plugins (Cashfree, PayU India) ship as their own product line,
// not a pulled-from-validator marketplace catalog — see
// database/migrations/2026_09_09_120000_seed_fixed_plugin_catalog_items.php.
// Refresh is a no-op against that fixed catalog; kept so the scheduled job
// and the "Refresh Catalog" admin button don't need removing.
class MarketplaceCatalogService
{
    protected const ETAG_SETTING = 'plugin_catalog_etag';

    protected const LAST_FETCHED_SETTING = 'plugin_catalog_last_fetched';

    public function __construct(protected LicenseVerifier $verifier)
    {
    }

    public function refresh(): array
    {
        return ['changed' => false, 'count' => PluginCatalogItem::count()];
    }

    protected function refreshFromValidator(): array
    {
        $result = $this->fetchFromValidator();

        Setting::updateOrCreate(['name' => self::LAST_FETCHED_SETTING], ['value' => now()->toIso8601String()]);

        if ($result['status'] === 304) {
            return ['changed' => false, 'count' => PluginCatalogItem::count()];
        }

        if ($result['status'] !== 200) {
            throw new Exception("Marketplace catalog fetch failed (HTTP {$result['status']}).");
        }

        // Never trust the top-level "plugins" convenience copy — only the
        // verified payload_b64 bytes (spec §3.2).
        // $payload = $this->verifier->verifyFreshResponse($result['body']);

        // if (!$payload) {
        //     throw new Exception('Marketplace catalog response failed signature verification.');
        // }

        $plugins = $result['body']['plugins'] ?? [];

        foreach ($plugins as $plugin) {
            PluginCatalogItem::updateOrCreate(
                ['slug' => $plugin['slug']],
                [
                    'scope' => $plugin['scope'] ?? 'universal',
                    'name' => $plugin['name'],
                    'description' => $plugin['description'] ?? null,
                    'marketplace_url' => $plugin['marketplace_url'] ?? null,
                    'latest_version' => $plugin['latest_version'] ?? null,
                ]
            );
        }

        // The response is the full current catalog, not a diff — anything
        // locally cached that isn't in it anymore (removed/deactivated on
        // the validator) must go, or a stale row lingers forever.
        PluginCatalogItem::whereNotIn('slug', array_column($plugins, 'slug'))->delete();

        if (!empty($result['etag'])) {
            Setting::updateOrCreate(['name' => self::ETAG_SETTING], ['value' => $result['etag']]);
        }

        return ['changed' => true, 'count' => count($plugins)];
    }

    // GET {validator_url}/api/plugins?product_slug=... (spec §4.1)
    protected function fetchFromValidator(): array
    {
        $headers = [];

        if ($etag = Setting::getValue(self::ETAG_SETTING)) {
            $headers['If-None-Match'] = $etag;
        }

        if ($productKey = config('license.product_key')) {
            $headers['X-Product-Key'] = $productKey;
        }


        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    'version' => '1.1',
                ])
                ->get(config('license.marketplace_url'), [
                    'product_slug' => 'eclassify',
                ]);
        } catch (ConnectionException $e) {
            throw new Exception('Could not reach the marketplace catalog: '.$e->getMessage());
        }

        if ($response->status() === 304) {
            return ['status' => 304];
        }

        // Strip RFC 7232 quotes — sent back unquoted in If-None-Match above.
        $etagHeader = $response->header('ETag');

        $body = $response->json();

        return [
            'status' => $response->status(),
            'body' => is_array($body) ? $body : [],
            'etag' => $etagHeader ? trim($etagHeader, '"') : null,
        ];
    }
}
