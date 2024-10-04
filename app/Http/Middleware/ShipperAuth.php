<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ShipperAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('shipper')->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}