<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CampaignController extends Controller
{
    private string $uploadFolder = 'campaigns';

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['campaign-list', 'campaign-create', 'campaign-update', 'campaign-delete']);

        $totalCampaigns = Campaign::count();
        $activeCampaigns = Campaign::active()->currentlyRunning()->count();
        $expiredCampaigns = Campaign::where('status', 'expired')->orWhere('end_date', '<', Carbon::today())->count();
        $languages = CachingService::getLanguages()->values();

        return view('campaigns.index', compact('totalCampaigns', 'activeCampaigns', 'expiredCampaigns', 'languages'));
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('campaign-list');

        $offset = (int) ($request->offset ?? 0);
        $limit = (int) ($request->limit ?? 10);
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $query = Campaign::with('translations')->withCount('promotions');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $total = $query->count();
        $campaigns = $query->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        foreach ($campaigns as $campaign) {
            $actions = '';

            if (auth()->user()->can('campaign-update')) {
                $editData = [
                    'id'          => $campaign->id,
                    'title'       => $campaign->title,
                    'slug'        => $campaign->slug,
                    'description' => $campaign->description,
                    'start_date'  => $campaign->start_date ? $campaign->start_date->format('Y-m-d') : '',
                    'end_date'    => $campaign->end_date ? $campaign->end_date->format('Y-m-d') : '',
                    'status'      => $campaign->status,
                    'priority'    => $campaign->priority,
                    'translations'=> $campaign->translations->pluck('value', 'key')->toArray(),
                ];

                $actions .= '<button class="btn btn-sm btn-icon btn-primary rounded-pill me-1 edit-campaign" data-campaign=\'' . htmlspecialchars(json_encode($campaign), ENT_QUOTES, 'UTF-8') . '\' title="' . __('Edit') . '">
                    <i class="ph ph-pencil-simple fs-5"></i>
                </button>';
            }

            if (auth()->user()->can('campaign-delete')) {
                $actions .= '<button class="btn btn-sm btn-icon btn-danger rounded-pill delete-campaign" data-id="' . $campaign->id . '" title="' . __('Delete') . '">
                    <i class="ph ph-trash fs-5"></i>
                </button>';
            }

            $statusSwitch = '';
            if (auth()->user()->can('campaign-update')) {
                $checked = $campaign->status === 'active' ? 'checked' : '';
                $statusSwitch = '<div class="form-check form-switch form-switch-md">
                    <input class="form-check-input update-campaign-status" type="checkbox" role="switch" data-id="' . $campaign->id . '" ' . $checked . '>
                </div>';
            } else {
                $badgeClass = $campaign->status === 'active' ? 'bg-success' : 'bg-secondary';
                $statusSwitch = '<span class="badge ' . $badgeClass . '">' . ucfirst($campaign->status) . '</span>';
            }

            $bannerHtml = $campaign->banner_image_url
                ? '<img src="' . $campaign->banner_image_url . '" alt="' . htmlspecialchars($campaign->title) . '" class="rounded shadow-sm" style="width: 80px; height: 45px; object-fit: cover;">'
                : '<span class="text-muted">' . __('No Image') . '</span>';

            $rows[] = [
                'id'               => $campaign->id,
                'banner'           => $bannerHtml,
                'title'            => '<strong>' . htmlspecialchars($campaign->title) . '</strong><br><small class="text-muted">' . htmlspecialchars($campaign->slug) . '</small>',
                'start_date'       => $campaign->start_date ? $campaign->start_date->format('Y-m-d') : '-',
                'end_date'         => $campaign->end_date ? $campaign->end_date->format('Y-m-d') : '-',
                'promotions_count' => '<span class="badge bg-light-primary text-primary">' . $campaign->promotions_count . ' ' . __('Promotions') . '</span>',
                'priority'         => $campaign->priority,
                'status'           => $statusSwitch,
                'actions'          => $actions ?: '-',
            ];
        }

        return response()->json([
            'total' => $total,
            'rows'  => $rows,
        ]);
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('campaign-create');

        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'slug'         => 'nullable|string|max:255|unique:campaigns,slug',
            'description'  => 'nullable|string',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'status'       => 'required|in:active,inactive,scheduled',
            'priority'     => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $data = $request->only(['title', 'slug', 'description', 'start_date', 'end_date', 'status', 'priority']);
            $data['priority'] = $data['priority'] ?? 0;

            if ($request->hasFile('banner_image')) {
                $data['banner_image'] = FileService::compressAndUpload($request->file('banner_image'), $this->uploadFolder);
            }

            $campaign = Campaign::create($data);

            // Handle translations
            $languages = CachingService::getLanguages();
            $translationData = [];
            foreach ($languages as $language) {
                if ($request->has("translations.{$language->id}")) {
                    $trans = $request->input("translations.{$language->id}");
                    if (!empty($trans['title'])) {
                        $translationData[] = [
                            'translatable_id'   => $campaign->id,
                            'translatable_type' => Campaign::class,
                            'key'               => 'title',
                            'value'             => $trans['title'],
                            'language_id'       => $language->id,
                        ];
                    }
                    if (!empty($trans['description'])) {
                        $translationData[] = [
                            'translatable_id'   => $campaign->id,
                            'translatable_type' => Campaign::class,
                            'key'               => 'description',
                            'value'             => $trans['description'],
                            'language_id'       => $language->id,
                        ];
                    }
                }
            }

            if (!empty($translationData)) {
                $campaign->translations()->createMany($translationData);
            }

            DB::commit();
            return ResponseService::successResponse(__('Campaign created successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'CampaignController -> store');
            return ResponseService::errorResponse(__('Failed to create campaign'));
        }
    }

    public function update(Request $request)
    {
        ResponseService::noPermissionThenSendJson('campaign-update');

        $validator = Validator::make($request->all(), [
            'id'           => 'required|integer|exists:campaigns,id',
            'title'        => 'required|string|max:255',
            'slug'         => 'nullable|string|max:255|unique:campaigns,slug,' . $request->id,
            'description'  => 'nullable|string',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'status'       => 'required|in:active,inactive,scheduled,expired',
            'priority'     => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $campaign = Campaign::findOrFail($request->id);
            $data = $request->only(['title', 'slug', 'description', 'start_date', 'end_date', 'status', 'priority']);

            if ($request->hasFile('banner_image')) {
                $data['banner_image'] = FileService::compressAndUpload($request->file('banner_image'), $this->uploadFolder);
            }

            $campaign->update($data);

            // Update translations
            $languages = CachingService::getLanguages();
            foreach ($languages as $language) {
                if ($request->has("translations.{$language->id}")) {
                    $trans = $request->input("translations.{$language->id}");
                    if (!empty($trans['title'])) {
                        $campaign->translations()->updateOrCreate(
                            ['language_id' => $language->id, 'key' => 'title'],
                            ['value' => $trans['title']]
                        );
                    }
                    if (!empty($trans['description'])) {
                        $campaign->translations()->updateOrCreate(
                            ['language_id' => $language->id, 'key' => 'description'],
                            ['value' => $trans['description']]
                        );
                    }
                }
            }

            DB::commit();
            return ResponseService::successResponse(__('Campaign updated successfully'));
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'CampaignController -> update');
            return ResponseService::errorResponse(__('Failed to update campaign'));
        }
    }

    public function updateStatus(Request $request)
    {
        ResponseService::noPermissionThenSendJson('campaign-update');

        $validator = Validator::make($request->all(), [
            'id'     => 'required|integer|exists:campaigns,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $campaign = Campaign::findOrFail($request->id);
            $campaign->status = $request->status;
            $campaign->save();

            return ResponseService::successResponse(__('Campaign status updated successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'CampaignController -> updateStatus');
            return ResponseService::errorResponse(__('Failed to update status'));
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('campaign-delete');

        try {
            $campaign = Campaign::findOrFail($id);
            $campaign->delete();

            return ResponseService::successResponse(__('Campaign deleted successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'CampaignController -> destroy');
            return ResponseService::errorResponse(__('Failed to delete campaign'));
        }
    }
}
