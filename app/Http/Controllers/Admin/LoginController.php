<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            return redirect()->route('admin.dashboard.index');
        } 
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
        if ($account->role_id == 1) {
            // Admin - chuyển đến admin dashboard
            if(Auth::attempt($credentials)) {
                return redirect()->route('admin.dashboard.index');
            }
        } elseif ($account->role_id == 2) {
            // Staff - chuyển đến staff dashboard
            if(Auth::attempt($credentials)) {
                return redirect()->route('admin.staff.dashboard.index');
            }
        } else {
            // User thường - không được phép đăng nhập admin
            throw ValidationException::withMessages([
                'email' => ['Tài khoản này không có quyền truy cập admin.'],
            ]);
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ])->onlyInput('email');
    }
}
