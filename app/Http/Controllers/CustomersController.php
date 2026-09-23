<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Models\UserPurchasedPackage;
use App\Services\BootstrapTableService;
use App\Services\HelperService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class CustomersController extends Controller {
    public function index() {
        ResponseService::noAnyPermissionThenRedirect(['customer-list', 'customer-update']);
        $packages = Package::all()->where('status', 1);
        $settings = Setting::whereIn('name', ['currency_symbol', 'currency_symbol_position','free_ad_listing'])
        ->pluck('value', 'name');
        $currency_symbol = $settings['currency_symbol'] ?? '';
        $currency_symbol_position = $settings['currency_symbol_position'] ?? '';
        $free_ad_listing = $settings['free_ad_listing'] ?? '';
        $itemListingPackage = $packages->filter(function ($data) {
            return $data->type == "item_listing";
        });
        $advertisementPackage = $packages->filter(function ($data) {
            return $data->type == "advertisement";
        });

        return view('customer.index', compact('packages', 'itemListingPackage', 'advertisementPackage','currency_symbol','currency_symbol_position','free_ad_listing'));
    }

    public function update(Request $request) {
        try {
            ResponseService::noPermissionThenSendJson('customer-update');
            $adminUserID = User::role('Super Admin')->first()?->id;
            $userUpdateQuery = User::where('id', $request->id);
            if($adminUserID){
                $userUpdateQuery->where('id', '!=', $adminUserID);
            }
            $userUpdateQuery->update(['status' => $request->status]);
            $message = $request->status ? "Customer Activated Successfully" : "Customer Deactivated Successfully";
            ResponseService::successResponse($message);
        } catch (Throwable) {
            ResponseService::errorRedirectResponse('Something Went Wrong ');
        }
    }

    public function show(Request $request) {
        ResponseService::noAnyPermissionThenSendJson(['customer-list','notification-list', 'notification-create', 'notification-update', 'notification-delete']);
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        if ($request->notification_list) {
            $sql = User::role('User')->orderBy($sort, $order)->has('fcm_tokens')->where('notification', 1);
        } else {
            $sql = User::role('User')->orderBy($sort, $order)->withCount('items')->withTrashed();
        }


        if (!empty($request->search)) {
            $sql = $sql->search($request->search);
        }

        $total = $sql->count();
        $sql->skip($offset)->take($limit);
        $result = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($result as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['status'] = empty($row->deleted_at);
            $tempRow['is_verified'] = $row->is_verified;
            $tempRow['auto_approve_advertisement'] = $row->auto_approve_item;

            $tempRow = HelperService::maskDemoUserData($tempRow);

            $operate = BootstrapTableService::button(
                'ph ph-plus-circle',
                route('customer.assign.package', $row->id),
                ['assign_package'],
                [
                    'title'          => __("Assign Package"),
                    "data-bs-target" => "#assignPackageModal",
                    "data-bs-toggle" => "modal"
                ]
            );
            
            $operate .= BootstrapTableService::button(
                'ph ph-minus-circle',
                '#',
                ['manage_packages', 'ms-1'],
                [
                    'title'          => __("Cancel Packages"),
                    "data-bs-target" => "#managePackagesModal",
                    "data-bs-toggle" => "modal",
                    "data-user-id"   => $row->id
                ]
            );
            
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function assignPackage(Request $request) {
        ResponseService::noPermissionThenSendJson('customer-update');
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,id',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        $package = Package::findOrFail($request->package_id);
        if ($package->final_price > 0) {
            $gatewayValidator = Validator::make($request->all(), [
                'payment_gateway' => 'required|in:cash,cheque',
            ], [
                'payment_gateway.required' => __('Please select payment type, cash or cheque. If you cannot see the option, try assigning the package again.'),
                'payment_gateway.in'       => __('Please select payment type, cash or cheque. If you cannot see the option, try assigning the package again.'),
            ]);
            if ($gatewayValidator->fails()) {
                ResponseService::validationError($gatewayValidator->errors()->first());
            }
        }

        //try {
            DB::beginTransaction();
            $user = User::find($request->user_id);
            if (empty($user)) {
                ResponseService::errorResponse('User is not Active');
            }
            // Create a new payment transaction
            $paymentTransaction = PaymentTransaction::create([
                'user_id'         => $request->user_id,
                'package_id'      => $request->package_id,
                'amount'          => $package->final_price,
                'original_price'  => $package->price,
                'discount_price'  => $package->price - $package->final_price,
                'order_id'        => Str::random(10),
                'payment_gateway' => $package->final_price == 0 ? 'Free' : $request->payment_gateway,
                'payment_status'  => 'succeed',
            ]);

            // Create a new user purchased package record
          $userPackage =  UserPurchasedPackage::create([
                'user_id'                 => $request->user_id,
                'package_id'              => $request->package_id,
                'start_date'              => Carbon::now(),
                'end_date'                => is_null($package->duration) ? null : Carbon::now()->addDays((int)$package->duration),
                'total_limit'             => $package->item_limit == "unlimited" ? null : $package->item_limit,
                'used_limit'              => 0,
                'payment_transactions_id' => $paymentTransaction->id,
                'listing_duration_type' => $package->listing_duration_type,
                'listing_duration_days' => $package->listing_duration_days,
            ]);

            if (!empty($request->user_id)) {
                $title = "Package Assigned";
                $message = "A new subscription package has been assigned to your account by the administrator.";


                // Dispatch chunked notification jobs using centralized service
                NotificationService::dispatchChunkedNotifications(
                    $title,
                    $message,
                    'package-assigned',
                    ['id' => $userPackage->id],
                    false,
                    array($request->user_id)
                );
                // NotificationService::sendFcmNotification(
                //     $user_token,
                //     $title,
                //     $message,
                //     "package-assigned",
                //     ['id' => $userPackage->id]
                //);
            }

            DB::commit();
            ResponseService::successResponse('Package assigned to user Successfully');
        // } catch (Throwable $th) {
        //     DB::rollback();
        //     ResponseService::logErrorResponse($th, "CustomersController --> assignPackage");
        //     ResponseService::errorResponse();
        // }
    }

    public function getActivePackages(Request $request) {
        ResponseService::noPermissionThenSendJson('customer-update');
        try {
            $userId = $request->user_id;
            if (empty($userId)) {
                ResponseService::errorResponse('User ID is required');
            }

            $activePackages = UserPurchasedPackage::where('user_id', $userId)
                ->whereDate('start_date', '<=', date('Y-m-d'))
                ->where(function ($q) {
                    $q->whereDate('end_date', '>', date('Y-m-d'))->orWhereNull('end_date');
                })
                ->with(['package' => function($q) {
                    $q->select('id', 'name', 'type', 'duration', 'item_limit');
                }])
                ->orderBy('end_date', 'asc')
                ->get();

            $packages = [];
            foreach ($activePackages as $pkg) {
                $packages[] = [
                    'id' => $pkg->id,
                    'package_name' => $pkg->package->name ?? '',
                    'package_type' => $pkg->package->type ?? '',
                    'start_date' => $pkg->start_date,
                    'end_date' => $pkg->end_date ?? __('Unlimited'),
                    'total_limit' => $pkg->total_limit ?? __('Unlimited'),
                    'used_limit' => $pkg->used_limit,
                    'remaining_limit' => $pkg->remaining_item_limit,
                    'remaining_days' => $pkg->remaining_days,
                ];
            }

            ResponseService::successResponse(__('Data Fetched Successfully'), $packages);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "CustomersController --> getActivePackages");
            ResponseService::errorResponse();
        }
    }

    public function cancelPackage(Request $request) {
        ResponseService::noPermissionThenSendJson('customer-update');
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:user_purchased_packages,id',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $userPackage = UserPurchasedPackage::findOrFail($request->package_id);
            
            // Set end_date to today to cancel the package
            $userPackage->end_date = date('Y-m-d');
            $userPackage->save();
            $userID = $userPackage->user_id;

            if (!empty($userID)) {
                $title = "Subscription Cancelled";
                $message = "Your subscription has been cancelled by the administrator.";

                // Dispatch chunked notification jobs using centralized service
                NotificationService::dispatchChunkedNotifications(
                    $title,
                    $message,
                    'package-cancelled',
                    ['id' => $userPackage->id],
                    false,
                    array($userID)
                );
                // NotificationService::sendFcmNotification(
                //     $user_token,
                //     $title,
                //     $message,
                //     "package-cancelled",
                //     ['id' => $userPackage->id]
                // );
            }

            DB::commit();
            ResponseService::successResponse(__('Package cancelled successfully'));
        } catch (Throwable $th) {
            DB::rollback();
            ResponseService::logErrorResponse($th, "CustomersController --> cancelPackage");
            ResponseService::errorResponse();
        }
    }
}
