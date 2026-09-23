<?php

namespace App\Http\Controllers;

use App\Models\BannerAd;
use App\Models\Category;
use App\Models\FeatureSection;
use App\Models\HomeScreenSection;
use App\Models\Item;
use App\Services\BootstrapTableService;
use App\Services\FileService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class BannerAdController extends Controller
{
    private string $uploadFolder;

    public function __construct()
    {
        $this->uploadFolder = 'banner-ads';
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['banner-ad-list', 'banner-ad-create', 'banner-ad-update', 'banner-ad-delete']);

        return view('banner-ad.index');
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('banner-ad-create');

        // Roots first (so dropdowntree recursion starts from a top-level row), then by sequence — matches the create-ad category order
        $categories = Category::where('status', 1)->select('id', 'name', 'parent_category_id', 'sequence')->orderByRaw('parent_category_id IS NULL DESC')->orderBy('sequence')->get();
        $homepageSections = $this->buildHomepageSections();

        return view('banner-ad.create', compact('categories', 'homepageSections'));
    }

    /**
     * Homepage section layout used by the placement wizard (create + edit).
     */
    private function buildHomepageSections(): array
    {
        $homeSections = HomeScreenSection::where('is_active', 1)
            ->orderBy('sequence')
            ->whereNotIn('section_type', ['all_categories'])
            ->get(['id', 'section_type', 'sequence']);

        $featureSections = FeatureSection::orderBy('sequence')->get()->map(function ($fs) {
            return [
                'id' => $fs->id,
                'title' => $fs->translated_name ?? $fs->title,
            ];
        });

        $homepageSections = [];
        foreach ($homeSections as $s) {
            if ($s->section_type === 'featured_section') {
                foreach ($featureSections as $fs) {
                    $homepageSections[] = [
                        'id' => 'feature_' . $fs['id'],
                        'label' => $fs['title'],
                        'type' => 'card_grid',
                        'hs_id' => $s->id,
                        'feature_id' => $fs['id'],
                    ];
                }
                continue;
            }

            $sectionKey = $s->section_type === 'all_ads' ? 'all_advertisement' : $s->section_type;
            $homepageSections[] = [
                'id' => $sectionKey,
                'label' => $this->sectionLabel($s->section_type),
                'type' => $this->sectionPreviewType($s->section_type),
                'hs_id' => $s->id,
            ];
        }

        return $homepageSections;
    }

    /**
     * Empty-string form values for nullable id / enum columns must reach the DB as NULL —
     * '' breaks foreign-key columns and enum columns.
     */
    private function nullIfBlank($value)
    {
        return ($value === '' || $value === null) ? null : $value;
    }

    private function sectionLabel(string $type): string
    {
        return match ($type) {
            'slider' => __('Slider'),
            'all_categories' => __('Categories'),
            'popular_categories' => __('Popular Categories'),
            'all_ads' => __('All Advertisement'),
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    private function sectionPreviewType(string $type): string
    {
        return match ($type) {
            'slider' => 'slider',
            'all_categories' => 'cat_nav',
            'popular_categories' => 'categories',
            'all_ads' => 'all_advertisement',
            default => 'card_grid',
        };
    }

    /**
     * @return array{status: 'ok'|'disabled'|'deleted', name: ?string}|null
     */
    private function resolveCategoryState($categoryId): ?array
    {
        if (!$categoryId) {
            return null;
        }
        // Category has no soft-deletes — a missing row means hard-deleted.
        $category = Category::find($categoryId);
        if (!$category) {
            return ['status' => 'deleted', 'name' => null];
        }
        if ((int) $category->status !== 1) {
            return ['status' => 'disabled', 'name' => $category->name];
        }
        return ['status' => 'ok', 'name' => $category->name];
    }

    /**
     * @return array{status: 'ok'|'disabled'|'deleted', name: ?string}|null
     */
    private function resolveAdvertisementState($advertisementId): ?array
    {
        if (!$advertisementId) {
            return null;
        }
        $item = Item::withTrashed()->find($advertisementId);
        if (!$item) {
            return ['status' => 'deleted', 'name' => null];
        }
        if ($item->trashed()) {
            return ['status' => 'deleted', 'name' => $item->name];
        }
        if ($item->status !== 'approved') {
            return ['status' => 'disabled', 'name' => $item->name];
        }
        return ['status' => 'ok', 'name' => $item->name];
    }

    public function itemsByCategory(Request $request)
    {
        $request->validate(['category_id' => 'required|integer']);

        $items = Item::getNonExpiredItems()->where('status', 'approved')
            ->where('category_id', $request->category_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function existingBanners(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['banner-ad-create', 'banner-ad-update']);

        $request->validate([
            'platform' => 'required|in:web,app',
            'page'     => 'required|in:home,detail,listing',
        ]);

        $banners = BannerAd::where('platform', $request->platform)
            ->where('page', $request->page)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get([
                'id', 'group_id', 'layout', 'position', 'placement', 'image',
                'home_screen_section_id', 'feature_section_id',
                'detail_page_section', 'listing_page_section',
            ]);

        return response()->json(['data' => $banners]);
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenRedirect('banner-ad-create');
        $validator = Validator::make($request->all(), [
            'platform'                          => 'required|in:web,app',
            'page'                              => 'required|in:home,detail,listing',
            'layout'                            => 'required|in:single,dual,single_side,dual_side,large',
            'home_screen_section_id'            => 'required_if:page,home|nullable|integer',
            'feature_section_id'                => 'nullable|integer',
            'detail_page_section'               => 'required_if:page,detail|nullable|in:image,ad_info,custom_fields,about_ad,location,similar_ads',
            'listing_page_section'              => 'required_if:page,listing|nullable|in:category_list,listing_data',
            'placement'                         => 'required_if:layout,single,dual|nullable|in:above,below',
            'banners'                           => 'required|array',
            'banners.*.ad_type'                 => 'required|in:only_banner,category,advertisement,external_link',
            'banners.*.position'                => 'required|numeric',
            'banners.*.category_id'             => 'required_if:banners.*.ad_type,category|nullable|integer',
            'banners.*.advertisement_id'        => 'required_if:banners.*.ad_type,advertisement|nullable|integer',
            'banners.*.link'                    => 'required_if:banners.*.ad_type,external_link|nullable|active_url',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            if($request->has('banners') && is_array($request->banners)){
                $data = array();
                $groupId = Str::uuid()->toString();
                foreach ($request->banners as $banner) {
                    $imagePath = FileService::upload($banner['image'], 'promo-images'); // promo-images name used, Cause: image URL path contains trigger word — banner-ad, banner, ads, advert. Ad-blocker filter lists match those substrings → block.
                    $data[] = array(
                        'platform'                      => $request->platform,
                        'page'                          => $request->page,
                        'layout'                        => $request->layout,
                        'group_id'                      => $request->layout == 'dual_side' || $request->layout == 'dual' ? $groupId : null,
                        'home_screen_section_id'        => $this->nullIfBlank($request->home_screen_section_id),
                        'feature_section_id'            => $this->nullIfBlank($request->feature_section_id),
                        'detail_page_section'           => $this->nullIfBlank($request->detail_page_section),
                        'listing_page_section'          => $this->nullIfBlank($request->listing_page_section),
                        'placement'                     => $this->nullIfBlank($request->placement),
                        'ad_type'                       => $banner['ad_type'],
                        'position'                      => $banner['position'],
                        'category_id'                   => $this->nullIfBlank($banner['category_id'] ?? null),
                        'advertisement_id'              => $this->nullIfBlank($banner['advertisement_id'] ?? null),
                        'link'                          => $banner['link'] ?? null,
                        'image'                         => $imagePath,
                    );
                }

                if(!empty($data)){
                    BannerAd::insert($data);
                    ResponseService::successResponse('Banner Ad created successfully');
                }
            }
            return ResponseService::errorResponse('Banners not found!');
        } catch (Throwable $th) {
            return ResponseService::logErrorResponse($th, 'BannerAdController -> store');
        }
    }

    public function show(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['banner-ad-list', 'banner-ad-update', 'banner-ad-delete']);

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $order = $request->input('order', 'desc') === 'asc' ? 'asc' : 'desc';

        $base = BannerAd::query();
        if (!empty($request->search)) {
            $base->search($request->search);
        }

        if ($request->filled('platform') && in_array($request->platform, ['web', 'app'], true)) {
            $base->where('platform', $request->platform);
        }
        if ($request->filled('page_filter') && in_array($request->page_filter, ['home', 'detail', 'listing'], true)) {
            $base->where('page', $request->page_filter);
        }
        if ($request->filled('layout') && in_array($request->layout, ['single', 'dual', 'single_side', 'dual_side', 'large'], true)) {
            $base->where('layout', $request->layout);
        }
        if ($request->filled('ad_type') && in_array($request->ad_type, ['only_banner', 'category', 'advertisement', 'external_link'], true)) {
            // ad_type can differ between paired rows in a group — match if any row in the group has it.
            $base->where(function ($q) use ($request) {
                $q->where('ad_type', $request->ad_type)
                    ->orWhereIn('group_id', BannerAd::where('ad_type', $request->ad_type)->whereNotNull('group_id')->select('group_id'));
            });
        }

        // Logical unit = group_id for dual layouts (paired banners), else the row id.
        // Dual banners share a group_id and must paginate / display as one entity.
        $keyExpr = 'COALESCE(`group_id`, CAST(`id` AS CHAR))';

        $unitsQuery = (clone $base)
            ->selectRaw("$keyExpr as unit_key, MAX(id) as max_id")
            ->groupByRaw($keyExpr);

        $total = (clone $unitsQuery)->get()->count();

        $units = $unitsQuery
            ->orderBy('max_id', $order)
            ->skip($offset)->take($limit)
            ->get();

        // Resolve the page's unit keys back to their banner rows.
        $groupKeys = [];
        $singleIds = [];
        foreach ($units as $u) {
            if (ctype_digit((string) $u->unit_key)) {
                $singleIds[] = (int) $u->unit_key;
            } else {
                $groupKeys[] = $u->unit_key;
            }
        }

        $banners = $units->isEmpty()
            ? collect()
            : BannerAd::query()
                ->when($groupKeys, fn($q) => $q->orWhereIn('group_id', $groupKeys))
                ->when($singleIds, fn($q) => $q->orWhereIn('id', $singleIds))
                ->orderBy('position')
                ->get()
                ->groupBy(fn($b) => $b->group_id ?? (string) $b->id);

        $pageLabels = [
            'home'    => __('Homepage'),
            'detail'  => __('Ads Details Page'),
            'listing' => __('Listing Page'),
        ];
        $platformLabels = [
            'web' => __('Web'),
            'app' => __('App'),
        ];
        $adTypeLabels = [
            'only_banner'   => __('Only Banner'),
            'category'      => __('Category'),
            'advertisement' => __('Advertisement'),
            'external_link' => __('External Link'),
        ];
        $layoutLabels = [
            'single'      => __('Single'),
            'dual'        => __('Dual'),
            'single_side' => __('Single Side'),
            'dual_side'   => __('Dual Side'),
            'large'       => __('Large'),
        ];

        $rows = [];
        foreach ($units as $u) {
            $group = $banners->get($u->unit_key);
            if (empty($group)) {
                continue;
            }
            $first = $group->first();

            $operate = '';
            if(ResponseService::noPermissionThenSendJson('banner-ad-update')){
                $operate .= BootstrapTableService::editButton(route('banner-ad.edit', $first->id));
            }
            if(ResponseService::noPermissionThenSendJson('banner-ad-delete')){
                $operate .= BootstrapTableService::deleteButton(route('banner-ad.destroy', $first->id));
            }

            $images = $group->pluck('image')->filter()->values()->all();
            $adTypes = $group->pluck('ad_type')
                ->map(fn($t) => $adTypeLabels[$t] ?? $t)
                ->unique()->implode(', ');

            $rows[] = [
                'id'       => $first->id,
                'images'   => $images,
                'layout'   => $layoutLabels[$first->layout] ?? $first->layout,
                'page'     => $pageLabels[$first->page] ?? $first->page,
                'platform' => $platformLabels[$first->platform] ?? $first->platform,
                'ad_type'  => $adTypes,
                'action'   => $operate,
            ];
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function edit($id)
    {
        ResponseService::noPermissionThenRedirect('banner-ad-update');

        $bannerAd = BannerAd::findOrFail($id);

        // A unit is the whole group for dual layouts, else the single row.
        $rows = $bannerAd->group_id
            ? BannerAd::group($bannerAd->group_id)->orderBy('position')->get()
            : collect([$bannerAd]);
        $first = $rows->first();

        $editData = [
            'id'                     => $first->id,
            'group_id'               => $first->group_id,
            'platform'               => $first->platform,
            'page'                   => $first->page,
            'layout'                 => $first->layout,
            'placement'              => $first->placement,
            'home_screen_section_id' => $first->home_screen_section_id,
            'feature_section_id'     => $first->feature_section_id,
            'detail_page_section'    => $first->detail_page_section,
            'listing_page_section'   => $first->listing_page_section,
            'banner_ids'             => $rows->pluck('id')->all(),
            'banners'                => $rows->map(fn ($b) => [
                'position'         => $b->position,
                'image'            => $b->image,
                'ad_type'          => $b->ad_type,
                'category_id'      => $b->category_id,
                'advertisement_id' => $b->advertisement_id,
                'link'             => $b->link,
                'category_state'   => $this->resolveCategoryState($b->category_id),
                'advertisement_state' => $this->resolveAdvertisementState($b->advertisement_id),
            ])->values(),
        ];

        // Roots first (so dropdowntree recursion starts from a top-level row), then by sequence — matches the create-ad category order
        $categories = Category::where('status', 1)->select('id', 'name', 'parent_category_id', 'sequence')->orderByRaw('parent_category_id IS NULL DESC')->orderBy('sequence')->get();

        // Keep a disabled-but-currently-assigned category selectable so the edit form doesn't show it blank.
        $usedCategoryIds = $rows->pluck('category_id')->filter()->unique();
        $missingActiveIds = $usedCategoryIds->diff($categories->pluck('id'));
        if ($missingActiveIds->isNotEmpty()) {
            $disabledUsed = Category::whereIn('id', $missingActiveIds)
                ->select('id', 'name', 'parent_category_id', 'sequence')
                ->get();
            $categories = $categories->concat($disabledUsed);
        }

        $homepageSections = $this->buildHomepageSections();

        return view('banner-ad.edit', compact('bannerAd', 'editData', 'categories', 'homepageSections'));
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenRedirect('banner-ad-update');

        $validator = Validator::make($request->all(), [
            'platform'                          => 'required|in:web,app',
            'page'                              => 'required|in:home,detail,listing',
            'layout'                            => 'required|in:single,dual,single_side,dual_side,large',
            'home_screen_section_id'            => 'required_if:page,home|nullable|integer',
            'feature_section_id'                => 'nullable|integer',
            'detail_page_section'               => 'required_if:page,detail|nullable|in:image,ad_info,custom_fields,about_ad,location,similar_ads',
            'listing_page_section'              => 'required_if:page,listing|nullable|in:category_list,listing_data',
            'placement'                         => 'required_if:layout,single,dual|nullable|in:above,below',
            'banners'                           => 'required|array',
            'banners.*.ad_type'                 => 'required|in:only_banner,category,advertisement,external_link',
            'banners.*.position'                => 'required|numeric',
            'banners.*.category_id'             => 'required_if:banners.*.ad_type,category|nullable|integer',
            'banners.*.advertisement_id'        => 'required_if:banners.*.ad_type,advertisement|nullable|integer',
            'banners.*.link'                    => 'required_if:banners.*.ad_type,external_link|nullable|active_url',
        ]);

        if ($validator->fails()) {
            return ResponseService::errorResponse($validator->errors()->first());
        }

        try {
            $bannerAd = BannerAd::findOrFail($id);

            // Resolve the unit's rows; dual layouts span the whole group.
            $oldRows = ($bannerAd->group_id
                ? BannerAd::group($bannerAd->group_id)->orderBy('position')->get()
                : collect([$bannerAd]))->values();

            $submitted = array_values($request->banners);
            $isDual = in_array($request->layout, ['dual', 'dual_side'], true);
            // Keep the existing group when staying dual, mint one when single -> dual.
            $groupId = $isDual ? ($bannerAd->group_id ?: Str::uuid()->toString()) : null;

            DB::transaction(function () use ($submitted, $oldRows, $request, $groupId) {
                foreach ($submitted as $i => $banner) {
                    $row = $oldRows->get($i) ?? new BannerAd();

                    $file = $banner['image'] ?? null;
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        // image URL path uses promo-images — banner/ad substrings trip ad-blockers.
                        $imagePath = $row->exists
                            ? FileService::replace($file, 'promo-images', $row->getRawOriginal('image'))
                            : FileService::upload($file, 'promo-images');
                    } else {
                        // No new file uploaded — keep the current image.
                        $imagePath = $row->exists ? $row->getRawOriginal('image') : null;
                    }

                    $row->fill([
                        'platform'               => $request->platform,
                        'page'                   => $request->page,
                        'layout'                 => $request->layout,
                        'group_id'               => $groupId,
                        'home_screen_section_id' => $this->nullIfBlank($request->home_screen_section_id),
                        'feature_section_id'     => $this->nullIfBlank($request->feature_section_id),
                        'detail_page_section'    => $this->nullIfBlank($request->detail_page_section),
                        'listing_page_section'   => $this->nullIfBlank($request->listing_page_section),
                        'placement'              => $this->nullIfBlank($request->placement),
                        'ad_type'                => $banner['ad_type'],
                        'position'               => $banner['position'],
                        'category_id'            => $this->nullIfBlank($banner['category_id'] ?? null),
                        'advertisement_id'       => $this->nullIfBlank($banner['advertisement_id'] ?? null),
                        'link'                   => $banner['link'] ?? null,
                        'image'                  => $imagePath,
                    ]);
                    $row->save();
                }

                // Layout shrank (dual -> single): drop surplus rows + their images.
                if ($oldRows->count() > count($submitted)) {
                    foreach ($oldRows->slice(count($submitted)) as $extra) {
                        $extra->delete();
                    }
                }
            });

            return ResponseService::successResponse('Banner Ad updated successfully');
        } catch (Throwable $th) {
            return ResponseService::logErrorResponse($th, 'BannerAdController -> update');
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenRedirect('banner-ad-delete');
        try {
            $bannerAd = BannerAd::findOrFail($id);
            if($bannerAd->layout == 'dual' || $bannerAd->layout == 'dual_side'){
                $groupedBanner = $bannerAd->where('group_id', $bannerAd->group_id)->get();
                foreach ($groupedBanner as $banner) {
                    $banner->findOrFail($banner->id)->delete();
                }
            }else{
                $bannerAd->delete();
            }
            ResponseService::successResponse('Banner Ad deleted successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'BannerAdController -> destroy');
            ResponseService::errorResponse();
        }
    }
}
