<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'promotion_id',
        'item_id',
        'user_id',
        'user_purchased_package_id',
        'promotional_price',
        'discount_value',
        'discount_type',
        'stock_quantity',
        'remaining_stock_quantity',
        'valid_until',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'promotional_price'        => 'float',
        'discount_value'           => 'float',
        'stock_quantity'           => 'integer',
        'remaining_stock_quantity' => 'integer',
        'valid_until'              => 'datetime',
    ];

    protected $appends = [
        'is_available',
        'is_expired',
        'discount_percentage',
        'seconds_remaining',
    ];

    // Relationships
    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

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
    public function getIsAvailableAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->remaining_stock_quantity <= 0) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }

    public function getIsExpiredAttribute(): bool
    {
        if ($this->valid_until && $this->valid_until->isPast()) {
            return true;
        }
        return false;
    }

    public function getDiscountPercentageAttribute(): float
    {
        if ($this->discount_type === 'percentage') {
            return (float) $this->discount_value;
        }

        $itemPrice = (float) ($this->item->price ?? 0);
        if ($itemPrice > 0 && $this->promotional_price < $itemPrice) {
            return round((($itemPrice - $this->promotional_price) / $itemPrice) * 100, 2);
        }

        return 0;
    }

    public function getSecondsRemainingAttribute(): int
    {
        if (!$this->valid_until) {
            // Fallback to promotion's seconds remaining if available
            return $this->relationLoaded('promotion') && $this->promotion
                ? $this->promotion->seconds_remaining
                : 0;
        }

        $now = Carbon::now();
        if ($now->greaterThanOrEqualTo($this->valid_until)) {
            return 0;
        }

        return (int) $now->diffInSeconds($this->valid_until);
    }

    /**
     * Decrement remaining stock and update status if sold out
     */
    public function decrementStock(int $quantity = 1): bool
    {
        if ($this->remaining_stock_quantity < $quantity) {
            return false;
        }

        $this->remaining_stock_quantity -= $quantity;
        if ($this->remaining_stock_quantity <= 0) {
            $this->remaining_stock_quantity = 0;
            $this->status = 'sold_out';
        }
        return $this->save();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
            ->where('remaining_stock_quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>', Carbon::now());
            });
    }

    public function scopeOwner($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
