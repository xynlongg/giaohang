<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class SanctumLoggingMiddleware
{
    public function handle($request, Closure $next)
    {
        Log::info('Sanctum middleware start', [
            'token' => $request->bearerToken(),
            'user' => $request->user(),
        ]);

        $response = $next($request);

        Log::info('Sanctum middleware end', [
            'user' => $request->user(),
            'response_status' => $response->status(),
        ]);

        return $response;
    }
}