<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ReelApiController;
use App\Http\Controllers\Api\BlogApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\FeaturedSectionApiController;
use App\Http\Controllers\Api\GeneralApiController;
use App\Http\Controllers\Api\HomeScreenApiController;
use App\Http\Controllers\Api\ItemApiController;
use App\Http\Controllers\Api\JobApiController;
use App\Http\Controllers\Api\LocationApiController;
use App\Http\Controllers\Api\PackageApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\ReferralApiController;
use App\Http\Controllers\Api\ReviewApiController;
use App\Http\Controllers\Api\SettingsApiController;
use App\Http\Controllers\Api\OfferApiController;
use App\Http\Controllers\Api\SellerPromotionApiController;
use App\Http\Controllers\Api\SellerQrApiController;
use App\Http\Controllers\Api\SocialApiController;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\VerificationApiController;
use App\Http\Controllers\Api\ImageEditorApiController;
use App\Http\Controllers\GeminiAIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/* Authenticated Routes */
Route::group(['middleware' => ['auth:sanctum']], static function () {

    /* Auth Module */
    Route::post('reset-password', [AuthApiController::class, 'resetPassword']);
    Route::post('logout', [AuthApiController::class, 'logout']);
    Route::delete('delete-user', [AuthApiController::class, 'deleteUser']);

    /* User Module */
    Route::post('update-profile', [UserApiController::class, 'updateProfile']);
    Route::get('get-user-info', [UserApiController::class, 'getUser']);
    Route::get('get-notification-list', [UserApiController::class, 'getNotificationList']);

    /* Store Module */
    Route::post('setup-store', [StoreApiController::class, 'setupStore']);
    Route::get('get-my-store', [StoreApiController::class, 'getMyStore']);
    Route::post('toggle-store-status', [StoreApiController::class, 'toggleStoreStatus']);

    /* Seller QR Code Module (Authenticated Seller) */
    Route::group(['prefix' => 'seller-qr'], static function () {
        Route::get('eligibility', [SellerQrApiController::class, 'checkEligibility']);
        Route::get('my-qr', [SellerQrApiController::class, 'getMyQr']);
        Route::post('generate-or-update', [SellerQrApiController::class, 'generateOrUpdate']);
    });

    /* Item Module */
    Route::get('my-items', [ItemApiController::class, 'getMyItems']);
    Route::post('add-item', [ItemApiController::class, 'addItem']);
    Route::post('update-item', [ItemApiController::class, 'updateItem']);
    Route::post('delete-item', [ItemApiController::class, 'deleteItem']);
    Route::post('update-item-status', [ItemApiController::class, 'updateItemStatus']);
    Route::get('item-buyer-list', [ItemApiController::class, 'getItemBuyerList']);
    Route::post('renew-item', [ItemApiController::class, 'renewItem']);
    Route::post('make-item-featured', [ItemApiController::class, 'makeFeaturedItem']);
    Route::post('manage-favourite', [ItemApiController::class, 'manageFavourite']);
    Route::get('get-favourite-item', [ItemApiController::class, 'getFavouriteItem']);
    Route::get('get-limits', [ItemApiController::class, 'getLimits']);
    Route::get('get-item-status', [ItemApiController::class, 'getItemStatus']);
    Route::post('upload-media', [ItemApiController::class, 'uploadMedia']);
    Route::get('my-purchased-items', [ItemApiController::class, 'getPurchasedItemsForReview']);

    /* Review Module */
    Route::post('add-item-review', [ReviewApiController::class, 'addItemReview']);
    Route::get('my-review', [ReviewApiController::class, 'getMyReview']);
    Route::post('add-review-report', [ReviewApiController::class, 'addReviewReport']);

    /* Package Module */
    Route::get('get-user-purchased-packages', [PackageApiController::class, 'getUserPurchasedPackages']);
    Route::post('assign-free-package', [PackageApiController::class, 'assignFreePackage']);

    /* Payment Module */
    Route::get('get-payment-settings', [PaymentApiController::class, 'getPaymentSettings']);
    Route::post('payment-intent', [PaymentApiController::class, 'getPaymentIntent']);
    Route::get('payment-transactions', [PaymentApiController::class, 'getPaymentTransactions']);
    Route::post('in-app-purchase', [PaymentApiController::class, 'inAppPurchase']);
    Route::post('bank-transfer-update', [PaymentApiController::class, 'bankTransferUpdate']);
    Route::get('get-payment-receipt', [PaymentApiController::class, 'getPaymentReceipt']);
    Route::post('make-payment-transaction-fail', [PaymentApiController::class, 'makePaymentTransactionFail']);

    /* General Module */
    Route::get('get-enabled-plugins', [GeneralApiController::class, 'getEnabledPlugins']);

    /* Referral Module */
    // Route::get('calculate-referral-points-for-package', [ReferralApiController::class, 'calculateReferralPointsForPackage']);
    // Route::get('refer-points-balance', [ReferralApiController::class, 'getReferPointsBalance']);
    // Route::get('refer-points-history', [ReferralApiController::class, 'getReferPointsHistory']);
    // Route::get('referral-code', [ReferralApiController::class, 'getReferralCode']);

    /* Chat Module */
    Route::post('item-offer', [ChatApiController::class, 'createItemOffer']);
    Route::get('item-offer-list', [ChatApiController::class, 'getItemOfferList']);
    Route::get('chat-list', [ChatApiController::class, 'getChatList']);
    Route::post('send-message', [ChatApiController::class, 'sendMessage']);
    Route::get('chat-messages', [ChatApiController::class, 'getChatMessages']);
    Route::post('delete-chat', [ChatApiController::class, 'deleteChat']);
    Route::post('delete-chat-messages', [ChatApiController::class, 'deleteChatMessages']);
    Route::get('chat-template-questions', [ChatApiController::class, 'getChatTemplateQuestions']);

    /* Social Module (Block + Follow) */
    Route::post('block-user', [SocialApiController::class, 'blockUser']);
    Route::post('unblock-user', [SocialApiController::class, 'unblockUser']);
    Route::get('blocked-users', [SocialApiController::class, 'getBlockedUsers']);
    Route::post('follow-user', [SocialApiController::class, 'followUser']);
    Route::post('unfollow-user', [SocialApiController::class, 'unfollowUser']);

    /* Verification Module */
    Route::get('verification-fields', [VerificationApiController::class, 'getVerificationFields']);
    Route::post('send-verification-request', [VerificationApiController::class, 'sendVerificationRequest']);
    Route::get('verification-request', [VerificationApiController::class, 'getVerificationRequest']);

    /* Job Module */
    Route::post('job-apply', [JobApiController::class, 'applyJob']);
    Route::get('get-job-applications', [JobApiController::class, 'recruiterApplications']);
    Route::get('my-job-applications', [JobApiController::class, 'myJobApplications']);
    Route::post('update-job-applications-status', [JobApiController::class, 'updateJobStatus']);

    /* Settings Module (auth) */
    Route::post('add-reports', [SettingsApiController::class, 'addReports']);

    /* Blog Feedback Module */
    Route::post('set-blog-feedback', [BlogApiController::class, 'setBlogFeedback']);

    /* Reel Module */
    Route::post('manage-reel-like', [ReelApiController::class, 'manageReelLike']);
    Route::get('get-liked-reels', [ReelApiController::class, 'getLikedReels']);
    Route::get('get-my-reels', [ReelApiController::class, 'getMyReels']);

    /** Gemini AI */
    Route::group(['prefix' => 'gemini'], function () {
        Route::post('generate-description', [GeminiAIController::class, 'generateDescription']);
        Route::post('generate-meta', [GeminiAIController::class, 'generateMetaDetails']);
    });

    /* Promotions & Campaigns (Seller / Authenticated) */
    Route::group(['prefix' => 'seller/promotions'], static function () {
        Route::get('available', [SellerPromotionApiController::class, 'getAvailablePromotions']);
        Route::get('my-items', [SellerPromotionApiController::class, 'getMyPromotionItems']);
        Route::get('analytics', [SellerPromotionApiController::class, 'getPromotionsAnalytics']);
        Route::get('history', [SellerPromotionApiController::class, 'getPromotionsHistory']);
        Route::post('add-item', [SellerPromotionApiController::class, 'addPromotionItem']);
        Route::post('update-item', [SellerPromotionApiController::class, 'updatePromotionItem']);
        Route::post('toggle-item-status', [SellerPromotionApiController::class, 'togglePromotionItemStatus']);
        Route::post('delete-item', [SellerPromotionApiController::class, 'deletePromotionItem']);
    });

    /* Promote this Ad (Daily Bump Up, Top Ad, Spotlight) */
    Route::group(['prefix' => 'seller/items'], static function () {
        Route::get('promotion-options', [SellerPromotionApiController::class, 'getAdPromotionOptions']);
        Route::post('promote', [SellerPromotionApiController::class, 'promoteAd']);
    });
});

