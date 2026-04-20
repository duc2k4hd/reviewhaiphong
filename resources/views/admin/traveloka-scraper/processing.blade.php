@extends('admin.layouts.main')

@section('title', 'Đang cào bài viết từ Traveloka.com')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <h5 class="card-header"><i class="fas fa-download"></i> Đang cào bài viết từ Traveloka.com</h5>
            <div class="card-body">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Đang xử lý...</span></div>
                    <h5>Đang cào dữ liệu...</h5>
                    <p class="text-muted">Vui lòng không đóng trang này</p>
                </div>

                <div class="progress mb-3" style="height: 30px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"><span id="progressText">0%</span></div>
                </div>

                <div class="alert alert-info">
                    <div id="statusMessage">Đang khởi tạo...</div>
                    <div class="mt-2"><strong>Tiến trình:</strong> <span id="processedCount">0</span> / <span id="totalCount">{{ $totalUrls }}</span> URL</div>
                    <div class="mt-2"><strong>Thành công:</strong> <span id="successCount" class="text-success">0</span> bài viết</div>
                    <div class="mt-2"><strong>Lỗi:</strong> <span id="errorCount" class="text-danger">0</span> bài viết</div>
                </div>

                <div id="errorList" class="alert alert-danger" style="display: none;">
                    <strong>Danh sách lỗi:</strong>
                    <ul id="errorItems" class="mb-0"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let batchIndex = 0;
let totalUrls  = {{ $totalUrls }};
let isProcessing = false;

function processNextBatch() {
    if (isProcessing) return;
    isProcessing = true;

    const fd = new FormData();
    fd.append('batch_index', batchIndex);
    fd.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("admin.traveloka-scraper.scrape") }}', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: fd,
    })
    .then(r => {
        const ct = r.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            return r.text().then(() => { throw new Error('Server trả về HTML thay vì JSON (có thể do lỗi 500).'); });
        }
        if (!r.ok) return r.json().then(d => { throw new Error(d.message || ('Lỗi server: ' + r.status)); });
        return r.json();
    })
    .then(data => {
        isProcessing = false;
        if (!data.success) {
            if (data.retry) { setTimeout(processNextBatch, 2000); return; }
            document.getElementById('statusMessage').innerHTML = '<strong class="text-danger">Lỗi: ' + (data.message || 'Không xác định') + '</strong>';
            return;
        }
        const processed = data.processed || 0;
        const pct = Math.round((processed / totalUrls) * 100);
        document.getElementById('progressBar').style.width = pct + '%';
        document.getElementById('progressText').textContent = pct + '%';
        document.getElementById('processedCount').textContent = processed;
        document.getElementById('successCount').textContent = data.successCount || 0;
        document.getElementById('errorCount').textContent = data.errorCount || 0;
        document.getElementById('statusMessage').textContent = data.message || 'Đang xử lý...';

        if (data.completed) {
            document.getElementById('progressBar').classList.remove('progress-bar-animated');
            document.getElementById('statusMessage').innerHTML = '<strong class="text-success">' + data.message + '</strong>';
            if (data.errors && data.errors.length) {
                const ul = document.getElementById('errorItems');
                ul.innerHTML = '';
                data.errors.forEach(e => { const li = document.createElement('li'); li.textContent = e; ul.appendChild(li); });
                document.getElementById('errorList').style.display = 'block';
            }
            setTimeout(() => { window.location.href = '{{ route("admin.posts.index") }}'; }, 3000);
        } else {
            batchIndex++;
            setTimeout(processNextBatch, 1500);
        }
    })
    .catch(err => {
        isProcessing = false;
        console.error(err);
        document.getElementById('statusMessage').innerHTML = '<strong class="text-danger">Lỗi: ' + (err.message || 'Không xác định') + '</strong>';
        if (batchIndex < Math.ceil(totalUrls / 3)) {
            setTimeout(processNextBatch, 5000);
        }
    });
}

document.addEventListener('DOMContentLoaded', processNextBatch);
</script>
@endsection
