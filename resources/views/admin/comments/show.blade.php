@extends('admin.layouts.main')

@section('title', 'Chi tiết bình luận #' . $comment->id)

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Chi tiết bình luận #{{ $comment->id }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.comments.index') }}">Bình luận</a></li>
                            <li class="breadcrumb-item active">Chi tiết</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Comment Details -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thông tin bình luận</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">ID:</label>
                            </div>
                            <div class="col-md-9">
                                <span class="badge bg-primary">{{ $comment->id }}</span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Trạng thái:</label>
                            </div>
                            <div class="col-md-9">
                                <select class="form-select form-select-sm status-select" 
                                        data-comment-id="{{ $comment->id }}" 
                                        style="width: auto;">
                                    <option value="approved" {{ $comment->status == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                                    <option value="pending" {{ $comment->status == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                                    <option value="spam" {{ $comment->status == 'spam' ? 'selected' : '' }}>Spam</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Nội dung:</label>
                            </div>
                            <div class="col-md-9">
                                <div class="border rounded p-3 bg-light">
                                    {{ $comment->content }}
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Ngày tạo:</label>
                            </div>
                            <div class="col-md-9">
                                <span>{{ $comment->created_at->format('d/m/Y H:i:s') }}</span>
                                <small class="text-muted ms-2">({{ $comment->created_at->diffForHumans() }})</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Cập nhật lần cuối:</label>
                            </div>
                            <div class="col-md-9">
                                <span>{{ $comment->updated_at->format('d/m/Y H:i:s') }}</span>
                                <small class="text-muted ms-2">({{ $comment->updated_at->diffForHumans() }})</small>
                            </div>
                        </div>

                        @if($comment->ip)
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">IP Address:</label>
                            </div>
                            <div class="col-md-9">
                                <code>{{ $comment->ip }}</code>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Author Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thông tin người bình luận</h5>
                    </div>
                    <div class="card-body">
                        @if($comment->account && $comment->account->profile)
                            <div class="text-center mb-3">
                                @if($comment->account->profile->avatar)
                                    <img src="{{ asset('storage/' . $comment->account->profile->avatar) }}" 
                                         alt="Avatar" class="rounded-circle" width="80" height="80">
                                @else
                                    <div class="bg-secondary rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                         style="width: 80px; height: 80px;">
                                        <i class="bx bx-user text-white" style="font-size: 40px;"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="text-center">
                                <h6 class="mb-1">{{ $comment->account->profile->name }}</h6>
                                <p class="text-muted mb-2">{{ $comment->account->email }}</p>
                                @if($comment->account->profile->phone)
                                    <p class="text-muted mb-0">{{ $comment->account->profile->phone }}</p>
                                @endif
                            </div>
                        @else
                            <div class="text-center">
                                <div class="bg-secondary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" 
                                     style="width: 80px; height: 80px;">
                                    <i class="bx bx-user text-white" style="font-size: 40px;"></i>
                                </div>
                                <h6 class="mb-1">Khách</h6>
                                <p class="text-muted">Bình luận từ khách</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Post Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Bài viết liên quan</h5>
                    </div>
                    <div class="card-body">
                        @if($comment->post)
                            <h6 class="mb-2">{{ $comment->post->name }}</h6>
                            <p class="text-muted mb-2">{{ Str::limit($comment->post->seo_desc, 100) }}</p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('post.detail', $comment->post->slug) }}" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-show"></i> Xem bài viết
                                </a>
                                <a href="{{ route('admin.posts.edit', $comment->post->id) }}" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bx bx-edit"></i> Sửa bài viết
                                </a>
                            </div>
                        @else
                            <div class="text-center">
                                <i class="bx bx-file-x text-muted" style="font-size: 48px;"></i>
                                <p class="text-muted mt-2">Bài viết đã bị xóa</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thao tác</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" onclick="updateStatus('approved')">
                                <i class="bx bx-check"></i> Duyệt bình luận
                            </button>
                            <button type="button" class="btn btn-warning" onclick="updateStatus('pending')">
                                <i class="bx bx-time"></i> Chuyển về chờ duyệt
                            </button>
                            <button type="button" class="btn btn-danger" onclick="updateStatus('spam')">
                                <i class="bx bx-block"></i> Đánh dấu spam
                            </button>
                            <button type="button" class="btn btn-danger" onclick="deleteComment()">
                                <i class="bx bx-trash"></i> Xóa bình luận
                            </button>
                            <a href="{{ route('admin.comments.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back"></i> Quay lại danh sách
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Status change
document.querySelector('.status-select').addEventListener('change', function() {
    const commentId = this.dataset.commentId;
    const newStatus = this.value;
    
    fetch(`/admin/comments/${commentId}/status`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Có lỗi xảy ra khi cập nhật trạng thái.'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: 'Có lỗi xảy ra khi cập nhật trạng thái.'
        });
    });
});

// Update status function
function updateStatus(status) {
    const commentId = {{ $comment->id }};
    
    fetch(`/admin/comments/${commentId}/status`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the select element
            document.querySelector('.status-select').value = status;
            
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Có lỗi xảy ra khi cập nhật trạng thái.'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi!',
            text: 'Có lỗi xảy ra khi cập nhật trạng thái.'
        });
    });
}

// Delete comment
function deleteComment() {
    const commentId = {{ $comment->id }};
    
    Swal.fire({
        title: 'Xác nhận xóa?',
        text: "Bình luận này sẽ bị xóa vĩnh viễn!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xóa!',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/comments/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '{{ route("admin.comments.index") }}';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra khi xóa bình luận.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi xóa bình luận.'
                });
            });
        }
    });
}
</script>

<style>
.status-select {
    min-width: 150px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.form-label.fw-bold {
    color: #495057;
}
</style>
@endsection
