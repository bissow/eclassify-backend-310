<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddEtagHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->isMethod('GET')) {
            return $response;
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return $response;
        }

        // Skip if ETag already set (e.g. by ResponseService)
        if ($response->headers->has('ETag')) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return $response;
        }

        $etag = '"' . md5($content) . '"';
        $response->setEtag($etag);

        return $response;
    }
}
