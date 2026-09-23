<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Promotion;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PromotionController extends Controller
{
    private string $uploadFolder = 'promotions';

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['promotion-list', 'promotion-create', 'promotion-update', 'promotion-delete']);

        $totalPromotions = Promotion::count();
        $flashSales = Promotion::flashSales()->count();
        $clearanceSales = Promotion::clearanceSales()->count();
        $dealsOfTheDay = Promotion::dealsOfTheDay()->count();

        $campaigns = Campaign::active()->get();
        $languages = CachingService::getLanguages()->values();

        return view('promotions.index', compact(
            'totalPromotions',
            'flashSales',
            'clearanceSales',
            'dealsOfTheDay',
            'campaigns',
            'languages'
        ));
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('promotion-list');

        $offset = (int) ($request->offset ?? 0);
        $limit = (int) ($request->limit ?? 10);
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $query = Promotion::with(['campaign', 'translations'])->withCount('promotion_items');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('promotion_type')) {
            $query->where('promotion_type', $request->promotion_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $total = $query->count();
        $promotions = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        foreach ($promotions as $promo) {
            $actions = '';

            // View Items button
            $actions .= '<a href="' . route('promotions.items', ['promotion_id' => $promo->id]) . '" class="btn btn-sm btn-icon btn-info rounded-pill me-1" title="' . __('View Items') . '">
                <i class="ph ph-shopping-bag fs-5"></i>
            </a>';

            if (auth()->user()->can('promotion-update')) {
                $actions .= '<button class="btn btn-sm btn-icon btn-primary rounded-pill me-1 edit-promotion" data-promotion=\'' . htmlspecialchars(json_encode($promo), ENT_QUOTES, 'UTF-8') . '\' title="' . __('Edit') . '">
                    <i class="ph ph-pencil-simple fs-5"></i>
                </button>';
            }

            if (auth()->user()->can('promotion-delete')) {
                $actions .= '<button class="btn btn-sm btn-icon btn-danger rounded-pill delete-promotion" data-id="' . $promo->id . '" title="' . __('Delete') . '">
                    <i class="ph ph-trash fs-5"></i>
                </button>';
            }

            $statusSwitch = '';
            if (auth()->user()->can('promotion-update')) {
                $checked = $promo->status === 'active' ? 'checked' : '';
                $statusSwitch = '<div class="form-check form-switch form-switch-md">
                    <input class="form-check-input update-promotion-status" type="checkbox" role="switch" data-id="' . $promo->id . '" ' . $checked . '>
                </div>';
            } else {
                $badgeClass = $promo->status === 'active' ? 'bg-success' : 'bg-secondary';
                $statusSwitch = '<span class="badge ' . $badgeClass . '">' . ucfirst($promo->status) . '</span>';
            }

            $bannerHtml = $promo->banner_image_url
                ? '<img src="' . $promo->banner_image_url . '" alt="' . htmlspecialchars($promo->title) . '" class="rounded shadow-sm" style="width: 80px; height: 45px; object-fit: cover;">'
                : '<span class="text-muted">' . __('No Image') . '</span>';

            $typeBadge = match ($promo->promotion_type) {
                'flash_sale'      => '<span class="badge bg-danger"><i class="ph ph-lightning me-1"></i>' . __('Flash Sale') . '</span>',
                'clearance_sale'  => '<span class="badge bg-warning text-dark"><i class="ph ph-tag me-1"></i>' . __('Clearance') . '</span>',
                'deal_of_the_day' => '<span class="badge bg-info text-dark"><i class="ph ph-clock-countdown me-1"></i>' . __('Deal of the Day') . '</span>',
                default           => '<span class="badge bg-secondary">' . ucfirst($promo->promotion_type) . '</span>',
            };

            $discountText = $promo->discount ? ($promo->discount_type === 'percentage' ? $promo->discount . '%' : '$' . $promo->discount) : '-';

            $dateSchedule = ($promo->start_date ? $promo->start_date->format('Y-m-d') : '') . ' <i class="ph ph-arrow-right"></i> ' . ($promo->end_date ? $promo->end_date->format('Y-m-d') : '');
            if ($promo->start_time || $promo->end_time) {
                $dateSchedule .= '<br><small class="text-muted">' . ($promo->start_time ?: '00:00') . ' - ' . ($promo->end_time ?: '23:59') . '</small>';
            }

            $rows[] = [
                'id'            => $promo->id,
                'banner'        => $bannerHtml,
                'title'         => '<strong>' . htmlspecialchars($promo->title) . '</strong><br><small class="text-muted">' . htmlspecialchars($promo->campaign ? $promo->campaign->title : __('Standalone')) . '</small>',
                'type'          => $typeBadge,
                'schedule'      => $dateSchedule,
                'discount'      => '<span class="fw-bold text-success">' . $discountText . '</span>',
                'items_count'   => '<a href="' . route('promotions.items', ['promotion_id' => $promo->id]) . '" class="badge bg-light-primary text-primary">' . $promo->promotion_items_count . ' ' . __('Items') . '</a>',
                'status'        => $statusSwitch,
                'actions'       => $actions ?: '-',
            ];
        }

        return response()->json([
            'total' => $total,
            'rows'  => $rows,
        ]);
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('promotion-create');

        $validator = Validator::make($request->all(), [
            'title'                => 'required|string|max:255',
            'slug'                 => 'nullable|string|max:255|unique:promotions,slug',
            'campaign_id'          => 'nullable|integer|exists:campaigns,id',
            'promotion_type'       => 'required|in:flash_sale,clearance_sale,deal_of_the_day,custom',
            'description'          => 'nullable|string',
            'banner_image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after_or_equal:start_date',
            'start_time'           => 'nullable|date_format:H:i',
            'end_time'             => 'nullable|date_format:H:i',
            'frequency'            => 'required|in:daily,weekly,custom',
            'discount'             => 'nullable|numeric|min:0',
            'discount_type'        => 'required|in:percentage,flat',
            'status'               => 'required|in:active,inactive,scheduled',
            'priority'             => 'nullable|integer',
            'is_countdown_enabled' => 'nullable|boolean',
            'max_items_per_user'   => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $data = $request->only([
                'title', 'slug', 'campaign_id', 'promotion_type', 'description',
                'start_date', 'end_date', 'start_time', 'end_time', 'frequency',
                'discount', 'discount_type', 'status', 'priority', 'max_items_per_user'
            ]);

            $data['priority'] = $data['priority'] ?? 0;
            $data['is_countdown_enabled'] = $request->boolean('is_countdown_enabled', true);

            if ($request->hasFile('banner_image')) {
                $data['banner_image'] = FileService::compressAndUpload($request->file('banner_image'), $this->uploadFolder);
            }

            $promotion = Promotion::create($data);

            // Handle translations
            $languages = CachingService::getLanguages();
            $translationData = [];
            foreach ($languages as $language) {
                if ($request->has("translations.{$language->id}")) {
                    $trans = $request->input("translations.{$language->id}");
                    if (!empty($trans['title'])) {
                        $translationData[] = [
                            'translatable_id'   => $promotion->id,
                            'translatable_type' => Promotion::class,
                            'key'               => 'title',
                            'value'             => $trans['title'],
                            'language_id'       => $language->id,
                        ];
                    }
                    if (!empty($trans['description'])) {
                        $translationData[] = [
                            'translatable_id'   => $promotion->id,
                            'translatable_type' => Promotion::class,
                            'key'               => 'description',
                            'value'             => $trans['description'],
                            'language_id'       => $language->id,
                        ];
                    }
                }
            }

            if (!empty($translationData)) {
                $promotion->translations()->createMany($translationData);
            }

            DB::commit();
            return ResponseService::successResponse(__('Promotion created successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'PromotionController -> store');
            return ResponseService::errorResponse(__('Failed to create promotion'));
        }
    }

    public function update(Request $request)
    {
        ResponseService::noPermissionThenSendJson('promotion-update');

        $validator = Validator::make($request->all(), [
            'id'                   => 'required|integer|exists:promotions,id',
            'title'                => 'required|string|max:255',
            'slug'                 => 'nullable|string|max:255|unique:promotions,slug,' . $request->id,
            'campaign_id'          => 'nullable|integer|exists:campaigns,id',
            'promotion_type'       => 'required|in:flash_sale,clearance_sale,deal_of_the_day,custom',
            'description'          => 'nullable|string',
            'banner_image'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after_or_equal:start_date',
            'start_time'           => 'nullable',
            'end_time'             => 'nullable',
            'frequency'            => 'required|in:daily,weekly,custom',
            'discount'             => 'nullable|numeric|min:0',
            'discount_type'        => 'required|in:percentage,flat',
            'status'               => 'required|in:active,inactive,scheduled,expired',
            'priority'             => 'nullable|integer',
            'is_countdown_enabled' => 'nullable|boolean',
            'max_items_per_user'   => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $promotion = Promotion::findOrFail($request->id);
            $data = $request->only([
                'title', 'slug', 'campaign_id', 'promotion_type', 'description',
                'start_date', 'end_date', 'start_time', 'end_time', 'frequency',
                'discount', 'discount_type', 'status', 'priority', 'max_items_per_user'
            ]);

            $data['is_countdown_enabled'] = $request->boolean('is_countdown_enabled', true);

            if ($request->hasFile('banner_image')) {
                $data['banner_image'] = FileService::compressAndUpload($request->file('banner_image'), $this->uploadFolder);
            }

            $promotion->update($data);

            // Update translations
            $languages = CachingService::getLanguages();
            foreach ($languages as $language) {
                if ($request->has("translations.{$language->id}")) {
                    $trans = $request->input("translations.{$language->id}");
                    if (!empty($trans['title'])) {
                        $promotion->translations()->updateOrCreate(
                            ['language_id' => $language->id, 'key' => 'title'],
                            ['value' => $trans['title']]
                        );
                    }
                    if (!empty($trans['description'])) {
                        $promotion->translations()->updateOrCreate(
                            ['language_id' => $language->id, 'key' => 'description'],
                            ['value' => $trans['description']]
                        );
                    }
                }
            }

            DB::commit();
            return ResponseService::successResponse(__('Promotion updated successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'PromotionController -> update');
            return ResponseService::errorResponse(__('Failed to update promotion'));
        }
    }

    public function updateStatus(Request $request)
    {
        ResponseService::noPermissionThenSendJson('promotion-update');

        $validator = Validator::make($request->all(), [
            'id'     => 'required|integer|exists:promotions,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $promo = Promotion::findOrFail($request->id);
            $promo->status = $request->status;
            $promo->save();

            return ResponseService::successResponse(__('Promotion status updated successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'PromotionController -> updateStatus');
            return ResponseService::errorResponse(__('Failed to update status'));
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('promotion-delete');

        try {
            $promo = Promotion::findOrFail($id);
            $promo->delete();

            return ResponseService::successResponse(__('Promotion deleted successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'PromotionController -> destroy');
            return ResponseService::errorResponse(__('Failed to delete promotion'));
        }
    }
}
