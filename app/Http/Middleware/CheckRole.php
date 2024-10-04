<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    
public function handle(Request $request, Closure $next, ...$roles)
{
    $user = $request->user();
    Log::info('Checking user roles', [
        'user_id' => $user ? $user->id : null,
        'required_roles' => $roles,
        'user_roles' => $user ? $user->roles->pluck('name') : []
    ]);

    if (!$user || !$user->hasAnyRole($roles)) {
        Log::warning('Unauthorized access attempt', [
            'user_id' => $user ? $user->id : null,
            'required_roles' => $roles,
            'user_roles' => $user ? $user->roles->pluck('name') : []
        ]);
        abort(403, 'Unauthorized action.');
    }

    return $next($request);
}
}