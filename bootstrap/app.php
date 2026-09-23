<?php

use App\Http\Middleware\AddEtagHeaders;
use App\Http\Middleware\ApiLocalizationMiddleware;
use App\Http\Middleware\DemoMiddleware;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\EnsureRequestIntegrity;
use App\Http\Middleware\LanguageManager;
use App\Http\Middleware\NetworkAdaptiveImages;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: [], headers: 0);

        $middleware->prepend(TrustProxies::class);
        $middleware->prepend(HandleCors::class);
        $middleware->prepend(PreventRequestsDuringMaintenance::class);
        $middleware->prepend(TrimStrings::class);

        $middleware->web(prepend: [
            EnsureRequestIntegrity::class,
        ]);

        $middleware->web(append: [
            LanguageManager::class,
            PreventBackHistory::class,
            DemoMiddleware::class,
        ]);

        $middleware->web(replace: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class => EncryptCookies::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => VerifyCsrfToken::class,
        ]);

        $middleware->api(append: [
            ThrottleRequests::class.':api',
            ApiLocalizationMiddleware::class,
            DemoMiddleware::class,
            AddEtagHeaders::class,
            NetworkAdaptiveImages::class,
        ]);

        $middleware->alias([
            'language' => LanguageManager::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
