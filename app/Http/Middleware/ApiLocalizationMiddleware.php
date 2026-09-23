<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ApiLocalizationMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next) {
        $localization = $request->header('Content-Language');

        if (empty($localization)) {
            try {
                if (Schema::hasTable('settings')) {
                    $localization = Cache::rememberForever('global_default_language', function () {
                        return Setting::where('name', 'default_language')->value('value') ?? 'en';
                    });
                }
            } catch (\Exception $e) {
                $localization = config('app.locale', 'en');
            }
        }

        app()->setLocale($localization);

        return $next($request);
    }
}
