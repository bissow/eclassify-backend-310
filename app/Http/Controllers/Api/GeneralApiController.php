<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BannerAd;
use App\Models\PluginLicense;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeneralApiController extends Controller
{
    /**
     * Get Enabled Plugins
     */
    public function getEnabledPlugins(Request $request)
    {
        try {
            $domain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
            $plugins = PluginLicense::where(['domain' => $domain, 'is_enabled' => true, 'revoked' => false])
                ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
                ->get()->map(function ($license) {
                    return [
                        'slug' => $license->plugin_slug,
                        'type' => $license->type,
                        'version' => $license->version,
                    ];
                });

            return ResponseService::successResponse('Enabled plugins fetched successfully', $plugins);
        } catch (Exception $e) {
            return ResponseService::errorResponse('Something went wrong', $e->getMessage());
        }
    }


    /**
     * Get Banner Ads
     */
    public function getBannerAds(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform'  => 'required|in:web,app',
                'page'      => 'required|in:home,detail,listing',
                'ad_type'   => 'nullable|in:only_banner,category,advertisement,external_link',
            ]);
            if ($validator->fails()) {
                return ResponseService::errorResponse('Validation failed', $validator->errors()->first());
            }

            $banners = BannerAd::query()
                ->where('status', 1)
                ->when($request->filled('page'), function ($query) use ($request) {
                    $query->where('page', $request->page);
                })
                ->when($request->filled('platform'), function ($query) use ($request) {
                    $query->where('platform', $request->platform);
                })
                ->when($request->filled('ad_type'), function ($query) use ($request) {
                    $query->where('ad_type', $request->ad_type);
                })
                ->with(['advertisement' => function($adQuery){
                    $adQuery->without('translations')->where('status', 'approved')->select('id', 'slug', 'status');
                }, 'category' => function($categoryQuery){
                    $categoryQuery->without('translations')->where('status', 1)->select('id', 'name', 'slug', 'path', 'image', 'status');
                }])
                ->orderBy('position')
                ->get();

            // Category/Ad deleted or disabled -> relation resolves null. Degrade banner to only_banner
            // so the client renders it as a plain image instead of navigating to missing data.
            $banners->each(function ($banner) {
                if ($banner->ad_type === 'category' && !$banner->category) {
                    $banner->ad_type = 'only_banner';
                } elseif ($banner->ad_type === 'advertisement' && !$banner->advertisement) {
                    $banner->ad_type = 'only_banner';
                }
            });

            // Group banners into "places": page + section + placement. Layout varies per candidate.
            $places = $banners->groupBy(function ($banner) {
                return implode('|', [
                    $banner->page,
                    $banner->home_screen_section_id,
                    $banner->feature_section_id,
                    $banner->detail_page_section,
                    $banner->listing_page_section,
                    $banner->placement,
                ]);
            });

            // Per place: build candidates, shuffle, send one winner.
            // single/single_side -> 1 banner. dual/dual_side -> 2 banners sharing group_id.
            $ads = $places->map(function ($placeBanners) {
                $candidates = [];

                // dual layouts: one candidate per group_id (carries both positions) as nested array.
                foreach ($placeBanners->whereNotNull('group_id')->groupBy('group_id') as $groupBanners) {
                    $candidates[] = $groupBanners->sortBy('position')->values()->all();
                }

                // single layouts: one candidate per banner row.
                foreach ($placeBanners->whereNull('group_id') as $banner) {
                    $candidates[] = $banner;
                }

                if (empty($candidates)) {
                    return null;
                }

                return $candidates[array_rand($candidates)];
            })->filter()->values();

            return ResponseService::successResponse('Banner ads fetched successfully', $ads);
        } catch (Exception $e) {
            return ResponseService::errorResponse('Something went wrong', $e->getMessage());
        }
    }
}
