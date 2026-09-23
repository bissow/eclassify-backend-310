<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ItemApiResource;
use App\Models\Category;
use App\Models\Item;
use App\Models\SellerQrCode;
use App\Models\Store;
use App\Services\ResponseService;
use App\Services\SellerQrCodeService;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * @tags Seller QR Code
 */
class SellerQrApiController extends BaseApiController
{
    /**
     * Check if authenticated seller is eligible to use Seller QR Code feature
     */
    public function checkEligibility(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ResponseService::errorResponse(__('User not authenticated'), null, 401);
            }

            $eligibility = SellerQrCodeService::checkUserEligibility($user);

            $data = [
                'eligible'             => $eligibility['eligible'],
                'is_eligible'          => $eligibility['is_eligible'],
                'has_package'          => $eligibility['has_package'],
                'allows_seller_qr_code'=> $eligibility['eligible'],
                'package'              => $eligibility['active_package'] ? [
                    'id'                    => $eligibility['active_package']->id,
                    'name'                  => $eligibility['active_package']->translated_name,
                    'type'                  => $eligibility['active_package']->type,
                    'allows_seller_qr_code' => (bool)$eligibility['active_package']->allows_seller_qr_code,
                ] : null,
                'has_store'            => $eligibility['has_store'],
                'store'                => $eligibility['store'] ? [
                    'id'          => $eligibility['store']->id,
                    'name'        => $eligibility['store']->name,
                    'slug'        => $eligibility['store']->slug,
                    'is_verified' => (bool)$eligibility['store']->is_verified,
                    'logo'        => $eligibility['store']->logo,
                ] : null,
                'can_customize_logo'   => $eligibility['can_customize_logo'],
                'can_customize_colors' => $eligibility['can_customize_colors'],
                'message'              => $eligibility['message'],
            ];

