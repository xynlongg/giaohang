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
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    public function handle($request, Closure $next, ...$guards)
    {
        Log::info('Auth middleware called');
        Log::info('Current Session ID: ' . $request->session()->getId());
        Log::info('Session data: ' . json_encode($request->session()->all()));
        Log::info('Current user ID: ' . auth()->id());
        Log::info('Is user logged in: ' . (auth()->check() ? 'Yes' : 'No'));
        
        if ($this->auth->guard($guards)->check()) {
            Log::info('User is authenticated');
        } else {
            Log::info('User is not authenticated');
        }
        return parent::handle($request, $next, ...$guards);
    }
}
