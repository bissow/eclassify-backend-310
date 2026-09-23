<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Promotion extends Model
{
    use HasFactory, SoftDeletes, ManageTranslations;

    protected $fillable = [
        'campaign_id',
        'title',
        'slug',
        'description',
        'banner_image',
        'promotion_type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'frequency',
        'discount',
        'discount_type',
        'status',
        'priority',
        'is_countdown_enabled',
        'max_items_per_user',
        'metadata',
    ];

    protected $casts = [
        'start_date'           => 'date',
        'end_date'             => 'date',
        'discount'             => 'float',
        'priority'             => 'integer',
        'is_countdown_enabled' => 'boolean',
        'max_items_per_user'   => 'integer',
        'metadata'             => 'array',
    ];

    protected $appends = [
        'translated_title',
        'translated_description',
        'banner_image_url',
        'is_currently_running',
        'seconds_remaining',
        'formatted_end_datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($promotion) {
            if (empty($promotion->slug)) {
                $baseSlug = Str::slug($promotion->title ?: 'promotion');
                $slug = $baseSlug;
                $count = 1;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$count}";
                    $count++;
                }
                $promotion->slug = $slug;
            }
        });
    }

    // Relationships
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function promotion_items()
    {
        return $this->hasMany(PromotionItem::class);
    }

    public function active_items()
    {
        return $this->hasMany(PromotionItem::class)
            ->where('status', 'active')
            ->where('remaining_stock_quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>', Carbon::now());
            });
    }

    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    // Accessors
    public function getBannerImageUrlAttribute()
    {
        if (!empty($this->banner_image)) {
            if (filter_var($this->banner_image, FILTER_VALIDATE_URL)) {
                return $this->banner_image;
            }
            return url(Storage::url($this->banner_image));
        }
        return null;
    }

    public function getTranslatedTitleAttribute()
    {
        return $this->getTranslatedValue('title', $this->title);
    }

    public function getTranslatedDescriptionAttribute()
    {
        return $this->getTranslatedValue('description', $this->description);
    }

    /**
     * Resolves exact end datetime for this promotion
     */
    public function getEndDateTime(): Carbon
    {
        $endDateStr = $this->end_date ? $this->end_date->format('Y-m-d') : Carbon::today()->format('Y-m-d');
        $endTimeStr = $this->end_time ?: '23:59:59';

        if ($this->promotion_type === 'deal_of_the_day') {
            // Deals of the day end at midnight of today
            return Carbon::today()->endOfDay();
        }

        return Carbon::parse("{$endDateStr} {$endTimeStr}");
    }

    /**
     * Resolves exact start datetime for this promotion
     */
    public function getStartDateTime(): Carbon
    {
        $startDateStr = $this->start_date ? $this->start_date->format('Y-m-d') : Carbon::today()->format('Y-m-d');
        $startTimeStr = $this->start_time ?: '00:00:00';

        if ($this->promotion_type === 'deal_of_the_day') {
            return Carbon::today()->startOfDay();
        }

        return Carbon::parse("{$startDateStr} {$startTimeStr}");
    }

    public function getIsCurrentlyRunningAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = Carbon::now();
        $start = $this->getStartDateTime();
        $end = $this->getEndDateTime();

        return $now->between($start, $end);
    }

    public function getSecondsRemainingAttribute(): int
    {
        $now = Carbon::now();
        $end = $this->getEndDateTime();

        if ($now->greaterThanOrEqualTo($end)) {
            return 0;
        }

        return (int) $now->diffInSeconds($end);
    }

    public function getFormattedEndDatetimeAttribute(): string
    {
        return $this->getEndDateTime()->toIso8601String();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrentlyRunning($query)
    {
        $today = Carbon::today()->format('Y-m-d');
        return $query->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    public function scopeFlashSales($query)
    {
        return $query->where('promotion_type', 'flash_sale');
    }

    public function scopeClearanceSales($query)
    {
        return $query->where('promotion_type', 'clearance_sale');
    }

    public function scopeDealsOfTheDay($query)
    {
        return $query->where('promotion_type', 'deal_of_the_day');
    }

    public function scopeSearch($query, $search)
    {
        $search = "%{$search}%";
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', $search)
              ->orWhere('description', 'LIKE', $search)
              ->orWhere('slug', 'LIKE', $search)
              ->orWhere('promotion_type', 'LIKE', $search);
        });
    }
}
