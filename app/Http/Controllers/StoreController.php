<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Services\BootstrapTableService;
use App\Services\HelperService;
use App\Services\ResponseService;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StoreController extends Controller
{
    /**
     * Display a listing of stores.
     */
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['store-list', 'store-update', 'store-delete']);
        return view('stores.index');
    }

    /**
     * Fetch store list for Bootstrap Table
     */
    public function show(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('store-list');

            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');

            $sql = Store::with(['user', 'area'])->withCount(['items']);

            if (!empty($request->search)) {
                $sql = $sql->search($request->search);
            }

            if ($request->filled('status')) {
                $sql->where('status', $request->status);
            }

            if ($request->has('is_verified') && $request->is_verified !== '') {
                $sql->where('is_verified', (bool)$request->is_verified);
            }

            $total = $sql->count();
            $sql = $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $result = $sql->get();

            $rows = [];
            $no = $offset + 1;

            foreach ($result as $row) {
                $tempRow = $row->toArray();
                $tempRow['no'] = $no++;
                $tempRow['owner_name'] = $row->user ? $row->user->name : '-';
                $tempRow['owner_email'] = $row->user ? $row->user->email : '-';
                $tempRow['owner_mobile'] = $row->user ? $row->user->mobile : '-';
                $tempRow['items_count'] = $row->items_count ?? 0;

                // Format location
                $locationParts = array_filter([$row->area?->name, $row->city, $row->state, $row->country]);
                $tempRow['formatted_location'] = !empty($locationParts) ? implode(', ', $locationParts) : '-';

                // Status Switch
                $statusHtml = '<div class="form-check form-switch">';
                $statusHtml .= '<input class="form-check-input toggle-store-status" type="checkbox" data-id="' . $row->id . '" ' . ($row->status === 'active' ? 'checked' : '') . '>';
                $statusHtml .= '</div>';
                $tempRow['status_switch'] = $statusHtml;

                // Verification Switch
                $verifyHtml = '<div class="form-check form-switch">';
                $verifyHtml .= '<input class="form-check-input toggle-store-verify" type="checkbox" data-id="' . $row->id . '" ' . ($row->is_verified ? 'checked' : '') . '>';
                $verifyHtml .= '</div>';
                $tempRow['verify_switch'] = $verifyHtml;

                // Actions / Operations
                $operate = '';
                if (Auth()->user()->can('store-delete')) {
                    $operate .= BootstrapTableService::deleteButton(route('stores.destroy', $row->id));
                }

                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }

            return response()->json([
                'total' => $total,
                'rows'  => $rows,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreController -> show');
            return ResponseService::errorResponse();
        }
    }

    /**
     * Update store status or verification
     */
    public function update(Request $request, $id)
    {
        try {
            ResponseService::noPermissionThenSendJson('store-update');

            $store = Store::findOrFail($id);

            if ($request->has('status')) {
                $status = $request->status == 1 || $request->status === 'active' ? 'active' : 'inactive';
                $store->update(['status' => $status]);
                return ResponseService::successResponse(__('Store status updated successfully'));
            }

            if ($request->has('is_verified')) {
                $isVerified = (bool) $request->is_verified;
                $store->update(['is_verified' => $isVerified]);
                return ResponseService::successResponse(__('Store verification status updated successfully'));
            }

            $validator = Validator::make($request->all(), [
                'name'        => 'required|string|max:191',
                'description' => 'nullable|string',
                'email'       => 'nullable|email|max:191',
                'contact'     => 'nullable|string|max:50',
                'address'     => 'nullable|string',
                'city'        => 'nullable|string|max:191',
                'state'       => 'nullable|string|max:191',
                'country'     => 'nullable|string|max:191',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $store->update($request->only([
                'name', 'description', 'email', 'contact', 'address', 'city', 'state', 'country'
            ]));

            return ResponseService::successResponse(__('Store details updated successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreController -> update');
            return ResponseService::errorResponse();
        }
    }

    /**
     * Soft delete store
     */
    public function destroy($id)
    {
        try {
            ResponseService::noPermissionThenSendJson('store-delete');

            DB::beginTransaction();
            $store = Store::findOrFail($id);
            $userId = $store->user_id;

            $store->delete();

            if ($userId) {
                User::where('id', $userId)->update(['has_store' => false]);
            }

            DB::commit();
            return ResponseService::successResponse(__('Store deleted successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'StoreController -> destroy');
            return ResponseService::errorResponse();
        }
    }
}
