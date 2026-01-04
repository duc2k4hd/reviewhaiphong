@extends('admin.layouts.main')

@section('title', 'Kết quả trích xuất link từ Vinpearl.com')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <h5 class="card-header">
                <i class="fas fa-link"></i> Kết quả trích xuất link từ Vinpearl.com
            </h5>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Tổng số URL đã xử lý:</strong> {{ isset($totalUrls) ? $totalUrls : 1 }} URL<br>
                    <strong>Tổng số link đã trích xuất:</strong> {{ $count }} link{{ isset($totalUrls) && $totalUrls > 1 ? ' (đã loại bỏ trùng lặp)' : '' }}
                </div>

                @if(isset($processedUrls) && count($processedUrls) > 0)
                    <div class="alert alert-success">
                        <strong>URL đã xử lý thành công ({{ count($processedUrls) }}):</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($processedUrls as $processed)
                                <li>
                                    <a href="{{ $processed['url'] }}" target="_blank">{{ $processed['url'] }}</a> 
                                    - Tìm thấy {{ $processed['count'] }} link
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(isset($failedUrls) && count($failedUrls) > 0)
                    <div class="alert alert-warning">
                        <strong>URL xử lý thất bại ({{ count($failedUrls) }}):</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($failedUrls as $failed)
                                <li>
                                    <a href="{{ $failed['url'] }}" target="_blank">{{ $failed['url'] }}</a> 
                                    - Lỗi: {{ $failed['error'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <button type="button" class="btn btn-primary" onclick="copyAllLinks()">
                        <i class="fas fa-copy"></i> Copy tất cả link
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="selectAll()">
                        <i class="fas fa-check-square"></i> Chọn tất cả
                    </button>
                    <a href="{{ route('admin.vinpearl-link-extractor.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Link</th>
                                <th width="100">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($links as $index => $link)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ $link }}" target="_blank" class="text-break">{{ $link }}</a>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyLink('{{ $link }}')">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <h6>Danh sách link (để copy):</h6>
                    <textarea 
                        class="form-control" 
                        id="linksTextarea" 
                        rows="15" 
                        readonly
                    >{{ implode("\n", $links) }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyLink(link) {
    navigator.clipboard.writeText(link).then(function() {
        alert('Đã copy link: ' + link);
    }, function() {
        // Fallback cho trình duyệt cũ
        const textarea = document.createElement('textarea');
        textarea.value = link;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        alert('Đã copy link: ' + link);
    });
}

function copyAllLinks() {
    const textarea = document.getElementById('linksTextarea');
    textarea.select();
    document.execCommand('copy');
    alert('Đã copy tất cả {{ $count }} link!');
}

function selectAll() {
    const textarea = document.getElementById('linksTextarea');
    textarea.select();
}
</script>
@endsection

