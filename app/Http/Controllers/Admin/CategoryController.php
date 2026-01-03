<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Hiển thị danh sách categories
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Tìm kiếm
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Lọc theo parent
        if ($request->filled('parent')) {
            if ($request->parent === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent);
            }
        }

        // Sắp xếp
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Phân trang
        $perPage = $request->get('per_page', 15);
        $categories = $query->with('parent', 'children')
            ->withCount(['posts', 'children'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage);
        $categories->withQueryString();

        // Lấy danh sách parent categories để filter
        $parentCategories = Category::whereNull('parent_id')
            ->withCount(['posts', 'children'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Load counts cho tất cả categories (nếu cần)
        if ($categories->count() > 0) {
            $categories->each(function ($category) {
                if (!isset($category->posts_count)) {
                    $category->posts_count = $category->posts()->count();
                }
                if (!isset($category->children_count)) {
                    $category->children_count = $category->children()->count();
                }
            });
        }

        return view('admin.categories.index', compact('categories', 'parentCategories'));
    }

    /**
     * Hiển thị form tạo category mới
     */
    public function create()
    {
        $categories = Category::whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        return view('admin.categories.create', compact('categories'));
    }

    /**
     * Lưu category mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Tự động tạo slug nếu không có
        if (empty($request->slug)) {
            $request->merge(['slug' => Str::slug($request->name)]);
        }

        // Đảm bảo slug là duy nhất
        $slug = $request->slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $request->slug . '-' . $counter;
            $counter++;
        }
        $request->merge(['slug' => $slug]);

        Category::create($request->all());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Danh mục đã được tạo thành công!');
    }

    /**
     * Hiển thị form chỉnh sửa category
     */
    public function edit(Category $category)
    {
        // Load category với counts và đảm bảo timestamps được xử lý
        $category->loadCount(['posts', 'children']);
        
        // Đảm bảo timestamps được cast đúng
        if ($category->created_at) {
            $category->created_at = \Carbon\Carbon::parse($category->created_at);
        }
        if ($category->updated_at) {
            $category->updated_at = \Carbon\Carbon::parse($category->updated_at);
        }
        
        $categories = Category::where('id', '!=', $category->id)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        
        return view('admin.categories.edit', compact('category', 'categories'));
    }

    /**
     * Cập nhật category
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($category->id)
            ],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    if ($value == $category->id) {
                        $fail('Danh mục không thể là parent của chính nó.');
                    }
                }
            ],
            'description' => 'nullable|string|max:1000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Tự động tạo slug nếu không có
        if (empty($request->slug)) {
            $request->merge(['slug' => Str::slug($request->name)]);
        }

        // Đảm bảo slug là duy nhất (trừ category hiện tại)
        $slug = $request->slug;
        $counter = 1;
        while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
            $slug = $request->slug . '-' . $counter;
            $counter++;
        }
        $request->merge(['slug' => $slug]);

        $category->update($request->all());

        return redirect()->route('admin.categories.index')
            ->with('success', 'Danh mục đã được cập nhật thành công!');
    }

    /**
     * Xóa category
     */
    public function destroy(Category $category)
    {
        // Kiểm tra xem có bài viết nào thuộc category này không
        if ($category->posts()->count() > 0) {
            return back()->with('error', 'Không thể xóa danh mục này vì có bài viết thuộc về nó!');
        }

        // Kiểm tra xem có category con nào không
        if ($category->children()->count() > 0) {
            return back()->with('error', 'Không thể xóa danh mục này vì có danh mục con!');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Danh mục đã được xóa thành công!');
    }

    /**
     * Thay đổi trạng thái category
     */
    public function updateStatus(Request $request, Category $category)
    {
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        // Đảm bảo giá trị status là string
        $status = (string) $request->status;
        
        try {
            $category->update(['status' => $status]);
            
            $statusText = $status === 'active' ? 'hiện' : 'ẩn';
            return redirect()->route('admin.categories.index')
                ->with('success', "Danh mục '{$category->name}' đã được {$statusText} thành công!");
        } catch (\Exception $e) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách categories dạng JSON (cho AJAX)
     */
    public function getCategories()
    {
        $categories = Category::select('id', 'name', 'slug', 'parent_id')
            ->where('status', 'active')
            ->withCount(['posts', 'children'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($categories);
    }
}
