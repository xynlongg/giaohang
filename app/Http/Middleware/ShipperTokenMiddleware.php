<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShipperTokenMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        Log::info('Token received:', ['token' => $token]);

        if (!$token) {
            return response()->json(['message' => 'No token provided'], 401);
        }

        $shipper = \App\Models\Shipper::where('api_token', $token)->first();

        if (!$shipper) {
            Log::info('Invalid token:', ['token' => $token]);
            return response()->json(['message' => 'Invalid token'], 401);
        }

        Auth::guard('shipper')->setUser($shipper);

        Log::info('Shipper authenticated:', ['shipper_id' => $shipper->id]);

        return $next($request);
    }
}