<?php

// Plugin-scoped signed-license system. Independent of the legacy app-wide
// purchase-code flow in InstallerController/EnvSet (APPSECRET in .env).
return [
    // Validator public keys, keyed by key_id, base64 raw 32-byte Ed25519
    // keys — NOT PEM. Multiple entries let a key rotation ("v2") verify
    // alongside the still-pinned "v1" without a deploy race. Pin every key
    // the validator has ever signed with; never fetch+trust a key at runtime.
    'ed25519_public_keys' => [
        'v1' => '',
    ],

    // RSA fallback (PEM), used only when a response's "alg" is "rsa" —
    // e.g. a host runtime without ext-sodium. Same key_id namespace.
    'rsa_public_keys' => [
        'v1' => '',
    ],

    // Base URL only — LicenseInstaller appends /plugin/{eclassify|universal-plugins}/{slug}.
    'validator_url' => 'https://validator.wrteam.in',

    // Catalog endpoint (spec §4.1). Base URL only — MarketplaceCatalogService
    // appends /api/plugins.
    'marketplace_url' => 'https://validator.wrteam.in/api/plugins',

    // Per-product shared key sent as X-Product-Key on the catalog call
    // (spec §3.5) — validator rejects the request without it.
    'product_key' => 'eclassify',

    // Live call skew guard: reject a freshly-fetched token whose issued_at
    // is further than this from "now". Not applied when reading a cached
    // token back out of the DB — only right after a network round-trip.
    'issued_at_skew_seconds' => 300,

    // How long a plugin keeps working, on its last-known-good signed token,
    // after the validator becomes unreachable during a scheduled recheck.
    'offline_grace_days' => 14,
];
