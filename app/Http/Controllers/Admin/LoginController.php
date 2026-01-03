<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function index(Request $request)
    {
        Log::info('LoginController::index - Start', [
            'url' => $request->fullUrl(),
            'auth_check' => Auth::check(),
            'session_id' => $request->session()->getId(),
            'has_session' => $request->session()->has('_token'),
        ]);
        
        $user = Auth::user();
        Log::info('LoginController::index - User check', [
            'user' => $user ? $user->id : null,
            'role_id' => $user ? $user->role_id : null,
        ]);
        
        if ($user) {
            if ($user->role_id == 1) {
                Log::info('LoginController::index - Redirecting to admin dashboard');
                return redirect()->route('admin.dashboard.index');
            } elseif ($user->role_id == 2) {
                Log::info('LoginController::index - Redirecting to staff dashboard');
                return redirect()->route('admin.staff.dashboard.index');
            }
        }
        
        Log::info('LoginController::index - Showing login form');
        return view('admin.login.index');
    }

    public function post(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $account = Account::where('email', $credentials['email'])->first();

        if (!$account || !Hash::check($credentials['password'], $account->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không đúng.'],
            ]);
        }

        // Kiểm tra role và chuyển hướng phù hợp
        Log::info('LoginController::post - Attempting login', [
            'email' => $credentials['email'],
            'role_id' => $account->role_id,
        ]);
        
        if ($account->role_id == 1) {
            // Admin - chuyển đến admin dashboard
            if(Auth::attempt($credentials)) {
                Log::info('LoginController::post - Admin login successful', [
                    'user_id' => Auth::id(),
                    'session_id' => $request->session()->getId(),
                ]);
                $request->session()->regenerate();
                return redirect()->route('admin.dashboard.index');
            } else {
                Log::warning('LoginController::post - Admin login failed - Auth::attempt returned false');
            }
        } elseif ($account->role_id == 2) {
            // Staff - chuyển đến staff dashboard
            if(Auth::attempt($credentials)) {
                Log::info('LoginController::post - Staff login successful', [
                    'user_id' => Auth::id(),
                    'session_id' => $request->session()->getId(),
                ]);
                $request->session()->regenerate();
                return redirect()->route('admin.staff.dashboard.index');
            } else {
                Log::warning('LoginController::post - Staff login failed - Auth::attempt returned false');
            }
        } else {
            // User thường - không được phép đăng nhập admin
            Log::warning('LoginController::post - User without admin role tried to login', [
                'role_id' => $account->role_id,
            ]);
            throw ValidationException::withMessages([
                'email' => ['Tài khoản này không có quyền truy cập admin.'],
            ]);
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ])->onlyInput('email');
    }
}
