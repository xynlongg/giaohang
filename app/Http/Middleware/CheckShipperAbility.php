<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckShipperAbility
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('CheckShipperAbility start', [
            'user' => $request->user(),
            'token' => $request->bearerToken(),
        ]);

        if (!$request->user()) {
            Log::warning('No authenticated user');
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$request->user()->tokenCan('shipper')) {
            Log::warning('User does not have shipper ability');
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Log::info('CheckShipperAbility end - User authorized');

        return $next($request);
    }
}