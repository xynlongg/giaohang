<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class AuthLoggingMiddleware
{
    public function handle($request, Closure $next)
    {
        Log::info('Sanctum auth attempt', [
            'token' => $request->bearerToken(),
            'user' => $request->user(),
            'abilities' => $request->user() ? $request->user()->currentAccessToken()->abilities : 'No user',
        ]);

        try {
            $response = $next($request);

            Log::info('Sanctum auth result', [
                'user' => $request->user(),
                'status' => $response->status(),
                'content' => $response->getContent(),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Error in Sanctum auth', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}