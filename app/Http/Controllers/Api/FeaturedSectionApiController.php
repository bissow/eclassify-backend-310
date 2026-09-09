<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ItemApiResource;
use App\Models\Category;
use App\Models\FeatureSection;
use App\Models\Item;
use App\Services\CachingService;
use App\Services\HelperService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Featured Section */
class FeaturedSectionApiController extends BaseApiController
{
    /** Get Featured Section */
    public function getFeaturedSection(Request $request)
    {
        try {
            $featureSection = FeatureSection::with('translations')->orderBy('sequence', 'ASC');

            if (isset($request->slug)) {
                $featureSection->where('slug', $request->slug);
            }
            $featureSection = $featureSection->get();
            $tempRow = [];
            $rows = [];

            $buildBaseQuery = function () {
                return Item::query()
                    ->where('status', 'approved')
                    ->has('user')
                    ->whereHas('category', function ($q) {
                        $q->where('status', '!=', 0)
                            ->where(function ($query) {
                                $query->whereDoesntHave('parent')
                                    ->orWhereHas('parent', function ($q2) {
                                        $q2->where('status', '!=', 0);
                                    });
                            });
                    })
                    ->with([
                        'user:id,name,mobile,profile,is_verified,show_personal_details,country_code',
                        'category:id,name,image,is_job_category,price_optional',
                        'gallery_images:id,image,item_id,is_default',
                        'featured_items',
                        'favourites',
                        'item_custom_field_values.custom_field.translations',
                        'job_applications',
                        'translations',
                        'countryRelation:id,name',
                        'countryRelation.translations:id,country_id,language_id,name',
                        'countryRelation.currency:id,country_id,iso_code,symbol,symbol_position',
                        'active_promotion_items.promotion.campaign',
                        'active_promotion_items.promotion.translations',
                        'active_ad_promotions',
                    ])
                    ->getNonExpiredItems();
            };

            $applyAuthFilters = function ($query) {
                if (Auth::check()) {
                    $query->with(['item_offers' => function ($q) {
                        $q->where('buyer_id', Auth::user()->id);
                    }, 'user_reports' => function ($q) {
                        $q->where('user_id', Auth::user()->id);
                    }]);
                }

                return $query;
            };

            $featuredSort = CachingService::getSystemSettings('feature_item_sorting') ?: 'random';
            $generalSort = CachingService::getSystemSettings('general_item_sorting') ?: 'random';

            $applySort = function ($query, string $adminSort) {
                $query->reorder();

                if ($adminSort === 'latest') {
                    $query->orderBy('items.published_at', 'DESC');
                } elseif ($adminSort === 'oldest') {
                    $query->orderBy('items.published_at', 'ASC');
                } else {
                    $query->inRandomOrder();
                }

                return $query;
            };

            $locationMessage = null;
            $baseItems = $buildBaseQuery();
            $locationResult = HelperService::applyLocationFilters($baseItems, $request, $applyAuthFilters);

            $filteredBaseQuery = clone $locationResult['query'];
            $sectionLocationMessage = $locationResult['message'];

            foreach ($featureSection as $row) {
                $baseItems = clone $filteredBaseQuery;

                $items = match ($row->filter) {
                    'price_criteria' => $baseItems->clone()->where(function ($query) use ($row) {
                        $query->whereBetween('price', [$row->min_price, $row->max_price])
                            ->orWhere(function ($q) use ($row) {
                                $q->whereBetween('min_salary', [$row->min_price, $row->max_price])
                                    ->whereBetween('max_salary', [$row->min_price, $row->max_price]);
                            });
                    }),
                    'most_viewed' => $baseItems->clone()->orderBy('clicks', 'DESC'),
                    'category_criteria' => (static function () use ($row, $baseItems) {
                        $category = Category::whereIn('id', explode(',', $row->value))->with('children')->get();
                        $categoryIDS = HelperService::findAllCategoryIds($category);

                        return $baseItems->whereIn('category_id', $categoryIDS);
                    })(),
                    'most_liked' => $baseItems->clone()->withCount('favourites')->orderBy('favourites_count', 'DESC'),
                    'featured_ads' => $baseItems->clone()->whereHas('featured_items', fn ($q) => $q->onlyActive()),
                    'item_selection' => $baseItems->clone()->whereIn('items.id', array_filter(explode(',', $row->value ?? ''))),
                };

                if (Auth::check()) {
                    $items->with(['item_offers' => function ($q) {
                        $q->where('buyer_id', Auth::user()->id);
                    }, 'user_reports' => function ($q) {
                        $q->where('user_id', Auth::user()->id);
                    }]);
                }

                $sectionLimit = 5;

                if (in_array($row->filter, ['price_criteria', 'category_criteria', 'featured_ads', 'item_selection', 'most_viewed', 'most_liked'])) {
                    // Same weight-based split as getItemListLite: featuredPerPage from featured_items_weightage.
                    $weightage = (int) (CachingService::getSystemSettings('featured_items_weightage') ?: 10);
                    $featuredPerPage = (int) round($sectionLimit * $weightage / 100);

                    $usesOwnSort = in_array($row->filter, ['most_viewed', 'most_liked']);

                    // most_viewed/most_liked already carry their own order (clicks/favourites_count) from $items;
                    // keep it instead of overriding with the admin featured/general sort setting.
                    $featuredQuery = (clone $items)->whereHas('featured_items', fn ($q) => $q->onlyActive());
                    $normalQuery = (clone $items)->whereDoesntHave('featured_items', fn ($q) => $q->onlyActive());

                    if (! $usesOwnSort) {
                        $featuredQuery = $applySort($featuredQuery, $featuredSort);
                        $normalQuery = $applySort($normalQuery, $generalSort);
                    }

                    $total = (clone $featuredQuery)->count() + (clone $normalQuery)->count();

                    $featuredItems = (clone $featuredQuery)->limit($featuredPerPage)->get();
                    $normalItems = (clone $normalQuery)->limit($sectionLimit - $featuredItems->count())->get();

                    // If general items can't fill the section, top up with more featured items
                    $remaining = $sectionLimit - $featuredItems->count() - $normalItems->count();
                    if ($remaining > 0 && $featuredItems->isNotEmpty()) {
                        $featuredItems = $featuredItems->merge(
                            (clone $featuredQuery)
                                ->whereNotIn('items.id', $featuredItems->pluck('id'))
                                ->limit($remaining)
                                ->get()
                        );
                    }

                    $items = $featuredItems->merge($normalItems);
                } else {
                    $total = $items->count();
                    $items = $items->limit($sectionLimit)->get();
                }

                $tempRow[$row->id] = $row;
                $tempRow[$row->id]['total_data'] = $total;
                if ($total > 0) {
                    $tempRow[$row->id]['section_data'] = new ItemApiResource($items);
                } else {
                    $tempRow[$row->id]['section_data'] = [];
                }

                if (! empty($sectionLocationMessage) && empty($locationMessage)) {
                    $locationMessage = $sectionLocationMessage;
                }

                $rows[] = $tempRow[$row->id];
            }

            $responseMessage = ! empty($locationMessage) ? $locationMessage : __('Data Fetched Successfully');
            ResponseService::successResponse($responseMessage, $rows);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getFeaturedSection');
            ResponseService::errorResponse();
        }
    }

    /** Get Featured Section Slug */
    public function getFeatureSectionSlug(Request $request)
    {
        try {
            $FeatureSection = FeatureSection::without('translations')
                ->select('id', 'slug')
                ->paginate(500);

            if ($FeatureSection->isEmpty()) {
                return ResponseService::errorResponse(__('No active Feature Sections found.'));
            }

            return ResponseService::successResponse(__('Active Feature Sections slugs fetched successfully.'), $FeatureSection);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategoriesSlug');
            ResponseService::errorResponse();
        }
    }

    /** Get Featured Categories */
    public function getFeaturedCategories(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $categories = Category::where('status', 1)
                ->where('is_featured', 1)
                ->with('translations')
                ->orderBy('sequence', 'ASC')
                ->paginate();

            ResponseService::successResponse(null, $categories);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getFeaturedCategories');
            ResponseService::errorResponse();
        }
    }
}
