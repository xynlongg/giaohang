<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$roles
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login'); // Hoặc trang khác nếu người dùng chưa đăng nhập
        }

        if (in_array('admin', $roles) && $user->roles->contains('name', 'admin')) {
            return $next($request);
        }

        if (in_array('Khách hàng', $roles) && $user->roles->contains('name', 'Khách hàng')) {
            // Cho phép truy cập vào các route liên quan đến game, chat, profile
            if ($request->is('game*') || $request->is('chat*') || $request->is('profile*')) {
                return $next($request);
            } else {
                return redirect('/home')->with('error', 'Bạn không có quyền truy cập vào chức năng này.');
            }
        }

        return redirect('/home')->with('error', 'Bạn không có quyền truy cập vào chức năng này.');
    }
}
