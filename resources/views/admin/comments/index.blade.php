@extends('admin.layouts.main')

@section('title', 'Quản lý bình luận')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Quản lý bình luận</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Bình luận</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card mini-stats-wid">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium">Tổng bình luận</p>
                                <h4 class="mb-0">{{ number_format($stats['total']) }}</h4>
                            </div>
                            <div class="flex-shrink-0 align-self-center">
                                <div class="mini-stat-icon avatar-sm rounded-circle bg-primary align-self-center">
                                    <span class="avatar-title">
                                        <i class="bx bx-comment font-size-24"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card mini-stats-wid">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium">Đã duyệt</p>
                                <h4 class="mb-0 text-success">{{ number_format($stats['approved']) }}</h4>
                            </div>
                            <div class="flex-shrink-0 align-self-center">
                                <div class="mini-stat-icon avatar-sm rounded-circle bg-success align-self-center">
                                    <span class="avatar-title">
                                        <i class="bx bx-check font-size-24"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card mini-stats-wid">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium">Chờ duyệt</p>
                                <h4 class="mb-0 text-warning">{{ number_format($stats['pending']) }}</h4>
                            </div>
                            <div class="flex-shrink-0 align-self-center">
                                <div class="mini-stat-icon avatar-sm rounded-circle bg-warning align-self-center">
                                    <span class="avatar-title">
                                        <i class="bx bx-time font-size-24"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card mini-stats-wid">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="text-muted fw-medium">Spam</p>
                                <h4 class="mb-0 text-danger">{{ number_format($stats['spam']) }}</h4>
                            </div>
                            <div class="flex-shrink-0 align-self-center">
                                <div class="mini-stat-icon avatar-sm rounded-circle bg-danger align-self-center">
                                    <span class="avatar-title">
                                        <i class="bx bx-block font-size-24"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.comments.index') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">Tất cả</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                                <option value="spam" {{ request('status') == 'spam' ? 'selected' : '' }}>Spam</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bài viết</label>
                            <select name="post_id" class="form-select" onchange="onChangePostFilter(this)">
                                <option value="">Tất cả bài viết</option>
                                @foreach($posts as $post)
                                    <option value="{{ $post->id }}" {{ (string)request('post_id') === (string)$post->id ? 'selected' : '' }}>
                                        {{ Str::limit($post->name, 50) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sắp xếp</label>
                            <select name="sort_by" class="form-select" onchange="this.form.submit()">
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Ngày tạo</option>
                                <option value="content" {{ request('sort_by') == 'content' ? 'selected' : '' }}>Nội dung</option>
                                <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Trạng thái</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Thứ tự</label>
                            <select name="sort_order" class="form-select" onchange="this.form.submit()">
                                <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Giảm dần</option>
                                <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Tăng dần</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tìm kiếm</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Tìm trong nội dung bình luận..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bx bx-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <a href="{{ route('admin.comments.index') }}" class="btn btn-secondary me-2">
                                <i class="bx bx-refresh"></i> Làm mới
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Comments Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Danh sách bình luận</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm" onclick="bulkAction('approve')">
                        <i class="bx bx-check"></i> Duyệt
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" onclick="bulkAction('pending')">
                        <i class="bx bx-time"></i> Chờ duyệt
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="bulkAction('spam')">
                        <i class="bx bx-block"></i> Spam
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="bulkAction('delete')">
                        <i class="bx bx-trash"></i> Xóa
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($comments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th width="80">ID</th>
                                    <th>Nội dung</th>
                                    <th>Bài viết</th>
                                    <th>Người bình luận</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                    <th width="150">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comments as $comment)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input comment-checkbox" value="{{ $comment->id }}">
                                        </td>
                                        <td>{{ $comment->id }}</td>
                                        <td>
                                            <div class="comment-content">
                                                <div class="fw-medium">{{ Str::limit($comment->content, 100) }}</div>
                                                @if(strlen($comment->content) > 100)
                                                    <small class="text-muted">Xem thêm...</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($comment->post)
                                                <a href="{{ route('post.detail', $comment->post->slug) }}" target="_blank" class="text-decoration-none">
                                                    {{ Str::limit($comment->post->name, 50) }}
                                                </a>
                                            @else
                                                <span class="text-muted">Bài viết đã bị xóa</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($comment->account && $comment->account->profile)
                                                <div class="d-flex align-items-center">
                                                    @if($comment->account->profile->avatar)
                                                        <img src="{{ asset('storage/' . $comment->account->profile->avatar) }}" 
                                                             alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                                                    @else
                                                        <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                             style="width: 32px; height: 32px;">
                                                            <i class="bx bx-user text-white"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="fw-medium">{{ $comment->account->profile->name }}</div>
                                                        <small class="text-muted">{{ $comment->account->email }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">Khách</span>
                                            @endif
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm status-select" 
                                                    data-comment-id="{{ $comment->id }}" 
                                                    style="width: auto;">
                                                <option value="approved" {{ $comment->status == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                                                <option value="pending" {{ $comment->status == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                                                <option value="spam" {{ $comment->status == 'spam' ? 'selected' : '' }}>Spam</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div>{{ $comment->created_at->format('d/m/Y') }}</div>
                                            <small class="text-muted">{{ $comment->created_at->format('H:i') }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.comments.show', $comment->id) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteComment({{ $comment->id }})" title="Xóa">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $comments->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-comment-x font-size-48 text-muted"></i>
                        <h5 class="mt-3">Không có bình luận nào</h5>
                        <p class="text-muted">Chưa có bình luận nào trong hệ thống.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function onChangePostFilter(selectEl) {
    const form = document.getElementById('filterForm');
    // Khi đổi bài viết: reset trang, xóa trạng thái và ô tìm kiếm để tránh lọc chéo khiến rỗng
    const statusSelect = form.querySelector('select[name="status"]');
    if (statusSelect) statusSelect.value = '';
    const searchInput = form.querySelector('input[name="search"]');
    if (searchInput) searchInput.value = '';
    // Xóa tham số phân trang nếu tồn tại
    const pageInput = form.querySelector('input[name="page"]');
    if (pageInput) pageInput.remove();
    form.submit();
}

// Select all checkbox
const selectAllEl = document.getElementById('selectAll');
if (selectAllEl) {
    selectAllEl.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.comment-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
}

// Status change
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up status change listeners');
    
    const statusSelects = document.querySelectorAll('.status-select');
    console.log('Found status selects:', statusSelects.length);
    
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const commentId = this.dataset.commentId;
            const newStatus = this.value;
            
            console.log('Changing status for comment:', commentId, 'to:', newStatus);
            
            // Test SweetAlert2
            Swal.fire({
                icon: 'info',
                title: 'Đang cập nhật...',
                text: 'Vui lòng chờ',
                timer: 1000,
                showConfirmButton: false
            });
            
            fetch(`/admin/comments/${commentId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: data.message || 'Có lỗi xảy ra khi cập nhật trạng thái.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi cập nhật trạng thái: ' + error.message
                });
            });
        });
    });
});

// Delete comment
function deleteComment(commentId) {
    console.log('Deleting comment:', commentId);
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
            .then(response => {
                console.log('Delete response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Delete response data:', data);
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
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

// Bulk actions
function bulkAction(action) {
    const selectedComments = Array.from(document.querySelectorAll('.comment-checkbox:checked'))
        .map(checkbox => checkbox.value);
    
    if (selectedComments.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cảnh báo!',
            text: 'Vui lòng chọn ít nhất một bình luận.'
        });
        return;
    }

    let actionText = '';
    switch(action) {
        case 'approve': actionText = 'duyệt'; break;
        case 'pending': actionText = 'chuyển về chờ duyệt'; break;
        case 'spam': actionText = 'đánh dấu spam'; break;
        case 'delete': actionText = 'xóa'; break;
    }

    Swal.fire({
        title: 'Xác nhận thao tác?',
        text: `Bạn có chắc muốn ${actionText} ${selectedComments.length} bình luận đã chọn?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Xác nhận!',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/admin/comments/bulk-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    action: action,
                    comment_ids: selectedComments
                })
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
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi!',
                        text: 'Có lỗi xảy ra khi thực hiện thao tác.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: 'Có lỗi xảy ra khi thực hiện thao tác.'
                });
            });
        }
    });
}
</script>

<style>
.comment-content {
    max-width: 300px;
}

.status-select {
    min-width: 120px;
}

.table th {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.table td {
    vertical-align: middle;
}

.mini-stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-title {
    color: white;
    font-size: 18px;
}
</style>
@endsection
