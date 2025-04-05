<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('client.module.css')
    <link rel="shortcut icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" type="image/x-icon">
    <title>{{ $post->seo_title }}</title>

    <title>{{ $settings['site_name'] }} - {{ $post->seo_title }}</title>


    <!-- Từ khóa SEO (nên có các từ khóa phù hợp với nội dung trang) -->
    <meta name="keywords" content="{{ $settings['seo_keywords'] }}">

    <!-- Tên tác giả của trang -->
    <meta name="author" content="{{ $settings['seo_author'] }}">

    <!-- Thẻ Robots giúp chỉ định các công cụ tìm kiếm có thể làm gì với trang này -->
    <meta name="robots" content="index, follow">  <!-- "noindex, nofollow" nếu không muốn công cụ tìm kiếm đánh chỉ mục -->

    <meta name="description" content="{{ $settings['site_description'] }}">

    <!-- Thời gian khi trang được tạo -->
    <meta http-equiv="date" content="{{ \Carbon\Carbon::parse($post->published_at)->format('d/m/y') }}" />

    <!-- Thẻ ngôn ngữ trang -->
    <meta name="language" content="{{ $settings['site_language'] }}">
    
    <!-- Thẻ bản quyền -->
    <meta name="copyright" content="{{ $settings['seo_author'] }}">

    <!-- Cảnh báo về nội dung nhạy cảm (nếu có) -->
    <meta name="robots" content="noarchive">

    <!-- Open Graph Title -->
    <meta property="og:title" content="{{ $post->seo_title }}">

    <!-- Open Graph Description -->
    <meta property="og:description" content="{{ $post->seo_desc }}">

    <!-- Open Graph URL (URL của trang hiện tại) -->
    <meta property="og:url" content="{{ url()->current() }}">

    <!-- Open Graph Image (Hình ảnh khi chia sẻ trên mạng xã hội) -->
    <meta property="og:image" content="{{ asset('/client/assets/images/posts/'. $post->seo_image) }}">

    <!-- Open Graph Type (Loại trang, ví dụ: website, article, product, etc.) -->
    <meta property="og:type" content="article">

    <!-- Open Graph Site Name (Tên website) -->
    <meta property="og:site_name" content="{{ $settings['site_name'] }}">

    <!-- Open Graph Locale (Ngôn ngữ của trang) -->
    <meta property="og:locale" content="vi_VN">

    <!-- Twitter Card Type (summary hoặc summary_large_image) -->
    <meta name="twitter:card" content="{{ asset('/client/assets/images/posts/'. $post->seo_image) }}">

    <!-- Tên trang web -->
    <meta name="twitter:site" content="{{ $settings['site_name'] }}">

    <!-- Twitter Title -->
    <meta name="twitter:title" content="{{ $post->seo_title }}">

    <!-- Twitter Description -->
    <meta name="twitter:description" content="{{ $post->seo_desc }}">

    <!-- Twitter Image (Hình ảnh khi chia sẻ trên Twitter) -->
    <meta name="twitter:image" content="{{ asset('/client/assets/images/posts/'. $post->seo_image) }}">

    <!-- Twitter Creator (Tác giả của trang, nếu có) -->
    <meta name="twitter:creator" content="{{ $settings['site_name'] }}">

    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Thẻ HrefLang (Để hỗ trợ các ngôn ngữ khác nhau) --}}
    <link rel="alternate" hreflang="vi" href="{{ url()->current() }}" />
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}" />

    <!-- Thẻ Meta cho Twitter và Facebook Favicon (tùy chọn) -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="mask-icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" type="image/x-icon">

    {{--  Cấu hình các thẻ cho bảo mật --}}
    <meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "BlogPosting",
            "headline": "{{ $post->seo_title }}",
            "alternativeHeadline": "{{ $post->name }}",
            "image": "{{ asset('/client/assets/images/posts/'. $post->seo_image) }}",
            "author": {
            "@type": "Person",
            "name": "{{ $post->account->profile->name }}",
            "url": "",
            "sameAs": "https://twitter.com/author-profile"
            },
            "editor": {
            "@type": "Person",
            "name": "{{ $post->account->profile->name }}"
            },
            "publisher": {
            "@type": "Organization",
            "name": "{{ $settings['site_name'] }}",
            "logo": {
                "@type": "ImageObject",
                "url": "{{ asset('/client/assets/images/posts/'. $post->seo_image) }}"
            }
            },
            "datePublished": "{{ $post->published_at }}",
            "dateModified": "{{ $post->updated_at }}",
            "mainEntityOfPage": "{{ url()->current() }}",
            "url": "{{ url()->current() }}",
            "description": "{{ $post->seo_desc }}",
            "articleBody": "Nội dung bài viết, có thể là đoạn văn bản tóm tắt hoặc nội dung ngắn gọn",
            "keywords": "{{ $post->seo_keywords }}",
            "genre": "{{ $post->category->name }}",
            "commentCount": "{{ count($post->comments) }}",
            "interactionStatistic": {
            "@type": "InteractionCounter",
            "interactionType": {
                "@type": "ListenAction"
            },
            "userInteractionCount": {{ $post->views }}
            }
        }
    </script>
</head>
<body>
    {{-- Chỗ này để loading --}}
    @include('client.templates.loading')
    <div class="review-haiphong">
        <!-- Chỗ này để header -->
        @include('client.templates.header')
        
        <!-- Chỗ này để breadcrumbs -->
        @include('client.templates.breadcrumbs')
          
        <main class="main">
            {{-- Chỗ này để content --}}
            @include('client.templates.content')
        </main> 

        @include('client.templates.footer')
        
        {{-- <button id="backToTop" onclick="scrollToTop()">Back to Top</button> --}}
    </div>
    @include('client.templates.chat')
    @include('client.module.js')
</body> 
</html>
