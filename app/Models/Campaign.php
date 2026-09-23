<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasFactory, SoftDeletes, ManageTranslations;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'banner_image',
        'start_date',
        'end_date',
        'status',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'priority'   => 'integer',
        'metadata'   => 'array',
    ];

    protected $appends = [
        'translated_title',
        'translated_description',
        'banner_image_url',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (empty($campaign->slug)) {
                $baseSlug = Str::slug($campaign->title ?: 'campaign');
                $slug = $baseSlug;
                $count = 1;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$count}";
                    $count++;
                }
                $campaign->slug = $slug;
            }
        });
    }

    // Relationships
    public function promotions()
    {
        return $this->hasMany(Promotion::class)->orderBy('priority', 'asc');
    }

    public function active_promotions()
    {
        return $this->hasMany(Promotion::class)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->orderBy('priority', 'asc');
    }

    public function promotion_items()
    {
        return $this->hasManyThrough(PromotionItem::class, Promotion::class);
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

    public function getIsActiveAttribute(): bool
    {
        $today = Carbon::today();
        return $this->status === 'active'
            && $this->start_date <= $today
            && $this->end_date >= $today;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrentlyRunning($query)
    {
        $today = Carbon::today();
        return $query->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    public function scopeSearch($query, $search)
    {
        $search = "%{$search}%";
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', $search)
              ->orWhere('description', 'LIKE', $search)
              ->orWhere('slug', 'LIKE', $search);
        });
    }
}