            return ResponseService::successResponse(__('Eligibility status fetched successfully'), $data);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerQrApiController -> checkEligibility');
            return ResponseService::errorResponse();
        }
    }

    /**
     * Get authenticated seller's existing QR Code or default setup
     */
    public function getMyQr(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ResponseService::errorResponse(__('User not authenticated'), null, 401);
            }

            $eligibility = SellerQrCodeService::checkUserEligibility($user);
            $settings = SellerQrCodeService::getEffectiveSettings();

            $qrCode = SellerQrCode::with(['store'])->where('user_id', $user->id)->first();
            $store = $user->store;

            if (!$qrCode) {
                // If user is eligible and has a store, automatically initialize a default QR code
                if ($store && ($eligibility['is_eligible'] ?? false)) {
                    $qrCode = SellerQrCode::create([
                        'user_id'          => $user->id,
                        'store_id'         => $store->id,
                        'qr_code_token'    => SellerQrCodeService::generateQrToken($user, $store),
                        'title'            => $store->name ?: $settings['default_title'],
                        'tagline'          => $settings['default_tagline'],
                        'qr_style'         => 'standee',
                        'primary_color'    => $settings['primary_color'],
                        'secondary_color'  => $settings['secondary_color'],
                        'center_logo_type' => $settings['default_center_logo_type'],
                        'center_logo'      => null,
                        'is_active'        => true,
                    ]);
                } else {
                    return ResponseService::successResponse(__('No QR code generated yet'), [
                        'has_qr'               => false,
                        'eligible'             => $eligibility['eligible'],
                        'is_eligible'          => $eligibility['is_eligible'],
                        'can_customize_colors' => (bool)$settings['allow_user_customization'],
                        'can_customize_logo'   => (bool)$settings['allow_user_logo'],
                        'can_customize_slug'   => (bool)$settings['allow_user_customization'],
                        'catalog_base_url'     => $settings['catalog_base_url'] ?: url('/'),
                        'qr_code'              => null,
                        'default_settings'     => $settings,
                        'store'                => $store ? [
                            'id'          => $store->id,
                            'name'        => $store->name,
                            'slug'        => $store->slug,
                            'logo'        => $store->logo,
                            'is_verified' => (bool)$store->is_verified,
                        ] : null,
                    ]);
                }
            }

            // Generate preview data URIs for fast app rendering
            $centerLogoPath = SellerQrCodeService::resolveCenterLogoPath($qrCode, $settings, $store);

            $rawSvg = SellerQrCodeService::generateRawQrSvg($qrCode->qr_url, $qrCode->primary_color, 320, $centerLogoPath);
            $qrBase64Svg = 'data:image/svg+xml;base64,' . base64_encode($rawSvg);

            $effectiveCenterLogoUrl = match ($qrCode->center_logo_type) {
                'store_logo'    => $store?->logo,
                'custom'        => $qrCode->center_logo_url,
                'none'          => null,
                default         => !empty($settings['center_logo_url']) ? $settings['center_logo_url'] : $settings['footer_logo_url'],
            };

            $downloadLinks = [
                'pdf_standee' => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=pdf&size=standee'),
                'pdf_a4'      => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=pdf&size=a4'),
                'pdf_a5'      => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=pdf&size=a5'),
                'svg'         => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=svg'),
                'png'         => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=png'),
            ];

            return ResponseService::successResponse(__('Seller QR code details fetched successfully'), [
                'has_qr'               => true,
                'eligible'             => $eligibility['eligible'],
                'is_eligible'          => $eligibility['is_eligible'],
                'can_customize_colors' => (bool)$settings['allow_user_customization'],
                'can_customize_logo'   => (bool)$settings['allow_user_logo'],
                'can_customize_slug'   => (bool)$settings['allow_user_customization'],
                'catalog_base_url'     => $settings['catalog_base_url'] ?: ($qrCode ? Str::before($qrCode->qr_url, '/store-qr/') : url('/')),
                'qr_code'              => [
                    'id'                        => $qrCode->id,
                    'user_id'                   => $qrCode->user_id,
                    'store_id'                  => $qrCode->store_id,
                    'token'                     => $qrCode->qr_code_token,
                    'qr_code_token'             => $qrCode->qr_code_token,
                    'custom_slug'               => $qrCode->qr_code_token,
                    'slug'                      => $qrCode->qr_code_token,
                    'title'                     => $qrCode->title ?: ($store ? $store->name : $settings['default_title']),
                    'tagline'                   => $qrCode->tagline ?: $settings['default_tagline'],
                    'custom_tagline'            => $qrCode->tagline ?: $settings['default_tagline'],
                    'qr_style'                  => $qrCode->qr_style,
                    'format'                    => $qrCode->qr_style,
                    'size'                      => 'standee',
                    'primary_color'             => $qrCode->primary_color,
                    'custom_color'              => $qrCode->primary_color,
                    'secondary_color'           => $qrCode->secondary_color,
                    'can_customize_colors'      => (bool)$settings['allow_user_customization'],
                    'can_customize_logo'        => (bool)$settings['allow_user_logo'],
                    'can_customize_slug'        => (bool)$settings['allow_user_customization'],
                    'catalog_base_url'          => $settings['catalog_base_url'] ?: ($qrCode ? Str::before($qrCode->qr_url, '/store-qr/') : url('/')),
                    'badge_text'                => $settings['badge_text'],
                    'default_footer_text'       => $settings['default_footer_text'],
                    'footer_logo_url'           => $settings['footer_logo_url'],
                    'center_logo_type'          => $qrCode->center_logo_type,
                    'center_logo'               => $qrCode->center_logo,
                    'center_logo_url'           => $qrCode->center_logo_url,
                    'effective_center_logo_url' => $effectiveCenterLogoUrl,
                    'qr_url'                    => $qrCode->qr_url,
                    'deep_link'                 => $qrCode->deep_link,
                    'scans_count'               => (int)$qrCode->scans_count,
                    'last_scanned_at'           => $qrCode->last_scanned_at?->toIso8601String(),
                    'is_active'                 => (bool)$qrCode->is_active,
                    'qr_base64_svg'             => $qrBase64Svg,
                    'svg_raw'                   => $rawSvg,
                    'raw_svg'                   => $rawSvg,
                    'qr_png_url'                => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=png'),
                    'qr_svg_url'                => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=svg'),
                    'store'                     => $store ? [
                        'id'          => $store->id,
                        'name'        => $store->name,
                        'slug'        => $store->slug,
                        'logo'        => $store->logo,
                        'city'        => $store->city,
                        'state'       => $store->state,
                        'is_verified' => (bool)$store->is_verified,
                    ] : null,
                ],
                'download_links'       => $downloadLinks,
                'default_settings'     => $settings,
                'store'                => $store ? [
                    'id'          => $store->id,
                    'name'        => $store->name,
                    'slug'        => $store->slug,
                    'logo'        => $store->logo,
                    'city'        => $store->city,
                    'state'       => $store->state,
                    'is_verified' => (bool)$store->is_verified,
                ] : null,
            ]);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerQrApiController -> getMyQr');
            return ResponseService::errorResponse();
        }
    }

    /**
     * Generate or Update Seller QR Code Standee Configuration
     */
    public function generateOrUpdate(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ResponseService::errorResponse(__('User not authenticated'), null, 401);
            }

            $eligibility = SellerQrCodeService::checkUserEligibility($user);
            if (!$eligibility['eligible']) {
                return ResponseService::errorResponse($eligibility['message'], null, 403);
            }

            $settings = SellerQrCodeService::getEffectiveSettings();

            $validator = Validator::make($request->all(), [
                'custom_slug'      => 'nullable|string|min:3|max:64|regex:/^[a-zA-Z0-9_-]+$/',
                'slug'             => 'nullable|string|min:3|max:64|regex:/^[a-zA-Z0-9_-]+$/',
                'title'            => 'nullable|string|max:191',
                'tagline'          => 'nullable|string|max:255',
                'qr_style'         => 'nullable|in:standee,standard,compact,card',
                'primary_color'    => ['nullable', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
                'secondary_color'  => ['nullable', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
                'center_logo_type' => 'nullable|in:store_logo,platform_logo,custom,none',
                'center_logo'      => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:3072',
            ]);

            if ($validator->fails()) {
                return ResponseService::validationError($validator->errors()->first());
            }

            $store = $user->store;
            $qrCode = SellerQrCode::where('user_id', $user->id)->first();

            // Custom Slug validation and uniqueness
            $inputSlug = $request->input('custom_slug', $request->input('slug'));
            $customSlug = null;
            if ($settings['allow_user_customization'] && !empty($inputSlug)) {
                $customSlug = Str::slug($inputSlug);
                if (strlen($customSlug) < 3) {
                    return ResponseService::validationError(__('Slug must be at least 3 characters.'));
                }

                $existingQuery = SellerQrCode::where('qr_code_token', $customSlug);
                if ($qrCode) {
                    $existingQuery->where('id', '!=', $qrCode->id);
                }
                if ($existingQuery->exists()) {
                    return ResponseService::validationError(__('This URL slug is already taken. Please choose a different slug.'));
                }
            }

            DB::beginTransaction();

            // Read input with compatibility aliases
            $inputTitle = $request->input('title', $request->input('custom_title'));
            $inputTagline = $request->input('tagline', $request->input('custom_tagline'));
            $inputColor = $request->input('primary_color', $request->input('custom_color'));
            $inputStyle = $request->input('qr_style', $request->input('format', 'standee'));

            $primaryColor = $settings['allow_user_customization'] && !empty($inputColor)
                ? $inputColor
                : ($qrCode ? $qrCode->primary_color : $settings['primary_color']);

            $secondaryColor = $settings['allow_user_customization'] && $request->filled('secondary_color')
                ? $request->secondary_color
                : ($qrCode ? $qrCode->secondary_color : $settings['secondary_color']);

            $title = $settings['allow_user_customization'] && !is_null($inputTitle)
                ? $inputTitle
                : ($qrCode ? $qrCode->title : ($store ? $store->name : $settings['default_title']));

            $tagline = $settings['allow_user_customization'] && !is_null($inputTagline)
                ? $inputTagline
                : ($qrCode ? $qrCode->tagline : $settings['default_tagline']);

            $defaultCenterLogoType = $settings['default_center_logo_type'] ?? 'platform_logo';
            $centerLogoType = $request->input('center_logo_type', $qrCode ? $qrCode->center_logo_type : $defaultCenterLogoType);
            if (!$settings['allow_user_logo'] && $centerLogoType === 'custom') {
                $centerLogoType = 'platform_logo';
            }

            $centerLogoPath = $qrCode ? $qrCode->getRawOriginal('center_logo') : null;
            if ($request->hasFile('center_logo') && $settings['allow_user_logo']) {
                $centerLogoPath = SellerQrCodeService::handleCenterLogoUpload($request->file('center_logo'), $qrCode);
                $centerLogoType = 'custom';
            }

            $data = [
                'user_id'          => $user->id,
                'store_id'         => $store?->id,
                'title'            => $title,
                'tagline'          => $tagline,
                'qr_style'         => $inputStyle ?: 'standee',
                'primary_color'    => $primaryColor,
                'secondary_color'  => $secondaryColor,
                'center_logo_type' => $centerLogoType,
                'center_logo'      => $centerLogoPath,
                'is_active'        => true,
            ];

            if ($customSlug) {
                $data['qr_code_token'] = $customSlug;
            }

            if ($qrCode) {
                $qrCode->update($data);
            } else {
                if (empty($data['qr_code_token'])) {
                    $data['qr_code_token'] = SellerQrCodeService::generateQrToken($user, $store);
                }
                $qrCode = SellerQrCode::create($data);
            }

            DB::commit();

            $centerLogoPath = null;
            if ($qrCode->center_logo_type === 'custom' && !empty($qrCode->center_logo)) {
                $centerLogoPath = SellerQrCodeService::resolveLocalImagePath($qrCode->getRawOriginal('center_logo'));
            } elseif ($qrCode->center_logo_type === 'store_logo' && $store && !empty($store->logo)) {
                $centerLogoPath = SellerQrCodeService::resolveLocalImagePath($store->getRawOriginal('logo'));
            } elseif ($qrCode->center_logo_type === 'platform_logo') {
                $adminLogo = !empty($settings['center_logo']) ? $settings['center_logo'] : $settings['footer_logo'];
                $centerLogoPath = SellerQrCodeService::resolveLocalImagePath($adminLogo);
            }

            $rawSvg = SellerQrCodeService::generateRawQrSvg($qrCode->qr_url, $qrCode->primary_color, 320, $centerLogoPath);
            $qrBase64Svg = 'data:image/svg+xml;base64,' . base64_encode($rawSvg);

            $effectiveCenterLogoUrl = match ($qrCode->center_logo_type) {
                'store_logo'    => $store?->logo,
                'custom'        => $qrCode->center_logo_url,
                'none'          => null,
                default         => !empty($settings['center_logo_url']) ? $settings['center_logo_url'] : $settings['footer_logo_url'],
            };

            return ResponseService::successResponse(__('Seller QR Code generated successfully'), [
                'can_customize_colors' => (bool)$settings['allow_user_customization'],
                'can_customize_logo'   => (bool)$settings['allow_user_logo'],
                'can_customize_slug'   => (bool)$settings['allow_user_customization'],
                'catalog_base_url'     => $settings['catalog_base_url'] ?: ($qrCode ? Str::before($qrCode->qr_url, '/store-qr/') : url('/')),
                'qr_code' => [
                    'id'                        => $qrCode->id,
                    'user_id'                   => $qrCode->user_id,
                    'store_id'                  => $qrCode->store_id,
                    'token'                     => $qrCode->qr_code_token,
                    'qr_code_token'             => $qrCode->qr_code_token,
                    'custom_slug'               => $qrCode->qr_code_token,
                    'slug'                      => $qrCode->qr_code_token,
                    'title'                     => $qrCode->title,
                    'tagline'                   => $qrCode->tagline,
                    'custom_tagline'            => $qrCode->tagline,
                    'primary_color'             => $qrCode->primary_color,
                    'custom_color'              => $qrCode->primary_color,
                    'secondary_color'           => $qrCode->secondary_color,
                    'center_logo_type'          => $qrCode->center_logo_type,
                    'center_logo_url'           => $qrCode->center_logo_url,
                    'effective_center_logo_url' => $effectiveCenterLogoUrl,
                    'qr_url'                    => $qrCode->qr_url,
                    'deep_link'                 => $qrCode->deep_link,
                    'format'                    => $qrCode->qr_style,
                    'size'                      => 'standee',
                    'is_active'                 => (bool)$qrCode->is_active,
                    'scans_count'               => (int)$qrCode->scans_count,
                    'last_scanned_at'           => $qrCode->last_scanned_at?->toIso8601String(),
                    'qr_base64_svg'             => $qrBase64Svg,
                    'svg_raw'                   => $rawSvg,
                    'raw_svg'                   => $rawSvg,
                    'qr_png_url'                => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=png'),
                    'qr_svg_url'                => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=svg'),
                ],
                'download_links' => [
                    'pdf_standee' => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=pdf&size=standee'),
                    'pdf_a4'      => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=pdf&size=a4'),
                    'pdf_a5'      => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=pdf&size=a5'),
                    'svg'         => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=svg'),
                    'png'         => url('/api/seller-qr/download?token=' . $qrCode->qr_code_token . '&format=png'),
                ]
            ]);
        } catch (Throwable $th) {
            DB::rollBack();
            ResponseService::logErrorResponse($th, 'SellerQrApiController -> generateOrUpdate');
            return ResponseService::errorResponse();
        }
    }

    /**
     * Download Standee or QR image in PDF, PNG, SVG
     */
    public function downloadStandee(Request $request)
    {
        try {
            $token = $request->input('token') ?: $request->input('qr_code_token');
            $storeSlug = $request->input('store_slug') ?: $request->input('slug');
            $storeId = $request->input('store_id');
            $userId = $request->input('user_id');

            $qrCode = null;
            if ($token) {
                $qrCode = SellerQrCode::with(['store', 'user'])
                    ->where('qr_code_token', $token)
                    ->orWhereHas('store', function ($q) use ($token) {
                        $q->where('slug', $token);
                    })
                    ->first();
            }

            if (!$qrCode && $storeSlug) {
                $qrCode = SellerQrCode::with(['store', 'user'])
                    ->whereHas('store', fn($q) => $q->where('slug', $storeSlug))
                    ->first();
            }

            if (!$qrCode && $storeId) {
                $qrCode = SellerQrCode::with(['store', 'user'])->where('store_id', $storeId)->first();
            }

            if (!$qrCode && (Auth::guard('sanctum')->check() || Auth::check())) {
                $uid = Auth::guard('sanctum')->id() ?: Auth::id();
                $qrCode = SellerQrCode::with(['store', 'user'])->where('user_id', $uid)->first();
            }

            if (!$qrCode && $userId) {
                $qrCode = SellerQrCode::with(['store', 'user'])->where('user_id', $userId)->first();
            }

            // If user is authenticated seller with store and no QR generated yet, generate one on the fly
            if (!$qrCode && (Auth::guard('sanctum')->check() || Auth::check())) {
                $authUser = Auth::guard('sanctum')->user() ?: Auth::user();
                if ($authUser && $authUser->store) {
                    $qrCode = SellerQrCode::create([
                        'user_id'          => $authUser->id,
                        'store_id'         => $authUser->store->id,
                        'qr_code_token'    => SellerQrCodeService::generateQrToken($authUser, $authUser->store),
                        'title'            => $authUser->store->name ?: 'My Store',
                        'tagline'          => 'Explore all verified ads, items and exclusive offers',
                        'qr_style'         => 'standee',
                        'primary_color'    => '#00B2CA',
                        'secondary_color'  => '#0F172A',
                        'center_logo_type' => 'platform_logo',
                        'is_active'        => true,
                    ]);
                }
            }

            if (!$qrCode) {
                return ResponseService::errorResponse(__('QR code not found'), null, 404);
            }

            $settings = SellerQrCodeService::getEffectiveSettings();
            $format = strtolower($request->input('format', 'pdf'));
            $size = strtolower($request->input('size', 'standee'));
            $storeSlug = $qrCode->store ? $qrCode->store->slug : 'seller-' . $qrCode->user_id;

            // Clear any lingering output buffers to protect binary downloads
            if (ob_get_level()) {
                ob_end_clean();
            }

            $corsHeaders = [
                'Access-Control-Allow-Origin'      => '*',
                'Access-Control-Expose-Headers'    => 'Content-Disposition, Content-Type',
                'Cache-Control'                    => 'no-cache, private',
            ];

            $centerLogoPath = SellerQrCodeService::resolveCenterLogoPath($qrCode, $settings, $qrCode->store);

            if ($format === 'svg') {
                $svg = SellerQrCodeService::generateRawQrSvg($qrCode->qr_url, $qrCode->primary_color, 600, $centerLogoPath);
                return response($svg, 200, array_merge($corsHeaders, [
                    'Content-Type'        => 'image/svg+xml',
                    'Content-Disposition' => 'attachment; filename="QR-' . $storeSlug . '.svg"',
                ]));
            }

            if ($format === 'png') {
                $png = SellerQrCodeService::generateRawQrPng($qrCode->qr_url, $qrCode->primary_color, 800, $centerLogoPath);
                return response($png, 200, array_merge($corsHeaders, [
                    'Content-Type'        => 'image/png',
                    'Content-Disposition' => 'attachment; filename="QR-' . $storeSlug . '.png"',
                ]));
            }

            // PDF Standee
            $pdfStream = SellerQrCodeService::generateStandeePdf($qrCode, $size);
            return response($pdfStream, 200, array_merge($corsHeaders, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Standee-' . $storeSlug . '-' . $size . '.pdf"',
            ]));
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerQrApiController -> downloadStandee');
            return ResponseService::errorResponse();
        }
    }

    /**
     * Get Store and Catalog by scanning QR Code (Universal endpoint for Web & Mobile)
     * Handles Geolocation mismatch warning and records scan metric
     */
    public function getStoreByQr(Request $request, string $identifier)
    {
        try {
            // Find by QR token first, fallback to store slug
            $qrCode = SellerQrCode::with(['store', 'user'])
                ->where('qr_code_token', $identifier)
                ->first();

            $store = null;
            if ($qrCode) {
                $store = $qrCode->store;
                // Record scan event
                $qrCode->recordScan();
            } else {
                // Try finding store by slug or id directly
                $store = Store::where('slug', $identifier)->orWhere('id', $identifier)->first();
                if ($store) {
                    $qrCode = SellerQrCode::where('store_id', $store->id)->orWhere('user_id', $store->user_id)->first();
                    if ($qrCode) {
                        $qrCode->recordScan();
                    }
                }
            }

            if (!$store) {
                return ResponseService::errorResponse(__('Store or QR code catalog not found'), null, 404);
            }

            $settings = SellerQrCodeService::getEffectiveSettings();

            $userLat = $request->filled('latitude') ? (float)$request->latitude : null;
            $userLng = $request->filled('longitude') ? (float)$request->longitude : null;
            $userCity = $request->input('city');
            $userState = $request->input('state');

            // Compute location mismatch warning
            $locationWarning = SellerQrCodeService::calculateLocationMismatch($userLat, $userLng, $store, $userCity, $userState);

            // Check follow status
            $isFollowing = false;
            if (Auth::guard('sanctum')->check()) {
                $authUser = Auth::guard('sanctum')->user();
                $isFollowing = $authUser->isFollowing($store->user_id);
            }

            $formattedStore = StoreService::formatStoreData($store, $userLat, $userLng);
            $formattedStore['is_following'] = $isFollowing;

            // Fetch distinct categories available in this store
            $categoryIds = Item::where('user_id', $store->user_id)
                ->where('status', 'approved')
                ->getNonExpiredItems()
                ->pluck('category_id')
                ->unique()
                ->toArray();

            $categories = Category::whereIn('id', $categoryIds)
                ->select('id', 'name', 'image', 'slug')
                ->get();

            // Fetch Items with filters and sorting
            $limit = (int) ($request->input('limit', 12));
            $page = (int) ($request->input('page', 1));
            $sortBy = $request->input('sort_by', 'newest');

            $itemsQuery = Item::with([
                'category:id,name,image,is_job_category,price_optional,slug',
                'gallery_images:id,image,item_id,is_default',
                'featured_items',
                'currency',
            ])
            ->where('user_id', $store->user_id)
            ->where('status', 'approved')
            ->getNonExpiredItems();

            if ($request->filled('search')) {
                $search = '%' . trim($request->search) . '%';
                $itemsQuery->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', $search)
                      ->orWhere('description', 'LIKE', $search);
                });
            }

            if ($request->filled('category_id')) {
                $itemsQuery->where('category_id', $request->category_id);
            }

            // Sorting
            switch ($sortBy) {
                case 'price_asc':
                    $itemsQuery->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $itemsQuery->orderBy('price', 'desc');
                    break;
                case 'popular':
                    $itemsQuery->orderBy('clicks', 'desc');
                    break;
                case 'in_offer':
                    $itemsQuery->whereHas('promotion_items', function ($q) {
                        $q->where('status', 'active');
                    })->orderBy('created_at', 'desc');
                    break;
                case 'newest':
                default:
                    $itemsQuery->orderBy('created_at', 'desc');
                    break;
            }

            $paginated = $itemsQuery->paginate($limit, ['*'], 'page', $page);
            $rawResourceItems = (new ItemApiResource(collect($paginated->items())))->toArray($request);

            // Ensure items are a sequential array and that common fields (name, address, etc.) exist at both root and translation level
            $formattedItems = array_values(array_map(function ($row) {
                if (is_array($row)) {
                    if (isset($row['translation']['name']) && empty($row['name'])) {
                        $row['name'] = $row['translation']['name'];
                    }
                    if (isset($row['translation']['address']) && empty($row['address'])) {
                        $row['address'] = $row['translation']['address'];
                    }
                }
                return $row;
            }, $rawResourceItems));

            $response = [
                'store'            => $formattedStore,
                'location_warning' => $locationWarning,
                'categories'       => $categories,
                'items'            => [
                    'total'        => $paginated->total(),
                    'current_page' => $paginated->currentPage(),
                    'per_page'     => $paginated->perPage(),
                    'last_page'    => $paginated->lastPage(),
                    'data'         => $formattedItems,
                ],
                'qr_details'       => $qrCode ? [
                    'token'        => $qrCode->qr_code_token,
                    'title'        => $qrCode->title,
                    'tagline'      => $qrCode->tagline,
                    'deep_link'    => $qrCode->deep_link,
                ] : null,
                'qr_code'          => $qrCode ? [
                    'id'               => $qrCode->id,
                    'user_id'          => $qrCode->user_id,
                    'store_id'         => $qrCode->store_id,
                    'token'            => $qrCode->qr_code_token,
                    'qr_code_token'    => $qrCode->qr_code_token,
                    'custom_slug'      => $qrCode->qr_code_token,
                    'slug'             => $qrCode->qr_code_token,
                    'title'            => $qrCode->title ?: ($store ? $store->name : $settings['default_title']),
                    'tagline'          => $qrCode->tagline ?: $settings['default_tagline'],
                    'primary_color'    => $qrCode->primary_color ?: $settings['primary_color'],
                    'secondary_color'  => $qrCode->secondary_color ?: $settings['secondary_color'],
                    'qr_url'           => $qrCode->qr_url,
                    'deep_link'        => $qrCode->deep_link,
                    'center_logo_type' => $qrCode->center_logo_type,
                    'center_logo_url'  => $qrCode->center_logo_url,
                    'badge_text'       => $settings['badge_text'],
                    'default_footer_text' => $settings['default_footer_text'],
                    'footer_logo_url'  => $settings['footer_logo_url'],
                ] : null,
                'settings'         => [
                    'default_title'            => $settings['default_title'] ?? 'Scan to Browse Store & Catalog',
                    'default_tagline'          => $settings['default_tagline'] ?? 'Explore all verified ads, items and exclusive offers',
                    'default_footer_text'      => $settings['default_footer_text'] ?? 'Powered by Bissow.com',
                    'footer_logo_url'          => $settings['footer_logo_url'] ?? null,
                    'center_logo_url'          => $settings['center_logo_url'] ?? null,
                    'badge_text'               => $settings['badge_text'] ?? 'DIGITAL STORE & CATALOG',
                    'catalog_banner_text'      => $settings['catalog_banner_text'] ?? 'Browse this store catalog on our mobile app',
                    'catalog_base_url'         => $settings['catalog_base_url'] ?? '',
                    'primary_color'            => $settings['primary_color'] ?? '#00B2CA',
                    'secondary_color'          => $settings['secondary_color'] ?? '#0F172A',
                    'allow_user_customization' => (bool) ($settings['allow_user_customization'] ?? 1),
                    'allow_user_logo'          => (bool) ($settings['allow_user_logo'] ?? 1),
                ],
            ];

            return ResponseService::successResponse(__('Store QR catalog fetched successfully'), $response);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerQrApiController -> getStoreByQr');
            return ResponseService::errorResponse();
        }
    }

    /**
     * Get public QR Settings & Branding
     */
    public function getSettings()
    {
        try {
            $settings = SellerQrCodeService::getEffectiveSettings();
            return ResponseService::successResponse(__('Settings fetched successfully'), $settings);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'SellerQrApiController -> getSettings');
            return ResponseService::errorResponse();
        }
    }
}
