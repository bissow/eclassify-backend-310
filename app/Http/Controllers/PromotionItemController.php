<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PromotionItemController extends Controller
{
    public function index(Request $request)
    {
        ResponseService::noAnyPermissionThenRedirect(['promotion-item-list', 'promotion-item-update', 'promotion-item-delete']);

        $selectedPromotionId = $request->query('promotion_id');
        $promotions = Promotion::active()->get();

        $totalItems = PromotionItem::count();
        $activeItems = PromotionItem::where('status', 'active')->where('remaining_stock_quantity', '>', 0)->count();
        $soldOutItems = PromotionItem::where('remaining_stock_quantity', '<=', 0)->count();

        return view('promotions.items', compact(
            'totalItems',
            'activeItems',
            'soldOutItems',
            'promotions',
            'selectedPromotionId'
        ));
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('promotion-item-list');

        $offset = (int) ($request->offset ?? 0);
        $limit = (int) ($request->limit ?? 10);
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $query = PromotionItem::with(['promotion', 'item.currency', 'user']);

        if ($request->filled('promotion_id')) {
            $query->where('promotion_id', $request->promotion_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('item', fn($iq) => $iq->where('name', 'LIKE', $search))
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'LIKE', $search)->orWhere('email', 'LIKE', $search));
            });
        }

        $total = $query->count();
        $items = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        foreach ($items as $pi) {
            $actions = '';

            if (auth()->user()->can('promotion-item-update')) {
                $actions .= '<button class="btn btn-sm btn-icon btn-primary rounded-pill me-1 edit-promo-item" data-item=\'' . htmlspecialchars(json_encode($pi), ENT_QUOTES, 'UTF-8') . '\' title="' . __('Edit') . '">
                    <i class="ph ph-pencil-simple fs-5"></i>
                </button>';
            }

            if (auth()->user()->can('promotion-item-delete')) {
                $actions .= '<button class="btn btn-sm btn-icon btn-danger rounded-pill delete-promo-item" data-id="' . $pi->id . '" title="' . __('Delete') . '">
                    <i class="ph ph-trash fs-5"></i>
                </button>';
            }

            $itemImage = $pi->item && $pi->item->image
                ? '<img src="' . $pi->item->image . '" class="rounded shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">'
                : '<span class="text-muted">' . __('No Image') . '</span>';

            $itemTitle = $pi->item
                ? '<strong>' . htmlspecialchars($pi->item->name) . '</strong><br><small class="text-muted">' . htmlspecialchars($pi->item->city ?: '') . '</small>'
                : '<span class="text-danger">' . __('Ad Deleted') . '</span>';

            $sellerName = $pi->user
                ? htmlspecialchars($pi->user->name) . ($pi->user->is_verified ? ' <i class="ph ph-seal-check text-primary" title="' . __('Verified') . '"></i>' : '')
                : '-';

            $statusBadge = match ($pi->status) {
                'active'   => '<span class="badge bg-success">' . __('Active') . '</span>',
                'inactive' => '<span class="badge bg-secondary">' . __('Inactive') . '</span>',
                'sold_out' => '<span class="badge bg-danger">' . __('Sold Out') . '</span>',
                'expired'  => '<span class="badge bg-dark">' . __('Expired') . '</span>',
                'pending'  => '<span class="badge bg-warning text-dark">' . __('Pending') . '</span>',
                'rejected' => '<span class="badge bg-danger">' . __('Rejected') . '</span>',
                default    => '<span class="badge bg-secondary">' . ucfirst($pi->status) . '</span>',
            };

            $stockBadge = $pi->remaining_stock_quantity > 0
                ? '<span class="badge bg-light-primary text-primary">' . $pi->remaining_stock_quantity . ' / ' . $pi->stock_quantity . ' ' . __('Units') . '</span>'
                : '<span class="badge bg-danger text-white">' . __('0 Units') . '</span>';

            $currencySymbol = $pi->item?->currency?->symbol ?: '$';

            $rows[] = [
                'id'          => $pi->id,
                'image'       => $itemImage,
                'item'        => $itemTitle,
                'seller'      => $sellerName,
                'promotion'   => htmlspecialchars($pi->promotion?->title ?: '-'),
                'original_price' => $currencySymbol . number_format($pi->item?->price ?? 0, 2),
                'promo_price' => '<strong>' . $currencySymbol . number_format($pi->promotional_price, 2) . '</strong> <span class="badge bg-success">-' . round($pi->discount_percentage) . '%</span>',
                'stock'       => $stockBadge,
                'valid_until' => $pi->valid_until ? $pi->valid_until->format('Y-m-d H:i') : '-',
                'status'      => $statusBadge,
                'actions'     => $actions ?: '-',
            ];
        }

        return response()->json([
            'total' => $total,
            'rows'  => $rows,
        ]);
    }

    public function update(Request $request)
    {
        ResponseService::noPermissionThenSendJson('promotion-item-update');

        $validator = Validator::make($request->all(), [
            'id'                       => 'required|integer|exists:promotion_items,id',
            'promotional_price'        => 'required|numeric|min:0.01',
            'stock_quantity'           => 'required|integer|min:1',
            'remaining_stock_quantity' => 'required|integer|min:0',
            'valid_until'              => 'nullable|date',
            'status'                   => 'required|in:active,inactive,sold_out,expired,pending,rejected',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $pi = PromotionItem::findOrFail($request->id);
            $data = $request->only(['promotional_price', 'stock_quantity', 'remaining_stock_quantity', 'status']);

            if ($request->filled('valid_until')) {
                $data['valid_until'] = Carbon::parse($request->valid_until);
            }

            if ((int) $request->remaining_stock_quantity <= 0) {
                $data['status'] = 'sold_out';
            }

            $pi->update($data);

            DB::commit();
            return ResponseService::successResponse(__('Promotional item updated successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'PromotionItemController -> update');
            return ResponseService::errorResponse(__('Failed to update promotional item'));
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('promotion-item-delete');

        try {
            $pi = PromotionItem::findOrFail($id);
            $pi->delete();

            return ResponseService::successResponse(__('Promotional item removed successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'PromotionItemController -> destroy');
            return ResponseService::errorResponse(__('Failed to delete promotional item'));
        }
    }
}
