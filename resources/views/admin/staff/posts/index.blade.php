@extends('admin.layouts.main')

@section('title', 'Danh sách bài viết - Staff')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Danh sách bài viết</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.staff.dashboard.index') }}">Staff Dashboard</a></li>
                            <li class="breadcrumb-item active">Bài viết</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <a href="{{ route('admin.staff.dashboard.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-2"></i>
                            Quay lại Dashboard
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('admin.staff.posts.new') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-2"></i>
                            Tạo bài viết mới
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-file-text me-2"></i>
                            Bài viết của bạn
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($posts->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tiêu đề</th>
                                            <th>Danh mục</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                            <th>Ngày cập nhật</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($posts as $post)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm bg-light-secondary rounded me-3">
                                                            <span class="avatar-initial rounded bg-secondary">
                                                                {{ strtoupper(substr($post->name, 0, 1)) }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ Str::limit($post->name, 50) }}</h6>
                                                            <small class="text-muted">{{ $post->slug }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-label-primary">{{ $post->category->name ?? 'N/A' }}</span>
                                                </td>
                                                <td>
                                                    @switch($post->status)
                                                        @case('published')
                                                            <span class="badge bg-success">Đã xuất bản</span>
                                                            @break
                                                        @case('pending')
                                                            <span class="badge bg-warning">Chờ duyệt</span>
                                                            @break
                                                        @case('draft')
                                                            <span class="badge bg-secondary">Bản nháp</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ $post->status }}</span>
                                                    @endswitch
                                                </td>
                                                <td>{{ $post->created_at->format('d/m/Y H:i') }}</td>
                                                <td>{{ $post->updated_at->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ route('admin.staff.posts.edit', $post) }}" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="bx bx-edit"></i>
                                                        </a>
                                                        <form action="{{ route('admin.staff.posts.destroy', $post) }}" 
                                                              method="POST" 
                                                              onsubmit="return confirm('Bạn có chắc muốn xóa bài viết này?')"
                                                              style="display: inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-center mt-4">
                                {{ $posts->links() }}
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-file-text text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">Bạn chưa có bài viết nào</p>
                                <a href="{{ route('admin.staff.posts.new') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-2"></i>
                                    Tạo bài viết đầu tiên
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection








