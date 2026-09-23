<?php

namespace App\Services;

use App\Models\Package;
use App\Models\SellerQrCode;
use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPurchasedPackage;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class SellerQrCodeService
{
    /**
     * Generate unique, SEO-friendly QR code token (slug)
     */
    public static function generateQrToken(User $user, ?Store $store = null): string
    {
        $baseSlug = null;
        if ($store) {
            $baseSlug = !empty($store->slug) ? Str::slug($store->slug) : Str::slug($store->name);
        }
        if (empty($baseSlug)) {
            $baseSlug = Str::slug($user->name);
        }
        if (empty($baseSlug)) {
            $baseSlug = 'seller-store';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (SellerQrCode::where('qr_code_token', $slug)->withTrashed()->exists()) {
            $counter++;
            $slug = $baseSlug . '-' . $counter;
        }

        return $slug;
    }

    /**
     * Get consolidated admin default settings for Seller QR codes
     */
    public static function getEffectiveSettings(): array
    {
        $defaultSettings = DefaultSettingService::get();
        $defaults = [];
        foreach ($defaultSettings as $setting) {
            if (str_starts_with($setting['name'], 'seller_qr_')) {
                $defaults[$setting['name']] = $setting['value'];
            }
        }

        $dbSettings = Setting::where('name', 'LIKE', 'seller_qr_%')->pluck('value', 'name')->toArray();

        $merged = array_merge($defaults, $dbSettings);

        // Resolve footer logo url
        $footerLogo = $merged['seller_qr_footer_logo'] ?? 'assets/images/logo/sidebar_logo.png';
        if (!empty($footerLogo) && !filter_var($footerLogo, FILTER_VALIDATE_URL)) {
            if (Str::contains($footerLogo, 'assets')) {
                $footerLogoUrl = asset($footerLogo);
            } else {
                $footerLogoUrl = url(Storage::url($footerLogo));
            }
        } else {
            $footerLogoUrl = $footerLogo;
        }

        // Resolve center logo url (dedicated admin QR center logo)
        $centerLogo = $merged['seller_qr_center_logo'] ?? 'assets/images/logo/favicon.png';
        if (!empty($centerLogo) && !filter_var($centerLogo, FILTER_VALIDATE_URL)) {
            if (Str::contains($centerLogo, 'assets')) {
                $centerLogoUrl = asset($centerLogo);
            } else {
                $centerLogoUrl = url(Storage::url($centerLogo));
            }
        } else {
            $centerLogoUrl = $centerLogo;
        }

        return [
            'enabled'                   => (bool) ($merged['seller_qr_enabled'] ?? 1),
            'allow_user_logo'           => (bool) ($merged['seller_qr_allow_user_logo'] ?? 1),
            'allow_user_customization'  => (bool) ($merged['seller_qr_allow_user_customization'] ?? 1),
            'default_title'             => $merged['seller_qr_default_title'] ?? 'Scan to Browse Store & Catalog',
            'default_tagline'           => $merged['seller_qr_default_tagline'] ?? 'Explore all verified ads, items and exclusive offers',
            'default_footer_text'       => $merged['seller_qr_default_footer_text'] ?? 'Powered by Bissow.com',
            'footer_logo'               => $footerLogo,
            'footer_logo_url'           => $footerLogoUrl,
            'center_logo'               => $centerLogo,
            'center_logo_url'           => $centerLogoUrl,
            'catalog_base_url'          => $merged['seller_qr_catalog_base_url'] ?? '',
            'primary_color'             => $merged['seller_qr_primary_color'] ?? '#00B2CA',
            'secondary_color'           => $merged['seller_qr_secondary_color'] ?? '#0F172A',
            'default_center_logo_type'  => $merged['seller_qr_default_center_logo_type'] ?? 'platform_logo',
            'warning_distance_km'       => (float) ($merged['seller_qr_warning_distance_km'] ?? 25),
            'badge_text'                => $merged['seller_qr_badge_text'] ?? 'DIGITAL STORE & CATALOG',
            'catalog_banner_text'       => $merged['seller_qr_catalog_banner_text'] ?? 'Browse this store catalog on our mobile app',
        ];
    }

    /**
     * Check if a user is eligible to generate / use Seller QR codes
     */
    public static function checkUserEligibility(User $user): array
    {
        $settings = self::getEffectiveSettings();

        if (!$settings['enabled']) {
            return [
                'eligible'             => false,
                'is_eligible'          => false,
                'has_package'          => false,
                'active_package'       => null,
                'has_store'            => false,
                'store'                => null,
                'can_customize_logo'   => false,
                'can_customize_colors' => false,
                'message'              => __('Seller QR Code feature is currently disabled by administrator.'),
            ];
        }

        $today = Carbon::now()->format('Y-m-d');

        // Check active purchased package with allows_seller_qr_code
        $activePurchasedPackage = UserPurchasedPackage::with('package')
            ->where('user_id', $user->id)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereDate('end_date', '>=', $today)->orWhereNull('end_date');
            })
            ->whereHas('package', function ($q) {
                $q->where('allows_seller_qr_code', true);
            })
            ->latest('id')
            ->first();

        $store = Store::where('user_id', $user->id)->first();
        $hasStore = ($store !== null);
        $hasPackage = ($activePurchasedPackage !== null);

        if (!$hasPackage) {
            return [
                'eligible'             => false,
                'is_eligible'          => false,
                'has_package'          => false,
                'active_package'       => null,
                'has_store'            => $hasStore,
                'store'                => $store,
                'can_customize_logo'   => false,
                'can_customize_colors' => false,
                'message'              => __('Your current active subscription package does not include the Seller QR Code feature. Please upgrade your package.'),
            ];
        }

        return [
            'eligible'             => true,
            'is_eligible'          => true,
            'has_package'          => true,
            'active_package'       => $activePurchasedPackage->package,
            'has_store'            => $hasStore,
            'store'                => $store,
            'can_customize_logo'   => $settings['allow_user_logo'],
            'can_customize_colors' => $settings['allow_user_customization'],
            'message'              => __('User is eligible for Seller QR Code feature.'),
        ];
    }

    /**
     * Convert HEX color string to [R, G, B]
     */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } elseif (strlen($hex) >= 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } else {
            return [0, 178, 202]; // Default fallback #00B2CA
        }

        return [$r, $g, $b];
    }

    /**
     * Resolve the effective local absolute path for the QR center logo
     */
    public static function resolveCenterLogoPath(SellerQrCode $qrCode, ?array $settings = null, ?Store $store = null): ?string
    {
        $settings = $settings ?: self::getEffectiveSettings();
        $store = $store ?: ($qrCode->store ?: $qrCode->user?->store);
        $type = $qrCode->center_logo_type ?: ($settings['default_center_logo_type'] ?? 'platform_logo');

        if ($type === 'none') {
            return null;
        }

        if ($type === 'custom' && !empty($qrCode->center_logo)) {
            $customPath = self::resolveLocalImagePath($qrCode->getRawOriginal('center_logo'));
            if (!empty($customPath) && File::exists($customPath)) {
                return $customPath;
            }
        }

        if ($type === 'store_logo' && $store && !empty($store->logo)) {
            $storeLogoPath = self::resolveLocalImagePath($store->getRawOriginal('logo'));
            if (!empty($storeLogoPath) && File::exists($storeLogoPath)) {
                return $storeLogoPath;
            }
        }

        if ($type === 'platform_logo' || empty($type)) {
            $adminLogo = !empty($settings['center_logo']) ? $settings['center_logo'] : ($settings['footer_logo'] ?? null);
            $platformPath = self::resolveLocalImagePath($adminLogo);
            if (!empty($platformPath) && File::exists($platformPath)) {
                return $platformPath;
            }
        }

        return null;
    }

    /**
     * Generate raw SVG string of the QR code with embedded center logo badge
     */
    public static function generateRawQrSvg(string $url, string $primaryColor = '#00B2CA', int $size = 300, ?string $logoPath = null): string
    {
        [$r, $g, $b] = self::hexToRgb($primaryColor);

        $svg = (string) QrCode::format('svg')
            ->size($size)
            ->color($r, $g, $b)
            ->backgroundColor(255, 255, 255)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($url);

        if (!empty($logoPath) && File::exists($logoPath)) {
            $logoData = @file_get_contents($logoPath);
            if ($logoData) {
                $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'png'         => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg'         => 'image/svg+xml',
                    'webp'        => 'image/webp',
                    'gif'         => 'image/gif',
                    default       => 'image/png',
                };
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($logoData);

                // Proportional circular badge dimensions matching preview mockup
                $badgeRadius = round($size * 0.125, 2);
                $center = round($size / 2, 2);
                $logoBoxSize = round($badgeRadius * 1.35, 2);
                $logoX = round($center - ($logoBoxSize / 2), 2);
                $logoY = round($center - ($logoBoxSize / 2), 2);

                $badgeSvg = '  <!-- Embedded QR Center Logo Badge -->
  <g id="qrCenterLogoBadge">
    <circle cx="' . $center . '" cy="' . $center . '" r="' . $badgeRadius . '" fill="#ffffff" stroke="' . $primaryColor . '" stroke-width="2.5" />
    <image href="' . $logoDataUri . '" xlink:href="' . $logoDataUri . '" x="' . $logoX . '" y="' . $logoY . '" width="' . $logoBoxSize . '" height="' . $logoBoxSize . '" preserveAspectRatio="xMidYMid meet" />
  </g>
</svg>';

                $svg = preg_replace('/<\/svg>\s*$/i', $badgeSvg, $svg);
            }
        }

        return $svg;
    }

    /**
     * Generate raw PNG binary of the QR code using GD with circular badge support
     */
    public static function generateRawQrPng(string $url, string $primaryColor = '#00B2CA', int $size = 400, ?string $logoPath = null): string
    {
        return self::generateQrPngWithGd($url, $primaryColor, $size, $logoPath);
    }

    /**
     * Pure GD-based QR code PNG generator with center logo support (requires no Imagick extension)
     */
    public static function generateQrPngWithGd(string $url, string $primaryColor = '#00B2CA', int $size = 600, ?string $logoPath = null): string
    {
        [$r, $g, $b] = self::hexToRgb($primaryColor);

        $qrCode = \BaconQrCode\Encoder\Encoder::encode($url, \BaconQrCode\Common\ErrorCorrectionLevel::H());
        $matrix = $qrCode->getMatrix();
        $matrixWidth = $matrix->getWidth();
        $matrixHeight = $matrix->getHeight();

        $margin = 2;
        $totalModules = $matrixWidth + ($margin * 2);
        $moduleSize = max(1, (int)floor($size / $totalModules));
        $imgSize = $totalModules * $moduleSize;

        $img = imagecreatetruecolor($imgSize, $imgSize);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $white = imagecolorallocate($img, 255, 255, 255);
        $primary = imagecolorallocate($img, $r, $g, $b);

        imagefill($img, 0, 0, $white);

        for ($y = 0; $y < $matrixHeight; $y++) {
            for ($x = 0; $x < $matrixWidth; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $px = ($x + $margin) * $moduleSize;
                    $py = ($y + $margin) * $moduleSize;
                    imagefilledrectangle($img, $px, $py, $px + $moduleSize - 1, $py + $moduleSize - 1, $primary);
                }
            }
        }

        if (!empty($logoPath) && File::exists($logoPath)) {
            $logoData = @file_get_contents($logoPath);
            if ($logoData) {
                $logoImg = @imagecreatefromstring($logoData);
                if ($logoImg) {
                    $lw = imagesx($logoImg);
                    $lh = imagesy($logoImg);

                    $center = (int)($imgSize / 2);
                    $badgeRadius = (int)($imgSize * 0.125);
                    $borderThickness = max(2, (int)round($imgSize * 0.006));

                    // Draw circular white badge and primary-color border matching SVG & preview
                    imagefilledellipse($img, $center, $center, $badgeRadius * 2, $badgeRadius * 2, $white);
                    for ($b = 0; $b < $borderThickness; $b++) {
                        imageellipse($img, $center, $center, ($badgeRadius * 2) - $b, ($badgeRadius * 2) - $b, $primary);
                    }

                    $logoBoxSize = (int)($badgeRadius * 1.35);
                    $lx = (int)($center - ($logoBoxSize / 2));
                    $ly = (int)($center - ($logoBoxSize / 2));

                    imagecopyresampled($img, $logoImg, $lx, $ly, 0, 0, $logoBoxSize, $logoBoxSize, $lw, $lh);
                    imagedestroy($logoImg);
                }
            }
        }

        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData ?: '';
    }

    /**
     * Generate Base64 Data URI for inline embedding in HTML/PDF
     */
    public static function generateQrBase64Svg(string $url, string $primaryColor = '#00B2CA', int $size = 300, ?string $logoPath = null): string
    {
        $svg = self::generateRawQrSvg($url, $primaryColor, $size, $logoPath);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate Base64 Data URI for PNG
     */
    public static function generateQrBase64Png(string $url, string $primaryColor = '#00B2CA', int $size = 400, ?string $logoPath = null): string
    {
        $png = self::generateRawQrPng($url, $primaryColor, $size, $logoPath);
        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * Upload custom center logo for QR code
     */
    public static function handleCenterLogoUpload(UploadedFile $file, ?SellerQrCode $qrCode = null): string
    {
        $oldLogo = $qrCode ? $qrCode->getRawOriginal('center_logo') : null;
        if (!empty($oldLogo)) {
            return FileService::compressAndReplace($file, 'seller_qr_logos', $oldLogo);
        }
        return FileService::compressAndUpload($file, 'seller_qr_logos');
    }

    /**
     * Calculate Location Mismatch and return comprehensive warning data
     */
    public static function calculateLocationMismatch(
        ?float $deviceLat,
        ?float $deviceLng,
        Store $store,
        ?string $selectedCity = null,
        ?string $selectedState = null
    ): array {
        $settings = self::getEffectiveSettings();
        $thresholdKm = $settings['warning_distance_km'];

        $storeLocation = [
            'store_id' => $store->id,
            'name'     => $store->name,
            'address'  => $store->address,
            'city'     => $store->city,
            'state'    => $store->state,
            'country'  => $store->country,
            'latitude' => $store->latitude,
            'longitude'=> $store->longitude,
        ];

        $hasDeviceCoords = ($deviceLat !== null && $deviceLng !== null && $store->latitude && $store->longitude);
        $distanceKm = null;
        $distanceFormatted = null;

        if ($hasDeviceCoords) {
            $distanceKm = round(StoreService::calculateHaversineDistance($deviceLat, $deviceLng, (float)$store->latitude, (float)$store->longitude), 2);
            $distanceFormatted = StoreService::formatDistance($distanceKm);
        }

        $isFar = false;
        $isCityMismatch = false;

        if ($distanceKm !== null && $distanceKm > $thresholdKm) {
            $isFar = true;
        }

        if (!empty($selectedCity) && !empty($store->city) && strcasecmp(trim($selectedCity), trim($store->city)) !== 0) {
            $isCityMismatch = true;
        }

        if (!empty($selectedState) && !empty($store->state) && strcasecmp(trim($selectedState), trim($store->state)) !== 0) {
            $isCityMismatch = true;
        }

        $warning = $isFar || $isCityMismatch;

        $message = null;
        if ($warning) {
            $storeLocText = implode(', ', array_filter([$store->city, $store->state]));
            if ($distanceKm !== null) {
                $message = __('Notice: You are browsing items from :store located in :location (approx. :distance away). This seller is outside your current or selected area.', [
                    'store'    => $store->name,
                    'location' => $storeLocText ?: $store->name,
                    'distance' => $distanceFormatted['formatted'] ?? ($distanceKm . ' km'),
                ]);
            } else {
                $message = __('Notice: You are browsing items from :store located in :location. This seller may not be in your currently selected browsing area.', [
                    'store'    => $store->name,
                    'location' => $storeLocText ?: $store->name,
                ]);
            }
        }

        return [
            'is_matched'        => !$warning,
            'warning'           => $warning,
            'message'           => $message,
            'distance_km'       => $distanceKm,
            'distance'          => $distanceFormatted,
            'threshold_km'      => $thresholdKm,
            'store_location'    => $storeLocation,
        ];
    }

    /**
     * Resolve absolute local path for an image (store logo, center logo, or platform logo)
     */
    public static function resolveLocalImagePath(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        // Direct local file check
        if (File::exists($imagePath)) {
            return $imagePath;
        }

        // Handle URL or relative path containing /storage/
        if (Str::contains($imagePath, '/storage/')) {
            $rel = Str::after($imagePath, '/storage/');
            $local = storage_path('app/public/' . ltrim($rel, '/'));
            if (File::exists($local)) {
                return $local;
            }
        }

        // Handle URL or relative path containing /assets/
        if (Str::contains($imagePath, '/assets/')) {
            $rel = Str::after($imagePath, '/assets/');
            $local = public_path('assets/' . ltrim($rel, '/'));
            if (File::exists($local)) {
                return $local;
            }
        }

        if (Str::startsWith($imagePath, 'http://') || Str::startsWith($imagePath, 'https://')) {
            // Attempt to resolve local path from storage URL
            $storageUrl = url(Storage::url(''));
            if (Str::startsWith($imagePath, $storageUrl)) {
                $relativePath = Str::after($imagePath, $storageUrl);
                $localPath = storage_path('app/public/' . ltrim($relativePath, '/'));
                if (File::exists($localPath)) {
                    return $localPath;
                }
            }

            $assetUrl = url('assets');
            if (Str::startsWith($imagePath, $assetUrl)) {
                $relativePath = Str::after($imagePath, url(''));
                $localPath = public_path(ltrim($relativePath, '/'));
                if (File::exists($localPath)) {
                    return $localPath;
                }
            }

            return null;
        }

        if (Str::contains($imagePath, 'assets')) {
            $local = public_path(ltrim($imagePath, '/'));
            if (File::exists($local)) {
                return $local;
            }
        }

        $storagePath = storage_path('app/public/' . ltrim($imagePath, '/'));
        if (File::exists($storagePath)) {
            return $storagePath;
        }

        $publicPath = public_path(ltrim($imagePath, '/'));
        if (File::exists($publicPath)) {
            return $publicPath;
        }

        return null;
    }

    /**
     * Convert an image (local file, relative path, or URL) to a Base64 data URI
     * for instant, offline, zero-network rendering in PDF engines (Mpdf)
     */
    public static function imageToBase64Uri(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        if (Str::startsWith($imagePath, 'data:image/')) {
            return $imagePath;
        }

        $localPath = self::resolveLocalImagePath($imagePath);
        if ($localPath && File::exists($localPath)) {
            $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png'   => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg'   => 'image/svg+xml',
                'webp'  => 'image/webp',
                'gif'   => 'image/gif',
                default => 'image/png',
            };
            $content = @file_get_contents($localPath);
            if ($content !== false && strlen($content) > 0) {
                return 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        }

        return $imagePath;
    }

    /**
     * Render Standee / Poster HTML for PDF and web preview
     */
    public static function renderStandeeHtml(SellerQrCode $qrCode, string $pageSize = 'standee'): string
    {
        $settings = self::getEffectiveSettings();
        $store = $qrCode->store ?: $qrCode->user->store;

        $title = $qrCode->title ?: ($store ? $store->name : $settings['default_title']);
        $tagline = $qrCode->tagline ?: $settings['default_tagline'];
        $primaryColor = $qrCode->primary_color ?: $settings['primary_color'];
        $secondaryColor = $qrCode->secondary_color ?: $settings['secondary_color'];

        $centerLogoPath = self::resolveCenterLogoPath($qrCode, $settings, $store);

        // Generate QR code base64 SVG
        $qrBase64 = self::generateQrBase64Svg($qrCode->qr_url, $primaryColor, 340, $centerLogoPath);

        // Store avatar converted to offline Base64 URI
        $rawStoreLogo = $store ? ($store->logo ?: asset('assets/images/logo/placeholder.png')) : asset('assets/images/logo/placeholder.png');
        $storeLogoUrl = self::imageToBase64Uri($rawStoreLogo) ?: $rawStoreLogo;

        $storeAddress = $store ? ($store->address ?: ($store->city . ', ' . $store->state)) : null;
        $storeContact = $store ? ($store->contact ? ($store->country_code . ' ' . $store->contact) : null) : null;
        $isVerified = $store ? (bool)$store->is_verified : false;

        $footerText = $settings['default_footer_text'];
        $rawFooterLogo = $settings['footer_logo_url'];
        $footerLogoUrl = self::imageToBase64Uri($rawFooterLogo) ?: $rawFooterLogo;
        $badgeText = $settings['badge_text'];

        return view('seller_qr.standee_template', compact(
            'qrCode',
            'store',
            'title',
            'tagline',
            'badgeText',
            'primaryColor',
            'secondaryColor',
            'qrBase64',
            'storeLogoUrl',
            'storeAddress',
            'storeContact',
            'isVerified',
            'footerText',
            'footerLogoUrl',
            'pageSize'
        ))->render();
    }

    /**
     * Generate Standee PDF stream
     */
    public static function generateStandeePdf(SellerQrCode $qrCode, string $pageSize = 'standee'): string
    {
        $tempDir = storage_path('app/temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $html = self::renderStandeeHtml($qrCode, $pageSize);

        $dimensions = match (strtolower($pageSize)) {
            'a4'      => 'A4',
            'a5'      => 'A5',
            'poster'  => [210, 297],
            'standee' => [148, 210], // Standee ratio
            default   => [148, 210],
        };

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => $dimensions,
            'margin_left'   => 0,
            'margin_right'  => 0,
            'margin_top'    => 0,
            'margin_bottom' => 0,
            'autoPageBreak' => false,
            'tempDir'       => $tempDir,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
