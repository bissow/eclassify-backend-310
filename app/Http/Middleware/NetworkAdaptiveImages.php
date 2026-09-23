<?php

namespace App\Http\Middleware;

use App\Services\CachingService;
use App\Services\ResponseService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NetworkAdaptiveImages
{
    private const PRESETS = [
        'good'   => ['w' => 800, 'h' => 800, 'q' => 85],  // WiFi / 4G / 5G
        'medium' => ['w' => 300, 'h' => 300, 'q' => 50],  // 3G
        'poor'   => ['w' => 150, 'h' => 150, 'q' => 10],  // 2G / EDGE / Offline
    ];

    public static function presetForRequest(Request $request): ?array
    {
        $type = strtolower(trim($request->header('X-Network-Type', '')));

        return self::PRESETS[$type] ?? null;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $preset = self::presetForRequest($request);

        $response = $next($request);

        if (
            $preset !== null
            && $response instanceof JsonResponse
            && CachingService::getSystemSettings('feature_image_resizing') !== '0'
        ) {
            $response = ResponseService::applyImageUrlTransform($response, $preset);
        }

        return $response;
    }
}
