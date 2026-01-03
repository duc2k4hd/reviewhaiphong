@extends('admin.layouts.main')

@section('title', 'Quản lý Cache')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5 class="card-header">
                        🗂️ Quản lý Cache
                        <button type="button" class="btn btn-danger float-end" onclick="clearAllCache()">
                            🗑️ Xóa tất cả Cache
                        </button>
                    </h5>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="row">
                            <!-- Application Cache -->
                            <div class="col-md-4 mb-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">📱 Application Cache</h5>
                                        <p class="card-text">
                                            <strong>Kích thước:</strong> {{ $cacheInfo['app_cache'] }}
                                        </p>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="clearSpecificCache('app')">
                                            Xóa Cache
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Config Cache -->
                            <div class="col-md-4 mb-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">⚙️ Config Cache</h5>
                                        <p class="card-text">
                                            <strong>Kích thước:</strong> {{ $cacheInfo['config_cache'] }}
                                        </p>
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="clearSpecificCache('config')">
                                            Xóa Cache
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Route Cache -->
                            <div class="col-md-4 mb-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">🛣️ Route Cache</h5>
                                        <p class="card-text">
                                            <strong>Kích thước:</strong> {{ $cacheInfo['route_cache'] }}
                                        </p>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="clearSpecificCache('route')">
                                            Xóa Cache
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- View Cache -->
                            <div class="col-md-4 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">👁️ View Cache</h5>
                                        <p class="card-text">
                                            <strong>Kích thước:</strong> {{ $cacheInfo['view_cache'] }}
                                        </p>
                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearSpecificCache('view')">
                                            Xóa Cache
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Bootstrap Cache -->
                            <div class="col-md-4 mb-4">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">🚀 Bootstrap Cache</h5>
                                        <p class="card-text">
                                            <strong>Kích thước:</strong> {{ $cacheInfo['bootstrap_cache'] }}
                                        </p>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearSpecificCache('bootstrap')">
                                            Xóa Cache
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Commands -->
                            <div class="col-md-4 mb-4">
                                <div class="card border-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">🔧 Lệnh thủ công</h5>
                                        <p class="card-text">Chạy lệnh Artisan</p>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="runArtisanCommand('clear-compiled')">
                                            Clear Compiled
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cache Statistics -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <h6 class="card-header">📊 Thống kê Cache</h6>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 text-center">
                                                <div class="border rounded p-3">
                                                    <h4 class="text-primary">{{ $cacheInfo['app_cache'] }}</h4>
                                                    <small>Application Cache</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="border rounded p-3">
                                                    <h4 class="text-success">{{ $cacheInfo['config_cache'] }}</h4>
                                                    <small>Config Cache</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="border rounded p-3">
                                                    <h4 class="text-info">{{ $cacheInfo['route_cache'] }}</h4>
                                                    <small>Route Cache</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="border rounded p-3">
                                                    <h4 class="text-warning">{{ $cacheInfo['view_cache'] }}</h4>
                                                    <small>View Cache</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function clearAllCache() {
    if (confirm('⚠️ Bạn có chắc muốn xóa TẤT CẢ cache? Điều này có thể làm chậm website tạm thời.')) {
        // Tạo form và submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.cache.clear-all") }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function clearSpecificCache(type) {
    if (confirm(`⚠️ Bạn có chắc muốn xóa ${type} cache?`)) {
        // Tạo form và submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ route("admin.cache.clear-specific", "") }}/${type}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function runArtisanCommand(command) {
    if (confirm(`⚠️ Bạn có chắc muốn chạy lệnh: ${command}?`)) {
        // Có thể implement AJAX call để chạy lệnh Artisan
        alert('🔄 Đang chạy lệnh... Vui lòng chờ!');
        // Reload trang sau 2 giây
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
}
</script>
@endsection
