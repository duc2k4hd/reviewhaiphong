@extends('admin.layouts.main')

@section('title', 'Thêm danh mục mới')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">📂 Thêm danh mục mới</h5>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
            
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.categories.store') }}" onsubmit="return validateForm()">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Thông tin cơ bản -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">📝 Thông tin cơ bản</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name') }}" 
                                               placeholder="Nhập tên danh mục..." required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="slug" class="form-label">Slug (URL)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">/</span>
                                            <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                                   id="slug" name="slug" value="{{ old('slug') }}" 
                                                   placeholder="tu-khoa-bai-viet">
                                        </div>
                                        <div class="form-text">Để trống để tự động tạo từ tên danh mục</div>
                                        @error('slug')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="parent_id" class="form-label">Danh mục cha</label>
                                        <select class="form-select @error('parent_id') is-invalid @enderror" 
                                                id="parent_id" name="parent_id">
                                            <option value="">-- Chọn danh mục cha (để trống nếu là danh mục gốc) --</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ old('parent_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('parent_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Mô tả</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                                  id="description" name="description" rows="3" 
                                                  placeholder="Mô tả ngắn gọn về danh mục...">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- SEO -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">🔍 SEO</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="meta_title" class="form-label">Meta Title</label>
                                        <input type="text" class="form-control @error('meta_title') is-invalid @enderror" 
                                               id="meta_title" name="meta_title" value="{{ old('meta_title') }}" 
                                               placeholder="Tiêu đề SEO (50-60 ký tự)">
                                        <div class="form-text">
                                            <span id="meta_title_count">0</span>/60 ký tự
                                        </div>
                                        @error('meta_title')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="meta_description" class="form-label">Meta Description</label>
                                        <textarea class="form-control @error('meta_description') is-invalid @enderror" 
                                                  id="meta_description" name="meta_description" rows="2" 
                                                  placeholder="Mô tả SEO (150-160 ký tự)">{{ old('meta_description') }}</textarea>
                                        <div class="form-text">
                                            <span id="meta_description_count">0</span>/160 ký tự
                                        </div>
                                        @error('meta_description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                        <input type="text" class="form-control @error('meta_keywords') is-invalid @enderror" 
                                               id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords') }}" 
                                               placeholder="Từ khóa, phân cách bởi dấu phẩy">
                                        @error('meta_keywords')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Cài đặt -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">⚙️ Cài đặt</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Trạng thái</label>
                                        <select class="form-select @error('status') is-invalid @enderror" 
                                                id="status" name="status">
                                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                                Hoạt động
                                            </option>
                                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                                Không hoạt động
                                            </option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="sort_order" class="form-label">Thứ tự sắp xếp</label>
                                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                               id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" 
                                               min="0" step="1">
                                        <div class="form-text">Số càng nhỏ càng hiển thị trước</div>
                                        @error('sort_order')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">👁️ Xem trước</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>URL:</strong>
                                        <div class="text-muted small" id="url_preview">/</div>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Meta Title:</strong>
                                        <div class="text-muted small" id="title_preview">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Meta Description:</strong>
                                        <div class="text-muted small" id="desc_preview">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nút điều khiển -->
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu danh mục
                        </button>
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Tự động tạo slug từ tên
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/đ/g, "d")
        .replace(/[^a-z0-9\s-]/g, "")
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
    
    document.getElementById('slug').value = slug;
    updateUrlPreview();
});

// Cập nhật preview
function updateUrlPreview() {
    const slug = document.getElementById('slug').value;
    document.getElementById('url_preview').textContent = '/' + (slug || 'danh-muc');
}

function updateTitlePreview() {
    const title = document.getElementById('meta_title').value;
    document.getElementById('title_preview').textContent = title || '-';
    document.getElementById('meta_title_count').textContent = title.length;
}

function updateDescPreview() {
    const desc = document.getElementById('meta_description').value;
    document.getElementById('desc_preview').textContent = desc || '-';
    document.getElementById('meta_description_count').textContent = desc.length;
}

// Event listeners cho preview
document.getElementById('slug').addEventListener('input', updateUrlPreview);
document.getElementById('meta_title').addEventListener('input', updateTitlePreview);
document.getElementById('meta_description').addEventListener('input', updateDescPreview);

// Khởi tạo preview
document.addEventListener('DOMContentLoaded', function() {
    updateUrlPreview();
    updateTitlePreview();
    updateDescPreview();
});

// Validation
function validateForm() {
    const name = document.getElementById('name').value.trim();
    if (!name) {
        alert('Vui lòng nhập tên danh mục!');
        document.getElementById('name').focus();
        return false;
    }
    return true;
}
</script>

<style>
.card {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
    font-weight: 600;
}

.form-label {
    font-weight: 600;
    color: #5a5c69;
}

.input-group-text {
    background-color: #f8f9fc;
    border-color: #d1d3e2;
}

.form-text {
    font-size: 0.875em;
    color: #858796;
}
</style>
@endsection

