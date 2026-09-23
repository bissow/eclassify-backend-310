<?php

namespace App\Models;

use App\Services\FileService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BannerAd extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            if ($model->image) {
                FileService::delete($model->getRawOriginal('image'));
            }
        });
    }

    protected $table = 'banner_ads';

    protected $fillable = [
        'title',
        'platform',
        'page',
        'layout',
        'group_id',
        'position',
        'image',
        'ad_type',
        'link',
        'category_id',
        'advertisement_id',
        'section_key',
        'home_screen_section_id',
        'feature_section_id',
        'detail_page_section',
        'listing_page_section',
        'placement',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'position' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function advertisement()
    {
        return $this->belongsTo(Item::class, 'advertisement_id');
    }

    public function featureSection()
    {
        return $this->belongsTo(FeatureSection::class, 'feature_section_id');
    }

    public function getImageAttribute($value)
    {
        return !empty($value) ? url(Storage::url($value)) : $value;
    }

    public function scopeGroup($query, string $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeSearch($query, $search)
    {
        $search = '%' . $search . '%';
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', $search)
                ->orWhere('platform', 'LIKE', $search)
                ->orWhere('page', 'LIKE', $search)
                ->orWhere('layout', 'LIKE', $search)
                ->orWhere('ad_type', 'LIKE', $search);
        });
    }
}
