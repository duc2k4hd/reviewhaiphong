@extends('admin.layouts.main')

@section('title', 'Đang cào bài viết từ Vinpearl.com')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <h5 class="card-header">
                <i class="fas fa-download"></i> Đang cào bài viết từ Vinpearl.com
            </h5>
            <div class="card-body">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Đang xử lý...</span>
                    </div>
                    <h5>Đang cào dữ liệu và xử lý bằng AI...</h5>
                    <p class="text-muted">Vui lòng không đóng trang này</p>
                </div>

                <div class="progress mb-3" style="height: 30px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">
                        <span id="progressText">0%</span>
                    </div>
                </div>

                <div class="alert alert-info">
                    <div id="statusMessage">Đang khởi tạo...</div>
                    <div class="mt-2">
                        <strong>Tiến trình:</strong> <span id="processedCount">0</span> / <span id="totalCount">{{ $totalUrls }}</span> URL
                    </div>
                    <div class="mt-2">
                        <strong>Thành công:</strong> <span id="successCount" class="text-success">0</span> bài viết
                    </div>
                    <div class="mt-2">
                        <strong>Lỗi:</strong> <span id="errorCount" class="text-danger">0</span> bài viết
                    </div>
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
let totalUrls = {{ $totalUrls }};
let isProcessing = false;

function processNextBatch() {
    if (isProcessing) return;
    
    isProcessing = true;
    
    const formData = new FormData();
    formData.append('batch_index', batchIndex);
    formData.append('category', '{{ $category }}');
    formData.append('_token', '{{ csrf_token() }}');
    
    fetch('{{ route("admin.vinpearl-scraper.scrape") }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        // Kiểm tra Content-Type trước khi parse
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Nếu không phải JSON, đọc text và throw error
            return response.text().then(text => {
                throw new Error('Server trả về HTML thay vì JSON. Có thể do lỗi 500. Vui lòng kiểm tra log server.');
            });
        }
        // Nếu status không OK, vẫn cố parse JSON để lấy message
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Lỗi server: ' + response.status);
            });
        }
        return response.json();
    })
    .then(data => {
        isProcessing = false;
        
        if (data.success) {
            // Cập nhật progress
            const processed = data.processed || 0;
            const percentage = Math.round((processed / totalUrls) * 100);
            
            document.getElementById('progressBar').style.width = percentage + '%';
            document.getElementById('progressText').textContent = percentage + '%';
            document.getElementById('processedCount').textContent = processed;
            document.getElementById('totalCount').textContent = totalUrls;
            document.getElementById('successCount').textContent = data.successCount || 0;
            document.getElementById('errorCount').textContent = data.errorCount || 0;
            document.getElementById('statusMessage').textContent = data.message || 'Đang xử lý...';
            
            if (data.completed) {
                // Hoàn thành
                document.getElementById('progressBar').classList.remove('progress-bar-animated');
                document.getElementById('statusMessage').innerHTML = '<strong class="text-success">' + data.message + '</strong>';
                
                // Hiển thị lỗi nếu có
                if (data.errors && data.errors.length > 0) {
                    const errorList = document.getElementById('errorList');
                    const errorItems = document.getElementById('errorItems');
                    errorItems.innerHTML = '';
                    data.errors.forEach(error => {
                        const li = document.createElement('li');
                        li.textContent = error;
                        errorItems.appendChild(li);
                    });
                    errorList.style.display = 'block';
                }
                
                // Chuyển hướng sau 3 giây
                setTimeout(() => {
                    window.location.href = '{{ route("admin.posts.index") }}';
                }, 3000);
            } else {
                // Tiếp tục batch tiếp theo
                batchIndex++;
                // Đợi 2 giây trước khi xử lý batch tiếp theo để đảm bảo lock được giải phóng
                setTimeout(() => {
                    processNextBatch();
                }, 2000);
            }
        } else {
            // Nếu có retry flag, thử lại sau 2 giây
            if (data.retry) {
                setTimeout(() => {
                    processNextBatch();
                }, 2000);
            } else {
                document.getElementById('statusMessage').innerHTML = '<strong class="text-danger">Lỗi: ' + (data.message || 'Không xác định') + '</strong>';
            }
        }
    })
    .catch(error => {
        isProcessing = false;
        console.error('Error:', error);
        
        let errorMessage = error.message || 'Lỗi không xác định';
        
        // Nếu là lỗi network, hiển thị thông báo khác
        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
            errorMessage = 'Lỗi kết nối mạng. Vui lòng kiểm tra kết nối internet.';
        } else if (error.message.includes('HTML thay vì JSON')) {
            errorMessage = 'Lỗi server (500). Vui lòng kiểm tra log server hoặc thử lại sau.';
        }
        
        document.getElementById('statusMessage').innerHTML = '<strong class="text-danger">Lỗi: ' + errorMessage + '</strong>';
        
        // Chỉ thử lại nếu chưa xử lý hết
        if (batchIndex < Math.ceil(totalUrls / 5)) {
            // Thử lại sau 5 giây
            setTimeout(() => {
                processNextBatch();
            }, 5000);
        } else {
            // Nếu đã xử lý hết nhưng có lỗi, dừng lại
            document.getElementById('statusMessage').innerHTML += '<br><small>Đã dừng xử lý do lỗi. Vui lòng kiểm tra log và thử lại.</small>';
        }
    });
}

// Bắt đầu xử lý
document.addEventListener('DOMContentLoaded', function() {
    processNextBatch();
});
</script>
@endsection

