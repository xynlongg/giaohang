<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->roles()->where('name', 'admin')->exists()) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}