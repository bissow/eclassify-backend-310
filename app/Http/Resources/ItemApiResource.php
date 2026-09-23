<?php

namespace App\Http\Resources;

use App\Models\City;
use App\Models\Language;
use App\Models\SellerRating;
use App\Models\Setting;
use App\Services\ContentFormatterService;
use App\Services\CurrencyFormatterService;
use App\Services\HelperService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ItemApiResource extends ResourceCollection
{
    protected bool $detail = false;

    protected bool $myItem = false;

    protected bool $single = false;

    protected bool $myPurchased = false;

    public function asDetail(): static
    {
        $this->detail = true;

        return $this;
    }

    public function asSingle(): static
    {
        $this->single = true;

        return $this;
    }

    public function asMyItem(): static
    {
        $this->myItem = true;

        return $this;
    }

    public function asMyPurchased(): static
    {
        $this->myPurchased = true;

        return $this;
    }

    public function toArray(Request $request)
    {
        $formatter = app(CurrencyFormatterService::class);

        $contentLangCode = $request->header('Content-Language') ?? app()->getLocale();
        $currentLangId = Language::where('code', $contentLangCode)->value('id') ?? 1;
        $defaultLangId = HelperService::getDefaultLanguageId() ?? 1;

        $data = [];

        $cityNames = $this->collection->pluck('city')->filter()->unique()->values();
        $cityLookup = [];
        if ($cityNames->isNotEmpty()) {
            City::with(['translations', 'state.translations', 'country.translations'])
                ->whereIn('name', $cityNames)
                ->get()
                ->each(function ($city) use (&$cityLookup) {
                    $key = $city->name.'|'.($city->state->name ?? '');
                    $cityLookup[$key] = $city;
                });
        }

        foreach ($this->collection as $item) {
            $isJobCategory = (bool) ($item->category->is_job_category ?? false);

            $city = $cityLookup[$item->city.'|'.$item->state] ?? null;

            $name = $item->name;
            $description = ContentFormatterService::cleanPlainText($item->description);
            $descriptionJson = $item->description_json ?: $description;

            if ($item->relationLoaded('translations')) {
                foreach ($item->translations as $t) {
                    if ($t->language_id != $currentLangId) {
                        continue;
                    }
                    if ($t->key === 'name') {
                        $name = $t->value;
                    } elseif ($t->key === 'description') {
                        $description = ContentFormatterService::cleanPlainText($t->value);
                    } elseif ($t->key === 'description_json') {
                        $descriptionJson = $t->value;
                    }
                }
            }

            $cityName = $city->translated_name ?? $item->city;
            $stateName = $city->state->translated_name ?? $item->state;
            $countryName = $city->country->translated_name ?? $item->country;
            $address = $cityName.', '.$stateName.', '.$countryName;
            $translation = [
                'name' => $name,
                'description' => $description,
                'description_json' => $descriptionJson,
                'descriptionJson' => $descriptionJson,
                'formatted_description' => ContentFormatterService::formatItemDescription($descriptionJson ?: $description),
                'city' => $cityName,
                'state' => $stateName,
                'country' => $countryName,
                'address' => $address,
            ];

            $isFeature = false;
            if ($item->status == 'approved' && $item->relationLoaded('featured_items')) {
                $isFeature = $item->featured_items->isNotEmpty();
            }

            $formattedPrice = $isJobCategory ? null : $formatter->formatPrice($item->price, $item->currency);
            $formattedSalary = $isJobCategory ? $formatter->formatSalaryRange($item->min_salary, $item->max_salary, $item->currency) : null;
            $currencyData = $item->currency ?: [
                'iso_code' => Setting::getValue('currency_code'),
                'name' => Setting::getValue('currency_name'),
                'symbol' => Setting::getValue('currency_symbol'),
                'symbol_position' => Setting::getValue('currency_symbol_position'),
                'decimal_places' => Setting::getValue('decimal_places'),
                'thousand_separator' => Setting::getValue('thousand_separator'),
                'decimal_separator' => Setting::getValue('decimal_separator'),
            ];
            $isLiked = $item->relationLoaded('favourites') && Auth::guard('sanctum')->check() ? $item->favourites->where('user_id', Auth::id())->isNotEmpty() : false;

            if ($this->detail) {
                $row = [
                    'id' => $item->id,
                    'slug' => $item->slug,
                    'name' => $name,
                    'description' => $description,
                    'description_json' => $descriptionJson,
                    'descriptionJson' => $descriptionJson,
                    'formatted_description' => ContentFormatterService::formatItemDescription($descriptionJson ?: $description),
                    'extracted_contacts' => ContentFormatterService::extractIndianMobileNumbers($descriptionJson ?: $description),
                    'extracted_links' => ContentFormatterService::extractUrls($descriptionJson ?: $description),
                    'image' => $item->image,
                    'price' => $item->price,
                    'formatted_price' => $formattedPrice,
                    'formatted_salary_range' => $formattedSalary,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                    'published_at' => Carbon::parse($item->published_at),
                    'address' => $item->address,
                    'contact' => Auth::guard('sanctum')->check() ? $item->contact : '',
                    'region_code' => $item->region_code,
                    'country_code' => $item->country_code,
                    'item_video' => $item->relationLoaded('itemVideo') ? $item->itemVideo : null,
                    'user' => $item->relationLoaded('user') && $item->user
                        ? (function () use ($item) {
                            // Detail branch is only used for a single item, so these two
                            // queries run once per request (no N+1). If this ever gets used
                            // for a list, switch to loadCount/loadAvg on the users collection.
                            $ratingsQuery = SellerRating::where('seller_id', $item->user->id);
                            $user = $item->user->toArray();
                            $user['average_rating'] = $ratingsQuery->clone()->avg('ratings');
                            $user['reviews_count'] = $ratingsQuery->clone()->count();
                            return $user;
                        })()
                        : null,
                    'gallery_images' => $item->relationLoaded('gallery_images') ? $item->gallery_images : null,
                    'category' => $item->relationLoaded('category') ? $item->category : null,
                    'is_feature' => $isFeature,
                    'created_at' => $item->created_at,
                    'item_type' => $item->item_type,
                    'user_id' => $item->user_id,
                    'offer_item_id' => $item->relationLoaded('item_offers') ? $item->item_offers->first()?->id : null,
                    'is_already_offered' => $item->relationLoaded('item_offers') && Auth::guard('sanctum')->check()
                        ? $item->item_offers->where('buyer_id', Auth::id())->isNotEmpty()
                        : false,
                    'is_already_job_applied' => $item->relationLoaded('job_applications') && Auth::guard('sanctum')->check()
                        ? $item->job_applications->where('user_id', Auth::id())->isNotEmpty()
                        : false,
                    'is_already_reported' => $item->relationLoaded('user_reports') && Auth::guard('sanctum')->check()
                        ? $item->user_reports->where('user_id', Auth::id())->isNotEmpty()
                        : false,
                    'is_purchased' => Auth::guard('sanctum')->check() && $item->sold_to == Auth::id(),
                    'sold_to' => $item->sold_to,
                    'review' => $item->relationLoaded('review') ? $item->review : null,
                    'is_already_reviewed' => $item->relationLoaded('review') && Auth::guard('sanctum')->check()
                        ? $item->review->where('buyer_id', Auth::id())->isNotEmpty()
                        : false,
                    'rejected_reason' => $item->rejected_reason,
                    'admin_edit_reason' => $item->admin_edit_reason,
                    'translated_item' => [
                        'name' => $translation['name'],
                        'description' => $translation['description'],
                        'address' => $translation['address'],
                        'city' => $translation['city'],
                        'state' => $translation['state'],
                        'country' => $translation['country'],
                    ],
                    'all_translated_custom_fields' => $item->relationLoaded('item_custom_field_values')
                        ? $this->buildAllTranslatedCustomFields($item, $defaultLangId)
                        : [],
                    'currency' => $currencyData,
                    'translated_schema' => $item->relationLoaded('seoDetail') ? $item->seoDetail?->translated_schema : null,
                    'translated_meta_title' => $item->relationLoaded('seoDetail') ? $item->seoDetail?->translated_meta_title : null,
                    'translated_meta_description' => $item->relationLoaded('seoDetail') ? $item->seoDetail?->translated_meta_description : null,
                    'translated_meta_keywords' => $item->relationLoaded('seoDetail') ? $item->seoDetail?->translated_meta_keywords : null,
                    'seo_details' => $item->relationLoaded('seoDetail') ? $item->seoDetail : null,
                ];

                if ($this->myItem) {
                    $row['item_type'] = $item->item_type;
                    $row['price'] = $item->price;
                    $row['min_salary'] = $item->min_salary;
                    $row['max_salary'] = $item->max_salary;
                    $row['total_likes'] = $item->relationLoaded('favourites') ? $item->favourites->count() : 0;
                    $row['views'] = $item->clicks;
                    $row['status'] = $item->status;
                    $row['translations'] = $item->relationLoaded('translations') ? $item->translations : null;
                    $row['rejected_reason'] = $item->rejected_reason;
                    $row['area'] = $item->relationLoaded('area') ? ($item->area->translated_name ?? $item->area->name ?? null) : null;
                    $row['city'] = $translation['city'];
                    $row['state'] = $translation['state'];
                    $row['country'] = $translation['country'];
                    $row['is_edited_by_admin'] = (bool) $item->is_edited_by_admin;
                    $row['admin_edit_reason'] = $item->admin_edit_reason;
                    $row['currency'] = $currencyData;
                    $row['seo_detail'] = $item->relationLoaded('seoDetail') ? $item->seoDetail : null;
                    $row['clicks'] = $item->clicks;
                    $row['translations'] = $item->relationLoaded('translations') ? $item->translations : null;
                    $row['custom_fields'] = $item->relationLoaded('item_custom_field_values') ? $this->buildCustomFields($item, $currentLangId, $defaultLangId) : [];
                } else {
                    $row['is_liked'] = $isLiked;
                }

                $activePromotions = $item->active_promotions;
                $row['active_promotions'] = $activePromotions;
                $row['active_promotion_item'] = !empty($activePromotions['sales']) ? $activePromotions['sales'][0] : null;
                $row['is_spotlight'] = $activePromotions['is_spotlight'] ?? false;
                $row['is_top_ad'] = $activePromotions['is_top_ad'] ?? false;
            } else {
                $activePromotions = $item->active_promotions;
                $row = [
                    'id' => $item->id,
                    'slug' => $item->slug,
                    'user_id' => $item->user_id,
                    'image' => $item->image,
                    'is_feature' => $isFeature,
                    'formatted_salary_range' => $formattedSalary,
                    'formatted_price' => $formattedPrice,
                    'translation' => $translation,
                    'is_liked' => $isLiked,
                    'published_at' => Carbon::parse($item->published_at),
                    'is_my_listing' => (bool) ($item->user_id === Auth::guard('sanctum')->id()),
                    'price' => $item->price,
                    'active_promotions' => $activePromotions,
                    'active_promotion_item' => !empty($activePromotions['sales']) ? $activePromotions['sales'][0] : null,
                    'is_spotlight' => $activePromotions['is_spotlight'] ?? false,
                    'is_top_ad' => $activePromotions['is_top_ad'] ?? false,
                    'item_type' => $item->item_type ?? 'normal',
                ];
                if ($this->myItem) {
                    $row['status'] = $item->status;
                    $row['item_type'] = $item->item_type;
                    $row['is_edited_by_admin'] = $item->is_edited_by_admin;
                    $row['views'] = $item->clicks;
                    $row['likes'] = $item->favourites->count();
                    $row['is_my_listing'] = true;
                }
                if ($item->relationLoaded('user')) {
                    $row['user'] = $item->user;
                }
                if ($item->relationLoaded('review')) {
                    $row['review'] = $item->review->first();
                    $row['is_already_reviewed'] = Auth::guard('sanctum')->check()
                        ? $item->review->where('buyer_id', Auth::id())->isNotEmpty()
                        : false;
                }
                if ($this->myPurchased || (Auth::guard('sanctum')->check() && $item->sold_to == Auth::id())) {
                    $row['is_purchased'] = Auth::guard('sanctum')->check() && $item->sold_to == Auth::id();
                    $row['sold_to'] = $item->sold_to;
                }
            }

            $data[] = $row;
        }

        if ($this->detail || $this->single) {
            return Arr::first($data, null, []);
        }

        if ($this->resource instanceof AbstractPaginator) {
            return [
                ...$this->resource->toArray(),
                'data' => $data,
            ];
        }

        return $data;
    }

    protected function buildCustomFields($item, $currentLangId, $defaultLangId): array
    {
        $result = [];

        $grouped = $item->item_custom_field_values->groupBy('custom_field_id');

        foreach ($grouped as $fieldValues) {
            $default = $fieldValues->firstWhere('language_id', $defaultLangId) ?? $fieldValues->firstWhere('language_id', null);
            $translated = $fieldValues->firstWhere('language_id', $currentLangId);
            $active = $translated ?? $default;

            if (! $active || ! $active->relationLoaded('custom_field') || empty($active->custom_field)) {
                continue;
            }

            $field = $active->custom_field;
            $tempRow = $field->toArray();

            if ($field->type === 'fileinput') {
                $tempRow['value'] = ! empty($active->value)
                    ? url(Storage::url($this->extractFilePath($active->getRawOriginal('value'))))
                    : '';
            } else {
                $tempRow['value'] = is_array($active->value) ? $active->value : json_decode($active->value, true);
            }

            if ($this->isEmptyCustomFieldValue($tempRow['value'])) {
                continue;
            }

            $result[] = $tempRow;
        }

        return $result;
    }

    protected function buildAllTranslatedCustomFields($item, $defaultLangId): array
    {
        $result = [];

        foreach ($item->item_custom_field_values as $fieldValue) {
            if (! $fieldValue->relationLoaded('custom_field') || empty($fieldValue->custom_field)) {
                continue;
            }

            $field = $fieldValue->custom_field;

            if ($field->type === 'fileinput') {
                $value = ! empty($fieldValue->value)
                    ? url(Storage::url($this->extractFilePath($fieldValue->getRawOriginal('value'))))
                    : '';
                $translatedValue = $value;
            } else {
                $value = is_array($fieldValue->value) ? $fieldValue->value : json_decode($fieldValue->value, true);
                $translatedValue = $this->translatedCustomFieldValue($field, $value);
            }

            if ($this->isEmptyCustomFieldValue($value)) {
                continue;
            }

            $result[] = [
                'id' => $field->id,
                'image' => $field->image,
                'language_id' => $fieldValue->language_id ?? $defaultLangId,
                'type' => $field->type,
                'translated_name' => $field->translated_name,
                'value' => $value,
                'translated_value' => $translatedValue,
            ];
        }

        return $result;
    }

    protected function translatedCustomFieldValue($field, $value)
    {
        $options = is_array($field->values) ? $field->values : (json_decode((string) $field->values, true) ?: []);

        if (empty($options)) {
            return $value;
        }

        $translatedRaw = $field->translated_value;
        $translatedOptions = is_array($translatedRaw) ? $translatedRaw : (json_decode((string) $translatedRaw, true) ?: []);

        if (count($translatedOptions) !== count($options)) {
            return $value;
        }

        $map = array_combine($options, $translatedOptions);

        if (is_array($value)) {
            return array_map(fn ($v) => $map[$v] ?? $v, $value);
        }

        return $map[$value] ?? $value;
    }

    protected function extractFilePath($rawValue)
    {
        if (empty($rawValue)) {
            return '';
        }

        if (json_validate($rawValue)) {
            $decoded = json_decode($rawValue, true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded[0] ?? '';
            }

            return is_string($decoded) ? $decoded : '';
        }

        return $rawValue;
    }

    protected function isEmptyCustomFieldValue($value): bool
    {
        if (is_array($value)) {
            $filtered = array_filter($value, fn ($v) => $v !== null && $v !== '');

            return count($filtered) === 0;
        }

        return $value === null || $value === '';
    }
}
