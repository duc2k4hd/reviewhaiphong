@extends('admin.layouts.main')

@section('title', 'Chỉnh sửa bài viết - Staff')

@section('content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <!--  -->
            <div class="card">
                <h5 class="card-header">Chỉnh sửa bài viết - Staff</h5>
                <div class="container">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <!-- Status Info for Staff -->
                    <div class="alert alert-info mb-4">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Lưu ý:</strong> Staff chỉ có thể chỉnh sửa nội dung bài viết. Trạng thái sẽ được Admin quản lý.
                    </div>

                    <form method="POST" action="{{ route('admin.staff.posts.update', $post) }}" enctype="multipart/form-data"
                        onsubmit="syncContent()">
                        @csrf
                        @method('PUT')

                        {{-- Tiêu đề bài viết --}}
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề bài viết <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $post->name) }}"
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
                            <label class="form-label">Danh mục <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">Chọn danh mục</option>
                                @foreach ($categories as $category)
                                    @if($category->id == 1)
                                        @continue
                                    @endif
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id', $post->category_id) == $category->id ? 'selected' : '' }}>
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
                            <input type="text" id="tags" name="tags" class="form-control @error('tags') is-invalid @enderror"
                                value="{{ old('tags', $post->tags) }}" placeholder="ví dụ: Laravel, PHP, Quill">
                            @error('tags')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Slug --}}
                        <div class="mb-3">
                            <label class="form-label">Đường dẫn (Slug)</label>
                            <input type="text" id="slug" name="slug"
                                class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $post->slug) }}"
                                oninput="autoRenderSlugWithTitle();" placeholder="tu-khoa-bai-viet">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="button" class="btn btn-outline-primary mb-3" onclick="openMediaLibrary()">📷 Chọn
                            ảnh</button>
                        
                        {{-- Nội dung --}}
                        <div class="mb-3">
                            <label class="form-label">Nội dung bài viết <span class="text-danger">*</span></label>
                            <div id="editor" style="height: 300px;">{!! old('content', $post->content) !!}</div>
                            <textarea class="edit-code" id="htmlEditor" style="width: 100%; height: 300px; display: none;">{!! old('content', $post->content) !!}</textarea>
                            <div class="text-muted small">Số từ <span id="lengthContent">0 từ (0 ký tự)</span></div>
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
                                value="{{ old('seo_title', $post->seo_title) }}">
                            @error('seo_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SEO Description</label>
                            <textarea id="seo_desc" name="seo_desc" class="form-control @error('seo_desc') is-invalid @enderror">{{ old('seo_desc', $post->seo_desc) }}</textarea>
                            @error('seo_desc')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SEO Keywords</label>
                            <input type="text" id="seo_keywords" name="seo_keywords"
                                class="form-control @error('seo_keywords') is-invalid @enderror"
                                value="{{ old('seo_keywords', $post->seo_keywords) }}">
                            @error('seo_keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Trạng thái hiện tại --}}
                        <div class="mb-3">
                            <label class="form-label">Trạng thái hiện tại</label>
                            <div class="form-control-plaintext">
                                @switch($post->status)
                                    @case('published')
                                        <span class="badge bg-success">Đã xuất bản</span>
                                        @break
                                    @case('pending')
                                        <span class="badge bg-warning">Chờ duyệt</span>
                                        @break
                                    @case('draft')
                                        <span class="badge bg-secondary">Bản nháp</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $post->status }}</span>
                                @endswitch
                            </div>
                            <small class="form-text text-muted">Staff không thể thay đổi trạng thái bài viết</small>
                        </div>

                        {{-- Các nút điều khiển --}}
                        <div class="d-flex gap-2 mb-3">
                            <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
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
                
                <!-- Media Library Modal -->
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
        </div>
    </div>

    <style>
        #editor img {
            width: 300px;
            height: auto;
        }

        /* Quill Editor Container */
        #editor {
            border: 1px solid #ccc;
            border-radius: 5px;
            background: white;
        }

        .ql-toolbar.ql-snow {
            border: 1px solid #ccc;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            background: #f8f9fa;
        }

        .ql-container.ql-snow {
            border: 1px solid #ccc;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }

        .ql-editor {
            caret-color: transparent;
            position: relative;
            font-size: 16px;
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
            0%, 50% { opacity: 1; }
            50.01%, 100% { opacity: 0; }
        }

        .ql-editor::selection {
            background-color: rgba(255, 0, 0, 0.2);
        }

        .ql-editor::before {
            font-size: inherit !important;
        }

        .ql-editor * {
            caret-color: red;
        }

        .ql-editor {
            font-size: 18px;
        }

        /* Quill Editor Styles */
        .ql-editor {
            min-height: 350px;
            font-size: 16px;
            line-height: 1.6;
        }

        .ql-editor img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .ql-editor h1, .ql-editor h2, .ql-editor h3, .ql-editor h4, .ql-editor h5, .ql-editor h6 {
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            font-weight: 600;
        }

        .ql-editor p {
            margin-bottom: 1em;
        }

        .ql-editor blockquote {
            border-left: 4px solid #007bff;
            padding-left: 1em;
            margin: 1em 0;
            font-style: italic;
            background-color: #f8f9fa;
            padding: 1em;
            border-radius: 4px;
        }

        .ql-editor code {
            background-color: #f8f9fa;
            padding: 0.2em 0.4em;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        .ql-editor pre {
            background-color: #f8f9fa;
            padding: 1em;
            border-radius: 4px;
            overflow-x: auto;
        }

        /* Preview sections */
        #preview, #codeView, #textView {
            margin-top: 2rem;
        }

        #previewContent img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Media library modal */
        #mediaModal .modal-dialog {
            max-width: 90%;
        }

        #mediaModal .card-img-top {
            transition: transform 0.2s ease;
        }

        #mediaModal .card-img-top:hover {
            transform: scale(1.05);
        }

        /* Editor controls */
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        /* Content info */
        #lengthContent, #charCount {
            font-weight: 600;
            color: #007bff;
        }

        .modal-backdrop {
            z-index: 1040;
        }
        
        #mediaLibraryModal {
            z-index: 1050;
        }
        
        #editor {
            z-index: 1;
            position: relative;
        }
        
        .modal-open {
            overflow: hidden;
        }
        
        .modal-open .modal {
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        #mediaLibraryModal .modal-dialog {
            max-width: 95%;
            width: 90%;
        }
        
        #imageContainer {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .media-img {
            width: 100px;
            height: auto;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .media-img:hover {
            border-color: #007bff;
            transform: scale(1.05);
        }
        
        #imageSearchInput {
            min-width: 200px;
        }
        
        @media (max-width: 768px) {
            #mediaLibraryModal .modal-dialog {
                width: 95%;
                max-width: 95%;
            }
            
            .media-img {
                width: 80px;
            }
            
            #imageSearchInput {
                min-width: 150px;
            }
        }

        @media (min-width: 768px) {
            #editor, .edit-code {
                width: 100%;
                height: 500px;
                min-height: 500px;
                border: 1px solid #ccc;
                border-radius: 5px;
                padding: 10px;
            }

            #editor {
                border: 2px solid blue;
            }

            .edit-code {
                border: 2px solid red;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .ql-toolbar.ql-snow {
                padding: 8px;
            }
            
            .ql-toolbar.ql-snow .ql-formats {
                margin-right: 8px;
            }
            
            .ql-toolbar.ql-snow button {
                width: 28px;
                height: 28px;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/showdown/dist/showdown.min.js"></script>
    
    <script>
        const quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Viết nội dung bài viết...',
            modules: {
                toolbar: [
                    [{'font': []}],
                    [{'size': ['small', 'medium', 'large', 'huge']}],
                    [{'header': [1, 2, 3, 4, 5, 6, false]}],
                    [{'align': []}],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{'list': 'ordered'}, {'list': 'bullet'}],
                    [{'indent': '-1'}, {'indent': '+1'}],
                    ['link', 'image', 'video'],
                    ['blockquote', 'code-block'],
                    [{'color': []}, {'background': []}],
                    ['undo', 'redo'],
                    ['clean']
                ],
                history: {
                    delay: 2000,
                    maxStack: 500,
                    userOnly: true
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

        let isDirty = false;
        let lastRange = null;
        
        quill.on('selection-change', function(range) {
            if (range) {
                lastRange = range;
            }
        });

        // Auto-generate slug from title
        function autoRenderSlugWithTitle() {
            const titleInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');

            if (document.activeElement === titleInput || slugInput.value.trim() === '') {
                const title = titleInput.value;
                const slug = title
                    .toLowerCase()
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "")
                    .replace(/đ/g, "d")
                    .replace(/[^a-z0-9\s-]/g, "")
                    .trim()
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');

                slugInput.value = slug;
            }
            return true;
        }

        // Sync content before form submission
        function syncContent() {
            const editorDiv = document.getElementById('editor');
            const htmlEditor = document.getElementById('htmlEditor');
            let html = (editorDiv.style.display !== 'none' ? quill.root.innerHTML : htmlEditor.value) || '';

            if (html.includes('&lt;') || html.includes('&gt;')) {
                const txt = document.createElement('textarea');
                txt.innerHTML = html;
                let decoded = txt.value;

                decoded = decoded
                    .replace(/<p>\s*(<(?:h1|h2|h3|ul|ol|blockquote|figure)[\s\S]*?>)\s*<\/p>/gi, '$1')
                    .replace(/<p>\s*(<\/\s*(?:ul|ol|blockquote|figure|h1|h2|h3)>)\s*<\/p>/gi, '$1');

                document.querySelector('#content').value = decoded.trim();
                return;
            }

            document.querySelector('#content').value = html.trim();
        }

        // Preview content
        function previewContent() {
            document.querySelector('#previewContent').innerHTML = quill.root.innerHTML;
            document.querySelector('#preview').classList.remove('d-none');
        }

        // View HTML code
        function viewCode() {
            document.querySelector('#htmlContent').textContent = quill.root.innerHTML;
            document.querySelector('#codeView').classList.remove('d-none');
        }

        // View plain text
        function viewText() {
            document.querySelector('#textContent').textContent = quill.getText();
            document.querySelector('#textView').classList.remove('d-none');
        }

        // Open media library
        function openMediaLibrary() {
            lastRange = quill.getSelection(true);
            const modal = new bootstrap.Modal(document.getElementById('mediaLibraryModal'));
            modal.show();
        }

        // Toggle between Quill editor and HTML editor
        document.getElementById('editCodeBtn').addEventListener('click', function() {
            const editorDiv = document.getElementById('editor');
            const htmlEditor = document.getElementById('htmlEditor');
            
            if (editorDiv.style.display !== 'none') {
                htmlEditor.value = quill.root.innerHTML;
                editorDiv.style.display = 'none';
                htmlEditor.style.display = 'block';
                this.textContent = 'Xong';
            } else {
                quill.root.innerHTML = htmlEditor.value;
                htmlEditor.style.display = 'none';
                editorDiv.style.display = 'block';
                this.textContent = 'Sửa Code';
            }
        });

        // Handle image clicks in media library
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('media-img')) {
                const selectedImageUrl = e.target.getAttribute('data-url');
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('mediaLibraryModal'));
                if (modal) {
                    modal.hide();
                }
                
                setTimeout(() => {
                    let range = quill.getSelection();
                    if (!range && lastRange) range = lastRange;
                    let index = range && typeof range.index === 'number' ? range.index : quill.getLength();

                    quill.focus();
                    quill.insertEmbed(index, 'image', selectedImageUrl);
                    quill.setSelection(index + 1);
                    isDirty = true;
                }, 150);
            }
        });

        // Handle image upload
        document.getElementById("imageUploadForm").addEventListener("submit", function(event) {
            event.preventDefault();

            const formData = new FormData();
            const files = document.getElementById("imageUploadInput").files;

            if (files.length === 0) {
                alert("Vui lòng chọn ít nhất một ảnh để tải lên.");
                return;
            }

            for (let i = 0; i < files.length; i++) {
                formData.append("image[]", files[i]);
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/api/media/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.urls) {
                    alert("Upload thành công " + data.urls.length + " ảnh!");
                    const container = document.getElementById("imageContainer");

                    data.urls.forEach(url => {
                        const filename = url.split('/').pop();

                        const imgElement = document.createElement("img");
                        imgElement.src = url;
                        imgElement.setAttribute("data-url", url);
                        imgElement.setAttribute("data-name", filename);
                        imgElement.classList.add("col", "img-thumbnail", "media-img");
                        imgElement.style.width = "100px";
                        imgElement.style.height = "auto";
                        imgElement.style.cursor = "pointer";

                        container.prepend(imgElement);
                    });

                    const modal = bootstrap.Modal.getInstance(document.getElementById('mediaLibraryModal'));
                    if (modal) {
                        modal.hide();
                    }
                } else {
                    alert("Lỗi khi tải ảnh lên.");
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert("Có lỗi xảy ra khi upload ảnh.");
            });
        });

        // Search functionality for images
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
                    btn.className = 'btn btn-sm mx-1 ' + (i === currentPage ? 'btn-primary' : 'btn-outline-primary');
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

            const mediaModal = document.getElementById('mediaLibraryModal');
            
            mediaModal.addEventListener('shown.bs.modal', function() {
                searchInput?.focus();
                allImages = Array.from(document.querySelectorAll('.media-img'));
                applySearch();
            });

            mediaModal.addEventListener('hidden.bs.modal', function() {
                setTimeout(() => {
                    quill.focus();
                }, 100);
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = bootstrap.Modal.getInstance(mediaModal);
                    if (modal) {
                        modal.hide();
                    }
                }
            });
        });

        // Content change listener
        quill.on('text-change', function() {
            isDirty = true;
            const text = quill.getText().trim();
            const wordCount = text.split(/\s+/).filter(word => word.length > 0).length;
            const charCount = text.length;

            document.querySelector('#lengthContent').innerHTML = `${wordCount} từ (${charCount} ký tự)`;
        });

        // Initialize word count on page load
        document.addEventListener('DOMContentLoaded', function() {
            const text = quill.getText().trim();
            const wordCount = text.split(/\s+/).filter(word => word.length > 0).length;
            const charCount = text.length;
            document.querySelector('#lengthContent').innerHTML = `${wordCount} từ (${charCount} ký tự)`;
        });

        // Thêm context menu cho ảnh trong editor
        quill.on('selection-change', function(range, oldRange, source) {
            if (source === 'user' && range && range.length === 0) {
                const [leaf] = quill.getLeaf(range.index);
                if (leaf && leaf.domNode && leaf.domNode.tagName === 'IMG') {
                    showImageContextMenu(leaf.domNode, range.index);
                }
            }
        });

        // Đặt quill instance vào global để context menu có thể truy cập
        window.currentQuill = quill;

        // Before unload warning
        window.addEventListener('beforeunload', function(event) {
            if (isDirty) {
                const message = "Bạn chưa lưu thay đổi. Bạn có chắc muốn thoát?";
                event.returnValue = message;
                return message;
            }
        });
    </script>
@endsection
