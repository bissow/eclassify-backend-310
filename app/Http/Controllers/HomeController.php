<?php

namespace App\Http\Controllers;

use App\Services\BootstrapTableService;
use App\Services\HelperService;
use App\Models\Category;
use App\Models\Chat;
use App\Models\CustomField;
use App\Models\Favourite;
use App\Models\FeaturedItems;
use App\Models\Item;
use App\Models\PaymentTransaction;
use App\Models\SellerRating;
use App\Models\User;
use App\Models\UserPurchasedPackage;
use App\Models\UserReports;
use App\Models\VerificationRequest;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

class HomeController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth')->except([
            'sendPasswordResetOtp',
            'verifyPasswordResetOtp',
            'updatePasswordWithOtp',
        ]);
    }


    public function index() {
        // Top stat cards
        $total_advertisement = Item::without('image')->withTrashed()->count();
        $featured_listing    = FeaturedItems::onlyActive()->count();
        $active_listing      = Item::without('image')->where(function($query){
            $query->whereNull('expiry_date')->orWhere('expiry_date', '>=', Carbon::now());
        })->where('status', 'approved')->count();
        $expired_listing     = Item::without('image')->whereNotNull('expiry_date')->where('expiry_date', '<', Carbon::now())->where('status', '!=', 'sold out')->whereNull('deleted_at')->count();
        $sold_listing        = Item::without('image')->where('status', 'sold out')->count();

        // Side stat cards
        $user_count          = User::role('User')->count();
        $subscription_count  = UserPurchasedPackage::whereDate('start_date', '<=', now())
            ->where(function ($q) { $q->whereDate('end_date', '>=', now())->orWhereNull('end_date'); })->count('user_id');
        $favorite_count      = Favourite::distinct('item_id')->count();
        $reviews_count       = SellerRating::count();
        $categories_count    = Category::count();
        $item_count          = $total_advertisement;
        $custom_field_count  = CustomField::count();

        // Revenue chart — week / month / year buckets
        $revenue = [
            'week'  => $this->buildRevenue('week'),
            'month' => $this->buildRevenue('month'),
            'year'  => $this->buildRevenue('year'),
            'all'   => $this->buildRevenue('all'),
        ];

        // New Message — latest chat per conversation on admin (Super Admin) items
        $adminUser = User::role('Super Admin')->first();
        $adminId = $adminUser?->id;

        $latestChatIds = Chat::selectRaw('MAX(id) as id')
            ->whereNotNull('item_offer_id')
            ->when($adminId, function ($q) use ($adminId) {
                $q->whereHas('itemOffer.item', fn($i) => $i->where('user_id', $adminId));
            })
            ->groupBy('item_offer_id')
            ->orderByDesc('id')
            ->limit(10)
            ->pluck('id');

        $new_messages = Chat::with([
            'sender:id,name,profile',
            'itemOffer.item:id,name,user_id',
            'itemOffer.item.gallery_images',
            'itemOffer.buyer:id,name,profile',
        ])
        ->whereIn('id', $latestChatIds)
        ->orderByDesc('id')
        ->get();

        $unreadCounts = Chat::selectRaw('item_offer_id, COUNT(*) as cnt')
            ->whereIn('item_offer_id', $new_messages->pluck('item_offer_id'))
            ->where('is_read', 0)
            ->when($adminId, fn($q) => $q->where('sender_id', '!=', $adminId))
            ->groupBy('item_offer_id')
            ->pluck('cnt', 'item_offer_id');

        $new_messages->each(function ($m) use ($unreadCounts) {
            $m->unread_count = (int) ($unreadCounts[$m->item_offer_id] ?? 0);
        });

        // Ads Statistics donut
        $statusAdsQuery = Item::without('image')->withTrashed();
        $expiredCounts = $statusAdsQuery->clone()->whereNotNull('expiry_date')->where('expiry_date', '<', Carbon::now())->where('status', '!=', 'sold out')->count();
        $inactiveCounts = $statusAdsQuery->clone()->whereNotNull('deleted_at')->count();
        $soldOutCounts = $statusAdsQuery->clone()->where('status','sold out')->count();
        $statusCounts = $statusAdsQuery->whereNull('deleted_at')
                            ->where(function ($q) {
                                $q->whereNull('expiry_date')
                                    ->orWhere('expiry_date', '>=', Carbon::now());
                            })->clone()->get(['status'])->groupBy('status')->map->count();
        $ads_stats_labels = ['Active', 'Under Review', 'Sold Out', 'Expired', 'Rejected', 'Inactive', 'Resubmitted'];
        $ads_stats_data = [
            (int) ($statusCounts['approved'] ?? 0),
            (int) ($statusCounts['review'] ?? 0),
            (int) ($soldOutCounts ?? 0),
            (int) ($expiredCounts),
            (int) (($statusCounts['soft rejected'] ?? 0) + ($statusCounts['permanent rejected'] ?? 0)),
            (int) ($inactiveCounts),
            (int) ($statusCounts['resubmitted'] ?? 0),
        ];

        // Resolve every category to its main (root) category, so sub-category counts roll up
        $cats = Category::all()->keyBy('id');
        $rootOf = function ($id) use ($cats) {
            $guard = 0;
            while ($id && ($c = $cats->get($id)) && $c->parent_category_id && $guard++ < 50) {
                $id = $c->parent_category_id;
            }
            return $id;
        };
        $rollupToMain = function ($counts) use ($cats, $rootOf) {
            $byMain = [];
            foreach ($counts as $cid => $cnt) {
                $root = $rootOf($cid);
                if (! $root) continue;
                $byMain[$root] = ($byMain[$root] ?? 0) + (int) $cnt;
            }
            return collect($byMain)
                ->map(fn($v, $k) => ['label' => optional($cats->get($k))->translated_name ?? 'Unknown', 'val' => $v])
                ->sortByDesc('val')->values()->toArray();
        };

        // Ads per main category donut — top 5 + Other
        $adsByCat = Item::query()->selectRaw('category_id, COUNT(*) as cnt')
            ->groupBy('category_id')->pluck('cnt', 'category_id');
        [$ads_per_cat_labels, $ads_per_cat_data] = $this->topWithOther($rollupToMain($adsByCat), 5, false);

        // Sold per main category donut — top 5 + Other
        $soldByCat = Item::query()->where('status', 'sold out')->selectRaw('category_id, COUNT(*) as cnt')
            ->groupBy('category_id')->pluck('cnt', 'category_id');
        [$sold_per_cat_labels, $sold_per_cat_data] = $this->topWithOther($rollupToMain($soldByCat), 5, false);

        // Customer Statistics donut
        $cust_active     = User::role('User')->whereNull('deleted_at')->count();
        $cust_inactive   = User::role('User')->onlyTrashed()->count();
        $customer_stats_labels = ['Active', 'Inactive'];
        $customer_stats_data   = [$cust_active, $cust_inactive];

        // Legacy category pie data (kept for backward compat — unused in new view)
        $category_name = [];
        $category_item_count = [];

        $items = collect();

        return view('home', compact(
            'total_advertisement', 'featured_listing', 'active_listing', 'expired_listing', 'sold_listing',
            'user_count', 'subscription_count', 'favorite_count', 'reviews_count',
            'categories_count', 'item_count', 'custom_field_count',
            'revenue',
            'new_messages',
            'ads_stats_labels', 'ads_stats_data',
            'ads_per_cat_labels', 'ads_per_cat_data',
            'sold_per_cat_labels', 'sold_per_cat_data',
            'customer_stats_labels', 'customer_stats_data',
            'category_name', 'category_item_count', 'items'
        ));
    }

    private function buildRevenue(string $range): array {
        if ($range === 'week') {
            $start = Carbon::now()->startOfWeek();
            $end   = Carbon::now()->endOfWeek();
            $bucket = 'day';
            $fmt    = 'd';
            $points = 7;
        } elseif ($range === 'month') {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfDay();
            $bucket = 'day';
            $fmt    = 'd';
            $points = 30;
        } elseif ($range === 'year') {
            $start = Carbon::now()->startOfYear();
            $end   = Carbon::now()->endOfYear();
            $bucket = 'month';
            $fmt    = 'M';
            $points = 12;
        } else { // all time — group by year, start from earliest payment
            $earliest = PaymentTransaction::whereNotNull('package_id')
                ->whereIn('payment_status', ['succeed', 'succeeded', 'success', 'approved', 'completed'])
                ->min('created_at');
            $start = $earliest ? Carbon::parse($earliest)->startOfYear() : Carbon::now()->startOfYear();
            $end   = Carbon::now()->endOfYear();
            $bucket = 'year';
            $fmt    = 'Y';
            $points = max(1, $start->diffInYears($end) + 1);
        }

        $dbFmt = $bucket === 'day' ? '%Y-%m-%d' : ($bucket === 'month' ? '%Y-%m-01' : '%Y-01-01');

        $rows = PaymentTransaction::query()
            ->join('packages', 'packages.id', '=', 'payment_transactions.package_id')
            ->whereIn('payment_status', ['succeed', 'succeeded', 'success', 'approved', 'completed'])
            ->whereBetween('payment_transactions.created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(payment_transactions.created_at, ?) as bucket, packages.type as ptype, SUM(payment_transactions.amount) as total", [$dbFmt])
            ->groupBy('bucket', 'packages.type')
            ->get()
            ->groupBy('bucket');

        $labels = [];
        $subscription = [];
        $feature = [];
        for ($i = 0; $i < $points; $i++) {
            if ($bucket === 'day')        $d = $start->copy()->addDays($i);
            elseif ($bucket === 'month')  $d = $start->copy()->addMonths($i);
            else                          $d = $start->copy()->addYears($i);

            if ($bucket === 'day')        $key = $d->format('Y-m-d');
            elseif ($bucket === 'month')  $key = $d->format('Y-m-01');
            else                          $key = $d->format('Y-01-01');
            $labels[] = $d->format($fmt);

            $b = $rows->get($key, collect());
            $subscription[] = (float) ($b->firstWhere('ptype', 'item_listing')->total ?? 0);
            $feature[]      = (float) ($b->firstWhere('ptype', 'advertisement')->total ?? 0);
        }

        // Weekly
        // $subscription = array(10, 30, 40, 20, 11, 13, 17);
        // $feature = array(13, 0, 12, 4, 22, 6, 16);

        // Monthly
        // $subscription = array(10, 30, 40, 20, 11, 13, 17, 19, 24, 28, 32, 36, 16, 17, 18, 36, 49, 46, 1, 19, 46, 8, 8, 10, 26, 17, 49, 56, 41, 3);
        // $feature = array(13, 0, 12, 4, 22, 6, 8, 10, 14, 18, 22, 26, 32, 36, 16, 17, 18, 36, 49, 46, 1, 19, 46, 8, 8, 13, 0, 12, 4, 22);

        // yearly
        // $subscription = array(10, 30, 40, 20, 11, 13, 17, 19, 24, 28, 32, 36);
        // $feature = array(13, 0, 12, 4, 22, 6, 8, 10, 14, 18, 22, 26);

        // All time
        // $subscription = array(11,36);
        // $feature = array(0,12);

        return ['labels' => $labels, 'subscription' => $subscription, 'feature' => $feature];
    }

    private function topWithOther(array $rows, int $top = 5, bool $withOther = true): array {
        $rows = array_values(array_filter($rows, fn($r) => ($r['val'] ?? 0) > 0));
        if (empty($rows)) {
            return [[], []];
        }
        $head   = array_slice($rows, 0, $top);
        $tail   = array_slice($rows, $top);
        $labels = array_map(fn($r) => $r['label'], $head);
        $data   = array_map(fn($r) => (int) $r['val'], $head);
        if ($withOther && !empty($tail)) {
            $labels[] = 'Other';
            $data[]   = (int) array_sum(array_map(fn($r) => $r['val'], $tail));
        }
        return [$labels, $data];
    }

    public function recentAdsTable(Request $request) {
        $sort  = $request->input('sort', 'id');
        $order = strtoupper($request->input('order', 'DESC'));

        $result = Item::with([
            'user' => function ($q) { $q->withTrashed()->select('id', 'name', 'email', 'profile', 'deleted_at'); },
            'gallery_images',
            'category:id,name',
            'currency:id,symbol',
        ])->withTrashed()->orderByDesc('published_at')->limit(10)->get();

        $result = $this->sortCollection($result, $sort, $order);
        $total  = $result->count();

        $rows = [];
        foreach ($result as $row) {
            $tempRow = $row->toArray();
            if (!empty($tempRow['user'])) {
                $tempRow['user'] = HelperService::maskDemoUserData($tempRow['user']);
            }
            $tempRow['created_at'] = $row->created_at ? $row->created_at->format('Y-m-d') : null;
            $operate = '';
            if (Auth::user()->can('advertisement-update')) {
                $operate .= BootstrapTableService::button('fa fa-pen', route('advertisement.edit', $row->id), ['edit'], ['title' => __('Edit')]);
            }
            if (Auth::user()->can('advertisement-delete')) {
                $operate .= BootstrapTableService::button('fa fa-trash', url('advertisement') . '/' . $row->id, ['delete', 'delete-form-reload'], ['title' => __('Delete')]);
            }
            if (Auth::user()->can('advertisement-list')) {
                $operate .= BootstrapTableService::button('fa fa-eye', 'javascript:void(0)', ['view', 'recent-ad-view'], ['title' => __('View'), 'data-row' => json_encode($tempRow)]);
            }
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function recentReportsTable(Request $request) {
        $sort  = $request->input('sort', 'id');
        $order = strtoupper($request->input('order', 'DESC'));

        $result = UserReports::has('item')->with([
            'report_reason',
            'item' => function ($q) {
                $q->withTrashed()->with([
                    'user' => function ($u) {
                        $u->withTrashed()->select('id', 'name', 'email', 'profile', 'deleted_at');
                    },
                    'gallery_images',
                    'category:id,name',
                    'currency:id,symbol',
                ]);
            },
            'user:id,name',
        ])->orderByDesc('id')->limit(10)->get();

        $result = $this->sortCollection($result, $sort, $order);
        $total  = $result->count();

        $rows = [];
        foreach ($result as $row) {
            $tempRow = $row->toArray();
            $tempRow['reason_text']        = $row->report_reason->reason ?? $row->other_message ?? '-';
            $tempRow['item_active_status'] = $row->item ? empty($row->item->deleted_at) : false;
            $tempRow['user_active_status'] = ($row->item && $row->item->user) ? empty($row->item->user->deleted_at) : false;
            if ($row->item) {
                $tempRow['item']['is_item_deleted'] = !empty($row->item->deleted_at);
                $tempRow['item']['is_user_deleted'] = $row->item->user ? !empty($row->item->user->deleted_at) : false;
                $tempRow['item']['user_id']        = $row->item->user_id;
                if (!empty($tempRow['item']['user'])) {
                    $tempRow['item']['user'] = HelperService::maskDemoUserData($tempRow['item']['user']);
                }
            }
            $action = '';
            if ($row->item && Auth::user()->can('advertisement-list')) {
                $action .= BootstrapTableService::button('fa fa-eye', 'javascript:void(0)', ['view', 'recent-ad-view'], ['title' => __('View'), 'data-row' => json_encode($tempRow['item'])]);
            }
            $tempRow['action'] = $action;
            $rows[] = $tempRow;
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function topCustomersTable(Request $request) {
        $sort  = $request->input('sort', 'listing_count');
        $order = strtoupper($request->input('order', 'DESC'));

        $result = User::role('User')
            ->withCount(['items as listing_count'])
            ->withCount(['items as sold_count' => function ($q) { $q->where('status', 'sold out'); }])
            ->orderByDesc('listing_count')->limit(10)->get();

        $result = $this->sortCollection($result, $sort, $order);
        $total  = $result->count();
        $rows   = $result->map(fn($u) => $u->toArray())->values()->all();
        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function mostViewedTable(Request $request) {
        $sort  = $request->input('sort', 'clicks');
        $order = strtoupper($request->input('order', 'DESC'));

        $result = Item::with(['category', 'user:id,name', 'gallery_images'])
            ->where('clicks', '>', 0)
            ->orderByDesc('clicks')->limit(10)->get();

        $result = $this->sortCollection($result, $sort, $order);
        $total  = $result->count();
        $rows   = $result->map(function($it) {
            $operate = '';
            if (Auth::user()->can('advertisement-list')) {
                $operate .= BootstrapTableService::button('fa fa-eye', 'javascript:void(0)', ['view', 'recent-ad-view'], ['title' => __('View'), 'data-row' => json_encode($it)]);
            }
            $itemData = $it->toArray();
            $itemData['operate'] = $operate;
            return $itemData;
        } )->values()->all();
        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    private function sortCollection($collection, string $sort, string $order) {
        $desc = strtoupper($order) === 'DESC';
        $sorted = $collection->sortBy(function ($row) use ($sort) {
            $val = data_get($row, $sort);
            if ($val === null) {
                $val = data_get($row, 'item.' . $sort);
            }
            return \is_string($val) ? \mb_strtolower($val) : $val;
        }, SORT_REGULAR, $desc);
        return $sorted->values();
    }

    public function changePasswordIndex() {
        $this->clearPasswordResetState();

        return view('change_password.index', [
            'otpResetState' => $this->emptyPasswordResetState(Auth::user()->email ?? ''),
        ]);
    }


    public function changePasswordUpdate(Request $request) {
        $validator = Validator::make($request->all(), [
            'old_password'     => 'required',
            'new_password'     => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();
            if (!Hash::check($request->old_password, Auth::user()->password)) {
                ResponseService::errorResponse("Incorrect old password");
            }
            $user->password = Hash::make($request->confirm_password);
            $user->update();
            ResponseService::successResponse('Password Change Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "HomeController --> changePasswordUpdate");
            ResponseService::errorResponse();
        }


    }

    public function sendPasswordResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $email = trim((string) $request->email);
            $user = $this->getPasswordResetUserByEmail($email);

            if (! $user) {
                ResponseService::validationError('Reset password works only on your valid admin email.');
            }

            $otp = (string) random_int(100000, 999999);
            $expiresAt = now()->addMinutes(10);

            NotificationService::sendMail(
                '<p>Your admin password reset OTP is <strong>' . e($otp) . '</strong>.</p><p>This OTP will expire in 10 minutes.</p>',
                $email,
                'Admin Password Reset OTP',
                $user->name ?? 'Admin',
                null,
                null,
                true // OTP must be sent immediately, skip queue
            );

            $this->storePasswordResetState([
                'user_id' => $user->id,
                'email' => $email,
                'otp' => Hash::make($otp),
                'expires_at' => $expiresAt->timestamp,
                'verified' => false,
                'verified_at' => null,
            ]);

            ResponseService::successResponse('OTP sent successfully.');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'HomeController --> sendPasswordResetOtp');
            ResponseService::errorResponse('Unable to send OTP. Please verify SMTP settings are configured correctly.');
        }
    }

    public function verifyPasswordResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric|digits:6',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $state = session('admin_password_reset');
            if (! $this->isValidResetState($state)) {
                $this->clearPasswordResetState();
                ResponseService::validationError('OTP session expired.');
            }

            if (! Hash::check((string) $request->otp, $state['otp'])) {
                ResponseService::validationError('Invalid OTP.');
            }

            $state['verified'] = true;
            $state['verified_at'] = now()->timestamp;
            $this->storePasswordResetState($state);

            ResponseService::successResponse('OTP Verified Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'HomeController --> verifyPasswordResetOtp');
            ResponseService::errorResponse();
        }
    }

    public function updatePasswordWithOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
            'confirm_password' => 'required|same:new_password',
        ], [
            'new_password.min' => __('The new password must be at least :min characters.'),
            'new_password.regex' => __('The new password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.'),
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $state = session('admin_password_reset');
            if (! $this->isValidResetState($state) || empty($state['verified'])) {
                $this->clearPasswordResetState();
                ResponseService::validationError('Please verify OTP before resetting password.');
            }

            $user = User::find($state['user_id'] ?? 0);
            if (! $user || strcasecmp($user->email ?? '', $state['email'] ?? '') !== 0 || $user->hasRole('User')) {
                $this->clearPasswordResetState();
                ResponseService::validationError('Reset password works only on your valid admin email.');
            }

            $user->password = Hash::make($request->confirm_password);
            $user->update();

            $this->clearPasswordResetState();

            ResponseService::successResponse('Password Change Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'HomeController --> updatePasswordWithOtp');
            ResponseService::errorResponse();
        }
    }

    private function getPasswordResetState(): array
    {
        $state = session('admin_password_reset');
        if (! $this->isValidResetState($state)) {
            session()->forget('admin_password_reset');

            return [
                'sent' => false,
                'verified' => false,
                'email' => Auth::user()->email ?? '',
            ];
        }

        return [
            'sent' => true,
            'verified' => ! empty($state['verified']),
            'email' => $state['email'] ?? (Auth::user()->email ?? ''),
        ];
    }

    private function emptyPasswordResetState(string $email = ''): array
    {
        return [
            'sent' => false,
            'verified' => false,
            'email' => $email,
        ];
    }

    private function isValidResetState($state): bool
    {
        return ! empty($state['user_id']) &&
            ! empty($state['email']) &&
            ! empty($state['otp']) &&
            ! empty($state['expires_at']) &&
            now()->timestamp <= (int) $state['expires_at'];
    }

    private function storePasswordResetState(array $state): void
    {
        session(['admin_password_reset' => $state]);
        session()->save();
    }

    private function clearPasswordResetState(): void
    {
        session()->forget('admin_password_reset');
        session()->save();
    }

    private function getPasswordResetUserByEmail(string $email): ?User
    {
        $user = User::where('email', $email)->first();

        if (! $user || !$user->hasRole('Super Admin')) {
            return null;
        }

        return $user;
    }


    public function changeProfileIndex() {
        return view('change_profile.index');
    }

    public function changeProfileUpdate(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'    => 'required',
            'email'   => 'required|email|unique:users,email,' . Auth::user()->id,
            'profile' => 'nullable|mimes:jpeg,jpg,png'
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();
            $data = [
                'name'  => $request->name,
                'email' => $request->email
            ];
            if ($request->hasFile('profile')) {
                $data['profile'] = $request->file('profile')->store('admin_profile', 'public');
            }
            $user->update($data);
            ResponseService::successResponse('Profile Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "HomeController --> updateProfile");
            ResponseService::errorResponse();
        }

    }
    public function getMapsData()
    {
        $apiKey = env('PLACE_API_KEY');

        $url = "https://maps.googleapis.com/maps/api/js?" . http_build_query([
            'libraries' => 'places',
            'key' => $apiKey, // Use the API key from the .env file
            // Add any other parameters you need here
        ]);

        return file_get_contents($url);
    }
}
