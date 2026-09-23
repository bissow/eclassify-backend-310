<?php

use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\BannerAdController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\ChatTemplateController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FeatureSectionController;
use App\Http\Controllers\GeminiAIController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HomeScreenSectionController;
use App\Http\Controllers\ImageResizeController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PluginManagerController;
use App\Http\Controllers\ReportReasonController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SeoSettingController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Controllers\TipController;
use App\Http\Controllers\UserVerificationController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Auth\LoginController;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Rap2hpoutre\LaravelLogViewer\LogViewerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
Route::post('change-password/send-reset-otp', [HomeController::class, 'sendPasswordResetOtp'])->name('change-password.send-reset-otp');
Route::post('change-password/verify-reset-otp', [HomeController::class, 'verifyPasswordResetOtp'])->name('change-password.verify-reset-otp');
Route::post('change-password/update-with-otp', [HomeController::class, 'updatePasswordWithOtp'])->name('change-password.update-with-otp');
Route::get('forgot-password', [LoginController::class, 'showForgotPasswordForm'])->name('admin.forgot-password');

Route::get('/', static function () {
    if (Auth::user()) {
        return redirect('/home');
    }

    return view('auth.login');
});

Route::get('page/privacy-policy', function (Request $request) {
    $langId = $request->query('lang', 1); // default to English (id=1)

    // Fetch the privacy_policy setting
    $privacySetting = Setting::where('name', 'privacy_policy')->first();

    if (! $privacySetting) {
        return '';
    }

    // Try to get the translation
    $translation = $privacySetting->translations()
        ->where('language_id', $langId)
        ->first();

    $privacyPolicy = $translation->translated_value ?? $privacySetting->value;

    echo htmlspecialchars_decode($privacyPolicy);
})->name('public.privacy-policy');

Route::get('page/contact-us', function (Request $request) {
    $langId = $request->query('lang', 1);
    $setting = Setting::where('name', 'contact_us')->first();
    if (! $setting) {
        return '';
    }

    $translation = $setting->translations()->where('language_id', $langId)->first();
    $content = $translation->translated_value ?? $setting->value;

    echo htmlspecialchars_decode($content);
})->name('public.contact-us');

// Terms & Conditions
Route::get('page/terms-conditions', function (Request $request) {
    $langId = $request->query('lang', 1);
    $setting = Setting::where('name', 'terms_conditions')->first();
    if (! $setting) {
        return '';
    }

    $translation = $setting->translations()->where('language_id', $langId)->first();
    $content = $translation->translated_value ?? $setting->value;

    echo htmlspecialchars_decode($content);
})->name('public.terms-conditions');

// Refund Policy
Route::get('page/refund-policy', function (Request $request) {
    $langId = $request->query('lang', 1);
    $setting = Setting::where('name', 'refund_policy')->first();
    if (! $setting) {
        return '';
    }

    $translation = $setting->translations()->where('language_id', $langId)->first();
    $content = $translation->translated_value ?? $setting->value;

    echo htmlspecialchars_decode($content);
})->name('public.refund-policy');

Route::group(['prefix' => 'webhook'], static function () {
    Route::post('/stripe', [WebhookController::class, 'stripe']);
    Route::post('/paystack', [WebhookController::class, 'paystack']);
    Route::post('/razorpay', [WebhookController::class, 'razorpay']);
    Route::post('/phonePe', [WebhookController::class, 'phonePe']);
    Route::post('/flutterwave', [WebhookController::class, 'flutterwave']);
    Route::post('/paypal', [WebhookController::class, 'paypal'])->name('paypal.webhook');
    Route::post('/paytabs', [WebhookController::class, 'paytabs'])->name('paytabs.webhook');
    Route::post('/dpo', [WebhookController::class, 'dpo'])->name('dpo.webhook');
    // Generic — shared by every plugin gateway that supports a webhook.
    Route::post('/plugin/{gateway}', [WebhookController::class, 'pluginWebhook'])->name('plugin.webhook');

});

