<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('CheckLogin::handle - Start', [
            'url' => $request->fullUrl(),
            'auth_check' => Auth::check(),
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'has_session' => $request->session()->has('_token'),
            'session_data' => $request->session()->all(),
        ]);
        
        if(!Auth::check()) {
            Log::warning('CheckLogin::handle - Not authenticated, redirecting to login');
            return redirect()->route("admin.login.index")->with("error", "Vui lòng đăng nhập để sử dụng chức năng này!");
        }
        
        Log::info('CheckLogin::handle - Authenticated, proceeding');
        return $next($request);
    }
}
