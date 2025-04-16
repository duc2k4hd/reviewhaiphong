@extends('admin.layouts.main')

@section('title', 'Tất cả bài viết')

@section('content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <!--  -->
            <div class="card">
                <h5 class="card-header">Tất cả bài viết</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>ID</th>
                                <th>Tiêu đề SEO</th>
                                <th>Danh mục</th>
                                <th>Tác giả</th>
                                <th>Ngày đăng</th>
                                {{-- <th>Tên</th> --}}
                                {{-- <th>Mô tả SEO</th> --}}
                                {{-- <th>Từ khóa SEO</th> --}}
                                <th>Ảnh SEO</th>
                                <th>Type</th>
                                {{-- <th>Tags</th> --}}
                                <th>Đường dẫn</th>
                                <th>Trạng thái</th>
                                <th>Người sửa cuối</th>
                                <th colspan="3">Tác vụ</th>
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

                            @foreach (['draft', 'published', 'archived', 'pending'] as $statusKey)
                                @foreach ($posts->where('status', $statusKey) as $post)
                                    <tr>
                                        <th scope="row">{{ $increasing++ }}</th>
                                        <th>{{ $post->id }}</th>
                                        <td>{{ $post->seo_title }}</td>
                                        <td>{{ $post->category->name }}</td>
                                        <td>{{ $post->account->profile->name . ' (' . $post->account->id . ')' }}</td>
                                        <td>{{ $post->published_at }}</td>
                                        <td>{{ $post->seo_image }}</td>
                                        <td>{{ $post->type }}</td>
                                        <td>
                                            <a
                                                href="{{ $settings['site_url'] }}/{{ $post->category->slug }}/{{ $post->slug }}">
                                                {{ $settings['site_url'] }}/{{ $post->category->slug }}/{{ $post->slug }}
                                            </a>
                                        </td>
                                        <td class="text-end">{{ $statusOrder[$statusKey][0] }}</td>
                                        <td>
                                            {{ isset($post->lastUpdatedBy->profile)
                                                ? $post->lastUpdatedBy->profile->name . ' (' . $post->lastUpdatedBy->id . ')'
                                                : $post->account->profile->name . ' (' . $post->account->id . ')' }}
                                        </td>
                                        @foreach ($statusOrder[$statusKey]['task'] as $i => $task)
                                            <td>
                                                <button class="btn btn-sm btn-{{ $statusOrder[$statusKey]['color'][$i] }}">{{ $task }}</button>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach


                        </tbody>
                    </table>
                </div>
            </div>
            <!--/ Responsive Table -->
        </div>
        <!-- / Content -->

        <!-- Footer -->
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
    </div>
@endsection
