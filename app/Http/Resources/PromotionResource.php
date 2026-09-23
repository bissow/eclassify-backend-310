<?php

namespace App\Http\Resources;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $contentLangCode = $request->header('Content-Language') ?? app()->getLocale();
        $currentLangId = Language::where('code', $contentLangCode)->value('id') ?? 1;

        $title = $this->title;
        $description = $this->description;

        if ($this->relationLoaded('translations')) {
            foreach ($this->translations as $t) {
                if ($t->language_id == $currentLangId) {
                    if ($t->key === 'title') {
                        $title = $t->value;
                    } elseif ($t->key === 'description') {
                        $description = $t->value;
                    }
                }
            }
        }

        return [
            'id'                     => $this->id,
            'campaign_id'            => $this->campaign_id,
            'campaign_title'         => $this->relationLoaded('campaign') && $this->campaign ? $this->campaign->translated_title : null,
            'title'                  => $title,
            'slug'                   => $this->slug,
            'description'            => $description,
            'banner_image'           => $this->banner_image_url,
            'promotion_type'         => $this->promotion_type,
            'promotion_type_label'   => match($this->promotion_type) {
                'flash_sale'      => __('Flash Sale'),
                'clearance_sale'  => __('Stock Clearance Sale'),
                'deal_of_the_day' => __('Deal of the Day'),
                default           => ucfirst(str_replace('_', ' ', $this->promotion_type)),
            },
            'start_date'             => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date'               => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'start_time'             => $this->start_time,
            'end_time'               => $this->end_time,
            'frequency'              => $this->frequency,
            'discount'               => $this->discount,
            'discount_type'          => $this->discount_type,
            'status'                 => $this->status,
            'priority'               => $this->priority,
            'is_currently_running'   => $this->is_currently_running,
            'seconds_remaining'      => $this->seconds_remaining,
            'formatted_end_datetime' => $this->formatted_end_datetime,
            'is_countdown_enabled'   => $this->is_countdown_enabled,
            'max_items_per_user'     => $this->max_items_per_user,
            'items_count'            => $this->whenLoaded('promotion_items', fn() => $this->promotion_items->count()),
            'active_items_count'     => $this->whenLoaded('active_items', fn() => $this->active_items->count()),
            'created_at'             => $this->created_at?->toIso8601String(),
        ];
    }
}
