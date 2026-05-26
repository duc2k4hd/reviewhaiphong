@extends('admin.layouts.main')

@section('title', 'Tất cả bài viết')

@section('content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <!--  -->
            <div class="card">
                <h5 class="card-header">Tất cả bài viết</h5>
                <div class="p-3">
                    <form class="row g-2 align-items-center" method="get" action="{{ route('admin.posts.index') }}">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="q" value="{{ $keyword ?? '' }}" placeholder="Tìm theo tiêu đề, slug, mô tả, tags...">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                @foreach(['draft'=>'Bản nháp','published'=>'Đã xuất bản','archived'=>'Đã lưu trữ','pending'=>'Chờ duyệt'] as $k=>$v)
                                    <option value="{{ $k }}" {{ ($status ?? '')===$k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="per_page" class="form-select">
                                @foreach([50, 200, 500, 2000, 5000] as $pp)
                                    <option value="{{ $pp }}" {{ ($perPage ?? 50)===$pp ? 'selected' : '' }}>{{ $pp }} / trang</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit">Tìm kiếm</button>
                        </div>
                        <div class="col-md-2">
                            <a class="btn btn-outline-secondary w-100" href="{{ route('admin.posts.index') }}">Xóa lọc</a>
                        </div>
                    </form>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <button type="submit" form="bulkDeleteForm" id="bulkDeleteBtn" class="btn btn-danger" disabled>
                            <i class="fas fa-trash"></i> Xóa đã chọn (<span id="selectedCount">0</span>)
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="selectAllPosts()">
                            <i class="fas fa-check-square"></i> Chọn tất cả
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="deselectAllPosts()">
                            <i class="fas fa-square"></i> Bỏ chọn tất cả
                        </button>
                        <a href="{{ route('admin.posts.export', request()->query()) }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Xuất Excel
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fas fa-file-upload"></i> Nhập Excel
                        </button>
                    </div>
                </div>
                <form id="bulkDeleteForm" method="POST" action="{{ route('admin.posts.bulk-destroy') }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa các bài viết đã chọn? Hành động này không thể hoàn tác.');">
                    @csrf
                <div class="table-responsive" style="overflow-x:auto;">
                    <table class="table table-striped table-hover align-middle table-sm admin-posts-table">
                        <thead>
                            <tr>
                                <th class="col-checkbox" style="width: 40px;">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                </th>
                                <th class="col-stt">STT</th>
                                <th class="col-name">Tên</th>
                                <th class="col-title">Tiêu đề SEO</th>
                                <th class="col-author">Tác giả</th>
                                <th class="col-seoimg">Ảnh SEO</th>
                                <th class="col-status">Trạng thái</th>
                                <th class="col-actions" colspan="3">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $increasing = 1;
                                $statusOrder = [
                                    'draft' => [
                                        '-- Bản nháp',
                                        'task' => ['Xuất bản', 'Sửa', 'Xóa'],
                                        'color' => ['success', 'primary', 'danger'],
                                    ],
                                    'published' => [
                                        'Đã xuất bản',
                                        'task' => ['Xem chi tiết', 'Sửa', 'Xóa'],
                                        'color' => ['info', 'primary', 'danger'],
                                    ],
                                    'archived' => [
                                        'Đã lưu trữ',
                                        'task' => ['Khôi phục', 'Xóa'],
                                        'color' => ['warning', 'danger'],
                                    ],
                                    'pending' => [
                                        'Đang chờ duyệt',
                                        'task' => ['Xem chi tiết', 'Duyệt', 'Xóa'],
                                        'color' => ['info', 'primary', 'danger'],
                                    ],
                                ];
                            @endphp

                            @foreach ($posts as $post)
                                @php $statusKey = $post->status; @endphp
                                    <tr>
                                        <td class="col-checkbox">
                                            <input type="checkbox" class="post-checkbox" name="post_ids[]" value="{{ $post->id }}" onchange="updateBulkDeleteButton()">
                                        </td>
                                        <th scope="row" class="col-stt">{{ $increasing++ }}</th>
                                        <td class="col-name">{{ $post->name }}</td>
                                        <td class="col-title">{{ $post->seo_title }}</td>
                                        <td class="col-author">{{ $post->account->profile->name ?? 'Tác giả' }}</td>
                                        <td class="col-seoimg">
                                            @if(!empty($post->seo_image))
                                                <img src="/client/assets/images/posts/{{ $post->seo_image }}" alt="{{ $post->seo_title }}" style="width:64px;height:48px;object-fit:cover;border-radius:4px;border:1px solid #eee;"/>
                                            @else
                                                <div style="width:64px;height:48px;border:1px solid #eee;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#999;font-size:12px;">N/A</div>
                                            @endif
                                        </td>
                                        <td class="col-status">
                                            <span class="badge bg-{{ $statusKey==='published'?'success':($statusKey==='draft'?'secondary':($statusKey==='archived'?'warning':'info')) }}">{{ $statusOrder[$statusKey][0] }}</span>
                                        </td>
                                        <td colspan="3">
                                            <div class="d-flex gap-2">
                                                @if($statusKey === 'draft')
                                                    <form method="POST" action="{{ route('admin.posts.status', $post) }}" onsubmit="return confirm('Xuất bản bài viết này?');">
                                                        @csrf
                                                        <input type="hidden" name="action" value="publish">
                                                        <button type="submit" class="btn btn-sm btn-success">Xuất bản</button>
                                                    </form>
                                                @elseif($statusKey === 'published')
                                                    <form method="POST" action="{{ route('admin.posts.status', $post) }}" onsubmit="return confirm('Lưu trữ bài viết này? Bạn có thể khôi phục sau.');">
                                                        @csrf
                                                        <input type="hidden" name="action" value="archive">
                                                        <button type="submit" class="btn btn-sm btn-warning">Lưu trữ</button>
                                                    </form>
                                                @elseif($statusKey === 'archived')
                                                    <form method="POST" action="{{ route('admin.posts.status', $post) }}" onsubmit="return confirm('Khôi phục bài viết này về trạng thái nháp?');">
                                                        @csrf
                                                        <input type="hidden" name="action" value="restore">
                                                        <button type="submit" class="btn btn-sm btn-info">Khôi phục</button>
                                                    </form>
                                                @endif

                                                <a class="btn btn-sm btn-primary" href="{{ route('admin.posts.edit', $post) }}">Sửa</a>

                                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" onsubmit="return confirm('Xoá vĩnh viễn bài viết này?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Xoá</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                            @endforeach


                        </tbody>
                    </table>
                </div>
                </form>
                <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                    <div>Tổng: {{ $posts->total() }} bài</div>
                    <div class="pagination-wrapper">{{ $posts->links('pagination::bootstrap-5') }}</div>
                </div>
            </div>

            <style>
                .admin-posts-table td, .admin-posts-table th { white-space: normal; vertical-align: middle; }
                .admin-posts-table .col-title { max-width: 360px; }
                .admin-posts-table .col-name { max-width: 260px; }
                .admin-posts-table .col-seoimg { width: 88px; }
                .admin-posts-table .col-actions { width: 220px; }
                .admin-posts-table .col-checkbox { width: 40px; text-align: center; }
                .pagination-wrapper .pagination { margin: 0; }
            </style>

            <script>
                function updateBulkDeleteButton() {
                    const checkboxes = document.querySelectorAll('.post-checkbox:checked');
                    const count = checkboxes.length;
                    const btn = document.getElementById('bulkDeleteBtn');
                    const countSpan = document.getElementById('selectedCount');
                    
                    countSpan.textContent = count;
                    btn.disabled = count === 0;
                }

                function toggleSelectAll(checkbox) {
                    const checkboxes = document.querySelectorAll('.post-checkbox');
                    checkboxes.forEach(cb => {
                        cb.checked = checkbox.checked;
                    });
                    updateBulkDeleteButton();
                }

                function selectAllPosts() {
                    const checkboxes = document.querySelectorAll('.post-checkbox');
                    const selectAllCheckbox = document.getElementById('selectAll');
                    checkboxes.forEach(cb => {
                        cb.checked = true;
                    });
                    selectAllCheckbox.checked = true;
                    updateBulkDeleteButton();
                }

                function deselectAllPosts() {
                    const checkboxes = document.querySelectorAll('.post-checkbox');
                    const selectAllCheckbox = document.getElementById('selectAll');
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                    });
                    selectAllCheckbox.checked = false;
                    updateBulkDeleteButton();
                }

                // Cập nhật checkbox "Chọn tất cả" khi có thay đổi
                document.addEventListener('DOMContentLoaded', function() {
                    const checkboxes = document.querySelectorAll('.post-checkbox');
                    const selectAllCheckbox = document.getElementById('selectAll');
                    
                    checkboxes.forEach(cb => {
                        cb.addEventListener('change', function() {
                            const allChecked = Array.from(checkboxes).every(c => c.checked);
                            const someChecked = Array.from(checkboxes).some(c => c.checked);
                            selectAllCheckbox.checked = allChecked;
                            selectAllCheckbox.indeterminate = someChecked && !allChecked;
                        });
                    });
                });
            </script>
            <!--/ Responsive Table -->
        </div>
        <!-- / Content -->

        <!-- Footer -->
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
    </div>

    <!-- Modal Nhập Excel -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.posts.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">
                            <i class="fas fa-file-upload"></i> Nhập bài viết từ Excel
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="importFile" class="form-label">
                                <strong>Chọn file Excel (.xlsx hoặc .xls)</strong>
                            </label>
                            <input type="file" class="form-control" id="importFile" name="file" accept=".xlsx,.xls" required>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Sức mạnh bóc tách từ Trình duyệt. Không giới hạn dung lượng!
                            </div>
                        </div>

                        <!-- Vùng hiển thị Progress -->
                        <div id="importProgressArea" class="d-none mt-3">
                            <p class="mb-1 fw-bold text-primary" id="importStatusText">Đang bóc tách dữ liệu...</p>
                            <div class="progress" style="height: 20px;">
                                <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                            <small class="text-muted" id="importDetails">Vui lòng không đóng trình duyệt</small>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" id="btnImportSubmit">
                            <i class="fas fa-upload" id="importIcon"></i> <span id="importText">Bắt đầu Nhập</span>
                        </button>
                    </div>
                </form>
                
                <!-- Thư viện SheetJS -->
                <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
                
                <script>
                    document.getElementById('btnImportSubmit').addEventListener('click', async function() {
                        const fileInput = document.getElementById('importFile');
                        if (!fileInput.files.length) {
                            alert('Vui lòng chọn file Excel!');
                            return;
                        }

                        const file = fileInput.files[0];
                        const btn = document.getElementById('btnImportSubmit');
                        const icon = document.getElementById('importIcon');
                        const text = document.getElementById('importText');
                        const progressArea = document.getElementById('importProgressArea');
                        const progressBar = document.getElementById('importProgressBar');
                        const statusText = document.getElementById('importStatusText');
                        const detailsText = document.getElementById('importDetails');

                        // Disable controls
                        btn.disabled = true;
                        fileInput.disabled = true;
                        progressArea.classList.remove('d-none');
                        text.innerText = 'Đang đọc file...';
                        icon.className = 'fas fa-spinner fa-spin';

                        try {
                            // 1. Đọc file bằng FileReader
                            const data = await file.arrayBuffer();
                            
                            // 2. Parse Excel bằng SheetJS
                            statusText.innerText = 'Đang phân tích cấu trúc Excel...';
                            const workbook = XLSX.read(data, { type: 'array' });
                            const firstSheetName = workbook.SheetNames[0];
                            const worksheet = workbook.Sheets[firstSheetName];
                            
                            // Lấy dữ liệu dạng raw (header là dòng 1)
                            const rawJson = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                            if (rawJson.length < 2) {
                                throw new Error('File Excel không có dữ liệu');
                            }

                            // 3. Slugify headers giống Laravel Excel
                            const headers = rawJson[0].map(h => slugifyHeader(h));
                            
                            // 4. Map dữ liệu thành object
                            const rows = [];
                            for (let i = 1; i < rawJson.length; i++) {
                                const rowArr = rawJson[i];
                                // Bỏ qua dòng trống hoàn toàn
                                if (!rowArr || rowArr.length === 0 || rowArr.every(cell => cell === undefined || cell === null || cell === '')) continue;
                                
                                const rowObj = { _row_index: i + 1 };
                                headers.forEach((header, index) => {
                                    rowObj[header] = rowArr[index];
                                });
                                rows.push(rowObj);
                            }

                            const totalRows = rows.length;
                            statusText.innerText = `Đã phân tích xong ${totalRows} dòng. Bắt đầu đẩy lên Server...`;

                            // 5. Chia lô (Chunk)
                            const CHUNK_SIZE = 500;
                            const totalChunks = Math.ceil(totalRows / CHUNK_SIZE);
                            let processedCount = 0;
                            let allErrors = [];
                            let totalSuccess = 0;
                            let totalSkipped = 0;

                            for (let i = 0; i < totalChunks; i++) {
                                const chunk = rows.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                                
                                // Gọi AJAX lên server
                                const response = await fetch('{{ route("admin.posts.import-batch") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ rows: chunk })
                                });

                                const resData = await response.json();
                                if (!response.ok || !resData.success) {
                                    throw new Error(resData.message || 'Lỗi server khi lưu dữ liệu');
                                }

                                if (resData.errors && resData.errors.length > 0) {
                                    allErrors.push(...resData.errors);
                                }
                                totalSuccess += (resData.successCount || 0);
                                totalSkipped += (resData.skipCount || 0);

                                processedCount += chunk.length;
                                const percent = Math.round((processedCount / totalRows) * 100);
                                
                                progressBar.style.width = percent + '%';
                                progressBar.innerText = percent + '%';
                                detailsText.innerText = `Đã xử lý ${processedCount} / ${totalRows} dòng`;
                            }

                            // Hoàn thành
                            statusText.innerText = `Hoàn thành! Thành công: ${totalSuccess}, Bỏ qua: ${totalSkipped}`;
                            statusText.className = 'mb-1 fw-bold text-success';
                            progressBar.className = 'progress-bar bg-success';
                            text.innerText = 'Đã xong';
                            icon.className = 'fas fa-check';

                            if (allErrors.length > 0) {
                                let errorHtml = '<div class="alert alert-warning mt-3"><strong>Một số dòng bị bỏ qua:</strong><ul class="mb-0 mt-2" style="max-height: 200px; overflow-y: auto;">';
                                allErrors.forEach(err => {
                                    errorHtml += `<li>${err}</li>`;
                                });
                                errorHtml += '</ul></div>';
                                progressArea.insertAdjacentHTML('beforeend', errorHtml);
                                
                                // Đổi nút thành tải lại trang để user có thể đóng modal hoặc reload
                                btn.disabled = false;
                                text.innerText = 'Tải lại trang';
                                icon.className = 'fas fa-sync';
                                btn.onclick = function() { window.location.reload(); };
                            } else {
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            }

                        } catch (error) {
                            console.error(error);
                            alert('Có lỗi xảy ra: ' + error.message);
                            btn.disabled = false;
                            fileInput.disabled = false;
                            text.innerText = 'Nhập lại';
                            icon.className = 'fas fa-redo';
                            statusText.innerText = 'Xảy ra lỗi!';
                            statusText.className = 'mb-1 fw-bold text-danger';
                            progressBar.className = 'progress-bar bg-danger';
                        }
                    });

                    // Hàm chuyển đổi Tiêu đề cột thành slug_key giống Laravel Excel
                    function slugifyHeader(text) {
                        if (!text) return '';
                        return text.toString().toLowerCase()
                            .replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a")
                            .replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e")
                            .replace(/ì|í|ị|ỉ|ĩ/g, "i")
                            .replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o")
                            .replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u")
                            .replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y")
                            .replace(/đ/g, "d")
                            .replace(/[^a-z0-9\s-]/g, "") // Xóa ký tự đặc biệt (giữ lại khoảng trắng và gạch ngang)
                            .replace(/[\s-]+/g, "_") // Đổi khoảng trắng và gạch ngang thành gạch dưới
                            .replace(/^_|_$/g, "");
                    }
                </script>
            </div>
        </div>
    </div>
@endsection