/* Non-Authenticated Routes */

/* Auth Module */
Route::post('user-signup', [AuthApiController::class, 'userSignup']);
Route::get('user-exists', [AuthApiController::class, 'userExists']);
Route::get('get-otp', [AuthApiController::class, 'getOtp']);
Route::get('verify-otp', [AuthApiController::class, 'verifyOtp']);

/* User Module */
Route::get('get-seller', [UserApiController::class, 'getSeller']);
Route::get('get-seller-slug', [UserApiController::class, 'getSellerSlug']);

/* Store Module */
Route::get('get-stores', [StoreApiController::class, 'getStores']);
Route::get('get-store-detail', [StoreApiController::class, 'getStoreDetail']);
Route::get('get-store-slugs', [StoreApiController::class, 'getStoreSlugs']);

/* Seller QR Code Module (Public Scan & Settings) */
Route::get('seller-qr/store/{identifier}', [SellerQrApiController::class, 'getStoreByQr']);
Route::get('seller-qr/settings', [SellerQrApiController::class, 'getSettings']);
Route::get('seller-qr/download', [SellerQrApiController::class, 'downloadStandee']);

/* Social Module */
Route::get('followers', [SocialApiController::class, 'getFollowers']);
Route::get('following', [SocialApiController::class, 'getFollowing']);

