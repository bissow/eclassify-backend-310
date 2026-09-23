<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\City;
use App\Models\ContactUs;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Faq;
use App\Models\HomeScreenSection;
use App\Models\Item;
use App\Models\Language;
use App\Models\ReportReason;
use App\Models\SeoSetting;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\State;
use App\Models\Tip;
use App\Models\User;
use App\Models\UserReports;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Throwable;

/** @tags Settings */
class SettingsApiController extends BaseApiController
{
    /** Get System Settings */
    public function getSystemSettings(Request $request)
    {
        try {
            $query = Setting::select(['id', 'name', 'value', 'type']);

            if (! empty($request->type)) {
                $query->where('name', $request->type);
            }else{
                $query->whereIn('name', ['demo_mode','android_version','ios_version','default_language','current_language','force_update','android_maintenance_mode','ios_maintenance_mode','web_maintenance_mode','free_ad_listing','otp_service_provider','map_provider','google_map_key','currency_symbol','currency_iso_code','currency_symbol_position','banner_ad_status','banner_ad_id_android','banner_ad_id_ios','interstitial_ad_status','interstitial_ad_id_android','interstitial_ad_id_ios','native_ad_status','native_app_id_android','native_app_id_ios','play_store_link','app_store_link','default_latitude','default_longitude','min_length','max_length', 'email_authentication','mobile_authentication','google_authentication','apple_authentication','languages','about_us','adsense_banner_slot_id','adsense_client_id','adsense_enabled','adsense_mode','adsense_square_slot_id','adsense_vertical_slot_id','company_address','company_email','company_logo','company_name','company_tel1','company_tel2','contact_us','deep_link_scheme','facebook_link','favicon_icon','footer_description','footer_logo','google_map_iframe_link','header_logo','instagram_link','linkedin_link','pinterest_link','placeholder_image','privacy_policy','refund_policy','show_landing_page','terms_conditions','x_link', 'web_theme_color', 'web_setup', 'web_url', 'refer_earn_enabled','refer_points_for_referrer','refer_points_for_referred','refer_max_points_usage_percentage','refer_min_points_to_use','refer_max_points_to_use','gemini_ai_enabled', 'feature_item_sorting', 'general_item_sorting', 'featured_items_weightage','max_gallery_images','reel_max_file_size_mb','reel_max_duration_sec','item_video_max_file_size_mb']);
            }

            $settings = $query->with('translations')->get();

            $tempRow = [];

            foreach ($settings as $row) {
                if (in_array($row->name, [
                    'account_holder_name',
                    'bank_name',
                    'account_number',
                    'ifsc_swift_code',
                    'bank_transfer_status',
                    'place_api_key',
                    'mail_password',
                    'mail_from_address',
                    'mail_username',
                    'twilio_account_sid',
                    'twilio_auth_token',
                    'twilio_my_phone_number',
                    'S3_aws_access_key_id',
                    's3_aws_secret_access_key',
                    's3_aws_default_region',
                    's3_aws_bucket',
                    's3_aws_url',
                    'refer_max_points_usage_percentage',
                    'refer_min_points_to_use',
                    'refer_max_points_to_use',
                ])) {
                    continue;
                }
                $tempRow[$row->name] = $row->translated_value ?? $row->value;
            }

            $languageCode = $request->header('Content-Language') ?? app()->getLocale();
            $language = Language::where('code', $languageCode)->first();

            if (! $language) {
                $defaultLanguageCode = Setting::where('name', 'default_language')->value('value');
                $language = Language::where('code', $defaultLanguageCode)->first();
            }

            $tempRow['demo_mode'] = config('app.demo_mode');
            $tempRow['languages'] = CachingService::getLanguages()->where('status', true)->values();
            $tempRow['admin'] = User::role('Super Admin')->select(['name', 'profile'])->first();

            $tempRow['current_language'] = $language?->code ?? app()->getLocale();

            $sections = HomeScreenSection::active()
                ->orderBy('sequence')
                ->get(['id', 'section_type', 'sequence']);
            $tempRow['home_screen_sections'] = $sections;

            ResponseService::successResponse(__('Data Fetched Successfully'), $tempRow);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getSystemSettings');
            ResponseService::errorResponse();
        }
    }

