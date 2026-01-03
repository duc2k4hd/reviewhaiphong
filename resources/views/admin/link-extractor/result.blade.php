@extends('admin.layouts.main')

@section('title', 'Kết quả Trích xuất Link')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Công cụ /</span> Kết quả Trích xuất Link
    </h4>

    @if (!empty($errors))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Lỗi:</h6>
            <ul class="mb-0">
                @foreach ($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <h5 class="card-header">
                    Kết quả: Tìm thấy <strong>{{ $total }}</strong> link
                    <button class="btn btn-sm btn-primary float-end" onclick="copyAllLinks()">
                        <i class="fas fa-copy"></i> Copy tất cả
                    </button>
                </h5>
                <div class="card-body">
                    <div class="mb-3">
                        <textarea 
                            class="form-control" 
                            id="linksTextarea" 
                            rows="20" 
                            readonly
                            style="font-family: monospace; font-size: 12px;"
                        >{{ implode("\n", $links) }}</textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="copyAllLinks()">
                            <i class="fas fa-copy"></i> Copy tất cả
                        </button>
                        <a href="{{ route('admin.link-extractor.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyAllLinks() {
    const textarea = document.getElementById('linksTextarea');
    textarea.select();
    textarea.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        alert('Đã copy ' + {{ $total }} + ' link vào clipboard!');
    } catch (err) {
        // Fallback for modern browsers
        navigator.clipboard.writeText(textarea.value).then(function() {
            alert('Đã copy ' + {{ $total }} + ' link vào clipboard!');
        }, function(err) {
            alert('Lỗi khi copy: ' + err);
        });
    }
}
</script>
@endsection

