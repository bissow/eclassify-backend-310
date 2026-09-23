<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\CustomField;
use App\Models\ItemCustomFieldValue;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Setting;
use App\Models\UserPurchasedPackage;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Throwable;

/** @tags Category */
class CategoryApiController extends BaseApiController
{
    /** Get Categories */
    public function getSubCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $subcategoryLimit = 15;

            // Recursive subcategory loader — limits at query level
            $loadSubcategoriesRecursively = function ($query, $depth = 0, $maxDepth = 5) use (&$loadSubcategoriesRecursively, $subcategoryLimit) {
                if ($depth >= $maxDepth) {
                    return;
                }

                $query->where('status', 1)
                    ->orderBy('sequence', 'ASC')
                    ->with('translations', 'seoDetail.translations')
                    ->withCount(['approved_items', 'subcategories' => function ($q) {
                        $q->where('status', 1);
                    }]);

                $query->with(['subcategories' => function ($subQuery) use (&$loadSubcategoriesRecursively, $depth, $maxDepth) {
                    $loadSubcategoriesRecursively($subQuery, $depth + 1, $maxDepth);
                }]);
            };

            // Trim each parent's subcategories to $subcategoryLimit post-load (DB limit() in an
            // eager-load closure caps the whole batch, not per-parent, dropping rows for some parents)
            $trimSubcategoriesRecursively = function ($categories) use (&$trimSubcategoriesRecursively, $subcategoryLimit) {
                foreach ($categories as $category) {
                    if ($category->relationLoaded('subcategories')) {
                        $trimmed = $category->subcategories->take($subcategoryLimit)->values();
                        $trimSubcategoriesRecursively($trimmed);
                        $category->setRelation('subcategories', $trimmed);
                    }
                }
            };

            // Build main query
            $sql = Category::withCount(['subcategories' => function ($q) {
                $q->where('status', 1);
            }])
                ->with('translations', 'seoDetail.translations')
                ->where('status', 1)
                ->orderBy('sequence', 'ASC')
                ->with(['subcategories' => function ($query) use (&$loadSubcategoriesRecursively) {
                    $loadSubcategoriesRecursively($query, 0);
                }]);

            $parentCategory = null;

            if (! empty($request->category_id)) {
                $sql            = $sql->where('parent_category_id', $request->category_id);
                $parentCategory = Category::with('seoDetail.translations')->find($request->category_id);
            } elseif (! empty($request->slug)) {
                $parentCategory = Category::where('slug', $request->slug)->with('seoDetail.translations')->firstOrFail();
                $sql            = $sql->where('parent_category_id', $parentCategory->id);
            } else {
                $sql = $sql->whereNull('parent_category_id');
            }

            $sql = $sql->paginate();
            $trimSubcategoriesRecursively($sql->getCollection());

            // Resolve shared listing package state once — used for both subcategories and self_category
            $freeAdListingSetting  = Setting::where('name', 'free_ad_listing')->first()['value'];
            $activeListingPackages = collect();
            $globalActivePackage   = null;

            if (Auth::check()) {
                $activeListingPackagesQuery = UserPurchasedPackage::onlyActive()
                    ->whereHas('package', fn($q) => $q->where('type', 'item_listing'))
                    ->with('package:id,is_reel_allowed,is_global');

                $globalActivePackage = $activeListingPackagesQuery->clone()->whereHas('package',function($package){
                    $package->where('is_global',1);
                })->get();
                $activeListingPackages = $activeListingPackagesQuery->clone()->get();
            }

            $activePackageIdSet = $activeListingPackages->pluck('package_id')->flip()->toArray();

            // Helper: resolve listing status onto any Category model given its related package IDs
            $applyListingStatus = function ($model, array $packageIds) use ($freeAdListingSetting, $activeListingPackages, $globalActivePackage, $activePackageIdSet) {
                if ($freeAdListingSetting == 1) {
                    $model->is_listing_available       = true;
                    $model->is_reel_allowed_in_listing = true;
                } elseif ($activeListingPackages->isNotEmpty()) {
                    if (collect($globalActivePackage)->isNotEmpty()) {
                        $coveringPackages = $globalActivePackage;
                    } else {
                        $coveringIds      = array_intersect($packageIds, array_keys($activePackageIdSet));
                        $coveringPackages = $activeListingPackages->whereIn('package_id', $coveringIds);
                    }
                    $model->is_listing_available       = $coveringPackages->isNotEmpty();
                    $model->is_reel_allowed_in_listing = $coveringPackages->contains(fn($p) => (bool) ($p->package?->is_reel_allowed ?? false));
                } else {
                    $model->is_listing_available       = false;
                    $model->is_reel_allowed_in_listing = false;
                }
            };

