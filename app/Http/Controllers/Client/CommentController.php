<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\CommentModerationService;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        try {
            Log::info('Comment store hit', [
                'ip' => $request->ip(),
                'post_id' => $request->input('post_id'),
                'content_len' => mb_strlen((string) $request->input('content', '')),
                'ua' => substr((string) $request->userAgent(), 0, 120),
            ]);
            $validated = $request->validate([
                'post_id' => ['required','integer','exists:posts,id'],
                'content' => ['required','string','min:3','max:2000'],
            ]);

            // Giới hạn: 1 IP chỉ được bình luận 1 lần cho mỗi bài viết
            $clientIp = (string) $request->ip();
            $exists = \App\Models\Comment::where('post_id', (int)$validated['post_id'])
                ->where('ip', $clientIp)
                ->exists();
            if ($exists) {
                return back()->withErrors(['content' => 'Bạn đã bình luận bài viết này. Vui lòng không gửi trùng.'])->withInput();
            }

            $moderation = app(CommentModerationService::class)->moderate($validated['content']);
            $label = $moderation['label'] ?? 'pending';

            // Kiểm tra auto approve từ settings
            $autoApprove = Setting::getValue('auto_approve_comments', '0') === '1';
            
            $comment = new Comment();
            $comment->post_id = (int) $validated['post_id'];
            $comment->account_id = Auth::id() ?? 0;
            $comment->content = $validated['content'];
            $comment->ip = $clientIp;
            
            // Nếu auto approve được bật và comment không spam, thì approve
            if ($autoApprove && in_array($label, ['approved','pending'])) {
                $comment->status = 'approved';
            } else {
                $comment->status = in_array($label, ['approved','pending']) ? $label : 'pending';
            }
            
            $comment->save();

            Log::info('Comment store success', [
                'comment_id' => $comment->id,
                'post_id' => $comment->post_id,
                'label' => $comment->status,
            ]);
            $msg = $comment->status === 'approved' ? 'Bình luận của bạn đã được đăng.' : 'Bình luận đã ghi nhận và đang chờ duyệt.';
            return back()->with('success', $msg);
        } catch (\Throwable $th) {
            Log::error('Comment store failed: '.$th->getMessage(), [
                'post_id' => $request->input('post_id'),
            ]);
            return back()->withErrors(['content' => 'Gửi bình luận thất bại, vui lòng thử lại.'])->withInput();
        }
    }
}




