<?php

namespace App\Http\Controllers;
use App\Models\SeoSetting;
use App\Services\BootstrapTableService;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SeoSettingController extends Controller
{
    private string $uploadFolder;

    public function __construct() {
        $this->uploadFolder = "seo-setting";
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'page'          => 'required|unique:seo_settings,page',
                'title.1'       => 'required|string',
                'description.1' => 'required|string',
                'keywords.1'    => 'nullable|string',
                'schema.1'      => 'nullable|string',
                'image'         => 'nullable|mimes:jpeg,png,jpg,svg|max:7168',
                'languages'     => 'required|array',
                'languages.*'   => 'exists:languages,id',
            ],
            [
                'page.unique'              => 'This page already has SEO settings.',
                'title.1.required'         => 'The English title field is required.',
                'description.1.required'   => 'The English description field is required.',
            ]
        );

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $data = $request->all();

            // Handle image upload
            if ($request->hasFile('image')) {
                $data['image'] = FileService::upload($request->file('image'), $this->uploadFolder);
            }

            // Store main SEO setting (language_id = 1)
            $seoSetting = SeoSetting::create([
                'page'        => $data['page'],
                'title'       => $data['title'][1],
                'description' => $data['description'][1],
                'keywords'    => $data['keywords'][1] ?? null,
                'schema'      => $data['schema'][1] ?? null,
                'image'       => $data['image'] ?? null,
            ]);

            // Store translations for other languages
            $translationData = [];
            foreach ($data['languages'] as $langId) {
                if ($langId == 1) continue; // Skip default language

                $title = $data['title'][$langId] ?? null;
                $description = $data['description'][$langId] ?? null;
                $keywords = $data['keywords'][$langId] ?? null;
                $schema = $data['schema'][$langId] ?? null;
                // Validate JSON schema if provided
                if (!empty($schema)) {
                    try {
                        $parsed = json_decode($schema, true);
                        if (!isset($parsed['@context']) || !isset($parsed['@type'])) {
                            throw new Exception('Invalid schema structure');
                        }
                    } catch (Throwable $e) {
                        return ResponseService::validationError('Invalid JSON schema for language ' . $langId);
                    }
                }
                // Skip empty translations
                if (empty($title) && empty($description) && empty($keywords) && empty($schema)) {
                    continue;
                }
                $translationData[] = [
                    'translatable_id'   => $seoSetting->id,
                    'translatable_type' => SeoSetting::class,
                    'key'               => 'title',
                    'value'             => $title ?? '',
                    'language_id'       => $langId,
                ];
                $translationData[] = [
                    'translatable_id'   => $seoSetting->id,
                    'translatable_type' => SeoSetting::class,
                    'key'               => 'description',
                    'value'             => $description ?? '',
                    'language_id'       => $langId,
                ];
                $translationData[] = [
                    'translatable_id'   => $seoSetting->id,
                    'translatable_type' => SeoSetting::class,
                    'key'               => 'keywords',
                    'value'             => $keywords ?? '',
                    'language_id'       => $langId,
                ];
                $translationData[] = [
                    'translatable_id'   => $seoSetting->id,
                    'translatable_type' => SeoSetting::class,
                    'key'               => 'schema',
                    'value'             => $schema ?? '',
                    'language_id'       => $langId,
                ];
            }
            if (!empty($translationData)) {
                HelperService::storeTranslations($translationData);
            }

            return ResponseService::successResponse('SEO Setting Successfully Added');

        } catch (Throwable $th) {
            ResponseService::logErrorRedirect($th, "SeoSetting Controller -> Store");
            return ResponseService::errorResponse('Something Went Wrong');
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';

        $sql = SeoSetting::with('translations')->orderBy($sort, $order);

        if (!empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")->orwhere('code', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%");
        }
        $total = $sql->count();
        $sql->skip($offset)->take($limit);
        $result = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        foreach ($result as $key => $row) {
            $tempRow = $row->toArray();
            $operate = '';
            if ($row->code != "en") {
                $operate .= BootstrapTableService::editButton(route('seo-setting.update', $row->id), true);
                $operate .= BootstrapTableService::deleteButton(route('seo-setting.destroy', $row->id));
            }
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
        public function update(Request $request, $id)
        {
            $validator = Validator::make(
                $request->all(),
                [
                   
                    'title.1'       => 'required|string',
                    'description.1' => 'required|string',
                    'image'         => 'nullable|mimes:jpeg,png,jpg,svg|max:7168',
                ],
                [
                  
                    'title.1.required'         => 'The English title field is required.',
                    'description.1.required'   => 'The English description field is required.',
                ]
            );

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            try {
                $seo = SeoSetting::findOrFail($id);

                $data = $request->only('page');
                if ($request->hasFile('image')) {
                    $data['image'] = FileService::upload($request->file('image'), $this->uploadFolder);
                }

                // Save base (main) SEO setting
                $seo->update($data);

                // Update translation for each language
                $translationData = [];
                foreach ($request->input('languages', []) as $langId) {
                    $translatedTitle = $request->input("title.$langId");
                    $translatedDescription = $request->input("description.$langId");
                    $translatedKeywords = $request->input("keywords.$langId");

                    $translatedSchema = $request->input("schema.$langId");

                    // Validate JSON schema if provided
                    if (!empty($translatedSchema)) {
                        try {
                            $parsed = json_decode($translatedSchema, true);
                            if (!isset($parsed['@context']) || !isset($parsed['@type'])) {
                                throw new Exception('Invalid schema structure');
                            }
                        } catch (Throwable $e) {
                            return ResponseService::validationError('Invalid JSON schema for language ' . $langId);
                        }
                    }

                    if ($langId == 1) {
                        // English (default)
                        $seo->update([
                            'title'       => $translatedTitle,
                            'description' => $translatedDescription,
                            'keywords'    => $translatedKeywords,
                            'schema'      => $translatedSchema,
                        ]);
                    } else {
                        $translationData[] = [
                            'translatable_id'   => $seo->id,
                            'translatable_type' => SeoSetting::class,
                            'key'               => 'title',
                            'value'             => $translatedTitle ?? '',
                            'language_id'       => $langId,
                        ];
                        $translationData[] = [
                            'translatable_id'   => $seo->id,
                            'translatable_type' => SeoSetting::class,
                            'key'               => 'description',
                            'value'             => $translatedDescription ?? '',
                            'language_id'       => $langId,
                        ];
                        $translationData[] = [
                            'translatable_id'   => $seo->id,
                            'translatable_type' => SeoSetting::class,
                            'key'               => 'keywords',
                            'value'             => $translatedKeywords ?? '',
                            'language_id'       => $langId,
                        ];
                        $translationData[] = [
                            'translatable_id'   => $seo->id,
                            'translatable_type' => SeoSetting::class,
                            'key'               => 'schema',
                            'value'             => $translatedSchema ?? '',
                            'language_id'       => $langId,
                        ];
                    }
                }
                if (!empty($translationData)) {
                    HelperService::storeTranslations($translationData);
                }

                return ResponseService::successResponse('SEO Setting Updated Successfully');
            } catch (Throwable $th) {
                return ResponseService::logErrorRedirect($th, "SeoSetting Controller -> Update");
                return ResponseService::errorResponse('Something Went Wrong');
            }
        }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
                $seo_setting = SeoSetting::findOrFail($id);
                $seo_setting->delete();
                FileService::delete($seo_setting->getRawOriginal('image'));
                ResponseService::successResponse('Seo Setting Deleted successfully');
            } catch (Throwable $th) {
                ResponseService::logErrorRedirect($th, "Language Controller --> Destroy");
                ResponseService::errorResponse('Something Went Wrong');
            }
    }
}
