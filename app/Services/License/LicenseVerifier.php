<?php

namespace App\Services\License;

use App\Models\PluginLicense;

class LicenseVerifier
{
    // Verifies the exact bytes the validator signed (payload_b64), never a
    // payload rebuilt from plugin_slug/scope/domain columns — those columns
    // are mutable independent of the signature, so a rebuild would let a DB
    // edit slip through unchecked.
    public function signatureValid(PluginLicense $license): bool
    {
        if (!$license->signature || !$license->payload_b64) {
            return false;
        }

        $publicKey = $this->resolvePublicKey($license->alg ?? 'ed25519', $license->key_id);

        if (!$publicKey) {
            return false;
        }

        $payload = base64_decode($license->payload_b64, true);
        $signature = base64_decode($license->signature, true);

        if ($payload === false || $signature === false) {
            return false;
        }

        return $this->verifyBytes($payload, $signature, $publicKey, $license->alg ?? 'ed25519');
    }

    // Returns the signed payload, decoded, only after its signature checks
    // out. Callers making authorization decisions (scope, domain, slug,
    // active) must read those fields from here, not from the license's own
    // columns — the columns are for querying only.
    public function verifiedPayload(PluginLicense $license): ?array
    {
        if (!$this->signatureValid($license)) {
            return null;
        }

        $decoded = json_decode(base64_decode($license->payload_b64), true);

        return is_array($decoded) ? $decoded : null;
    }

    // Verifies a raw validator response (not yet persisted) before it's
    // trusted — used at install/recheck time, right after the HTTP call.
    // Also enforces the issued_at skew guard, which only makes sense for a
    // response that just came in live, never for a cached stored token.
    public function verifyFreshResponse(array $response): ?array
    {
        if (empty($response['payload_b64']) || empty($response['signature'])) {
            return null;
        }

        $publicKey = $this->resolvePublicKey($response['alg'] ?? 'ed25519', $response['key_id'] ?? null);

        if (!$publicKey) {
            return null;
        }

        $payload = base64_decode($response['payload_b64'], true);
        $signature = base64_decode($response['signature'], true);

        if ($payload === false || $signature === false) {
            return null;
        }

        if (!$this->verifyBytes($payload, $signature, $publicKey, $response['alg'] ?? 'ed25519')) {
            return null;
        }

        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            return null;
        }

        $skew = (int) config('license.issued_at_skew_seconds', 300);

        if (isset($decoded['issued_at']) && abs(time() - (int) $decoded['issued_at']) > $skew) {
            return null;
        }

        return $decoded;
    }

    protected function verifyBytes(string $payload, string $signature, string $publicKey, string $alg): bool
    {
        if ($alg === 'ed25519') {
            if (!function_exists('sodium_crypto_sign_verify_detached')) {
                return false;
            }

            $rawKey = base64_decode($publicKey, true);

            if ($rawKey === false) {
                return false;
            }

            try {
                return sodium_crypto_sign_verify_detached($signature, $payload, $rawKey);
            } catch (\SodiumException $e) {
                return false;
            }
        }

        if ($alg === 'rsa') {
            return openssl_verify($payload, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
        }

        return false;
    }

    protected function resolvePublicKey(string $alg, ?string $keyId): ?string
    {
        $keyId = $keyId ?: 'v1';
        $configKey = $alg === 'rsa' ? 'license.rsa_public_keys' : 'license.ed25519_public_keys';
        $key = config("{$configKey}.{$keyId}");

        return $key ?: null;
    }
}
