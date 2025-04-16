<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index() {
        if(Auth::check()) {
            $account = Auth::user()->load([
                'profile:id,account_id,name,age,address,avatar,phone,cover_photo,bio,social_link'
            ]);
            
            return view("admin.dashboard.index", compact(
                "account"
            ));
        }
        return view("about(404)");
    }
}
