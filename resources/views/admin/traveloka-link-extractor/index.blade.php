@extends('admin.layouts.main')

@section('title', 'Trích xuất link bài viết Traveloka')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <h5 class="card-header">
                <i class="fas fa-link"></i> Trích xuất link bài viết từ Traveloka.com
            </h5>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="alert alert-info">
                    <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Cách dùng</h6>
                    <ol class="mb-0">
                        <li>Nhập một hoặc nhiều từ khóa tìm kiếm (hoặc từ khóa từ URL search Traveloka), mỗi từ khóa trên một dòng.</li>
                        <li>Hệ thống tự phát hiện tổng số trang kết quả và quét tối đa <strong>N trang mỗi từ khóa</strong> (chỉnh ở ô "Max pages").</li>
                        <li>Kết quả: danh sách link bài viết unique (destination / activities / culinary / tips / accommodations) để bạn copy sang form "Cào từ Traveloka.com".</li>
                    </ol>
                </div>

                <form action="{{ route('admin.traveloka-link-extractor.extract') }}" method="POST" id="extractForm">
                    @csrf

                    <div class="mb-4">
                        <label for="urls" class="form-label"><strong>Từ khóa tìm kiếm (mỗi từ khóa một dòng)</strong></label>
                        <textarea
                            class="form-control @error('urls') is-invalid @enderror"
                            id="urls"
                            name="urls"
                            rows="8"
                            placeholder="hải phòng&#10;hà nội&#10;đà nẵng"
                            required
                        >{{ old('urls') }}</textarea>
                        @error('urls')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4" style="max-width: 260px;">
                        <label for="max_pages" class="form-label"><strong>Max pages / URL</strong></label>
                        <input type="number" class="form-control" id="max_pages" name="max_pages" value="{{ old('max_pages', 20) }}" min="1" max="1000">
                        <div class="form-text">Mặc định 20. Tối đa 1000. Traveloka search có thể tới 700+ trang — nhập số lớn để quét hết, nhưng sẽ tốn vài chục phút/URL.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-search"></i> Bắt đầu trích xuất
                        </button>
                        <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </form>

                <div id="loading" class="mt-4" style="display: none;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Đang xử lý...</span></div>
                        <p class="mt-2">Đang tải HTML từ Traveloka và trích link, vui lòng đợi...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('extractForm');
    form && form.addEventListener('submit', function () {
        var btn  = document.getElementById('submitBtn');
        var load = document.getElementById('loading');
        if (btn) btn.disabled = true;
        if (load) load.style.display = 'block';
    });
})();
</script>
@endsection
