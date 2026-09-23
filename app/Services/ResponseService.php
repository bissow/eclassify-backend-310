<?php

namespace App\Services;

use App\Http\Middleware\NetworkAdaptiveImages;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResponseService
{
    /**
     * Rewrite every /storage/ image URL inside a JSON response body to include
     * ?w=&q= resize params for the given network preset (e.g. ['w' => 300, 'q' => 50]).
     */
    public static function applyImageUrlTransform(JsonResponse $response, array $preset): JsonResponse
    {
        $data = $response->getData(true);
        if (!is_array($data)) {
            return $response;
        }

        $data = self::rewriteImageUrls($data, $preset);
        $response->setData($data);

        return $response;
    }

    private static function rewriteImageUrls($value, array $preset)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::rewriteImageUrls($item, $preset);
            }
            return $value;
        }

        if (is_string($value) && preg_match('#^https?://[^\s"?]+/storage/[^\s"?]+#i', $value)) {
            [$url, $query] = array_pad(explode('?', $value, 2), 2, '');
            $existing = [];
            if ($query !== '') {
                parse_str($query, $existing);
            }
            $existing['w'] = $preset['w'];
            $existing['h'] = $preset['h'];
            $existing['q'] = $preset['q'];

            return $url . '?' . http_build_query($existing);
        }

        return $value;
    }

    /**
     * @return Application|RedirectResponse|Redirector|true
     */
    public static function noPermissionThenRedirect($permission)
    {
        if (! Auth::user()->can($permission)) {
            return redirect(route('home'))->withErrors([
                'message' => trans("You Don't have enough permissions"),
            ])->send();
        }

        return true;
    }

    /**
     * @return true
     */
    public static function noPermissionThenSendJson($permission)
    {
        if (! Auth::user()->can($permission)) {
            self::errorResponse("You Don't have enough permissions");
        }

        return true;
    }

    /**
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector|true
     */
    // Check user role
    public static function noRoleThenRedirect($role)
    {
        if (! Auth::user()->hasRole($role)) {
            return redirect(route('home'))->withErrors([
                'message' => trans("You Don't have enough permissions"),
            ])->send();
        }

        return true;
    }

    /**
     * @return bool|Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public static function noAnyRoleThenRedirect(array $role)
    {
        if (! Auth::user()->hasAnyRole($role)) {
            return redirect(route('home'))->withErrors([
                'message' => trans("You Don't have enough permissions"),
            ])->send();
        }

        return true;
    }

    //    /**
    //     * @param $role
    //     * @return true
    //     */
    //    public static function noRoleThenSendJson($role)
    //    {
    //        if (!Auth::user()->hasRole($role)) {
    //            self::errorResponse("You Don't have enough permissions");
    //        }
    //        return true;
    //    }

    /**
     * @param  $feature
     * @return RedirectResponse|true
     */
    // Check Feature
    //    public static function noFeatureThenRedirect($feature) {
    //        if (Auth::user()->school_id && !app(FeaturesService::class)->hasFeature($feature)) {
    //            return redirect()->back()->withErrors([
    //                'message' => trans('Purchase') . " " . $feature . " " . trans("to Continue using this functionality")
    //            ])->send();
    //        }
    //        return true;
    //    }
    //
    //    public static function noFeatureThenSendJson($feature) {
    //        if (Auth::user()->school_id && !app(FeaturesService::class)->hasFeature($feature)) {
    //            self::errorResponse(trans('Purchase') . " " . $feature . " " . trans("to Continue using this functionality"));
    //        }
    //        return true;
    //    }

    /**
     * If User don't have any of the permission that is specified in Array then Redirect will happen
     *
     * @return RedirectResponse|true
     */
    public static function noAnyPermissionThenRedirect(array $permissions)
    {
        if (! Auth::user()->canany($permissions)) {
            return redirect()->back()->withErrors([
                'message' => trans("You Don't have enough permissions"),
            ])->send();
        }

        return true;
    }

    /**
     * If User don't have any of the permission that is specified in Array then Json Response will be sent
     *
     * @return true
     */
    public static function noAnyPermissionThenSendJson(array $permissions)
    {
        if (! Auth::user()->canany($permissions)) {
            self::errorResponse("You Don't have enough permissions");
        }

        return true;
    }

    /**
     * @param  null  $data
     * @param  null  $code
     */
    public static function successResponse(?string $message = 'Success', $data = null, array $customData = [], $code = null): void
    {
        $response = response()->json(array_merge([
            'error' => false,
            'message' => trans($message),
            'data' => $data,
            'code' => $code ?? config('constants.RESPONSE_CODE.SUCCESS'),
        ], $customData));
        self::applyNetworkPreset($response);
        self::addCacheHeaders($response);
        $response->send();
        exit();
    }

    /**
     * @return Application|\Illuminate\Foundation\Application|RedirectResponse|Redirector
     */
    public static function successRedirectResponse(string $message = 'success', $url = null)
    {
        return isset($url) ? redirect($url)->with([
            'success' => trans($message),
        ])->send() : redirect()->back()->with([
            'success' => trans($message),
        ])->send();
    }

    /**
     * @param  string  $message  - Pass the Translatable Field
     * @param  null  $data
     * @param  string  $code
     * @param  null  $e
     * @return void
     */
    public static function errorResponse(string $message = 'Error Occurred', $data = null, string|int|null $code = null, $e = null)
    {
        $response = response()->json([
            'error' => true,
            'message' => trans($message),
            'data' => $data,
            'code' => $code ?? config('constants.RESPONSE_CODE.EXCEPTION_ERROR'),
            'details' => (! empty($e) && is_object($e)) ? $e->getMessage().' --> '.$e->getFile().' At Line : '.$e->getLine() : '',
        ]);
        self::applyNetworkPreset($response);
        self::addCacheHeaders($response);
        $response->send();
        exit();
    }

    /**
     * return keyword should, must be used wherever this function is called.
     *
     * @param  string|string[]  $message
     * @param  null  $input
     * @return RedirectResponse
     */
    public static function errorRedirectResponse(string|array $message = 'Error Occurred', $url = 'back', $input = null)
    {
        return $url == 'back' ? redirect()->back()->with([
            'errors' => trans($message),
        ])->withInput($input) : redirect($url)->with([
            'errors' => trans($message),
        ])->withInput($input);
    }

    /**
     * @param  null  $data
     * @param  null  $code
     * @return void
     */
    public static function warningResponse(string $message = 'Error Occurred', $data = null, $code = null)
    {
        $response = response()->json([
            'error' => false,
            'warning' => true,
            'code' => $code,
            'message' => trans($message),
            'data' => $data,
        ]);
        self::applyNetworkPreset($response);
        self::addCacheHeaders($response);
        $response->send();
        exit();
    }

    /**
     * @param  null  $data
     * @return void
     */
    public static function validationError(string $message = 'Error Occurred', $data = null)
    {
        self::errorResponse($message, $data, config('constants.RESPONSE_CODE.VALIDATION_ERROR'));
    }

    /**
     * @return void
     */
    public static function validationErrorRedirect(string $message = 'Error Occurred')
    {
        self::errorRedirectResponse(route('custom-fields.create'), $message);
        exit();
    }

    /**
     * @return void
     */
    public static function logErrorResponse(Throwable|Exception $e, string $logMessage = ' ', string $responseMessage = 'Error Occurred', bool $jsonResponse = true)
    {
        $token = request()->bearerToken();

        Log::error($logMessage.' '.$e->getMessage().'---> '.$e->getFile().' At Line : '.$e->getLine()."\n\n".request()->method().' : '.request()->fullUrl()."\nToken : ".$token."\nParams : ", request()->all());
        if ($jsonResponse && config('app.debug')) {
            self::errorResponse($responseMessage, null, null, $e);
        }
    }

    public static function logErrorRedirect($e, string $logMessage = ' ', string $responseMessage = 'Error Occurred', bool $jsonResponse = true)
    {
        Log::error($logMessage.' '.$e->getMessage().'---> '.$e->getFile().' At Line : '.$e->getLine());
        if ($jsonResponse && config('app.debug')) {
            throw $e;
        }
    }

    public static function errorRedirectWithToast(string $message, $input = null)
    {
        return redirect()->back()->with('error', $message)->withInput($input);
    }

    private static function applyNetworkPreset(JsonResponse $response): void
    {
        $preset = NetworkAdaptiveImages::presetForRequest(request());
        if ($preset === null || CachingService::getSystemSettings('feature_image_resizing') === '0') {
            return;
        }

        self::applyImageUrlTransform($response, $preset);
    }

    private static function addCacheHeaders(JsonResponse $response): void
    {
        $content = $response->getContent();
        if ($content === false || $content === '') {
            return;
        }
        $etag = '"' . md5($content) . '"';
        $response->setEtag($etag);

        $request = request();
        if (! $request->isMethod('GET')) {
            return;
        }

        // Normalize client ETags: strip Apache mod_deflate '-gzip' suffix and weak prefix
        $clientEtags = array_map(function ($tag) {
            $tag = preg_replace('/^W\//', '', $tag);           // drop weak indicator
            $tag = preg_replace('/-gzip("?)$/', '$1', $tag);   // drop mod_deflate suffix (quoted or unquoted)
            // Ensure the tag is quoted so it can match server-generated ETags
            if ($tag !== '*' && !str_starts_with($tag, '"')) {
                $tag = '"' . $tag . '"';
            }
            return $tag;
        }, $request->getETags());

        if (in_array($etag, $clientEtags, true) || in_array('*', $clientEtags, true)) {
            $response->setNotModified(); // sets 304 and clears body
        }
    }

}
