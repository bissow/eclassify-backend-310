<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferPointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'points',
        'transaction_type',
        'type',
        'remark',
        'package_original_price',
        'package_discounted_price',
        'points_used',
        'points_remaining_after',
        'final_payment_amount',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'points' => 'integer',
        'package_original_price' => 'integer',
        'package_discounted_price' => 'float',
        'points_used' => 'integer',
        'points_remaining_after' => 'integer',
        'final_payment_amount' => 'double',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
