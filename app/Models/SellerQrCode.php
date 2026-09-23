<?php

namespace App\Models;

use App\Services\CachingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SellerQrCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'seller_qr_codes';

    protected $fillable = [
        'user_id',
        'store_id',
        'qr_code_token',
        'title',
        'tagline',
        'qr_style',
        'primary_color',
        'secondary_color',
        'center_logo_type',
        'center_logo',
        'scans_count',
        'last_scanned_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'scans_count'      => 'integer',
        'last_scanned_at'  => 'datetime',
        'metadata'         => 'array',
    ];

    protected $appends = [
        'qr_url',
        'deep_link',
        'center_logo_url',
    ];

    // Accessors
    public function getCenterLogoAttribute($image): ?string
    {
        if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL)) {
            return url(Storage::url($image));
        }
        return $image;
    }

    public function getCenterLogoUrlAttribute(): ?string
    {
        return $this->center_logo;
    }

    public function getQrUrlAttribute(): string
    {
        $customBaseUrl = Setting::getValue('seller_qr_catalog_base_url');
        if (!empty($customBaseUrl)) {
            return rtrim($customBaseUrl, '/') . '/store-qr/' . $this->qr_code_token;
        }

        $webUrl = CachingService::getSystemSettings('web_url');
        if (!empty($webUrl)) {
            return rtrim($webUrl, '/') . '/store-qr/' . $this->qr_code_token;
        }
        return url('/store-qr/' . $this->qr_code_token);
    }

    public function getDeepLinkAttribute(): string
    {
        $scheme = CachingService::getSystemSettings('depp_link_scheme') ?: 'eclassify';
        return $scheme . '://store-qr/' . $this->qr_code_token;
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByToken($query, string $token)
    {
        return $query->where('qr_code_token', $token);
    }

    /**
     * Record a scan event for analytics
     */
    public function recordScan(): void
    {
        $this->increment('scans_count');
        $this->update(['last_scanned_at' => now()]);
    }
}
