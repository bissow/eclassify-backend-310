<?php

namespace App\Providers;

use App\Models\Language;
use App\Models\Setting;
use App\Services\CachingService;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
      View::composer('layouts.topbar', function ($view) {
            $languages = CachingService::getLanguages()->where('status', true)->values();

            // Always get the most recent default from DB
            $defaultLangCodeData = CachingService::getSystemSettings('default_language');
            $defaultLangCode = $defaultLangCodeData ?? 'en';
            $defaultLanguage = $languages->where('code', $defaultLangCode)->first();

            // If session is empty, use the database default
            $currentLocale = Session::get('locale', $defaultLangCode);
            $currentLanguage = $languages->where('code', $currentLocale)->first();

            $view->with([
                'languages'       => $languages,
                'defaultLanguage' => $defaultLanguage, // Now correctly shows the DB value
                'currentLanguage' => $currentLanguage,
                'settings'        => CachingService::getSystemSettings()
            ]);
        });




        View::composer('layouts.sidebar', static function (\Illuminate\View\View $view) {
            $settings = CachingService::getSystemSettings('company_logo');
            $view->with('company_logo', $settings ?? '');
        });

        View::composer('layouts.main', static function (\Illuminate\View\View $view) {
            $settings = CachingService::getSystemSettings('favicon_icon');
            $view->with('favicon', $settings ?? '');
            $view->with('lang', Session::get('language'));
            $view->with('settings', CachingService::getSystemSettings());
        });

        View::composer('auth.login', static function (\Illuminate\View\View $view) {
            // Get Required Settings Data from DB
            $settings = ['favicon_icon','company_logo','login_image','admin_primary_color'];
            $settingData = CachingService::getSystemSettings($settings);

            // Get specific data
            $faviconIcon = isset($settingData['favicon_icon']) && !empty($settingData['favicon_icon']) ? $settingData['favicon_icon'] : null;
            $companyLogo = isset($settingData['company_logo']) && !empty($settingData['company_logo']) ? $settingData['company_logo'] : null;
            $LoginBgImage = isset($settingData['login_image']) && !empty($settingData['login_image']) ? $settingData['login_image'] : null;
            $adminPrimaryColor =  isset($settingData['admin_primary_color']) && !empty($settingData['admin_primary_color']) ? $settingData['admin_primary_color'] : '#00B2CA';


            $view->with('company_logo', $companyLogo);
            $view->with('favicon', $faviconIcon);
            $view->with('login_bg_image', $LoginBgImage);
            $view->with('theme_color', $adminPrimaryColor);
        });

        View::composer('auth.forgot-password', static function (\Illuminate\View\View $view) {
            // Get Required Settings Data from DB
            $settings = ['favicon_icon','company_logo','login_image','admin_primary_color'];
            $settingData = CachingService::getSystemSettings($settings);

            // Get specific data
            $faviconIcon = isset($settingData['favicon_icon']) && !empty($settingData['favicon_icon']) ? $settingData['favicon_icon'] : null;
            $companyLogo = isset($settingData['company_logo']) && !empty($settingData['company_logo']) ? $settingData['company_logo'] : null;
            $LoginBgImage = isset($settingData['login_image']) && !empty($settingData['login_image']) ? $settingData['login_image'] : null;
            $adminPrimaryColor =  isset($settingData['admin_primary_color']) && !empty($settingData['admin_primary_color']) ? $settingData['admin_primary_color'] : '#00B2CA';


            $view->with('company_logo', $companyLogo);
            $view->with('favicon', $faviconIcon);
            $view->with('login_bg_image', $LoginBgImage);
            $view->with('theme_color', $adminPrimaryColor);
        });

        View::composer('layouts.footer_script', static function (\Illuminate\View\View $view) {
            $settingData = CachingService::getSystemSettings([
                'apiKey', 'authDomain', 'projectId', 'storageBucket',
                'messagingSenderId', 'appId', 'vapidKey'
            ]);

            $firebaseWebConfig = [
                'apiKey' => $settingData['apiKey'] ?? null,
                'authDomain' => $settingData['authDomain'] ?? null,
                'projectId' => $settingData['projectId'] ?? null,
                'storageBucket' => $settingData['storageBucket'] ?? null,
                'messagingSenderId' => $settingData['messagingSenderId'] ?? null,
                'appId' => $settingData['appId'] ?? null,
                'vapidKey' => $settingData['vapidKey'] ?? null,
            ];

            $firebaseWebConfigured = !empty($firebaseWebConfig['apiKey']) &&
                !empty($firebaseWebConfig['projectId']) &&
                !empty($firebaseWebConfig['messagingSenderId']) &&
                !empty($firebaseWebConfig['authDomain']) &&
                !empty($firebaseWebConfig['appId']);

            $view->with('firebaseWebConfig', $firebaseWebConfig);
            $view->with('firebaseWebConfigured', $firebaseWebConfigured);
        });

        View::composer('layouts.include', static function (\Illuminate\View\View $view) {
            $settings = ['company_logo', 'favicon_icon'];

            // Raw original values (bypass the file-type URL accessor) so Storage::exists() gets the actual storage path
            $settingData = CachingService::getSystemSettingsRaw($settings);

            $companyLogo = $settingData['company_logo'] ?? '';
            $favicon = $settingData['favicon_icon'] ?? '';

            $view->with('company_logo', (!empty($companyLogo) && Storage::exists($companyLogo))
                ? Storage::url($companyLogo)
                : asset('assets/images/logo/sidebar_logo.png'));

            $view->with('favicon', (!empty($favicon) && Storage::exists($favicon))
                ? Storage::url($favicon)
                : asset('assets/images/logo/favicon.png'));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
