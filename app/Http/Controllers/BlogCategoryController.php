<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Services\BootstrapTableService;
use App\Services\HelperService;
use App\Services\ResponseService;
use App\Services\CachingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class BlogCategoryController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['blog-list', 'blog-create', 'blog-update', 'blog-delete']);
        return view('blog_category.index');
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('blog-create');
        $languages = CachingService::getLanguages()->values();
        return view('blog_category.create', compact('languages'));
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenRedirect('blog-create');

        $languages = CachingService::getLanguages();
        $defaultLangId = 1;
        $otherLanguages = $languages->where('id', '!=', $defaultLangId);

        $rules = [
            "name.$defaultLangId" => 'required|string|max:255',
            'is_active'          => 'nullable|boolean',
        ];

        foreach ($otherLanguages as $lang) {
            $langId = $lang->id;
            $rules["name.$langId"] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        try {
            $data = [
                'name' => $request->input("name.$defaultLangId"),
                'is_active' => $request->input('is_active', 1),
            ];

            // Generate unique slug
            $data['slug'] = HelperService::generateUniqueSlug(new BlogCategory(), $request->input("name.$defaultLangId"));

            $blogCategory = BlogCategory::create($data);

            // Store translations for other languages
            $translationData = [];
            foreach ($otherLanguages as $lang) {
                $langId = $lang->id;
                $translatedName = $request->input("name.$langId");

                if (!empty($translatedName)) {
                    $translationData[] = [
                        'translatable_id'   => $blogCategory->id,
                        'translatable_type' => BlogCategory::class,
                        'key'               => 'name',
                        'value'             => $translatedName,
                        'language_id'       => $langId,
                    ];
                }
            }
            if (!empty($translationData)) {
                HelperService::storeTranslations($translationData);
            }

            // Store SEO details
            HelperService::storeSeoDetails($blogCategory, $request, $languages->pluck('id')->toArray());

            ResponseService::successRedirectResponse("Blog Category Added Successfully", route('blog-category.index'));
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorRedirectResponse();
        }
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('blog-list');

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');

        $sql = BlogCategory::query();

        if (!empty($request->search)) {
            $sql = $sql->search($request->search);
        }

        $total = $sql->count();
        $sql = $sql->sort($sort, $order)->skip($offset)->take($limit);
        $result = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = 1;

        foreach ($result as $row) {
            $operate = '';
            if (Auth::user()->can('blog-update')) {
                $operate .= BootstrapTableService::editButton(route('blog-category.edit', $row->id));
            }

            if (Auth::user()->can('blog-delete')) {
                $operate .= BootstrapTableService::deleteButton(route('blog-category.destroy', $row->id));
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $tempRow['is_active'] = $row->is_active;

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function edit($id)
    {
        ResponseService::noPermissionThenRedirect('blog-update');
        $blogCategory = BlogCategory::findOrFail($id);

        $translations = [];
        $translations[1] = [
            'name' => $blogCategory->name,
        ];

        if($blogCategory->translations){
            $grouped = $blogCategory->translations->groupBy('language_id');
            foreach ($grouped as $langId => $items) {
                $translations[$langId] = [];
                foreach ($items as $item) {
                    $translations[$langId][$item->key] = $item->value;
                }
            }
        }

        $seoTranslations = HelperService::prepareSeoTranslationsForEdit($blogCategory);
        $languages = CachingService::getLanguages()->values();

        return view('blog_category.edit', compact('blogCategory', 'translations', 'seoTranslations', 'languages'));
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenRedirect('blog-update');

        $languages = CachingService::getLanguages();
        $defaultLangId = 1;
        $otherLanguages = $languages->where('id', '!=', $defaultLangId);

        $rules = [
            "name.$defaultLangId" => 'required|string|max:255',
            'is_active'          => 'nullable|boolean',
        ];

        foreach ($otherLanguages as $lang) {
            $langId = $lang->id;
            $rules["name.$langId"] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        try {
            $blogCategory = BlogCategory::findOrFail($id);

            $data = [
                'name' => $request->input("name.$defaultLangId"),
                'is_active' => $request->input('is_active', 0),
            ];

            // Re-generate slug if needed
            $data['slug'] = HelperService::generateUniqueSlug(new BlogCategory(), $request->input("name.$defaultLangId"), $id);
            $blogCategory->update($data);

            $translationData = [];
            foreach ($otherLanguages as $lang) {
                $langId = $lang->id;
                $translatedName = $request->input("name.$langId");

                $translationData[] = [
                    'translatable_id'   => $blogCategory->id,
                    'translatable_type' => BlogCategory::class,
                    'key'               => 'name',
                    'value'             => $translatedName ?? '',
                    'language_id'       => $langId,
                ];
            }
            if (!empty($translationData)) {
                HelperService::storeTranslations($translationData);
            }

            // Store SEO details
            HelperService::storeSeoDetails($blogCategory, $request, $languages->pluck('id')->toArray());

            ResponseService::successRedirectResponse("Blog Category Updated Successfully", route('blog-category.index'));
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th);
            ResponseService::errorRedirectResponse('Something Went Wrong');
        }
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('blog-delete');

        try {
            $blogCategory = BlogCategory::findOrFail($id);

            // Check if any blogs are using this category
            $isUsed = Blog::where('category_id', $id)->exists();
            if ($isUsed) {
                return ResponseService::errorResponse(
                    __('This category is associated with existing blogs. Please reassign or delete those blogs before deleting.'),
                    422
                );
            }

            $blogCategory->delete();
            return ResponseService::successResponse(__('Blog Category Deleted Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, "BlogCategoryController->destroy");
            return ResponseService::errorResponse(__('Something Went Wrong'));
        }
    }
}
