<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostController extends Controller
{
    protected function loadAccount()
    {
        return Auth::user()->load(['profile:id,account_id,name,age,address,avatar,phone,cover_photo,bio,social_link']);
    }

    protected function getPostsByStatus($status)
    {
        return Post::with(['account:id,role_id', 'account.profile:id,account_id,name', 'account.role:id,name', 'category:id,name,slug', 'lastUpdatedBy.profile:id,account_id,name'])
            ->when(is_array($status), fn($q) => $q->whereIn('status', $status))
            ->when(!is_array($status), fn($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(100);
    }

    public function index()
    {
        $account = $this->loadAccount();
        $posts = $this->getPostsByStatus(['published', 'draft']);
        return view('admin.posts.index', compact('posts', 'account'));
    }

    public function posts_published()
    {
        return $this->renderByStatus('published');
    }

    public function posts_draft()
    {
        return $this->renderByStatus('draft');
    }

    public function posts_trash()
    {
        return $this->renderByStatus('archived');
    }

    public function posts_pending()
    {
        return $this->renderByStatus('pending');
    }

    protected function renderByStatus($status)
    {
        $account = $this->loadAccount();
        $posts = $this->getPostsByStatus($status);
        return view('admin.posts.index', compact('posts', 'account'));
    }

    public function posts_new()
    {
        $account = $this->loadAccount();
        $categories = Category::where('status', 1)->get();
        return view('admin.posts.new', compact('account', 'categories'));
    }

    public function handle_posts_new(Request $request)
    {
        $account = $this->loadAccount();
        // Kiểm tra nếu là lưu nháp
        if ($request->status === 'draft') {
            // Validate sơ bộ
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'nullable|integer',
                'content' => 'nullable|string',
                'seo_title' => 'nullable|string|max:255',
                'slug' => 'required|regex:/^[a-z0-9-]+$/|unique:posts,slug',
                'seo_desc' => 'nullable|string|max:500',
                'seo_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'seo_keywords' => 'nullable|string|max:255',
                'tags' => 'nullable|string|max:255'
            ]);
            // Tạo slug nếu chưa có
            $slug = $request->slug ?? Str::slug($validated['name'], '-');

            // Tạo mới bài viết (hoặc model của bạn là gì thì thay thế)
            $post = new Post();
            $post->category_id = $validated['category_id'] ?? null;
            $post->account_id = $account->id;
            $post->name = $validated['name'];
            $post->slug = $slug;
            $post->content = $validated['content'] ?? '';
            $post->views = 0;
            $post->seo_title = $validated['seo_title'] ?? '';
            $post->seo_desc = $validated['seo_desc'] ?? '';
            $post->seo_image = $validated['seo_image'] ?? '';
            $post->seo_keywords = $validated['seo_keywords'] ?? '';
            $post->tags = $validated['tags'] ?? '';
            $post->type = $validated['type'] ??'';
            $post->published_at = Carbon::now()->format('Y-m-d H:i:s');
            $post->last_updated_by = $account->id;
            $post->status = 'draft';

            $post->save();

            return redirect()->back()->with('success', 'Lưu bản nháp thành công!');
        }

    }
}
