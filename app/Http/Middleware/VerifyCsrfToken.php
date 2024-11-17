<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Closure;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
        '/orders/*/update',
        // 'api/*', 
        // 'login',
        // 'register',
        'api/*',
        'sanctum/csrf-cookie',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Log::info('VerifyCsrfToken: Request method = ' . $request->method());
        Log::info('VerifyCsrfToken: CSRF token in request = ' . $request->input('_token'));
        Log::info('VerifyCsrfToken: X-CSRF-TOKEN header = ' . $request->header('X-CSRF-TOKEN'));
        
        return parent::handle($request, $next);
    }
}