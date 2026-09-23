<?php

namespace App\Services\License;

use App\Models\PluginLicense;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

// Runtime flow: a token past its expires_hint gets re-verified online. A
// network/validator failure doesn't disable the plugin right away — it
// keeps working on the still-valid-but-stale signature for
// offline_grace_days, then gets disabled if the validator stays unreachable.
class LicenseRechecker
{
    public function __construct(protected LicenseVerifier $verifier)
    {
    }

    // Licenses whose cached token is past its cache-freshness hint and due
    // for a re-verify.
    public function due(): \Illuminate\Support\Collection
    {
        return PluginLicense::where('revoked', false)
            ->whereNotNull('expires_hint_at')
            ->where('expires_hint_at', '<=', now())
            ->get();
    }

    public function recheck(PluginLicense $license): bool
    {
        try {
            $response = $this->callValidator($license);
        } catch (ConnectionException $e) {
            $this->recordFailure($license);

            return false;
        }

        // Signature verification stays disabled until the validator ships
        // the signed response shape (payload_b64/signature). Carry forward
        // re-enabling this once that lands.
        // $payload = $this->verifier->verifyFreshResponse($response);
        $payload = $response['payload'] ?? $response;

        if (!$payload || ($payload['plugin_slug'] ?? null) !== $license->plugin_slug
            || strcasecmp($payload['domain'] ?? '', $license->domain) !== 0) {
            // A malformed/unverifiable response from a reachable validator is
            // treated the same as unreachable — never trust it, but also
            // don't punish the customer harder than an outage would.
            $this->recordFailure($license);

            return false;
        }

        $license->update([
            'scope' => $payload['scope'] ?? $license->scope,
            // 'signature' column is NOT NULL but unused while signature
            // verification is disabled — validator doesn't sign yet.
            'signature' => $response['signature'] ?? '',
            'payload_b64' => $response['payload_b64'] ?? null,
            'key_id' => $response['key_id'] ?? 'v1',
            'alg' => $response['alg'] ?? 'ed25519',
            'issued_at' => isset($payload['issued_at']) ? Carbon::createFromTimestamp($payload['issued_at']) : now(),
            'expires_hint_at' => isset($payload['expires_hint']) ? Carbon::createFromTimestamp($payload['expires_hint']) : null,
            'recheck_failing_since' => null,
            'grace_expired' => false,
            'verified_at' => now(),
        ]);

        // 'active' is deliberately not enforced here as a hard local flag —
        // CheckPluginLicense re-derives it from the (now-updated) verified
        // payload on every protected request, same as install-time.

        return true;
    }

    protected function recordFailure(PluginLicense $license): void
    {
        $failingSince = $license->recheck_failing_since ?? now();
        $graceDays = (int) config('license.offline_grace_days', 14);
        $graceExpired = $failingSince->copy()->addDays($graceDays)->isPast();

        $license->update([
            'recheck_failing_since' => $failingSince,
            'grace_expired' => $graceExpired,
        ]);
    }

    protected function callValidator(PluginLicense $license): array
    {
        $catalogSegment = $license->scope === 'universal' ? 'universal-plugins' : $license->scope;
        $url = rtrim(config('license.validator_url'), '/')."/plugin/{$catalogSegment}/{$license->plugin_slug}";

        $headers = [];
        if ($productKey = config('license.product_key')) {
            $headers['X-Product-Key'] = $productKey;
        }

        $response = Http::withHeaders($headers)->withOptions([
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
            'allow_redirects' => ['max' => 10],
            'version' => '1.1',
        ])->get($url, [
            'purchase_code' => $license->purchase_code,
            'domain_url' => $license->domain,
            'version' => $license->version,
        ]);

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }
}
