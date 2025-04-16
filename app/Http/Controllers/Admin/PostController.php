<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
        $path = public_path('client/assets/images/posts');

        $images = [];

        if (file_exists($path)) {
            $files = scandir($path);

            foreach ($files as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $images[] = asset('client/assets/images/posts/' . $file);
                }
            }
        }
        // dd($images);
        return view('admin.posts.new', compact('account', 'categories', 'images'));
    }

    public function handle_posts_new(Request $request)
    {
        if ($request->status == 'draft' || $request->status == 'published') {
            $account = $this->loadAccount();
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'nullable|integer',
                'content' => 'nullable|string',
                'seo_title' => 'nullable|string|max:255',
                'slug' => 'required|regex:/^[a-z0-9-]+$/|unique:posts,slug',
                'seo_desc' => 'nullable|string|max:500',
                'seo_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'seo_keywords' => 'nullable|string|max:255',
                'tags' => 'nullable|string|max:255',
            ]);
            // Tạo slug nếu chưa có
            $slug = $request->slug ?? Str::slug($validated['name'], '-');
            if ($request->hasFile('seo_image')) {
                $image = $request->file('seo_image');
                $filename = $slug . '-' . Str::uuid() . '.' . $image->getClientOriginalExtension();
                $path = public_path('client/assets/images/posts');

                // Tạo folder nếu chưa tồn tại
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }

                // Lưu ảnh vào public/assets/images/posts/
                $image->move($path, $filename);
            }
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
            $post->seo_image = $filename ?? 'default_image.webp';
            $post->seo_keywords = $validated['seo_keywords'] ?? '';
            $post->tags = $validated['tags'] ?? '';
            $post->type = $validated['type'] ?? '';
            $post->published_at = Carbon::now()->format('Y-m-d H:i:s');
            $post->last_updated_by = $account->id;
            $post->status = $request->status == 'draft' ? 'draft' : 'published';

            $post->save();

            return redirect()
                ->back()
                ->with([
                    'success' => $request->status == 'draft' ? 'Lưu bản nháp thành công!' : 'Đăng bài thành công',
                    'post_id' => $post->id,
                ]);
        }
        return redirect()->back()->with('error', 'Chỉ cho phép 2 trạng thái Lưu nháp và Xuất bản!');
    }
}
