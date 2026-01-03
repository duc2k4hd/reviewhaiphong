<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;

class DashboardController extends Controller
{
    public function index()
    {
        // Nên để middleware('auth') ở route thay vì check thủ công,
        // nhưng vẫn giữ nguyên flow nếu bạn muốn:
        if (!Auth::check()) {
            // Tránh view tên lạ "about(404)" dễ gây lỗi/redirect
            return abort(404);
        }

        // Eager load profile với cột cần thiết (bạn đã làm đúng)
        $account = Auth::user()->load([
            'profile:id,account_id,name,age,address,avatar,phone,cover_photo,bio,social_link'
        ]);

        return view('admin.dashboard.index', compact('account'));
    }

    /**
     * Dashboard cho Staff
     */
    public function staffIndex()
    {
        if (!Auth::check()) {
            return abort(404);
        }

        $account = Auth::user()->load([
            'profile:id,account_id,name,age,address,avatar,phone,cover_photo,bio,social_link'
        ]);

        $userId = Auth::id();

        // ✅ Gộp 1 query thay vì 4 query count
        // MySQL: SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END)
        $counts = Post::where('account_id', $userId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft
            ', ['pending', 'published', 'draft'])
            ->first();

        // Nếu bạn thích withCount (cũng 1 query):
        // $counts = Post::where('account_id', $userId)
        //     ->selectRaw('COUNT(*) as total')
        //     ->withCount([
        //         'wherePending as pending' => fn($q) => $q->where('status', 'pending'),
        //         'wherePublished as published' => fn($q) => $q->where('status', 'published'),
        //         'whereDraft as draft' => fn($q) => $q->where('status', 'draft'),
        //     ])->first();

        $totalPosts     = (int) ($counts->total ?? 0);
        $pendingPosts   = (int) ($counts->pending ?? 0);
        $publishedPosts = (int) ($counts->published ?? 0);
        $draftPosts     = (int) ($counts->draft ?? 0);

        return view('admin.staff.dashboard.index', compact(
            'account', 'totalPosts', 'pendingPosts', 'publishedPosts', 'draftPosts'
        ));
    }
}
