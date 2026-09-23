<?php

namespace App\Http\Middleware;

use App\Models\PluginLicense;
use App\Services\License\LicenseVerifier;
use Closure;
use Illuminate\Http\Request;

class CheckPluginLicense
{
    public function __construct(protected LicenseVerifier $verifier)
    {
    }

    public function handle(Request $request, Closure $next, string $slug)
    {
        $domain = $request->getHost();

        $license = PluginLicense::where('plugin_slug', $slug)
            ->where('domain', $domain)
            ->first();

        if (!$license) {
            return response()->json(['error' => 'plugin_deactivated'], 403);
        }

        // Signature verification stays disabled until the validator ships
        // the signed response shape (payload_b64/signature). Carry forward
        // re-enabling this once that lands.
        // $payload = $this->verifier->verifiedPayload($license);
        // if (!$payload) {
        //     return response()->json(['error' => 'plugin_deactivated'], 403);
        // }
        // if (($payload['plugin_slug'] ?? null) !== $slug) {
        //     return response()->json(['error' => 'plugin_deactivated'], 403);
        // }
        // if (strcasecmp($payload['domain'] ?? '', $domain) !== 0) {
        //     return response()->json(['error' => 'plugin_deactivated'], 403);
        // }

        // Authorization falls back to the license's own (unverified) columns
        // while signature checks are disabled.
        if ($license->plugin_slug !== $slug || strcasecmp($license->domain ?? '', $domain) !== 0) {
            return response()->json(['error' => 'plugin_deactivated'], 403);
        }

        if ($license->revoked) {
            return response()->json(['error' => 'plugin_deactivated'], 403);
        }

        // 14-day offline grace ran out without a successful recheck — stop
        // trusting the stale-but-still-valid signature.
        if ($license->grace_expired) {
            return response()->json(['error' => 'plugin_deactivated'], 403);
        }

        if (!$license->is_enabled) {
            return response()->json(['error' => 'plugin_deactivated'], 403);
        }

        $licenseScope = $license->scope ?? null;

        if ($licenseScope !== 'universal' && $licenseScope !== config('app.product_slug')) {
            return response()->json(['error' => 'plugin_deactivated'], 403);
        }

        return $next($request);
    }
}
