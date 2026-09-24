<?php

namespace App\Http\Controllers;

use File;
use Throwable;
use App\Models\Setting;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Services\FileService;
use App\Services\HelperService;
use App\Services\DummyDataService;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentConfiguration;
use App\Models\PluginLicense;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    private string $uploadFolder;

    public function __construct()
    {
        $this->uploadFolder = 'settings';
    }

    public function index()
    {
        ResponseService::noPermissionThenRedirect('settings-update');

        return view('settings.index');
    }

    public function page()
    {
        ResponseService::noPermissionThenSendJson('settings-update');
        $type = last(request()->segments());
        $settings = CachingService::getSystemSettings()->toArray();
        if (! empty($settings['place_api_key']) && config('app.demo_mode')) {
            $settings['place_api_key'] = '**************************';
        }
        if (! empty($settings['google_map_key']) && config('app.demo_mode')) {
            $settings['google_map_key'] = '**************************';
        }
        $stripe_currencies = ['USD', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JMD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLE', 'SOS', 'SRD', 'STD', 'SZL', 'THB', 'TJS', 'TOP', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'UYU', 'UZS', 'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW'];
        $languages = CachingService::getLanguages();
        $translations = $this->getSettingTranslations();

        $languages_translate = CachingService::getLanguages()->where('code', '!=', 'en')->values();

        $currencies = Currency::select(['id', 'iso_code'])->get();

        // Prepare watermark settings for watermark-settings page
        $watermarkSettings = [];
        if ($type === 'watermark-settings') {
            // Get watermark image URL (Setting model already transforms file paths to URLs)
            $watermarkImageUrl = $settings['watermark_image'] ?? null;
            // Extract filename for display if needed
            $watermarkImageFilename = null;
            if ($watermarkImageUrl) {
                // Extract filename from URL or path
                $watermarkImageFilename = basename(parse_url($watermarkImageUrl, PHP_URL_PATH));
            }

            $watermarkSettings = [
                'enabled' => $settings['watermark_enabled'] ?? 0,
                'watermark_image' => $watermarkImageFilename,
                'watermark_image_url' => $watermarkImageUrl,
                'opacity' => $settings['watermark_opacity'] ?? 25,
                'size' => $settings['watermark_size'] ?? 10,
                'style' => $settings['watermark_style'] ?? 'tile',
                'position' => $settings['watermark_position'] ?? 'center',
                'rotation' => $settings['watermark_rotation'] ?? -30,
            ];
        }

        $notificationSettings = [];
        if($type == 'notification-setting'){
            // Get raw file path (bypass Setting model accessor that converts to full URL)
            $rawServiceFile = Setting::where('name', 'service_file')->value('value');
            $notificationSettings = [
                'fcm_service_file_exists' => !empty($rawServiceFile) && FileService::fileExists($rawServiceFile) ? 1 : 0,
            ];
        }

        return view('settings.' . $type, compact('settings', 'type', 'languages', 'stripe_currencies', 'languages_translate', 'translations', 'watermarkSettings', 'currencies', 'notificationSettings'));
    }

    private function getSettingTranslations()
    {
        $settings = Setting::with('translations')->get();

        $translations = [];

        foreach ($settings as $setting) {
            $grouped = $setting->translations->groupBy('language_id');
            foreach ($grouped as $langId => $items) {
                $trans = $items->where('key', 'translated_value')->first();
                if ($trans) {
                    $translations[$setting->name][$langId] = $trans->value;
                }
            }
        }

        return $translations;
    }

    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');
        $validator = Validator::make($request->all(), [
            'company_name' => 'nullable',
            'company_email' => 'nullable',
            'company_tel1' => 'nullable',
            'company_tel2' => 'nullable',
            'company_address' => 'nullable',
            'default_language' => 'nullable',
            'currency_symbol' => 'nullable',
            'android_version' => 'nullable',
            'play_store_link' => 'nullable',
            'ios_version' => 'nullable',
            'app_store_link' => 'nullable',
            'android_maintenance_mode' => 'nullable',
            'ios_maintenance_mode' => 'nullable',
            'web_maintenance_mode' => 'nullable',
            'force_update' => 'nullable',
            'number_with_suffix' => 'nullable',
            'firebase_project_id' => 'nullable',
            'service_file' => 'nullable',
            'favicon_icon' => 'nullable|mimes:jpg,jpeg,png,svg|max:7168',
            'company_logo' => 'nullable|mimes:jpg,jpeg,png,svg|max:7168',
            'login_image' => 'nullable|mimes:jpg,jpeg,png,svg|max:7168',
            // "watermark_image"        => 'nullable|mimes:jpg,jpeg,png|max:7168',
            'web_theme_color' => 'nullable',
            'web_setup' => 'nullable',
            'web_url' => 'nullable|url',
            'place_api_key' => 'nullable',
            'google_map_key' => 'nullable',
            'header_logo' => 'nullable|mimes:jpg,jpeg,png,svg|max:7168',
            'footer_logo' => 'nullable|mimes:jpg,jpeg,png,svg|max:7168',
            'placeholder_image' => 'nullable|mimes:jpg,jpeg,png,svg|max:7168',
            'footer_description' => 'nullable',
            'google_map_iframe_link' => 'nullable',
            'default_latitude' => 'nullable',
            'default_longitude' => 'nullable',
            'instagram_link' => 'nullable|url',
            'x_link' => 'nullable|url',
            'facebook_link' => 'nullable|url',
            'linkedin_link' => 'nullable|url',
            'pinterest_link' => 'nullable|url',
            'deep_link_text_file' => 'nullable',
            'deep_link_json_file' => 'nullable|mimes:json|max:7168',
            'mobile_authentication' => 'nullable',
            'google_authentication' => 'nullable',
            'email_authentication' => 'nullable',
            'apple_authenticaion' => 'nullable',
            // Email settings validation
            'mail_mailer' => 'nullable',
            'mail_host' => 'nullable',
            'mail_port' => 'nullable',
            'mail_username' => 'nullable',
            'mail_password' => 'nullable',
            'mail_encryption' => 'nullable',
            'mail_from_address' => 'nullable|email',
            'deep_link_scheme' => 'nullable|string|regex:/^[a-z][a-z0-9]*$/|max:30',
            'otp_service_provider' => 'nullable|in:firebase,twilio,2factor,test,test_otp,default',
            'test_otp_code' => 'nullable|string|digits:6',
            'twilio_account_sid' => 'nullable',
            'twilio_auth_token' => 'nullable',
            'twilio_my_phone_number' => 'nullable',
            'twofactor_api_key' => 'nullable',
            'twofactor_sender_id' => 'nullable',
            'twofactor_template_id' => 'nullable',
            'currency_iso_code' => 'nullable|string|regex:/^[a-zA-Z]+$/',
            'max_gallery_images' => 'nullable|integer|min:1|max:50',
            'item_video_max_file_size_mb'  => 'nullable|integer|min:1|max:500',
            'free_ad_unlimited'     => 'sometimes|nullable|boolean',
            'free_ad_duration_days' => 'sometimes|nullable|integer|min:1|required_if:free_ad_unlimited,0',
            'admin_primary_color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            // AdSense Settings
            'adsense_enabled'         => 'nullable|in:0,1',
            'adsense_mode'            => 'nullable|required_if:adsense_enabled,1|in:automatic,manual',
            'adsense_client_id'       => 'nullable|required_if:adsense_enabled,1|string',
            'adsense_banner_slot_id'  => 'nullable|required_if:adsense_mode,manual|string',
            'adsense_vertical_slot_id' => 'nullable|required_if:adsense_mode,manual|string',
            'adsense_square_slot_id'  => 'nullable|required_if:adsense_mode,manual|string',
            'feature_image_resizing' => 'nullable|in:0,1',
        ], [
            'currency_iso_code.regex' => trans('Only characters are allowed for the currency ISO code.'),
        ]);
        if (
            $request->has('mobile_authentication') && $request->mobile_authentication == 0 &&
            $request->has('google_authentication') && $request->google_authentication == 0 &&
            $request->has('email_authentication') && $request->email_authentication == 0 &&
            $request->has('apple_authentication') && $request->apple_authentication == 0
        ) {
            ResponseService::validationError('At least one authentication method must be enabled.');
        }
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {

            $inputs = $request->input();

            unset($inputs['_token']);
            if (config('app.demo_mode')) {
                unset($inputs['place_api_key']);
                unset($inputs['google_map_key']);
            }
            $data = [];
            foreach ($inputs as $key => $input) {
                if (in_array($key, ['translations', 'about_us', 'languages', 'contact_us', 'privacy_policy', 'refund_policy', 'terms_conditions', 'refer_earn_enabled'])) {
                    continue;
                }
                $data[] = [
                    'name' => $key,
                    'value' => $input,
                    'type' => 'string',
                ];
            }

            $oldSettingFiles = Setting::whereIn('name', collect($request->files)->keys())->get();
            foreach ($request->files as $key => $file) {

                if (in_array($key, ['deep_link_json_file', 'deep_link_text_file'])) {
                    $filenameMap = [
                        'deep_link_json_file' => 'assetlinks.json',
                        'deep_link_text_file' => 'apple-app-site-association',
                    ];

                    $filename = $filenameMap[$key];
                    $fileContents = File::get($file);
                    $publicWellKnownPath = public_path('.well-known');
                    if (! File::exists($publicWellKnownPath)) {
                        File::makeDirectory($publicWellKnownPath, 0755, true);
                    }

                    $publicPath = public_path('.well-known/' . $filename);
                    File::put($publicPath, $fileContents);

                    $rootPath = base_path('.well-known/' . $filename);
                    File::put($rootPath, $fileContents);
                } else {

                    $data[] = [
                        'name' => $key,
                        'value' => FileService::compressAndUpload($request->file($key), $this->uploadFolder),
                        // 'value' => $request->file($key)->store($this->uploadFolder, 'public'),
                        'type' => 'file',
                    ];
                    $oldFile = $oldSettingFiles->first(function ($old) use ($key) {
                        return $old->name == $key;
                    });
                    if (! empty($oldFile)) {
                        FileService::delete($oldFile->getRawOriginal('value'));
                    }
                }
            }
            if (($inputs['free_ad_duration_days'] ?? null) != 'free_ad_duration_days') {
                $data[] = [
                    'name'  => 'free_ad_duration_days',
                    'value' => $inputs['free_ad_duration_days'] ?? 'unlimited',
                    'type'  => 'string',
                ];
            } else {
                // Unlimited
                $data[] = [
                    'name'  => 'free_ad_duration_days',
                    'value' => "unlimited",
                    'type'  => 'string',
                ];
            }


            /** Make Refer Points disabled */
            $data[] = [
                'name'  => 'refer_earn_enabled',
                'value' => "0",
                'type'  => 'string',
            ];
            Setting::upsert($data, 'name', ['value']);

            if (! empty($inputs['company_name']) && config('app.name') != $inputs['company_name']) {
                HelperService::changeEnv([
                    'APP_NAME' => $inputs['company_name'],
                ]);
            }

            // Update .env file for email settings only if mail settings are provided in the request
            $emailKeys = ['mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address'];
            $hasEmailInputs = false;
            foreach ($emailKeys as $k) {
                if (array_key_exists($k, $inputs)) {
                    $hasEmailInputs = true;
                    break;
                }
            }

            if ($hasEmailInputs) {
                $emailSettings = [
                    'MAIL_MAILER' => $inputs['mail_mailer'] ?? config('mail.mailer'),
                    'MAIL_HOST' => $inputs['mail_host'] ?? config('mail.host'),
                    'MAIL_PORT' => $inputs['mail_port'] ?? config('mail.port'),
                    'MAIL_USERNAME' => $inputs['mail_username'] ?? config('mail.username'),
                    'MAIL_PASSWORD' => $inputs['mail_password'] ?? config('mail.password'),
                    'MAIL_ENCRYPTION' => $inputs['mail_encryption'] ?? config('mail.encryption'),
                    'MAIL_FROM_ADDRESS' => $inputs['mail_from_address'] ?? config('mail.from.address'),
                ];
                $filteredSettings = array_filter($emailSettings, function ($value) {
                    return ! is_null($value) && $value !== '';
                });

                // Only update env if there's something to update
                if (! empty($filteredSettings)) {
                    HelperService::changeEnv($filteredSettings);
                }
            }

            if (! empty($inputs['otp_service_provider']) && $inputs['otp_service_provider'] === 'twilio') {
                HelperService::changeEnv([
                    'TWILIO_ACCOUNT_SID' => $inputs['twilio_account_sid'] ?? config('services.twilio.account_sid'),
                    'TWILIO_AUTH_TOKEN' => $inputs['twilio_auth_token'] ?? config('services.twilio.auth_token'),
                ]);
            }

            $translationData = [];

            // Handle translatable setting fields
            $translatableFields = ['about_us', 'contact_us', 'privacy_policy', 'refund_policy', 'terms_conditions'];
            foreach ($translatableFields as $fieldName) {
                if ($request->has($fieldName)) {
                    $fieldInputs = $request->input($fieldName, []);

                    // Save default value (first language or fallback)
                    $defaultValue = reset($fieldInputs);
                    Setting::updateOrCreate(
                        ['name' => $fieldName],
                        ['value' => $defaultValue, 'type' => 'string']
                    );

                    // Collect translations
                    $setting = Setting::where('name', $fieldName)->first();
                    if ($setting) {
                        foreach ($fieldInputs as $languageId => $value) {
                            if (!empty($value)) {
                                $translationData[] = [
                                    'translatable_id'   => $setting->id,
                                    'translatable_type' => get_class($setting),
                                    'key'               => 'translated_value',
                                    'value'             => $value,
                                    'language_id'       => $languageId,
                                ];
                            }
                        }
                    }
                }
            }

            if ($request->has('translations')) {
                foreach ($request->input('translations') as $languageId => $transData) {
                    $setting = Setting::where('name', $transData['name'])->first();

                    if ($setting && !empty($transData['value'])) {
                        $translationData[] = [
                            'translatable_id'   => $setting->id,
                            'translatable_type' => get_class($setting),
                            'key'               => 'translated_value',
                            'value'             => $transData['value'],
                            'language_id'       => $languageId,
                        ];
                    }
                }
            }

            if (!empty($translationData)) {
                HelperService::storeTranslations($translationData);
            }
            CachingService::removeCache(config('constants.CACHE.SETTINGS'));
            CachingService::clearSettingsCache();
            foreach (array_keys($inputs) as $settingKey) {
                CachingService::clearSettingsCache($settingKey);
            }
            ResponseService::successResponse('Settings Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Setting Controller -> store');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function updateFirebaseSettings(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');
        $validator = Validator::make($request->all(), [
            'apiKey' => 'required',
            'authDomain' => 'required',
            'projectId' => 'required',
            'storageBucket' => 'required',
            'messagingSenderId' => 'required',
            'appId' => 'required',
            'measurementId' => 'nullable|string',
            'vapidKey' => 'required|string',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $inputs = $request->input();
            unset($inputs['_token']);
            $data = [];
            foreach ($inputs as $key => $input) {
                $data[] = [
                    'name' => $key,
                    'value' => $input,
                    'type' => 'string',
                ];
            }
            Setting::upsert($data, 'name', ['value']);
            // Service worker file will be copied here
            File::copy(public_path('assets/dummy-firebase-messaging-sw.js'), public_path('firebase-messaging-sw.js'));
            $serviceWorkerFile = file_get_contents(public_path('firebase-messaging-sw.js'));

            $updateFileStrings = [
                'apiKeyValue' => '"' . $request->apiKey . '"',
                'authDomainValue' => '"' . $request->authDomain . '"',
                'projectIdValue' => '"' . $request->projectId . '"',
                'storageBucketValue' => '"' . $request->storageBucket . '"',
                'messagingSenderIdValue' => '"' . $request->messagingSenderId . '"', // Fixed: use messagingSenderId, not measurementId
                'appIdValue' => '"' . $request->appId . '"',
                'measurementIdValue' => '"' . $request->measurementId . '"',
            ];
            $serviceWorkerFile = str_replace(array_keys($updateFileStrings), $updateFileStrings, $serviceWorkerFile);
            file_put_contents(public_path('firebase-messaging-sw.js'), $serviceWorkerFile);
            CachingService::removeCache(config('constants.CACHE.SETTINGS'));
            ResponseService::successResponse('Settings Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Settings Controller -> updateFirebaseSettings');
            ResponseService::errorResponse();
        }
    }

    public function paymentSettingsIndex()
    {
        ResponseService::noPermissionThenRedirect('settings-update');
        $paymentConfiguration = PaymentConfiguration::all();
        $paymentGateway = [];
        foreach ($paymentConfiguration as $row) {
            $paymentGateway[$row->payment_method] = HelperService::maskPaymentConfig($row->toArray());
        }
        $settings = CachingService::getSystemSettings()->toArray();

        // Installed type=payment plugins, rendered after the built-in
        // gateways — settings fields read live from module.json.
        $pluginGateways = PluginLicense::where('type', 'payment')
            ->where('revoked', false)
            ->get()
            ->map(fn ($license) => (object) [
                'plugin_slug' => $license->plugin_slug,
                'name' => $license->displayName(),
                'settings_fields' => $license->settingsFields(),
                'is_enabled' => $license->is_enabled,
                'webhook_url' => $license->supportsWebhook() ? url('/webhook/plugin/'.$license->plugin_slug) : null,
            ])
            ->filter(fn ($plugin) => !empty($plugin->settings_fields))
            ->values();

        return view('settings.payment-gateway', compact('paymentGateway', 'settings', 'pluginGateways'));
    }

    public function paymentSettingsStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|array',
            'gateway.Stripe' => 'required|array|required_array_keys:api_key,secret_key,webhook_secret_key,status',
            'gateway.Razorpay' => 'required|array|required_array_keys:api_key,secret_key,webhook_secret_key,status',
            'gateway.Paystack' => 'required|array|required_array_keys:api_key,secret_key,status',
            'gateway.Paytabs' => 'required|array|required_array_keys:api_key,secret_key,status,additional_data_1,additional_data_2',
            'gateway.DPO' => 'required|array|required_array_keys:secret_key,status,additional_data_1,payment_mode',
            'gateway.PhonePe' => 'required|array|required_array_keys:secret_key,api_key,additional_data_1,username,password,payment_mode,status',
            'bank' => 'required|array',
        ]);

        $gatewayStatuses = collect($request->input('gateway', []))
            ->pluck('status')
            ->push($request->input('bank.bank_transfer_status', 0))
            ->all();
        if (! in_array('1', $gatewayStatuses, true)) {
            ResponseService::validationError('At least one payment gateway must be enabled.');
        }
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        $gatewayInput = $request->input('gateway', []);
        foreach ($gatewayInput as $key => $gateway) {
            if (($gateway['status'] ?? '0') != '1') {
                continue;
            }
            $license = PluginLicense::where('plugin_slug', $key)->where('type', 'payment')->first();
            if ($license && ! $license->isUsable()) {
                // Plugin was disabled/revoked after this gateway was last saved as active;
                // force it off instead of blocking the save of unrelated gateways.
                $gatewayInput[$key]['status'] = '0';
            }
        }

        try {

            foreach ($request->input('bank') as $key => $value) {
                Setting::updateOrCreate(['name' => $key], ['value' => $value]);
            }
            foreach ($gatewayInput as $key => $gateway) {
                PaymentConfiguration::updateOrCreate(['payment_method' => $key], [
                    'api_key' => $gateway['api_key'] ?? '',
                    'secret_key' => $gateway['secret_key'] ?? '',
                    'webhook_secret_key' => $gateway['webhook_secret_key'] ?? '',
                    'merchant_id' => $gateway['merchant_id'] ?? '',
                    'status' => $gateway['status'] ?? '',
                    'currency_code' => $gateway['currency_code'] ?? '',
                    'additional_data_1' => $gateway['additional_data_1'] ?? '',
                    'additional_data_2' => $gateway['additional_data_2'] ?? '',
                    'payment_mode' => $gateway['payment_mode'] ?? '',
                    'username' => $gateway['username'] ?? '',
                    'password' => $gateway['password'] ?? '',

                ]);
                if ($key === 'Paystack') {
                    HelperService::changeEnv([
                        'PAYSTACK_PUBLIC_KEY' => $gateway['api_key'] ?? '',
                        'PAYSTACK_SECRET_KEY' => $gateway['secret_key'] ?? '',
                        'PAYSTACK_PAYMENT_URL' => 'https://api.paystack.co',
                    ]);
                }
            }
            ResponseService::successResponse('Settings Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Settings Controller -> updateFirebaseSettings');
            ResponseService::errorResponse();
        }
    }

    // public function syatemStatusIndex() {
    //     return view('settings.system-status');
    // }
    public function toggleStorageLink()
    {
        $linkPath = public_path('storage');

        if (file_exists($linkPath)) {
            if (is_link($linkPath)) {
                if (unlink($linkPath)) {
                    return back()->with('message', 'Storage link unlinked successfully!');
                }

                return back()->with('message', 'Failed to unlink the storage link.');
            }

            return back()->with('message', 'Storage link is not a symbolic link.');
        } else {
            Artisan::call('storage:link');

            if (file_exists($linkPath) && is_link($linkPath)) {
                return back()->with('message', 'Storage link created successfully!');
            }

            return back()->with('message', 'Failed to create the storage link.');
        }
    }

    public function systemStatus()
    {
        $linkPath = public_path('storage');
        $isLinked = file_exists($linkPath) && is_dir($linkPath);

        return view('settings.system-status', compact('isLinked'));
    }

    public function fileManagerSettingStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');
        $validator = Validator::make($request->all(), [
            'file_manager' => 'required|in:public,s3',
            'S3_aws_access_key_id' => 'required_if:file_manager,==,s3',
            's3_aws_secret_access_key' => 'required_if:file_manager,==,s3',
            's3_aws_default_region' => 'required_if:file_manager,==,s3',
            's3_aws_bucket' => 'required_if:file_manager,==,s3',
            's3_aws_url' => 'required_if:file_manager,==,s3',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $inputs = $request->input();
            $data = [];
            foreach ($inputs as $key => $input) {
                $data[] = [
                    'name' => $key,
                    'value' => $input,
                    'type' => 'string',
                ];
            }
            Setting::upsert($data, 'name', ['value']);

            $env = [
                'FILESYSTEM_DISK' => $inputs['file_manager'],
                'AWS_ACCESS_KEY_ID' => $inputs['S3_aws_access_key_id'] ?? null,
                'AWS_SECRET_ACCESS_KEY' => $inputs['s3_aws_secret_access_key'] ?? null,
                'AWS_DEFAULT_REGION' => $inputs['s3_aws_default_region'] ?? null,
                'AWS_BUCKET' => $inputs['s3_aws_bucket'] ?? null,
                'AWS_URL' => $inputs['s3_aws_url'] ?? null,
            ];

            HelperService::changeEnv($env);
            ResponseService::successResponse('File Manager Settings Updated Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Setting Controller -> fileManagerSettingStore');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function paystackPaymentSucesss()
    {
        return view('payment.paystack');
    }

    public function paytabsPaymentSucesssWeb(Request $request)
    {
       return view('payment.paytabs');
    }

    public function phonepePaymentSucesss()
    {
        return view('payment.phonepe');
    }

    public function webPageURL($slug)
    {
        $appStoreLink = CachingService::getSystemSettings('app_store_link');
        $playStoreLink = CachingService::getSystemSettings('play_store_link');
        $appName = CachingService::getSystemSettings('company_name');
        $scheme = CachingService::getSystemSettings('deep_link_scheme');

        return view('deep-link.deep_link', compact('appStoreLink', 'playStoreLink', 'appName', 'scheme'));
    }

    public function flutterWavePaymentSucesss()
    {
        return view('payment.flutterwave');
    }

    public function dummyDataIndex()
    {
        ResponseService::noPermissionThenRedirect('settings-update');

        $dummyCounts = (new DummyDataService())->counts();

        return view('settings.dummy-data', compact('dummyCounts'));
    }

    public function importDummyData(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        $validator = Validator::make($request->all(), [
            'items_count' => 'nullable|integer|min:0|max:500',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            ignore_user_abort(true);
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $itemsCount = (int) $request->input('items_count', 20);
            $result = (new DummyDataService())->populate($itemsCount, Auth::id());

            $message = $result['catalog_imported']
                ? __('Dummy categories & custom fields imported.')
                : __('Dummy categories & custom fields already present.');
            if ($result['items_created'] > 0) {
                $message .= ' ' . __(':count dummy advertisements created.', ['count' => $result['items_created']]);
            }

            ResponseService::successResponse($message, $result);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SettingController -> importDummyData');
            ResponseService::errorResponse(__('Something Went Wrong'));
        }
    }

    public function deleteDummyData()
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        try {
            ignore_user_abort(true);
            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            $result = (new DummyDataService())->delete();

            $message = __('Dummy data removed: :items advertisements, :categories categories, :fields custom fields.', [
                'items'      => $result['items_deleted'],
                'categories' => $result['categories_deleted'],
                'fields'     => $result['custom_fields_deleted'],
            ]);
            if ($result['categories_kept'] > 0 || $result['custom_fields_kept'] > 0) {
                $message .= ' ' . __('Some dummy categories/custom fields were kept because real data depends on them.');
            }

            ResponseService::successResponse($message, $result);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SettingController -> deleteDummyData');
            ResponseService::errorResponse(__('Something Went Wrong'));
        }
    }

    public function watermarkSettingsStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        try {
            // Build validation rules dynamically based on style
            $rules = [
                'watermark_enabled' => 'nullable|in:0,1',
                'watermark_image' => 'nullable|image|mimes:png,jpg,jpeg|max:3000',
                'opacity' => 'required_if:watermark_enabled,1|numeric|min:0|max:100',
                'size' => 'required_if:watermark_enabled,1|numeric|min:1|max:100',
                'style' => 'required_if:watermark_enabled,1|in:tile,single,center',
                'rotation' => 'nullable|numeric|min:-360|max:360',
            ];

            // Position is only required for 'single' and 'center' styles, not for 'tile'
            $style = $request->input('style');
            if ($style == 'single' || $style == 'tile') {
                $rules['position'] = 'required_if:watermark_enabled,1|in:top-left,top-right,bottom-left,bottom-right,center';
            } else {
                // For 'tile' style, position is not required but we'll set a default
                $rules['position'] = 'nullable|in:top-left,top-right,bottom-left,bottom-right,center';
            }

            $validator = Validator::make($request->all(), $rules, [
                'watermark_image.mimes' => trans('Image must be JPG, JPEG or PNG'),
                'watermark_image.max' => trans('Image size must be less than 3MB'),
            ]);

            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }

            // Get existing watermark image to delete if new one is uploaded
            $oldWatermarkImage = Setting::where('name', 'watermark_image')->first();
            $oldWatermarkPath = $oldWatermarkImage ? $oldWatermarkImage->getRawOriginal('value') : null;

            // Store watermark settings individually in settings table
            $data = [];

            // Store watermark_enabled
            $data[] = [
                'name' => 'watermark_enabled',
                'value' => $request->watermark_enabled ?? 0,
                'type' => 'string',
            ];

            // Handle watermark image upload
            if ($request->hasFile('watermark_image') && $request->file('watermark_image')->isValid()) {
                // Delete old watermark image if exists
                if (! empty($oldWatermarkPath)) {
                    FileService::delete($oldWatermarkPath);
                }

                // Upload new watermark image
                $watermarkImagePath = FileService::compressAndUpload($request->file('watermark_image'), $this->uploadFolder);
                $data[] = [
                    'name' => 'watermark_image',
                    'value' => $watermarkImagePath,
                    'type' => 'file',
                ];
            } else {
                // Keep existing watermark image if not uploading new one
                if ($oldWatermarkImage) {
                    $data[] = [
                        'name' => 'watermark_image',
                        'value' => $oldWatermarkPath,
                        'type' => 'file',
                    ];
                }
            }

            // Store other watermark settings
            if ($request->filled('opacity')) {
                $data[] = [
                    'name' => 'watermark_opacity',
                    'value' => $request->opacity,
                    'type' => 'string',
                ];
            }

            if ($request->filled('size')) {
                $data[] = [
                    'name' => 'watermark_size',
                    'value' => $request->size,
                    'type' => 'string',
                ];
            }

            if ($request->filled('style')) {
                $data[] = [
                    'name' => 'watermark_style',
                    'value' => $request->style,
                    'type' => 'string',
                ];
            }

            // Handle position - set default based on style
            $style = $request->input('style');
            $position = $request->input('position') ?? $request->input('position_hidden');

            // If style is 'center', force position to 'center'
            if ($style === 'center') {
                $position = 'center';
            } elseif ($style === 'tile') {
                // For tile, position doesn't matter but set a default for consistency
                $position = $position ?? 'center';
            }

            // Always save position (needed for watermark job)
            $data[] = [
                'name' => 'watermark_position',
                'value' => $position ?? 'center',
                'type' => 'string',
            ];

            if ($request->filled('rotation')) {
                $data[] = [
                    'name' => 'watermark_rotation',
                    'value' => $request->rotation ?? -30,
                    'type' => 'string',
                ];
            } else {
                // Set default rotation if not provided
                $data[] = [
                    'name' => 'watermark_rotation',
                    'value' => -30,
                    'type' => 'string',
                ];
            }

            // Upsert all settings
            Setting::upsert($data, 'name', ['value']);

            // Clear cache
            CachingService::removeCache(config('constants.CACHE.SETTINGS'));

            ResponseService::successResponse(trans('Watermark Settings Updated Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Setting Controller -> watermarkSettingsStore');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function reelSettingsStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        $validator = Validator::make($request->all(), [
            'reel_max_file_size_mb'        => 'required|integer|min:1|max:500',
            'reel_max_duration_sec'        => 'required|integer|min:1|max:600',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $data = [
                ['name' => 'reel_max_file_size_mb',       'value' => $request->reel_max_file_size_mb,       'type' => 'string'],
                ['name' => 'reel_max_duration_sec',        'value' => $request->reel_max_duration_sec,        'type' => 'string']
            ];

            Setting::upsert($data, 'name', ['value']);
            CachingService::removeCache(config('constants.CACHE.SETTINGS'));

            ResponseService::successResponse(trans('Reel Settings Updated Successfully'));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SettingController -> reelSettingsStore');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    public function emailTemplatesIndex()
    {
        ResponseService::noPermissionThenRedirect('settings-update');

        return view('settings.email-templates.index');
    }

    private function emailTemplatesDefinitions(): array
    {
        return [
            'email_template_item_expiry' => [
                'name' => 'email_template_item_expiry',
                'display_name' => __('Item Expiry Notification'),
                'description' => __('Email sent when an advertisement is expiring in 2 days'),
            ],
            'email_template_package_expiry' => [
                'name' => 'email_template_package_expiry',
                'display_name' => __('Package Expiry Notification'),
                'description' => __('Email sent when a subscription package is expiring in 2 days'),
            ],
            'email_template_new_login' => [
                'name' => 'email_template_new_login',
                'display_name' => __('New Device Login Notification'),
                'description' => __('Email sent when a new device logs in to user account'),
            ],
        ];
    }

    public function emailTemplatesList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $sort = $request->input('sort', 'display_name');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->input('search', ''));

        $settings = CachingService::getSystemSettings()->toArray();
        $templates = $this->emailTemplatesDefinitions();

        $rows = [];
        foreach ($templates as $key => $tpl) {
            $hasTemplate = !empty($settings[$key]);
            $rows[] = [
                'name' => $tpl['name'],
                'display_name' => $tpl['display_name'],
                'description' => $tpl['description'],
                'status' => $hasTemplate
                    ? '<span class="badge bg-success">' . trans('Configured') . '</span>'
                    : '<span class="badge bg-warning">' . trans('Not Configured') . '</span>',
                'operate' => BootstrapTableService::editButton(route('settings.email-templates.edit', $tpl['name'])),
            ];
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, function ($r) use ($needle) {
                return str_contains(mb_strtolower($r['display_name']), $needle)
                    || str_contains(mb_strtolower($r['description']), $needle);
            }));
        }

        usort($rows, function ($a, $b) use ($sort, $order) {
            $av = $a[$sort] ?? '';
            $bv = $b[$sort] ?? '';
            $cmp = strcmp((string) $av, (string) $bv);
            return $order === 'desc' ? -$cmp : $cmp;
        });

        $total = count($rows);
        $rows = array_slice($rows, $offset, $limit);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function emailTemplateEdit(string $template)
    {
        ResponseService::noPermissionThenRedirect('settings-update');
        
        $allowedTemplates = [
            'email_template_item_expiry' => __('Item Expiry Notification'),
            'email_template_package_expiry' => __('Package Expiry Notification'),
            'email_template_new_login' => __('New Device Login Notification'),
        ];

        if (!isset($allowedTemplates[$template])) {
            abort(404, 'Email template not found');
        }

        $settings = CachingService::getSystemSettings()->toArray();
        $templateValue = $settings[$template] ?? '';
        
        // Default professional templates
        $defaultTemplates = [
            'email_template_item_expiry' => '<p>Hello {{user_name}},</p>
            <p>This is to inform you that your advertisement <strong>{{item_name}}</strong> is expiring on <strong>{{expiry_date}}</strong>.</p>
            <p>Please take necessary action before it expires.</p>
            <p>Thank you for using our platform.</p>
            <p>Best regards,<br>{{company_name}}</p>',
                        'email_template_package_expiry' => '<p>Hello {{user_name}},</p>
            <p>This is to inform you that your subscription package <strong>{{package_name}}</strong> is expiring on <strong>{{expiry_date}}</strong>.</p>
            <p>Please renew or upgrade your subscription to continue enjoying our services.</p>
            <p>Thank you for using our platform.</p>
            <p>Best regards,<br>{{company_name}}</p>',
                        'email_template_new_login' => '<p>Hello {{user_name}},</p>
            <p>A new device has logged in to your {{company_name}} account.</p>
            <p><strong>Device Details:</strong></p>
            <ul>
            <li>Device Type: {{device_type}}</li>
            <li>IP Address: {{ip_address}}</li>
            <li>Login Time: {{login_time}}</li>
            </ul>
            <p>If this was not you, please secure your account immediately.</p>
            <p>Best regards,<br>{{company_name}}</p>',
        ];

        // If no template exists, use default
        if (empty($templateValue)) {
            $templateValue = $defaultTemplates[$template] ?? '';
        }

        $displayName = $allowedTemplates[$template];
        $languages = CachingService::getLanguages();
        $translations = $this->getSettingTranslations();

        return view('settings.email-templates.edit', compact('template', 'templateValue', 'displayName', 'languages', 'translations', 'settings'));
    }

    public function emailTemplateStore(Request $request, string $template)
    {
        ResponseService::noPermissionThenSendJson('settings-update');
        
        $allowedTemplates = [
            'email_template_item_expiry',
            'email_template_package_expiry',
            'email_template_new_login',
        ];

        if (!in_array($template, $allowedTemplates)) {
            ResponseService::errorResponse('Invalid email template');
        }

        $validator = Validator::make($request->all(), [
            'template_content' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            // Save default template (first language or fallback)
            $templateContent = $request->input('template_content');
            if (is_array($templateContent)) {
                // Validate array values
                foreach ($templateContent as $langId => $content) {
                    if (empty($content)) {
                        ResponseService::validationError('Template content cannot be empty for any language');
                    }
                }
                $templateContent = reset($templateContent);
            } else {
                if (empty($templateContent)) {
                    ResponseService::validationError('Template content cannot be empty');
                }
            }

            Setting::updateOrCreate(
                ['name' => $template],
                ['value' => $templateContent, 'type' => 'string']
            );

            // Save translations if provided
            if ($request->has('template_content') && is_array($request->input('template_content'))) {
                $templateInputs = $request->input('template_content', []);
                foreach ($templateInputs as $languageId => $value) {
                    $setting = Setting::where('name', $template)->first();
                    if ($setting) {
                        HelperService::storeTranslations([
                            ['translatable_id' => $setting->id, 'translatable_type' => \App\Models\Setting::class, 'key' => 'translated_value', 'value' => $value, 'language_id' => $languageId],
                        ]);
                    }
                }
            }

            // Handle new login email enabled setting
            if ($template === 'email_template_new_login') {
                $enabled = $request->input('email_new_login_enabled', 0);
                Setting::updateOrCreate(
                    ['name' => 'email_new_login_enabled'],
                    ['value' => $enabled, 'type' => 'string']
                );
            }

            CachingService::removeCache(config('constants.CACHE.SETTINGS'));
            ResponseService::successResponse('Email template updated successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Setting Controller -> emailTemplateStore');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    /**
     * Store Gemini AI settings
     */
    public function geminiSettingsStore(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        try {
            $validated = $request->validate([
                'gemini_ai_enabled' => 'nullable|in:0,1',
                'gemini_auto_translate_enabled' => 'nullable|in:0,1',
                'gemini_api_key' => 'nullable|string',
                'gemini_model' => 'required|string|max:100',
                'gemini_description_limit' => 'required|integer|min:0|max:1000',
                'gemini_meta_limit' => 'required|integer|min:0|max:1000',
                'gemini_description_limit_global' => 'required|integer|min:0|max:1000',
                'gemini_meta_limit_global' => 'required|integer|min:0|max:1000',
            ]);

            $validated['gemini_ai_enabled'] = $request->input('gemini_ai_enabled', '0');
            $validated['gemini_auto_translate_enabled'] = $request->input('gemini_auto_translate_enabled', '0');

            foreach ($validated as $key => $value) {
                Setting::updateOrCreate(
                    ['name' => $key],
                    ['value' => $value, 'type' => 'string']
                );
            }

            // Sync API key and model URL to .env
            $envUpdates = [];
            if ($request->filled('gemini_api_key')) {
                $envUpdates['GEMINI_API_KEY'] = $request->gemini_api_key;
            }
            $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/' . $request->gemini_model;
            $envUpdates['GEMINI_API_URL'] = $apiUrl;

            if (!empty($envUpdates)) {
                HelperService::changeEnv($envUpdates);
            }

            CachingService::removeCache(config('constants.CACHE.SETTINGS'));
            ResponseService::successResponse('Gemini AI settings updated successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Setting Controller -> geminiSettingsStore');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    /**
     * Clear Gemini AI cache
     */
    public function geminiClearCache()
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        try {
            // Clear entire gemini cache store (isolated from app cache)
            Cache::store('gemini')->flush();
            Log::info('Gemini AI cache cleared');
            ResponseService::successResponse('Gemini AI cache cleared successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Setting Controller -> geminiClearCache');
            ResponseService::errorResponse('Failed to clear cache');
        }
    }

    /**
     * Fetch available Gemini models from Google API
     */
    public function geminiModelsList(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        try {
            $apiKey = $request->input('api_key') ?: config('services.gemini.api_key');

            if (empty($apiKey)) {
                ResponseService::validationError('Please enter an API key first.');
            }

            $response = Http::timeout(10)
                ->get('https://generativelanguage.googleapis.com/v1beta/models', [
                    'key' => $apiKey,
                    'pageSize' => 100,
                ]);

            if ($response->failed()) {
                ResponseService::validationError('Failed to fetch models. Please check your API key.');
            }

            $allModels = $response->json()['models'] ?? [];

            // Filter: only generateContent-capable Gemini models (not Gemma, TTS, image, etc.)
            $models = [];
            foreach ($allModels as $m) {
                $methods = $m['supportedGenerationMethods'] ?? [];
                if (!in_array('generateContent', $methods)) continue;

                $name = str_replace('models/', '', $m['name'] ?? '');
                $displayName = $m['displayName'] ?? $name;

                // Only include Gemini text models (exclude gemma, tts, image, robotics, preview-only, etc.)
                if (!str_starts_with($name, 'gemini-')) continue;
                if (str_contains($name, 'tts') || str_contains($name, 'image') || str_contains($name, 'robotics') || str_contains($name, 'computer-use') || str_contains($name, 'deep-research') || str_contains($name, 'nano-banana')) continue;

                $models[] = [
                    'name' => $name,
                    'displayName' => $displayName,
                    'inputTokenLimit' => $m['inputTokenLimit'] ?? 0,
                    'outputTokenLimit' => $m['outputTokenLimit'] ?? 0,
                    'description' => $m['description'] ?? '',
                ];
            }

            // Sort: stable models first, then by name
            usort($models, function ($a, $b) {
                // Prioritize non-preview models
                $aPreview = str_contains($a['name'], 'preview') ? 1 : 0;
                $bPreview = str_contains($b['name'], 'preview') ? 1 : 0;
                if ($aPreview !== $bPreview) return $aPreview - $bPreview;
                return strcmp($a['name'], $b['name']);
            });

            ResponseService::successResponse('Models fetched successfully', $models);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Setting Controller -> geminiModelsList');
            ResponseService::errorResponse('Failed to fetch models');
        }
    }

    public function imageCacheStats()
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $cacheDir = 'image-cache';

        $count = 0;
        $size = 0;
        if ($disk->exists($cacheDir)) {
            foreach ($disk->files($cacheDir) as $file) {
                $count++;
                $size += $disk->size($file);
            }
        }

        ResponseService::successResponse('Image cache stats fetched successfully', [
            'count' => $count,
            'size' => $size,
            'size_human' => $this->humanFileSize($size),
        ]);
    }

    public function clearImageCache(Request $request)
    {
        ResponseService::noPermissionThenSendJson('settings-update');

        $days = $request->input('days');

        Artisan::call('image-cache:clear', $days ? ['--days' => (int) $days] : []);

        ResponseService::successResponse(trim(Artisan::output()) ?: 'Image cache cleared successfully');
    }

    private function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
