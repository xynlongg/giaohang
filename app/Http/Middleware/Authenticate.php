<?php
namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Closure; // Sử dụng đúng lớp Closure của PHP

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        \Log::info('Redirecting unauthenticated user', [
            'url' => $request->url(),
            'expectsJson' => $request->expectsJson()
        ]);
    
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    public function handle($request, Closure $next, ...$guards)
{
    Log::info('Authenticate middleware called', ['guards' => $guards]);

    if (empty($guards)) {
        $guards = [null];
    }

    foreach ($guards as $guard) {
        Log::info('Checking guard', ['guard' => $guard]);
        if ($this->auth->guard($guard)->check()) {
            Log::info('Authentication successful', ['guard' => $guard, 'user' => $this->auth->guard($guard)->user()]);
            return $next($request);
        }
    }

    Log::warning('Authentication failed');
    return $this->unauthenticated($request, $guards);
}
}
