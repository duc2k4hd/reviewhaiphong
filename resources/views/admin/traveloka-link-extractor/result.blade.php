@extends('admin.layouts.main')

@section('title', 'Kết quả trích xuất link Traveloka')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card mb-3">
            <h5 class="card-header">
                <i class="fas fa-check-circle text-success"></i> Kết quả trích xuất link
            </h5>
            <div class="card-body">
                <div class="alert alert-success">
                    Đã trích xuất được <strong>{{ $count }}</strong> link bài viết unique từ <strong>{{ $totalUrls }}</strong> từ khóa (cap: {{ $maxPages }} trang/từ khóa).
                </div>

                @if (!empty($perCategory))
                    <h6>Chi tiết từng từ khóa</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Từ khóa</th><th>Tổng trang</th><th>Đã quét</th><th>Bài viết</th></tr></thead>
                            <tbody>
                            @foreach ($perCategory as $row)
                                <tr>
                                    <td style="word-break:break-all;">{{ $row['url'] }}</td>
                                    <td>{{ $row['total_pages'] }}</td>
                                    <td>{{ $row['scanned'] }}</td>
                                    <td><strong>{{ $row['article_count'] }}</strong></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if (!empty($failedUrls))
                    <div class="alert alert-warning">
                        <strong>Thất bại:</strong>
                        <ul class="mb-0">
                            @foreach ($failedUrls as $f)
                                <li>{{ $f['url'] }} — {{ $f['error'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Danh sách link (copy sang form "Cào từ Traveloka.com"):</strong>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyLinks()">
                            <i class="fas fa-copy"></i> Copy tất cả
                        </button>
                        <a class="btn btn-sm btn-success" href="{{ route('admin.traveloka-scraper.index') }}?prefill=1" id="goScrape">
                            <i class="fas fa-download"></i> Mở form cào
                        </a>
                    </div>
                </div>

                <textarea id="linksArea" class="form-control" rows="18" readonly>{{ implode("\n", $links) }}</textarea>

                <div class="mt-3">
                    <a href="{{ route('admin.traveloka-link-extractor.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Trích xuất URL khác
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyLinks() {
    var ta = document.getElementById('linksArea');
    ta.select();
    document.execCommand('copy');
    alert('Đã copy ' + ta.value.split('\n').filter(Boolean).length + ' link vào clipboard.');
}

(function () {
    try {
        var links = document.getElementById('linksArea').value;
        sessionStorage.setItem('traveloka_prefill_links', links);
    } catch (e) {}
})();
</script>
@endsection
