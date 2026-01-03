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

    /**
     * Dashboard cho Staff
     */
    public function staffIndex() {
        if(Auth::check()) {
            $account = Auth::user()->load([
                'profile:id,account_id,name,age,address,avatar,phone,cover_photo,bio,social_link'
            ]);
            
            // Lấy thống kê bài viết của Staff
            $totalPosts = \App\Models\Post::where('account_id', Auth::id())->count();
            $pendingPosts = \App\Models\Post::where('account_id', Auth::id())->where('status', 'pending')->count();
            $publishedPosts = \App\Models\Post::where('account_id', Auth::id())->where('status', 'published')->count();
            $draftPosts = \App\Models\Post::where('account_id', Auth::id())->where('status', 'draft')->count();
            
            return view("admin.staff.dashboard.index", compact(
                "account", "totalPosts", "pendingPosts", "publishedPosts", "draftPosts"
            ));
        }
        return view("about(404)");
    }
}
