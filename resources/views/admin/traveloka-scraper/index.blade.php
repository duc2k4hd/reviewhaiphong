@extends('admin.layouts.main')

@section('title', 'Cào bài viết từ Traveloka.com')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <h5 class="card-header">
                <i class="fas fa-download"></i> Cào bài viết từ Traveloka.com
            </h5>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Thành công!</strong> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <strong>Thông tin:</strong> {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('admin.traveloka-scraper.scrape') }}" method="POST" id="scrapeForm">
                    @csrf
                    <div class="mb-4">
                        <label for="urls" class="form-label"><strong>URL bài viết từ Traveloka.com (mỗi link 1 dòng)</strong></label>
                        <textarea
                            class="form-control @error('urls') is-invalid @enderror"
                            id="urls" name="urls" rows="10"
                            placeholder="https://www.traveloka.com/vi-vn/explore/destination/...&#10;https://www.traveloka.com/vi-vn/explore/culinary/..."
                            required>{{ old('urls') }}</textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> Chỉ nhận URL thuộc <code>/vi-vn/explore/{destination|activities|culinary|tips|accommodations}/...</code>. Dùng mục "Trích xuất link Traveloka" để lấy danh sách URL này.
                        </div>
                        @error('urls')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @if(isset($processedUrlsCount) && $processedUrlsCount > 0)
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong><i class="fas fa-info-circle"></i> Thông tin:</strong>
                            Đã có <strong>{{ $processedUrlsCount }}</strong> URL Traveloka đã cào trước đó. URL trùng sẽ tự động bị bỏ qua.
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('Xóa {{ $processedUrlsCount }} URL đã cào?')) { document.getElementById('clearUrlsForm').submit(); }">
                                    <i class="fas fa-trash"></i> Xóa danh sách ({{ $processedUrlsCount }} URL)
                                </button>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-download"></i> Bắt đầu cào
                        </button>
                        <a href="{{ route('admin.traveloka-link-extractor.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-link"></i> Trích xuất link danh mục
                        </a>
                        <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </form>

                @if(isset($processedUrlsCount) && $processedUrlsCount > 0)
                <form action="{{ route('admin.traveloka-scraper.clear-processed-urls') }}" method="POST" id="clearUrlsForm" style="display: none;">@csrf</form>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('scrapeForm');
    form && form.addEventListener('submit', function () {
        var btn = document.getElementById('submitBtn');
        if (btn) btn.disabled = true;
    });

    if (new URLSearchParams(window.location.search).get('prefill') === '1') {
        try {
            var prefill = sessionStorage.getItem('traveloka_prefill_links');
            if (prefill) {
                var ta = document.getElementById('urls');
                if (ta && !ta.value) { ta.value = prefill; }
            }
        } catch (e) {}
    }
})();
</script>
@endsection
