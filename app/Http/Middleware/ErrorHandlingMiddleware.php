<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class ErrorHandlingMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (\Throwable $e) {
            Log::error('Unhandled exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An unexpected error occurred'], 500);
        }
    }
}