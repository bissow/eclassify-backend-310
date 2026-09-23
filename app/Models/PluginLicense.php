<?php

namespace App\Models;

use App\Services\Payment\WebhookVerifiable;
use App\Services\Plugin\PluginManifest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PluginLicense extends Model
{
    protected $fillable = [
        'plugin_slug',
        'type',
        'version',
        'purchase_code_hash',
        'purchase_code_encrypted',
        'domain',
        'scope',
        'verified_at',
        'revoked',
        'is_enabled',
        'signature',
        'payload_b64',
        'key_id',
        'alg',
        'issued_at',
        'expires_hint_at',
        'recheck_failing_since',
        'grace_expired',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'is_enabled' => 'boolean',
        'verified_at' => 'datetime',
        'issued_at' => 'datetime',
        'expires_hint_at' => 'datetime',
        'recheck_failing_since' => 'datetime',
        'grace_expired' => 'boolean',
    ];

    public function manifest(): array
    {
        return PluginManifest::read($this->plugin_slug) ?? [];
    }

    public function settingsFields(): array
    {
        return $this->manifest()['settings_fields'] ?? [];
    }

    public function displayName(): string
    {
        return $this->manifest()['name'] ?? $this->plugin_slug;
    }

    public function isUsable(): bool
    {
        return $this->is_enabled && ! $this->revoked;
    }

    public function supportsWebhook(): bool
    {
        $class = $this->manifest()['main_class'] ?? null;

        return $class && is_a($class, WebhookVerifiable::class, true);
    }

    protected $hidden = [
        'purchase_code_encrypted',
        'purchase_code_hash',
        'signature',
        'payload_b64',
    ];

    public function getPurchaseCodeAttribute(): string
    {
        return Crypt::decryptString($this->purchase_code_encrypted);
    }
}
