<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Package extends Model {
    use HasFactory, ManageTranslations;

    protected $fillable = [
        'name',
        'price',
        'discount_in_percentage',
        'final_price',
        'duration',
        'item_limit',
        'type',
        'icon',
        'description',
        'status',
        'ios_product_id',
        'is_global',
        'key_points',
        'listing_duration_type',
        'listing_duration_days',
        'refer_max_points_usage_percentage',
        'refer_min_points_to_use',
        'refer_max_points_to_use',
        'is_reel_allowed',
        'is_discontinued',
        'discontinued_at',
        'allows_promotions',
        'promotion_item_limit',
        'allowed_promotion_types',
        'allows_daily_bump_up',
        'daily_bump_up_limit',
        'allows_top_ad',
        'top_ad_limit',
        'allows_spotlight',
        'spotlight_limit',
    ];
    protected $appends = ['translated_name', 'translated_description','translated_key_points'];
    
    /**
     * Get listing duration type, fallback to package duration if null
     * 
     * @return string|null
     */
    public function getListingDurationTypeAttribute($value)
    {
        // If listing_duration_type is null, return 'package' to indicate it uses package duration
        if ($value === null) {
            return 'package';
        }
        return $value;
    }
    
    /**
     * Get listing duration days, fallback to package duration if null and type is package
     * 
     * @return int|string|null
     */
    public function getListingDurationDaysAttribute($value)
    {
        // If listing_duration_days is null and listing_duration_type is null or 'package', use package duration
        if ($value === null || $value === '') {
            $listingDurationType = $this->attributes['listing_duration_type'] ?? null;
            if ($listingDurationType === null || $listingDurationType === '' || $listingDurationType === 'package') {
                // Use raw duration attribute to avoid infinite loop
                return $this->attributes['duration'] ?? null;
            }
        }
        return $value;
    }

    public function user_purchased_packages() {
        return $this->hasMany(UserPurchasedPackage::class);
    }

    public function payment_transactions(){
        return $this->hasMany(PaymentTransaction::class, 'package_id', 'id');
    }
    
    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'package_categories', 'package_id', 'category_id');
    }

    public function package_categories()
    {
        return $this->hasMany(PackageCategory::class);
    }

    public function getIconAttribute($icon) {
        if (!empty($icon)) {
            return url(Storage::url($icon));
        }
        return $icon;
    }

    public function scopeSearch($query, $search) {
        $search = "%" . $search . "%";
        $query = $query->where(function ($q) use ($search) {
            $q->orWhere('name', 'LIKE', $search)
                ->orWhere('price', 'LIKE', $search)
                ->orWhere('discount_in_percentage', 'LIKE', $search)
                ->orWhere('final_price', 'LIKE', $search)
                ->orWhere('duration', 'LIKE', $search)
                ->orWhere('item_limit', 'LIKE', $search)
                ->orWhere('type', 'LIKE', $search)
                ->orWhere('description', 'LIKE', $search)
                ->orWhere('status', 'LIKE', $search)
                ->orWhere('created_at', 'LIKE', $search)
                ->orWhere('updated_at', 'LIKE', $search);
        });
        return $query;
    }
    public function getTranslatedNameAttribute() {
        return $this->getTranslatedValue('name', $this->name);
    }

    public function getTranslatedDescriptionAttribute() {
        return $this->getTranslatedValue('description', $this->description);
    }

    public function scopeFilter($query, $filterObject) {
        if (!empty($filterObject)) {
            foreach ($filterObject as $column => $value) {
                if ($column == "type") {
                    $query->where('type', $value);
                } else {
                    $query->where((string)$column, (string)$value);
                }
            }
        }
        return $query;

    }

    public function getTranslatedKeyPointsAttribute()
    {
        // ---------- Default / fallback ----------
        if (!empty($this->key_points)) {
            $defaultKeyPoints = is_array($this->key_points)
                ? $this->key_points
                : (json_decode($this->key_points, true) ?? []);
        } else {
            $defaultKeyPoints = [];
        }

        // ---------- Translation ----------
        $translatedValue = $this->getTranslatedValue('key_points', null);

        if (!empty($translatedValue)) {
            $translatedKeyPoints = is_array($translatedValue)
                ? $translatedValue
                : json_decode($translatedValue, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($translatedKeyPoints)) {
                return $translatedKeyPoints;
            }
        }

        return is_array($defaultKeyPoints) ? $defaultKeyPoints : [];
    }

    protected function casts(): array
    {
        return [
            'duration'                 => 'integer',
            'listing_duration_days'    => 'integer',
            'allows_promotions'        => 'boolean',
            'promotion_item_limit'     => 'integer',
            'allowed_promotion_types'  => 'array',
            'allows_daily_bump_up'     => 'boolean',
            'daily_bump_up_limit'      => 'integer',
            'allows_top_ad'            => 'boolean',
            'top_ad_limit'             => 'integer',
            'allows_spotlight'         => 'boolean',
            'spotlight_limit'          => 'integer',
        ];
    }

    public function allowsPromotions(): bool
    {
        return (bool) ($this->allows_promotions || $this->type === 'promotional');
    }

    public function allowsDailyBumpUp(): bool
    {
        return (bool) $this->allows_daily_bump_up;
    }

    public function allowsTopAd(): bool
    {
        return (bool) $this->allows_top_ad;
    }

    public function allowsSpotlight(): bool
    {
        return (bool) $this->allows_spotlight;
    }
}

