<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Setting;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request) {
        $settings = Setting::getSettings();
        $user = Account::with(
                [
                'profile', 'role', 
                'posts' => function($query) {
                    $query->where('status', 'published')->orderBy('published_at', 'DESC')->take(6);
                }
            
            ]
        )->where("username", $request->username)->first();
        // dd($user);

        if (!$user) {
            return view('client.templates.errors.404');
        }
        return view("client.profile.index", compact(
            "user",
            "settings"
        ));
    }
}
