@extends('admin.layouts.main')

@section('title', 'Quản lý danh mục')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">📂 Quản lý danh mục</h5>
                <div>
                    <a href="{{ route('admin.categories.export') }}" class="btn btn-success me-2">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </a>
                    <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                        <i class="fas fa-file-import"></i> Nhập Excel
                    </button>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Thêm danh mục mới
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <!-- Form tìm kiếm và lọc -->
                <form method="GET" action="{{ route('admin.categories.index') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="q" class="form-control" 
                                placeholder="Tìm kiếm theo tên hoặc slug..." 
                                value="{{ request('q') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="parent" class="form-select">
                                <option value="">Tất cả danh mục</option>
                                <option value="null" {{ request('parent') === 'null' ? 'selected' : '' }}>
                                    Danh mục gốc
                                </option>
                                @foreach($parentCategories as $parent)
                                    <option value="{{ $parent->id }}" {{ request('parent') == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="per_page" class="form-select">
                                <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15</option>
                                <option value="30" {{ request('per_page') == 30 ? 'selected' : '' }}>30</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Xóa bộ lọc
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Bulk Actions -->
                <div class="mb-3">
                    <button id="btnBulkDelete" class="btn btn-danger d-none">
                        <i class="fas fa-trash"></i> Xóa (<span id="selectedCount">0</span>) mục đã chọn
                    </button>
                </div>

                <!-- Bảng danh sách -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th width="50">STT</th>
                                <th>Tên danh mục</th>
                                <th>Slug</th>
                                <th>Danh mục cha</th>
                                <th>Mô tả</th>
                                <th>Trạng thái</th>
                                <th>Số bài viết</th>
                                <th>Thứ tự</th>
                                <th width="200">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $index => $category)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input category-checkbox" value="{{ $category->id }}">
                                    </td>
                                    <td>{{ $index + 1 + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                                    <td>
                                        <strong>{{ $category->name }}</strong>
                                        @if($category->children_count > 0)
                                            <span class="badge bg-info ms-1">{{ $category->children_count }} con</span>
                                        @endif
                                    </td>
                                    <td>
                                        <code>{{ $category->slug }}</code>
                                    </td>
                                    <td>
                                        @if($category->parent)
                                            <span class="badge bg-secondary">{{ $category->parent->name }}</span>
                                        @else
                                            <span class="badge bg-primary">Gốc</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($category->description)
                                            <span title="{{ $category->description }}">
                                                {{ Str::limit($category->description, 50) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $category->status === 'active' ? 'success' : 'danger' }}">
                                            {{ $category->status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $category->posts_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $category->sort_order }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.categories.edit', $category) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            @if($category->status === 'active')
                                                <form method="POST" action="{{ route('admin.categories.status', $category) }}" 
                                                      class="d-inline" 
                                                      onsubmit="return confirm('Bạn có chắc muốn ẩn danh mục này?')">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="inactive">
                                                    <button type="submit" class="btn btn-sm btn-warning" title="Ẩn">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.categories.status', $category) }}" 
                                                      class="d-inline" 
                                                      onsubmit="return confirm('Bạn có chắc muốn hiện danh mục này?')">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Hiện">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" 
                                                  class="d-inline" 
                                                  onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này? Hành động này không thể hoàn tác!')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                                            <p>Chưa có danh mục nào</p>
                                            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Thêm danh mục đầu tiên
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                @if($categories->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $categories->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Nhập Excel -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.categories.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importExcelModalLabel">Nhập danh mục từ Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Chọn file Excel (.xlsx)</label>
                        <input type="file" class="form-control" id="excel_file" name="file" accept=".xlsx, .xls" required>
                        <small class="text-muted mt-2 d-block">
                            Lưu ý: Bạn nên <strong>Xuất Excel</strong> trước để lấy mẫu cấu trúc chuẩn. Nếu cột ID để trống, hệ thống sẽ thêm mới. Nếu cột ID đã có, hệ thống sẽ cập nhật thông tin.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-file-import"></i> Tiến hành nhập
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.table-responsive {
    overflow-x: auto;
}

.table th {
    white-space: nowrap;
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75em;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.category-checkbox');
    const btnBulkDelete = document.getElementById('btnBulkDelete');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.category-checkbox:checked').length;
        selectedCountSpan.textContent = checkedCount;
        if (checkedCount > 0) {
            btnBulkDelete.classList.remove('d-none');
        } else {
            btnBulkDelete.classList.add('d-none');
            selectAll.checked = false;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkDeleteButton();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteButton);
    });

    if (btnBulkDelete) {
        btnBulkDelete.addEventListener('click', function() {
            const checkedIds = Array.from(document.querySelectorAll('.category-checkbox:checked')).map(cb => cb.value);
            
            if (checkedIds.length === 0) return;

            if (confirm(`Bạn có chắc chắn muốn xóa ${checkedIds.length} danh mục đã chọn? Các danh mục chứa bài viết hoặc có thư mục con sẽ bị bỏ qua tự động.`)) {
                
                // Disable button
                btnBulkDelete.disabled = true;
                btnBulkDelete.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xóa...';

                fetch('{{ route("admin.categories.bulkDestroy") }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ids: checkedIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.message || 'Có lỗi xảy ra');
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Đã xảy ra lỗi hệ thống');
                    window.location.reload();
                });
            }
        });
    }
});
</script>
@endsection