/* Category Module */
Route::get('get-parent-categories', [CategoryApiController::class, 'getParentCategoryTree']);
Route::get('get-categories', [CategoryApiController::class, 'getSubCategories']);
// Route::get('get-categories-demo', [CategoryApiController::class, 'getCategories']);
Route::get('get-categories-slug', [CategoryApiController::class, 'getCategoriesSlug']);
Route::get('get-customfields', [CategoryApiController::class, 'getCustomFields']);

/* Settings Module */
Route::get('get-system-settings', [SettingsApiController::class, 'getSystemSettings']);
Route::get('seo-settings', [SettingsApiController::class, 'seoSettings']);
Route::get('get-currencies', [SettingsApiController::class, 'getCurrencies']);
Route::get('get-languages', [SettingsApiController::class, 'getLanguages']);
Route::get('get-system-languages-codes', [SettingsApiController::class, 'getSystemLanguagesCodes']);
Route::get('get-slider', [SettingsApiController::class, 'getSlider']);
Route::get('get-report-reasons', [SettingsApiController::class, 'getReportReasons']);
Route::get('faq', [SettingsApiController::class, 'getFaqs']);
Route::get('tips', [SettingsApiController::class, 'getTips']);
Route::post('contact-us', [SettingsApiController::class, 'storeContactUs']);

/* Package Module */
Route::get('get-package', [PackageApiController::class, 'getPackage']);
// Route::get('app-payment-status', [PackageApiController::class, 'appPaymentStatus']);

/* Reel Module (public) */
Route::get('get-reels', [ReelApiController::class, 'getReels']);

/* Item Module */
Route::get('get-item-list', [ItemApiController::class, 'getItemList']);
Route::get('get-item-slug', [ItemApiController::class, 'getItemSlugs']);

/* Blog Module */
Route::get('blogs', [BlogApiController::class, 'getBlog']);
Route::get('blog-tags', [BlogApiController::class, 'getAllBlogTags']);
Route::get('get-blogs-slug', [BlogApiController::class, 'getBlogsSlug']);
Route::get('get-blog-categories', [BlogApiController::class, 'getBlogCategories']);
Route::get('get-blog-categories-slug', [BlogApiController::class, 'getBlogCategoriesSlug']);
Route::get('get-popular-blogs', [BlogApiController::class, 'getPopularBlogs']);

/* Location Module */
Route::get('countries', [LocationApiController::class, 'getCountries']);
Route::get('states', [LocationApiController::class, 'getStates']);
Route::get('cities', [LocationApiController::class, 'getCities']);
Route::get('areas', [LocationApiController::class, 'getAreas']);
Route::get('get-location', [LocationApiController::class, 'getLocationFromCoordinates']);

/* Featured Section Module */
Route::get('get-featured-section', [FeaturedSectionApiController::class, 'getFeaturedSection']);
Route::get('get-featured-section-slug', [FeaturedSectionApiController::class, 'getFeatureSectionSlug']);
Route::get('get-featured-categories', [FeaturedSectionApiController::class, 'getFeaturedCategories']);

/* Home Screen Module */
Route::get('get-home-screen', [HomeScreenApiController::class, 'getHomeScreen']);
Route::get('get-popular-categories', [HomeScreenApiController::class, 'getPopularCategories']);


/** General APIs */
// Banner API
Route::get('get-banner-ads', [GeneralApiController::class, 'getBannerAds']);

/* Offers, Campaigns & Promotions Module (Public) */
Route::group(['prefix' => 'offers'], static function () {
    Route::get('campaigns', [OfferApiController::class, 'getCampaigns']);
    Route::get('campaign-detail', [OfferApiController::class, 'getCampaignDetail']);
    Route::get('promotions', [OfferApiController::class, 'getPromotions']);
    Route::get('promotion-detail', [OfferApiController::class, 'getPromotionDetail']);
    Route::get('promotion-items', [OfferApiController::class, 'getPromotionItems']);
    Route::get('flash-sales', [OfferApiController::class, 'getFlashSales']);
    Route::get('clearance-sales', [OfferApiController::class, 'getClearanceSales']);
    Route::get('deals-of-the-day', [OfferApiController::class, 'getDealsOfTheDay']);
    Route::get('spotlight-ads', [OfferApiController::class, 'getSpotlightAds']);
});

/* Image Editor & Rich Text Content Formatter Module */
Route::group(['prefix' => 'image'], static function () {
    Route::post('edit', [ImageEditorApiController::class, 'edit']);
    Route::post('preview', [ImageEditorApiController::class, 'preview']);
    Route::middleware('auth:sanctum')->group(static function () {
        Route::get('history', [ImageEditorApiController::class, 'history']);
        Route::delete('{id}', [ImageEditorApiController::class, 'delete']);
    });
});

Route::post('format-content', [ImageEditorApiController::class, 'formatContent']);
Route::post('editor/upload-image', [ImageEditorApiController::class, 'uploadEditorImage']);
