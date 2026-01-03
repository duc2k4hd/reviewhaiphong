<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('CheckAdmin::handle - Start', [
            'url' => $request->fullUrl(),
            'auth_check' => Auth::check(),
            'user_id' => Auth::id(),
            'role_id' => Auth::check() ? Auth::user()->role_id : null,
            'session_id' => $request->session()->getId(),
        ]);
        
        if (Auth::check()) {
            // So sánh với cả string và integer vì role_id có thể là string từ database
            $roleId = Auth::user()->role_id;
            if ($roleId != 1 && $roleId !== '1') {
                Log::warning('CheckAdmin::handle - User is not admin', [
                    'role_id' => $roleId,
                    'role_id_type' => gettype($roleId),
                ]);
                return redirect()->route("admin.login.index")
                    ->with("error", "Vui lòng dùng tài khoản ADMIN để sử dụng chức năng này!");
            }
        } else {
            Log::warning('CheckAdmin::handle - Not authenticated');
            return redirect()->route("admin.login.index")
                ->with("error", "Vui lòng đăng nhập để sử dụng chức năng này!");
        }
        
        Log::info('CheckAdmin::handle - Admin check passed, proceeding');
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}
