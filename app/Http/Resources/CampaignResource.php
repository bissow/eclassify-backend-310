<?php

namespace App\Http\Resources;

use App\Models\Language;
use App\Services\HelperService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            'id'                  => $this->id,
            'title'               => $title,
            'slug'                => $this->slug,
            'description'         => $description,
            'banner_image'        => $this->banner_image_url,
            'start_date'          => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date'            => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'status'              => $this->status,
            'is_active'           => $this->is_active,
            'priority'            => $this->priority,
            'promotions_count'    => $this->whenLoaded('promotions', fn() => $this->promotions->count()),
            'promotions'          => PromotionResource::collection($this->whenLoaded('promotions')),
            'active_promotions'   => PromotionResource::collection($this->whenLoaded('active_promotions')),
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
