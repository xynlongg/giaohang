<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class JwtLoggingMiddleware
{
    public function handle($request, Closure $next)
    {
        Log::info('JWT auth attempt', [
            'token' => $request->bearerToken(),
        ]);

        $response = $next($request);

        Log::info('JWT auth result', [
            'user' => auth()->user(),
            'status' => $response->status(),
        ]);

        return $response;
    }
}