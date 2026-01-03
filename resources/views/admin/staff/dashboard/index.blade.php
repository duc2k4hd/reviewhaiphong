@extends('admin.layouts.main')

@section('title', 'Staff Dashboard')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Staff Dashboard</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item active">Staff Dashboard</li>
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

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md bg-primary rounded">
                                <i class="bx bx-file-text text-white fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <h5 class="mb-1">{{ $totalPosts }}</h5>
                                <span class="text-muted">Tổng bài viết</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md bg-warning rounded">
                                <i class="bx bx-time text-white fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <h5 class="mb-1">{{ $pendingPosts }}</h5>
                                <span class="text-muted">Chờ duyệt</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md bg-success rounded">
                                <i class="bx bx-check-circle text-white fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <h5 class="mb-1">{{ $publishedPosts }}</h5>
                                <span class="text-muted">Đã xuất bản</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md bg-secondary rounded">
                                <i class="bx bx-edit text-white fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <h5 class="mb-1">{{ $draftPosts }}</h5>
                                <span class="text-muted">Bản nháp</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-plus-circle me-2"></i>
                            Thao tác nhanh
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.staff.posts.new') }}" class="btn btn-primary">
                                <i class="bx bx-plus me-2"></i>
                                Tạo bài viết mới
                            </a>
                            <a href="{{ route('admin.staff.posts.index') }}" class="btn btn-outline-primary">
                                <i class="bx bx-list-ul me-2"></i>
                                Xem tất cả bài viết
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Posts -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-time me-2"></i>
                            Bài viết gần đây
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $recentPosts = \App\Models\Post::where('account_id', Auth::id())
                                ->with('category:id,name,slug')
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
                        @endphp

                        @if($recentPosts->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tiêu đề</th>
                                            <th>Danh mục</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentPosts as $post)
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








