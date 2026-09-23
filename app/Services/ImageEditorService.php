<?php

namespace App\Services;

use App\Models\EditedImage;
use App\Models\Setting;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImageEditorService
{
    /**
     * Apply image editing transformations to an uploaded file or existing image path.
     *
     * @param UploadedFile|string $sourceFileOrPath
     * @param array $operations Operations dictionary (crop, rotate, flip, filters, text, stickers)
     * @param string $folder Destination storage folder
     * @param int|null $userId User performing the edit
     * @param int|null $itemId Associated Item ID
     * @param bool $addWatermark Whether to apply platform watermark
     * @return array [success => bool, data => EditedImage|null, path => string, url => string, message => string]
     */
    public static function processAndSave(
        $sourceFileOrPath,
        array $operations = [],
        string $folder = 'item_images',
        ?int $userId = null,
        ?int $itemId = null,
        bool $addWatermark = false
    ): array {
        return DB::transaction(function () use ($sourceFileOrPath, $operations, $folder, $userId, $itemId, $addWatermark) {
            try {
                $disk = config('filesystems.default', 'public');

                // 1. Resolve source image into Intervention Image instance
                $originalPath = '';
                if ($sourceFileOrPath instanceof UploadedFile) {
                    $originalName = pathinfo($sourceFileOrPath->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = strtolower($sourceFileOrPath->getClientOriginalExtension());
                    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $extension = 'jpg';
                    }
                    $originalPath = $sourceFileOrPath->storeAs('temp_uploads', uniqid('src_', true) . '.' . $extension, $disk);
                    $image = Image::make($sourceFileOrPath)->orientate();
                } elseif (is_string($sourceFileOrPath)) {
                    $originalPath = $sourceFileOrPath;
                    if (filter_var($sourceFileOrPath, FILTER_VALIDATE_URL)) {
                        $image = Image::make($sourceFileOrPath)->orientate();
                        $extension = 'jpg';
                    } elseif (Storage::disk($disk)->exists($sourceFileOrPath)) {
                        $image = Image::make(Storage::disk($disk)->get($sourceFileOrPath))->orientate();
                        $extension = strtolower(pathinfo($sourceFileOrPath, PATHINFO_EXTENSION)) ?: 'jpg';
                    } elseif (file_exists($sourceFileOrPath)) {
                        $image = Image::make($sourceFileOrPath)->orientate();
                        $extension = strtolower(pathinfo($sourceFileOrPath, PATHINFO_EXTENSION)) ?: 'jpg';
                    } else {
                        // Check if base64 encoded string
                        if (preg_match('/^data:image\/(\w+);base64,/', $sourceFileOrPath, $type)) {
                            $data = substr($sourceFileOrPath, strpos($sourceFileOrPath, ',') + 1);
                            $data = base64_decode($data);
                            $image = Image::make($data)->orientate();
                            $extension = strtolower($type[1]) ?: 'jpg';
                        } else {
                            throw new Exception("Source image not found or unreadable: {$sourceFileOrPath}");
                        }
                    }
                } else {
                    throw new Exception("Invalid source image provided.");
                }

                // 2. Apply operations sequentially
                $image = self::applyTransformations($image, $operations);

                // 3. Optional platform watermark
                if ($addWatermark && HelperService::getWatermarkConfigStatus()) {
                    $image = self::applyWatermark($image);
                }

                // 4. Encode & Save result
                $quality = $operations['quality'] ?? 85;
                $outputExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp']) ? $extension : 'jpg';
                $encodedData = (string) $image->encode($outputExtension, (int)$quality);

                $fileName = 'edited_' . Str::random(16) . '_' . time() . '.' . $outputExtension;
                $editedPath = $folder . '/' . $fileName;

                Storage::disk($disk)->put($editedPath, $encodedData);

                // 5. Optimize via Spatie Optimizer if local file exists
                try {
                    $absolutePath = Storage::disk($disk)->path($editedPath);
                    if (file_exists($absolutePath)) {
                        OptimizerChainFactory::create()->optimize($absolutePath);
                    }
                } catch (Exception $optEx) {
                    Log::warning('ImageEditorService optimizer notice: ' . $optEx->getMessage());
                }

                // 6. Record metadata in edited_images table
                $fileSize = Storage::disk($disk)->size($editedPath);
                $mimeType = 'image/' . ($outputExtension === 'jpg' ? 'jpeg' : $outputExtension);

                $editedRecord = EditedImage::create([
                    'user_id' => $userId,
                    'item_id' => $itemId,
                    'original_path' => $originalPath,
                    'edited_path' => $editedPath,
                    'transformations' => $operations,
                    'disk' => $disk,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                ]);

                $fullUrl = url(Storage::disk($disk)->url($editedPath));

                return [
                    'success' => true,
                    'data' => $editedRecord,
                    'path' => $editedPath,
                    'url' => $fullUrl,
                    'width' => $image->width(),
                    'height' => $image->height(),
                    'message' => 'Image edited and saved successfully.',
                ];
            } catch (Exception $e) {
                Log::error('ImageEditorService error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return [
                    'success' => false,
                    'data' => null,
                    'path' => null,
                    'url' => null,
                    'message' => 'Failed to process image: ' . $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Apply transformation pipeline to Intervention image instance.
     *
     * @param \Intervention\Image\Image $image
     * @param array $operations
     * @return \Intervention\Image\Image
     */
    public static function applyTransformations($image, array $operations)
    {
        // 1. Rotation (degrees, e.g. 90, 180, 270, or negative)
        if (!empty($operations['rotate'])) {
            $degrees = (int) $operations['rotate'];
            // Intervention rotate rotates counter-clockwise for positive degrees, so invert for natural CW rotation
            $image->rotate(-$degrees);
        }

        // 2. Flip (horizontal / vertical)
        if (!empty($operations['flip'])) {
            $flipMode = strtolower($operations['flip']);
            if ($flipMode === 'h' || $flipMode === 'horizontal') {
                $image->flip('h');
            } elseif ($flipMode === 'v' || $flipMode === 'vertical') {
                $image->flip('v');
            }
        }

        // 3. Crop (width, height, x, y)
        if (!empty($operations['crop']) && is_array($operations['crop'])) {
            $c = $operations['crop'];
            $cropWidth = isset($c['width']) ? (int) $c['width'] : null;
            $cropHeight = isset($c['height']) ? (int) $c['height'] : null;
            $cropX = isset($c['x']) ? (int) $c['x'] : null;
            $cropY = isset($c['y']) ? (int) $c['y'] : null;

            if ($cropWidth && $cropHeight && $cropWidth > 0 && $cropHeight > 0) {
                // Ensure bounds do not exceed image dimensions
                $cropWidth = min($cropWidth, $image->width());
                $cropHeight = min($cropHeight, $image->height());
                $image->crop($cropWidth, $cropHeight, $cropX, $cropY);
            }
        }

        // 4. Resize / Max Dimension constraints
        if (!empty($operations['resize']) && is_array($operations['resize'])) {
            $r = $operations['resize'];
            $targetWidth = $r['width'] ?? null;
            $targetHeight = $r['height'] ?? null;
            $maintainAspect = $r['aspect_ratio'] ?? true;

            if ($targetWidth || $targetHeight) {
                $image->resize($targetWidth, $targetHeight, function ($constraint) use ($maintainAspect) {
                    if ($maintainAspect) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                });
            }
        }

        // 5. Adjustments (Brightness, Contrast)
        if (isset($operations['brightness'])) {
            $brightness = max(-100, min(100, (int) $operations['brightness']));
            if ($brightness !== 0) {
                $image->brightness($brightness);
            }
        }

        if (isset($operations['contrast'])) {
            $contrast = max(-100, min(100, (int) $operations['contrast']));
            if ($contrast !== 0) {
                $image->contrast($contrast);
            }
        }

        // 6. Filters (grayscale, sepia, invert, blur, sharpen)
        if (!empty($operations['filter'])) {
            $filter = strtolower($operations['filter']);
            switch ($filter) {
                case 'grayscale':
                case 'greyscale':
                    $image->greyscale();
                    break;
                case 'invert':
                    $image->invert();
                    break;
                case 'sepia':
                    $image->greyscale();
                    $image->colorize(20, 10, -10);
                    break;
                case 'warm':
                    $image->colorize(15, 5, -10);
                    break;
                case 'cool':
                    $image->colorize(-10, 5, 20);
                    break;
                case 'vintage':
                    $image->greyscale();
                    $image->contrast(10);
                    $image->colorize(30, 15, -15);
                    break;
                case 'blur':
                    $blurAmount = isset($operations['blur']) ? (int) $operations['blur'] : 5;
                    $image->blur(max(1, min(100, $blurAmount)));
                    break;
                case 'sharpen':
                    $sharpAmount = isset($operations['sharpen']) ? (int) $operations['sharpen'] : 15;
                    $image->sharpen(max(1, min(100, $sharpAmount)));
                    break;
            }
        }

        // 7. Text Overlay with background highlight & styling
        if (!empty($operations['texts']) && is_array($operations['texts'])) {
            foreach ($operations['texts'] as $textItem) {
                if (!empty($textItem['text'])) {
                    $image = self::applyTextOverlay($image, $textItem);
                }
            }
        } elseif (!empty($operations['text']) && is_string($operations['text'])) {
            $image = self::applyTextOverlay($image, $operations);
        }

        // 8. Stickers / Badges Overlay
        if (!empty($operations['stickers']) && is_array($operations['stickers'])) {
            foreach ($operations['stickers'] as $sticker) {
                $image = self::applyStickerBadge($image, $sticker);
            }
        }

        return $image;
    }

    /**
     * Render text overlay with optional background highlight box and customizable styling.
     *
     * @param \Intervention\Image\Image $image
     * @param array $config
     * @return \Intervention\Image\Image
     */
    private static function applyTextOverlay($image, array $config)
    {
        $text = trim($config['text']);
        if (empty($text)) {
            return $image;
        }

        $fontSize = isset($config['font_size']) ? (int) $config['font_size'] : 28;
        $textColor = !empty($config['color']) ? $config['color'] : '#FFFFFF';
        $bgColor = !empty($config['bg_color']) ? $config['bg_color'] : null;
        $position = !empty($config['position']) ? $config['position'] : 'bottom-center';

        // Calculate positioning coordinates
        $imgWidth = $image->width();
        $imgHeight = $image->height();

        $x = isset($config['x']) ? (int) $config['x'] : null;
        $y = isset($config['y']) ? (int) $config['y'] : null;

        // Approx character dimension estimation for background box
        $charWidth = (int) ($fontSize * 0.6);
        $boxWidth = (int) (strlen($text) * $charWidth) + 30;
        $boxHeight = (int) ($fontSize * 1.5) + 20;

        if ($x === null || $y === null) {
            switch ($position) {
                case 'top-left':
                    $x = 30;
                    $y = 40;
                    break;
                case 'top-center':
                    $x = (int) ($imgWidth / 2);
                    $y = 40;
                    break;
                case 'top-right':
                    $x = $imgWidth - 30;
                    $y = 40;
                    break;
                case 'center':
                    $x = (int) ($imgWidth / 2);
                    $y = (int) ($imgHeight / 2);
                    break;
                case 'bottom-left':
                    $x = 30;
                    $y = $imgHeight - 50;
                    break;
                case 'bottom-right':
                    $x = $imgWidth - 30;
                    $y = $imgHeight - 50;
                    break;
                case 'bottom-center':
                default:
                    $x = (int) ($imgWidth / 2);
                    $y = $imgHeight - 50;
                    break;
            }
        }

        // Render background badge/box if requested
        if (!empty($bgColor)) {
            $boxX1 = max(0, $x - (int)($boxWidth / 2));
            $boxY1 = max(0, $y - (int)($boxHeight / 2));
            $boxX2 = min($imgWidth, $boxX1 + $boxWidth);
            $boxY2 = min($imgHeight, $boxY1 + $boxHeight);

            $image->rectangle($boxX1, $boxY1, $boxX2, $boxY2, function ($draw) use ($bgColor) {
                $draw->background($bgColor);
            });
        }

        // Draw text
        $image->text($text, $x, $y, function ($font) use ($fontSize, $textColor, $position) {
            $font->size($fontSize);
            $font->color($textColor);

            if (strpos($position, 'center') !== false) {
                $font->align('center');
            } elseif (strpos($position, 'right') !== false) {
                $font->align('right');
            } else {
                $font->align('left');
            }

            $font->valign('middle');
        });

        return $image;
    }

    /**
     * Overlay sticker or badge (e.g. "SALE", "HOT", "NEW", "VERIFIED").
     *
     * @param \Intervention\Image\Image $image
     * @param array|string $sticker
     * @return \Intervention\Image\Image
     */
    private static function applyStickerBadge($image, $sticker)
    {
        $badgeText = is_array($sticker) ? ($sticker['badge'] ?? 'SALE') : $sticker;
        $badgeText = strtoupper(trim($badgeText));

        $colorMap = [
            'SALE' => ['bg' => '#E11D48', 'text' => '#FFFFFF'],
            'HOT' => ['bg' => '#EA580C', 'text' => '#FFFFFF'],
            'NEW' => ['bg' => '#16A34A', 'text' => '#FFFFFF'],
            'VERIFIED' => ['bg' => '#2563EB', 'text' => '#FFFFFF'],
            'FEATURED' => ['bg' => '#7C3AED', 'text' => '#FFFFFF'],
            'BEST OFFER' => ['bg' => '#D97706', 'text' => '#FFFFFF'],
        ];

        $colors = $colorMap[$badgeText] ?? ['bg' => '#0F172A', 'text' => '#FFFFFF'];

        $x = is_array($sticker) && isset($sticker['x']) ? (int) $sticker['x'] : 40;
        $y = is_array($sticker) && isset($sticker['y']) ? (int) $sticker['y'] : 40;
        $fontSize = is_array($sticker) && isset($sticker['font_size']) ? (int) $sticker['font_size'] : 20;

        $padX = 16;
        $padY = 10;
        $boxW = (int)(strlen($badgeText) * ($fontSize * 0.65)) + ($padX * 2);
        $boxH = $fontSize + ($padY * 2);

        $image->rectangle($x, $y, $x + $boxW, $y + $boxH, function ($draw) use ($colors) {
            $draw->background($colors['bg']);
        });

        $image->text($badgeText, $x + (int)($boxW / 2), $y + (int)($boxH / 2), function ($font) use ($fontSize, $colors) {
            $font->size($fontSize);
            $font->color($colors['text']);
            $font->align('center');
            $font->valign('middle');
        });

        return $image;
    }

    /**
     * Apply platform watermark image or text.
     *
     * @param \Intervention\Image\Image $image
     * @return \Intervention\Image\Image
     */
    private static function applyWatermark($image)
    {
        try {
            $watermarkSetting = Setting::where('name', 'watermark_image')->value('value');
            if (!empty($watermarkSetting)) {
                $disk = config('filesystems.default', 'public');
                if (Storage::disk($disk)->exists($watermarkSetting)) {
                    $watermark = Image::make(Storage::disk($disk)->get($watermarkSetting));
                    $watermark->resize((int)($image->width() * 0.25), null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $watermark->opacity(25);
                    $image->insert($watermark, 'bottom-right', 20, 20);
                }
            }
        } catch (Exception $e) {
            Log::warning('ImageEditorService applyWatermark skipped: ' . $e->getMessage());
        }

        return $image;
    }
}
