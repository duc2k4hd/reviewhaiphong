@extends('admin.layouts.main')

@section('title', 'Cài đặt chung')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Cài đặt chung</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Cài đặt</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Settings Form -->
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf
            {{-- @method('PUT') --}}
            <div class="row">
                <!-- Navigation Tabs -->
                <div class="col-md-3 mt-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="nav flex-column nav-pills" id="settings-tab" role="tablist">
                                @foreach($settingGroups as $key => $group)
                                    <button class="text-start nav-link {{ $loop->first ? 'active' : '' }}" 
                                            id="{{ $key }}-tab" 
                                            data-bs-toggle="pill" 
                                            data-bs-target="#{{ $key }}-content" 
                                            type="button" 
                                            role="tab">
                                        <i class="bx bx-{{ $key == 'general' ? 'cog' : ($key == 'images' ? 'image' : ($key == 'contact' ? 'phone' : ($key == 'social' ? 'share' : ($key == 'content' ? 'book' : ($key == 'seo' ? 'search' : 'shield'))))) }} me-2"></i>
                                        {{ $group['title'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="col-md-9">
                    <div class="tab-content" id="settings-tabContent">
                        @foreach($settingGroups as $key => $group)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                 id="{{ $key }}-content" 
                                 role="tabpanel">
                                
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bx bx-{{ $key == 'general' ? 'cog' : ($key == 'images' ? 'image' : ($key == 'contact' ? 'phone' : ($key == 'social' ? 'share' : ($key == 'content' ? 'file-text' : ($key == 'seo' ? 'search' : 'shield'))))) }} me-2"></i>
                                            {{ $group['title'] }}
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @foreach($group['settings'] as $settingKey => $setting)
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    {{ $setting['label'] }}
                                                    @if(isset($setting['required']) && $setting['required'])
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>

                                                @switch($setting['type'])
                                                    @case('textarea')
                                                        <textarea name="{{ $settingKey }}" 
                                                                  class="form-control" 
                                                                  rows="3" 
                                                                  placeholder="{{ $setting['placeholder'] ?? '' }}"
                                                                  {{ isset($setting['required']) && $setting['required'] ? 'required' : '' }}>{{ $setting['value'] }}</textarea>
                                                        @break

                                                    @case('checkbox')
                                                        <div class="form-check">
                                                            <input type="checkbox" 
                                                                   name="{{ $settingKey }}" 
                                                                   class="form-check-input" 
                                                                   value="1" 
                                                                   id="{{ $settingKey }}"
                                                                   {{ $setting['value'] == '1' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="{{ $settingKey }}">
                                                                {{ $setting['description'] ?? '' }}
                                                            </label>
                                                        </div>
                                                        @break

                                                    @case('number')
                                                        <input type="number" 
                                                               name="{{ $settingKey }}" 
                                                               class="form-control" 
                                                               value="{{ $setting['value'] }}" 
                                                               placeholder="{{ $setting['placeholder'] ?? '' }}"
                                                               min="{{ $setting['min'] ?? '' }}"
                                                               max="{{ $setting['max'] ?? '' }}"
                                                               {{ isset($setting['required']) && $setting['required'] ? 'required' : '' }}>
                                                        @break

                                                    @case('image')
                                                        <div class="d-flex align-items-center gap-3">
                                                            @if($setting['value'])
                                                                @if($settingKey === 'avatar_admin')
                                                                    <img src="{{ asset('/client/assets/images/avatar/' . $setting['value']) }}" 
                                                                         alt="{{ $setting['label'] }}" 
                                                                         class="img-thumbnail" 
                                                                         style="max-width: 100px; max-height: 100px;"
                                                                         id="preview_{{ $settingKey }}">
                                                                @else
                                                                    <img src="{{ asset('/client/assets/images/logo/' . $setting['value']) }}" 
                                                                         alt="{{ $setting['label'] }}" 
                                                                         class="img-thumbnail" 
                                                                         style="max-width: 100px; max-height: 100px;"
                                                                         id="preview_{{ $settingKey }}">
                                                                @endif
                                                            @endif
                                                            <div class="flex-grow-1">
                                                                <input type="file" 
                                                                       name="{{ $settingKey }}" 
                                                                       class="form-control" 
                                                                       accept="image/*"
                                                                       onchange="previewImage(this, '{{ $settingKey }}')">
                                                                <small class="form-text text-muted">
                                                                    @if($settingKey === 'avatar_admin')
                                                                        Chỉ lưu tên ảnh vào database, ảnh sẽ được lưu trong /client/assets/images/avatar/
                                                                    @else
                                                                        Chỉ lưu tên ảnh vào database, ảnh sẽ được lưu trong /client/assets/images/logo/
                                                                    @endif
                                                                </small>
                                                            </div>
                                                            @if($setting['value'])
                                                                <input type="hidden" name="{{ $settingKey }}_current" value="{{ $setting['value'] }}">
                                                            @endif
                                                        </div>
                                                        @break

                                                    @default
                                                        <input type="text" 
                                                               name="{{ $settingKey }}" 
                                                               class="form-control" 
                                                               value="{{ $setting['value'] }}" 
                                                               placeholder="{{ $setting['placeholder'] ?? '' }}">
                                                @endswitch

                                                @error($settingKey)
                                                    <div class="text-danger mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-warning" onclick="resetSettings()">
                                    <i class="bx bx-refresh me-2"></i>
                                    Reset về mặc định
                                </button>
                                <div>
                                    <a href="{{ route('admin.dashboard.index') }}" class="btn btn-secondary me-2">
                                        <i class="bx bx-arrow-back me-2"></i>
                                        Quay lại
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-2"></i>
                                        Lưu cài đặt
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận reset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc muốn reset tất cả cài đặt về mặc định? Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form method="POST" action="{{ route('admin.settings.reset') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">Reset</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function resetSettings() {
    const modal = new bootstrap.Modal(document.getElementById('resetModal'));
    modal.show();
}

// Image preview function
function previewImage(input, settingKey) {
    const previewId = 'preview_' + settingKey;
    let preview = document.getElementById(previewId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            if (!preview) {
                // Tạo preview mới nếu chưa có
                const container = input.closest('.d-flex');
                const newPreview = document.createElement('img');
                newPreview.id = previewId;
                newPreview.src = e.target.result;
                newPreview.alt = 'Preview';
                newPreview.className = 'img-thumbnail';
                newPreview.style.cssText = 'max-width: 100px; max-height: 100px;';
                
                container.insertBefore(newPreview, container.firstChild);
                preview = newPreview;
            } else {
                preview.src = e.target.result;
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Simple form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[enctype="multipart/form-data"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submitted');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-2"></i>Đang lưu...';
                submitBtn.disabled = true;
                
                // Re-enable after 5 seconds if no response
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    }
});
</script>
    

<style>
.nav-pills .nav-link {
    color: #6c757d;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
}

.nav-pills .nav-link.active {
    background-color: #696cff;
    color: white;
}

.nav-pills .nav-link:hover {
    background-color: #f8f9fa;
}

.form-check-input:checked {
    background-color: #696cff;
    border-color: #696cff;
}

.card {
    border: 1px solid #d9dee3;
    box-shadow: 0 0.25rem 1rem rgba(161, 172, 184, 0.45);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #d9dee3;
}
</style>
@endsection
