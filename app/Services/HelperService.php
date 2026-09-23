<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Category;
use App\Models\Language;
use App\Models\SeoDetail;
use App\Models\Setting;
use App\Models\Translation;
use App\Services\CachingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class HelperService
{
    public static function getDefaultLanguageId(): ?int
    {
        $code = Cache::rememberForever('global_default_language', function () {
            return Setting::where('name', 'default_language')->value('value') ?? 'en';
        });

        return Language::where('code', $code)->value('id');
    }

    public static function changeEnv($updateData = []): bool
    {
        if (count($updateData) > 0) {
            // Read .env-file
            $env = file_get_contents(base_path() . '/.env');
            // Split string on every " " and write into array
            //            $env = explode(PHP_EOL, $env);
            $env = preg_split('/\r\n|\r|\n/', $env);
            $env_array = [];
            foreach ($env as $env_value) {
                if (empty($env_value)) {
                    // Add and Empty Line
                    $env_array[] = '';

                    continue;
                }

                $entry = explode('=', $env_value, 2);
                $env_array[$entry[0]] = $entry[0] . '="' . str_replace('"', '', $entry[1]) . '"';
            }

            foreach ($updateData as $key => $value) {
                $env_array[$key] = $key . '="' . str_replace('"', '', $value) . '"';
            }
            // Turn the array back to a String
            $env = implode("\n", $env_array);

            // And overwrite the .env with the new data
            file_put_contents(base_path() . '/.env', $env);

            return true;
        }

        return false;
    }

    /**
     * @description - This function will return the nested category Option tags using in memory optimization
     */
    public static function childCategoryRendering(&$categories, int $level = 0, ?string $parentCategoryID = ''): bool
    {
        // Foreach loop only on the parent category objects
        foreach (collect($categories)->where('parent_category_id', $parentCategoryID) as $key => $category) {
            echo "<option value='$category->id'>" . str_repeat('&nbsp;', $level * 4) . '|-- ' . $category->name . '</option>';
            // Once the parent category object is rendered we can remove the category from the main object so that redundant data can be removed
            $categories->forget($key);

            // Now fetch the subcategories of the main category
            $subcategories = $categories->where('parent_category_id', $category->id);
            if (! empty($subcategories)) {
                // Finally if subcategories are available then call the recursive function & see the magic
                self::childCategoryRendering($categories, $level + 1, $category->id);
            }
        }

        return false;
    }

    public static function buildNestedChildSubcategoryObject($categories)
    {
        // Used json_decode & encode simultaneously because i wanted to convert whole nested array into object
        try {
            return json_decode(json_encode(self::buildNestedChildSubcategoryArray($categories), JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return (object) [];
        }
    }

    private static function buildNestedChildSubcategoryArray($categories)
    {
        $children = [];
        // First Add Parent Categories to root level in an array
        foreach ($categories->toArray() as $value) {
            if ($value['parent_category_id'] == '') {
                $children[] = $value;
            }
        }

        // Then loop on the Parent Category to find the children categories
        foreach ($children as $key => $value) {
            $children[$key]['subcategories'] = self::findChildCategories($categories->toArray(), $value['id']);
        }

        return $children;
    }

    public static function findChildCategories($arr, $parent)
    {
        $children = [];
        foreach ($arr as $key => $value) {
            if ($value['parent_category_id'] == $parent) {
                $children[] = $value;
            }
        }
        foreach ($children as $key => $value) {
            $children[$key]['subcategories'] = self::findChildCategories($arr, $value['id']);
        }

        return $children;
    }

    /*
     * Sagar's Code :
     * in this i have approached the reverse object moving & removing.
     * which is not working as of now.
     * but will continue working on this in future as it seems bit optimized approach from the current one
    public static function buildNestedChildSubcategoryObject($categories, $finalCategories = []) {
        echo "<pre>";
        // Foreach loop only on the parent category objects
        if (!empty($finalCategories)) {
            $finalCategories = $categories->whereNull('parent_category_id');
        }
        foreach ($categories->whereNotNull('parent_category_id')->sortByDesc('parent_category_id') as $key => $category) {
            echo "----------------------------------------------------------------------<br>";
            $parentCategoryIndex = $categories->search(function ($data) use ($category) {
                return $data['id'] == $category->parent_category_id;
            });
            if (!$parentCategoryIndex) {
                continue;
            }
            // echo "*** This category will be moved to its parent category object ***<br>";
            // print_r($category->toArray());

            // Once the parent category object is rendered we can remove the category from the main object so that redundant data can be removed
            $categories[$parentCategoryIndex]->subcategories[] = $category->toArray();

            $categories->forget($key);
            echo "<br>*** After all the operation main categories object will look like this ***<br>";
            print_r($categories->toArray());

            if (!empty($categories)) {
                // Finally if subcategories are available then call the recursive function & see the magic
                return self::buildNestedChildSubcategoryObject($categories, $finalCategories);
            }
        }
        return $categories;
    } */

    public static function findParentCategory($category, $finalCategories = [])
    {
        $category = Category::find($category);

        if (! empty($category)) {
            $finalCategories[] = $category->id;

            if (! empty($category->parent_category_id)) {
                $finalCategories[] = self::findParentCategory($category->id, $finalCategories);
            }
        }

        return $finalCategories;
    }

    /**
     * Generate Slug for any model
     *
     * @param  $model  - Instance of Model
     */
    public static function generateUniqueSlug($model, string $slug, ?int $excludeID = null, int $count = 0): string
    {
        /* NOTE : This can be improved by directly calling in the UI on type of title via AJAX */
        $slug = Str::slug($slug);
        $newSlug = $count ? $slug . '-' . $count : $slug;

        $data = $model::where('slug', $newSlug);
        if ($excludeID !== null) {
            $data->where('id', '!=', $excludeID);
        }

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $data->withTrashed();
        }
        while ($data->exists()) {
            return self::generateUniqueSlug($model, $slug, $excludeID, $count + 1);
        }

        return $newSlug;
    }

    public static function findAllCategoryIds($model): array
    {
        $ids = [];

        foreach ($model as $item) {
            $ids[] = $item['id'];

            if (! empty($item['children'])) {
                $ids = array_merge($ids, self::findAllCategoryIds($item['children']));
            }
        }

        return $ids;
    }

    public static function generateRandomSlug($length = 10)
    {
        // Generate a random string of lowercase letters and numbers
        $characters = 'abcdefghijklmnopqrstuvwxyz-';
        $slug = '';

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $slug .= $characters[$index];
        }

        return $slug;
    }

    /**
     * Apply location filters to Item query with fallback logic
     * Priority: area_id > city > state > country > latitude/longitude
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Http\Request $request
     * @param callable $applyAuthFilters Function to apply auth-specific filters
     * @return array ['query' => $query, 'message' => $locationMessage]
     */
    public static function applyLocationFilters($query, $request, $applyAuthFilters)
    {
        $isHomePage = $request->current_page === 'home';
        $locationMessage = null;
        $hasLocationFilter = $request->latitude !== null && $request->longitude !== null;
        $hasCityFilter = !empty($request->city);
        $hasStateFilter = !empty($request->state);
        $hasCountryFilter = !empty($request->country);
        $hasAreaFilter = !empty($request->area_id);
        $hasAreaLocationFilter = !empty($request->area_latitude) && !empty($request->area_longitude);

        $cityName = $request->city ?? null;
        $stateName = $request->state ?? null;
        $countryName = $request->country ?? null;
        $areaId = $request->area_id ?? null;

        $cityItemCount = 0;
        $stateItemCount = 0;
        $countryItemCount = 0;
        $areaItemCount = 0;
        $areaName = null;

        // Handle area location filter (find closest area by lat/long)
        if ($hasAreaLocationFilter && !$hasAreaFilter) {
            $areaLat = $request->area_latitude;
            $areaLng = $request->area_longitude;
            $haversine = "(6371 * acos(cos(radians($areaLat))
                    * cos(radians(latitude))
                    * cos(radians(longitude) - radians($areaLng))
                    + sin(radians($areaLat)) * sin(radians(latitude))))";

            $closestArea = Area::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->selectRaw("areas.*, {$haversine} AS distance")
                // ->orderBy('distance', 'asc')

                ->orderByRaw(
                    '(6371 * acos(
                        cos(radians(?)) *
                        cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )) ASC',
                    [$areaLat, $areaLng, $areaLat]
                )

                ->first();

            if ($closestArea) {
                $hasAreaFilter = true;
                $areaId = $closestArea->id;
            }
        }

        // Get area name if area filter is set
        if ($hasAreaFilter) {
            $area = Area::find($areaId);
            $areaName = $area ? $area->name : __('the selected area');
        }

        // Save base query before location filters for fallback
        $baseQueryBeforeLocation = clone $query;

        // Priority chain: area > city > state > country. Each level only
        // runs if every higher-priority level was either absent or empty,
        // and accumulates "missing" names so the final message can list
        // all levels that had no ads (e.g. "area or city or state").
        $levels = [
            'area' => ['has' => $hasAreaFilter, 'name' => $areaName, 'column' => 'area_id', 'value' => $areaId],
            'city' => ['has' => $hasCityFilter, 'name' => $cityName, 'column' => 'city', 'value' => $cityName],
            'state' => ['has' => $hasStateFilter, 'name' => $stateName, 'column' => 'state', 'value' => $stateName],
            'country' => ['has' => $hasCountryFilter, 'name' => $countryName, 'column' => 'country', 'value' => $countryName],
        ];

        $itemCounts = ['area' => 0, 'city' => 0, 'state' => 0, 'country' => 0];
        $missingNames = [];
        $higherLevelsResolved = false; // true once a level was checked and had no ads

        foreach ($levels as $key => $level) {
            if (!$level['has']) {
                continue;
            }

            $levelQuery = clone $query;
            $levelQuery->where($level['column'], $level['value']);
            $levelQuery = $applyAuthFilters($levelQuery);
            $levelItemExists = $levelQuery->exists();

            if ($levelItemExists) {
                $query = $levelQuery;
                $itemCounts[$key] = 1;

                if ($higherLevelsResolved && $isHomePage) {
                    $locationMessage = __('No Ads found in :name. Showing all available Ads.', ['name' => $level['name']]);
                }

                // A hit resolves the chain; lower-priority filters must not run.
                break;
            }

            $itemCounts[$key] = 0;
            $missingNames[] = $level['name'];

            if ($isHomePage) {
                $locationMessage = count($missingNames) === 1
                    ? __('No Ads found in :name. Showing all available Ads.', ['name' => $level['name']])
                    : __('No Ads found in :locations. Showing all available Ads.', ['locations' => implode(', ', $missingNames)]);
            } else {
                $query = $levelQuery;
            }

            $higherLevelsResolved = true;
        }

        $areaItemCount = $itemCounts['area'];
        $cityItemCount = $itemCounts['city'];
        $stateItemCount = $itemCounts['state'];
        $countryItemCount = $itemCounts['country'];

        // Fifth, handle latitude/longitude location-based search
        $hasHigherPriorityFilter = ($hasAreaFilter && $areaItemCount > 0) || ($hasCityFilter && $cityItemCount > 0) || ($hasStateFilter && $stateItemCount > 0) || ($hasCountryFilter && $countryItemCount > 0);
        if ($hasLocationFilter && ((!$hasAreaFilter && !$hasCityFilter && !$hasStateFilter && !$hasCountryFilter) || $hasHigherPriorityFilter)) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $requestedRadius = (float)($request->radius ?? null);
            $exactLocationRadius = $request->radius;

            $haversine = '(6371 * acos(cos(radians(?))
                * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))))';

            $exactLocationQuery = clone $query;
            $exactLocationQuery
                ->select('items.*')
                ->selectRaw("$haversine AS distance", [$latitude, $longitude, $latitude])
                ->where('latitude', '!=', 0)
                ->where('longitude', '!=', 0)
                // CHANGE THIS: Use whereRaw instead of having to support pagination count
                // Use <= so radius=0.0 still returns items at the exact same coordinates (distance 0)
                ->whereRaw("$haversine <= ?", [$latitude, $longitude, $latitude, $exactLocationRadius])
                ->orderBy('distance', 'asc');

            if (Auth::check()) {
                $exactLocationQuery->with(['item_offers' => function ($q) {
                    $q->where('buyer_id', Auth::user()->id);
                }, 'user_reports' => function ($q) {
                    $q->where('user_id', Auth::user()->id);
                }]);

                $currentURI = explode('?', $request->getRequestUri(), 2);
                if ($currentURI[0] == '/api/my-items') {
                    $exactLocationQuery->where(['user_id' => Auth::user()->id])->withTrashed();
                } else {
                    $exactLocationQuery->where('status', 'approved')->has('user')->onlyNonBlockedUsers()->getNonExpiredItems();
                }
            } else {
                $exactLocationQuery->where('status', 'approved')->getNonExpiredItems();
            }

            $exactLocationExists = $exactLocationQuery->exists();

            if ($exactLocationExists) {
                $query = $exactLocationQuery;
            } else {
                $searchRadius = $requestedRadius !== null && $requestedRadius > 0 ? $requestedRadius : 50;

                $nearbyQuery = clone $query;
                $nearbyQuery
                    ->select('items.*')
                    ->selectRaw("$haversine AS distance", [$latitude, $longitude, $latitude])
                    ->where('latitude', '!=', 0)
                    ->where('longitude', '!=', 0)
                    // CHANGE THIS: Use whereRaw instead of having
                    ->whereRaw("$haversine < ?", [$latitude, $longitude, $latitude, $searchRadius])
                    ->orderBy('distance', 'asc');

                $nearbyQuery = $applyAuthFilters($nearbyQuery);
                $nearbyItemExists = $nearbyQuery->exists();

                if ($nearbyItemExists) {
                    $query = $nearbyQuery;
                    if (!$locationMessage) {
                        $locationMessage = __('No Ads found at your location. Showing nearby Ads.');
                    }
                } else {
                    if ($isHomePage) {
                        $query = clone $baseQueryBeforeLocation;
                        if (!$locationMessage) {
                            $locationMessage = __('No Ads found at your location. Showing all available Ads.');
                        }
                    } else {
                        $query = $nearbyQuery;
                    }
                }
            }
        }

        return ['query' => $query, 'message' => $locationMessage];
    }

    /**
     * Get watermark configuration status (enabled/disabled)
     *
     * @return bool
     */
    public static function getWatermarkConfigStatus(): bool
    {
        $enabled = CachingService::getSystemSettings('watermark_enabled');
        return !empty($enabled) && (int)$enabled === 1;
    }

    /**
     * Get watermark configuration as decoded array
     *
     * @return array
     */
    public static function getWatermarkConfigDecoded(): array
    {
        $settings = CachingService::getSystemSettings([
            'watermark_enabled',
            'watermark_image',
            'watermark_opacity',
            'watermark_size',
            'watermark_style',
            'watermark_position',
            'watermark_rotation',
        ]);

        return [
            'enabled' => (int)($settings['watermark_enabled'] ?? 0),
            'watermark_image' => $settings['watermark_image'] ?? null,
            'opacity' => (int)($settings['watermark_opacity'] ?? 25),
            'size' => (int)($settings['watermark_size'] ?? 10),
            'style' => $settings['watermark_style'] ?? 'tile',
            'position' => $settings['watermark_position'] ?? 'center',
            'rotation' => (int)($settings['watermark_rotation'] ?? -30),
        ];
    }

    /**
     * Get a specific setting value
     *
     * @param string $key
     * @return string|null
     */
    public static function getSettingData(string $key): ?string
    {
        return CachingService::getSystemSettings($key);
    }

    /**
     * Calculate item expiry date based on package listing duration
     * Priority: listing_duration > package expiry > default
     * 
     * @param \App\Models\Package|null $package
     * @param \App\Models\UserPurchasedPackage|null $userPackage
     * @return \Carbon\Carbon|null
     */
    public static function calculateItemExpiryDate($package = null, $userPackage = null)
    {

        if (!$package) {
            $freeAdUnlimited = Setting::where('name', 'free_ad_unlimited')->value('value') ?? 0;
            $freeAdDays = Setting::where('name', 'free_ad_duration_days')->value('value') ?? 0;
            // Unlimited free ads
            if ((int) $freeAdUnlimited === 1) {
                return null;
            }

            // Limited free ads
            if (!empty($freeAdDays) && (int) $freeAdDays > 0) {
                return Carbon::now()->addDays((int) $freeAdDays);
            }

            // Safety fallback (no expiry)
            return null;
        }

        // ---------------------------------------
        // PACKAGE LOGIC (existing, unchanged)
        // ---------------------------------------

        $listingDurationType = $package->listing_duration_type ?? null;
        $listingDurationDays = $package->listing_duration_days ?? null;

        // If listing_duration_type is null → use package expiry
        if ($listingDurationType === null) {
            if ($userPackage && $userPackage->end_date) {
                return Carbon::parse($userPackage->end_date);
            }

            if (is_null($package->duration)) {
                return null;
            }

            return Carbon::now()->addDays((int) $package->duration);
        }

        if ($listingDurationType === 'package') {
            if (is_null($package->duration)) {
                return null;
            }

            return Carbon::now()->addDays((int) $package->duration);
        }

        if ($listingDurationType === 'custom') {
            if (!empty($listingDurationDays) && (int) $listingDurationDays > 0) {
                return Carbon::now()->addDays((int) $listingDurationDays);
            }

            return Carbon::now()->addDays(30);
        }

        // Standard fallback
        if (!empty($listingDurationDays) && (int) $listingDurationDays > 0) {
            return Carbon::now()->addDays((int) $listingDurationDays);
        }

        return Carbon::now()->addDays(30);
    }

    public static function getQueryLog($sqlQuery)
    {
        $query = $sqlQuery['query'];
        $bindings = $sqlQuery['bindings'];

        $sql = vsprintf(
            str_replace('?', "'%s'", $query),
            collect($bindings)->map(function ($binding) {
                // Handle DateTime objects
                if ($binding instanceof \DateTime) {
                    return $binding->format('Y-m-d H:i:s');
                }
                // Escape strings properly
                return addslashes($binding);
            })->toArray()
        );

        return $sql;
    }

    /**
     * Store or update translations using upsert
     *
     * @param array $translations Array of translation data with keys:
     *   - id (optional, for updates)
     *   - translatable_id
     *   - translatable_type
     *   - language_id
     *   - key
     *   - value
     */
    public static function storeTranslations($translations)
    {
        try {
            $storeTranslations = [];
            foreach ($translations as $translation) {
                if (isset($translation['language_id']) && !empty($translation['language_id']) && isset($translation['value']) && !empty($translation['value'])) {
                    $storeTranslations[] = [
                        'translatable_id'   => $translation['translatable_id'],
                        'translatable_type' => $translation['translatable_type'],
                        'language_id'       => $translation['language_id'],
                        'key'               => $translation['key'],
                        'value'             => $translation['value'],
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }
            }
            if (!empty($storeTranslations)) {
                Translation::upsert(
                    $storeTranslations,
                    ['language_id', 'key', 'translatable_id', 'translatable_type'],
                    ['value', 'updated_at']
                );
            }
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, 'Issue in store translations helper function');
        }
    }

    /**
     * Get translated data for a model attribute
     *
     * @param mixed $dataObject The model instance
     * @param mixed $defaultData The default value (from main language)
     * @param string $key The translation key (e.g., 'name', 'description')
     * @return mixed
     */
    public static function getTranslatedData($dataObject, $defaultData, $key)
    {
        $languageCode = request()->header('Content-Language') ?? null;

        if (empty($languageCode)) {
            return $defaultData;
        }

        // Cache language ID lookup
        $languageId = cache()->remember("language_id_{$dataObject->id}_{$key}_{$languageCode}", 3600, function () use ($languageCode) {
            return Language::where('code', $languageCode)->value('id');
        });

        if (empty($languageId)) {
            return $defaultData;
        }

        // Use specific relationship or direct query (check it's a collection, not a string column)
        if ($dataObject->relationLoaded('translations') && $dataObject->getRelation('translations') instanceof \Illuminate\Support\Collection) {
            $translation = $dataObject->getRelation('translations')->where('language_id', $languageId)
                ->where('key', $key)
                ->first();
            return $translation?->value ?? $defaultData;
        }

        return $defaultData;
    }

    /**
     * Transform morph translations (key-value rows) into column-style objects keyed by language_id.
     * This allows blade views to access translations as $trans->name, $trans->description etc.
     *
     * @param \Illuminate\Database\Eloquent\Collection $translations The model's translations collection
     * @return \Illuminate\Support\Collection Keyed by language_id, each value is an object with key names as properties
     */
    public static function transformTranslationsForEdit($translations)
    {
        return $translations->groupBy('language_id')->map(function ($items) {
            $obj = new \stdClass();
            $obj->language_id = $items->first()->language_id;
            foreach ($items as $item) {
                $obj->{$item->key} = $item->value;
            }
            return $obj;
        });
    }

    /**
     * Store or update SEO details for a model (Category, Blog, Item).
     *
     * @param \Illuminate\Database\Eloquent\Model $model The parent model
     * @param \Illuminate\Http\Request $request The request containing SEO fields
     * @param array $languages Array of language IDs
     */
    public static function storeSeoDetails($model, $request, $languages)
    {
        try {
            $seoDetail = $model->seoDetail()->updateOrCreate(
                ['seoable_id' => $model->id, 'seoable_type' => get_class($model)],
                [
                    'meta_title'       => $request->input('meta_title.1') ?? null,
                    'meta_description' => $request->input('meta_description.1') ?? null,
                    'meta_keywords'    => $request->input('meta_keywords.1') ?? null,
                    'schema'           => $request->input('schema.1') ?? null,
                ]
            );

            $translationData = [];
            foreach ($languages as $langId) {
                if ($langId == 1) continue;

                $fields = ['meta_title', 'meta_description', 'meta_keywords', 'schema'];
                foreach ($fields as $field) {
                    $value = $request->input("{$field}.{$langId}");
                    if (!empty($value)) {
                        if ($field === 'schema') {
                            try {
                                $parsed = json_decode($value, true);
                                if (!isset($parsed['@context']) || !isset($parsed['@type'])) {
                                    ResponseService::errorResponse("Invalid schema structure");
                                }
                            } catch (Throwable $e) {
                                ResponseService::errorResponse('Invalid JSON schema for language ' . $langId);
                            }
                        }

                        $translationData[] = [
                            'translatable_id'   => $seoDetail->id,
                            'translatable_type' => SeoDetail::class,
                            'key'               => $field,
                            'value'             => $value,
                            'language_id'       => $langId,
                        ];
                    }
                }
            }

            if (!empty($translationData)) {
                self::storeTranslations($translationData);
            }
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, 'Issue in storeSeoDetails helper function');
        }
    }

    /**
     * Store or update SEO details for a model from API input.
     * {
     *   "1": {"meta_title": "...", "meta_description": "...", "meta_keywords": "...", "schema": "..."},
     *   "2": {"meta_title": "...", "meta_description": "...", "meta_keywords": "...", "schema": "..."}
     * }
     *
     * @param \Illuminate\Database\Eloquent\Model $model The parent model (Item, Category, Blog)
     * @param array $seoData Decoded JSON array keyed by language_id
     */
    public static function storeSeoDetailsFromApi($model, array $seoData)
    {
        try {
            if (empty($seoData)) {
                return;
            }

            $keys = array_keys($seoData);
            $defaultLangId = $keys[0];
            $defaultData = $seoData[$defaultLangId] ?? [];

            $defaultSchema = $defaultData['schema'] ?? null;
            if (!empty($defaultSchema)) {
                try {
                    $parsed = json_decode($defaultSchema, true);
                    if (!isset($parsed['@context']) || !isset($parsed['@type'])) {
                        throw new Exception('Invalid schema structure');
                    }
                } catch (Throwable $e) {
                    ResponseService::errorResponse(__("Invalid JSON schema for main language"));
                }
            }

            $seoDetail = $model->seoDetail()->updateOrCreate(
                ['seoable_id' => $model->id, 'seoable_type' => get_class($model)],
                [
                    'meta_title'       => $defaultData['meta_title'] ?? null,
                    'meta_description' => $defaultData['meta_description'] ?? null,
                    'meta_keywords'    => $defaultData['meta_keywords'] ?? null,
                    'schema'           => $defaultSchema,
                ]
            );

            // Store translations for other languages
            $translationData = [];
            $fields = ['meta_title', 'meta_description', 'meta_keywords', 'schema'];

            foreach ($seoData as $langId => $langData) {
                if ($langId === $defaultLangId) continue;
                if (!is_array($langData)) continue;

                foreach ($fields as $field) {
                    $value = $langData[$field] ?? null;
                    if (!empty($value)) {
                        if ($field === 'schema') {
                            try {
                                $parsed = json_decode($value, true);
                                if (!isset($parsed['@context']) || !isset($parsed['@type'])) {
                                    throw new Exception('Invalid schema structure');
                                }
                            } catch (Throwable $e) {
                                ResponseService::errorResponse(__("Invalid JSON schema for language". $langId));
                            }
                        }

                        $translationData[] = [
                            'translatable_id'   => $seoDetail->id,
                            'translatable_type' => SeoDetail::class,
                            'key'               => $field,
                            'value'             => $value,
                            'language_id'       => $langId,
                        ];
                    }
                }
            }

            if (!empty($translationData)) {
                self::storeTranslations($translationData);
            }
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, 'Issue in storeSeoDetailsFromApi helper function');
        }
    }

    /**
     * Prepare SEO translations for edit forms.
     * Returns an array keyed by language_id with SEO field values.
     *
     * @param \Illuminate\Database\Eloquent\Model $model The parent model with seoDetail relationship
     * @return array
     */
    public static function prepareSeoTranslationsForEdit($model)
    {
        $seoTranslations = [];
        $seoDetail = $model->seoDetail;

        if (!$seoDetail) {
            return $seoTranslations;
        }

        // Default language (id=1) from main columns
        $seoTranslations[1] = [
            'meta_title'       => $seoDetail->meta_title,
            'meta_description' => $seoDetail->meta_description,
            'meta_keywords'    => $seoDetail->meta_keywords,
            'schema'           => $seoDetail->schema,
        ];

        // Other languages from translations
        if ($seoDetail->relationLoaded('translations')) {
            $grouped = $seoDetail->translations->groupBy('language_id');
            foreach ($grouped as $langId => $items) {
                $data = [];
                foreach ($items as $item) {
                    $data[$item->key] = $item->value;
                }
                $seoTranslations[$langId] = $data;
            }
        }

        return $seoTranslations;
    }

    public static function getCurlRequest()
    {
        $request = request();

        $method  = strtoupper($request->method());
        $url     = $request->fullUrl();
        $headers = [];

        foreach ($request->headers->all() as $key => $values) {
            foreach ($values as $value) {
                $headers[] = "-H '" . $key . ": " . $value . "'";
            }
        }

        $data = '';

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $contentType = $request->header('Content-Type');

            if (str_contains($contentType, 'application/json')) {
                // JSON payload
                $payload = $request->all();
                $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $data = " --data '" . addslashes($payloadJson) . "'";
            } elseif (str_contains($contentType, 'multipart/form-data')) {
                // Multipart (e.g., file upload)
                $parts = [];
                foreach ($request->all() as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $parts[] = "-F '{$key}={$v}'";
                        }
                    } else {
                        $parts[] = "-F '{$key}={$value}'";
                    }
                }

                // Handle uploaded files
                foreach ($request->files->all() as $key => $file) {
                    if (is_array($file)) {
                        foreach ($file as $f) {
                            $parts[] = "-F '{$key}=@{$f->getRealPath()};filename={$f->getClientOriginalName()}'";
                        }
                    } else {
                        $parts[] = "-F '{$key}=@{$file->getRealPath()};filename={$file->getClientOriginalName()}'";
                    }
                }

                $data = " " . implode(" \\\n  ", $parts);
            } else {
                // Default: x-www-form-urlencoded
                $payload = http_build_query($request->all());
                if (!empty($payload)) {
                    $data = " --data '" . addslashes($payload) . "'";
                }
            }
        }

        $curl = "curl -X {$method} '" . $url . "' \\\n  " . implode(" \\\n  ", $headers) . $data;

        Log::error("CURL Request:\n" . $curl);
    }



    // -------------------------------------------------------------------------
    // Settings Masking
    // -------------------------------------------------------------------------

    /**
     * Setting keys that should always be masked in API responses.
     * Add any new sensitive key here — masking is applied automatically.
     *
     * Covers:
     *  - Payment gateway credentials (Stripe, Razorpay, Twilio, PayPal, etc.)
     *  - Map / location API keys
     *  - Mail credentials
     *  - AWS / storage secrets
     *  - Firebase / push-notification keys
     *  - Bank & OTP provider secrets
     */
    public const MASKED_SETTING_KEYS = [
        // --- Google Maps / Location ---
        'place_api_key',
        'google_map_key',

        // --- Mail / SMTP ---
        'mail_password',
        'mail_username',
        'mail_from_address',

        // --- Twilio (SMS / OTP) ---
        'twilio_account_sid',
        'twilio_auth_token',
        'twilio_my_phone_number',

        // --- 2Factor OTP ---
        'twofactor_api_key',

        // --- AWS / S3 Storage ---
        'S3_aws_access_key_id',
        's3_aws_secret_access_key',
        's3_aws_default_region',
        's3_aws_bucket',
        's3_aws_url',

        // --- Firebase / Push Notifications ---
        'firebase_project_id',
        'service_file',

        // --- Bank Transfer ---
        'account_number',
        'ifsc_swift_code',
    ];

    /**
     * Mask a single sensitive value.
     * Shows first 3 chars + **** + last 3 chars for longer strings,
     * or pure **** for short / empty values.
     *
     * Example: "sk_live_abc123xyz" → "sk_****xyz"
     */
    public static function maskSettingValue(?string $value): string
    {
        if (empty($value)) {
            return '****';
        }

        $len = strlen($value);

        if ($len <= 6) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, 3) . '****' . substr($value, -3);
    }

    /**
     * Mask all sensitive keys inside a flat key→value settings array.
     * Only masks when demo mode is enabled; support@wrteam.in always sees real values.
     *
     * Usage:
     *   $settings = HelperService::maskSettings($settings);
     */
    public static function maskSettings(array $settings): array
    {
        if (!config('app.demo_mode') || (Auth::check() && Auth::user()->email == 'support@wrteam.in')) {
            return $settings;
        }

        foreach (self::MASKED_SETTING_KEYS as $key) {
            if (array_key_exists($key, $settings)) {
                $settings[$key] = self::maskSettingValue($settings[$key]);
            }
        }

        return $settings;
    }

    // -------------------------------------------------------------------------
    // Payment Gateway Config Masking (PaymentConfiguration table)
    // -------------------------------------------------------------------------

    /**
     * PaymentConfiguration fields that must never be exposed in API responses.
     * These are the server-side secret credentials for each payment gateway
     * (Stripe, Razorpay, PayPal, PhonePe, Flutterwave, Paystack, PayTabs, DPO …).
     *
     * The public API key (api_key) is intentionally NOT masked here because
     * client SDKs need it (e.g. Stripe publishable key, Razorpay key_id).
     *
     * To add a new gateway field, just append it here.
     */
    public const MASKED_PAYMENT_CONFIG_KEYS = [
        'secret_key',           // Stripe secret, Razorpay secret, PayPal secret, etc.
        'webhook_secret_key',   // Stripe / Flutterwave / PayPal webhook signing secret
        'additional_data_1',    // Gateway-specific extra credential (e.g. PhonePe merchant id, PayTabs profile id)
        'additional_data_2',    // Gateway-specific extra credential (e.g. PhonePe salt index)
        'username',             // Basic-auth username (some gateways)
        'password',             // Basic-auth password (some gateways)
    ];

    /**
     * Mask sensitive fields of a single PaymentConfiguration row array.
     * Safe fields (payment_method, currency_code, api_key, status, payment_mode)
     * are passed through untouched.
     * Only masks when demo mode is enabled; support@wrteam.in always sees real values.
     *
     * Usage:
     *   $masked = HelperService::maskPaymentConfig($paymentConfig->toArray());
     *
     * Or for a collection:
     *   $masked = $configs->map(fn($c) => HelperService::maskPaymentConfig($c->toArray()));
     */
    public static function maskPaymentConfig(array $config): array
    {
        if (!config('app.demo_mode') || (Auth::check() && Auth::user()->email == 'support@wrteam.in')) {
            return $config;
        }

        foreach (self::MASKED_PAYMENT_CONFIG_KEYS as $key) {
            if (array_key_exists($key, $config)) {
                $config[$key] = self::maskSettingValue($config[$key]);
            }
        }

        return $config;
    }

    // -------------------------------------------------------------------------
    // Demo-mode User Data Masking (email / mobile)
    // -------------------------------------------------------------------------

    /**
     * Mask an email address when demo mode is enabled.
     * Pattern: first 3 chars + **** + @domain (e.g. adm****@gmail.com)
     */
    public static function maskDemoEmail(?string $email): ?string
    {
        if (!config('app.demo_mode') || empty($email) || (Auth::check() && Auth::user()->email == 'support@wrteam.in')) {
            return $email;
        }

        $atPosition = strpos($email, '@');
        if ($atPosition === false) {
            return substr($email, 0, 3) . '****';
        }

        return substr($email, 0, min(3, $atPosition)) . '****' . substr($email, $atPosition);
    }

    /**
     * Mask a mobile number when demo mode is enabled.
     * Pattern: first 3 digits + asterisks + last 2 digits (e.g. 987****65)
     */
    public static function maskDemoMobile(?string $mobile): ?string
    {
        if (!config('app.demo_mode') || empty($mobile) || (Auth::check() && Auth::user()->email == 'support@wrteam.in')) {
            return $mobile;
        }

        if (strlen($mobile) <= 5) {
            return str_repeat('*', strlen($mobile));
        }

        return substr($mobile, 0, 3) . str_repeat('*', strlen($mobile) - 5) . substr($mobile, -2);
    }

    /**
     * Mask email/mobile keys of a user array (e.g. from toArray()) when demo mode is enabled.
     */
    public static function maskDemoUserData(?array $user): ?array
    {
        if (!config('app.demo_mode') || empty($user) || (Auth::check() && Auth::user()->email == 'support@wrteam.in')) {
            return $user;
        }

        if (!empty($user['email'])) {
            $user['email'] = self::maskDemoEmail($user['email']);
        }
        if (!empty($user['mobile'])) {
            $user['mobile'] = self::maskDemoMobile($user['mobile']);
        }

        return $user;
    }

    /**
     * Get all ancestor category IDs recursively (going up to root)
     */
    public static function getAllAncestorCategoryIds(int $categoryId): array
    {
        $ids = [];
    
        $parentId = Category::where('id', $categoryId)
            ->value('parent_category_id');
    
        if ($parentId) {
            $ids[] = (int) $parentId;
            $ids   = array_merge($ids, self::getAllAncestorCategoryIds($parentId));
        }
    
        return $ids;
    }
    
    /**
     * Get all descendant category IDs recursively (self + children + grandchildren...)
     */
    public static function getAllDescendantCategoryIds(int $categoryId): array
    {
        $ids = [$categoryId];
    
        $children = Category::where('parent_category_id', $categoryId)
            ->where('status', 1)
            ->pluck('id')
            ->toArray();
    
        foreach ($children as $childId) {
            $ids = array_merge($ids, self::getAllDescendantCategoryIds($childId));
        }
    
        return $ids;
    }

    /**
     * Revalidate language codes on the web portal if configured
     */
    public static function revalidateLanguageCodes(): void
    {
        try {
            $webUrl = CachingService::getSystemSettings('web_url');
            if (!empty($webUrl)) {
                $endpoint = rtrim($webUrl, '/') . '/api/revalidate-lang-codes';
                Http::timeout(3)->post($endpoint);
            }
        } catch (Throwable $th) {
            Log::error('HelperService -> revalidateLanguageCodes error: ' . $th->getMessage());
        }
    }

    /**
     * Resolve a stable RAND() seed for a paginated random-order listing (reels, random-sorted items).
     * Logged-in users key off their user id. Anonymous users should send back the 'X-Seed-Key'
     * header the client generated/stored on page 1 (device/session id) so the key survives IP
     * churn behind CDNs/carrier NAT; falls back to request IP if the client doesn't send one.
     * Seed itself is cached per key for 30 minutes and re-rolled only when a new session starts
     * (page <= 1), so subsequent pages reuse the exact same MySQL RAND(seed) ordering.
     */
    public static function resolveRandomSeed(Request $request, ?int $userId, string $cachePrefix): int
    {
        $page = (int) ($request->page ?? 1);
        $seedKey = $cachePrefix . '_' . self::listingSessionIdentity($request, $userId);
        $seedCache = self::listingSessionCache();

        if ($page <= 1) {
            $seed = random_int(1, PHP_INT_MAX);
            $seedCache->put($seedKey, $seed, now()->addMinutes(30));
        } else {
            $seed = $seedCache->get($seedKey) ?? random_int(1, PHP_INT_MAX);
        }

        return $seed;
    }

    /**
     * Remember a value for the rest of a paginated browsing session.
     *
     * Some filters are only sent by the client on page 1 (e.g. the pinned reel_id), but they change
     * which rows are in the candidate pool. If later pages drop them, the pool differs and the
     * RAND(seed) ordering shifts, producing duplicates/skips even though the seed is unchanged.
     * Stores the page 1 value and replays it on subsequent pages.
     */
    public static function resolveStickyListingValue(Request $request, ?int $userId, string $cachePrefix, $pageOneValue)
    {
        $page = (int) ($request->page ?? 1);
        $key = $cachePrefix . '_' . self::listingSessionIdentity($request, $userId);
        $cache = self::listingSessionCache();

        if ($page <= 1) {
            $cache->put($key, $pageOneValue, now()->addMinutes(30));

            return $pageOneValue;
        }

        return $pageOneValue ?? $cache->get($key);
    }

    /**
     * Stable per-requester identity for paginated listing sessions. Logged-in users key off their
     * user id. Anonymous clients should send the 'X-Seed-Key' header they generated on page 1
     * (device/session id) so the key survives IP churn behind CDNs/carrier NAT; falls back to the
     * request IP when the client doesn't send one.
     */
    private static function listingSessionIdentity(Request $request, ?int $userId): string
    {
        return (string) ($userId ?: ($request->header('x-seed-key') ?: $request->ip()));
    }

    /**
     * If the configured cache driver is null/none it never persists, so Cache::get always misses on
     * page >1 and the stored session state is lost. Fall back to the file store in that case.
     */
    private static function listingSessionCache()
    {
        return config('cache.default') === 'none'
            ? \Illuminate\Support\Facades\Cache::store('file')
            : \Illuminate\Support\Facades\Cache::store();
    }
}
