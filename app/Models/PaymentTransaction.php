<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentTransaction extends Model {
    protected $fillable = [
        'user_id',
        'package_id',
        'amount',
        'original_price',
        'discount_price',
        'payment_gateway',
        'order_id',
        'payment_status',
        'created_at',
        'updated_at',
        'payment_receipt',
        'refer_points_used',
    ];
    use HasFactory;
    protected $appends = ['payment_status_upper'];
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function package() {
        return $this->belongsTo(Package::class);
    }

    public function scopeSearch($query, $search) {
        $search = "%" . $search . "%";
        return $query->where(function ($q) use ($search) {
            $q->orWhere('id', 'LIKE', $search)
                ->orWhere('payment_gateway', 'LIKE', $search)
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'LIKE', $search);
                });
        });
    }
    public function getPaymentReceiptAttribute($value)
    {
        if (!empty($value)) {
            return url(Storage::url($value));
        }
        return $value;
    }
    public function getPaymentStatusUpperAttribute()
    {
        $value = ucfirst($this->attributes['payment_status'] ?? '');
        return $value;
    }
}