/** Payment gateway callbacks */
// DPO routes
Route::match(['GET', 'POST'], 'response/dpo/success', [WebhookController::class, 'dpoSuccessCallback'])->name('dpo.success');
Route::match(['GET', 'POST'], 'response/dpo/success/web', [WebhookController::class, 'dpoPaymentSuccess'])->name('dpo.success.web');
// PayPal routes
Route::match(['GET', 'POST'], 'response/paypal/cancel', [WebhookController::class, 'paypalCancelCallback'])->name('paypal.cancel');
Route::match(['GET', 'POST'], 'response/paypal/success', [WebhookController::class, 'paypalSuccessCallback'])->name('paypal.success');
Route::match(['GET', 'POST'], 'response/paypal/success/web', [WebhookController::class, 'paypalPaymentSuccess'])->name('paypal.success.web');
Route::match(['GET', 'POST'], 'response/paypal/cancel/web', [WebhookController::class, 'paypalCancelCallbackWeb'])->name('paypal.cancel.web');
// PhonePe routes
Route::match(['GET', 'POST'], 'response/phonepe/success', [WebhookController::class, 'phonePeSuccessCallback'])->name('phonepe.success');
Route::match(['GET', 'POST'], 'response/phonepe/success/web', [SettingController::class, 'phonepePaymentSucesss'])->name('phonepe.success.web');
// PayTabs routes
Route::match(['GET', 'POST'], 'response/paytabs/success', [WebhookController::class, 'paytabsSuccessCallback'])->name('paytabs.success');
Route::match(['GET', 'POST'], 'response/paytabs/success/web', [SettingController::class, 'paytabsPaymentSucesssWeb'])->name('paytabs.success.web');
// PayStack routes
Route::match(['GET', 'POST'], 'response/paystack/success', [WebhookController::class, 'paystackSuccessCallback'])->name('paystack.success');
Route::match(['GET', 'POST'], 'response/paystack/success/web', [SettingController::class, 'paystackPaymentSucesss'])->name('paystack.success.web');
// FlutterWave routes
Route::match(['GET', 'POST'], 'response/flutter-wave/success', [WebhookController::class, 'flutterWaveSuccessCallback'])->name('flutterwave.success');
Route::match(['GET', 'POST'], 'response/flutter-wave/success/web', [SettingController::class, 'flutterWavePaymentSucesss'])->name('flutterwave.success.web');
// Plugin payment gateway return routes — shared by every type=payment
// plugin, not one pair per plugin.
Route::match(['GET', 'POST'], 'response/plugin-payment/success/{paymentTransactionId}', [WebhookController::class, 'pluginPaymentSuccess'])->name('plugin-payment.success');
Route::match(['GET', 'POST'], 'response/plugin-payment/success/{paymentTransactionId}/web', [WebhookController::class, 'pluginPaymentSuccessWeb'])->name('plugin-payment.success.web');
Route::match(['GET', 'POST'], 'response/plugin-payment/failed/{paymentTransactionId}', [WebhookController::class, 'pluginPaymentFailed'])->name('plugin-payment.failed');
Route::match(['GET', 'POST'], 'response/plugin-payment/failed/{paymentTransactionId}/web', [WebhookController::class, 'pluginPaymentFailedWeb'])->name('plugin-payment.failed.web');
/*************************************************** */

/* Non-Authenticated Common Functions */
Route::group(['prefix' => 'common'], static function () {
    Route::get('/js/lang', [Controller::class, 'readLanguageFile'])->name('common.language.read');
});
Route::group(['prefix' => 'install'], static function () {
    Route::get('purchase-code', [InstallerController::class, 'purchaseCodeIndex'])->name('install.purchase-code.index');
    Route::post('purchase-code', [InstallerController::class, 'checkPurchaseCode'])->name('install.purchase-code.post');
    Route::get('php-function', [InstallerController::class, 'phpFunctionIndex'])->name('install.php-function.index');
});

