@extends('admin.layouts.main')

@section('title', 'Cào bài viết từ Vinpearl.com')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <h5 class="card-header">
                <i class="fas fa-download"></i> Cào bài viết từ Vinpearl.com
            </h5>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Thành công!</strong> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

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

                <form action="{{ route('admin.vinpearl-scraper.scrape') }}" method="POST" id="scrapeForm">
                    @csrf
                    <div class="mb-4">
                        <label for="urls" class="form-label">
                            <strong>URL bài viết từ Vinpearl.com (mỗi link 1 dòng)</strong>
                        </label>
                        <textarea 
                            class="form-control @error('urls') is-invalid @enderror" 
                            id="urls" 
                            name="urls" 
                            rows="10"
                            placeholder="https://vinpearl.com/vi/...&#10;https://vinpearl.com/vi/...&#10;https://vinpearl.com/vi/..."
                            required
                        >{{ old('urls') }}</textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> Nhập nhiều link bài viết từ website vinpearl.com, mỗi link trên một dòng để cào tự động
                        </div>
                        @error('urls')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <strong>Chọn danh mục để thêm bài viết:</strong>
                        </label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="category" id="category-du-lich" value="du-lich" {{ old('category', 'du-lich') === 'du-lich' ? 'checked' : '' }}>
                            <label class="form-check-label" for="category-du-lich">
                                Du lịch
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="category" id="category-am-thuc" value="am-thuc" {{ old('category') === 'am-thuc' ? 'checked' : '' }}>
                            <label class="form-check-label" for="category-am-thuc">
                                Ẩm thực
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="category" id="category-check-in" value="check-in" {{ old('category') === 'check-in' ? 'checked' : '' }}>
                            <label class="form-check-label" for="category-check-in">
                                Check-in
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="category" id="category-dich-vu" value="dich-vu" {{ old('category') === 'dich-vu' ? 'checked' : '' }}>
                            <label class="form-check-label" for="category-dich-vu">
                                Dịch vụ
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="category" id="category-review-tong-hop" value="review-tong-hop" {{ old('category') === 'review-tong-hop' ? 'checked' : '' }}>
                            <label class="form-check-label" for="category-review-tong-hop">
                                Review tổng hợp
                            </label>
                        </div>
                        @error('category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Quy trình cào tự động:</h6>
                        <ol class="mb-0">
                            <li>Cào tiêu đề và viết lại bằng AI Gemini (SEO < 70 ký tự)</li>
                            <li>Cào meta description và viết lại bằng AI Gemini (SEO < 160 ký tự)</li>
                            <li>Cào nội dung từ bài viết (tự động decode HTML entities và URL encoding)</li>
                            <li>Loại bỏ các comment HTML và style tag không cần thiết</li>
                            <li>Decode các ID trong heading bị mã hóa (ví dụ: 3.+Đặt+phòng&lt;!--{cke_protected}...)</li>
                            <li>Chuyển tất cả thẻ <code>div</code> thành <code>p</code></li>
                            <li>Loại bỏ class và attributes không cần thiết</li>
                            <li>Download và lưu tất cả ảnh vào <code>client/assets/images/posts</code></li>
                            <li>Thay thế link URL từ <code>https://vinpearl.com/...</code> thành <code>https://reviewhaiphong.io.vn/...</code></li>
                            <li>Thay thế link "Xem thêm" bằng bài viết random từ database (ưu tiên danh mục "Du lịch")</li>
                            <li>Lấy ảnh đại diện từ meta og:image hoặc ảnh đầu tiên trong bài</li>
                            <li>Tự động loại bỏ tất cả nội dung liên quan đến vinpearl.com</li>
                            <li>Tự động thêm vào danh mục đã chọn (Du lịch, Ẩm thực, Check-in, Dịch vụ, Review tổng hợp)</li>
                        </ol>
                        <p class="mb-0 mt-2"><strong>Lưu ý:</strong> Có thể nhập nhiều link, mỗi link trên một dòng. Hệ thống sẽ cào từng link một. Tất cả nội dung liên quan đến vinpearl.com sẽ được tự động chuyển đổi thành Review Hải Phòng.</p>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-download"></i> Bắt đầu cào
                        </button>
                        <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </form>

                <div id="loading" class="mt-4" style="display: none;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang xử lý...</span>
                        </div>
                        <p class="mt-2">Đang cào dữ liệu và xử lý bằng AI, vui lòng đợi...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('scrapeForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const loading = document.getElementById('loading');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    loading.style.display = 'block';
});
</script>
@endsection

