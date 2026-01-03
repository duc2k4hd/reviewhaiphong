@extends('admin.layouts.main')

@section('title', 'Trích xuất Link')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Công cụ /</span> Trích xuất Link
    </h4>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <h5 class="card-header">Trích xuất Link từ URL</h5>
                <div class="card-body">
                    <form action="{{ route('admin.link-extractor.extract') }}" method="POST" id="extractForm">
                        @csrf
                        <div class="mb-3">
                            <label for="urls" class="form-label">Nhập các URL (mỗi URL một dòng):</label>
                            <textarea 
                                class="form-control @error('urls') is-invalid @enderror" 
                                id="urls" 
                                name="urls" 
                                rows="10" 
                                placeholder="https://mia.vn/cam-nang-du-lich/...
https://mia.vn/cam-nang-du-lich/...
..."
                                required
                            >{{ old('urls') }}</textarea>
                            @error('urls')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Tool này sẽ tìm tất cả các thẻ <code>&lt;a class="article-link"&gt;</code> và trích xuất href của chúng.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            Trích xuất Link
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('extractForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
});
</script>
@endsection

