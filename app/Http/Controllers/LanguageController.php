<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Setting;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\ResponseService;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Throwable;

class LanguageController extends Controller
{
    private string $uploadFolder;

    public function __construct()
    {
        $this->uploadFolder = 'language';
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'name_in_english' => 'required',
            'code' => 'required|unique:languages,code|regex:/^[a-zA-Z]+$/',
            'rtl' => 'nullable',
            'image' => 'required|mimes:jpeg,png,jpg,svg|max:7168',
            'country_code' => 'nullable',
        ], [
            'code.regex' => trans('Only characters are allowed for the language code.'),
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $data = $request->all();
            $data['rtl'] = $request->rtl == 'on';

            if ($request->hasFile('panel_file')) {
                $data['panel_file'] = FileService::uploadLanguageFile($request->file('panel_file'), $request->code);
            }

            if ($request->hasFile('app_file')) {
                $data['app_file'] = FileService::uploadLanguageFile($request->file('app_file'), $request->code . '_app');
            }

            if ($request->hasFile('web_file')) {
                $data['web_file'] = FileService::uploadLanguageFile($request->file('web_file'), $request->code . '_web');
            }

            if ($request->hasFile('image')) {
                $data['image'] = FileService::upload($request->file('image'), $this->uploadFolder);
            }

            Language::create($data);
            CachingService::removeCache(config('constants.CACHE.LANGUAGE'));
            HelperService::revalidateLanguageCodes();
            ResponseService::successResponse('Language Successfully Added');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th, 'Language Controller -> Store');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function show(Request $request)
    {
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $sql = Language::orderBy($sort, $order);

        if (! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")->orwhere('code', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%");
        }
        $total = $sql->count();
        $sql->skip($offset)->take($limit);
        $result = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        foreach ($result as $key => $row) {
            $tempRow = $row->toArray();
            $tempRow['rtl_text'] = ($row->rtl == 1) ? 'Yes' : 'No';
            $operate = '';
            if ($row->code == 'en') {
                $operate .= BootstrapTableService::editButton(route('language.update', $row->id), true);
            } else {
                $operate .= BootstrapTableService::editButton(route('language.update', $row->id), true);
                $operate .= BootstrapTableService::deleteButton(route('language.destroy', $row->id));
            }
            $dropdownItems = [
                [
                    'icon' => '',
                    'url' => route('languageedit', [$row->id, 'type' => 'panel']),
                    'text' => trans('Edit Panel Json'),
                ],
                [
                    'icon' => '',
                    'url' => route('languageedit', [$row->id, 'type' => 'web']),
                    'text' => trans('Edit Web Json'),
                ],
                [
                    'icon' => '',
                    'url' => route('languageedit', [$row->id, 'type' => 'app']),
                    'text' => trans('Edit App Json'),
                ],
            ];

            $operate .= BootstrapTableService::dropdown('ph-bold ph-dots-three-vertical', $dropdownItems);

            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function update(Request $request, $id)
    {
        try {
            $language = Language::findOrFail($id);
            $isDefaultLang = ($language->code === 'en');

            // Different validation rules for default 'en' language (only name & image allowed)
            if ($isDefaultLang) {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'image' => 'nullable|mimes:jpeg,png,jpg,svg|max:7168',
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'name_in_english' => 'required',
                    'code' => 'required|regex:/^[a-zA-Z]+$/|unique:languages,code,' . $id,
                    'rtl' => 'nullable|boolean',
                    'app_file' => 'nullable|mimes:json',
                    'panel_file' => 'nullable|mimes:json',
                    'web_file' => 'nullable|mimes:json',
                    'image' => 'nullable|mimes:jpeg,png,jpg,svg|max:7168',
                    'country_code' => 'nullable',
                ], [
                    'code.regex' => trans('Only characters are allowed for the language code.'),
                ]);
            }

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $oldCode = $language->code;
            $defaultCode = Setting::where('name', 'default_language')->value('value');

            // For default 'en' language, only update name and image
            if ($isDefaultLang) {
                $data = $request->only(['name']);

                if ($request->hasFile('image')) {
                    $data['image'] = FileService::replace(
                        $request->file('image'),
                        $this->uploadFolder,
                        $language->getRawOriginal('image')
                    );
                }

                $language->update($data);
                CachingService::removeCache(config('constants.CACHE.LANGUAGE'));
                HelperService::revalidateLanguageCodes();

                return ResponseService::successResponse('Language Updated successfully');
            }

            $newCode = $request->input('code');

            $data = $request->only([
                'name',
                'name_in_english',
                'code',
                'country_code',
            ]);

            // Preserve RTL unless changed
            if ($request->has('rtl')) {
                $data['rtl'] = (bool) $request->rtl;
            }

            unset($data['is_default']);

            if ($request->hasFile('panel_file')) {
                $data['panel_file'] = FileService::uploadLanguageFile(
                    $request->file('panel_file'),
                    $oldCode
                );
            }

            if ($request->hasFile('app_file')) {
                $data['app_file'] = FileService::uploadLanguageFile(
                    $request->file('app_file'),
                    $oldCode . '_app'
                );
            }

            if ($request->hasFile('web_file')) {
                $data['web_file'] = FileService::uploadLanguageFile(
                    $request->file('web_file'),
                    $oldCode . '_web'
                );
            }

            if ($request->hasFile('image')) {
                $data['image'] = FileService::replace(
                    $request->file('image'),
                    $this->uploadFolder,
                    $language->getRawOriginal('image')
                );
            }


            if ($oldCode !== $newCode) {
                FileService::renameLanguageFiles($oldCode, $newCode);
            }

            if ($defaultCode === $oldCode) {

                Setting::updateOrCreate(
                    ['name' => 'default_language'],
                    ['value' => $newCode, 'type' => 'string']
                );

                Session::forget('locale');
                Session::put('locale', $newCode);
                Session::save();

                app()->setLocale($newCode);
            }

            if (Session::get('locale') === $oldCode) {
                Session::put('locale', $newCode);
                app()->setLocale($newCode);
            }

            $language->update($data);

            CachingService::removeCache(config('constants.CACHE.LANGUAGE'));
            HelperService::revalidateLanguageCodes();

            return ResponseService::successResponse('Language Updated successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Language Controller --> update');
            return ResponseService::errorResponse('Something Went Wrong');
        }
    }


    public function destroy($id)
    {
        try {
            $language = Language::findOrFail($id);

            // Prevent deleting the 'en' language as it serves as the base template
            if ($language->code === 'en') {
                ResponseService::errorResponse('You cannot delete the default English language as it serves as the base template');
                return;
            }

            $language->delete();

            // Delete language files
            FileService::deleteLanguageFile($language->app_file);
            FileService::deleteLanguageFile($language->panel_file);
            FileService::deleteLanguageFile($language->web_file);
            FileService::delete($language->getRawOriginal('image'));
            CachingService::removeCache(config('constants.CACHE.LANGUAGE'));
            HelperService::revalidateLanguageCodes();

            ResponseService::successResponse('Language Deleted successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th, 'Language Controller --> Destroy');

            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function setLanguage($languageCode)
    {
        $language = Language::where('code', $languageCode)->where('status', true)->first();

        Session::put('locale', $language->code);
        Session::put('language', $language);
        app()->setLocale($language->code);

        return redirect()->back();
    }

    // public function setDefaultLanguage(Request $request)
    // {
    //     ResponseService::noPermissionThenSendJson('settings-update');

    //     $request->validate([
    //         'default_language' => 'required|exists:languages,code',
    //     ]);

    //     // Save default globally
    //     Setting::updateOrCreate(
    //         ['name' => 'default_language'],
    //         ['value' => $request->default_language, 'type' => 'string']
    //     );
    //     $language = Language::where('code', $request->default_language)->firstOrFail();
    //     Cache::forget('global_default_language');
    //     // Update current session too
    //     Session::put('locale', $request->default_language);
    //     Session::put('language', $language);
    //     Session::put('Default_langauge', $request->default_language);
    //     app()->setLocale($request->default_language);

    //     return redirect()->back()
    //         ->with('success', __('Default language updated successfully.'));
    // }
    public function setDefaultLanguage(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        $request->validate([
            'default_language' => 'required|exists:languages,code',
        ]);

        // 1. Save globally in the database
        Setting::updateOrCreate(
            ['name' => 'default_language'],
            ['value' => $request->default_language, 'type' => 'string']
        );

        // 2. CRITICAL: Clear the Middleware cache
        Cache::forget('global_default_language');

        // 3. Update current user's session so the UI reflects the change immediately
        $language = Language::where('code', $request->default_language)->firstOrFail();
        
        Session::put('locale', $request->default_language);
        Session::put('language', $language);
        
        // Clear the CachingService cache if it has one (e.g., system_settings)
        // Cache::forget('system_settings'); 

        app()->setLocale($request->default_language);
        HelperService::revalidateLanguageCodes();

        return redirect()->back()
            ->with('success', __('Default language updated successfully.'));
    }




    public function editlanguage(Request $request, $id, $type)
    {
        $language = Language::findOrFail($id);
        $languageCode = $language->code ?? 'en';
        //        $name = $type;

        if ($type == 'panel') {
            $fileName = $language->panel_file ?: "{$languageCode}.json";
            $defaultFile = base_path('resources/lang/en.json');
        } elseif ($type == 'web') {
            $fileName = $language->web_file ?: "{$languageCode}_web.json";
            $defaultFile = base_path('resources/lang/en_web.json');
        } elseif ($type == 'app') {
            $fileName = $language->app_file ?: "{$languageCode}_app.json";
            $defaultFile = base_path('resources/lang/en_app.json');
        } else {
            $fileName = 'en.json';
            $defaultFile = base_path('resources/lang/en.json');
        }

        $jsonFile = base_path("resources/lang/{$fileName}");

        if (! File::exists($jsonFile)) {
            if (File::exists($defaultFile)) {
                $defaultContent = File::get($defaultFile);
            } else {
                $defaultContent = json_encode([]);
            }

            File::put($jsonFile, $defaultContent);

            if ($type == 'panel') {
                $language->panel_file = $fileName;
            } elseif ($type == 'web') {
                $language->web_file = $fileName;
            } elseif ($type == 'app') {
                $language->app_file = $fileName;
            }
            $language->save();
        }

        $jsonContent = File::get($jsonFile);

        $enContent = File::exists($defaultFile) ? json_decode(File::get($defaultFile), true) : [];
        $targetContent = File::exists($jsonFile) ? json_decode(File::get($jsonFile), true) : [];

        foreach ($enContent as $key => $value) {
            if (! array_key_exists($key, $targetContent)) {
                $targetContent[$key] = $value;
            }
        }
        File::put($jsonFile, json_encode($targetContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $enLabels = json_decode($jsonContent, true);

        return view('settings.languageedit', compact('enLabels', 'language', 'type'));
    }

    public function updatelanguage(Request $request, $id, $type)
    {
        $language = Language::findOrFail($id);

        if ($type == 'panel') {
            $jsonFile = base_path('resources/lang/' . $language->panel_file);
        } elseif ($type == 'web') {
            $jsonFile = base_path('resources/lang/' . $language->web_file);
        } elseif ($type == 'app') {
            $jsonFile = base_path('resources/lang/' . $language->app_file);
        } else {
            $jsonFile = base_path('resources/lang/en.json');
        }

        $directory = dirname($jsonFile);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! File::exists($jsonFile)) {
            $defaultContent = [];
            File::put($jsonFile, json_encode($defaultContent, JSON_PRETTY_PRINT));
        }
        $jsonContent = File::get($jsonFile);
        $enLabels = json_decode($jsonContent, true);

        $updatedLabels = $request->input('values');
        $keys = array_keys($enLabels);
        foreach ($keys as $index => $key) {
            if (isset($updatedLabels[$index])) {
                $enLabels[$key] = $updatedLabels[$index];
            }
        }
        File::put($jsonFile, json_encode($enLabels, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        HelperService::revalidateLanguageCodes();

        ResponseService::successResponse('Json File updated successfully');
    }

    public function downloadJson($id, $type)
    {
        try {
            $language = Language::findOrFail($id);
            $languageCode = $language->code ?? 'en';
            if ($type == 'panel') {
                $fileName = $language->panel_file ?: "{$languageCode}.json";
                $defaultFile = base_path('resources/lang/en.json');
            } elseif ($type == 'web') {
                $fileName = $language->web_file ?: "{$languageCode}_web.json";
                $defaultFile = base_path('resources/lang/en_web.json');
            } elseif ($type == 'app') {
                $fileName = $language->app_file ?: "{$languageCode}_app.json";
                $defaultFile = base_path('resources/lang/en_app.json');
            } else {
                abort(404);
            }

            $jsonFile = base_path("resources/lang/{$fileName}");
            if (!File::exists(dirname($jsonFile))) {
                File::makeDirectory(dirname($jsonFile), 0755, true);
            }
            $defaultContent = File::exists($defaultFile)
                ? json_decode(File::get($defaultFile), true)
                : [];

            if (!is_array($defaultContent)) {
                $defaultContent = [];
            }
            $targetContent = File::exists($jsonFile)
                ? json_decode(File::get($jsonFile), true)
                : [];

            if (!is_array($targetContent)) {
                $targetContent = [];
            }
            foreach ($defaultContent as $key => $value) {
                if (!array_key_exists($key, $targetContent)) {
                    $targetContent[$key] = $value;
                }
            }
            file_put_contents(
                $jsonFile,
                json_encode($targetContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
            return response()->download(
                $jsonFile,
                $fileName,
                ['Content-Type' => 'application/json; charset=utf-8']
            );

        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Language Controller --> downloadJson');
            abort(500, 'Failed to download language file.');
        }
    }
}
