@extends('admin.layouts.main')

@section('title', 'Sửa bài viết')

@section('content')
    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="card">
                <h5 class="card-header">Sửa bài viết: #{{ $post->id }}</h5>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.posts.update', $post) }}" enctype="multipart/form-data" onsubmit="return syncContent()">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Tiêu đề bài viết</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $post->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Danh mục <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chọn danh mục --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ (old('category_id', $post->category_id) == $category->id) ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Đường dẫn (slug)</label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug', $post->slug) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Thẻ (Tags)</label>
                            <input type="text" name="tags" class="form-control" value="{{ old('tags', $post->tags) }}" placeholder="ví dụ: cafe, hải phòng, review">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ảnh đại diện (SEO)</label>
                            <input type="file" name="seo_image" class="form-control" accept="image/*">
                            @if(!empty($post->seo_image))
                                <div class="mt-2"><img src="/client/assets/images/posts/{{ $post->seo_image }}" alt="SEO Image" style="width:120px;height:auto;border:1px solid #eee;border-radius:6px"></div>
                            @endif
                        </div>

                        <button type="button" class="btn btn-outline-primary mb-3" onclick="openMediaLibrary()">📷 Chọn ảnh</button>

                        <div class="mb-3">
                            <label class="form-label">Nội dung bài viết</label>
                            <div id="editor" style="height: 400px; border:1px solid #ddd; border-radius:6px;">{!! old('content', $post->content) !!}</div>
                            <textarea id="htmlEditor" class="form-control" style="display:none; height: 400px;">{!! old('content', $post->content) !!}</textarea>
                            <input type="hidden" id="content" name="content">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SEO Title</label>
                                <input type="text" name="seo_title" class="form-control" value="{{ old('seo_title', $post->seo_title) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SEO Keywords</label>
                                <input type="text" name="seo_keywords" id="seo_keywords" class="form-control" value="{{ old('seo_keywords', $post->seo_keywords) }}" placeholder="Ví dụ: cafe, hải phòng, review, ẩm thực">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SEO Description</label>
                            <textarea name="seo_desc" id="seo_desc" class="form-control" rows="3">{{ old('seo_desc', $post->seo_desc) }}</textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Lưu thay đổi bài viết?')">Lưu thay đổi</button>
                            <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Media Library -->
        <div class="modal fade" id="mediaLibraryModal" tabindex="-1" aria-labelledby="mediaLibraryLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" style="max-width: 95%; width: 90%;">
                <div class="modal-content p-3">
                    <div class="modal-header flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                        <!-- Form Upload -->
                        <form id="imageUploadForm" enctype="multipart/form-data" class="d-flex row flex-wrap align-items-center gap-2">
                            @csrf
                            <input type="file" id="imageUploadInput" accept="image/webp,image/png,image/jpg,image/jpeg" class="col form-control" name="image[]" multiple>
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
                        @php
                            $path = public_path('client/assets/images/posts');
                            $images = ['name' => [], 'urls' => []];
                            
                            function slugToTitle($slug) {
                                return implode(' ', array_map('ucfirst', explode('-', $slug)));
                            }
                            
                            if (file_exists($path)) {
                                $files = scandir($path);
                                foreach ($files as $file) {
                                    if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                        $images['name'][] = slugToTitle(str_replace(['http://127.0.0.1:8000/client/assets/images/posts/', '.webp'], '', $file));
                                        $images['urls'][] = str_replace('http://127.0.0.1:8000', '', asset('client/assets/images/posts/' . $file));
                                    }
                                }
                            }
                        @endphp
                        
                        @foreach ($images['urls'] as $key => $image)
                            @php
                                $filename = basename($image);
                                $name = $images['name'][$key];
                            @endphp
                            <img src="{{ $image }}" data-url="{{ $image }}" data-name="{{ $name }}" class="media-img col" style="width: 100px; height: auto; cursor: pointer;">
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-center mt-2" id="paginationContainer">
                        <!-- Nút phân trang sẽ render ở đây -->
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Style cho modal media library */
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
            
            /* Đảm bảo modal hoạt động đúng */
            .modal-backdrop {
                z-index: 1040;
            }
            
            #mediaLibraryModal {
                z-index: 1050;
            }
            
            /* Đảm bảo Quill editor không bị ảnh hưởng */
            #editor {
                z-index: 1;
                position: relative;
            }
            
            /* Xử lý overlay */
            .modal-open {
                overflow: hidden;
            }
            
            .modal-open .modal {
                overflow-x: hidden;
                overflow-y: auto;
            }
            
            /* CSS cho Image Resizer và Alignment */
            .ql-editor img {
                cursor: pointer;
                max-width: 100%;
                height: auto;
            }
            
            /* Image alignment classes */
            .ql-editor img.align-left {
                float: left;
                margin: 0 20px 20px 0;
            }
            
            .ql-editor img.align-right {
                float: right;
                margin: 0 0 20px 20px;
            }
            
            .ql-editor img.align-center {
                display: block;
                margin: 20px auto;
            }
            
            .ql-editor img.align-full {
                width: 100%;
                height: auto;
            }
            
            /* Image resize handles */
            .ql-editor .image-resizer {
                border: 2px solid #007bff;
                position: relative;
            }
            
            .ql-editor .image-resizer .resize-handle {
                background: #007bff;
                border: 1px solid white;
                width: 8px;
                height: 8px;
                position: absolute;
            }
            
            .ql-editor .image-resizer .resize-handle.nw { top: -4px; left: -4px; cursor: nw-resize; }
            .ql-editor .image-resizer .resize-handle.ne { top: -4px; right: -4px; cursor: ne-resize; }
            .ql-editor .image-resizer .resize-handle.sw { bottom: -4px; left: -4px; cursor: sw-resize; }
            .ql-editor .image-resizer .resize-handle.se { bottom: -4px; right: -4px; cursor: se-resize; }
            
            /* Context Menu Styling */
            .image-context-menu {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                padding: 8px 0;
                min-width: 180px;
                font-size: 14px;
            }
            
            .context-menu-item {
                padding: 8px 16px;
                cursor: pointer;
                transition: background-color 0.2s;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .context-menu-item:hover {
                background-color: #f8f9fa;
            }
            
            .context-menu-separator {
                height: 1px;
                background-color: #eee;
                margin: 4px 0;
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
        </style>

        <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet"/>
        
        <script>
            // Cấu hình Quill với toolbar đầy đủ
            const toolbarOptions = [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'direction': 'rtl' }],
                [{ 'size': ['small', false, 'large', 'huge'] }],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'font': [] }],
                [{ 'align': [] }],
                ['clean'],
                ['link', 'image', 'video']
            ];

            const quill = new Quill('#editor', { 
                theme: 'snow', 
                placeholder: 'Viết nội dung bài viết...',
                modules: {
                    toolbar: toolbarOptions
                }
            });
            
            let lastRange = null;
            
            function syncContent(){
                document.getElementById('content').value = quill.root.innerHTML.trim();
                
                // Kiểm tra danh mục trước khi submit
                const categorySelect = document.querySelector('select[name="category_id"]');
                if (!categorySelect.value) {
                    alert('Vui lòng chọn danh mục cho bài viết!');
                    categorySelect.focus();
                    return false;
                }
                
                return true;
            }

            // Mở modal chọn ảnh
            function openMediaLibrary() {
                // Lưu vị trí con trỏ hiện tại
                lastRange = quill.getSelection(true);
                // Mở modal
                const modal = new bootstrap.Modal(document.getElementById('mediaLibraryModal'));
                modal.show();
            }

            // Gán sự kiện khi click vào ảnh trong thư viện
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('media-img')) {
                    const selectedImageUrl = e.target.getAttribute('data-url');
                    
                    // Đóng modal trước
                    const modal = bootstrap.Modal.getInstance(document.getElementById('mediaLibraryModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Chèn ảnh vào editor sau khi modal đã đóng
                    setTimeout(() => {
                        let range = quill.getSelection();
                        if (!range && lastRange) range = lastRange;
                        let index = range && typeof range.index === 'number' ? range.index : quill.getLength();

                        quill.focus();
                        quill.insertEmbed(index, 'image', selectedImageUrl);
                        quill.setSelection(index + 1);
                    }, 150);
                }
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

            // Hiển thị context menu cho ảnh
            function showImageContextMenu(imgElement, index) {
                // Xóa context menu cũ nếu có
                const oldMenu = document.querySelector('.image-context-menu');
                if (oldMenu) oldMenu.remove();

                const menu = document.createElement('div');
                menu.className = 'image-context-menu';
                menu.innerHTML = `
                    <div class="context-menu-item" onclick="alignImage(${index}, 'left')">⬅️ Căn trái</div>
                    <div class="context-menu-item" onclick="alignImage(${index}, 'center')">⏺️ Căn giữa</div>
                    <div class="context-menu-item" onclick="alignImage(${index}, 'right')">➡️ Căn phải</div>
                    <div class="context-menu-item" onclick="alignImage(${index}, 'full')">📏 Căn đầy</div>
                    <div class="context-menu-separator"></div>
                    <div class="context-menu-item" onclick="resizeImage(${index}, 'small')">📏 Nhỏ</div>
                    <div class="context-menu-item" onclick="resizeImage(${index}, 'medium')">📏 Vừa</div>
                    <div class="context-menu-item" onclick="resizeImage(${index}, 'large')">📏 Lớn</div>
                    <div class="context-menu-separator"></div>
                    <div class="context-menu-item" onclick="removeImage(${index})">🗑️ Xóa ảnh</div>
                `;

                // Đặt vị trí menu
                const rect = imgElement.getBoundingClientRect();
                menu.style.position = 'fixed';
                menu.style.left = rect.left + 'px';
                menu.style.top = (rect.bottom + 5) + 'px';
                menu.style.zIndex = '9999';

                document.body.appendChild(menu);

                // Đóng menu khi click ra ngoài
                setTimeout(() => {
                    document.addEventListener('click', function closeMenu() {
                        menu.remove();
                        document.removeEventListener('click', closeMenu);
                    });
                }, 100);
            }

            // Căn chỉnh ảnh
            function alignImage(index, alignment) {
                const [leaf] = quill.getLeaf(index);
                if (leaf && leaf.domNode && leaf.domNode.tagName === 'IMG') {
                    const img = leaf.domNode;
                    
                    // Xóa class cũ
                    img.classList.remove('align-left', 'align-center', 'align-right', 'align-full');
                    
                    // Xóa style cũ
                    img.style.float = '';
                    img.style.display = '';
                    img.style.margin = '';
                    img.style.width = '';
                    img.style.textAlign = '';
                    
                    // Thêm inline CSS mới
                    switch(alignment) {
                        case 'left':
                            img.style.float = 'left';
                            img.style.margin = '0 20px 20px 0';
                            img.style.maxWidth = '50%';
                            break;
                        case 'center':
                            img.style.display = 'block';
                            img.style.margin = '20px auto';
                            img.style.textAlign = 'center';
                            break;
                        case 'right':
                            img.style.float = 'right';
                            img.style.margin = '0 0 20px 20px';
                            img.style.maxWidth = '50%';
                            break;
                        case 'full':
                            img.style.width = '100%';
                            img.style.height = 'auto';
                            img.style.margin = '20px 0';
                            break;
                    }
                }
            }

            // Thay đổi kích thước ảnh
            function resizeImage(index, size) {
                const [leaf] = quill.getLeaf(index);
                if (leaf && leaf.domNode && leaf.domNode.tagName === 'IMG') {
                    const img = leaf.domNode;
                    
                    switch(size) {
                        case 'small':
                            img.style.width = '200px';
                            break;
                        case 'medium':
                            img.style.width = '400px';
                            break;
                        case 'large':
                            img.style.width = '600px';
                            break;
                    }
                }
            }

            // Xóa ảnh
            function removeImage(index) {
                quill.deleteText(index, 1);
            }

            // Xử lý form upload ảnh
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

                        // Đóng modal sau khi upload xong
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

                // Xử lý modal events
                const mediaModal = document.getElementById('mediaLibraryModal');
                
                mediaModal.addEventListener('shown.bs.modal', function() {
                    searchInput?.focus();
                    allImages = Array.from(document.querySelectorAll('.media-img'));
                    applySearch();
                });

                mediaModal.addEventListener('hidden.bs.modal', function() {
                    // Đảm bảo focus về editor sau khi đóng modal
                    setTimeout(() => {
                        quill.focus();
                    }, 100);
                });

                // Xử lý ESC key để đóng modal
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        const modal = bootstrap.Modal.getInstance(mediaModal);
                        if (modal) {
                            modal.hide();
                        }
                    }
                });
            });
        </script>
    </div>
@endsection