    /** Get SEO Settings */
    public function seoSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page' => 'nullable',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $settings = new SeoSetting;
            if (! empty($request->page)) {
                $settings = $settings->where('page', $request->page);
            }

            $settings = $settings->get();
            ResponseService::successResponse(__('SEO settings fetched successfully.'), $settings);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> seoSettings');
            ResponseService::errorResponse();
        }
    }

    /** Get Currencies */
    public function getCurrencies(Request $request)
    {
        try {
            $countryName = $request->query('country');

            $selectedCurrencyId = null;

            if ($countryName) {
                $country = Country::where('name', $countryName)
                    ->select('id', 'currency_id')
                    ->first();

                $selectedCurrencyId = $country?->id;
            }

            $currencies = Currency::select('id', 'iso_code', 'symbol', 'symbol_position', 'country_id', 'decimal_places', 'thousand_separator', 'decimal_separator')
                ->orderBy('iso_code')
                ->get()
                ->map(function ($currency) use ($selectedCurrencyId) {
                    return [
                        'id' => $currency->id,
                        'iso_code' => $currency->iso_code,
                        'symbol' => $currency->symbol,
                        'position' => $currency->symbol_position,
                        'selected' => $selectedCurrencyId == $currency->country_id ? 1 : 0,
                        'decimal_places' => $currency->decimal_places,
                        'thousand_separator' => $currency->thousand_separator,
                        'decimal_separator' => $currency->decimal_separator,
                    ];
                });
            ResponseService::successResponse(__('Currency fetched Successfully'), $currencies);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getCurrencies');
            ResponseService::errorResponse();
        }
    }

    /** Get Languages */
    public function getLanguages(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'language_code' => 'required',
                'type' => 'nullable|in:app,web',
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            $language = Language::where(['code' => $request->language_code, 'status' => true])->first();
            if(collect($language)->isEmpty()){
                ResponseService::errorResponse(__('Language not found'), []);
            }

            $type = $request->type ?? 'app';
            $languageCode = $request->language_code;

            if ($type === 'web') {
                $json_file_path = base_path("resources/lang/{$language->web_file}");
                $default_file_path = base_path('resources/lang/en_web.json');
            } else {
                $json_file_path = base_path("resources/lang/{$language->app_file}");
                $default_file_path = base_path('resources/lang/en_app.json');
            }

            if (! is_file($json_file_path)) {
                if (is_file($default_file_path)) {
                    $json_file_path = $default_file_path;
                } else {
                    ResponseService::errorResponse(__('Default language file not found'));
                }
            }

            $json_string = file_get_contents($json_file_path);

            try {
                $json_data = json_decode($json_string, false, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                ResponseService::errorResponse(__('Invalid JSON format in the language file'));
            }

            $language->file_name = $json_data;

            ResponseService::successResponse(__('Data Fetched Successfully'), $language);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getLanguages');
            ResponseService::errorResponse();
        }
    }

    /** Get System Languages Codes */
    public function getSystemLanguagesCodes()
    {
        try {
            $defaultLanguage = CachingService::getSystemSettings('default_language');
            $languagesQuery = Language::query();
            $defaultLanguageData = $languagesQuery->clone()->where('code',$defaultLanguage)->select('id','code')->first();
            $otherLanguageData = $languagesQuery->clone()->where('id','!=',$defaultLanguageData->id)->where('status', true)->select('id', 'code')->get();
            $languageData = array(
                'default_language_code' => $defaultLanguageData->code,
                'language_codes' => $otherLanguageData->pluck('code')->toArray(),
            );

            ResponseService::successResponse(__('Languages Codes Fetched Successfully'), $languageData);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getSystemLanguagesCodes');
            ResponseService::errorResponse();
        }
    }

    /** Get Slider */
    public function getSlider(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'nullable|string',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {

            $countryId = $request->country
                ? Country::where('name', $request->country)->value('id')
                : null;

            $stateId = $request->state
                ? State::where('name', $request->state)->value('id')
                : null;

            $cityId = $request->city
                ? City::where('name', $request->city)->value('id')
                : null;

            $rows = Slider::with([
                'model' => function (MorphTo $morphTo) {
                    $morphTo->constrain([
                        Category::class => fn($q) => $q->withCount('subcategories'),
                    ]);
                },
            ])->where(function ($query) {
                $query->whereNull('model_type')
                    ->orWhereHasMorph(
                        'model',
                        [Category::class, Item::class],
                        fn($q) => $q->whereNotNull('id')
                    );
            })
                ->where(function ($q) use ($countryId, $stateId, $cityId) {
                    if ($countryId || $stateId || $cityId) {
                        $q->where(function ($sub) use ($countryId, $stateId, $cityId) {
                            if ($countryId) {
                                $sub->orWhere('country_id', $countryId);
                            }
                            if ($stateId) {
                                $sub->orWhere('state_id', $stateId);
                            }
                            if ($cityId) {
                                $sub->orWhere('city_id', $cityId);
                            }
                        })
                            ->orWhere(function ($global) {
                                $global->whereNull('country_id')
                                    ->whereNull('state_id')
                                    ->whereNull('city_id');
                            });
                    } else {
                        $q->whereNull('country_id')
                            ->whereNull('state_id')
                            ->whereNull('city_id');
                    }
                })->get();

            ResponseService::successResponse(null, $rows);
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getSlider');
            ResponseService::errorResponse();
        }
    }

    /** Get Report Reasons */
    public function getReportReasons(Request $request)
    {
        try {
            $report_reason = new ReportReason;
            if (! empty($request->id)) {
                $id = $request->id;
                $report_reason->where('id', '=', $id);
            }
            $result = $report_reason->paginate();
            $total = $report_reason->count();
            ResponseService::successResponse(__('Data Fetched Successfully'), $result, ['total' => $total]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getReportReasons');
            ResponseService::errorResponse();
        }
    }

    /** Get FAQs */
    public function getFaqs()
    {
        try {
            $faqs = Faq::with('translations')->get();
            ResponseService::successResponse(__('FAQ Data fetched Successfully'), $faqs);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getFaqs');
            ResponseService::errorResponse(__('Failed to fetch Faqs'));
        }
    }

    /** Get Tips */
    public function getTips()
    {
        try {
            $tips = Tip::select(['id', 'description'])->orderBy('sequence', 'ASC')->with('translations')->get();
            ResponseService::successResponse(__('Tips Fetched Successfully'), $tips);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> getTips');
            ResponseService::errorResponse();
        }
    }

    /** Store Contact Us */
    public function storeContactUs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'subject' => 'required',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            ContactUs::create($request->all());
            ResponseService::successResponse(__('Contact Us Stored Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> storeContactUs');
            ResponseService::errorResponse();
        }
    }

    /** Add Reports */
    public function addReports(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_id' => 'required',
                'report_reason_id' => 'required_without:other_message',
                'other_message' => 'required_without:report_reason_id',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            $user = Auth::user();
            $report_count = UserReports::where('item_id', $request->item_id)->where('user_id', $user->id)->first();
            if ($report_count) {
                ResponseService::errorResponse(__('Already Reported'));
            }
            UserReports::create([
                ...$request->all(),
                'user_id' => $user->id,
                'other_message' => $request->other_message ?? '',
            ]);
            ResponseService::successResponse(__('Report Submitted Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> addReports');
            ResponseService::errorResponse();
        }
    }
}