Route::group(['middleware' => ['auth', 'language']], static function () {
    /*** Authenticated Common Functions ***/
    Route::group(['prefix' => 'common'], static function () {
        Route::put('/change-row-order', [Controller::class, 'changeRowOrder'])->name('common.row-order.change');
        Route::put('/change-status', [Controller::class, 'changeStatus'])->name('common.status.change');
    });

    /*** Home Module : START ***/
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/recent-ads', [HomeController::class, 'recentAdsTable'])->name('home.recent-ads');
    Route::get('/home/recent-reports', [HomeController::class, 'recentReportsTable'])->name('home.recent-reports');
    Route::get('/home/top-customers', [HomeController::class, 'topCustomersTable'])->name('home.top-customers');
    Route::get('/home/most-viewed', [HomeController::class, 'mostViewedTable'])->name('home.most-viewed');
    Route::get('change-password', [HomeController::class, 'changePasswordIndex'])->name('change-password.index');
    Route::post('change-password', [HomeController::class, 'changePasswordUpdate'])->name('change-password.update');

    Route::get('change-profile', [HomeController::class, 'changeProfileIndex'])->name('change-profile.index');
    Route::post('change-profile', [HomeController::class, 'changeProfileUpdate'])->name('change-profile.update');
    /*** Home Module : END ***/

    /*** Category Module : START ***/
    Route::group(['prefix' => 'category'], static function () {
        // Bulk operations routes must come BEFORE resource route to avoid route conflicts
        Route::get('/bulk-upload', [CategoryController::class, 'bulkUpload'])->name('category.bulk-upload');
        Route::post('/bulk-upload', [CategoryController::class, 'processBulkUpload'])->name('category.bulk-upload.process');
        Route::get('/bulk-upload/example', [CategoryController::class, 'downloadExample'])->name('category.bulk-upload.example');
        Route::post('/bulk-upload/gallery/upload', [CategoryController::class, 'uploadGalleryImage'])->name('category.bulk-upload.gallery.upload');
        Route::get('/bulk-upload/gallery/list', [CategoryController::class, 'getGalleryImages'])->name('category.bulk-upload.gallery.list');
        Route::get('/bulk-update', [CategoryController::class, 'bulkUpdate'])->name('category.bulk-update');
        Route::get('/bulk-update/download', [CategoryController::class, 'downloadCurrentCategories'])->name('category.bulk-update.download');
        Route::post('/bulk-update', [CategoryController::class, 'processBulkUpdate'])->name('category.bulk-update.process');
        Route::get('/categories/order', [CategoryController::class, 'categoriesReOrder'])->name('category.order');
        Route::post('categories/change-order', [CategoryController::class, 'updateOrder'])->name('category.order.change');
        Route::get('/{id}/subcategories', [CategoryController::class, 'getSubCategories'])->name('category.subcategories');
        Route::get('/{id}/custom-fields', [CategoryController::class, 'customFields'])->name('category.custom-fields');
        Route::get('/{id}/custom-fields/show', [CategoryController::class, 'getCategoryCustomFields'])->name('category.custom-fields.show');
        Route::delete('/{id}/custom-fields/{customFieldID}/delete', [CategoryController::class, 'destroyCategoryCustomField'])->name('category.custom-fields.destroy');
        Route::get('/{id}/sub-category/change-order', [CategoryController::class, 'subCategoriesReOrder'])->name('sub.category.order.change');
    });
    Route::resource('category', CategoryController::class);
    /*** Category Module : END ***/

    /*** Chat Template Module : START ***/
    Route::resource('chat-templates', ChatTemplateController::class);
    /*** Chat Template Module : END ***/

    /*** Custom Field Module : START ***/
    Route::group(['prefix' => 'custom-fields'], static function () {
        Route::post('/{id}/value/add', [CustomFieldController::class, 'addCustomFieldValue'])->name('custom-fields.value.add');
        Route::get('/{id}/value/show', [CustomFieldController::class, 'getCustomFieldValues'])->name('custom-fields.value.show');
        Route::put('/{id}/value/edit', [CustomFieldController::class, 'updateCustomFieldValue'])->name('custom-fields.value.update');
        Route::delete('/{id}/value/{value}/delete', [CustomFieldController::class, 'deleteCustomFieldValue'])->name('custom-fields.value.delete');
        Route::get('/bulk-upload', [CustomFieldController::class, 'bulkUpload'])->name('custom-fields.bulk-upload');
        Route::post('/bulk-upload', [CustomFieldController::class, 'processBulkUpload'])->name('custom-fields.bulk-upload.process');
        Route::get('/bulk-upload/example', [CustomFieldController::class, 'downloadExample'])->name('custom-fields.bulk-upload.example');
        Route::get('/bulk-upload/instructions-pdf', [CustomFieldController::class, 'downloadInstructionsPdf'])->name('custom-fields.bulk-upload.instructions-pdf');
        Route::post('/bulk-upload/gallery/upload', [CustomFieldController::class, 'uploadGalleryImage'])->name('custom-fields.bulk-upload.gallery.upload');
        Route::get('/bulk-upload/gallery/list', [CustomFieldController::class, 'getGalleryImages'])->name('custom-fields.bulk-upload.gallery.list');
        Route::get('/bulk-update', [CustomFieldController::class, 'bulkUpdate'])->name('custom-fields.bulk-update');
        Route::get('/bulk-update/download', [CustomFieldController::class, 'downloadCurrentCustomFields'])->name('custom-fields.bulk-update.download');
        Route::post('/bulk-update', [CustomFieldController::class, 'processBulkUpdate'])->name('custom-fields.bulk-update.process');
    });
    Route::resource('custom-fields', CustomFieldController::class);
    /*** Custom Field Module : END ***/

    /* NOTE : Improve this mess of routes */

    /** Verification Field Routes */
    Route::group(['prefix' => 'verification-field'], static function () {
        Route::get('/', [UserVerificationController::class, 'verificationField'])->name('seller-verification.verification-field');
        Route::get('/list', [UserVerificationController::class, 'showVerificationFields'])->name('verification-field.show');
        Route::get('/{id}/edit', [UserVerificationController::class, 'edit'])->name('seller-verification.verification-field.edit');
        Route::put('/{id}', [UserVerificationController::class, 'update'])->name('seller-verification.verification-field.update');
        Route::delete('/{id}/delete', [UserVerificationController::class, 'destroy'])->name('seller-verification.verification-field.delete');
    });

    /** Seller Verification Routes */
    Route::group(['prefix' => 'seller-verification'], static function () {
        Route::put('/{id}/approval', [UserVerificationController::class, 'updateSellerApproval'])->name('seller_verification.approval');
        Route::get('/verification-requests', [UserVerificationController::class, 'show'])->name('verification_requests.show');
        Route::get('/verification-details/{id}', [UserVerificationController::class, 'getVerificationDetails']);
        Route::put('/seller-verification/status-change', [UserVerificationController::class, 'updateStatus'])->name('seller-verification.update_status');
        Route::post('/{id}/value/add', [UserVerificationController::class, 'addSellerVerificationValue'])->name('seller-verification.value.add');
        Route::get('/{id}/value/show', [UserVerificationController::class, 'getSellerVerificationValues'])->name('seller-verification.value.show');
        Route::put('/{id}/value/edit', [UserVerificationController::class, 'updateSellerVerificationValue'])->name('seller-verification.value.update');
        Route::delete('/{id}/value/{value}/delete', [UserVerificationController::class, 'deleteSellerVerificationValue'])->name('seller-verification.value.delete');
    });
    Route::resource('seller-verification', UserVerificationController::class);

    /*** Item Module : START ***/
    Route::group(['prefix' => 'advertisement'], static function () {
        Route::post('/approval', [ItemController::class, 'updateItemApproval'])->name('advertisement.approval');
        Route::post('/bulk-approval', [ItemController::class, 'bulkUpdateItemApproval'])->name('advertisement.bulk-approval');
        Route::get('/{id}/edit', [ItemController::class, 'editForm'])->name('advertisement.edit');
    });
    Route::get('/get-custom-fields/{categoryId}', [ItemController::class, 'getCustomFields']);
    Route::resource('advertisement', ItemController::class)->except(['edit']);
    Route::get('item/create', [ItemController::class, 'create'])->name('item.create');
    Route::get('item/states/search', [ItemController::class, 'searchState'])->name('state.search');
    Route::get('item/cities/search', [ItemController::class, 'searchCities'])->name('item.cities.search');
    Route::get('/get-parent-categories', [ItemController::class, 'getParentCategories'])->name('advertisement.get-parent-categories');
    Route::get('/get-subcategories', [ItemController::class, 'getSubCategories'])->name('advertisement.get-subcategories');
    /*** Item Module : END ***/

    /*** Admin Chat Module : START ***/
    Route::group(['prefix' => 'admin-chat'], static function () {
        Route::get('/', [AdminChatController::class, 'index'])->name('admin-chat.index');
        Route::get('/products', [AdminChatController::class, 'getProducts'])->name('admin-chat.products');
        Route::get('/chat-list', [AdminChatController::class, 'getChatList'])->name('admin-chat.chat-list');
        Route::get('/messages', [AdminChatController::class, 'getChatMessages'])->name('admin-chat.messages');
        Route::get('/link-preview', [AdminChatController::class, 'linkPreview'])->name('admin-chat.link-preview');
        Route::post('/send-message', [AdminChatController::class, 'sendMessage'])->name('admin-chat.send-message');
        Route::post('/send-offer', [AdminChatController::class, 'sendOffer'])->name('admin-chat.send-offer');
        Route::post('/register-fcm-token', [AdminChatController::class, 'registerFcmToken'])->name('admin-chat.register-fcm-token');
        Route::post('/delete-chat', [AdminChatController::class, 'deleteChat'])->name('admin-chat.delete-chat');
        Route::post('/delete-messages', [AdminChatController::class, 'deleteChatMessages'])->name('admin-chat.delete-messages');
        Route::post('/block-user', [AdminChatController::class, 'blockUser'])->name('admin-chat.block-user');
        Route::post('/unblock-user', [AdminChatController::class, 'unblockUser'])->name('admin-chat.unblock-user');
    });
    /*** Admin Chat Module : END ***/

    Route::resource('seller-review', SellerController::class);
    Route::group(['prefix' => 'review-report', 'as' => 'review-report.'], static function () {
        Route::get('/', [SellerController::class, 'reportsIndex'])->name('index');
        Route::get('/show', [SellerController::class, 'showReports'])->name('show');
    });

    /*** Store Module : START ***/
    Route::group(['prefix' => 'stores', 'as' => 'stores.'], static function () {
        Route::get('/bulk-upload', [StoreController::class, 'bulkUploadIndex'])->name('bulk-upload.index');
        Route::get('/bulk-upload/example', [StoreController::class, 'bulkUploadExample'])->name('bulk-upload.example');
        Route::post('/bulk-upload/process', [StoreController::class, 'bulkUploadProcess'])->name('bulk-upload.process');
        Route::get('/bulk-upload/gallery/list', [StoreController::class, 'getGalleryImages'])->name('bulk-upload.gallery.list');
        Route::post('/bulk-upload/gallery/upload', [StoreController::class, 'uploadGalleryImages'])->name('bulk-upload.gallery.upload');
        Route::get('/search-users', [StoreController::class, 'searchUsers'])->name('search-users');
        Route::get('/details/{id}', [StoreController::class, 'getDetails'])->name('details');
    });
    Route::resource('stores', StoreController::class);
    /*** Store Module : END ***/

    /*** Setting Module : START ***/
    Route::group(['prefix' => 'settings'], static function () {
        Route::get('/', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/store', [SettingController::class, 'store'])->name('settings.store');
        Route::get('image-cache/stats', [SettingController::class, 'imageCacheStats'])->name('settings.image-cache.stats');
        Route::post('image-cache/clear', [SettingController::class, 'clearImageCache'])->name('settings.image-cache.clear');

        Route::get('system', [SettingController::class, 'page'])->name('settings.system');
        Route::get('about-us', [SettingController::class, 'page'])->name('settings.about-us.index');
        Route::get('privacy-policy', [SettingController::class, 'page'])->name('settings.privacy-policy.index');
        Route::get('contact-us', [SettingController::class, 'page'])->name('settings.contact-us.index');
        Route::get('terms-conditions', [SettingController::class, 'page'])->name('settings.terms-conditions.index');

        Route::get('firebase', [SettingController::class, 'page'])->name('settings.firebase.index');
        Route::post('firebase/update', [SettingController::class, 'updateFirebaseSettings'])->name('settings.firebase.update');

        Route::get('payment-gateway', [SettingController::class, 'paymentSettingsIndex'])->name('settings.payment-gateway.index');
        Route::post('payment-gateway', [SettingController::class, 'paymentSettingsStore'])->name('settings.payment-gateway.store');
        Route::get('language', [SettingController::class, 'page'])->name('settings.language.index');
        Route::get('default-currency', [SettingController::class, 'page'])->name('settings.default-currency.index');
        Route::get('admob', [SettingController::class, 'page'])->name('settings.admob.index');
        Route::get('adsense', [SettingController::class, 'page'])->name('settings.adsense.index');
        Route::get('/system-status', [SettingController::class, 'systemStatus'])->name('settings.system-status.index');
        Route::post('/toggle-storage-link', [SettingController::class, 'toggleStorageLink'])->name('toggle.storage.link');
        Route::get('error-logs', [LogViewerController::class, 'index'])->name('settings.error-logs.index');
        Route::get('seo-setting', [SettingController::class, 'page'])->name('settings.seo-settings.index');
        Route::get('file-manager', [SettingController::class, 'page'])->name('settings.file-manager.index');
        Route::get('web-settings', [SettingController::class, 'page'])->name('settings.web-settings');
        Route::get('notification-setting', [SettingController::class, 'page'])->name('settings.notification-setting');
        Route::get('login-method', [SettingController::class, 'page'])->name('settings.login-method');
        Route::post('file-manager-store', [SettingController::class, 'fileManagerSettingStore'])->name('settings.file-manager.store');
        Route::get('manage-bank-account-details', [SettingController::class, 'page'])->name('settings.bank-details.index');
        Route::get('refund-policy', [SettingController::class, 'page'])->name('settings.refund-policy.index');
        Route::get('dummy-data', [SettingController::class, 'dummyDataIndex'])->name('settings.dummy-data.index');
        Route::get('watermark-settings', [SettingController::class, 'page'])->name('settings.watermark-settings');
        Route::get('refer-earn', [SettingController::class, 'page'])->name('settings.refer-earn');
        Route::post('dummy-data/import', [SettingController::class, 'importDummyData'])->name('settings.dummy-data.import');
        Route::post('dummy-data/delete', [SettingController::class, 'deleteDummyData'])->name('settings.dummy-data.delete');
        Route::post('watermark-settings/store', [SettingController::class, 'watermarkSettingsStore'])->name('settings.watermark-settings-store');
        Route::get('email-templates', [SettingController::class, 'emailTemplatesIndex'])->name('settings.email-templates.index');
        Route::get('email-templates/list', [SettingController::class, 'emailTemplatesList'])->name('settings.email-templates.list');
        Route::get('email-templates/{template}/edit', [SettingController::class, 'emailTemplateEdit'])->name('settings.email-templates.edit');
        Route::post('email-templates/{template}/store', [SettingController::class, 'emailTemplateStore'])->name('settings.email-templates.store');
        Route::get('gemini-settings', [SettingController::class, 'page'])->name('settings.gemini-settings');
        Route::post('gemini-settings/store', [SettingController::class, 'geminiSettingsStore'])->name('settings.gemini-settings.store');
        Route::post('gemini-settings/clear-cache', [SettingController::class, 'geminiClearCache'])->name('settings.gemini-settings.clear-cache');
        Route::post('gemini-settings/fetch-models', [SettingController::class, 'geminiModelsList'])->name('settings.gemini-settings.fetch-models');
        Route::get('reel-settings', [SettingController::class, 'page'])->name('settings.reel-settings');
        Route::post('reel-settings/store', [SettingController::class, 'reelSettingsStore'])->name('settings.reel-settings.store');
    });
    Route::group(['prefix' => 'system-update'], static function () {
        Route::get('/', [SystemUpdateController::class, 'index'])->name('system-update.index');
        Route::post('/', [SystemUpdateController::class, 'update'])->name('system-update.update');
    });
    Route::get('reset-purchase-code', [SystemUpdateController::class, 'resetPurchaseCode'])->name('system-update.reset-purchase-code');
    /*** Setting Module : END ***/

    /*** Plugin Manager : START ***/
    Route::group(['prefix' => 'plugin-manager'], static function () {
        Route::get('/', [PluginManagerController::class, 'index'])->name('plugin-manager.index');
        Route::get('installed', [PluginManagerController::class, 'installed'])->name('plugin-manager.installed');
        Route::get('install/{slug?}', [PluginManagerController::class, 'installForm'])->name('plugin-manager.install-form');
        Route::post('install', [PluginManagerController::class, 'install'])->name('plugin-manager.install');
        Route::post('{slug}/toggle', [PluginManagerController::class, 'toggleEnabled'])->name('plugin-manager.toggle');
        Route::post('refresh-catalog', [PluginManagerController::class, 'refreshCatalog'])
            ->middleware('throttle:5,60')
            ->name('plugin-manager.refresh-catalog');
    });
    /*** Plugin Manager : END ***/

    /*** Gemini AI Generation : START ***/
    Route::post('gemini/generate-description', [GeminiAIController::class, 'generateDescription'])->name('gemini.generate-description');
    Route::post('gemini/generate-meta', [GeminiAIController::class, 'generateMetaDetails'])->name('gemini.generate-meta');
    Route::post('gemini/auto-translate', [GeminiAIController::class, 'autoTranslate'])->name('gemini.auto-translate');
    /*** Gemini AI Generation : END ***/

    /*** Language Module : START ***/
    Route::group(['prefix' => 'language'], static function () {
        Route::get('set-language/{lang}', [LanguageController::class, 'setLanguage'])->name('language.set-current');
        Route::put('/language/update/{id}/{type}', [LanguageController::class, 'updatelanguage'])->name('updatelanguage');
        Route::get('languageedit/{id}/{type}', [LanguageController::class, 'editLanguage'])->name('languageedit');
    });
    Route::post('/language/set-default', [LanguageController::class, 'setDefaultLanguage'])
        ->name('settings.set-default-language');
    Route::resource('language', LanguageController::class);
    Route::get('language/{id}/download/{type}', [LanguageController::class, 'downloadJson'])->name('language.download.json');

    /*** Language Module : END ***/

    Route::resource('seo-setting', SeoSettingController::class);

    /*** User Module : START ***/
    Route::group(['prefix' => 'staff'], static function () {
        Route::put('/{id}/change-password', [StaffController::class, 'changePassword'])->name('staff.change-password');
    });
    Route::resource('staff', StaffController::class);

    /*** User Module : END ***/

    /*** Customer Module : START ***/
    Route::group(['prefix' => 'customer'], static function () {
        Route::post('/assign-package', [CustomersController::class, 'assignPackage'])->name('customer.assign.package');
        Route::get('/active-packages', [CustomersController::class, 'getActivePackages'])->name('customer.active.packages');
        Route::post('/cancel-package', [CustomersController::class, 'cancelPackage'])->name('customer.cancel.package');
    });
    Route::resource('customer', CustomersController::class);

    /*** Customer Module : END ***/

    /*** Slider Module : START ***/
    Route::resource('slider', SliderController::class);
    /*** Slider Module : END ***/

    /*** Banner Ad Module : START ***/
    Route::get('banner-ad/items-by-category', [BannerAdController::class, 'itemsByCategory'])->name('banner-ad.items-by-category');
    Route::get('banner-ad/existing-banners', [BannerAdController::class, 'existingBanners'])->name('banner-ad.existing-banners');
    Route::resource('banner-ad', BannerAdController::class);
    /*** Banner Ad Module : END ***/

    /*** Package Module : STARTS ***/
    Route::group(['prefix' => 'users-packages'], static function () {
        Route::get('/', [PackageController::class, 'userPackagesIndex'])->name('package.users.index');
        Route::get('show', [PackageController::class, 'userPackagesShow'])->name('package.users.show');
    });

    Route::group(['prefix' => 'payment-transactions'], static function () {
        Route::get('/', [PackageController::class, 'paymentTransactionIndex'])->name('package.payment-transactions.index');
        Route::get('show', [PackageController::class, 'paymentTransactionShow'])->name('package.payment-transactions.show');
        Route::get('/{id}/receipt', [PackageController::class, 'viewReceipt'])->name('package.payment-transactions.receipt');
    });
    
    Route::group(['prefix' => 'bank-transfer'], static function () {
        Route::get('/', [PackageController::class, 'bankTransferIndex'])->name('package.bank-transfer.index');
        Route::get('/show', [PackageController::class, 'bankTransferShow'])->name('package.bank-transfer.show');
        Route::put('/{id}/update', [PackageController::class, 'updateStatus'])->name('package.bank-transfer.update-status');
    });
    Route::resource('package', PackageController::class);
    Route::put('package/{id}/discontinue', [PackageController::class, 'discontinue'])->name('package.discontinue');
    /*** Package Module : ENDS ***/

    /*** Report Reason Module : START ***/
    // Route::group(['prefix' => 'report-reasons'], static function () {
    // });
    Route::get('user-report', [ReportReasonController::class, 'usersReports'])->name('report-reasons.user-reports.index');
    Route::get('user-report/show', [ReportReasonController::class, 'userReportsShow'])->name('report-reasons.user-reports.show');
    Route::resource('report-reasons', ReportReasonController::class);
    /*** Report Reason Module : END ***/

    /*** Notification Module : START ***/
    Route::group(['prefix' => 'notification'], static function () {
        Route::delete('/batch-delete', [NotificationController::class, 'batchDelete'])->name('notification.batch.delete');
    });
    Route::resource('notification', NotificationController::class);
    /*** Notification Module : END ***/

    /*** Feature Section Module : START ***/
    Route::get('feature-section/items-by-category', [FeatureSectionController::class, 'itemsByCategory'])->name('feature-section.items-by-category');
    Route::get('feature-section/items-by-ids', [FeatureSectionController::class, 'itemsByIds'])->name('feature-section.items-by-ids');
    Route::resource('feature-section', FeatureSectionController::class);
    /*** Feature Section Module : END ***/

    /*** Home Screen Section Module : START ***/
    Route::group(['prefix' => 'home-screen-sections'], static function () {
        Route::get('/', [HomeScreenSectionController::class, 'index'])->name('home-screen-section.index');
        Route::post('/toggle', [HomeScreenSectionController::class, 'toggleSection'])->name('home-screen-section.toggle');
        Route::get('/popular-categories/list', [HomeScreenSectionController::class, 'popularCategoriesList'])->name('home-screen-section.popular-categories.list');
        Route::post('/popular-categories', [HomeScreenSectionController::class, 'storePopularCategory'])->name('home-screen-section.popular-categories.store');
        Route::delete('/popular-categories/{id}', [HomeScreenSectionController::class, 'deletePopularCategory'])->name('home-screen-section.popular-categories.delete');
        Route::get('/popular-categories/order', [HomeScreenSectionController::class, 'popularCategoriesOrder'])->name('home-screen-section.popular-categories.order');
        Route::post('/popular-categories/change-order', [HomeScreenSectionController::class, 'updatePopularCategoryOrder'])->name('home-screen-section.popular-categories.change-order');
    });
    /*** Home Screen Section Module : END ***/

    /*** Roles Module : END ***/
    Route::get('/roles-list', [RoleController::class, 'list'])->name('roles.list');
    Route::resource('roles', RoleController::class);
    /*** Roles Module : END ***/

    /*** Tips Module : END ***/
    Route::resource('tips', TipController::class);
    /*** Tips Module : END ***/

    /*** Blog Module : END ***/
    Route::resource('blog', BlogController::class);
    Route::resource('blog-category', BlogCategoryController::class);
    /*** Blog Module : END ***/

    Route::resource('faq', FaqController::class);

    Route::resource('currency', CurrencyController::class);
    Route::get('currency/country/{country}/data', [CurrencyController::class, 'getCountryCurrencyData'])->name('currency.country-data');

    Route::group(['prefix' => 'countries'], static function () {
        Route::get('/', [PlaceController::class, 'countryIndex'])->name('countries.index');
        Route::get('/show', [PlaceController::class, 'countryShow'])->name('countries.show');
        Route::post('/import', [PlaceController::class, 'importCountry'])->name('countries.import');
        Route::delete('/{id}/delete', [PlaceController::class, 'destroyCountry'])->name('countries.destroy');
        Route::get('/country-translation', [PlaceController::class, 'showCountryTranslations'])->name('countries.translation');
        Route::put('/update-country-translation', [PlaceController::class, 'updateCountriesTranslations'])->name('countries.translation.update');
    });

    Route::group(['prefix' => 'states'], static function () {
        Route::get('/', [PlaceController::class, 'stateIndex'])->name('states.index');
        Route::get('/show', [PlaceController::class, 'stateShow'])->name('states.show');
        Route::get('/search', [PlaceController::class, 'stateSearch'])->name('states.search');
        Route::delete('/{id}/delete', [PlaceController::class, 'destroyState'])->name('states.destroy');
        Route::get('/state-translation', [PlaceController::class, 'showStatesTranslations'])->name('states.translation');
        Route::put('/update-state-translation', [PlaceController::class, 'updateStatesTranslations'])->name('states.translation.update');
    });

    Route::group(['prefix' => 'cities'], static function () {
        Route::get('/', [PlaceController::class, 'cityIndex'])->name('cities.index');
        Route::get('/show', [PlaceController::class, 'cityShow'])->name('cities.show');
        Route::get('/search', [PlaceController::class, 'citySearch'])->name('cities.search');
        Route::get('/city-translation', [PlaceController::class, 'showCitiesTranslations'])->name('cities.translation');
        Route::put('/update-city-translation', [PlaceController::class, 'updateCitiesTranslations'])->name('cities.translation.update');
    });
    Route::get('/city-translations/{state}', [PlaceController::class, 'loadStateCities']);
    /*** Area Module : START ***/
    Route::group(['prefix' => 'area'], static function () {
        Route::get('/', [PlaceController::class, 'createArea'])->name('area.index');
        Route::post('/create', [PlaceController::class, 'addArea'])->name('area.create');
        Route::get('/show/{id}', [PlaceController::class, 'areaShow'])->name('area.show');
        Route::put('/{id}/update-area', [PlaceController::class, 'updateArea'])->name('area.update');
        Route::delete('/{id}/delete-area', [PlaceController::class, 'destroyArea'])->name('area.destroy');
        Route::post('/create-city', [PlaceController::class, 'addCity'])->name('city.create');
        Route::put('/{id}/update', [PlaceController::class, 'updateCity'])->name('city.update');
        Route::delete('/{id}/delete', [PlaceController::class, 'destroyCity'])->name('city.destroy');
    });
    Route::group(['prefix' => 'contact-us'], static function () {
        Route::get('/', [Controller::class, 'contactUsUIndex'])->name('contact-us.index');
        Route::get('/show', [Controller::class, 'contactUsShow'])->name('contact-us.show');
    });
    /*** Area Module : END ***/
});
// Area Translation Routes
Route::get('/area-translations', [PlaceController::class, 'areaTranslation'])->name('areas.translation');
Route::get('/area-translations/{city}', [PlaceController::class, 'loadCityAreas']);
Route::put('/area-translations/update', [PlaceController::class, 'updateAreasTranslations'])->name('areas.translation.update');

Route::get('/product-details/{slug}', [SettingController::class, 'webPageURL'])->name('deep-link');

// Apache rewrites /storage/{path}?w=..&h=..&q=.. straight to index.php, keeping the original URI.
// Nginx instead rewrites to /image-resize/{path}, so both prefixes are routed here.
Route::get('/storage/{path}', [ImageResizeController::class, 'resize'])
    ->where('path', '.*')
    ->name('image-resize.storage');
Route::get('/image-resize/{path}', [ImageResizeController::class, 'resize'])
    ->where('path', '.*')
    ->name('image-resize');



/** 
 * COMMANDS
*/

// // Migrate
// Route::get('/migrate', static function () {
//     Artisan::call('migrate');
//     $output = Artisan::output();
//     echo nl2br($output); // Convert newlines to <br> for better readability in HTML
// });

// // Migrate Status
// Route::get('migrate-status', function () {
//     Artisan::call('migrate:status');
//     $output = Artisan::output();
//     echo nl2br($output); // Convert newlines to <br> for better readability in HTML
// });

// // Migrate Rollback
// Route::get('/migrate-rollback', static function () {
//     Artisan::call('migrate:rollback');
//     echo 'done';
// });

// // Seeder
// Route::get('/seeder', static function () {
//     Artisan::call('db:seed --class=SystemUpgradeSeeder');
//     return redirect()->back();
// });

// Clear Cache
Route::get('clear', static function () {
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('optimize:clear');
    Artisan::call('debugbar:clear');
    return redirect()->back();
});

// Storage Link
// Route::get('storage-link', static function () {
//     Artisan::call('storage:link');
// });

// Auto Translate
Route::get('auto-translate/{id}/{type}/{locale}', function ($id, $type, $locale) {
    // Log when job is triggered
    Log::info('Auto-translate started', [
        'id' => $id,
        'type' => $type,
        'locale' => $locale,
    ]);

    // Build artisan command with arguments
    $artisan = base_path('artisan');
    $command = "php {$artisan} custom:translate-missing {$type} {$locale} >> " . storage_path('logs/translate.log') . ' 2>&1 &';

    // Run command in background
    exec($command);

    // Log immediately after dispatching
    Log::info('Auto-translate command dispatched', [
        'id' => $id,
        'type' => $type,
        'locale' => $locale,
    ]);

    return redirect()->route('languageedit', ['id' => $id, 'type' => $type])
        ->with('success', 'Auto translation started in background.');
})->name('auto-translate');

// Run Scheduler
Route::get('/run-scheduler', function () {
    // Use atomic lock to prevent overlaps
    $lock = Cache::lock('scheduler_running', 60); // lock for 60 seconds

    if (!$lock->get()) {
        return response()->json(['status' => 'Already processing']);
    }

    try {
        Artisan::call('schedule:run', ['--quiet' => true]);
        return response()->json(['status' => 'Scheduler processed']);
    } finally {
        optional($lock)->release();
    }
});

// // Debug queue endpoint (remove in production or add authentication)
// Route::get('/debug-queue', function () {
//     $queueConnection = config('queue.default');
//     $jobsTableExists = Schema::hasTable('jobs');
//     $failedJobsTableExists = Schema::hasTable('failed_jobs');

//     $pendingJobs = 0;
//     $failedJobs = 0;

//     if ($jobsTableExists) {
//         $pendingJobs = DB::table('jobs')->count();
//     }

//     if ($failedJobsTableExists) {
//         $failedJobs = DB::table('failed_jobs')->count();
//     }

//     // Try to run queue:work once to see if it processes jobs
//     $queueOutput = '';
//     try {
//         Artisan::call('queue:work', [
//             '--once' => true,
//             '--tries' => 1,
//             '--timeout' => 10
//         ]);
//         $queueOutput = Artisan::output();
//     } catch (\Exception $e) {
//         $queueOutput = 'Error: ' . $e->getMessage();
//     }

//     return response()->json([
//         'queue_connection' => $queueConnection,
//         'jobs_table_exists' => $jobsTableExists,
//         'failed_jobs_table_exists' => $failedJobsTableExists,
//         'pending_jobs' => $pendingJobs,
//         'failed_jobs' => $failedJobs,
//         'queue_work_output' => $queueOutput,
//         'env_queue_connection' => env('QUEUE_CONNECTION', 'not set'),
//     ]);
// });