            // Collect paginated category IDs
            $categoryIds = $sql->pluck('id')->toArray();

            if (! empty($categoryIds)) {
                // Batch-fetch ancestor + descendant IDs in two recursive CTE queries instead of 2N
                $categories = Category::whereIn('id', $categoryIds)
                    ->with([
                        'ancestors'   => fn($q) => $q->where('status', 1),
                        'descendants' => fn($q) => $q->where('status', 1),
                    ])
                    ->get();

                $allRelatedIds = [];
                foreach ($categories as $cat) {
                    $ancestorIds   = $cat->ancestors->pluck('id')->toArray();
                    $descendantIds = $cat->descendants->pluck('id')->toArray();
                    $allRelatedIds[$cat->id] = array_unique(array_merge([$cat->id], $ancestorIds, $descendantIds));
                }

                // Single query for all package-category mappings
                $flatIds = array_unique(array_merge([], ...array_values($allRelatedIds)));

                $packageIdsByCategory = PackageCategory::whereIn('category_id', $flatIds)
                    ->whereHas('package', function ($q) {
                        $q->where(['status' => 1, 'is_discontinued' => 0]);
                    })
                    ->get()
                    ->groupBy('category_id')
                    ->map(fn($rows) => $rows->pluck('package_id')->unique()->toArray())
                    ->toArray();

                // Mutate in place — no new collection needed
                $sql->each(function ($category) use ($allRelatedIds, $packageIdsByCategory, $applyListingStatus) {
                    $relatedCatIds = $allRelatedIds[$category->id] ?? [$category->id];

                    $packageIds = [];
                    foreach ($relatedCatIds as $catId) {
                        if (isset($packageIdsByCategory[$catId])) {
                            array_push($packageIds, ...$packageIdsByCategory[$catId]);
                        }
                    }

                    $category->packages_count  = count(array_unique($packageIds));
                    $category->all_items_count = $category->approved_items_count + $category->subcategories->sum('approved_items_count');

                    $applyListingStatus($category, array_unique($packageIds));
                });
            }

            // Compute listing availability for self (parent) category — runs even when no subcategories
            if ($parentCategory !== null) {
                $ancestorIds      = $parentCategory->ancestors()->where('status', 1)->pluck('id')->toArray();
                $selfAndAncestorsIds = array_unique(array_merge([$parentCategory->id], $ancestorIds));

                $parentPackageMappings = PackageCategory::whereIn('category_id', $selfAndAncestorsIds)
                    ->whereHas('package', fn($q) => $q->where(['status' => 1, 'is_discontinued' => 0]))
                    ->pluck('package_id')
                    ->unique()
                    ->toArray();

                $parentCategory->packages_count = count($parentPackageMappings);

                $applyListingStatus($parentCategory, $parentPackageMappings);
            }

