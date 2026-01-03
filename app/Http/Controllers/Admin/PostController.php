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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\AIArticleService;

class PostController extends Controller
{
    protected function getPostsByStatus($status)
    {
        return Post::with(['account:id,role_id', 'account.profile:id,account_id,name', 'account.role:id,name', 'category:id,name,slug', 'lastUpdatedBy.profile:id,account_id,name'])
            ->when(is_array($status), fn($q) => $q->whereIn('status', $status))
            ->when(!is_array($status), fn($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(100);
    }

    public function index(Request $request)
    {
        $account = $this->loadAccount();
        $perPage = (int) ($request->input('per_page') ?? 20);
        $perPage = $perPage > 0 && $perPage <= 200 ? $perPage : 20;
        $status = $request->input('status');
        $keyword = trim((string) $request->input('q', ''));

        $query = Post::with(['account:id,role_id', 'account.profile:id,account_id,name', 'category:id,name,slug', 'lastUpdatedBy.profile:id,account_id,name'])
            ->when(!empty($status) && in_array($status, ['draft','published','archived','pending']), function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when(!empty($keyword), function ($q) use ($keyword) {
                $like = '%' . str_replace(['%','_'], ['\%','\_'], $keyword) . '%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('name', 'like', $like)
                        ->orWhere('seo_title', 'like', $like)
                        ->orWhere('seo_desc', 'like', $like)
                        ->orWhere('seo_keywords', 'like', $like)
                        ->orWhere('tags', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                });
            })
            ->orderByDesc('created_at');

        $posts = $query->paginate($perPage)->withQueryString();

        return view('admin.posts.index', compact('posts', 'account', 'keyword', 'status', 'perPage'));
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
        $categories = Category::all(); // Lấy tất cả danh mục
        $path = public_path('client/assets/images/posts');

        $images = [
            'name' => [],
            'urls' => []
        ];

        function slugToTitle($slug) {
            return implode(' ', array_map('ucfirst', explode('-', $slug)));
        }

        if (file_exists($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                // Kiểm tra nếu file có phần mở rộng là ảnh
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    // Lưu tên tệp vào mảng 'name'
                    $images['name'][] = slugToTitle(str_replace(['http://127.0.0.1:8000/client/assets/images/posts/', '.webp'], '', $file));
                    
                    // Lưu URL vào mảng 'urls' (nối vào mảng thay vì ghi đè)
                    $images['urls'][] = str_replace('http://127.0.0.1:8000', '', asset('client/assets/images/posts/' . $file));
                }
            }
        }
        return view('admin.posts.new', compact('account', 'categories', 'images'));
    }

    public function handle_posts_new(Request $request)
    {
        if ($request->status == 'draft' || $request->status == 'published') {
            $account = $this->loadAccount();
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category_id' => 'required|integer|exists:categories,id',
                'content' => 'nullable|string',
                'seo_title' => 'nullable|string|max:255',
                'slug' => 'required|regex:/^[a-z0-9-]+$/|unique:posts,slug',
                'seo_desc' => 'nullable|string|max:500',
                'seo_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'seo_keywords' => 'nullable|string|max:255',
                'tags' => 'nullable|string|max:255',
                'type' => 'nullable|string|max:255',
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
            // Tạo mới bài viết
            $postData = [
                'category_id' => $validated['category_id'] ?? null,
                'account_id' => $account->id,
                'name' => $validated['name'],
                'slug' => $slug,
                'content' => $validated['content'] ?? '',
                'views' => 0,
                'seo_title' => $validated['seo_title'] ?? '',
                'seo_desc' => $validated['seo_desc'] ?? '',
                'seo_image' => $filename ?? 'default_image.webp',
                'seo_keywords' => $validated['seo_keywords'] ?? '',
                'tags' => $validated['tags'] ?? '',
                'type' => $validated['type'] ?? '',
                'published_at' => Carbon::now(),
                'last_updated_by' => $account->id,
                'status' => $request->status == 'draft' ? 'draft' : 'published'
            ];
            
            $post = Post::create($postData);

            // Xóa cache sau khi tạo bài viết
            $this->clearCache();

            return redirect()
                ->back()
                ->with([
                    'success' => $request->status == 'draft' ? 'Lưu bản nháp thành công!' : 'Đăng bài thành công',
                    'post_id' => $post->id,
                ]);
        }
        return redirect()->back()->with('error', 'Chỉ cho phép 2 trạng thái Lưu nháp và Xuất bản!');
    }

    /**
     * Test kết nối AI đơn giản
     */
    public function testAISimple(Request $request)
    {
        try {
            Log::info('AI Test Simple Request: ' . json_encode($request->all()));
            
            $prompt = $request->input('prompt', 'Test prompt');
            Log::info('Prompt received: prompt = ' . $prompt . ', length = ' . strlen($prompt));
            
            $aiService = app(AIArticleService::class);
            $result = $aiService->testConnection();
            
            return response()->json([
                'success' => true,
                'connection_test' => $result,
                'prompt_received' => $prompt,
                'prompt_length' => strlen($prompt)
            ]);
            
        } catch (\Exception $e) {
            Log::error('AI Test Simple Error: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi test AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo bài viết bằng AI
     */
    public function generateWithAI(Request $request)
    {
        try {
            // Debug logging
            Log::info('AI Generation Request:', [
                'all_data' => $request->all(),
                'prompt' => $request->input('prompt'),
                'prompt_length' => strlen($request->input('prompt') ?? ''),
                'has_prompt' => $request->has('prompt'),
                'prompt_trimmed' => trim($request->input('prompt') ?? ''),
                'trimmed_length' => strlen(trim($request->input('prompt') ?? ''))
            ]);

            $request->validate([
                'prompt' => 'required|string|min:10|max:2000',
                'category' => 'nullable|string',
                'tone' => 'nullable|string|in:professional,friendly,casual,formal',
                'language' => 'nullable|string|in:Vietnamese,English',
                'type' => 'nullable|string|in:review,am-thuc,du-lich,check-in,dich-vu,tin-tuc'
            ]);

            $aiService = app(AIArticleService::class);
            
            $options = [
                'category' => $request->category ?? 'review',
                'tone' => $request->tone ?? 'professional',
                'language' => $request->language ?? 'Vietnamese',
                'type' => $request->type ?? 'review'
            ];

            $aiData = $aiService->generateArticle($request->prompt, $options);

            return response()->json([
                'success' => true,
                'data' => $aiData,
                'message' => 'Bài viết đã được tạo thành công bằng AI!'
            ]);

        } catch (\Exception $e) {
            Log::error('AI Generation Error: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo bài viết: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(Post $post)
    {
        $account = $this->loadAccount();
        $categories = Category::all(); // Lấy tất cả danh mục thay vì chỉ status = 1
        return view('admin.posts.edit', compact('post', 'account', 'categories'));
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'content' => 'nullable|string',
            'seo_title' => 'nullable|string',
            'slug' => 'required|regex:/^[a-z0-9-]+$/|unique:posts,slug,' . $post->id,
            'seo_desc' => 'nullable|string',
            'seo_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'seo_keywords' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        if ($request->hasFile('seo_image')) {
            $image = $request->file('seo_image');
            $filename = ($request->slug ?? $post->slug) . '-' . \Illuminate\Support\Str::uuid() . '.' . $image->getClientOriginalExtension();
            $path = public_path('client/assets/images/posts');
            if (!\Illuminate\Support\Facades\File::exists($path)) {
                \Illuminate\Support\Facades\File::makeDirectory($path, 0755, true);
            }
            $image->move($path, $filename);
            $post->seo_image = $filename;
        }

        $post->fill([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'], // Đã validate required nên không bao giờ null
            'seo_title' => $validated['seo_title'] ?? '',
            'seo_desc' => $validated['seo_desc'] ?? '',
            'seo_keywords' => $validated['seo_keywords'] ?? '',
            'tags' => $validated['tags'] ?? '',
            'slug' => $request->slug ?? $post->slug,
        ]);
        $post->setAttribute('content', $validated['content'] ?? $post->content);
        $post->last_updated_by = $this->loadAccount()->id;
        $post->save();

        // Xóa cache sau khi cập nhật bài viết
        $this->clearCache();

        return redirect()->route('admin.posts.index')->with('success', 'Cập nhật bài viết thành công');
    }
    /**
     * Cập nhật trạng thái bài viết (publish/draft/archive/restore)
     */
    public function updateStatus(Request $request, Post $post)
    {
        $request->validate([
            'action' => 'required|string|in:publish,draft,archive,restore'
        ]);

        switch ($request->action) {
            case 'publish':
                $post->status = 'published';
                $post->published_at = now();
                break;
            case 'draft':
                $post->status = 'draft';
                break;
            case 'archive':
                $post->status = 'archived';
                break;
            case 'restore':
                $post->status = 'draft';
                break;
        }
        $post->save();

        // Xóa cache sau khi cập nhật trạng thái
        $this->clearCache();

        return back()->with('success', 'Cập nhật trạng thái thành công');
    }

    /**
     * Xoá bài viết vĩnh viễn
     */
    public function destroy(Post $post)
    {
        $post->delete();
        
        // Xóa cache sau khi xóa bài viết
        $this->clearCache();
        
        return back()->with('success', 'Đã xoá bài viết');
    }

    /**
     * Test kết nối AI
     */
    public function testAI()
    {
        try {
            $aiService = app(AIArticleService::class);
            $isConnected = $aiService->testConnection();

            return response()->json([
                'success' => true,
                'connected' => $isConnected,
                'message' => $isConnected ? 'Kết nối AI thành công!' : 'Không thể kết nối AI'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi test AI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa cache khi có thay đổi bài viết
     */
    private function clearCache()
    {
        try {
            // Xóa cache ứng dụng
            Artisan::call('cache:clear');
            
            // Xóa cache view
            Artisan::call('view:clear');
            
            // Xóa cache route
            Artisan::call('route:clear');
            
            // Xóa cache thủ công
            Cache::flush();
            
            // Xóa cache cụ thể cho bài viết
            Cache::forget('posts_list');
            Cache::forget('posts_published');
            Cache::forget('posts_draft');
            Cache::forget('posts_pending');
            Cache::forget('posts_archived');
            
        } catch (\Exception $e) {
            // Log lỗi nếu có
            Log::error('Lỗi khi xóa cache: ' . $e->getMessage());
        }
    }

    // ==================== STAFF METHODS ====================

    /**
     * Danh sách bài viết cho Staff (chỉ hiển thị bài viết của họ)
     */
    public function staffIndex()
    {
        $account = $this->loadAccount();
        $posts = Post::with(['category:id,name,slug'])
            ->where('account_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.staff.posts.index', compact('posts', 'account'));
    }

    /**
     * Form tạo bài viết mới cho Staff
     */
    public function staffCreate()
    {
        $account = $this->loadAccount();
        $categories = Category::where('status', 'active')->get();
        $path = public_path('client/assets/images/posts');

        $images = [
            'name' => [],
            'urls' => []
        ];

        if (is_dir($path)) {
            $files = File::files($path);
            foreach ($files as $file) {
                $images['name'][] = $file->getFilename();
                $images['urls'][] = asset('client/assets/images/posts/' . $file->getFilename());
            }
        }

        return view('admin.staff.posts.create', compact('categories', 'images', 'account'));
    }

    /**
     * Lưu bài viết mới cho Staff (luôn ở trạng thái pending)
     */
    public function staffStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'content' => 'required|string',
            'seo_title' => 'nullable|string',
            'seo_desc' => 'nullable|string',
            'seo_keywords' => 'nullable|string',
            'tags' => 'nullable|string',
            'slug' => 'nullable|string'
        ]);

        $data = $request->all();
        $data['account_id'] = Auth::id();
        $data['status'] = 'pending'; // Staff chỉ có thể tạo bài viết ở trạng thái pending
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $post = Post::create($data);

        // Xóa cache
        $this->clearCache();

        return redirect()->route('admin.staff.posts.index')
            ->with('success', 'Bài viết đã được tạo và chờ duyệt!');
    }

    /**
     * Form chỉnh sửa bài viết cho Staff
     */
    public function staffEdit(Post $post)
    {
        $account = $this->loadAccount();
        
        // Kiểm tra quyền: Staff chỉ có thể sửa bài viết của mình
        if ($post->account_id !== Auth::id()) {
            return redirect()->route('admin.staff.posts.index')
                ->with('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
        }

        $categories = Category::where('status', 'active')->get();
        $path = public_path('client/assets/images/posts');

        $images = [
            'name' => [],
            'urls' => []
        ];

        if (is_dir($path)) {
            $files = File::files($path);
            foreach ($files as $file) {
                $images['name'][] = $file->getFilename();
                $images['urls'][] = asset('client/assets/images/posts/' . $file->getFilename());
            }
        }

        return view('admin.staff.posts.edit', compact('post', 'categories', 'images', 'account'));
    }

    /**
     * Cập nhật bài viết cho Staff
     */
    public function staffUpdate(Request $request, Post $post)
    {
        $account = $this->loadAccount();
        
        // Kiểm tra quyền: Staff chỉ có thể sửa bài viết của mình
        if ($post->account_id !== Auth::id()) {
            return redirect()->route('admin.staff.posts.index')
                ->with('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
        }

        $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'content' => 'required|string',
            'seo_title' => 'nullable|string',
            'seo_desc' => 'nullable|string',
            'seo_keywords' => 'nullable|string',
            'tags' => 'nullable|string',
            'slug' => 'nullable|string'
        ]);

        $data = $request->all();
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['last_updated_by'] = Auth::id();

        // Staff không thể thay đổi trạng thái bài viết
        unset($data['status']);

        $post->update($data);

        // Xóa cache
        $this->clearCache();

        return redirect()->route('admin.staff.posts.index')
            ->with('success', 'Bài viết đã được cập nhật thành công!');
    }

    /**
     * Xóa bài viết cho Staff
     */
    public function staffDestroy(Post $post)
    {
        // Kiểm tra quyền: Staff chỉ có thể xóa bài viết của mình
        if ($post->account_id !== Auth::id()) {
            return redirect()->route('admin.staff.posts.index')
                ->with('error', 'Bạn không có quyền xóa bài viết này!');
        }

        $post->delete();
        
        // Xóa cache
        $this->clearCache();
        
        return redirect()->route('admin.staff.posts.index')
            ->with('success', 'Bài viết đã được xóa thành công!');
    }

    /**
     * Test AI connection nhanh
     */
    public function quickAITest()
    {
        try {
            $aiService = app(AIArticleService::class);
            $result = $aiService->quickTest();
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi test AI: ' . $e->getMessage()
            ], 500);
        }
    }
}
