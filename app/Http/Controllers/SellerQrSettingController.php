<?php

namespace App\Http\Controllers;

use App\Models\SellerQrCode;
use App\Models\Setting;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\ResponseService;
use App\Services\SellerQrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SellerQrSettingController extends Controller
{
    /**
     * Display listing of Seller QR Codes
     */
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['seller-qr-list', 'seller-qr-manage', 'seller-qr-setting']);

        $totalQrCodes = SellerQrCode::count();
        $totalScans = SellerQrCode::sum('scans_count');
        $activeQrCodes = SellerQrCode::where('is_active', true)->count();
        $topScanned = SellerQrCode::with('store')->orderBy('scans_count', 'desc')->first();

        return view('seller_qr.index', compact('totalQrCodes', 'totalScans', 'activeQrCodes', 'topScanned'));
    }

    /**
     * Fetch seller QR codes for Bootstrap Table
     */
    public function show(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('seller-qr-list');

            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 10);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');

            $query = SellerQrCode::with(['user:id,name,email,mobile', 'store:id,name,city,state,is_verified,logo']);

            if ($request->filled('search')) {
                $search = '%' . trim($request->search) . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', $search)
                      ->orWhere('qr_code_token', 'LIKE', $search)
                      ->orWhereHas('user', function ($uq) use ($search) {
                          $uq->where('name', 'LIKE', $search)
                             ->orWhere('email', 'LIKE', $search);
                      })
                      ->orWhereHas('store', function ($sq) use ($search) {
                          $sq->where('name', 'LIKE', $search)
                             ->orWhere('city', 'LIKE', $search);
                      });
                });
            }

            if ($request->filled('status')) {
                $statusVal = $request->status === 'active' ? 1 : 0;
                $query->where('is_active', $statusVal);
            }

            $total = $query->count();
            $items = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

            $rows = [];
            foreach ($items as $row) {
                $storeName = $row->store ? $row->store->name : ($row->user ? $row->user->name : __('N/A'));
                $sellerName = $row->user ? $row->user->name : __('N/A');
                $sellerContact = $row->user ? ($row->user->email ?: $row->user->mobile) : '';

                $statusBadge = $row->is_active
                    ? '<span class="badge bg-success">' . __('Active') . '</span>'
                    : '<span class="badge bg-danger">' . __('Inactive') . '</span>';

                $operate = '';
                $operate .= '<a href="' . route('seller-qr.preview', $row->id) . '" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="' . __('Preview Standee') . '"><i class="ph ph-eye"></i></a>';
                $operate .= '<a href="' . route('seller-qr.download', [$row->id, 'size' => 'standee']) . '" class="btn btn-sm btn-outline-success me-1" title="' . __('Download PDF Standee') . '"><i class="ph ph-download-simple"></i></a>';
                $operate .= '<button class="btn btn-sm btn-outline-' . ($row->is_active ? 'warning' : 'info') . ' toggle-status-btn" data-id="' . $row->id . '" title="' . ($row->is_active ? __('Deactivate') : __('Activate')) . '"><i class="ph ph-power"></i></button>';

                $rows[] = [
                    'id'            => $row->id,
                    'store'         => '<div class="d-flex align-items-center">' .
                                       ($row->store && $row->store->logo ? '<img src="' . $row->store->logo . '" class="rounded me-2" style="width:36px;height:36px;object-fit:cover;">' : '') .
                                       '<div><strong>' . htmlspecialchars($storeName, ENT_QUOTES) . '</strong>' .
                                       ($row->store && $row->store->is_verified ? ' <span class="text-info font-weight-bold">✓</span>' : '') .
                                       '<br><small class="text-muted">' . htmlspecialchars($sellerName . ($sellerContact ? ' (' . $sellerContact . ')' : ''), ENT_QUOTES) . '</small></div></div>',
                    'token'         => '<code>' . htmlspecialchars($row->qr_code_token, ENT_QUOTES) . '</code>',
                    'scans'         => '<span class="badge bg-primary px-2 py-1 fs-6">' . number_format($row->scans_count) . '</span>',
                    'last_scanned'  => $row->last_scanned_at ? $row->last_scanned_at->diffForHumans() : '<span class="text-muted">' . __('Never') . '</span>',
                    'status'        => $statusBadge,
                    'created_at'    => $row->created_at->format('Y-m-d H:i'),
                    'operate'       => $operate,
                ];
            }

            return response()->json([
                'total' => $total,
                'rows'  => $rows,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerQrSettingController -> show');
            return response()->json(['total' => 0, 'rows' => []]);
        }
    }

    /**
     * Show Settings & Standee Customization Form
     */
    public function settings()
    {
        ResponseService::noAnyPermissionThenRedirect(['seller-qr-setting']);

        $settings = SellerQrCodeService::getEffectiveSettings();

        return view('seller_qr.settings', compact('settings'));
    }

    /**
     * Update Seller QR Code Admin Settings
     */
    public function updateSettings(Request $request)
    {
        ResponseService::noPermissionThenSendJson('seller-qr-setting');

        $validator = Validator::make($request->all(), [
            'seller_qr_enabled'                  => 'required|in:0,1',
            'seller_qr_allow_user_logo'          => 'required|in:0,1',
            'seller_qr_allow_user_customization' => 'required|in:0,1',
            'seller_qr_default_title'            => 'required|string|max:191',
            'seller_qr_default_tagline'          => 'nullable|string|max:255',
            'seller_qr_badge_text'               => 'nullable|string|max:100',
            'seller_qr_catalog_banner_text'      => 'nullable|string|max:255',
            'seller_qr_catalog_base_url'         => 'nullable|url|max:255',
            'seller_qr_default_footer_text'      => 'nullable|string|max:191',
            'seller_qr_primary_color'            => ['required', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'seller_qr_secondary_color'          => ['required', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'seller_qr_default_center_logo_type' => 'required|in:platform_logo,store_logo,none',
            'seller_qr_warning_distance_km'      => 'required|numeric|min:1|max:1000',
            'seller_qr_footer_logo'              => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:3072',
            'seller_qr_center_logo'              => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:3072',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $fields = [
                'seller_qr_enabled'                  => $request->seller_qr_enabled,
                'seller_qr_allow_user_logo'          => $request->seller_qr_allow_user_logo,
                'seller_qr_allow_user_customization' => $request->seller_qr_allow_user_customization,
                'seller_qr_default_title'            => $request->seller_qr_default_title,
                'seller_qr_default_tagline'          => $request->seller_qr_default_tagline,
                'seller_qr_badge_text'               => $request->input('seller_qr_badge_text', 'DIGITAL STORE & CATALOG'),
                'seller_qr_catalog_banner_text'      => $request->seller_qr_catalog_banner_text,
                'seller_qr_catalog_base_url'         => $request->seller_qr_catalog_base_url ? rtrim($request->seller_qr_catalog_base_url, '/') : '',
                'seller_qr_default_footer_text'      => $request->seller_qr_default_footer_text,
                'seller_qr_primary_color'            => $request->seller_qr_primary_color,
                'seller_qr_secondary_color'          => $request->seller_qr_secondary_color,
                'seller_qr_default_center_logo_type' => $request->seller_qr_default_center_logo_type,
                'seller_qr_warning_distance_km'      => $request->seller_qr_warning_distance_km,
            ];

            if ($request->hasFile('seller_qr_footer_logo')) {
                $oldLogoSetting = Setting::where('name', 'seller_qr_footer_logo')->first();
                $oldPath = $oldLogoSetting ? $oldLogoSetting->getRawOriginal('value') : null;
                $uploadedPath = FileService::compressAndReplace($request->file('seller_qr_footer_logo'), 'seller_qr', $oldPath);
                $fields['seller_qr_footer_logo'] = $uploadedPath;
            }

            if ($request->hasFile('seller_qr_center_logo')) {
                $oldCenterSetting = Setting::where('name', 'seller_qr_center_logo')->first();
                $oldCenterPath = $oldCenterSetting ? $oldCenterSetting->getRawOriginal('value') : null;
                $uploadedCenterPath = FileService::compressAndReplace($request->file('seller_qr_center_logo'), 'seller_qr', $oldCenterPath);
                $fields['seller_qr_center_logo'] = $uploadedCenterPath;
            }

            foreach ($fields as $name => $value) {
                $type = in_array($name, ['seller_qr_footer_logo', 'seller_qr_center_logo']) ? 'file' : 'string';
                Setting::updateOrCreate(
                    ['name' => $name],
                    ['value' => $value, 'type' => $type]
                );
            }

            DB::commit();
            CachingService::removeCache(config('constants.CACHE.SETTINGS'));

            ResponseService::successResponse(__('Seller QR Code settings updated successfully.'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'SellerQrSettingController -> updateSettings');
            ResponseService::errorResponse();
        }
    }

    /**
     * Toggle active status of a Seller QR Code
     */
    public function toggleStatus($id)
    {
        try {
            ResponseService::noPermissionThenSendJson('seller-qr-manage');

            $qr = SellerQrCode::findOrFail($id);
            $qr->update(['is_active' => !$qr->is_active]);

            ResponseService::successResponse(__('Seller QR status updated successfully'), [
                'is_active' => $qr->is_active,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerQrSettingController -> toggleStatus');
            ResponseService::errorResponse();
        }
    }

    /**
     * Preview a Seller's standee in browser
     */
    public function previewStandee($id)
    {
        ResponseService::noAnyPermissionThenRedirect(['seller-qr-list', 'seller-qr-manage']);

        $qrCode = SellerQrCode::with(['user', 'store'])->findOrFail($id);
        return SellerQrCodeService::renderStandeeHtml($qrCode, 'standee');
    }

    /**
     * Download a Seller's standee PDF
     */
    public function downloadStandee($id, Request $request)
    {
        ResponseService::noAnyPermissionThenRedirect(['seller-qr-list', 'seller-qr-manage']);

        $size = $request->input('size', 'standee');
        $qrCode = SellerQrCode::with(['user', 'store'])->findOrFail($id);

        if (ob_get_level()) {
            ob_end_clean();
        }

        $pdfStream = SellerQrCodeService::generateStandeePdf($qrCode, $size);

        $storeSlug = $qrCode->store ? $qrCode->store->slug : 'seller-' . $qrCode->user_id;
        $filename = 'Standee-' . $storeSlug . '-' . $size . '.pdf';

        return response($pdfStream, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
