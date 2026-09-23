<?php

namespace App\Http\Controllers;

use App\Models\ItemAdPromotion;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ItemAdPromotionController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['ad-promotion-list', 'ad-promotion-update', 'ad-promotion-delete']);

        $totalPromotions = ItemAdPromotion::count();
        $totalDailyBumps = ItemAdPromotion::where('promotion_type', 'daily_bump_up')->where('status', 'active')->count();
        $totalTopAds = ItemAdPromotion::where('promotion_type', 'top_ad')->where('status', 'active')->count();
        $totalSpotlights = ItemAdPromotion::where('promotion_type', 'spotlight')->where('status', 'active')->count();

        return view('ad_promotions.index', compact(
            'totalPromotions',
            'totalDailyBumps',
            'totalTopAds',
            'totalSpotlights'
        ));
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('ad-promotion-list');

        $offset = (int) ($request->offset ?? 0);
        $limit = (int) ($request->limit ?? 10);
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $query = ItemAdPromotion::with(['item', 'user']);

        if ($request->filled('promotion_type')) {
            $query->where('promotion_type', $request->promotion_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('item', fn($iq) => $iq->where('name', 'LIKE', $search))
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'LIKE', $search));
            });
        }

        $total = $query->count();
        $promos = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        foreach ($promos as $promo) {
            $actions = '';

            if (auth()->user()->can('ad-promotion-delete')) {
                $actions .= '<button class="btn btn-sm btn-icon btn-danger rounded-pill delete-ad-promotion" data-id="' . $promo->id . '" title="' . __('Delete') . '">
                    <i class="ph ph-trash fs-5"></i>
                </button>';
            }

            $typeBadge = match ($promo->promotion_type) {
                'daily_bump_up' => '<span class="badge bg-primary"><i class="ph ph-rocket-launch me-1"></i>' . __('Daily Bump Up') . '</span>',
                'top_ad'        => '<span class="badge bg-warning text-dark"><i class="ph ph-arrow-fat-line-up me-1"></i>' . __('Top Ad') . '</span>',
                'spotlight'     => '<span class="badge bg-success"><i class="ph ph-sparkle me-1"></i>' . __('Spotlight') . '</span>',
                default         => '<span class="badge bg-secondary">' . ucfirst($promo->promotion_type) . '</span>',
            };

            $statusBadge = match ($promo->status) {
                'active'    => '<span class="badge bg-success">' . __('Active') . '</span>',
                'expired'   => '<span class="badge bg-dark">' . __('Expired') . '</span>',
                'cancelled' => '<span class="badge bg-danger">' . __('Cancelled') . '</span>',
                default     => '<span class="badge bg-secondary">' . ucfirst($promo->status) . '</span>',
            };

            $itemHtml = $promo->item
                ? '<strong>' . htmlspecialchars($promo->item->name) . '</strong>'
                : '<span class="text-danger">' . __('Deleted Ad') . '</span>';

            $sellerHtml = $promo->user
                ? htmlspecialchars($promo->user->name)
                : '-';

            $rows[] = [
                'id'             => $promo->id,
                'item'           => $itemHtml,
                'seller'         => $sellerHtml,
                'type'           => $typeBadge,
                'start_date'     => $promo->start_date ? $promo->start_date->format('Y-m-d H:i') : '-',
                'end_date'       => $promo->end_date ? $promo->end_date->format('Y-m-d H:i') : '-',
                'last_bumped_at' => $promo->last_bumped_at ? $promo->last_bumped_at->format('Y-m-d H:i') : '-',
                'status'         => $statusBadge,
                'actions'        => $actions ?: '-',
            ];
        }

        return response()->json([
            'total' => $total,
            'rows'  => $rows,
        ]);
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('ad-promotion-delete');

        try {
            $adPromo = ItemAdPromotion::findOrFail($id);
            $adPromo->delete();

            return ResponseService::successResponse(__('Promoted ad removed successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemAdPromotionController -> destroy');
            return ResponseService::errorResponse(__('Failed to delete promoted ad'));
        }
    }

    /**
     * View user-specific promotions, sales, and boost analytics in admin panel
     */
    public function userAnalytics(Request $request)
    {
        ResponseService::noAnyPermissionThenRedirect(['ad-promotion-list', 'promotion-item-list']);

        // Fetch users who have either submitted promo items or bought ad promotions or created items
        $users = \App\Models\User::where(function ($q) {
            $q->whereHas('items')
                ->orWhereHas('user_purchased_packages')
                ->orWhereHas('ad_promotions')
                ->orWhereHas('promotion_items');
        })
        ->select(['id', 'name', 'email', 'mobile'])
        ->orderBy('name', 'asc')
        ->get();

        if ($users->isEmpty()) {
            $users = \App\Models\User::select(['id', 'name', 'email', 'mobile'])
                ->orderBy('name', 'asc')
                ->limit(100)
                ->get();
        }

        // Prioritize a user who already has promotional activity or items
        $defaultUser = $users->first(function ($u) {
            return $u->ad_promotions()->exists() || $u->promotion_items()->exists();
        }) ?? $users->first();

        $selectedUserId = $request->user_id ?? ($defaultUser?->id ?? null);

        return view('ad_promotions.user_analytics', compact('users', 'selectedUserId'));
    }

    /**
     * AJAX endpoint for user promotions analytics & history data
     */
    public function userAnalyticsData(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['ad-promotion-list', 'promotion-item-list']);

        try {
            $userId = (int) $request->user_id;
            if (!$userId) {
                return ResponseService::errorResponse(__('Please select a user'));
            }

            // Forward to SellerPromotionApiController logic
            $apiCtrl = app(\App\Http\Controllers\Api\SellerPromotionApiController::class);
            $analyticsRes = $apiCtrl->getPromotionsAnalytics($request);
            $analyticsData = json_decode($analyticsRes->getContent(), true)['data'] ?? [];

            return response()->json([
                'error' => false,
                'data'  => $analyticsData,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemAdPromotionController -> userAnalyticsData');
            return ResponseService::errorResponse(__('Failed to load user analytics data'));
        }
    }

    /**
     * AJAX endpoint for user promotions & boosts history table
     */
    public function userAnalyticsHistory(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['ad-promotion-list', 'promotion-item-list']);

        try {
            $userId = (int) $request->user_id;
            if (!$userId) {
                return response()->json([
                    'total' => 0,
                    'rows'  => [],
                ]);
            }

            // Forward to SellerPromotionApiController logic
            $apiCtrl = app(\App\Http\Controllers\Api\SellerPromotionApiController::class);
            $historyRes = $apiCtrl->getPromotionsHistory($request);
            $historyData = json_decode($historyRes->getContent(), true)['data'] ?? [];

            $total = $historyData['total'] ?? 0;
            $rows = $historyData['data'] ?? [];

            return response()->json([
                'error' => false,
                'total' => $total,
                'rows'  => $rows,
                'data'  => $historyData,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ItemAdPromotionController -> userAnalyticsHistory');
            return response()->json([
                'total' => 0,
                'rows'  => [],
            ]);
        }
    }
}

