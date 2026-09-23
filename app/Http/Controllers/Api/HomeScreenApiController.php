<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\HomeScreenSection;
use App\Models\PopularCategory;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Throwable;

/** @tags Home Screen */
class HomeScreenApiController extends BaseApiController
{
    /** Get Home Screen Configuration */
    public function getHomeScreen(Request $request)
    {
        try {
            $sections = HomeScreenSection::active()
                ->orderBy('sequence')
                ->get(['id','section_type', 'sequence']);

            ResponseService::successResponse('Data Fetched Successfully', [
                'sections' => $sections,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getHomeScreen');
            ResponseService::errorResponse();
        }
    }

    /** Get Popular Categories */
    public function getPopularCategories(Request $request)
    {
        try {
            $popularCategories = PopularCategory::with(['category' => function($q){
                $q->where('status',1)->with('translations');
            }])
                ->orderBy('sequence')
                ->get()
                ->pluck('category')
                ->filter();

            ResponseService::successResponse('Data Fetched Successfully', $popularCategories->values());
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getPopularCategories');
            ResponseService::errorResponse();
        }
    }
}
