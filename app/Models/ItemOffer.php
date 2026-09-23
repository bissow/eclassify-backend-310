<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ItemOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'seller_id',
        'buyer_id',
        'amount',
        'deleted_by_seller_at',
        'deleted_by_buyer_at',
        'cleared_by_seller_at',
        'cleared_by_buyer_at',
    ];
    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function seller()
    {
        return $this->belongsTo(User::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class);
    }

    public function chat()
    {
        return $this->hasMany(Chat::class, 'item_offer_id');
    }

    public function sellerChat()
    {
        return $this->hasMany(Chat::class, 'item_offer_id')
            ->whereRaw('chats.created_at > COALESCE((SELECT io.cleared_by_seller_at FROM item_offers AS io WHERE io.id = chats.item_offer_id), \'1970-01-01\')')->latest();
    }

    public function buyerChat()
    {
        return $this->hasMany(Chat::class, 'item_offer_id')
            ->whereRaw('chats.created_at > COALESCE((SELECT io.cleared_by_buyer_at FROM item_offers AS io WHERE io.id = chats.item_offer_id), \'1970-01-01\')')->latest();
    }

    // public function scopeOwner($query)
    // {
    //     return $query->where('seller_id', Auth::user()->id)->orWhere('buyer_id', Auth::user()->id);
    // }

    public function scopeOwner($query)
    {
        return $query->where(function ($q) {
            $q->where('seller_id', Auth::id())
                ->orWhere('buyer_id', Auth::id());
        });
    }
}
