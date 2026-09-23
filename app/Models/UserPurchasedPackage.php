<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserPurchasedPackage extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'start_date',
        'end_date',
        'total_limit',
        'used_limit',
        'payment_transactions_id',
        'listing_duration_type' ,
        'listing_duration_days',
        'used_promotions_limit',
        'used_daily_bump_up_limit',
        'used_top_ad_limit',
        'used_spotlight_limit',
    ];

    protected $casts = [
        'used_promotions_limit'    => 'integer',
        'used_daily_bump_up_limit' => 'integer',
        'used_top_ad_limit'        => 'integer',
        'used_spotlight_limit'     => 'integer',
    ];

    protected $appends = ['remaining_days', 'remaining_item_limit', 'status'];

    public function package() {
        return $this->belongsTo(Package::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function PaymentTransaction() {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function scopeOnlyActive($query) {
        return $query->where('user_id', Auth::user()->id)->whereDate('start_date', '<=', date('Y-m-d'))->where(function ($q) {
            $q->whereDate('end_date', '>', date('Y-m-d'))->orWhereNull('end_date');
        })->where(function ($q) {
            $q->whereColumn('used_limit', '<', 'total_limit')->orWhereNull('total_limit');
        })->orderBy('end_date', 'asc');
    }

    public function getRemainingDaysAttribute() {
        if (!empty($this->end_date)) {
            $startDate = Carbon::createFromFormat('Y-m-d', $this->start_date);
            $endDate = Carbon::createFromFormat('Y-m-d', $this->end_date);
            return $startDate->diffInDays($endDate);
        }
        return "unlimited";
    }
    public function getStatusAttribute()
    {
        if (empty($this->end_date)) {
            return "Active"; // Or "Unlimited" if you prefer
        }

        $endDate = Carbon::createFromFormat('Y-m-d', $this->end_date)->startOfDay();
        $now = Carbon::now()->startOfDay();

        return $now->lessThanOrEqualTo($endDate) ? "Active" : "Expired";
    }
    public function getRemainingItemLimitAttribute() {
        if (!empty($this->total_limit)) {
            return $this->total_limit - $this->used_limit;
        }
        return "unlimited";
    }

    public function scopeSearch($query, $search) {
        $search = "%" . $search . "%";
        $query = $query->where(function ($q) use ($search) {
            $q->orWhere('user_id', 'LIKE', $search)
                ->orWhere('package_id', 'LIKE', $search)
                ->orWhere('start_date', 'LIKE', $search)
                ->orWhere('end_date', 'LIKE', $search)
                ->orWhere('total_limit', 'LIKE', $search)
                ->orWhere('used_limit', 'LIKE', $search)
                ->orWhere('created_at', 'LIKE', $search)
                ->orWhere('updated_at', 'LIKE', $search)
                ->orWhereHas('package', function ($q) use ($search) {
                    $q->where('name', 'LIKE', $search);
                })->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'LIKE', $search);
                });
        });
        return $query;
    }

    public function canAddPromotionItem(): bool
    {
        if (!$this->package || !$this->package->allowsPromotions()) {
            return false;
        }

        $limit = $this->package->promotion_item_limit;
        if (is_null($limit) || $limit === 0) {
            return true; // Unlimited
        }

        return $this->used_promotions_limit < $limit;
    }

    public function canUseDailyBump(): bool
    {
        if (!$this->package || !$this->package->allowsDailyBumpUp()) {
            return false;
        }

        $limit = $this->package->daily_bump_up_limit;
        if (is_null($limit) || $limit === 0) {
            return true;
        }

        return $this->used_daily_bump_up_limit < $limit;
    }

    public function canUseTopAd(): bool
    {
        if (!$this->package || !$this->package->allowsTopAd()) {
            return false;
        }

        $limit = $this->package->top_ad_limit;
        if (is_null($limit) || $limit === 0) {
            return true;
        }

        return $this->used_top_ad_limit < $limit;
    }

    public function canUseSpotlight(): bool
    {
        if (!$this->package || !$this->package->allowsSpotlight()) {
            return false;
        }

        $limit = $this->package->spotlight_limit;
        if (is_null($limit) || $limit === 0) {
            return true;
        }

        return $this->used_spotlight_limit < $limit;
    }
}

