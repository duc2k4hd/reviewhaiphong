@extends('admin.layouts.main')

@section('title', 'Thêm bài viết mới');

@section('content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <!--  -->
            <div class="card">
                <h5 class="card-header">Thêm bài viết mới</h5>
                <div class="container">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.posts.new.handle') }}" enctype="multipart/form-data"
                        onsubmit="syncContent()">
                        @csrf

                        {{-- Tiêu đề bài viết --}}
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề bài viết</label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                required oninput="autoRenderSlugWithTitle();">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Ảnh đại diện SEO --}}
                        <div class="mb-3">
                            <label class="form-label">Ảnh đại diện (SEO)</label>
                            <input type="file" name="seo_image"
                                class="form-control @error('seo_image') is-invalid @enderror" accept="image/*">
                            @error('seo_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Danh mục --}}
                        <div class="mb-3">
                            <label class="form-label">Danh mục</label>
                            <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tags --}}
                        <div class="mb-3">
                            <label class="form-label">Thẻ (Tags, phân cách bởi dấu phẩy)</label>
                            <input type="text" name="tags" class="form-control @error('tags') is-invalid @enderror"
                                value="{{ old('tags') }}" placeholder="ví dụ: Laravel, PHP, Quill">
                            @error('tags')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slug --}}
                        <div class="mb-3">
                            <label class="form-label">Đường dẫn (Slug)</label>
                            <input type="text" id="slug" name="slug"
                                class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}"
                                oninput="autoRenderSlugWithTitle();" placeholder="tu-khoa-bai-viet">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="button" class="btn btn-outline-primary" onclick="openMediaLibrary()">📷 Chọn
                            ảnh</button>
                        {{-- Nội dung --}}
                        <div class="mb-3">
                            <label class="form-label">Nội dung bài viết</label>
                            <div id="editor" style="height: 300px;">{!! old('content') !!}</div>
                            <textarea class="edit-code" id="htmlEditor" style="width: 100%; height: 300px; display: none;">{!! old('content') !!}</textarea>
                            <div>Số từ <span id="lengthContent">0</span></div>
                            <button type="button" id="editCodeBtn" class="btn btn-warning mt-2">Sửa Code</button>
                            <input type="hidden" name="content" id="content">
                            @error('content')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Trường SEO --}}
                        <div class="mb-3">
                            <label class="form-label">SEO Title</label>
                            <input type="text" id="seo_title" name="seo_title"
                                class="form-control @error('seo_title') is-invalid @enderror"
                                value="{{ old('seo_title') }}">
                            @error('seo_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SEO Description</label>
                            <textarea name="seo_desc" class="form-control @error('seo_desc') is-invalid @enderror">{{ old('seo_desc') }}</textarea>
                            @error('seo_desc')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SEO Keywords</label>
                            <input type="text" name="seo_keywords"
                                class="form-control @error('seo_keywords') is-invalid @enderror"
                                value="{{ old('seo_keywords') }}">
                            @error('seo_keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        {{-- Type (nếu cần) --}}
                        {{-- <input type="" name="type" value="blog"> --}}

                        {{-- Các nút điều khiển --}}
                        <div class="d-flex gap-2 mb-3">
                            <button type="submit" name="status" value="draft" class="btn btn-warning">💾 Lưu
                                nháp</button>
                            <button type="submit" name="status" value="published" class="btn btn-success">🚀 Xuất
                                bản</button>
                            <button type="button" onclick="previewContent()" class="btn btn-info">👁 Xem trước</button>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="viewCode()">Xem
                                Code</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="viewText()">Xem
                                Text</button>
                        </div>

                        {{-- Preview --}}
                        <div id="preview" class="mt-4 p-3 border rounded bg-light d-none">
                            <h5>Xem trước nội dung</h5>
                            <div id="previewContent"></div>
                        </div>

                        {{-- HTML Code view --}}
                        <div id="codeView" class="mt-4 p-3 border rounded bg-light d-none">
                            <h5>HTML Code</h5>
                            <pre><code id="htmlContent"></code></pre>
                        </div>

                        {{-- Plain Text view --}}
                        <div id="textView" class="mt-4 p-3 border rounded bg-light d-none">
                            <h5>Plain Text</h5>
                            <pre><code id="textContent"></code></pre>
                        </div>
                    </form>
                </div>
                <div class="modal fade" id="mediaLibraryModal" tabindex="-1" aria-labelledby="mediaLibraryLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg" style="max-width: 95%; width: 90%;">
                        <div class="modal-content p-3">
                            <div class="modal-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                                <!-- Form Upload -->
                                <form id="imageUploadForm" enctype="multipart/form-data" class="d-flex row flex-wrap align-items-center gap-2">
                                    @csrf
                                    <input type="file" id="imageUploadInput" accept="image/webp,image/png,image/jpg,image/jpeg"
                                        class="col form-control" name="image[]" multiple>
                                    <button type="submit" class="btn btn-primary col">Thêm ảnh</button>
                                </form>
                            
                                <!-- Tìm kiếm + Đóng modal -->
                                <div class="d-flex align-items-center gap-2 mt-2 mt-md-0 w-md-auto">
                                    <input type="text" id="imageSearchInput" class="form-control" placeholder="Tìm ảnh...">
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                            </div>
                            
                            <div class="modal-body row gap-2" id="imageContainer">
                                <!-- Các ảnh đã tải lên sẽ xuất hiện ở đây -->
                                @foreach ($images['urls'] as $key => $image)
                                    @php
                                        $filename = basename($image);
                                        $name = $images['name'][$key]; // Lấy name tương ứng với mỗi URL
                                    @endphp
                                    <img src="{{ $image }}" data-url="{{ $image }}"
                                        data-name="{{ $name }}" class="media-img col"
                                        style="width: 100px; height: auto; cursor: pointer;">
                                @endforeach
                            </div>
                            <div class="d-flex justify-content-center mt-2" id="paginationContainer">
                                <!-- Nút phân trang sẽ render ở đây -->
                            </div>
                        </div>
                    </div>
                </div>


            </div>
            <!--/ Responsive Table -->
        </div>
        <!-- Modal -->

        <!-- / Content -->
        <style>
            #editor img {
                width: 300px;
                height: auto;
            }

            /* Đổi màu và thêm hiệu ứng nhấp nháy cho con trỏ */
            .ql-editor {
                caret-color: transparent;
                /* Ẩn caret mặc định */
                position: relative;
                font-size: 16px;
                /* Tùy chỉnh kích thước */
            }

            .ql-editor::after {
                content: '';
                position: absolute;
                top: 0;
                width: 2px;
                height: 1em;
                background-color: red;
                animation: blinkCaret 0.5s steps(1) infinite;
                pointer-events: none;
            }

            @keyframes blinkCaret {

                0%,
                50% {
                    opacity: 1;
                }

                50.01%,
                100% {
                    opacity: 0;
                }
            }

            .ql-editor::selection {
                background-color: rgba(255, 0, 0, 0.2);
                /* Màu nền khi bôi đen, tùy chọn */
            }

            /* Trick tăng kích thước con trỏ bằng cách tăng font-size tạm cho ::before */
            .ql-editor::before {
                font-size: inherit !important;
            }

            /* Nếu muốn hiệu ứng con trỏ lớn hơn thật sự, dùng transform */
            .ql-editor * {
                caret-color: red;
                /* Áp dụng cho các phần tử con */
            }

            /* Nếu vẫn chưa thấy to, có thể tăng font-size toàn vùng soạn thảo */
            .ql-editor {
                font-size: 18px;
                /* Tùy chỉnh */
            }

            .ql-undo {
                background-color: #007bff;
                /* Thêm màu nền */
                color: white;
                /* Màu chữ */
                border: none;
                /* Bỏ đường viền */
                padding: 5px 10px;
                /* Thêm padding */
                border-radius: 4px;
                /* Bo tròn góc */
                font-size: 16px;
                /* Đặt kích thước font */
                display: flex;
                /* Hiển thị theo dòng */
                align-items: center;
                /* Căn giữa nội dung */
                justify-content: center;
                /* Căn giữa nội dung */
            }

            /* Tùy chỉnh biểu tượng Undo */
            .ql-undo::before {
                content: '\21B2';
                /* Thêm ký tự mũi tên trái (Undo) */
                font-size: 18px;
                /* Điều chỉnh kích thước biểu tượng */
            }

            /* Tùy chỉnh nút Redo */
            .ql-redo {
                background-color: #28a745;
                /* Thêm màu nền */
                color: white;
                /* Màu chữ */
                border: none;
                /* Bỏ đường viền */
                padding: 5px 10px;
                /* Thêm padding */
                border-radius: 4px;
                /* Bo tròn góc */
                font-size: 16px;
                /* Đặt kích thước font */
                display: flex;
                /* Hiển thị theo dòng */
                align-items: center;
                /* Căn giữa nội dung */
                justify-content: center;
                /* Căn giữa nội dung */
            }

            /* Tùy chỉnh biểu tượng Redo */
            .ql-redo::before {
                content: '\21B7';
                /* Thêm ký tự mũi tên phải (Redo) */
                font-size: 18px;
                /* Điều chỉnh kích thước biểu tượng */
            }

            @media (min-width: 768px) {

                #editor,
                .edit-code {
                    width: 100%;
                    /* Đặt chiều rộng là 100% của bố cục, có thể thay đổi theo ý muốn */
                    height: 500px;
                    /* Điều chỉnh chiều cao */
                    min-height: 500px;
                    /* Đảm bảo chiều cao tối thiểu */
                    border: 1px solid #ccc;
                    /* Thêm đường viền cho rõ ràng */
                    border-radius: 5px;
                    /* Bo tròn góc */
                    padding: 10px;
                    /* Thêm padding để nội dung không sát mép */
                }

                #editor {
                    border: 2px solid blue;
                }

                .edit-code {
                    border: 2px solid red;
                }
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
        </script>
        <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
        <script>
            const quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'Viết nội dung bài viết...',
                modules: {
                    toolbar: [
                        [{
                            'font': []
                        }],
                        [{
                            'size': ['small', 'medium', 'large', 'huge']
                        }],
                        [{
                            'header': [1, 2, 3, 4, 5, 6, false]
                        }],
                        [{
                            'align': []
                        }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{
                            'list': 'ordered'
                        }, {
                            'list': 'bullet'
                        }],
                        [{
                            'indent': '-1'
                        }, {
                            'indent': '+1'
                        }],
                        ['link', 'image', 'video'],
                        ['blockquote', 'code-block'],
                        [{
                            'color': []
                        }, {
                            'background': []
                        }],
                        ['undo', 'redo'],
                        ['clean']
                    ],
                    history: {
                        delay: 2000,
                        maxStack: 500,
                        userOnly: true // Chỉ lưu lịch sử của người dùng
                    }
                },
                handlers: {
                    link: function(value) {
                        if (value) {
                            const href = prompt('Enter the URL');
                            this.quill.format('link', href);
                        } else {
                            this.quill.format('link', false);
                        }
                    }
                }
            });

            // Khi nhấn vào nút "Sửa Code"
            document.getElementById('editCodeBtn').addEventListener('click', function() {
                const editorDiv = document.getElementById('editor');
                const htmlEditor = document.getElementById('htmlEditor');

                // Kiểm tra nếu đang ở chế độ Quill editor
                if (editorDiv.style.display !== 'none') {
                    // Chuyển Quill editor thành textarea
                    htmlEditor.value = quill.root.innerHTML; // Cập nhật nội dung vào textarea
                    editorDiv.style.display = 'none'; // Ẩn Quill editor
                    htmlEditor.style.display = 'block'; // Hiển thị textarea

                    // Cập nhật button thành "Xong"
                    this.textContent = 'Xong';
                } else {
                    // Quay lại chế độ Quill editor
                    quill.root.innerHTML = htmlEditor.value; // Cập nhật lại nội dung trong Quill editor
                    htmlEditor.style.display = 'none'; // Ẩn textarea
                    editorDiv.style.display = 'block'; // Hiển thị Quill editor

                    // Cập nhật button thành "Sửa Code"
                    this.textContent = 'Sửa Code';
                }
            });

            // Xử lý form upload ảnh
            document.getElementById("imageUploadForm").addEventListener("submit", function(event) {
                event.preventDefault(); // Ngăn form reload

                const formData = new FormData();
                const files = document.getElementById("imageUploadInput").files;

                if (files.length === 0) {
                    alert("Vui lòng chọn ít nhất một ảnh để tải lên.");
                    return;
                }

                // 👇 Sửa đúng tên field để Laravel nhận được
                for (let i = 0; i < files.length; i++) {
                    formData.append("image[]", files[i]);
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch('/api/media/upload', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                            // Không set Content-Type vì FormData sẽ tự thêm
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.urls) {
                            alert("Upload thành công " + data.urls.length + " ảnh!");
                            const container = document.getElementById("imageContainer");

                            data.urls.forEach(url => {
                                const filename = url.split('/').pop(); // Lấy tên file từ URL

                                const imgElement = document.createElement("img");
                                imgElement.src = url;
                                imgElement.setAttribute("data-url", url);
                                imgElement.setAttribute("data-name", filename); // Gán name nếu cần
                                imgElement.classList.add("col", "img-thumbnail");
                                imgElement.style.width = "150px";
                                imgElement.style.height = "auto";
                                imgElement.style.cursor = "pointer";

                                // ✅ Thêm ảnh mới vào ĐẦU danh sách
                                container.prepend(imgElement);
                            });

                            // ✅ Mở modal sau khi upload xong
                            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
                            modal.show();
                        } else {
                            alert("Lỗi khi tải ảnh lên.");
                        }

                    });
            });
            // Lắng nghe sự kiện undo và redo
            document.querySelector('.ql-undo').addEventListener('click', () => {
                quill.history.undo(); // Thực hiện undo
            });

            document.querySelector('.ql-redo').addEventListener('click', () => {
                quill.history.redo(); // Thực hiện redo
            });


            function autoRenderSlugWithTitle() {
                const titleInput = document.getElementById('name');
                const slugInput = document.getElementById('slug');

                // Nếu người dùng chưa sửa slug thì tự động cập nhật từ title
                if (document.activeElement === titleInput || slugInput.value.trim() === '') {
                    const title = titleInput.value;
                    const slug = title
                        .toLowerCase()
                        .normalize("NFD") // tách dấu
                        .replace(/[\u0300-\u036f]/g, "") // xóa dấu
                        .replace(/đ/g, "d") // thay đ -> d
                        .replace(/[^a-z0-9\s-]/g, "") // xóa ký tự đặc biệt
                        .trim()
                        .replace(/\s+/g, '-') // thay khoảng trắng thành dấu -
                        .replace(/-+/g, '-'); // loại bỏ dấu - lặp

                    slugInput.value = slug;
                }

                return true;
            }

            function syncContent() {
                document.querySelector('#content').value = quill.root.innerHTML;
            }

            function previewContent() {
                document.querySelector('#previewContent').innerHTML = quill.root.innerHTML;
                document.querySelector('#preview').classList.remove('d-none');
            }

            function viewCode() {
                document.querySelector('#htmlContent').textContent = quill.root.innerHTML;
                document.querySelector('#codeView').classList.remove('d-none');
            }

            // Mở modal chọn ảnh
            function openMediaLibrary() {
                $('#mediaLibraryModal').modal('show');
            }

            // Gán sự kiện khi click vào ảnh trong thư viện
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('media-img')) {
                    const selectedImageUrl = e.target.getAttribute('data-url');
                    const range = quill.getSelection();
                    if (range) {
                        quill.insertEmbed(range.index, 'image', selectedImageUrl);
                    } else {
                        // Nếu không có vùng chọn, bạn có thể tự động thêm ảnh ở vị trí con trỏ cuối
                        const length = quill.getLength();
                        quill.insertEmbed(length, 'image', selectedImageUrl);
                    }
                    $('#mediaLibraryModal').modal('hide');
                }
            });

            // Hàm xử lý khi click vào ảnh trong modal
            function imageHandler() {
                // Lấy phần tử modal
                const modal = new bootstrap.Modal(document.getElementById('mediaLibraryModal'));
                modal.show();

                // Gán sự kiện chỉ một lần sau khi modal đã hiển thị
                document.getElementById('mediaLibraryModal').addEventListener('shown.bs.modal', () => {
                    // Lấy tất cả ảnh trong modal
                    const images = document.querySelectorAll('.media-img');

                    // Gán sự kiện click vào mỗi ảnh
                    images.forEach(img => {
                        img.addEventListener('click', function() {
                            const imageUrl = this.getAttribute('data-url'); // Lấy URL của ảnh
                            const imageName = this.getAttribute(
                                'data-name'); // Lấy tên ảnh để dùng làm alt và title
                            const range = quill.getSelection(); // Vị trí con trỏ trong Quill

                            const imgHtml =
                                `<img src="${imageUrl}" alt="${imageName}" title="${imageName}" />`;

                            if (range) {
                                quill.clipboard.dangerouslyPasteHTML(range.index, imgHtml);
                            } else {
                                const length = quill.getLength();
                                quill.clipboard.dangerouslyPasteHTML(length, imgHtml);
                            }
                            quill.update();
                            modal.hide(); // Đóng modal sau khi chọn ảnh
                        });
                    });

                });
            }



            // Tìm kiếm ảnh trong modal
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('imageSearchInput');
                const imageContainer = document.getElementById('imageContainer');
                const paginationContainer = document.getElementById('paginationContainer');
                const pageSize = window.innerWidth < 768 ? 20 : 120;
                let currentPage = 1;
                let allImages = Array.from(document.querySelectorAll('.media-img'));

                function removeVietnameseTones(str) {
                    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/đ/g, "d").replace(/Đ/g, "D");
                }

                function renderImages(images, page) {
                    const start = (page - 1) * pageSize;
                    const end = start + pageSize;
                    const pagedImages = images.slice(start, end);

                    imageContainer.innerHTML = '';
                    pagedImages.forEach(img => {
                        imageContainer.appendChild(img);
                    });

                    renderPagination(images.length, page);
                }

                function renderPagination(totalItems, currentPage) {
                    const totalPages = Math.ceil(totalItems / pageSize);
                    paginationContainer.innerHTML = '';

                    if (totalPages <= 1) return;

                    for (let i = 1; i <= totalPages; i++) {
                        const btn = document.createElement('button');
                        btn.className = 'btn btn-sm mx-1 ' + (i === currentPage ? 'btn-primary' :
                            'btn-outline-primary');
                        btn.textContent = i;
                        btn.addEventListener('click', () => {
                            renderImages(filteredImages, i);
                        });
                        paginationContainer.appendChild(btn);
                    }
                }

                let filteredImages = allImages;

                function applySearch() {
                    const keyword = removeVietnameseTones(searchInput.value.toLowerCase());

                    filteredImages = allImages.filter(img => {
                        const name = img.getAttribute('data-name')?.toLowerCase() || '';
                        return removeVietnameseTones(name).includes(keyword);
                    });

                    currentPage = 1;
                    renderImages(filteredImages, currentPage);
                }

                searchInput.addEventListener('keyup', applySearch);

                $('#mediaLibraryModal').on('shown.bs.modal', function() {
                    searchInput?.focus();
                    allImages = Array.from(document.querySelectorAll('.media-img'));
                    applySearch();
                });
            });

            document.querySelector('.ql-clearAll').addEventListener('click', function() {
                if (confirm("Bạn có chắc muốn xóa tất cả nội dung không?")) {
                    quill.setText('');
                }
            });

            function viewText() {
                document.querySelector('#textContent').textContent = quill.getText();
                document.querySelector('#textView').classList.remove('d-none');
            }
            // Biến để kiểm tra thay đổi nội dung
            let isDirty = false;

            // Lắng nghe sự kiện thay đổi nội dung
            quill.on('text-change', function() {
                isDirty = true;
                const text = quill.getText().trim();
                const wordCount = text.split(/\s+/).filter(word => word.length > 0).length;
                const charCount = text.length;

                document.querySelector('#lengthContent').innerHTML =
                    `Số ký tự: ${charCount} | Số từ: ${wordCount}`;
            });

            // Xử lý sự kiện trước khi người dùng rời khỏi trang
            window.addEventListener('beforeunload', function(event) {
                if (isDirty) {
                    // Hiển thị thông báo xác nhận
                    const message = "Bạn chưa lưu thay đổi. Bạn có chắc muốn thoát?";
                    event.returnValue = message; // Firefox và Chrome
                    return message; // Chrome
                }
            });
        </script>
        <!-- Footer -->
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
    </div>
@endsection
