<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LogRoute
{
    public function handle($request, Closure $next)
    {
        Log::info('Route được truy cập', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id() ?? 'guest',
            'user_roles' => auth()->user() ? auth()->user()->roles->pluck('name') : [],
            'route_name' => $request->route() ? $request->route()->getName() : null,
            'middleware' => $request->route() ? $request->route()->middleware() : []
        ]);

        return $next($request);
    }
}