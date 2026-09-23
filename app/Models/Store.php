<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'email',
        'contact',
        'country_code',
        'address',
        'latitude',
        'longitude',
        'country',
        'state',
        'city',
        'area_id',
        'website',
        'tax_number',
        'opening_time',
        'closing_time',
        'working_days',
        'social_links',
        'status',
        'is_verified',
    ];

    protected $casts = [
        'working_days'  => 'array',
        'social_links'  => 'array',
        'is_verified'   => 'boolean',
        'latitude'      => 'float',
        'longitude'     => 'float',
    ];

    protected $appends = [
        'logo_url',
        'banner_url',
    ];

    // Accessors
    public function getLogoAttribute($image)
    {
        if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL)) {
            return url(Storage::url($image));
        }
        return $image;
    }

    public function getBannerAttribute($image)
    {
        if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL)) {
            return url(Storage::url($image));
        }
        return $image;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner;
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'user_id', 'user_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(SellerRating::class, 'seller_id', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeSearch($query, $search)
    {
        $searchParam = '%' . trim($search) . '%';
        return $query->where(function ($q) use ($searchParam) {
            $q->where('name', 'LIKE', $searchParam)
              ->orWhere('description', 'LIKE', $searchParam)
              ->orWhere('address', 'LIKE', $searchParam)
              ->orWhere('city', 'LIKE', $searchParam)
              ->orWhere('state', 'LIKE', $searchParam)
              ->orWhere('country', 'LIKE', $searchParam)
              ->orWhereHas('user', function ($uq) use ($searchParam) {
                  $uq->where('name', 'LIKE', $searchParam)
                     ->orWhere('email', 'LIKE', $searchParam)
                     ->orWhere('mobile', 'LIKE', $searchParam);
              });
        });
    }

    /**
     * Scope to calculate distance from coordinates and filter by radius
     */
    public function scopeNearby($query, $latitude, $longitude, $radiusKm = null)
    {
        if ($latitude === null || $longitude === null) {
            return $query;
        }

        $haversine = '(6371 * acos(least(1.0, greatest(-1.0, cos(radians(?))
            * cos(radians(latitude))
            * cos(radians(longitude) - radians(?))
            + sin(radians(?)) * sin(radians(latitude))))))';

        $query->select('stores.*')
              ->selectRaw("$haversine AS distance", [$latitude, $longitude, $latitude])
              ->whereNotNull('latitude')
              ->whereNotNull('longitude')
              ->where('latitude', '!=', 0)
              ->where('longitude', '!=', 0);

        if ($radiusKm !== null && $radiusKm > 0) {
            $query->whereRaw("$haversine <= ?", [$latitude, $longitude, $latitude, $radiusKm]);
        }

        return $query->orderBy('distance', 'asc');
    }
}
