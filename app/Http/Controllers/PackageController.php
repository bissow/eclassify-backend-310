<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Package;
use App\Models\PackageCategory;

use App\Models\PaymentTransaction;
use App\Models\UserFcmToken;
use App\Models\UserPurchasedPackage;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\NotificationService;
use App\Services\PaymentReceiptService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PackageController extends Controller {

    private string $uploadFolder;

    public function __construct() {
        $this->uploadFolder = 'packages';
    }

    public function index() {
        ResponseService::noAnyPermissionThenRedirect([
            'advertisement-listing-package-list', 'advertisement-listing-package-create', 'advertisement-listing-package-update', 'advertisement-listing-package-delete',
            'featured-advertisement-package-list', 'featured-advertisement-package-create', 'featured-advertisement-package-update', 'featured-advertisement-package-delete'
        ]);
        $categories = Category::without('translations')
            ->where('status', 1)
            ->orderBy('sequence')
            ->get()
            ->each->setAppends([]);
        $categories = HelperService::buildNestedChildSubcategoryObject($categories);
        $languages = CachingService::getLanguages()->values();
        $currency_symbol = CachingService::getSystemSettings('currency_symbol');
        return view('packages.index', compact('categories', 'currency_symbol', 'languages'));
    }

    public function create(Request $request) {
        ResponseService::noAnyPermissionThenRedirect([
            'advertisement-listing-package-create',
            'featured-advertisement-package-create'
        ]);
        $categories = Category::without('translations')
            ->where('status', 1)
            ->orderBy('sequence')
            ->get()
            ->each->setAppends([]);
        $categories = HelperService::buildNestedChildSubcategoryObject($categories);
        $languages = CachingService::getLanguages()->values();
        $currency_symbol = CachingService::getSystemSettings('currency_symbol');
        $selected_categories = [];
        $selected_all_categories = [];
        return view('packages.create', compact('categories', 'currency_symbol', 'languages', 'selected_categories', 'selected_all_categories'));
    }
    public function store(Request $request) {
        ResponseService::noAnyPermissionThenSendJson([
            'advertisement-listing-package-create',
            'featured-advertisement-package-create'
        ]);
        
        $languages = CachingService::getLanguages();
        $defaultLangId = 1;
        $otherLanguages = $languages->where('id', '!=', $defaultLangId);

        // Support both new UI (`type`) and legacy UI (`package_types[]`)
        $resolvedPackageType = $request->input('type');
        if (empty($resolvedPackageType)) {
            $resolvedPackageType = $request->input('package_types.0', 'item_listing');
        }

        $rules = [
            "name.$defaultLangId"     => 'required|string',
            'price'                  => 'required|numeric',
            'discount_in_percentage' => 'required|numeric',
            'final_price'            => 'required|numeric',
            'package_duration_type'  => 'required|in:limited,unlimited',
            'duration'               => ($request->package_duration_type === 'limited') ? 'required|integer|min:1' : 'nullable',
            'type'                   => 'required|in:item_listing,advertisement',
            'icon'                   => 'required|mimes:jpeg,jpg,png|max:7168',
            'is_global'              => 'nullable|in:0,1',
            'selected_categories'     => 'required_unless:is_global,1|array|min:1',
            'ads_item_limit_type'    => 'required_if:type,item_listing|in:limited,unlimited',
            'ads_item_limit'         => 'required_if:ads_item_limit_type,limited',
            'ads_listing_duration_type' => 'required_if:type,item_listing|in:standard,package,custom',
            'ads_listing_duration_days' => ($request->ads_listing_duration_type === 'custom') ? 'required|integer|min:1' : 'nullable',
            'featured_item_limit_type' => 'required_if:type,advertisement|in:limited,unlimited',
            'featured_item_limit'    => 'required_if:featured_item_limit_type,limited',
            'featured_ads_duration_type' => 'required_if:type,advertisement|in:standard,package,custom',
            'featured_ads_duration_days' => ($request->featured_ads_duration_type === 'custom') ? 'required|integer|min:1' : 'nullable',
        ];

        foreach ($otherLanguages as $lang) {
            $langId = $lang->id;
            $rules["name.$langId"] = 'nullable|string';
        }

        // Get package type - needed before validation for is_global enforcement
        $packageType = $resolvedPackageType;
        
        // Set is_global to 1 for advertisement packages before validation
        if ($packageType === 'advertisement' && !$request->has('is_global')) {
            $request->merge(['is_global' => 1]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            
            // Prepare key points for default language
            $defaultKeyPoints = $request->input("key_points.$defaultLangId", []);
            $defaultKeyPoints = array_filter($defaultKeyPoints); // Remove empty values
            
            // If old description exists, add it as first key point
            // $oldDescription = $request->input("description.$defaultLangId");
            // if (!empty($oldDescription) && !in_array($oldDescription, $defaultKeyPoints)) {
            //     array_unshift($defaultKeyPoints, $oldDescription);
            // }
            
            // Auto-calculate final price if not provided or if price/discount changed
            $finalPrice = $request->final_price;
            if (empty($finalPrice) || ($request->has('price') && $request->has('discount_in_percentage'))) {
                $price = (float) $request->price;
                $discount = (float) $request->discount_in_percentage;
                if ($price > 0 && $discount >= 0 && $discount <= 100) {
                    $discountAmount = ($price * $discount) / 100;
                    $finalPrice = $price - $discountAmount;
                }
            }
            
            // Set is_global: 1 for advertisement packages by default, otherwise use request value or 0
            $isGlobal = ($packageType === 'advertisement') ? 1 : ($request->is_global ?? 0);
            
            $data = [
                'name' => $request->input("name.$defaultLangId"),
                'price' => $request->price,
                'discount_in_percentage' => $request->discount_in_percentage,
                'final_price' => $finalPrice,
                'ios_product_id' => $request->ios_product_id,
                'duration' => ($request->package_duration_type == "limited") ? $request->duration : null,
                'type' => $packageType,
                'is_global' => $isGlobal,
                'key_points' => !empty($defaultKeyPoints) ? json_encode($defaultKeyPoints, JSON_UNESCAPED_UNICODE) : null,
            ];
            
            // Handle item limits and duration types based on package type
            if ($packageType === 'item_listing') {
                // item_limit can be "unlimited" or a number
                if ($request->ads_item_limit_type == "limited") {
                    $data['item_limit'] = $request->ads_item_limit;
                } else {
                    $data['item_limit'] = "unlimited";
                }
                $data['listing_duration_type'] = $request->ads_listing_duration_type ?? 'standard';
                // Set days: 30 for 'standard', null for 'package', custom value for 'custom'
                if ($request->ads_listing_duration_type == 'standard') {
                    $data['listing_duration_days'] = 30;
                } elseif ($request->ads_listing_duration_type == 'package') {
                    $data['listing_duration_days'] = $data['duration']; // Uses package duration
                } elseif ($request->ads_listing_duration_type == 'custom') {
                    $data['listing_duration_days'] = $request->ads_listing_duration_days;
                } else {
                    $data['listing_duration_days'] = null;
                }
            } else if ($packageType === 'advertisement') {
                // item_limit can be "unlimited" or a number
                if ($request->featured_item_limit_type == "limited") {
                    $data['item_limit'] = $request->featured_item_limit;
                } else {
                    $data['item_limit'] = "unlimited";
                }
                // Use listing_duration for advertisement packages too
                $data['listing_duration_type'] = $request->featured_ads_duration_type ?? 'standard';
                // Set days: 30 for 'standard', null for 'package', custom value for 'custom'
                if ($request->featured_ads_duration_type == 'standard') {
                    $data['listing_duration_days'] = 30;
                } elseif ($request->featured_ads_duration_type == 'package') {
                    $data['listing_duration_days'] = $data['duration']; // Uses package duration
                } elseif ($request->featured_ads_duration_type == 'custom') {
                    $data['listing_duration_days'] = $request->featured_ads_duration_days;
                } else {
                    $data['listing_duration_days'] = null;
                }
            }
            
            /** Make Refer Hide */
            // Refer points per-package settings (nullable = use global)
            // $data['refer_max_points_usage_percentage'] = $request->refer_max_points_usage_percentage ?: null;
            // $data['refer_min_points_to_use'] = $request->refer_min_points_to_use ?: null;
            // $data['refer_max_points_to_use'] = $request->refer_max_points_to_use ?: null;

            $data['refer_max_points_usage_percentage'] = null;
            $data['refer_min_points_to_use'] = null;
            $data['refer_max_points_to_use'] = null;

            if ($packageType === 'item_listing') {
                $data['is_reel_allowed'] = $request->boolean('is_reel_allowed', false) ? 1 : 0;
            }

            if ($request->hasFile('icon')) {
                $data['icon'] = FileService::compressAndUpload($request->file('icon'), $this->uploadFolder);
            }
            $package = Package::create($data);

            // Handle categories
            if ($request->is_global == 1) {
                // Global package - no categories needed
            } else {
                if (!empty($request->selected_categories)) {
                    $selectedIds = collect($request->selected_categories)->map(fn($id) => (int) $id);
                    $filteredIds = $this->filterCategorySelections($selectedIds->toArray());

                    $categoryMappings = collect($filteredIds)->map(function ($categoryId) use ($package) {
                        return [
                            'category_id' => $categoryId,
                            'package_id' => $package->id,
                        ];
                    })->toArray();
                    PackageCategory::upsert($categoryMappings, ['package_id', 'category_id']);
                }
            }

            // Handle translations with key points
            $translationData = [];
            foreach ($otherLanguages as $lang) {
                $langId = $lang->id;
                $translatedName = $request->input("name.$langId");
                $translatedKeyPoints = $request->input("key_points.$langId", []);
                $translatedKeyPoints = array_filter($translatedKeyPoints); // Remove empty values

                if (!empty($translatedName)) {
                    $translationData[] = [
                        'translatable_id'   => $package->id,
                        'translatable_type' => get_class($package),
                        'key'               => 'name',
                        'value'             => $translatedName,
                        'language_id'       => $langId,
                    ];
                }
                if (!empty($translatedKeyPoints)) {
                    $translationData[] = [
                        'translatable_id'   => $package->id,
                        'translatable_type' => get_class($package),
                        'key'               => 'key_points',
                        'value'             => json_encode($translatedKeyPoints, JSON_UNESCAPED_UNICODE),
                        'language_id'       => $langId,
                    ];
                }
            }
            if (!empty($translationData)) {
                HelperService::storeTranslations($translationData);
            }
            
            DB::commit();
            ResponseService::successResponse('Package Successfully Added', $data);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, "PackageController -> store method");
            ResponseService::errorResponse();
        }

    }

    public function show(Request $request) {
        ResponseService::noAnyPermissionThenSendJson([
            'advertisement-listing-package-list',
            'featured-advertisement-package-list'
        ]);
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $today = date('Y-m-d');
        $discontinuedParam = $request->input('is_discontinued'); // null=all, 0=active, 1=discontinued
        $isDiscontinued = $discontinuedParam === '1' || $discontinuedParam === 1;
        $statusParam = $request->status ?? null;

        $sql = Package::with(['translations', 'categories']);
        if ($discontinuedParam !== null && $discontinuedParam !== '') {
            $sql->where('is_discontinued', $isDiscontinued ? 1 : 0);
        }
        if($statusParam !== null && $statusParam !== ''){
            $sql->where('status', $statusParam);
        }
        $sql
            ->withSum(['payment_transactions as total_revenue' => function ($query) {
                $query->whereIn('payment_status', ['succeed', 'succeeded', 'success', 'approved', 'completed']);
            }], 'amount')
            ->withCount([
                'user_purchased_packages as total_subscribers' => function ($query) use ($today) {
                    $query->where(function ($q) use ($today) {
                        $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                    });
                },
                'user_purchased_packages as active_subscribers' => function ($query) use ($today) {
                    $query->where(function ($q) use ($today) {
                        $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                    });
                },
            ]);

        if (!empty($request->search)) {
            $sql = $sql->search($request->search);
        }
        if (!empty($request->filter)) {
            $filterString = html_entity_decode($request->filter, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            try {
                $filterData = json_decode($filterString, false, 512, JSON_THROW_ON_ERROR);
                $sql = $sql->filter($filterData);
            } catch (\JsonException $e) {
                return response()->json(['error' => 'Invalid JSON format in filter parameter'], 400);
            }
        }
        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $result = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        foreach ($result as $key => $row) {
            $tempRow = $row->toArray();
            $tempRow['category_names'] = $row->is_global == 1 ? 'Global' : 'Category Based';
            $tempRow['discontinued_at'] = $row->discontinued_at
                ? Carbon::parse($row->discontinued_at)->format('d-m-Y H:i')
                : null;

            $operate = '';
            if (Auth::user()->can('user-package-list')) {
                $operate .= BootstrapTableService::button('ph ph-users', '#', [], [
                    'title'          => __('View Subscribers'),
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#subscribersModal',
                    'data-package-id' => $row->id,
                    'data-package-name' => htmlspecialchars($row->name, ENT_QUOTES),
                    'onclick'        => 'loadSubscribers(this)',
                ]);
            }
            if (Auth::user()->can('advertisement-listing-package-update') || Auth::user()->can('featured-advertisement-package-update')) {
                if (!$isDiscontinued && $row->is_discontinued == 0) {
                    $operate .= BootstrapTableService::editButton(route('package.edit', $row->id));
                    $operate .= BootstrapTableService::button('ph ph-prohibit', '#', ['btn-outline-danger', 'discontinue-btn'], [
                        'title'            => __('Discontinue Package'),
                        'data-id'          => $row->id,
                        'data-name'        => htmlspecialchars($row->name, ENT_QUOTES),
                        'data-url'         => route('package.discontinue', $row->id),
                        'data-subscribers' => $row->total_subscribers,
                    ]);
                }
            }
            if (!empty($operate)) {
                $tempRow['operate'] = $operate;
            }
            $tempRow['total_revenue'] = round($row->total_revenue, 2) ?? 0;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function edit($id) {
        ResponseService::noPermissionThenRedirect([
            'advertisement-listing-package-update',
            'featured-advertisement-package-update'
        ]);
        $package = Package::with(['package_categories', 'translations'])->findOrFail($id);
        
        $translations = [];
        $translations[1] = [
            'name' => $package->name,
            'description' => $package->description,
        ];

        $grouped = $package->translations->groupBy('language_id');
        foreach ($grouped as $langId => $items) {
            $translations[$langId] = [];
            foreach ($items as $item) {
                $translations[$langId][$item->key] = $item->value;
            }
        }
        
        $selected_categories = $package->package_categories->pluck('category_id')->toArray();
        $selected_all_categories = $selected_categories;
        
        foreach ($selected_categories as $catId) {
            $categoryId = $catId;
            while ($categoryId) {
                $parent = Category::without('translations')->where('id', $categoryId)->value('parent_category_id');
                if ($parent) {
                    $selected_all_categories[] = $parent;
                    $categoryId = $parent;
                } else {
                    $categoryId = null;
                }
            }
        }
        
        $selected_all_categories = array_unique($selected_all_categories);

        $allCategoryPairs = Category::without('translations')->pluck('parent_category_id', 'id');
        foreach ($selected_categories as $catId) {
            $descendantIds = array_keys($allCategoryPairs->filter(fn($parentId) => $parentId == $catId)->all());
            $queue = $descendantIds;
            while (!empty($queue)) {
                $childId = array_shift($queue);
                if (!in_array($childId, $selected_categories)) {
                    $selected_categories[] = $childId;
                }
                if (!in_array($childId, $selected_all_categories)) {
                    $selected_all_categories[] = $childId;
                }
                $grandChildIds = array_keys($allCategoryPairs->filter(fn($parentId) => $parentId == $childId)->all());
                $queue = array_merge($queue, $grandChildIds);
            }
        }
        $selected_categories = array_unique($selected_categories);
        $selected_all_categories = array_unique($selected_all_categories);

        $categories = Category::without('translations')
            ->where('status', 1)
            ->orderBy('sequence')
            ->get()
            ->each->setAppends([]);
        $categories = HelperService::buildNestedChildSubcategoryObject($categories);
        $languages = CachingService::getLanguages()->values();
        $currency_symbol = CachingService::getSystemSettings('currency_symbol');
        
        return view('packages.edit', compact('package', 'categories', 'selected_categories', 'selected_all_categories', 'languages', 'translations', 'currency_symbol'));
    }

    public function update(Request $request, $id) {
        ResponseService::noAnyPermissionThenSendJson([
            'advertisement-listing-package-update',
            'featured-advertisement-package-update'
        ]);
        
        $languages = CachingService::getLanguages();
        $defaultLangId = 1;
        $otherLanguages = $languages->where('id', '!=', $defaultLangId);

        $package = Package::with('package_categories')->findOrFail($id);
        
        $rules = [
            "name.$defaultLangId"     => 'required|string',
            "description.$defaultLangId" => 'nullable|string',
            'icon'                   => 'nullable|mimes:jpeg,jpg,png|max:7168',
            'ios_product_id'         => 'nullable|string',
            'refer_max_points_usage_percentage' => 'nullable|integer|min:1|max:100',
            'refer_min_points_to_use' => 'nullable|integer|min:1',
            'refer_max_points_to_use' => 'nullable|integer|min:1',
        ];

        foreach ($otherLanguages as $lang) {
            $langId = $lang->id;
            $rules["name.$langId"] = 'nullable|string';
            $rules["description.$langId"] = 'nullable|string';
        }
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $data = [
                'name' => $request->input("name.$defaultLangId"),
                'description' => $request->input("description.$defaultLangId"),
                'ios_product_id' => $request->ios_product_id,
                // 'refer_max_points_usage_percentage' => $request->refer_max_points_usage_percentage ?: null,
                // 'refer_min_points_to_use' => $request->refer_min_points_to_use ?: null,
                // 'refer_max_points_to_use' => $request->refer_max_points_to_use ?: null,
            ];

            if ($request->hasFile('icon')) {
                $data['icon'] = FileService::compressAndReplace($request->file('icon'), $this->uploadFolder, $package->getRawOriginal('icon'));
            }

            // Prepare key points for default language
            $defaultKeyPoints = $request->input("key_points.$defaultLangId", []);
            $defaultKeyPoints = array_filter($defaultKeyPoints); // Remove empty values
            
            // If old description exists, add it as first key point
            $oldDescription = $request->input("description.$defaultLangId");
            if (!empty($oldDescription) && !in_array($oldDescription, $defaultKeyPoints)) {
                array_unshift($defaultKeyPoints, $oldDescription);
            }
            
            // Re-index array with array_values to prevent JSON object encoding
            $data['key_points'] = !empty($defaultKeyPoints) ? json_encode(array_values($defaultKeyPoints), JSON_UNESCAPED_UNICODE) : null;
            
            $package->update($data);

            $translationData = [];
            foreach ($otherLanguages as $lang) {
                $langId = $lang->id;
                $translatedName = $request->input("name.$langId");
                $translatedDescription = $request->input("description.$langId");
                $translatedKeyPoints = $request->input("key_points.$langId", []);
                $translatedKeyPoints = array_filter($translatedKeyPoints); // Remove empty values

                // If old description exists for this language, add it as first key point
                if (!empty($translatedDescription) && !in_array($translatedDescription, $translatedKeyPoints)) {
                    array_unshift($translatedKeyPoints, $translatedDescription);
                }

                if (!empty($translatedName)) {
                    $translationData[] = [
                        'translatable_id'   => $package->id,
                        'translatable_type' => get_class($package),
                        'key'               => 'name',
                        'value'             => $translatedName,
                        'language_id'       => $langId,
                    ];
                }
                if (!empty($translatedDescription)) {
                    $translationData[] = [
                        'translatable_id'   => $package->id,
                        'translatable_type' => get_class($package),
                        'key'               => 'description',
                        'value'             => $translatedDescription,
                        'language_id'       => $langId,
                    ];
                }
                if (!empty($translatedKeyPoints)) {
                    $translationData[] = [
                        'translatable_id'   => $package->id,
                        'translatable_type' => get_class($package),
                        'key'               => 'key_points',
                        // Re-index array with array_values to prevent JSON object encoding
                        'value'             => json_encode(array_values($translatedKeyPoints), JSON_UNESCAPED_UNICODE),
                        'language_id'       => $langId,
                    ];
                }
            }
            if (!empty($translationData)) {
                HelperService::storeTranslations($translationData);
            }

            DB::commit();
            ResponseService::successResponse("Package Successfully Updated");
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, "PackageController ->  update");
            ResponseService::errorResponse();
        }
    }
    public function discontinue(Request $request, $id)
    {
        ResponseService::noAnyPermissionThenSendJson([
            'advertisement-listing-package-update',
            'featured-advertisement-package-update'
        ]);
        try {
            $package = Package::findOrFail($id);

            if ($package->is_discontinued) {
                ResponseService::errorResponse('Package is already discontinued');
                return;
            }

            $package->update([
                'is_discontinued' => 1,
                'discontinued_at' => Carbon::now(),
            ]);

            ResponseService::successResponse('Package has been discontinued. It will be removed from listings once all active subscriptions expire.');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "PackageController -> discontinue");
            ResponseService::errorResponse();
        }
    }

    public function userPackagesIndex() {
        ResponseService::noPermissionThenRedirect('user-package-list');
        return view('packages.user');
    }

    public function userPackagesShow(Request $request) {
        ResponseService::noPermissionThenSendJson('user-package-list');
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $sql = UserPurchasedPackage::with('user:id,name,profile', 'package:id,name');
        if (!empty($request->package_id)) {
            $sql = $sql->where('package_id', $request->package_id);
        }
        if (!empty($request->search)) {
            $sql = $sql->search($request->search);
        }
        if (!empty($request->status)) {
            $today = date('Y-m-d');
            if ($request->status === 'active') {
                $sql = $sql->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                });
            } elseif ($request->status === 'expired') {
                $sql = $sql->whereNotNull('end_date')->whereDate('end_date', '<', $today);
            }
        }
        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $result = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        foreach ($result as $key => $row) {
            $rows[] = $row->toArray();
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function paymentTransactionIndex() {
        ResponseService::noPermissionThenRedirect('payment-transactions-list');
        return view('packages.payment-transactions');
    }

    public function paymentTransactionShow(Request $request) {
        ResponseService::noPermissionThenSendJson('payment-transactions-list');
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $sql = PaymentTransaction::with('user')->orderBy($sort, $order);
        if (!empty($request->search)) {
            $sql = $sql->search($request->search);
        }
        $total = $sql->count();
        $sql->skip($offset)->take($limit);
        $result = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();

        foreach ($result as $key => $row) {
            $tempRow = $row->toArray();
            if (!empty($tempRow['user'])) {
                $tempRow['user'] = HelperService::maskDemoUserData($tempRow['user']);
            }
            $tempRow['payment_status'] = __($row->payment_status_upper);
            $tempRow['created_at'] = Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at)->format('d-m-y H:i:s');
            $tempRow['updated_at'] = Carbon::createFromFormat('Y-m-d H:i:s', $row->updated_at)->format('d-m-y H:i:s');
            if (in_array($row->payment_status, ['succeed', 'failed'])) {
                $tempRow['operate'] = BootstrapTableService::button('ph ph-receipt', route('package.payment-transactions.receipt', $row->id), [], ['title' => __('View Receipt'), 'target' => '_blank']);
            } else {
                $tempRow['operate'] = null;
            }
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function viewReceipt($id) {
        ResponseService::noPermissionThenRedirect('payment-transactions-list');
        try {
            $payment = PaymentTransaction::with('user')->findOrFail($id);
            if (!in_array($payment->payment_status, ['succeed', 'failed'])) {
                return redirect()->back()->with('error', __('Receipt is only available for completed transactions'));
            }
            return PaymentReceiptService::streamPDF($payment);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            return redirect()->back()->with('error', __('Something Went Wrong'));
        }
    }

    public function bankTransferIndex() {
        ResponseService::noPermissionThenRedirect('payment-transactions-list');
        return view('packages.bank-transfer');
    }
    public function bankTransferShow(Request $request) {
        ResponseService::noPermissionThenSendJson('payment-transactions-list');
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $sql = PaymentTransaction::with('user')->where('payment_gateway' ,'BankTransfer')->orderBy($sort, $order);
        if (!empty($request->search)) {
            $sql = $sql->search($request->search);
        }
        $total = $sql->count();
        $sql->skip($offset)->take($limit);
        $result = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        foreach ($result as $key => $row) {
            $tempRow = $row->toArray();
            if (!empty($tempRow['user'])) {
                $tempRow['user'] = HelperService::maskDemoUserData($tempRow['user']);
            }
            $tempRow['created_at'] = Carbon::createFromFormat('Y-m-d H:i:s', $row->created_at)->format('d-m-y H:i:s');
            $tempRow['updated_at'] = Carbon::createFromFormat('Y-m-d H:i:s', $row->updated_at)->format('d-m-y H:i:s');
            if (Auth::user()->can('featured-advertisement-package-update')) {
                $tempRow['operate'] = BootstrapTableService::editButton(route('package.bank-transfer.update-status', $row->id), true, '#editStatusModal', 'edit-status', $row->id);
            }
            $tempRow['payment_status'] = $row->payment_status_upper;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
    public function updateStatus(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:succeed,rejected'
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
                DB::beginTransaction();

                $transaction = PaymentTransaction::findOrFail($id);
                $transaction->update(['payment_status' => $request->payment_status]);

                if ($request->payment_status === 'succeed') {
                    $parts = explode('-', $transaction->order_id);
                    $package_id = $parts[2];
                    $package = Package::find((int) $package_id);
                    
                if ($package) {
                        UserPurchasedPackage::create([
                            'package_id'  => $package->id,
                            'user_id'     => $transaction->user_id,
                            'start_date'  => Carbon::now(),
                            'end_date'    => is_null($package->duration) ? null : Carbon::now()->addDays($package->duration),
                            'total_limit' => $package->item_limit == "unlimited" ? null : $package->item_limit,
                            'payment_transactions_id' => $transaction->id,
                            'listing_duration_type' => $package->listing_duration_type,
                        'listing_duration_days' => $package->listing_duration_days
                        ]);
                    }
                }

                DB::commit(); // Close the DB transaction as soon as database work is don  e

                // NOW handle the external notification logic after commit
                if (!empty($transaction->user_id)) {
                    if ($request->payment_status === 'succeed') {
                        $title = "Package Purchased";
                        $body = 'Amount :- ' . $transaction->amount;

                        // Dispatch chunked notification jobs using centralized service
                        NotificationService::dispatchChunkedNotifications(
                            $title,
                            $body,
                            'payment',
                            ['id' => $transaction->id],
                            false,
                            array($transaction->user_id)
                        );
                        // NotificationService::sendFcmNotification($userTokens, $title, $body, 'payment');
                    } elseif ($request->payment_status === 'rejected') {
                        $title = "Payment Rejected";
                        $body = "Your payment of " . $transaction->amount . " has been rejected.";
                        
                        // Dispatch chunked notification jobs using centralized service
                        NotificationService::dispatchChunkedNotifications(
                            $title,
                            $body,
                            'payment',
                            ['id' => $transaction->id],
                            false,
                            array($transaction->user_id)
                        );
                        // NotificationService::sendFcmNotification($userTokens, $title, $body, 'payment');
                    }
                }
            return ResponseService::successResponse('Payment Status Updated Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'PackageController ->updateStatus');
            return ResponseService::errorResponse('Something Went Wrong');
        }
    }

    /**
     * Filter selected categories: if a parent and ALL its subcategories are selected,
     * keep only the parent. If only some subcategories are selected, keep only those subcategories.
     */
    /**
     * Reduce a selected-category tree down to the minimal set of ids to store:
     * only the highest (root-of-selection) category per branch is kept.
     * Descendants are never persisted — the API resolves them at read time
     * by walking parent_category_id.
     */
    private function filterCategorySelections(array $selectedIds): array
    {
        $selectedIds = array_values(array_unique($selectedIds));
        if (empty($selectedIds)) {
            return [];
        }
        $selectedSet = array_flip($selectedIds);

        // Load all category parent relationships to avoid querying in a loop
        $parentMap = Category::without('translations')->pluck('parent_category_id', 'id')->toArray();

        $hasSelectedAncestor = function ($id) use ($parentMap, $selectedSet) {
            $parentId = $parentMap[$id] ?? null;
            while ($parentId !== null) {
                if (isset($selectedSet[$parentId])) {
                    return true;
                }
                $parentId = $parentMap[$parentId] ?? null;
            }
            return false;
        };

        return array_values(array_filter($selectedIds, fn($id) => !$hasSelectedAncestor($id)));
    }

}
