<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckStaff
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // Staff có thể là admin (role_id = 1) hoặc staff (role_id = 2)
            if (Auth::user()->role_id > 2) {
                return redirect()->route("admin.login.index")
                    ->with("error", "Bạn không có quyền truy cập chức năng này!");
            }
        } else {
            return redirect()->route("admin.login.index")
                ->with("error", "Vui lòng đăng nhập để sử dụng chức năng này!");
        }
        
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}
