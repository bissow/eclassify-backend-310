<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Store;
use App\Models\User;
use App\Services\BootstrapTableService;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\ResponseService;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Throwable;

class StoreController extends Controller
{
    private string $uploadFolder = 'store';
    private string $galleryFolder = 'store/gallery';

    /**
     * Display a listing of stores.
     */
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['store-list', 'store-create', 'store-update', 'store-delete']);

        $totalStores = 0;
        $activeStores = 0;
        $verifiedStores = 0;

        if (Schema::hasTable('stores')) {
            $totalStores = Store::count();
            $activeStores = Store::where('status', 'active')->count();
            $verifiedStores = Store::where('is_verified', true)->count();
        }

        return view('stores.index', compact('totalStores', 'activeStores', 'verifiedStores'));
    }

    /**
     * Search users for Select2 dropdown
     */
    public function searchUsers(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['store-create', 'store-update']);

        $search = $request->input('q', '');
        $users = User::select('id', 'name', 'email', 'mobile')
            ->where(function ($q) use ($search) {
                if (!empty($search)) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('mobile', 'LIKE', "%{$search}%");
                }
            })
            ->limit(30)
            ->get();

        $results = $users->map(function ($u) {
            $label = $u->name . ' (' . ($u->email ?: $u->mobile ?: 'ID: ' . $u->id) . ')';
            return [
                'id'   => $u->id,
                'text' => $label,
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Fetch store list for Bootstrap Table
     */
    public function show(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('store-list');

            if (!Schema::hasTable('stores')) {
                return response()->json([
                    'total' => 0,
                    'rows'  => [],
                ]);
            }

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
                $sql->where('is_verified', (bool) $request->is_verified);
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

                // View Details button
                $operate .= '<button type="button" class="btn icon btn-s btn-rounded btn-icon rounded-pill mb-1 btn-white view-store-btn" data-id="' . $row->id . '" title="' . __('View Store Details') . '"><i class="ph ph-eye text-info" style="font-size:18px;"></i></button>&nbsp;&nbsp;';

                // Edit Button
                if (auth()->user()->can('store-update')) {
                    $operate .= '<button type="button" class="btn icon btn-s btn-rounded btn-icon rounded-pill mb-1 btn-white edit-store-btn" data-id="' . $row->id . '" title="' . __('Edit Store') . '"><i class="ph ph-note-pencil text-primary" style="font-size:18px;"></i></button>&nbsp;&nbsp;';
                }

                // Delete Button
                if (auth()->user()->can('store-delete')) {
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
     * Get Store Details for View or Edit Modal
     */
    public function getDetails($id)
    {
        try {
            ResponseService::noAnyPermissionThenSendJson(['store-list', 'store-update']);

            $store = Store::with(['user', 'area'])->withCount('items')->findOrFail($id);

            $data = $store->toArray();
            $data['raw_working_days'] = $store->working_days ?? [];
            $data['owner'] = $store->user ? [
                'id'      => $store->user->id,
                'name'    => $store->user->name,
                'email'   => $store->user->email,
                'mobile'  => $store->user->mobile,
                'profile' => $store->user->profile,
            ] : null;

            return ResponseService::successResponse(__('Store details fetched successfully'), $data);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreController -> getDetails');
            return ResponseService::errorResponse(__('Store not found or error loading details'));
        }
    }

    /**
     * Store a newly created store in storage (Admin)
     */
    public function store(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('store-create');

            $validator = Validator::make($request->all(), [
                'user_id'      => 'required|integer|exists:users,id',
                'name'         => 'required|string|max:191',
                'slug'         => 'nullable|string|max:191|unique:stores,slug',
                'description'  => 'nullable|string',
                'email'        => 'nullable|email|max:191',
                'contact'      => 'nullable|string|max:50',
                'country_code' => 'nullable|string|max:10',
                'address'      => 'nullable|string',
                'city'         => 'nullable|string|max:191',
                'state'        => 'nullable|string|max:191',
                'country'      => 'nullable|string|max:191',
                'latitude'     => 'nullable|numeric|between:-90,90',
                'longitude'    => 'nullable|numeric|between:-180,180',
                'area_id'      => 'nullable|integer',
                'website'      => 'nullable|string|max:191',
                'tax_number'   => 'nullable|string|max:191',
                'opening_time' => 'nullable|string|max:20',
                'closing_time' => 'nullable|string|max:20',
                'working_days' => 'nullable',
                'status'       => 'required|in:active,inactive,pending',
                'is_verified'  => 'nullable|boolean',
                'logo'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'banner'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:7168',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            // Check if user already owns an active store
            $existing = Store::where('user_id', $request->user_id)->first();
            if ($existing) {
                return ResponseService::errorResponse(__('This user already has a store registered (ID: ' . $existing->id . '). Please edit the existing store instead.'));
            }

            DB::beginTransaction();

            $slug = !empty($request->slug)
                ? Str::slug($request->slug)
                : StoreService::generateSlug($request->name);

            $workingDays = $request->working_days;
            if (is_string($workingDays)) {
                $decoded = json_decode($workingDays, true);
                $workingDays = is_array($decoded) ? $decoded : explode(',', $workingDays);
            }

            $storeData = [
                'user_id'      => $request->user_id,
                'name'         => $request->name,
                'slug'         => $slug,
                'description'  => $request->description,
                'email'        => $request->email,
                'contact'      => $request->contact,
                'country_code' => $request->country_code,
                'address'      => $request->address,
                'city'         => $request->city,
                'state'        => $request->state,
                'country'      => $request->country,
                'latitude'     => $request->filled('latitude') ? (float) $request->latitude : null,
                'longitude'    => $request->filled('longitude') ? (float) $request->longitude : null,
                'area_id'      => $request->filled('area_id') ? (int) $request->area_id : null,
                'website'      => $request->website,
                'tax_number'   => $request->tax_number,
                'opening_time' => $request->opening_time ?? '09:00 AM',
                'closing_time' => $request->closing_time ?? '08:00 PM',
                'working_days' => $workingDays,
                'status'       => $request->status ?? 'active',
                'is_verified'  => (bool) ($request->is_verified ?? false),
            ];

            if ($request->hasFile('logo')) {
                $storeData['logo'] = FileService::compressAndUpload($request->file('logo'), $this->uploadFolder);
            }

            if ($request->hasFile('banner')) {
                $storeData['banner'] = FileService::compressAndUpload($request->file('banner'), $this->uploadFolder);
            }

            $store = Store::create($storeData);

            // Update user flag & location
            User::where('id', $request->user_id)->update([
                'has_store' => true,
                'latitude'  => $storeData['latitude'] ?? null,
                'longitude' => $storeData['longitude'] ?? null,
                'country'   => $storeData['country'] ?? null,
                'state'     => $storeData['state'] ?? null,
                'city'      => $storeData['city'] ?? null,
                'area_id'   => $storeData['area_id'] ?? null,
            ]);

            DB::commit();

            return ResponseService::successResponse(__('Store created successfully'), $store);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'StoreController -> store');
            return ResponseService::errorResponse();
        }
    }

    /**
     * Update store details or status/verification (Admin)
     */
    public function update(Request $request, $id)
    {
        try {
            ResponseService::noPermissionThenSendJson('store-update');

            $store = Store::findOrFail($id);

            // Single Switch: Status Toggle
            if ($request->has('status') && !$request->has('name')) {
                $status = $request->status == 1 || $request->status === 'active' ? 'active' : 'inactive';
                $store->update(['status' => $status]);
                return ResponseService::successResponse(__('Store status updated successfully'));
            }

            // Single Switch: Verification Toggle
            if ($request->has('is_verified') && !$request->has('name')) {
                $isVerified = (bool) $request->is_verified;
                $store->update(['is_verified' => $isVerified]);
                return ResponseService::successResponse(__('Store verification status updated successfully'));
            }

            // Full Form Update
            $validator = Validator::make($request->all(), [
                'user_id'      => 'nullable|integer|exists:users,id',
                'name'         => 'required|string|max:191',
                'slug'         => 'nullable|string|max:191|unique:stores,slug,' . $id,
                'description'  => 'nullable|string',
                'email'        => 'nullable|email|max:191',
                'contact'      => 'nullable|string|max:50',
                'country_code' => 'nullable|string|max:10',
                'address'      => 'nullable|string',
                'city'         => 'nullable|string|max:191',
                'state'        => 'nullable|string|max:191',
                'country'      => 'nullable|string|max:191',
                'latitude'     => 'nullable|numeric|between:-90,90',
                'longitude'    => 'nullable|numeric|between:-180,180',
                'area_id'      => 'nullable|integer',
                'website'      => 'nullable|string|max:191',
                'tax_number'   => 'nullable|string|max:191',
                'opening_time' => 'nullable|string|max:20',
                'closing_time' => 'nullable|string|max:20',
                'working_days' => 'nullable',
                'status'       => 'nullable|in:active,inactive,pending',
                'is_verified'  => 'nullable|boolean',
                'logo'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                'banner'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:7168',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            DB::beginTransaction();

            $oldUserId = $store->user_id;
            $newUserId = $request->filled('user_id') ? (int) $request->user_id : $oldUserId;

            if ($newUserId !== $oldUserId) {
                $anotherStore = Store::where('user_id', $newUserId)->where('id', '!=', $id)->first();
                if ($anotherStore) {
                    return ResponseService::errorResponse(__('The selected new user already has a store assigned.'));
                }
            }

            $slug = !empty($request->slug)
                ? Str::slug($request->slug)
                : ($store->name !== $request->name ? StoreService::generateSlug($request->name, $id) : $store->slug);

            $workingDays = $request->working_days;
            if (is_string($workingDays)) {
                $decoded = json_decode($workingDays, true);
                $workingDays = is_array($decoded) ? $decoded : explode(',', $workingDays);
            }

            $updateData = [
                'user_id'      => $newUserId,
                'name'         => $request->name,
                'slug'         => $slug,
                'description'  => $request->description,
                'email'        => $request->email,
                'contact'      => $request->contact,
                'country_code' => $request->country_code ?? $store->country_code,
                'address'      => $request->address,
                'city'         => $request->city,
                'state'        => $request->state,
                'country'      => $request->country,
                'latitude'     => $request->filled('latitude') ? (float) $request->latitude : null,
                'longitude'    => $request->filled('longitude') ? (float) $request->longitude : null,
                'area_id'      => $request->filled('area_id') ? (int) $request->area_id : null,
                'website'      => $request->website,
                'tax_number'   => $request->tax_number,
                'opening_time' => $request->opening_time ?? $store->opening_time,
                'closing_time' => $request->closing_time ?? $store->closing_time,
                'working_days' => $workingDays ?? $store->working_days,
                'status'       => $request->status ?? $store->status,
                'is_verified'  => $request->has('is_verified') ? (bool) $request->is_verified : $store->is_verified,
            ];

            if ($request->hasFile('logo')) {
                $rawLogo = $store->getRawOriginal('logo');
                $updateData['logo'] = FileService::compressAndReplace($request->file('logo'), $this->uploadFolder, $rawLogo);
            }

            if ($request->hasFile('banner')) {
                $rawBanner = $store->getRawOriginal('banner');
                $updateData['banner'] = FileService::compressAndReplace($request->file('banner'), $this->uploadFolder, $rawBanner);
            }

            $store->update($updateData);

            // Re-sync user flags
            if ($newUserId !== $oldUserId) {
                User::where('id', $oldUserId)->update(['has_store' => false]);
            }

            User::where('id', $newUserId)->update([
                'has_store' => true,
                'latitude'  => $updateData['latitude'] ?? null,
                'longitude' => $updateData['longitude'] ?? null,
                'country'   => $updateData['country'] ?? null,
                'state'     => $updateData['state'] ?? null,
                'city'      => $updateData['city'] ?? null,
                'area_id'   => $updateData['area_id'] ?? null,
            ]);

            DB::commit();

            return ResponseService::successResponse(__('Store details updated successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
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

    // ==========================================
    // BULK UPLOAD & GALLERY MANAGEMENT
    // ==========================================

    /**
     * Display the Bulk Upload page for stores.
     */
    public function bulkUploadIndex()
    {
        ResponseService::noPermissionThenRedirect('store-create');
        return view('stores.bulk-upload');
    }

    /**
     * Download example CSV template for stores bulk upload.
     */
    public function bulkUploadExample()
    {
        ResponseService::noPermissionThenRedirect('store-create');

        try {
            $filename = 'stores-bulk-upload-example.csv';

            if (ob_get_level()) {
                ob_end_clean();
            }

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            $headers = [
                'User Identifier (Email or Mobile or User ID)',
                'Store Name',
                'Slug (Optional)',
                'Description',
                'Logo Path',
                'Banner Path',
                'Contact Phone',
                'Contact Email',
                'Address',
                'City',
                'State',
                'Country',
                'Latitude',
                'Longitude',
                'Opening Time',
                'Closing Time',
                'Working Days',
                'Website',
                'Tax Number',
                'Is Verified',
                'Status',
            ];

            fputcsv($output, $headers);

            $examples = [
                [
                    'seller1@example.com',
                    'Apex Electronics & Gadgets',
                    'apex-electronics',
                    'Premium retailer of genuine electronics, smartphones, and laptops.',
                    'store/gallery/logo1.png',
                    'store/gallery/banner1.jpg',
                    '9876543210',
                    'contact@apexelectronics.com',
                    '102 Grand Central Plaza, Tech Road',
                    'Malda',
                    'West Bengal',
                    'India',
                    '25.0108',
                    '88.1411',
                    '09:00 AM',
                    '08:00 PM',
                    'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
                    'https://apexelectronics.com',
                    'GSTIN-19ABCDE1234F1Z5',
                    '1',
                    '1',
                ],
                [
                    'seller2@example.com',
                    'Style Haven Boutique',
                    'style-haven',
                    'Fashion apparel, traditional attire, and footwear collection.',
                    'store/gallery/logo2.png',
                    'store/gallery/banner2.jpg',
                    '9123456789',
                    'stylehaven@example.com',
                    'Shop 14, Royal Fashion Mall',
                    'Kolkata',
                    'West Bengal',
                    'India',
                    '22.5726',
                    '88.3639',
                    '10:00 AM',
                    '09:00 PM',
                    'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                    'https://stylehaven.com',
                    'TAX-998877',
                    '0',
                    '1',
                ],
            ];

            foreach ($examples as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
            exit;
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreController -> bulkUploadExample');
            return ResponseService::errorResponse('Error generating CSV file: ' . $th->getMessage());
        }
    }

    /**
     * Process bulk upload CSV / Excel for stores.
     */
    public function bulkUploadProcess(Request $request)
    {
        ResponseService::noPermissionThenSendJson('store-create');

        $request->validate([
            'excel_file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('excel_file');
            $filePath = $file->getRealPath();
            $extension = strtolower($file->getClientOriginalExtension());

            if ($extension === 'csv') {
                $reader = new Csv();
                $reader->setInputEncoding('UTF-8');
                $reader->setDelimiter(',');
                $reader->setEnclosure('"');
                $reader->setSheetIndex(0);
            } elseif ($extension === 'xlsx') {
                $reader = new Xlsx();
            } elseif ($extension === 'xls') {
                $reader = new Xls();
            } else {
                return ResponseService::errorResponse(__('Unsupported file format. Only CSV, XLSX and XLS are supported.'));
            }

            $reader->setReadDataOnly(false);
            $reader->setReadEmptyCells(true);

            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);
            $rows = array_values($rows);

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if (count($rows) <= 1) {
                return ResponseService::errorResponse(__('File must contain at least one data row (excluding the header).'));
            }

            // Remove header row
            $header = array_shift($rows);

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $rowVals = array_values($row);

                $userIdentifier = trim((string) ($rowVals[0] ?? ''));
                $storeName      = trim((string) ($rowVals[1] ?? ''));
                $slug           = trim((string) ($rowVals[2] ?? ''));
                $description    = trim((string) ($rowVals[3] ?? ''));
                $logoPath       = trim((string) ($rowVals[4] ?? ''));
                $bannerPath     = trim((string) ($rowVals[5] ?? ''));
                $contact        = trim((string) ($rowVals[6] ?? ''));
                $email          = trim((string) ($rowVals[7] ?? ''));
                $address        = trim((string) ($rowVals[8] ?? ''));
                $city           = trim((string) ($rowVals[9] ?? ''));
                $state          = trim((string) ($rowVals[10] ?? ''));
                $country        = trim((string) ($rowVals[11] ?? ''));
                $latitude       = trim((string) ($rowVals[12] ?? ''));
                $longitude      = trim((string) ($rowVals[13] ?? ''));
                $openingTime    = trim((string) ($rowVals[14] ?? '09:00 AM'));
                $closingTime    = trim((string) ($rowVals[15] ?? '08:00 PM'));
                $workingDaysRaw = trim((string) ($rowVals[16] ?? ''));
                $website        = trim((string) ($rowVals[17] ?? ''));
                $taxNumber      = trim((string) ($rowVals[18] ?? ''));
                $isVerifiedVal  = trim((string) ($rowVals[19] ?? '0'));
                $statusVal      = trim((string) ($rowVals[20] ?? '1'));

                if (empty($userIdentifier) && empty($storeName)) {
                    continue; // Skip blank lines
                }

                if (empty($userIdentifier)) {
                    $errors[] = "Row {$rowNumber}: User Identifier is required.";
                    $errorCount++;
                    continue;
                }

                if (empty($storeName)) {
                    $errors[] = "Row {$rowNumber}: Store Name is required.";
                    $errorCount++;
                    continue;
                }

                // Match user by ID, email, or mobile
                $user = null;
                if (is_numeric($userIdentifier)) {
                    $user = User::find((int) $userIdentifier);
                }
                if (!$user && filter_var($userIdentifier, FILTER_VALIDATE_EMAIL)) {
                    $user = User::where('email', $userIdentifier)->first();
                }
                if (!$user) {
                    $user = User::where('mobile', $userIdentifier)->first();
                }

                if (!$user) {
                    $errors[] = "Row {$rowNumber}: User '{$userIdentifier}' not found in database.";
                    $errorCount++;
                    continue;
                }

                // Check working days
                $workingDays = [];
                if (!empty($workingDaysRaw)) {
                    $workingDays = array_map('trim', explode(',', $workingDaysRaw));
                } else {
                    $workingDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                }

                $isVerified = ($isVerifiedVal === '1' || strtolower($isVerifiedVal) === 'yes' || strtolower($isVerifiedVal) === 'true');
                $status = ($statusVal === '1' || strtolower($statusVal) === 'active') ? 'active' : 'inactive';

                $storeSlug = !empty($slug)
                    ? Str::slug($slug)
                    : StoreService::generateSlug($storeName, null);

                $store = Store::where('user_id', $user->id)->first();

                $storePayload = [
                    'user_id'      => $user->id,
                    'name'         => $storeName,
                    'slug'         => $store ? $store->slug : $storeSlug,
                    'description'  => $description ?: null,
                    'email'        => $email ?: $user->email,
                    'contact'      => $contact ?: $user->mobile,
                    'country_code' => $user->country_code,
                    'address'      => $address ?: null,
                    'city'         => $city ?: null,
                    'state'        => $state ?: null,
                    'country'      => $country ?: null,
                    'latitude'     => is_numeric($latitude) ? (float) $latitude : null,
                    'longitude'    => is_numeric($longitude) ? (float) $longitude : null,
                    'opening_time' => $openingTime ?: '09:00 AM',
                    'closing_time' => $closingTime ?: '08:00 PM',
                    'working_days' => $workingDays,
                    'website'      => $website ?: null,
                    'tax_number'   => $taxNumber ?: null,
                    'is_verified'  => $isVerified,
                    'status'       => $status,
                ];

                if (!empty($logoPath)) {
                    $storePayload['logo'] = $logoPath;
                }
                if (!empty($bannerPath)) {
                    $storePayload['banner'] = $bannerPath;
                }

                if ($store) {
                    $store->update($storePayload);
                } else {
                    Store::create($storePayload);
                }

                $user->update([
                    'has_store' => true,
                    'latitude'  => $storePayload['latitude'] ?? $user->latitude,
                    'longitude' => $storePayload['longitude'] ?? $user->longitude,
                    'country'   => $storePayload['country'] ?? $user->country,
                    'state'     => $storePayload['state'] ?? $user->state,
                    'city'      => $storePayload['city'] ?? $user->city,
                ]);

                $successCount++;
            }

            DB::commit();

            $msg = "{$successCount} store(s) processed successfully.";
            if ($errorCount > 0) {
                $msg .= " {$errorCount} row(s) encountered issues: " . implode(' | ', array_slice($errors, 0, 5));
            }

            return ResponseService::successResponse($msg, [
                'success_count' => $successCount,
                'error_count'   => $errorCount,
                'errors'        => $errors,
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'StoreController -> bulkUploadProcess');
            return ResponseService::errorResponse('Bulk upload error: ' . $th->getMessage());
        }
    }

    /**
     * Get list of gallery images for store bulk upload.
     */
    public function getGalleryImages()
    {
        try {
            ResponseService::noPermissionThenSendJson('store-create');

            $disk = config('filesystems.default');
            $files = Storage::disk($disk)->files($this->galleryFolder);

            $images = [];
            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $images[] = [
                        'path' => $file,
                        'url'  => url(Storage::url($file)),
                        'name' => basename($file),
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'images' => $images,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreController -> getGalleryImages');
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }

    /**
     * Upload images to the store gallery.
     */
    public function uploadGalleryImages(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('store-create');

            $request->validate([
                'images'   => 'required|array',
                'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);

            $disk = config('filesystems.default');
            $uploaded = 0;

            foreach ($request->file('images') as $file) {
                $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $file->getClientOriginalExtension();
                $path = $this->galleryFolder . '/' . $fileName;
                Storage::disk($disk)->putFileAs($this->galleryFolder, $file, $fileName);
                $uploaded++;
            }

            return response()->json([
                'status'  => 'success',
                'message' => "{$uploaded} image(s) uploaded successfully to store gallery.",
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'StoreController -> uploadGalleryImages');
            return response()->json(['status' => 'error', 'message' => $th->getMessage()]);
        }
    }
}

