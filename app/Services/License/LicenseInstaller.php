<?php

namespace App\Services\License;

use App\Models\PluginLicense;
use App\Services\Plugin\PluginManifest;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class LicenseInstaller
{
    public function __construct(protected LicenseVerifier $verifier)
    {
    }

    // eClassify plugins (Cashfree, PayU India) validate against the
    // validator's own per-product history endpoint — same pattern every
    // other WRTeam product uses ({slug}_validator?purchase_code=&domain_url=),
    // not the generic signed plugin-license API (that system was reverted).
    public function install(string $slug, string $purchaseCode, ?string $domain = null, string $scope = 'universal'): PluginLicense
    {
        $domain = $domain ?? parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        $version = $this->localModuleVersion($slug);

        $response = $this->callValidator($slug, $purchaseCode, $domain);

        if (($response['error'] ?? true) !== false) {
            throw new Exception($response['message'] ?? 'This purchase code is not valid.');
        }

        // 'revoked' is deliberately not set here — reinstalling must not
        // silently un-revoke a license killed via plugin:license:revoke.
        return PluginLicense::updateOrCreate(
            ['plugin_slug' => $slug, 'domain' => $domain],
            [
                'purchase_code_hash' => hash('sha256', $purchaseCode),
                'purchase_code_encrypted' => Crypt::encryptString($purchaseCode),
                'scope' => $scope,
                'version' => $version,
                // Signature columns are NOT NULL but unused — this endpoint
                // doesn't sign responses.
                'signature' => '',
                'payload_b64' => null,
                'key_id' => 'v1',
                'alg' => 'ed25519',
                'issued_at' => now(),
                'expires_hint_at' => null,
                'recheck_failing_since' => null,
                'grace_expired' => false,
                'verified_at' => now(),
            ]
        );
    }

    protected function localModuleVersion(string $slug): ?string
    {
        return PluginManifest::read($slug)['version'] ?? null;
    }

    // validator.wrteam.in/{slug_with_underscores}_validator — e.g.
    // cashfree-payment-gateway -> cashfree_payment_gateway_validator.
    protected function callValidator(string $slug, string $purchaseCode, string $domain): array
    {
        $routeSlug = str_replace('-', '_', $slug);
        $url = rtrim(config('license.validator_url'), '/')."/{$routeSlug}_validator";

        try {
            $response = Http::withOptions([
                'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                'allow_redirects' => ['max' => 10],
                'version' => '1.1',
            ])->get($url, [
                'purchase_code' => $purchaseCode,
                'domain_url' => $domain,
            ]);
        } catch (ConnectionException $e) {
            throw new Exception('Could not reach the license validator: '.$e->getMessage());
        }

        $decoded = $response->json();

        if (!is_array($decoded)) {
            throw new Exception('License validator returned an unexpected response.');
        }

        return $decoded;
    }
}
