<?php

   namespace App\Http\Middleware;

   use Closure;
   use Illuminate\Support\Facades\Log;

   class LogCsrfToken
   {
       public function handle($request, Closure $next)
       {
           Log::info('CSRF Token', [
               'token' => $request->header('X-CSRF-TOKEN'),
               'url' => $request->fullUrl(),
               'method' => $request->method(),
           ]);

           return $next($request);
       }
   }