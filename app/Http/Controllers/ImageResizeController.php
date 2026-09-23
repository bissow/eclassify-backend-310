<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;
use Symfony\Component\HttpFoundation\Response;

class ImageResizeController extends Controller
{
    const MIN_WIDTH = 10;
    const MAX_WIDTH = 3840;

    const MIN_HEIGHT = 10;
    const MAX_HEIGHT = 3840;

    const MIN_QUALITY = 10;
    const MAX_QUALITY = 100;
    const CACHE_DIR = 'image-cache';

    public function resize(Request $request, string $path)
    {
        $path = $this->sanitizePath($path);
        if ($path === null) {
            abort(400, 'Invalid path');
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            abort(404);
        }

        $width = $request->query('w');
        $height = $request->query('h');
        $quality = $request->query('q');

        $width = $width !== null ? max(self::MIN_WIDTH, min(self::MAX_WIDTH, (int) $width)) : null;
        $height = $height !== null ? max(self::MIN_HEIGHT, min(self::MAX_HEIGHT, (int) $height)) : null;
        $quality = $quality !== null ? max(self::MIN_QUALITY, min(self::MAX_QUALITY, (int) $quality)) : null;

        if ($width === null && $height === null && $quality === null) {
            return $this->stream($disk->path($path), $this->mimeFromExtension($path));
        }

        $cacheKey = md5($path . '_w' . ($width ?? '') . '_h' . ($height ?? '') . '_q' . ($quality ?? ''));
        $extension = 'jpg';
        $cachePath = self::CACHE_DIR . '/' . $cacheKey . '.' . $extension;

        if ($disk->exists($cachePath)) {
            return $this->stream($disk->path($cachePath), 'image/jpeg');
        }

        $sourcePath = $disk->path($path);

        [$originalWidth, $originalHeight] = @getimagesize($sourcePath) ?: [null, null];

        $disk->makeDirectory(self::CACHE_DIR);
        $destination = $disk->path($cachePath);

        $image = Image::load($sourcePath);

        if ($width !== null && $originalWidth !== null && $width < $originalWidth) {
            $image->width($width);
        }
        
        if ($height !== null && $originalHeight !== null && $height < $originalHeight) {
            $image->height($height);
        }

        if ($quality !== null) {
            $image->quality($quality);
        }

        $image->save($destination);

        return $this->stream($destination, 'image/jpeg');
    }

    private function sanitizePath(string $path): ?string
    {
        $path = ltrim($path, '/');

        if (str_contains($path, "\0")) {
            return null;
        }

        if (str_contains($path, '..') || str_contains($path, '\\')) {
            return null;
        }

        if (!preg_match('#^[A-Za-z0-9_\-./]+$#', $path)) {
            return null;
        }

        return $path;
    }

    private function mimeFromExtension(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    private function stream(string $filePath, string $mime): Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        return response()->stream(function () use ($filePath) {
            readfile($filePath);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => filesize($filePath),
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
