@extends('admin.layouts.main')

@section('title', 'Trích xuất link từ Vinpearl.com')

@section('content')
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <h5 class="card-header">
                <i class="fas fa-link"></i> Trích xuất link từ Vinpearl.com
            </h5>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('admin.vinpearl-link-extractor.extract') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="urls" class="form-label">
                            <strong>URL trang danh sách bài viết từ Vinpearl.com (mỗi link 1 dòng)</strong>
                        </label>
                        <textarea 
                            class="form-control @error('urls') is-invalid @enderror" 
                            id="urls" 
                            name="urls" 
                            rows="10"
                            placeholder="https://vinpearl.com/vi&#10;https://vinpearl.com/vi/du-lich/&#10;https://vinpearl.com/vi/am-thuc/..."
                            required
                        >{{ old('urls', 'https://vinpearl.com/vi') }}</textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> Nhập nhiều URL trang danh sách bài viết (trang chủ, category, hoặc bất kỳ trang nào có danh sách bài viết), mỗi URL trên một dòng
                        </div>
                        @error('urls')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Trích xuất link
                    </button>
                </form>

                <div class="mt-4">
                    <h6>Cách sử dụng:</h6>
                    <ol>
                        <li>Vào các trang danh sách bài viết trên vinpearl.com (ví dụ: trang chủ, category, tag...)</li>
                        <li>Copy URL của từng trang đó</li>
                        <li>Dán vào ô trên, mỗi URL trên một dòng và nhấn "Trích xuất link"</li>
                        <li>Tool sẽ tự động tìm và trích xuất tất cả link bài viết từ tất cả các trang đã nhập</li>
                        <li>Copy các link đã trích xuất để sử dụng cho tool cào bài viết</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

