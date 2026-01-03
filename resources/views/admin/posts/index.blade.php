@extends('admin.layouts.main')

@section('title', 'Tất cả bài viết')

@section('content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <!--  -->
            <div class="card">
                <h5 class="card-header">Tất cả bài viết</h5>
                <div class="p-3">
                    <form class="row g-2 align-items-center" method="get" action="{{ route('admin.posts.index') }}">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="q" value="{{ $keyword ?? '' }}" placeholder="Tìm theo tiêu đề, slug, mô tả, tags...">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                @foreach(['draft'=>'Bản nháp','published'=>'Đã xuất bản','archived'=>'Đã lưu trữ','pending'=>'Chờ duyệt'] as $k=>$v)
                                    <option value="{{ $k }}" {{ ($status ?? '')===$k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="per_page" class="form-select">
                                @foreach([10,20,50,100,200] as $pp)
                                    <option value="{{ $pp }}" {{ ($perPage ?? 20)===$pp ? 'selected' : '' }}>{{ $pp }} / trang</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit">Tìm kiếm</button>
                        </div>
                        <div class="col-md-2">
                            <a class="btn btn-outline-secondary w-100" href="{{ route('admin.posts.index') }}">Xóa lọc</a>
                        </div>
                    </form>
                </div>
                <div class="table-responsive" style="overflow-x:auto;">
                    <table class="table table-striped table-hover align-middle table-sm admin-posts-table">
                        <thead>
                            <tr>
                                <th class="col-stt">STT</th>
                                <th class="col-name">Tên</th>
                                <th class="col-title">Tiêu đề SEO</th>
                                <th class="col-author">Tác giả</th>
                                <th class="col-seoimg">Ảnh SEO</th>
                                <th class="col-status">Trạng thái</th>
                                <th class="col-actions" colspan="3">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $increasing = 1;
                                $statusOrder = [
                                    'draft' => [
                                        '-- Bản nháp',
                                        'task' => ['Xuất bản', 'Sửa', 'Xóa'],
                                        'color' => ['success', 'primary', 'danger'],
                                    ],
                                    'published' => [
                                        'Đã xuất bản',
                                        'task' => ['Xem chi tiết', 'Sửa', 'Xóa'],
                                        'color' => ['info', 'primary', 'danger'],
                                    ],
                                    'archived' => [
                                        'Đã lưu trữ',
                                        'task' => ['Khôi phục', 'Xóa'],
                                        'color' => ['warning', 'danger'],
                                    ],
                                    'pending' => [
                                        'Đang chờ duyệt',
                                        'task' => ['Xem chi tiết', 'Duyệt', 'Xóa'],
                                        'color' => ['info', 'primary', 'danger'],
                                    ],
                                ];
                            @endphp

                            @foreach ($posts as $post)
                                @php $statusKey = $post->status; @endphp
                                    <tr>
                                        <th scope="row" class="col-stt">{{ $increasing++ }}</th>
                                        <td class="col-name">{{ $post->name }}</td>
                                        <td class="col-title">{{ $post->seo_title }}</td>
                                        <td class="col-author">{{ $post->account->profile->name ?? 'Tác giả' }}</td>
                                        <td class="col-seoimg">
                                            @if(!empty($post->seo_image))
                                                <img src="/client/assets/images/posts/{{ $post->seo_image }}" alt="{{ $post->seo_title }}" style="width:64px;height:48px;object-fit:cover;border-radius:4px;border:1px solid #eee;"/>
                                            @else
                                                <div style="width:64px;height:48px;border:1px solid #eee;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#999;font-size:12px;">N/A</div>
                                            @endif
                                        </td>
                                        <td class="col-status">
                                            <span class="badge bg-{{ $statusKey==='published'?'success':($statusKey==='draft'?'secondary':($statusKey==='archived'?'warning':'info')) }}">{{ $statusOrder[$statusKey][0] }}</span>
                                        </td>
                                        <td colspan="3">
                                            <div class="d-flex gap-2">
                                                @if($statusKey === 'draft')
                                                    <form method="POST" action="{{ route('admin.posts.status', $post) }}" onsubmit="return confirm('Xuất bản bài viết này?');">
                                                        @csrf
                                                        <input type="hidden" name="action" value="publish">
                                                        <button type="submit" class="btn btn-sm btn-success">Xuất bản</button>
                                                    </form>
                                                @elseif($statusKey === 'published')
                                                    <form method="POST" action="{{ route('admin.posts.status', $post) }}" onsubmit="return confirm('Lưu trữ bài viết này? Bạn có thể khôi phục sau.');">
                                                        @csrf
                                                        <input type="hidden" name="action" value="archive">
                                                        <button type="submit" class="btn btn-sm btn-warning">Lưu trữ</button>
                                                    </form>
                                                @elseif($statusKey === 'archived')
                                                    <form method="POST" action="{{ route('admin.posts.status', $post) }}" onsubmit="return confirm('Khôi phục bài viết này về trạng thái nháp?');">
                                                        @csrf
                                                        <input type="hidden" name="action" value="restore">
                                                        <button type="submit" class="btn btn-sm btn-info">Khôi phục</button>
                                                    </form>
                                                @endif

                                                <a class="btn btn-sm btn-primary" href="{{ route('admin.posts.edit', $post) }}">Sửa</a>

                                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" onsubmit="return confirm('Xoá vĩnh viễn bài viết này?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Xoá</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                            @endforeach


                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <div>Tổng: {{ $posts->total() }} bài</div>
                    <div class="pagination-wrapper">{{ $posts->links('pagination::bootstrap-5') }}</div>
                </div>
            </div>

            <style>
                .admin-posts-table td, .admin-posts-table th { white-space: normal; vertical-align: middle; }
                .admin-posts-table .col-title { max-width: 360px; }
                .admin-posts-table .col-name { max-width: 260px; }
                .admin-posts-table .col-seoimg { width: 88px; }
                .admin-posts-table .col-actions { width: 220px; }
                .pagination-wrapper .pagination { margin: 0; }
            </style>
            <!--/ Responsive Table -->
        </div>
        <!-- / Content -->

        <!-- Footer -->
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
    </div>
@endsection
