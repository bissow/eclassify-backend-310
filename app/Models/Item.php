<?php

namespace App\Models;

use App\Traits\ManageTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasFactory, SoftDeletes, ManageTranslations;

    protected static function booted()
    {
        static::forceDeleting(function ($model) {
            if ($model->seoDetail) {
                $model->seoDetail->delete();
            }
            if($model->reel){
                $model->reel->delete();
            }
        });
    }

    protected $fillable = [
        'category_id',
        'currency_id',
        'name',
        'price',
        'description',
        'latitude',
        'longitude',
        'address',
        'contact',
        'country_code',
        'show_only_to_premium',
        'video_link',
        'status',
        'rejected_reason',
        'user_id',
        'country',
        'state',
        'city',
        'area_id',
        'all_category_ids',
        'slug',
        'sold_to',
        'expiry_date',
        'min_salary',
        'max_salary',
        'is_edited_by_admin',
        'admin_edit_reason',
        'package_id',
        'region_code',
        'created_at',
        'country_code',
        'item_type',
        'published_at',
        'renewed_at',
    ];

    protected $appends = ['translated_name', 'translated_description', 'image'];

    protected $with = ['translations'];

    // Relationships
    public function reel()
    {
        return $this->hasOne(Reel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function countryRelation()
    {
        return $this->belongsTo(Country::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    public function gallery_images()
    {
        return $this->hasMany(ItemImages::class)->orderBy('is_default', 'desc')->orderBy('id', 'desc');
    }

    public function custom_fields()
    {
        return $this->hasManyThrough(
            CustomField::class, CustomFieldCategory::class,
            'category_id', 'id', 'category_id', 'custom_field_id'
        );
    }

    public function item_custom_field_values()
    {
        return $this->hasMany(ItemCustomFieldValue::class, 'item_id');
    }

    public function featured_items()
    {
        return $this->hasMany(FeaturedItems::class)->onlyActive();
    }

    public function promotion_items()
    {
        return $this->hasMany(PromotionItem::class);
    }

    public function active_promotion_items()
    {
        return $this->hasMany(PromotionItem::class)->available();
    }

    public function ad_promotions()
    {
        return $this->hasMany(ItemAdPromotion::class);
    }

    public function active_ad_promotions()
    {
        return $this->hasMany(ItemAdPromotion::class)->active();
    }

    public function favourites()
    {
        return $this->hasMany(Favourite::class);
    }

    public function item_offers()
    {
        return $this->hasMany(ItemOffer::class);
    }

    public function user_reports()
    {
        return $this->hasMany(UserReports::class);
    }

    public function sliders(): MorphMany
    {
        return $this->morphMany(Slider::class, 'model');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function review()
    {
        return $this->hasMany(SellerRating::class);
    }

    public function job_applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    // Accessors
    public function getImageAttribute($image)
    {
        if (empty($image) && $this->id) {
            if ($this->relationLoaded('gallery_images')) {
                $defaultImage = $this->gallery_images->where('is_default', 1)->first() ?? $this->gallery_images->first();
                $image = $defaultImage ? $defaultImage->getRawOriginal('image') : null;
            } else {
                $defaultImage = $this->gallery_images()->where('is_default', 1)->first() ?? $this->gallery_images()->first();
                $image = $defaultImage ? $defaultImage->getRawOriginal('image') : null;
            }
        }
        return ! empty($image) ? url(Storage::url($image)) : $image;
    }

    public function itemVideo()
    {
        return $this->hasOne(ItemVideo::class);
    }

    public function getStatusAttribute($value)
    {
        if ($this->deleted_at) {
            return 'inactive';
        }
        if ($this->expiry_date && $this->expiry_date < Carbon::now() && $value != 'sold out') {
            return 'expired';
        }

        return $value;
    }

    public function getPublishedAtAttribute($value)
    {
        if (is_null($value) && $this->getRawOriginal('status') === 'approved') {
            return $this->created_at;
        }

        return $value;
    }

    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable');
    }

    public function seoDetail(): MorphOne
    {
        return $this->morphOne(\App\Models\SeoDetail::class, 'seoable');
    }

    // Scopes
    public function scopeSearch($query, $search)
    {
        $originalSearch = $search;
        // Split the search into individual words so that extra/odd whitespace in
        // stored values (e.g. "Orange  couch") still matches a "Orange couch" query.
        $words = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function ($query) use ($words, $originalSearch) {
            foreach ($words as $word) {
                $searchParam = '%'.$word.'%';

                // Every word must match at least one of the searchable fields.
                $query->where(function ($q) use ($searchParam, $originalSearch) {
                    $q->where('id', $originalSearch)
                        ->orWhere('name', 'LIKE', $searchParam)
                        ->orWhereHas('category', function ($q) use ($searchParam) {
                            $q->where('name', 'LIKE', $searchParam);
                        })->orWhereHas('user', function ($q) use ($searchParam) {
                            $q->where('name', 'LIKE', $searchParam);
                        })->orWhereHas('translations', function ($q) use ($searchParam) {
                            $q->where('value', 'LIKE', $searchParam);
                        });
                });
            }
        });
    }

    public function scopeOwner($query)
    {
        if (Auth::user()->hasRole('User')) {
            return $query->where('user_id', Auth::user()->id);
        }

        return $query;
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeNotOwner($query)
    {
        return $query->where('user_id', '!=', Auth::user()->id);
    }

    public function scopeSort($query, $column, $order)
    {
        if ($column == 'user_name') {
            return $query->leftJoin('users', 'users.id', '=', 'items.user_id')
                ->orderBy('users.name', $order)
                ->select('items.*');
        }

        return $query->orderBy($column, $order);
    }

    public function scopeFilter($query, $filterObject)
    {
        if (empty($filterObject)) {
            return $query;
        }

        foreach ($filterObject as $column => $value) {

            if ($column === 'category_id') {

                $categoryId = (int) $value;

                // Match the selected category and ALL its descendants (any depth),
                // so selecting a parent (or mid-level sub) shows items in that
                // category and every level beneath it.
                $category = Category::find($categoryId);
                $categoryIds = $category
                    ? $category->descendantsAndSelf()->pluck('id')->toArray()
                    : [$categoryId];

                $query->whereIn('category_id', $categoryIds);

                continue; // Skip to next filter

            }
            if ($column == 'status') {

                if ($value == 'inactive') {
                    $query->whereNotNull('deleted_at')
                        ->where(function ($q) {
                            $q->whereNull('expiry_date')
                                ->orWhere('expiry_date', '>=', Carbon::now());
                        });

                } elseif ($value == 'expired') {
                    $query->whereNotNull('expiry_date')
                        ->where('expiry_date', '<', Carbon::now())
                        ->where('status', '!=', 'sold out')
                        ->whereNull('deleted_at');

                } elseif ($value == 'sold out') {
                    $query->where('status', 'sold out')->whereNull('deleted_at');
                } else {
                    if (in_array($value, [
                        'review', 'approved', 'rejected',
                        'soft rejected',
                        'permanent rejected', 'resubmitted',
                    ])) {

                        $query->whereNull('deleted_at')
                            ->where(function ($q) {
                                $q->whereNull('expiry_date')
                                    ->orWhere('expiry_date', '>=', Carbon::now());
                            });
                    }

                    $query->where($column, $value);
                }

            } elseif ($column == 'featured_status') {

                if ($value == 'featured') {
                    $query->whereHas('featured_items');
                } elseif ($value == 'premium') {
                    $query->whereDoesntHave('featured_items');
                }

            } elseif ($column === 'item_type') {

                if (in_array($value, ['normal', 'reel'])) {
                    $query->where('item_type', $value);
                }

            } elseif ($column === 'deleted_user') {
                $query->whereHas('user', function ($q) {
                    $q->onlyTrashed();
                });

            } elseif (in_array($column, ['country', 'state', 'city'])) {

                $query->where($column, 'LIKE', '%'.$value.'%');

            } else {
                $query->where((string) $column, (string) $value);
            }
        }

        return $query;
    }

    public function scopeOnlyNonBlockedUsers($query)
    {
        $blocked_user_ids = BlockUser::where('user_id', Auth::user()->id)
            ->pluck('blocked_user_id');

        return $query->whereNotIn('user_id', $blocked_user_ids);
    }

    public function scopeGetNonExpiredItems($query)
    {
        return $query->where(function ($query) {
            $query->where('expiry_date', '>', date('Y-m-d'))->orWhereNull('expiry_date');
        });
    }

    public function scopeIsJobCategory($query, $isJob = 1)
    {
        return $query->whereHas('category', function ($q) use ($isJob) {
            $q->where('is_job_category', $isJob);
        });
    }

    public function scopePriceOptional($query, $isJob = 1)
    {
        return $query->whereHas('category', function ($q) use ($isJob) {
            $q->where('price_optional', $isJob);
        });
    }

    public function getTranslatedNameAttribute()
    {
        return $this->getTranslatedValue('name', $this->name);
    }

    public function getTranslatedDescriptionAttribute()
    {
        return $this->getTranslatedValue('description', $this->description);
    }

    public function scopeTopAds($query)
    {
        return $query->whereHas('ad_promotions', function ($q) {
            $q->topAds();
        });
    }

    public function scopeSpotlight($query)
    {
        return $query->whereHas('ad_promotions', function ($q) {
            $q->spotlight();
        });
    }

    public function scopeInPromotions($query, $promotionId = null)
    {
        return $query->whereHas('promotion_items', function ($q) use ($promotionId) {
            $q->available();
            if ($promotionId) {
                $q->where('promotion_id', $promotionId);
            }
        });
    }

    public function getIsTopAdAttribute(): bool
    {
        if ($this->relationLoaded('ad_promotions')) {
            return $this->ad_promotions->where('promotion_type', 'top_ad')->where('is_active', true)->isNotEmpty();
        }
        return $this->ad_promotions()->topAds()->exists();
    }

    public function getIsSpotlightAttribute(): bool
    {
        if ($this->relationLoaded('ad_promotions')) {
            return $this->ad_promotions->where('promotion_type', 'spotlight')->where('is_active', true)->isNotEmpty();
        }
        return $this->ad_promotions()->spotlight()->exists();
    }

    public function getIsDailyBumpedAttribute(): bool
    {
        if ($this->relationLoaded('ad_promotions')) {
            return $this->ad_promotions->where('promotion_type', 'daily_bump_up')->where('is_active', true)->isNotEmpty();
        }
        return $this->ad_promotions()->dailyBump()->exists();
    }

    public function getActivePromotionsAttribute(): array
    {
        $salesItems = $this->relationLoaded('active_promotion_items')
            ? $this->active_promotion_items
            : $this->active_promotion_items()->with(['promotion.campaign', 'promotion.translations'])->get();

        $boosts = $this->relationLoaded('active_ad_promotions')
            ? $this->active_ad_promotions
            : $this->active_ad_promotions()->get();

        $formatter = app(\App\Services\CurrencyFormatterService::class);
        $promotionsList = [];
        foreach ($salesItems as $pi) {
            $promotionsList[] = [
                'id'                     => $pi->id,
                'promotion_id'           => $pi->promotion_id,
                'promotion_title'        => $pi->promotion?->translated_title ?? $pi->promotion?->title,
                'promotion_type'         => $pi->promotion?->promotion_type,
                'campaign_id'            => $pi->promotion?->campaign_id,
                'campaign_title'         => $pi->promotion?->campaign?->title,
                'campaign_slug'          => $pi->promotion?->campaign?->slug,
                'promotional_price'      => (float) $pi->promotional_price,
                'formatted_promotional_price' => $formatter->formatPrice($pi->promotional_price, $this->currency),
                'formatted_original_price'    => $formatter->formatPrice($this->price, $this->currency),
                'discount_value'         => (float) $pi->discount_value,
                'discount_type'          => $pi->discount_type,
                'discount_percentage'    => $pi->discount_percentage,
                'stock_quantity'         => $pi->stock_quantity,
                'remaining_stock_quantity' => $pi->remaining_stock_quantity,
                'claimed_count'          => max(0, $pi->stock_quantity - $pi->remaining_stock_quantity),
                'valid_until'            => $pi->valid_until?->toIso8601String(),
                'status'                 => $pi->status,
            ];
        }

        $boostsList = [];
        foreach ($boosts as $b) {
            $boostsList[] = [
                'id'             => $b->id,
                'promotion_type' => $b->promotion_type,
                'type_title'     => $b->type_title,
                'start_date'     => $b->start_date?->toIso8601String(),
                'end_date'       => $b->end_date?->toIso8601String(),
                'last_bumped_at' => $b->last_bumped_at?->toIso8601String(),
                'status'         => $b->status,
                'is_active'      => $b->is_active,
            ];
        }

        return [
            'has_active_promotions' => count($promotionsList) > 0 || count($boostsList) > 0,
            'is_top_ad'             => $this->is_top_ad,
            'is_spotlight'          => $this->is_spotlight,
            'is_daily_bumped'       => $this->is_daily_bumped,
            'sales'                 => $promotionsList,
            'boosts'                => $boostsList,
        ];
    }
}


