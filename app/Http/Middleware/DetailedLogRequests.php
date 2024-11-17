<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DetailedLogRequests
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // in milliseconds

        Log::info('Request processed', [
            'uri' => $request->getRequestUri(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'execution_time' => round($executionTime, 2) . 'ms',
            'response_status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}