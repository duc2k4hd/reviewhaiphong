<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::with(['post', 'account.profile']);


        // Lọc theo status
        $statusParam = $request->query('status');
        if ($statusParam !== null && $statusParam !== '') {
            $query->where('status', $statusParam);
        }

        // Lọc theo bài viết
        $postIdParam = $request->query('post_id');
        if ($postIdParam !== null && $postIdParam !== '') {
            $postId = (int) trim((string) $postIdParam);
            if ($postId > 0) {
                $query->where('post_id', '=', $postId);
            }
        }

        // Tìm kiếm theo nội dung
        $searchParam = $request->query('search');
        if ($searchParam !== null && $searchParam !== '') {
            $query->where('content', 'like', '%' . $searchParam . '%');
        }

        // Sắp xếp
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $comments = $query->paginate(20);

        // Lấy danh sách bài viết có bình luận (đảm bảo distinct theo cột post_id và không null)
        $postIds = Comment::select('post_id')
            ->whereNotNull('post_id')
            ->distinct()
            ->pluck('post_id')
            ->toArray();
        $posts = Post::whereIn('id', $postIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // Thống kê
        $stats = [
            'total' => Comment::count(),
            'approved' => Comment::where('status', 'approved')->count(),
            'pending' => Comment::where('status', 'pending')->count(),
            'spam' => Comment::where('status', 'spam')->count(),
        ];

        return view('admin.comments.index', compact('comments', 'posts', 'stats'));
    }

    public function show($id)
    {
        $comment = Comment::with(['post', 'account.profile'])->findOrFail($id);
        return view('admin.comments.show', compact('comment'));
    }

    public function updateStatus(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        $oldStatus = $comment->status;
        
        $validated = $request->validate([
            'status' => 'required|in:approved,pending,spam'
        ]);

        $comment->status = $validated['status'];
        $comment->save();

        Log::info('Comment status updated', [
            'comment_id' => $comment->id,
            'old_status' => $oldStatus,
            'new_status' => $comment->status,
            'admin_id' => auth()->guard('web')->user()->id ?? 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái bình luận thành công!',
            'status' => $comment->status
        ]);
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        Log::info('Comment deleted', [
            'comment_id' => $id,
            'admin_id' => auth()->guard('web')->user()->id ?? 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Xóa bình luận thành công!'
        ]);
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,pending,spam,delete',
            'comment_ids' => 'required|array',
            'comment_ids.*' => 'integer|exists:comments,id'
        ]);

        $commentIds = $validated['comment_ids'];
        $action = $validated['action'];

        switch ($action) {
            case 'approve':
                Comment::whereIn('id', $commentIds)->update(['status' => 'approved']);
                $message = 'Đã duyệt ' . count($commentIds) . ' bình luận!';
                break;
            case 'pending':
                Comment::whereIn('id', $commentIds)->update(['status' => 'pending']);
                $message = 'Đã chuyển ' . count($commentIds) . ' bình luận về chờ duyệt!';
                break;
            case 'spam':
                Comment::whereIn('id', $commentIds)->update(['status' => 'spam']);
                $message = 'Đã đánh dấu ' . count($commentIds) . ' bình luận là spam!';
                break;
            case 'delete':
                Comment::whereIn('id', $commentIds)->delete();
                $message = 'Đã xóa ' . count($commentIds) . ' bình luận!';
                break;
        }

        Log::info('Bulk comment action', [
            'action' => $action,
            'comment_ids' => $commentIds,
            'admin_id' => auth()->guard('web')->user()->id ?? 0
        ]);

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
