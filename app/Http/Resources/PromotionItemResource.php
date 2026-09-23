<?php

namespace App\Http\Resources;

use App\Models\Language;
use App\Models\Setting;
use App\Services\CurrencyFormatterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $formatter = app(CurrencyFormatterService::class);
        $contentLangCode = $request->header('Content-Language') ?? app()->getLocale();
        $currentLangId = Language::where('code', $contentLangCode)->value('id') ?? 1;

        $currencyData = [
            'currency_code'   => Setting::getValue('currency_code'),
            'currency_symbol' => Setting::getValue('currency_symbol'),
        ];

        $itemData = null;
        if ($this->relationLoaded('item') && $this->item) {
            $item = $this->item;

            $name = $item->name;
            $description = $item->description;

            if ($item->relationLoaded('translations')) {
                foreach ($item->translations as $t) {
                    if ($t->language_id == $currentLangId) {
                        if ($t->key === 'name') {
                            $name = $t->value;
                        } elseif ($t->key === 'description') {
                            $description = $t->value;
                        }
                    }
                }
            }

            $itemData = [
                'id'              => $item->id,
                'name'            => $name,
                'slug'            => $item->slug,
                'description'     => $description,
                'image'           => $item->image,
                'gallery_images'  => $item->relationLoaded('gallery_images') ? $item->gallery_images : [],
                'original_price'  => (float) $item->price,
                'formatted_original_price' => $formatter->formatPrice($item->price, $item->currency),
                'city'            => $item->city,
                'state'           => $item->state,
                'country'         => $item->country,
                'address'         => $item->address,
                'latitude'        => $item->latitude,
                'longitude'       => $item->longitude,
                'distance'        => isset($item->distance) ? round((float) $item->distance, 2) : null,
                'category'        => $item->relationLoaded('category') && $item->category ? [
                    'id'    => $item->category->id,
                    'name'  => $item->category->name,
                    'slug'  => $item->category->slug,
                    'image' => $item->category->image,
                ] : null,
                'user'            => $item->relationLoaded('user') && $item->user ? [
                    'id'          => $item->user->id,
                    'name'        => $item->user->name,
                    'profile'     => $item->user->profile,
                    'is_verified' => (bool) $item->user->is_verified,
                    'has_store'   => (bool) $item->user->has_store,
                ] : null,
            ];
        }

        return [
            'id'                        => $this->id,
            'promotion_id'              => $this->promotion_id,
            'item_id'                   => $this->item_id,
            'user_id'                   => $this->user_id,
            'promotional_price'         => (float) $this->promotional_price,
            'formatted_promotional_price' => $formatter->formatPrice($this->promotional_price, $this->item?->currency),
            'discount_value'            => (float) $this->discount_value,
            'discount_type'             => $this->discount_type,
            'discount_percentage'       => $this->discount_percentage,
            'stock_quantity'            => $this->stock_quantity,
            'remaining_stock_quantity'  => $this->remaining_stock_quantity,
            'valid_until'               => $this->valid_until?->toIso8601String(),
            'seconds_remaining'         => $this->seconds_remaining,
            'status'                    => $this->status,
            'is_available'              => $this->is_available,
            'is_expired'                => $this->is_expired,
            'item'                      => $itemData,
            'promotion'                 => $this->relationLoaded('promotion') && $this->promotion ? [
                'id'             => $this->promotion->id,
                'title'          => $this->promotion->translated_title,
                'slug'           => $this->promotion->slug,
                'promotion_type' => $this->promotion->promotion_type,
                'banner_image'   => $this->promotion->banner_image_url,
            ] : null,
            'created_at'                => $this->created_at?->toIso8601String(),
        ];
    }
}
