<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ChatTemplate;
use App\Models\ChatTemplateCategory;
use App\Models\ChatTemplateQuestion;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\HelperService;
use App\Services\ResponseService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ChatTemplateController extends Controller
{
    /**
     * Drop any category id whose ancestor is also in the list, so only the
     * topmost (main) selected category per branch is stored.
     */
    private function collapseToTopLevelCategoryIds(array $categoryIds): array
    {
        $categoryIds = array_map('intval', $categoryIds);
        $idSet = array_flip($categoryIds);

        $categories = Category::whereIn('id', $categoryIds)->get(['id', 'parent_category_id'])->keyBy('id');

        return array_values(array_filter($categoryIds, function ($id) use ($idSet, &$categories) {
            $current = $categories->get($id);

            while ($current && $current->parent_category_id) {
                if (isset($idSet[$current->parent_category_id])) {
                    return false;
                }

                if (! $categories->has($current->parent_category_id)) {
                    $parent = Category::find($current->parent_category_id, ['id', 'parent_category_id']);
                    if ($parent) {
                        $categories->put($parent->id, $parent);
                    }
                }

                $current = $categories->get($current->parent_category_id);
            }

            return true;
        }));
    }

    /**
     * @param  array<int, array<int, string>>  $questionsByLang  [langId => [questionText, ...]] positionally aligned across languages
     */
    /**
     * A category can only belong to one chat template at a time. Returns the names of any
     * requested category ids that are already assigned to a different template, so the
     * caller can reject the request instead of silently saving a broken/partial template.
     */
    private function categoriesAlreadyInUse(array $categoryIds, ?int $excludeTemplateId = null): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $conflictingIds = ChatTemplateCategory::whereIn('category_id', $categoryIds)
            ->when($excludeTemplateId, fn ($query) => $query->where('chat_template_id', '!=', $excludeTemplateId))
            ->pluck('category_id')
            ->unique();

        if ($conflictingIds->isEmpty()) {
            return [];
        }

        return Category::without('translations')->whereIn('id', $conflictingIds)->pluck('name')->all();
    }

    private function saveQuestions(ChatTemplate $chatTemplate, string $role, array $questionsByLang, int $defaultLangId, $otherLanguages): void
    {
        $chatTemplate->questions()->where('role', $role)->delete();

        $defaultQuestions = $questionsByLang[$defaultLangId] ?? [];
        $sequence = 0;

        foreach ($defaultQuestions as $index => $question) {
            $question = trim((string) $question);
            if ($question === '') {
                continue;
            }

            $chatTemplateQuestion = ChatTemplateQuestion::create([
                'chat_template_id' => $chatTemplate->id,
                'role' => $role,
                'question' => $question,
                'sequence' => $sequence++,
            ]);

            $translationData = [];
            foreach ($otherLanguages as $language) {
                $translated = trim((string) ($questionsByLang[$language->id][$index] ?? ''));
                if ($translated === '') {
                    continue;
                }

                $translationData[] = [
                    'translatable_id' => $chatTemplateQuestion->id,
                    'translatable_type' => get_class($chatTemplateQuestion),
                    'key' => 'question',
                    'value' => $translated,
                    'language_id' => $language->id,
                ];
            }

            if (! empty($translationData)) {
                HelperService::storeTranslations($translationData);
            }
        }
    }

    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['chat-template-list', 'chat-template-create', 'chat-template-update', 'chat-template-delete']);
        $categories = Category::orderByRaw('parent_category_id IS NULL DESC')->orderBy('sequence')->get();

        return view('chat-templates.index', compact('categories'));
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('chat-template-create');

        $categories = Category::without('translations')
            ->orderBy('sequence')
            ->get()
            ->each->setAppends([]);

        $categories = HelperService::buildNestedChildSubcategoryObject($categories);

        $selected_categories = [];
        $selected_all_categories = [];
        $languages = CachingService::getLanguages()->values();

        return view('chat-templates.create', compact('categories', 'selected_categories', 'selected_all_categories', 'languages'));
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('chat-template-create');

        $languages = CachingService::getLanguages();
        $defaultLangId = 1;
        $otherLanguages = $languages->where('id', '!=', $defaultLangId);

        $rules = [
            "name.$defaultLangId" => 'required|string|max:255',
            'is_global' => 'nullable|boolean',
            'status' => 'required|in:0,1',
            'selected_categories' => 'required_unless:is_global,1|array',
            "customer_questions.$defaultLangId" => 'nullable|array',
            "customer_questions.$defaultLangId.*" => 'nullable|string|max:1000',
            "seller_questions.$defaultLangId" => 'nullable|array',
            "seller_questions.$defaultLangId.*" => 'nullable|string|max:1000',
        ];
        foreach ($otherLanguages as $lang) {
            $rules["name.$lang->id"] = 'nullable|string|max:255';
            $rules["customer_questions.$lang->id"] = 'nullable|array';
            $rules["customer_questions.$lang->id.*"] = 'nullable|string|max:1000';
            $rules["seller_questions.$lang->id"] = 'nullable|array';
            $rules["seller_questions.$lang->id.*"] = 'nullable|string|max:1000';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        $isGlobal = (bool) $request->boolean('is_global');
        $selectedCategories = $isGlobal ? [] : $this->collapseToTopLevelCategoryIds($request->selected_categories ?? []);

        if (! $isGlobal) {
            $conflictingNames = $this->categoriesAlreadyInUse($selectedCategories);
            if (! empty($conflictingNames)) {
                return ResponseService::validationError(
                    __('These categories are already assigned to another chat template: :categories', ['categories' => implode(', ', $conflictingNames)])
                );
            }
        }

        try {
            DB::beginTransaction();

            $chatTemplate = ChatTemplate::create([
                'name' => $request->input("name.$defaultLangId"),
                'is_global' => $isGlobal,
                'status' => $request->status,
                'created_by' => Auth::id(),
            ]);

            if (! $isGlobal) {
                $categoryMappings = collect($selectedCategories)->map(function ($categoryId) use ($chatTemplate) {
                    return [
                        'category_id' => $categoryId,
                        'chat_template_id' => $chatTemplate->id,
                    ];
                })->toArray();

                if (! empty($categoryMappings)) {
                    ChatTemplateCategory::upsert($categoryMappings, ['chat_template_id', 'category_id']);
                }
            }

            $translationData = [];
            foreach ($otherLanguages as $lang) {
                $translatedName = $request->input("name.$lang->id");
                if (! empty($translatedName)) {
                    $translationData[] = [
                        'translatable_id' => $chatTemplate->id,
                        'translatable_type' => get_class($chatTemplate),
                        'key' => 'name',
                        'value' => $translatedName,
                        'language_id' => $lang->id,
                    ];
                }
            }
            if (! empty($translationData)) {
                HelperService::storeTranslations($translationData);
            }

            $this->saveQuestions($chatTemplate, 'customer', $request->input('customer_questions', []), $defaultLangId, $otherLanguages);
            $this->saveQuestions($chatTemplate, 'seller', $request->input('seller_questions', []), $defaultLangId, $otherLanguages);

            DB::commit();

            return ResponseService::successResponse('Chat Template Added Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'ChatTemplateController -> store');

            return ResponseService::errorResponse('Something went wrong while saving the chat template.');
        }
    }

    public function show(Request $request)
    {
        try {
            ResponseService::noPermissionThenSendJson('chat-template-list');
            $offset = $request->input('offset', 0);
            $limit = $request->input('limit', 15);
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'DESC');

            $sql = ChatTemplate::orderBy($sort, $order);
            $sql->with(['categories:id,name,parent_category_id', 'questions']);

            if (! empty($request->filter)) {
                $filterString = html_entity_decode($request->filter, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                try {
                    $filterData = json_decode($filterString, false, 512, JSON_THROW_ON_ERROR);
                    $sql = $sql->filter($filterData);
                } catch (\JsonException $e) {
                    return response()->json(['error' => 'Invalid JSON format in filter parameter'], 400);
                }
            }

            if (! empty($request->search)) {
                $sql = $sql->search($request->search);
            }

            $total = $sql->count();
            $result = $sql->skip($offset)->take($limit)->get();

            $rows = [];
            foreach ($result as $row) {
                $operate = '';
                if (Auth::user()->can('chat-template-update')) {
                    $operate .= BootstrapTableService::editButton(route('chat-templates.edit', $row->id));
                }
                if (Auth::user()->can('chat-template-delete')) {
                    $operate .= BootstrapTableService::deleteButton(route('chat-templates.destroy', $row->id));
                }

                $tempRow = $row->toArray();
                $tempRow['operate'] = $operate;
                $tempRow['category_names'] = $row->is_global
                    ? ['Global']
                    : array_column($row->categories->toArray(), 'name');
                $tempRow['customer_questions_count'] = $row->questions->where('role', 'customer')->count();
                $tempRow['seller_questions_count'] = $row->questions->where('role', 'seller')->count();

                $rows[] = $tempRow;
            }

            return response()->json(['total' => $total, 'rows' => $rows]);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'ChatTemplateController -> show');

            return ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function edit($id)
    {
        ResponseService::noPermissionThenRedirect('chat-template-update');

        $chat_template = ChatTemplate::with(['chat_template_category', 'customerQuestions.translations', 'sellerQuestions.translations', 'translations'])->findOrFail($id);

        $selected_categories = $chat_template->chat_template_category->pluck('category_id')->toArray();
        $selected_all_categories = $selected_categories;

        foreach ($selected_categories as $catId) {
            $categoryId = $catId;
            while ($categoryId) {
                $parent = Category::without('translations')->where('id', $categoryId)->value('parent_category_id');
                if ($parent) {
                    $selected_all_categories[] = $parent;
                    $categoryId = $parent;
                } else {
                    $categoryId = null;
                }
            }
        }
        $selected_all_categories = array_unique($selected_all_categories);

        $categories = Category::without('translations')
            ->orderBy('sequence')
            ->get()
            ->each->setAppends([]);

        $categories = HelperService::buildNestedChildSubcategoryObject($categories);
        $languages = CachingService::getLanguages()->values();

        $name_translations = [1 => $chat_template->name];
        foreach ($chat_template->translations->where('key', 'name') as $translation) {
            $name_translations[$translation->language_id] = $translation->value;
        }

        $original_category_names = Category::without('translations')
            ->whereIn('id', $selected_categories)
            ->pluck('name')
            ->all();

        return view('chat-templates.edit', compact('chat_template', 'categories', 'selected_categories', 'selected_all_categories', 'languages', 'name_translations', 'original_category_names'));
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('chat-template-update');

        $languages = CachingService::getLanguages();
        $defaultLangId = 1;
        $otherLanguages = $languages->where('id', '!=', $defaultLangId);

        $rules = [
            "name.$defaultLangId" => 'required|string|max:255',
            'is_global' => 'nullable|boolean',
            'status' => 'required|in:0,1',
            'selected_categories' => 'required_unless:is_global,1|array',
            "customer_questions.$defaultLangId" => 'nullable|array',
            "customer_questions.$defaultLangId.*" => 'nullable|string|max:1000',
            "seller_questions.$defaultLangId" => 'nullable|array',
            "seller_questions.$defaultLangId.*" => 'nullable|string|max:1000',
        ];
        foreach ($otherLanguages as $lang) {
            $rules["name.$lang->id"] = 'nullable|string|max:255';
            $rules["customer_questions.$lang->id"] = 'nullable|array';
            $rules["customer_questions.$lang->id.*"] = 'nullable|string|max:1000';
            $rules["seller_questions.$lang->id"] = 'nullable|array';
            $rules["seller_questions.$lang->id.*"] = 'nullable|string|max:1000';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        $isGlobal = (bool) $request->boolean('is_global');
        $newCategories = $isGlobal ? [] : $this->collapseToTopLevelCategoryIds($request->selected_categories ?? []);

        if (! $isGlobal) {
            $conflictingNames = $this->categoriesAlreadyInUse($newCategories, (int) $id);
            if (! empty($conflictingNames)) {
                return ResponseService::validationError(
                    __('These categories are already assigned to another chat template: :categories', ['categories' => implode(', ', $conflictingNames)])
                );
            }
        }

        try {
            DB::beginTransaction();

            $chatTemplate = ChatTemplate::with('chat_template_category')->findOrFail($id);

            $chatTemplate->update([
                'name' => $request->input("name.$defaultLangId"),
                'is_global' => $isGlobal,
                'status' => $request->status,
            ]);

            $oldCategories = $chatTemplate->chat_template_category->pluck('category_id')->toArray();

            foreach (array_diff($oldCategories, $newCategories) as $categoryId) {
                $chatTemplate->chat_template_category
                    ->firstWhere('category_id', $categoryId)
                    ?->delete();
            }

            $insertData = [];
            foreach (array_diff($newCategories, $oldCategories) as $categoryId) {
                $insertData[] = [
                    'category_id' => $categoryId,
                    'chat_template_id' => $chatTemplate->id,
                ];
            }
            if (! empty($insertData)) {
                ChatTemplateCategory::insert($insertData);
            }

            $translationData = [];
            foreach ($otherLanguages as $lang) {
                $translatedName = $request->input("name.$lang->id");
                if (! empty($translatedName)) {
                    $translationData[] = [
                        'translatable_id' => $chatTemplate->id,
                        'translatable_type' => get_class($chatTemplate),
                        'key' => 'name',
                        'value' => $translatedName,
                        'language_id' => $lang->id,
                    ];
                }
            }
            if (! empty($translationData)) {
                HelperService::storeTranslations($translationData);
            }

            $this->saveQuestions($chatTemplate, 'customer', $request->input('customer_questions', []), $defaultLangId, $otherLanguages);
            $this->saveQuestions($chatTemplate, 'seller', $request->input('seller_questions', []), $defaultLangId, $otherLanguages);

            DB::commit();

            return ResponseService::successResponse('Chat Template Updated Successfully');
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'ChatTemplateController -> update');

            return ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function destroy($id)
    {
        try {
            ResponseService::noPermissionThenSendJson('chat-template-delete');
            ChatTemplate::find($id)?->delete();
            ResponseService::successResponse('Chat Template Deleted Successfully');
        } catch (QueryException $th) {
            ResponseService::logErrorResponse($th, 'ChatTemplateController -> destroy');
            ResponseService::errorResponse('Cannot delete chat template! Remove associated data first');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'ChatTemplateController -> destroy');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }
}
