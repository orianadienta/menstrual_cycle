<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleApiException
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->is('api/*') && !$request->expectsJson()) {
            if (strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'API endpoint requires JSON response',
                    'original_html_snippet' => substr($response->getContent(), 0, 200)
                ], 400);
            }
        }

        return $response;
    }
}
