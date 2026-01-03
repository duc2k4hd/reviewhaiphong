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
                    <!-- AI Writing Assistant -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">🤖 Trợ lý AI viết bài</h6>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="testAIConnection()">
                                <i class="fas fa-wifi"></i> Test kết nối
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Mô tả bài viết bạn muốn AI viết</label>
                                        <textarea id="ai-prompt" class="form-control" rows="3" 
                                            placeholder="Ví dụ: Viết bài review về quán cà phê ngon tại Hải Phòng, có địa chỉ, giá cả, đánh giá chi tiết..."></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Danh mục</label>
                                        <select id="ai-category" class="form-select">
                                            <option value="review">Review</option>
                                            <option value="am-thuc">Ẩm thực</option>
                                            <option value="du-lich">Du lịch</option>
                                            <option value="check-in">Check-in</option>
                                            <option value="dich-vu">Dịch vụ</option>
                                            <option value="tin-tuc">Tin tức</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Giọng văn</label>
                                        <select id="ai-tone" class="form-select">
                                            <option value="professional">Chuyên nghiệp</option>
                                            <option value="friendly">Thân thiện</option>
                                            <option value="casual">Tự nhiên</option>
                                            <option value="formal">Trang trọng</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ngôn ngữ</label>
                                        <select id="ai-language" class="form-select">
                                            <option value="Vietnamese">Tiếng Việt</option>
                                            <option value="English">Tiếng Anh</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary w-100" onclick="generateWithAI()">
                                        <i class="fas fa-magic"></i> Tạo bài viết bằng AI
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Loading và kết quả AI -->
                            <div id="ai-loading" class="text-center py-3" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang tạo bài viết...</span>
                                </div>
                                <p class="mt-2">AI đang viết bài viết, vui lòng chờ...</p>
                            </div>
                            
                            <div id="ai-result" class="alert alert-success" style="display: none;">
                                <h6>✅ Bài viết đã được tạo thành công!</h6>
                                <p>AI đã điền đầy đủ thông tin vào form. Bạn có thể chỉnh sửa và lưu bài viết.</p>
                            </div>
                            
                            <div id="ai-error" class="alert alert-danger" style="display: none;">
                                <h6>❌ Có lỗi xảy ra</h6>
                                <p id="ai-error-message"></p>
                            </div>
                        </div>
                    </div>

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
                                <option value="">-- Chọn danh mục --</option>
                                @foreach ($categories as $category)
                                    @if($category->id == 1)
                                        @continue
                                    @endif
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
                            <input type="text" id="tags" name="tags" class="form-control @error('tags') is-invalid @enderror"
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
                            <textarea id="seo_desc" name="seo_desc" class="form-control @error('seo_desc') is-invalid @enderror">{{ old('seo_desc') }}</textarea>
                            @error('seo_desc')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SEO Keywords</label>
                            <input type="text" id="seo_keywords" name="seo_keywords"
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
        <script src="https://cdn.jsdelivr.net/npm/showdown/dist/showdown.min.js"></script>
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

            // Biến global để kiểm tra thay đổi nội dung
            let isDirty = false;
            // Lưu vị trí con trỏ gần nhất để chèn ảnh đúng chỗ kể cả khi mở modal bị mất focus
            let lastRange = null;
            quill.on('selection-change', function(range) {
                if (range) {
                    lastRange = range;
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
                                imgElement.classList.add("col", "img-thumbnail", "media-img");
                                imgElement.style.width = "100px";
                                imgElement.style.height = "auto";
                                imgElement.style.cursor = "pointer";

                                // ✅ Thêm ảnh mới vào ĐẦU danh sách
                                container.prepend(imgElement);
                            });

                            // ✅ Đóng modal sau khi upload xong
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
                const editorDiv = document.getElementById('editor');
                const htmlEditor = document.getElementById('htmlEditor');
                let html = (editorDiv.style.display !== 'none' ? quill.root.innerHTML : htmlEditor.value) || '';

                // Nếu phát hiện bị encode (&lt;...&gt;), giải mã về HTML thực
                if (html.includes('&lt;') || html.includes('&gt;')) {
                    const txt = document.createElement('textarea');
                    txt.innerHTML = html;
                    let decoded = txt.value;

                    // Loại bỏ <p> bao ngoài các block-level do paste sai trước đó
                    decoded = decoded
                        .replace(/<p>\s*(<(?:h1|h2|h3|ul|ol|blockquote|figure)[\s\S]*?>)\s*<\/p>/gi, '$1')
                        .replace(/<p>\s*(<\/\s*(?:ul|ol|blockquote|figure|h1|h2|h3)>)\s*<\/p>/gi, '$1');

                    document.querySelector('#content').value = decoded.trim();
                    return;
                }

                document.querySelector('#content').value = html.trim();
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
                        isDirty = true;
                    }, 150);
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

            document.querySelector('.ql-clearAll').addEventListener('click', function() {
                if (confirm("Bạn có chắc muốn xóa tất cả nội dung không?")) {
                    quill.setText('');
                }
            });

            function viewText() {
                document.querySelector('#textContent').textContent = quill.getText();
                document.querySelector('#textView').classList.remove('d-none');
            }

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

            // ===== AI WRITING FUNCTIONS =====
            
            /**
             * Test kết nối AI
             */
            function testAIConnection() {
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang test...';
                
                fetch('{{ route("admin.posts.ai.test") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.connected) {
                                showNotification('success', '✅ ' + data.message);
                            } else {
                                showNotification('warning', '⚠️ ' + data.message);
                            }
                        } else {
                            showNotification('error', '❌ ' + data.message);
                        }
                    })
                    .catch(error => {
                        showNotification('error', '❌ Lỗi kết nối: ' + error.message);
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    });
            }

            /**
             * Tạo bài viết bằng AI
             */
            function generateWithAI() {
                const prompt = document.getElementById('ai-prompt').value.trim();
                const category = document.getElementById('ai-category').value;
                const tone = document.getElementById('ai-tone').value;
                const language = document.getElementById('ai-language').value;

                if (!prompt) {
                    showNotification('warning', '⚠️ Vui lòng nhập mô tả bài viết!');
                    document.getElementById('ai-prompt').focus();
                    return;
                }

                // Hiển thị loading
                showAILoading(true);
                hideAIResult();
                hideAIError();

                const formData = new FormData();
                formData.append('prompt', prompt);
                formData.append('category', category);
                formData.append('tone', tone);
                formData.append('language', language);
                formData.append('_token', '{{ csrf_token() }}');

                fetch('{{ route("admin.posts.ai.generate") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    credentials: 'same-origin'
                })
                .then(async (response) => {
                    const contentType = response.headers.get('content-type') || '';
                    const raw = await response.text();
                    let data;
                    if (contentType.includes('application/json')) {
                        try { data = JSON.parse(raw); } catch { throw new Error('Phản hồi JSON không hợp lệ'); }
                    } else {
                        // Không phải JSON (thường là trang HTML lỗi 419/500)
                        const snippet = raw.replace(/<[^>]*>/g, '').trim();
                        throw new Error(snippet ? snippet.substring(0, 300) + (snippet.length > 300 ? '...' : '') : 'Phản hồi không phải JSON');
                    }
                    if (!response.ok) {
                        const msg = data?.message || 'Yêu cầu không thành công';
                        throw new Error(msg);
                    }
                    return data;
                })
                .then(data => {
                    if (data.success) {
                        // Điền dữ liệu vào form
                        fillFormWithAIData(data.data);
                        showAIResult();
                        showNotification('success', '✅ ' + data.message);
                    } else {
                        const err = data.message || 'Có lỗi xảy ra';
                        showAIError(err);
                        showNotification('error', '❌ ' + err);
                    }
                })
                .catch(error => {
                    let msg = error?.message || 'Không xác định';
                    if (msg.includes('DOCTYPE') || msg.includes('<html') || msg.toLowerCase().includes('csrf')) {
                        msg = 'Phiên làm việc có thể đã hết hạn (419) hoặc lỗi máy chủ. Vui lòng tải lại trang và thử lại.';
                    }
                    const errorMessage = 'Lỗi kết nối: ' + msg;
                    showAIError(errorMessage);
                    showNotification('error', '❌ ' + errorMessage);
                })
                .finally(() => {
                    showAILoading(false);
                });
            }

            /**
             * Điền dữ liệu AI vào form
             */
            function fillFormWithAIData(aiData) {
                console.log('Raw AI Data:', aiData);
                
                // Kiểm tra nếu aiData là string JSON, parse nó
                if (typeof aiData === 'string') {
                    try {
                        aiData = JSON.parse(aiData);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        return;
                    }
                }
                
                console.log('Parsed AI Data:', aiData);
                
                // Hàm làm sạch dữ liệu một cách triệt để
                function deepCleanFieldValue(value) {
                    if (typeof value !== 'string') return value;
                    
                    let cleaned = value;
                    
                    // Loại bỏ các pattern JSON một cách triệt để
                    cleaned = cleaned.replace(/^"[^"]+":\s*"/, ''); // "field": "..." ở đầu
                    cleaned = cleaned.replace(/\s*"[^"]+"\s*:\s*"/, ''); // "field": "..." ở giữa
                    cleaned = cleaned.replace(/",?\s*$/, ''); // dấu phẩy và dấu ngoặc kép ở cuối
                    cleaned = cleaned.replace(/,\s*$/, ''); // dấu phẩy thừa
                    cleaned = cleaned.replace(/\s*"[^"]+"\s*:\s*$/, ''); // "field": ở cuối
                    cleaned = cleaned.replace(/^\s*\{\s*"/, ''); // { " ở đầu
                    cleaned = cleaned.replace(/"\s*\}\s*$/, ''); // " } ở cuối
                    
                    // Loại bỏ các ký tự JSON khác
                    cleaned = cleaned.replace(/^\s*\[\s*"/, ''); // [ " ở đầu
                    cleaned = cleaned.replace(/"\s*\]\s*$/, ''); // " ] ở cuối
                    cleaned = cleaned.replace(/^\s*"/, ''); // " ở đầu
                    cleaned = cleaned.replace(/"\s*$/, ''); // " ở cuối
                    
                    return cleaned.trim();
                }
                
                // Điền các trường cơ bản với dữ liệu đã làm sạch hoàn toàn
                if (aiData.title) {
                    const nameField = document.getElementById('name');
                    if (nameField) {
                        const cleanTitle = deepCleanFieldValue(aiData.title);
                        nameField.value = cleanTitle;
                        console.log('Setting title:', cleanTitle);
                    }
                }
                
                if (aiData.seo_title) {
                    const seoTitleField = document.getElementById('seo_title');
                    if (seoTitleField) {
                        const cleanSeoTitle = deepCleanFieldValue(aiData.seo_title);
                        seoTitleField.value = cleanSeoTitle;
                        console.log('Setting seo_title:', cleanSeoTitle);
                    }
                }
                
                if (aiData.seo_desc) {
                    const seoDescField = document.getElementById('seo_desc');
                    if (seoDescField) {
                        const cleanSeoDesc = deepCleanFieldValue(aiData.seo_desc);
                        seoDescField.value = cleanSeoDesc;
                        console.log('Setting seo_desc:', cleanSeoDesc);
                    }
                }
                
                if (aiData.seo_keywords) {
                    const seoKeywordsField = document.getElementById('seo_keywords');
                    if (seoKeywordsField) {
                        const cleanSeoKeywords = deepCleanFieldValue(aiData.seo_keywords);
                        seoKeywordsField.value = cleanSeoKeywords;
                        console.log('Setting seo_keywords:', cleanSeoKeywords);
                    }
                }
                
                if (aiData.tags) {
                    const tagsField = document.getElementById('tags');
                    if (tagsField) {
                        const cleanTags = deepCleanFieldValue(aiData.tags);
                        tagsField.value = cleanTags;
                        console.log('Setting tags:', cleanTags);
                    }
                }
                
                if (aiData.slug) {
                    const slugField = document.getElementById('slug');
                    if (slugField) {
                        const cleanSlug = deepCleanFieldValue(aiData.slug);
                        // Loại bỏ prefix "title-" nếu có
                        const finalSlug = cleanSlug.replace(/^title-/, '');
                        slugField.value = finalSlug;
                        console.log('Setting slug:', finalSlug);
                    }
                }
                
                // Hàm chuyển Markdown nhẹ -> HTML (fallback nếu content là Markdown)
                function formatAiTextToHtml(text) {
                    if (!text || typeof text !== 'string') return '';
                    const converter = new showdown.Converter({ tables: true, strikethrough: true });
                    return converter.makeHtml(text);
                }

                // Điền nội dung vào editor với dữ liệu đã được làm sạch hoàn toàn
                if (aiData.content) {
                    const cleanContent = deepCleanFieldValue(aiData.content);
                    // Nếu nội dung đã là HTML (có thẻ đóng mở), dùng trực tiếp; nếu không thì convert Markdown -> HTML
                    const looksLikeHtml = /<\w+[^>]*>.*<\/\w+>/.test(cleanContent) || /<h[1-6]|<p>|<ul>|<ol>|<li>|<strong>|<em>|<br\/?|<blockquote/.test(cleanContent);
                    let html = looksLikeHtml ? cleanContent : formatAiTextToHtml(cleanContent);

                    // Bắt buộc bắt đầu từ H2: xóa mọi H1, nâng heading đầu tiên thành H2 nếu cần
                    html = html.replace(/<h1[^>]*>[\s\S]*?<\/h1>/gi, '');
                    if (!/<h2\b/i.test(html)) {
                        html = html.replace(/<h([3-6])([^>]*)>/i, '<h2$2>');
                        html = html.replace(/<\/h([3-6])>/i, '</h2>');
                    }
                    console.log('Setting content HTML:', html);

                    // Paste HTML đã chuyển đổi vào editor
                    quill.clipboard.dangerouslyPasteHTML(html);
                    
                    // Cập nhật số từ
                    const text = quill.getText().trim();
                    const wordCount = text.split(/\s+/).filter(word => word.length > 0).length;
                    const charCount = text.length;
                    document.querySelector('#lengthContent').innerHTML = 
                        `Số ký tự: ${charCount} | Số từ: ${wordCount}`;
                }

                // Cập nhật trạng thái dirty
                isDirty = true;
            }

            /**
             * Hiển thị/ẩn loading AI
             */
            function showAILoading(show) {
                document.getElementById('ai-loading').style.display = show ? 'block' : 'none';
            }

            /**
             * Hiển thị kết quả AI
             */
            function showAIResult() {
                document.getElementById('ai-result').style.display = 'block';
            }

            /**
             * Ẩn kết quả AI
             */
            function hideAIResult() {
                document.getElementById('ai-result').style.display = 'none';
            }

            /**
             * Hiển thị lỗi AI
             */
            function showAIError(message) {
                document.getElementById('ai-error-message').textContent = message;
                document.getElementById('ai-error').style.display = 'block';
            }

            /**
             * Ẩn lỗi AI
             */
            function hideAIError() {
                document.getElementById('ai-error').style.display = 'none';
            }

            /**
             * Hiển thị thông báo
             */
            function showNotification(type, message) {
                // Tạo toast notification
                const toast = document.createElement('div');
                toast.className = `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} alert-dismissible fade show position-fixed`;
                toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                toast.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.body.appendChild(toast);
                
                // Tự động ẩn sau 5 giây
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 5000);
            }
        </script>
        <!-- Footer -->
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
    </div>
@endsection
