<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Notifications;
use App\Models\Setting;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;


class NotificationController extends Controller
{

    private string $uploadFolder;

    public function __construct()
    {
        $this->uploadFolder = "notification";
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['notification-list', 'notification-create', 'notification-update', 'notification-delete']);
        $item_list = Item::where('status', 'approved')->getNonExpiredItems()->get();
        return view('notification.index', compact('item_list'));
    }


    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('notification-create');

        $validator = Validator::make($request->all(), [
            'file'    => 'image|mimes:jpeg,png,jpg',
            'send_to' => 'required|in:all,selected',
            'user_id' => 'required_if:send_to,selected',
            'title'   => 'required',
            'message' => 'required',
        ], [
            'user_id.required_if' => __("Please select at least one user")
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            // Check Service File Exists
            $serviceFileExists = false;
            $serviceFile = Setting::where('name', 'service_file')->first();
            if(collect($serviceFile)->isNotEmpty()){
                $file = $serviceFile->getRawOriginal('value') ?? null;
                $serviceFileExists = FileService::fileExists($file);
            }
            // Get Firebase Project ID
            $firebaseProjectId = CachingService::getSystemSettings('firebase_project_id');
            // Check if any of them is not available show error
            if (!$serviceFileExists || empty($firebaseProjectId)) {
                ResponseService::errorResponse('Notification Configuration is missing.');
            }

            $notification = Notifications::create([
                ...$request->all(),
                'image' => $request->hasFile('file') ? FileService::compressAndUpload($request->file('file'), $this->uploadFolder) : '',
                'user_id' => $request->user_id
            ]);

            $customBodyFields = [
                'notification_id' => $notification->id, // Add this line
                'image' => $notification->image,
                'item_id' => $notification->item_id,
            ];

            $sendToAll = $request->send_to == 'all' ? true : false;
            $userIds = $request->send_to == 'selected' && !empty($request->user_id) ? explode(',', $request->user_id) : array();

            // Dispatch chunked notification jobs using centralized service
            $result = NotificationService::dispatchChunkedNotifications(
                $request->title,
                $request->message,
                'notification',
                $customBodyFields,
                $sendToAll,
                $userIds
            );

            if ($result['success']) {
                $message = trans("Notification queued successfully. It will be sent in background.");
            } else {
                $message = trans('Notification queued with warnings: ' . $result['message']);
            }

            ResponseService::successResponse($message);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'NotificationController -> store');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function destroy($id)
    {
        try {
            ResponseService::noPermissionThenSendJson('notification-delete');
            $notification = Notifications::findOrFail($id);
            $notification->delete();
            FileService::delete($notification->getRawOriginal('image'));
            ResponseService::successResponse('Notification Deleted successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'NotificationController -> destroy');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('notification-list');
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $sql = Notifications::where('id', '!=', 0)->orderBy($sort, $order);

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
            $operate = '';

            if (Auth::user()->can('notification-delete')) {
                $operate .= BootstrapTableService::deleteButton(route('notification.destroy', $row->id));
            }
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function batchDelete(Request $request)
    {
        ResponseService::noPermissionThenSendJson('notification-delete');
        try {
            foreach (Notifications::whereIn('id', explode(',', $request->id))->get() as $row) {
                $row->delete();
                FileService::delete($row->getRawOriginal('image'));
            }
            ResponseService::successResponse("Notification deleted successfully");
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "NotificationController -> batchDelete");
            ResponseService::errorResponse();
        }
    }
}
