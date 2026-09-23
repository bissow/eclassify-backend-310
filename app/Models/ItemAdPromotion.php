<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemAdPromotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_id',
        'user_id',
        'user_purchased_package_id',
        'promotion_type',
        'start_date',
        'end_date',
        'bump_frequency',
        'last_bumped_at',
        'status',
    ];

    protected $casts = [
        'start_date'     => 'datetime',
        'end_date'       => 'datetime',
        'last_bumped_at' => 'datetime',
    ];

    protected $appends = [
        'is_active',
        'type_title',
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function user_purchased_package()
    {
        return $this->belongsTo(UserPurchasedPackage::class);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = Carbon::now();
        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }

    public function getTypeTitleAttribute(): string
    {
        return match ($this->promotion_type) {
            'daily_bump_up' => __('Daily Bump Up'),
            'top_ad'        => __('Top Ad'),
            'spotlight'     => __('Spotlight'),
            default         => ucfirst(str_replace('_', ' ', $this->promotion_type)),
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        $now = Carbon::now();
        return $query->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            });
    }

    public function scopeTopAds($query)
    {
        return $query->active()->where('promotion_type', 'top_ad');
    }

    public function scopeSpotlight($query)
    {
        return $query->active()->where('promotion_type', 'spotlight');
    }

    public function scopeDailyBump($query)
    {
        return $query->active()->where('promotion_type', 'daily_bump_up');
    }
}
