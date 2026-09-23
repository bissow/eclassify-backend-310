<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomeScreenSection;
use App\Models\PopularCategory;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class HomeScreenSectionController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['home-screen-section-list', 'home-screen-section-update']);

        $sections = HomeScreenSection::orderBy('sequence')->get();
        $popularCategories = PopularCategory::with('category')->orderBy('sequence')->get();
        $categories = Category::whereNotIn('id', $popularCategories->pluck('category_id'))->orderByRaw('parent_category_id IS NULL DESC')->orderBy('sequence')->get();

        return view('home_screen_section.index', compact('sections', 'popularCategories', 'categories'));
    }

    public function popularCategoriesList(Request $request)
    {
        ResponseService::noAnyPermissionThenSendJson(['home-screen-section-list', 'home-screen-section-update']);

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $sort = $request->input('sort', 'sequence');
        $order = $request->input('order', 'asc');

        $sql = PopularCategory::with('category')->orderBy($sort, $order);

        if (!empty($request->search)) {
            $search = $request->search;
            $sql->whereHas('category', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $total = $sql->count();
        $result = $sql->skip($offset)->take($limit)->get();

        $rows = [];
        foreach ($result as $pc) {
            $imgSrc = ($pc->category && $pc->category->image)
                ? $pc->category->image
                : asset('assets/images/logo/placeholder.png');
            $imgAlt = $pc->category->name ?? trans('Deleted Category');
            $imageHtml = '<img src="' . e($imgSrc) . '" alt="' . e($imgAlt) . '" width="40" height="40" style="object-fit: cover; border-radius: 4px;">';

            $typeHtml = ($pc->category && $pc->category->parent_category_id)
                ? '<span class="badge bg-info">' . trans('Sub Category') . '</span>'
                : '<span class="badge bg-primary">' . trans('Main Category') . '</span>';

            $operate = '';
            if (Auth::user()->can('home-screen-section-update')) {
                $operate = BootstrapTableService::deleteButton(
                    route('home-screen-section.popular-categories.delete', $pc->id),
                    null,
                    null,
                    null,
                    '-reload'
                );
            }

            $rows[] = [
                'id' => $pc->id,
                'sequence' => $pc->sequence,
                'image' => $imageHtml,
                'name' => $pc->category->name ?? trans('Deleted Category'),
                'type' => $typeHtml,
                'action' => $operate,
            ];
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function toggleSection(Request $request)
    {
        ResponseService::noPermissionThenSendJson('home-screen-section-update');

        try {
            $request->validate([
                'id' => 'required|exists:home_screen_sections,id',
                'is_active' => 'required|boolean',
            ]);

            HomeScreenSection::where('id', $request->id)->update([
                'is_active' => $request->is_active,
            ]);

            ResponseService::successResponse('Section Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function storePopularCategory(Request $request)
    {
        ResponseService::noPermissionThenSendJson('home-screen-section-update');

        try {
            $request->validate([
                'category_id' => 'required|exists:categories,id|unique:popular_categories,category_id',
            ]);

            $maxSequence = PopularCategory::max('sequence') ?? 0;

            PopularCategory::create([
                'category_id' => $request->category_id,
                'sequence' => $maxSequence + 1,
            ]);

            ResponseService::successResponse('Category Added Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function deletePopularCategory($id)
    {
        ResponseService::noPermissionThenSendJson('home-screen-section-update');

        try {
            PopularCategory::findOrFail($id)->delete();
            ResponseService::successResponse('Category Removed Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function popularCategoriesOrder()
    {
        ResponseService::noAnyPermissionThenRedirect(['home-screen-section-list', 'home-screen-section-update']);

        $popularCategories = PopularCategory::with('category')->orderBy('sequence')->get();

        return view('home_screen_section.popular-categories-order', compact('popularCategories'));
    }

    public function updatePopularCategoryOrder(Request $request)
    {
        ResponseService::noPermissionThenSendJson('home-screen-section-update');

        $request->validate([
            'order' => 'required',
        ]);

        try {
            $order = json_decode($request->input('order'), true);
            $data = [];
            foreach ($order as $index => $id) {
                $data[] = [
                    'id' => $id,
                    'sequence' => $index + 1,
                ];
            }
            PopularCategory::upsert($data, ['id'], ['sequence']);
            ResponseService::successResponse('Order Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorResponse('Something Went Wrong');
        }
    }
}
