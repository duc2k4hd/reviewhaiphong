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

                        {{-- Nội dung --}}
                        <div class="mb-3">
                            <label class="form-label">Nội dung bài viết</label>
                            <div id="editor" style="height: 300px;">{!! old('content') !!}</div>
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
                            <button type="button" class="btn btn-outline-secondary" onclick="viewCode()">Xem Code</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="viewText()">Xem Text</button>
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

            </div>
            <!--/ Responsive Table -->
        </div>
        <!-- / Content -->
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
                        ['clean'],
                        ['clearAll'] // Tạo thêm nút Clear All
                    ]
                }
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