            ResponseService::successResponse(null, $sql, ['self_category' => $parentCategory ?? null]);

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategories');
            ResponseService::errorResponse();
        }
    }


    /** Get Parent Category Tree */
    public function getParentCategoryTree(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_category_id' => 'nullable|integer',
            'tree' => 'nullable|boolean',
            'slug' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $sql = Category::when($request->child_category_id, function ($sql) use ($request) {
                $sql->where('categories.id', $request->child_category_id);
            })
            ->when($request->slug, function ($sql) use ($request) {
                $sql->where('slug', $request->slug);
            })
            ->with('translations', 'seoDetail.translations')
            ->firstOrFail()
            ->ancestorsAndSelf()
            ->breadthFirst()
            ->get();

            if ($request->tree) {
                $sql = $sql->toTree();
            }
            ResponseService::successResponse(null, $sql);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategories');
            ResponseService::errorResponse();
        }
    }

    /** Get Categories */
    public function getCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_code' => 'nullable',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $categories = Category::all();
            $languageCode = $request->get('language_code', 'en');

            $translator = new GoogleTranslate($languageCode);
            $categoriesJson = $categories->toJson();
            $translatedJson = $translator->translate($categoriesJson);
            $translatedCategories = json_decode($translatedJson, true);

            return ResponseService::successResponse(null, $translatedCategories);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategories');
            ResponseService::errorResponse();
        }
    }

    /** Get Categories Slug */
    public function getCategoriesSlug(Request $request)
    {
        try {
            $categories = Category::without('translations')
                ->select('id', 'slug', 'path', 'updated_at')
                ->where('status', 1)
                ->paginate(500);

            $categories->getCollection()->transform(function ($category) {
                return [
                    'path' => $category->path,
                    'updated_at' => $category->updated_at
                ];
            });

            if ($categories->isEmpty()) {
                return ResponseService::errorResponse(__('No active Categories found.'));
            }

            return ResponseService::successResponse(__('Active Categories slugs fetched successfully.'), $categories);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCategoriesSlug');
            ResponseService::errorResponse();
        }
    }

    /** Get Custom Fields */
    public function getCustomFields(Request $request)
    {
        try {

            $filter = filter_var($request->input('filter', false), FILTER_VALIDATE_BOOLEAN);
            $categoryId = $request->input('category_id');

            // Build category IDs array including all ancestor categories
            $categoryIds = [(int)$categoryId];
            $category = Category::find($categoryId);
            while ($category && $category->parent_category_id) {
                $categoryIds[] = $category->parent_category_id;
                $category = $category->parent;
            }

            // Load custom fields
            $customFieldsQuery = CustomField::with('translations')
                ->whereHas('custom_field_category', function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                })
                ->where('status', 1);

            // Apply filtering logic
            if ($filter === true) {

                // Modify the collection with filtering
                $customFields = $customFieldsQuery->clone()->whereNotIn('type',array('number','textbox','fileinput'))->get()->filter(function ($field) use ($categoryIds) {

                    // Only filter for dropdown/checkbox/radio
                    if (! in_array($field->type, ['dropdown', 'checkbox', 'radio'])) {
                        return true; // keep text, number etc.
                    }

                    // Get used values for this field (pluck only value column)
                    $values = ItemCustomFieldValue::where('custom_field_id', $field->id)
                        ->whereHas('item', function ($q) use ($categoryIds) {
                            $q->getNonExpiredItems()
                                ->whereNull('deleted_at')
                                ->where('status', 'approved')
                                ->whereIn('category_id', $categoryIds);
                        })
                        ->pluck('value')
                        ->toArray();

                    $used = [];

                    // Decode values properly
                    foreach ($values as $raw) {
                        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

                        if (is_array($decoded)) {
                            $used = array_merge($used, $decoded);
                        } else {
                            $used[] = $decoded;
                        }
                    }

                    $used = array_unique(array_filter($used));

                    // ❌ Remove the entire field if no used values exist
                    if (empty($used)) {
                        return false;
                    }

                    // Get original main language values before filtering
                    $originalMainValues = $field->values ?? [];

                    // Filter original field values
                    $field->values = array_values(array_intersect($originalMainValues, $used));

                    // Find indices of used values in original main language array
                    $usedIndices = [];
                    foreach ($field->values as $usedValue) {
                        $index = array_search($usedValue, $originalMainValues);
                        if ($index !== false) {
                            $usedIndices[] = $index;
                        }
                    }

                    // Filter translations by same indices to maintain alignment
                    foreach ($field->translations as $t) {
                        $translationValues = $t->value ?? [];
                        if (is_array($translationValues) && count($translationValues) > 0) {
                            $filteredTranslationValues = [];
                            foreach ($usedIndices as $idx) {
                                if (isset($translationValues[$idx])) {
                                    $filteredTranslationValues[] = $translationValues[$idx];
                                }
                            }
                            $t->value = array_values($filteredTranslationValues);
                        }
                    }

                    return true; // KEEP field
                })->values(); // re-index collection
            }else{
                $customFields = $customFieldsQuery->clone()->get();
            }

            // Load translated attributes
            $customFields->each(function ($field) {
                $field->translated_name = $field->translated_name;
                $field->translated_value = $field->translated_value;
            });

            ResponseService::successResponse(__('Data Fetched successfully'), $customFields);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCustomFields');
            ResponseService::errorResponse();
        }
    }
}
