<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('client.module.css')
    <link rel="shortcut icon" href="{{ asset('/client/assets/images/logo/' . ($settings['site_favicon'] ?? 'favicon.ico')) }}"
        type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/font-awesome.min.css">
    
    <title>{{ $post->seo_title ?? $post->name ?? 'Bài viết' }} | {{ $settings['site_name'] ?? 'Review Hải Phòng' }}</title>

    <!-- Từ khóa SEO -->
    <meta name="keywords" content="{{ $post->seo_keywords ?? '' }}">

    <!-- Tên tác giả của trang -->
    <meta name="author" content="{{ $settings['seo_author'] ?? 'Review Hải Phòng' }}">

    <!-- Thẻ Robots -->
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

    <meta name="description" content="{{ $post->seo_desc ?? '' }}">

    <!-- Thời gian khi trang được tạo -->
    @if($post->published_at)
        <meta http-equiv="date" content="{{ \Carbon\Carbon::parse($post->published_at)->format('Y-m-d') }}" />
    @endif

    <!-- Thẻ ngôn ngữ trang -->
    <meta name="language" content="{{ $settings['site_language'] ?? 'vi' }}">

    <!-- Thẻ bản quyền -->
    <meta name="copyright" content="{{ $settings['seo_author'] ?? 'Review Hải Phòng' }}">

    <!-- Open Graph Title -->
    <meta property="og:title" content="{{ $post->seo_title ?? $post->name ?? 'Bài viết' }}">

    <!-- Open Graph Description -->
    <meta property="og:description" content="{{ $post->seo_desc ?? '' }}">

    <!-- Open Graph URL -->
    <meta property="og:url" content="{{ url('/' . $post->slug) }}">

    <!-- Open Graph Image -->
    @if($post->seo_image)
        <meta property="og:image" content="{{ asset('/client/assets/images/posts/' . $post->seo_image) }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $post->seo_title ?? $post->name }}" />
        <meta property="og:image:type" content="image/webp" />
    @endif

    <!-- Open Graph Type -->
    <meta property="og:type" content="article">

    <!-- Open Graph Site Name -->
    <meta property="og:site_name" content="{{ $settings['site_name'] ?? 'Review Hải Phòng' }}">

    <!-- Open Graph Locale -->
    <meta property="og:locale" content="vi_VN">
    <meta property="og:locale:alternate" content="en_US">
    
    <!-- Article Specific OG Tags -->
    @if($post->published_at)
    <meta property="article:published_time" content="{{ \Carbon\Carbon::parse($post->published_at)->toIso8601String() }}">
    @endif
    <meta property="article:modified_time" content="{{ \Carbon\Carbon::parse($post->updated_at)->toIso8601String() }}">
    @if($post->category)
    <meta property="article:section" content="{{ $post->category->name }}">
    @endif
    @if($post->tags)
    @php
        $tags = is_string($post->tags) ? explode(',', $post->tags) : [];
    @endphp
    @foreach(array_slice($tags, 0, 5) as $tag)
    <meta property="article:tag" content="{{ trim($tag) }}">
    @endforeach
    @endif
    <meta property="article:author" content="{{ $post->account->profile->name ?? $post->account->username ?? 'Admin' }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ $settings['site_name'] ?? 'Review Hải Phòng' }}">
    <meta name="twitter:title" content="{{ $post->seo_title ?? $post->name ?? 'Bài viết' }}">
    <meta name="twitter:description" content="{{ $post->seo_desc ?? '' }}">
    @if($post->seo_image)
        <meta name="twitter:image" content="{{ asset('/client/assets/images/posts/' . $post->seo_image) }}">
    @endif
    <meta name="twitter:creator" content="{{ $settings['site_name'] ?? 'Review Hải Phòng' }}">

    <!-- Canonical -->
    <link rel="canonical" href="{{ url('/' . $post->slug) }}" />

    <!-- HrefLang -->
    <link rel="alternate" hreflang="vi" href="{{ url('/' . $post->slug) }}" />
    <link rel="alternate" hreflang="x-default" href="{{ url('/' . $post->slug) }}" />
    
    <!-- Additional Meta Tags -->
    <meta name="news_keywords" content="{{ $post->seo_keywords ?? '' }}">
    <meta name="article:publisher" content="{{ $settings['facebook_link'] ?? '' }}">
    <meta name="geo.region" content="VN-HP">
    <meta name="geo.placename" content="Hải Phòng">
    <meta name="ICBM" content="20.8584917, 106.6844285">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180"
        href="{{ asset('/client/assets/images/logo/' . ($settings['site_favicon'] ?? 'favicon.ico')) }}">
    <link rel="icon" type="image/png" sizes="32x32"
        href="{{ asset('/client/assets/images/logo/' . ($settings['site_favicon'] ?? 'favicon.ico')) }}">
    <link rel="icon" type="image/png" sizes="16x16"
        href="{{ asset('/client/assets/images/logo/' . ($settings['site_favicon'] ?? 'favicon.ico')) }}">
    <link rel="mask-icon" href="{{ asset('/client/assets/images/logo/' . ($settings['site_favicon'] ?? 'favicon.ico')) }}" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">

    <!-- Security Headers -->
    <meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">

    <!-- Structured Data - Enhanced Schema -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
        "@graph": [
            {
            "@type": "BlogPosting",
                "@id": "{{ url('/' . $post->slug) . '/#blogposting' }}",
            "mainEntityOfPage": {
                "@type": "WebPage",
                    "@id": "{{ url('/' . $post->slug) . '/#webpage' }}"
            },
                "headline": {!! json_encode($post->seo_title ?? $post->name ?? 'Bài viết', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                "alternativeHeadline": {!! json_encode($post->name ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
            @if($post->seo_image)
                "image": {
                    "@type": "ImageObject",
                    "url": "{{ asset('/client/assets/images/posts/' . $post->seo_image) }}",
                    "width": 1200,
                    "height": 630
                },
            @endif
            "author": {
                "@type": "Person",
                    "name": "{{ $post->account->profile->name ?? $post->account->username ?? 'Admin' }}",
                "url": "{{ url('/') }}"
            },
            "publisher": {
                "@type": "Organization",
                    "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#organization' }}",
                "name": "{{ $settings['site_name'] ?? 'Review Hải Phòng' }}",
                "logo": {
                    "@type": "ImageObject",
                        "url": "{{ asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')) }}",
                        "width": 600,
                        "height": 600
                    }
                },
                "identifier": "{{ url('/' . $post->slug) }}",
                "url": "{{ url('/' . $post->slug) }}",
            @if($post->published_at)
            "datePublished": "{{ \Carbon\Carbon::parse($post->published_at)->toIso8601String() }}",
            @endif
            "dateModified": "{{ \Carbon\Carbon::parse($post->updated_at)->toIso8601String() }}",
                "description": {!! json_encode(\Illuminate\Support\Str::limit(strip_tags($post->seo_desc ?? ''), 300), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                "articleBody": {!! json_encode(\Illuminate\Support\Str::limit(strip_tags($post->content ?? ''), 5000), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
            @if($post->category)
            "articleSection": "{{ $post->category->name }}",
                "about": {
                    "@type": "Thing",
                    "name": "{{ $post->category->name }}"
                },
            @endif
            "keywords": "{{ $post->seo_keywords ?? '' }}",
            @if($post->category)
            "genre": "{{ $post->category->name }}",
            @endif
            "inLanguage": "vi",
                "wordCount": {{ str_word_count(strip_tags($post->content ?? '')) }},
            "commentCount": {{ $post->comments ? $post->comments->count() : 0 }},
            @if($post->comments && $post->comments->count() > 0)
            "comment": [
                    @foreach ($post->comments->take(10) as $comment)
                {
                    "@type": "Comment",
                        "@id": "{{ url('/' . $post->slug) . '#comment-' . $comment->id }}",
                    "author": {
                        "@type": "Person",
                            "name": "{{ $comment->account->profile->name ?? $comment->account->username ?? 'Khách' }}"
                    },
                    "dateCreated": "{{ \Carbon\Carbon::parse($comment->created_at)->toIso8601String() }}",
                        "text": {!! json_encode(\Illuminate\Support\Str::limit(strip_tags($comment->content), 200), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                        "url": "{{ url('/' . $post->slug) . '#comment-' . $comment->id }}"
                    }@if(!$loop->last),@endif
                @endforeach
            ],
            @endif
                "interactionStatistic": [
                    {
                "@type": "InteractionCounter",
                "interactionType": {
                    "@type": "ViewAction"
                },
                "userInteractionCount": {{ $post->views ?? 0 }}
                    }
                ],
                "isPartOf": {
                    "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#website' }}"
                }
            },
            {
                "@type": "WebPage",
                "@id": "{{ url('/' . $post->slug) . '/#webpage' }}",
                "url": "{{ url('/' . $post->slug) }}",
                "name": {!! json_encode($post->seo_title ?? $post->name ?? 'Bài viết', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                "description": {!! json_encode(\Illuminate\Support\Str::limit(strip_tags($post->seo_desc ?? ''), 300), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                "isPartOf": {
                    "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#website' }}"
                },
                "primaryImageOfPage": {
                    "@type": "ImageObject",
                    "url": "{{ $post->seo_image ? asset('/client/assets/images/posts/' . $post->seo_image) : asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')) }}"
                },
                "datePublished": "{{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->toIso8601String() : '' }}",
                "dateModified": "{{ \Carbon\Carbon::parse($post->updated_at)->toIso8601String() }}",
                "inLanguage": "vi",
                "breadcrumb": {
                    "@id": "{{ url('/' . $post->slug) . '/#breadcrumb' }}"
                },
                "mainEntity": {
                    "@id": "{{ url('/' . $post->slug) . '/#blogposting' }}"
                }
            },
            {
                "@type": "BreadcrumbList",
                "@id": "{{ url('/' . $post->slug) . '/#breadcrumb' }}",
                "itemListElement": [
                    {
                        "@type": "ListItem",
                        "position": 1,
                        "name": "{{ $settings['site_name'] ?? 'Review Hải Phòng' }}",
                        "item": "{{ url('/') }}"
                    },
                    @if($post->category)
                    {
                        "@type": "ListItem",
                        "position": 2,
                        "name": "{{ $post->category->name }}",
                        "item": "{{ url('/' . $post->category->slug) }}"
                    },
                    @endif
                    {
                        "@type": "ListItem",
                        "position": {{ $post->category ? 3 : 2 }},
                        "name": {!! json_encode($post->seo_title ?? $post->name ?? 'Bài viết', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                        "item": "{{ url('/' . $post->slug) }}"
                    }
                ]
            }
            @if(isset($relatedPosts) && $relatedPosts->count() > 0)
            ,{
                "@type": "ItemList",
                "name": "Bài viết liên quan",
                "itemListElement": [
                    @foreach($relatedPosts->take(6) as $index => $relatedPost)
                    {
                        "@type": "ListItem",
                        "position": {{ $index + 1 }},
                        "item": {
                            "@type": "Article",
                            "@id": "{{ url('/' . $relatedPost->slug) }}",
                            "name": {!! json_encode($relatedPost->seo_title ?? $relatedPost->name ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                            "headline": {!! json_encode($relatedPost->seo_title ?? $relatedPost->name ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                            "url": "{{ url('/' . $relatedPost->slug) }}",
                            "image": "{{ $relatedPost->seo_image ? asset('/client/assets/images/posts/' . $relatedPost->seo_image) : '' }}"
                        }
                    }@if(!$loop->last),@endif
                    @endforeach
                ]
            }
            @endif
        ]
        }
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            font-size: 15px;
            line-height: 1.8;
            color: #444;
            background: #f5f5f5;
        }

        /* Top Header - Blue Bar */
        .reviewhaiphong_blog_top-header {
            background: #1d9cd7;
            color: #fff;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .reviewhaiphong_blog_header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .reviewhaiphong_blog_logo {
            flex-shrink: 0;
        }

        .reviewhaiphong_blog_logo img {
            height: 35px;
        }

        .reviewhaiphong_blog_menu-toggle {
            background: none;
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .reviewhaiphong_blog_menu-toggle:hover {
            background: rgba(255,255,255,0.1);
        }

        .reviewhaiphong_blog_search-box {
            flex: 1;
            max-width: 500px;
            position: relative;
        }

        .reviewhaiphong_blog_search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .reviewhaiphong_blog_search-box .fa-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .reviewhaiphong_blog_header-actions {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .reviewhaiphong_blog_header-action {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            white-space: nowrap;
        }

        .reviewhaiphong_blog_header-action i {
            font-size: 18px;
        }

        .reviewhaiphong_blog_header-action .reviewhaiphong_blog_number {
            background: #fff;
            color: #1d9cd7;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        /* Main Layout */
        .reviewhaiphong_blog_main-wrapper {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            min-height: calc(100vh - 60px);
        }

        /* Left Sidebar */
        .reviewhaiphong_blog_left-sidebar {
            width: 280px;
            background: #fff;
            border-right: 1px solid #e5e5e5;
            flex-shrink: 0;
            position: sticky;
            top: 60px;
            height: fit-content;
            overflow-y: auto;
        }

        .reviewhaiphong_blog_left-sidebar::webkit-scrollbar {
            display: none;
        }
        .reviewhaiphong_blog_left-sidebar::-webkit-scrollbar {
            display: none!important;
        }
        .reviewhaiphong_blog_left-sidebar {
            -ms-overflow-style: none!important;
        }
        .reviewhaiphong_blog_left-sidebar::-webkit-scrollbar {
            display: none!important;
        }

        /* Sidebar overlay cho mobile */
        .reviewhaiphong_blog_sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .reviewhaiphong_blog_sidebar-overlay.active {
            display: block;
        }

        .reviewhaiphong_blog_sidebar-menu {
            padding: 20px 0;
        }

        .reviewhaiphong_blog_sidebar-widget {
            padding: 20px;
            border-bottom: 1px solid #e5e5e5;
        }

        .reviewhaiphong_blog_sidebar-widget:last-child {
            border-bottom: none;
        }

        .reviewhaiphong_blog_sidebar-widget-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .reviewhaiphong_blog_sidebar-widget-title i {
            color: #1d9cd7;
        }

        .reviewhaiphong_blog_popular-post-item {
            display: flex;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .reviewhaiphong_blog_popular-post-item:last-child {
            border-bottom: none;
        }

        .reviewhaiphong_blog_popular-post-image {
            width: 80px;
            height: 60px;
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
            background: #f0f0f0;
        }

        .reviewhaiphong_blog_popular-post-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .reviewhaiphong_blog_popular-post-info {
            flex: 1;
        }

        .reviewhaiphong_blog_popular-post-title {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            line-height: 1.4;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .reviewhaiphong_blog_popular-post-title a {
            color: #333;
            text-decoration: none;
        }

        .reviewhaiphong_blog_popular-post-title a:hover {
            color: #1d9cd7;
        }

        .reviewhaiphong_blog_popular-post-meta {
            font-size: 11px;
            color: #999;
        }

        .reviewhaiphong_blog_popular-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .reviewhaiphong_blog_tag-item {
            display: inline-block;
            padding: 5px 12px;
            background: #f0f0f0;
            color: #666;
            border-radius: 15px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .reviewhaiphong_blog_tag-item:hover {
            background: #1d9cd7;
            color: #fff;
        }

        .reviewhaiphong_blog_menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 25px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .reviewhaiphong_blog_menu-item:hover {
            background: #f8f8f8;
            color: #1d9cd7;
        }

        .reviewhaiphong_blog_menu-item i {
            font-size: 18px;
            width: 24px;
        }

        .reviewhaiphong_blog_submenu {
            padding-left: 60px;
        }

        .reviewhaiphong_blog_submenu .reviewhaiphong_blog_menu-item {
            padding: 8px 25px;
            font-size: 13px;
            color: #888;
        }

        /* Social Sidebar Icons */
        .reviewhaiphong_blog_social-icons {
            position: fixed;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            z-index: 999;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .reviewhaiphong_blog_social-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .reviewhaiphong_blog_social-icon:hover {
            transform: translateX(5px);
        }

        .reviewhaiphong_blog_social-icon.reviewhaiphong_blog_zalo { background: #00a8ff; }
        .reviewhaiphong_blog_social-icon.reviewhaiphong_blog_messenger { background: #0084ff; }
        .reviewhaiphong_blog_social-icon.reviewhaiphong_blog_chat { background: #9b59b6; }
        .reviewhaiphong_blog_social-icon.reviewhaiphong_blog_phone { background: #27ae60; }
        .reviewhaiphong_blog_social-icon.reviewhaiphong_blog_location { background: #f39c12; }
        .reviewhaiphong_blog_social-icon.reviewhaiphong_blog_facebook { background: #3b5998; }

        /* Content Area */
        .reviewhaiphong_blog_content-area {
            flex: 1;
            padding: 0;
            background: #fff;
        }

        /* Breadcrumb */
        .reviewhaiphong_blog_breadcrumb {
            padding: 15px 30px;
            background: #fff;
            border-bottom: 1px solid #e5e5e5;
            font-size: 13px;
            color: #999;
        }

        .reviewhaiphong_blog_breadcrumb a {
            color: #666;
            text-decoration: none;
        }

        .reviewhaiphong_blog_breadcrumb a:hover {
            color: #1d9cd7;
        }

        /* Featured Image Section */
        .reviewhaiphong_blog_featured-section {
            position: relative;
            background: #000;
            border-radius: 15px;
            margin: 20px 30px;
            overflow: hidden;
        }

        .reviewhaiphong_blog_featured-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .reviewhaiphong_blog_featured-overlay {
            position: absolute;
            left: 40px;
            top: 50%;
            transform: translateY(-50%);
            color: #fff;
            max-width: 500px;
        }

        .reviewhaiphong_blog_featured-overlay h2 {
            font-size: 48px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 10px;
        }

        .reviewhaiphong_blog_featured-overlay h2 span {
            color: #999;
        }

        .reviewhaiphong_blog_featured-overlay p {
            font-size: 24px;
            color: #ccc;
            line-height: 1.4;
        }

        /* Article Header */
        .reviewhaiphong_blog_article-header {
            padding: 30px 30px 20px;
            background: #fff;
        }

        .reviewhaiphong_blog_article-category {
            display: inline-block;
            background: #e74c3c;
            color: #fff;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .reviewhaiphong_blog_article-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .reviewhaiphong_blog_article-date {
            color: #999;
            font-size: 13px;
        }

        .reviewhaiphong_blog_article-date i {
            margin-right: 5px;
        }

        /* Table of Contents (TOC) */
        .reviewhaiphong_blog_toc {
            margin: 20px 0 0 0!important;
            padding: 20px !important;
            background: #f8f9fa !important;
            border-left: 3px solid #1d9cd7 !important;
            border-radius: 4px !important;
        }

        .reviewhaiphong_blog_toc-title {
            font-size: 16px !important;
            font-weight: 700 !important;
            color: #1a1a1a !important;
            margin: 0 0 15px 0 !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .reviewhaiphong_blog_toc-title i {
            color: #1d9cd7 !important;
            font-size: 18px !important;
        }

        .reviewhaiphong_blog_toc-list {
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .reviewhaiphong_blog_toc-item {
            margin: 0 !important;
            padding: 0 !important;
        }

        .reviewhaiphong_blog_toc-link {
            display: block !important;
            padding: 8px 12px !important;
            color: #444 !important;
            text-decoration: none !important;
            border-radius: 4px !important;
            transition: all 0.2s ease !important;
            font-size: 14px !important;
            line-height: 1.5 !important;
            margin: 2px 0 !important;
        }

        .reviewhaiphong_blog_toc-link:hover {
            background: #e9ecef !important;
            color: #1d9cd7 !important;
            padding-left: 16px !important;
        }

        .reviewhaiphong_blog_toc-link.active {
            background: #1d9cd7 !important;
            color: #fff !important;
            font-weight: 600 !important;
        }

        .reviewhaiphong_blog_toc-item.level-2 .reviewhaiphong_blog_toc-link {
            padding-left: 12px !important;
        }

        .reviewhaiphong_blog_toc-item.level-3 .reviewhaiphong_blog_toc-link {
            padding-left: 24px !important;
            font-size: 13px !important;
            color: #666 !important;
        }

        .reviewhaiphong_blog_toc-item.level-3 .reviewhaiphong_blog_toc-link:hover {
            padding-left: 28px !important;
        }

        .reviewhaiphong_blog_toc-item.level-4 .reviewhaiphong_blog_toc-link {
            padding-left: 36px !important;
            font-size: 12px !important;
            color: #777 !important;
        }

        .reviewhaiphong_blog_toc-toggle {
            display: none !important;
        }

        /* TOC Collapse trên mobile */
        @media (max-width: 768px) {
            .reviewhaiphong_blog_toc {
                padding: 15px !important;
            }

            .reviewhaiphong_blog_toc-title {
                font-size: 15px !important;
                margin-bottom: 12px !important;
                cursor: pointer !important;
                user-select: none !important;
            }

            .reviewhaiphong_blog_toc-list {
                max-height: 1000px !important;
                overflow: hidden !important;
                transition: max-height 0.3s ease !important;
            }

            .reviewhaiphong_blog_toc-toggle:not(:checked) + .reviewhaiphong_blog_toc-title + .reviewhaiphong_blog_toc-list {
                max-height: 0 !important;
            }

            .reviewhaiphong_blog_toc-title::after {
                content: '\f078' !important;
                font-family: 'Font Awesome 6 Free' !important;
                font-weight: 900 !important;
                margin-left: auto !important;
                transition: transform 0.3s ease !important;
                color: #666 !important;
            }

            .reviewhaiphong_blog_toc-toggle:checked + .reviewhaiphong_blog_toc-title::after {
                transform: rotate(180deg) !important;
            }
        }

        /* Article Content - Styles chuyên nghiệp cho tất cả thẻ HTML */
        .reviewhaiphong_blog_article-content {
            padding: 0 30px 40px !important;
            font-size: 16px !important;
            line-height: 1.75 !important;
            color: #333 !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
        }

        /* Paragraphs */
        .reviewhaiphong_blog_article-content p {
            margin: 0 0 16px 0 !important;
            padding: 0 !important;
            line-height: 1.75 !important;
            color: #333 !important;
        }

        .reviewhaiphong_blog_article-content p:last-child {
            margin-bottom: 0 !important;
        }

        /* Headings */
        .reviewhaiphong_blog_article-content h1 {
            font-size: 28px !important;
            font-weight: 700 !important;
            color: #1a1a1a !important;
            margin: 24px 0 16px 0 !important;
            padding: 0 !important;
            line-height: 1.3 !important;
        }

        .reviewhaiphong_blog_article-content h2 {
            font-size: 22px !important;
            font-weight: 700 !important;
            color: #1a1a1a !important;
            margin: 20px 0 14px 0 !important;
            padding: 0 !important;
            line-height: 1.35 !important;
        }

        .reviewhaiphong_blog_article-content h3 {
            font-size: 19px !important;
            font-weight: 600 !important;
            color: #222 !important;
            margin: 18px 0 12px 0 !important;
            padding: 0 !important;
            line-height: 1.4 !important;
        }

        .reviewhaiphong_blog_article-content h4 {
            font-size: 17px !important;
            font-weight: 600 !important;
            color: #222 !important;
            margin: 16px 0 10px 0 !important;
            padding: 0 !important;
            line-height: 1.4 !important;
        }

        .reviewhaiphong_blog_article-content h5 {
            font-size: 16px !important;
            font-weight: 600 !important;
            color: #333 !important;
            margin: 14px 0 8px 0 !important;
            padding: 0 !important;
            line-height: 1.45 !important;
        }

        .reviewhaiphong_blog_article-content h6 {
            font-size: 15px !important;
            font-weight: 600 !important;
            color: #333 !important;
            margin: 12px 0 8px 0 !important;
            padding: 0 !important;
            line-height: 1.45 !important;
        }

        /* Text formatting */
        .reviewhaiphong_blog_article-content strong,
        .reviewhaiphong_blog_article-content b {
            font-weight: 600 !important;
            color: #1a1a1a !important;
        }

        .reviewhaiphong_blog_article-content em,
        .reviewhaiphong_blog_article-content i {
            font-style: italic !important;
            color: #333 !important;
        }

        .reviewhaiphong_blog_article-content u {
            text-decoration: underline !important;
            text-decoration-thickness: 1px !important;
        }

        .reviewhaiphong_blog_article-content mark {
            background-color: #fff3cd !important;
            color: #333 !important;
            padding: 2px 4px !important;
        }

        .reviewhaiphong_blog_article-content del {
            text-decoration: line-through !important;
            color: #666 !important;
        }

        /* Links */
        .reviewhaiphong_blog_article-content a {
            color: #1d9cd7 !important;
            text-decoration: none !important;
            border-bottom: 1px solid transparent !important;
            transition: border-color 0.2s ease !important;
        }

        .reviewhaiphong_blog_article-content a:hover {
            color: #1584b8 !important;
            border-bottom-color: #1584b8 !important;
        }

        /* Lists */
        .reviewhaiphong_blog_article-content ul,
        .reviewhaiphong_blog_article-content ol {
            margin: 12px 0 12px 24px !important;
            padding: 0 !important;
            list-style-position: outside !important;
        }

        .reviewhaiphong_blog_article-content ul {
            list-style-type: disc !important;
        }

        .reviewhaiphong_blog_article-content ol {
            list-style-type: decimal !important;
        }

        .reviewhaiphong_blog_article-content li {
            margin: 6px 0 !important;
            padding: 0 !important;
            line-height: 1.75 !important;
            color: #333 !important;
        }

        .reviewhaiphong_blog_article-content ul ul,
        .reviewhaiphong_blog_article-content ol ol,
        .reviewhaiphong_blog_article-content ul ol,
        .reviewhaiphong_blog_article-content ol ul {
            margin: 6px 0 6px 20px !important;
        }

        .reviewhaiphong_blog_article-content ul ul {
            list-style-type: circle !important;
        }

        .reviewhaiphong_blog_article-content ul ul ul {
            list-style-type: square !important;
        }

        /* Images */
        .reviewhaiphong_blog_article-content img {
            max-width: 100% !important;
            height: auto !important;
            border-radius: 4px !important;
            margin: 16px auto !important;
            padding: 0 !important;
            display: block !important;
        }

        .reviewhaiphong_blog_article-content figure {
            margin: 16px 0 !important;
            padding: 0 !important;
        }

        .reviewhaiphong_blog_article-content figure img {
            margin: 0 !important;
        }

        .reviewhaiphong_blog_article-content figcaption {
            font-size: 14px !important;
            color: #666 !important;
            text-align: center !important;
            margin-top: 8px !important;
            padding: 0 !important;
            font-style: italic !important;
        }

        /* Blockquote */
        .reviewhaiphong_blog_article-content blockquote {
            margin: 16px 0 !important;
            padding: 12px 16px !important;
            border-left: 3px solid #1d9cd7 !important;
            background-color: #f8f9fa !important;
            color: #555 !important;
            font-style: normal !important;
            line-height: 1.7 !important;
        }

        .reviewhaiphong_blog_article-content blockquote p {
            margin: 0 0 8px 0 !important;
        }

        .reviewhaiphong_blog_article-content blockquote p:last-child {
            margin-bottom: 0 !important;
        }

        .reviewhaiphong_blog_article-content blockquote cite {
            display: block !important;
            font-size: 14px !important;
            color: #666 !important;
            margin-top: 8px !important;
            font-style: italic !important;
        }

        /* Code */
        .reviewhaiphong_blog_article-content code {
            background-color: #f4f4f4 !important;
            color: #d63384 !important;
            padding: 2px 6px !important;
            border-radius: 3px !important;
            font-size: 14px !important;
            font-family: 'Courier New', Courier, monospace !important;
        }

        .reviewhaiphong_blog_article-content pre {
            background-color: #f8f9fa !important;
            border: 1px solid #e5e5e5 !important;
            border-radius: 4px !important;
            padding: 12px 16px !important;
            margin: 16px 0 !important;
            overflow-x: auto !important;
            line-height: 1.6 !important;
        }

        .reviewhaiphong_blog_article-content pre code {
            background-color: transparent !important;
            color: #333 !important;
            padding: 0 !important;
            border-radius: 0 !important;
        }

        /* Tables */
        .reviewhaiphong_blog_article-content table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 16px 0 !important;
            padding: 0 !important;
            background: #fff !important;
            border: 1px solid #e0e0e0 !important;
            font-size: 15px !important;
        }

        .reviewhaiphong_blog_article-content thead {
            background-color: #f8f9fa !important;
        }

        .reviewhaiphong_blog_article-content tbody {
            background-color: #fff !important;
        }

        .reviewhaiphong_blog_article-content th,
        .reviewhaiphong_blog_article-content td {
            padding: 10px 12px !important;
            text-align: left !important;
            border-bottom: 1px solid #e5e5e5 !important;
            vertical-align: top !important;
        }

        .reviewhaiphong_blog_article-content th {
            font-weight: 600 !important;
            color: #1a1a1a !important;
            background-color: #f8f9fa !important;
        }

        .reviewhaiphong_blog_article-content td {
            color: #333 !important;
        }

        .reviewhaiphong_blog_article-content tr:last-child td {
            border-bottom: none !important;
        }

        .reviewhaiphong_blog_article-content tbody tr:hover {
            background-color: #f8f9fa !important;
        }

        /* Horizontal Rule */
        .reviewhaiphong_blog_article-content hr {
            border: none !important;
            border-top: 1px solid #e5e5e5 !important;
            margin: 20px 0 !important;
            padding: 0 !important;
            height: 0 !important;
        }

        /* Div và Span */
        .reviewhaiphong_blog_article-content div {
            margin: 0 !important;
            padding: 0 !important;
        }

        .reviewhaiphong_blog_article-content span {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Address */
        .reviewhaiphong_blog_article-content address {
            font-style: normal !important;
            margin: 12px 0 !important;
            padding: 0 !important;
            line-height: 1.75 !important;
        }

        /* Small text */
        .reviewhaiphong_blog_article-content small {
            font-size: 14px !important;
            color: #666 !important;
        }

        /* Sub và Sup */
        .reviewhaiphong_blog_article-content sub,
        .reviewhaiphong_blog_article-content sup {
            font-size: 12px !important;
            line-height: 0 !important;
            position: relative !important;
            vertical-align: baseline !important;
        }

        .reviewhaiphong_blog_article-content sup {
            top: -0.5em !important;
        }

        .reviewhaiphong_blog_article-content sub {
            bottom: -0.25em !important;
        }

        /* Definition lists */
        .reviewhaiphong_blog_article-content dl {
            margin: 12px 0 !important;
            padding: 0 !important;
        }

        .reviewhaiphong_blog_article-content dt {
            font-weight: 600 !important;
            color: #1a1a1a !important;
            margin: 8px 0 4px 0 !important;
            padding: 0 !important;
        }

        .reviewhaiphong_blog_article-content dd {
            margin: 0 0 8px 20px !important;
            padding: 0 !important;
            color: #333 !important;
        }

        /* Abbreviation */
        .reviewhaiphong_blog_article-content abbr {
            text-decoration: underline dotted !important;
            cursor: help !important;
        }

        /* Time */
        .reviewhaiphong_blog_article-content time {
            color: #666 !important;
        }

        /* Table - Styles chung (nếu có table ngoài article content) */
        table:not(.reviewhaiphong_blog_article-content table) {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
            background: #fff;
            border: 1px solid #e0e0e0;
        }

        table:not(.reviewhaiphong_blog_article-content table) th,
        table:not(.reviewhaiphong_blog_article-content table) td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
        }

        table:not(.reviewhaiphong_blog_article-content table) th {
            background: #f8f9fa;
            font-weight: 600;
            color: #1a1a1a;
        }

        table:not(.reviewhaiphong_blog_article-content table) td {
            color: #333;
        }

        table:not(.reviewhaiphong_blog_article-content table) tr:last-child td {
            border-bottom: none;
        }

        /* Comments Section */
        .reviewhaiphong_blog_comments-section {
            padding: 30px;
            background: #fff;
            border-top: 1px solid #e5e5e5;
        }

        .reviewhaiphong_blog_comments-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reviewhaiphong_blog_comments-title i {
            color: #1d9cd7;
        }

        .reviewhaiphong_blog_comment-item {
            padding: 20px 0;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            gap: 15px;
        }

        .reviewhaiphong_blog_comment-item:last-child {
            border-bottom: none;
        }

        .reviewhaiphong_blog_comment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .reviewhaiphong_blog_comment-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .reviewhaiphong_blog_comment-content {
            flex: 1;
        }

        .reviewhaiphong_blog_comment-author {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .reviewhaiphong_blog_comment-date {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }

        .reviewhaiphong_blog_comment-text {
            color: #666;
            line-height: 1.6;
        }

        .reviewhaiphong_blog_comment-form {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e5e5e5;
        }

        .reviewhaiphong_blog_comment-form label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .reviewhaiphong_blog_comment-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        .reviewhaiphong_blog_comment-form button {
            margin-top: 10px;
            padding: 10px 20px;
            background: #1d9cd7;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .reviewhaiphong_blog_comment-form button:hover {
            background: #1584b8;
        }

        .reviewhaiphong_blog_alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .reviewhaiphong_blog_alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .reviewhaiphong_blog_alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .reviewhaiphong_blog_alert-danger ul {
            margin: 0;
            padding-left: 20px;
        }

        /* Chat Bubble */
        .reviewhaiphong_blog_chat-bubble {
            position: fixed;
            bottom: 100px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: #00bfa5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 998;
        }

        .reviewhaiphong_blog_chat-bubble:hover {
            transform: scale(1.1);
        }

        /* Support Button */
        .reviewhaiphong_blog_support-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #00c853;
            color: #fff;
            padding: 12px 25px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 999;
        }

        .reviewhaiphong_blog_support-button i {
            font-size: 18px;
        }

        .reviewhaiphong_blog_support-button:hover {
            background: #00b248;
        }

        /* Right Sidebar Icons */
        .reviewhaiphong_blog_right-icons {
            position: fixed;
            right: 20px;
            bottom: 200px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 997;
        }

        .reviewhaiphong_blog_right-icon {
            width: 45px;
            height: 45px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .reviewhaiphong_blog_right-icon:hover {
            background: #f8f8f8;
            transform: translateY(-2px);
        }

        /* Responsive - Tablet và nhỏ hơn */
        @media (max-width: 1024px) {
            .reviewhaiphong_blog_header-container {
                padding: 0 15px;
                flex-wrap: wrap;
            }

            .reviewhaiphong_blog_search-box {
                order: 3;
                width: 100%;
                max-width: 100%;
                margin-top: 10px;
            }

            .reviewhaiphong_blog_left-sidebar {
                position: fixed;
                left: -280px;
                transition: left 0.3s ease;
                z-index: 1001;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
                background: #fff;
                height: 100vh;
                top: 0;
            }

            .reviewhaiphong_blog_left-sidebar.reviewhaiphong_blog_active {
                left: 0;
            }

            .reviewhaiphong_blog_sidebar-overlay {
                display: block;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }

            .reviewhaiphong_blog_sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .reviewhaiphong_blog_main-wrapper {
                padding-left: 0;
            }

            .reviewhaiphong_blog_social-icon {
                width: 45px;
                height: 45px;
                font-size: 20px;
                border-radius: 50%;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }

            .reviewhaiphong_blog_featured-section {
                margin: 15px 20px;
                border-radius: 10px;
            }

            .reviewhaiphong_blog_featured-overlay {
                left: 30px;
                max-width: 400px;
            }

            .reviewhaiphong_blog_featured-overlay h2 {
                font-size: 32px;
            }

            .reviewhaiphong_blog_featured-overlay p {
                font-size: 18px;
            }

            .reviewhaiphong_blog_article-header {
                padding: 25px 25px 15px;
            }

            .reviewhaiphong_blog_article-title {
                font-size: 24px;
            }

            .reviewhaiphong_blog_article-content {
                padding: 0 25px 35px !important;
                font-size: 15px !important;
            }

            .reviewhaiphong_blog_article-content h2 {
                font-size: 20px !important;
                margin: 18px 0 12px 0 !important;
            }

            .reviewhaiphong_blog_article-content h3 {
                font-size: 18px !important;
                margin: 16px 0 10px 0 !important;
            }

            .reviewhaiphong_blog_article-content p {
                margin: 0 0 14px 0 !important;
            }

            .reviewhaiphong_blog_article-content img {
                margin: 14px auto !important;
            }

            .reviewhaiphong_blog_article-content table {
                font-size: 14px !important;
                margin: 14px 0 !important;
            }

            .reviewhaiphong_blog_article-content th,
            .reviewhaiphong_blog_article-content td {
                padding: 8px 10px !important;
            }

            .reviewhaiphong_blog_comments-section {
                padding: 25px;
            }

            /* Related posts grid */
            .reviewhaiphong_blog_comments-section[style*="grid-template-columns"] {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
                gap: 15px !important;
            }
        }

        /* Responsive - Mobile lớn và Tablet nhỏ */
        @media (max-width: 768px) {
            .reviewhaiphong_blog_top-header {
                padding: 10px 0;
            }

            .reviewhaiphong_blog_header-container {
                padding: 0 10px;
                gap: 10px;
            }

            .reviewhaiphong_blog_logo img {
                height: 30px;
            }

            .reviewhaiphong_blog_menu-toggle {
                padding: 6px 12px;
                font-size: 13px;
            }

            .reviewhaiphong_blog_menu-toggle span {
                display: none;
            }

            .reviewhaiphong_blog_header-actions {
                gap: 8px;
            }

            .reviewhaiphong_blog_header-action {
                font-size: 12px;
                gap: 5px;
            }

            .reviewhaiphong_blog_header-action i {
                font-size: 16px;
            }

            .reviewhaiphong_blog_header-action > div {
                display: none;
            }

            .reviewhaiphong_blog_search-box {
                margin-top: 8px;
            }

            .reviewhaiphong_blog_search-box input {
                padding: 8px 35px 8px 12px;
                font-size: 13px;
            }

            .reviewhaiphong_blog_breadcrumb {
                padding: 12px 15px;
                font-size: 12px;
            }

            .reviewhaiphong_blog_featured-section {
                margin: 10px 15px;
                border-radius: 8px;
            }

            .reviewhaiphong_blog_featured-overlay {
                left: 15px;
                right: 15px;
                max-width: 100%;
                top: auto;
                bottom: 20px;
                transform: none;
            }

            .reviewhaiphong_blog_featured-overlay h2 {
                font-size: 20px;
                margin-bottom: 8px;
            }

            .reviewhaiphong_blog_featured-overlay p {
                font-size: 13px;
            }

            .reviewhaiphong_blog_article-header {
                padding: 20px 15px 15px;
            }

            .reviewhaiphong_blog_article-category {
                padding: 5px 15px;
                font-size: 12px;
                margin-bottom: 15px;
            }

            .reviewhaiphong_blog_article-title {
                line-height: 1.3;
                margin-bottom: 8px;
            }

            .reviewhaiphong_blog_article-date {
                font-size: 12px;
            }

            .reviewhaiphong_blog_article-content {
                padding: 0 15px 25px !important;
                font-size: 15px !important;
                line-height: 1.7 !important;
            }

            .reviewhaiphong_blog_article-content h1 {
                font-size: 24px !important;
                margin: 20px 0 14px 0 !important;
            }

            .reviewhaiphong_blog_article-content h2 {
                font-size: 20px !important;
                margin: 18px 0 12px 0 !important;
            }

            .reviewhaiphong_blog_article-content h3 {
                font-size: 18px !important;
                margin: 16px 0 10px 0 !important;
            }

            .reviewhaiphong_blog_article-content h4 {
                font-size: 16px !important;
                margin: 14px 0 8px 0 !important;
            }

            .reviewhaiphong_blog_article-content p {
                margin: 0 0 14px 0 !important;
            }

            .reviewhaiphong_blog_article-content ul,
            .reviewhaiphong_blog_article-content ol {
                margin: 10px 0 10px 20px !important;
            }

            .reviewhaiphong_blog_article-content li {
                margin: 5px 0 !important;
            }

            .reviewhaiphong_blog_article-content img {
                margin: 14px auto !important;
                border-radius: 4px !important;
            }

            .reviewhaiphong_blog_article-content blockquote {
                margin: 14px 0 !important;
                padding: 10px 14px !important;
            }

            .reviewhaiphong_blog_article-content table {
                font-size: 13px !important;
                margin: 14px 0 !important;
            }

            .reviewhaiphong_blog_article-content th,
            .reviewhaiphong_blog_article-content td {
                padding: 8px 10px !important;
            }

            .reviewhaiphong_blog_article-content pre {
                padding: 10px 12px !important;
                margin: 14px 0 !important;
                font-size: 13px !important;
            }

            .reviewhaiphong_blog_article-content code {
                font-size: 13px !important;
                padding: 2px 5px !important;
            }

            .reviewhaiphong_blog_comments-section {
                padding: 20px 15px;
            }

            .reviewhaiphong_blog_comments-title {
                font-size: 20px;
                margin-bottom: 15px;
            }

            .reviewhaiphong_blog_comment-item {
                padding: 15px 0;
                gap: 12px;
            }

            .reviewhaiphong_blog_comment-avatar {
                width: 40px;
                height: 40px;
            }

            .reviewhaiphong_blog_comment-author {
                font-size: 14px;
            }

            .reviewhaiphong_blog_comment-date {
                font-size: 11px;
            }

            .reviewhaiphong_blog_comment-text {
                font-size: 14px;
            }

            .reviewhaiphong_blog_comment-form textarea {
                font-size: 14px;
                min-height: 80px;
            }

            .reviewhaiphong_blog_comment-form button {
                width: 100%;
                padding: 12px;
            }

            /* Sidebar widgets trên mobile */
            .reviewhaiphong_blog_sidebar-widget {
                padding: 15px;
            }

            .reviewhaiphong_blog_sidebar-widget-title {
                font-size: 14px;
                margin-bottom: 12px;
            }

            .reviewhaiphong_blog_popular-post-item {
                padding: 8px 0;
            }

            .reviewhaiphong_blog_popular-post-image {
                width: 70px;
                height: 50px;
            }

            .reviewhaiphong_blog_popular-post-title {
                font-size: 12px;
            }

            .reviewhaiphong_blog_popular-post-meta {
                font-size: 10px;
            }

            .reviewhaiphong_blog_tag-item {
                padding: 4px 10px;
                font-size: 11px;
            }

            /* Related posts grid - mobile */
            .reviewhaiphong_blog_comments-section[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }

            /* Hiển thị các nút trên mobile với kích thước nhỏ hơn */
            .reviewhaiphong_blog_chat-bubble {
                bottom: 80px;
                right: 15px;
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .reviewhaiphong_blog_support-button {
                bottom: 15px;
                right: 15px;
                padding: 10px 16px;
                font-size: 12px;
            }

            .reviewhaiphong_blog_support-button span {
                display: none;
            }

            .reviewhaiphong_blog_right-icons {
                bottom: 140px;
                right: 15px;
                gap: 10px;
            }

            .reviewhaiphong_blog_right-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
        }

        /* Responsive - Mobile nhỏ */
        @media (max-width: 480px) {
            .reviewhaiphong_blog_header-container {
                padding: 0 8px;
            }

            .reviewhaiphong_blog_logo img {
                height: 28px;
            }

            .reviewhaiphong_blog_menu-toggle {
                padding: 5px 10px;
            }

            .reviewhaiphong_blog_search-box {
                margin-top: 5px;
            }

            .reviewhaiphong_blog_search-box input {
                padding: 7px 30px 7px 10px;
                font-size: 12px;
            }

            .reviewhaiphong_blog_breadcrumb {
                padding: 10px 12px;
                font-size: 11px;
            }

            .reviewhaiphong_blog_featured-section {
                margin: 8px 10px;
            }

            .reviewhaiphong_blog_featured-overlay {
                left: 10px;
                right: 10px;
                bottom: 15px;
            }

            .reviewhaiphong_blog_featured-overlay h2 {
                font-size: 18px;
            }

            .reviewhaiphong_blog_featured-overlay p {
                font-size: 12px;
            }

            .reviewhaiphong_blog_article-header {
                padding: 15px 12px 12px;
            }

            .reviewhaiphong_blog_article-date {
                font-size: 11px;
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }

            .reviewhaiphong_blog_article-date span {
                display: none;
            }

            .reviewhaiphong_blog_article-content {
                padding: 0 12px 20px !important;
                font-size: 14px !important;
                line-height: 1.7 !important;
            }

            .reviewhaiphong_blog_article-content h1 {
                font-size: 22px !important;
                margin: 18px 0 12px 0 !important;
            }

            .reviewhaiphong_blog_article-content h2 {
                font-size: 18px !important;
                margin: 16px 0 10px 0 !important;
            }

            .reviewhaiphong_blog_article-content h3 {
                font-size: 17px !important;
                margin: 14px 0 8px 0 !important;
            }

            .reviewhaiphong_blog_article-content h4 {
                font-size: 15px !important;
                margin: 12px 0 8px 0 !important;
            }

            .reviewhaiphong_blog_article-content p {
                margin: 0 0 12px 0 !important;
            }

            .reviewhaiphong_blog_article-content ul,
            .reviewhaiphong_blog_article-content ol {
                margin: 10px 0 10px 18px !important;
            }

            .reviewhaiphong_blog_article-content li {
                margin: 4px 0 !important;
            }

            .reviewhaiphong_blog_article-content img {
                margin: 12px auto !important;
            }

            .reviewhaiphong_blog_article-content blockquote {
                margin: 12px 0 !important;
                padding: 8px 12px !important;
                font-size: 14px !important;
            }

            .reviewhaiphong_blog_article-content table {
                font-size: 12px !important;
                display: block !important;
                overflow-x: auto !important;
                margin: 12px 0 !important;
            }

            .reviewhaiphong_blog_article-content th,
            .reviewhaiphong_blog_article-content td {
                padding: 6px 8px !important;
                min-width: 100px !important;
            }

            .reviewhaiphong_blog_article-content pre {
                padding: 8px 10px !important;
                margin: 12px 0 !important;
                font-size: 12px !important;
            }

            .reviewhaiphong_blog_article-content code {
                font-size: 12px !important;
                padding: 2px 4px !important;
            }

            .reviewhaiphong_blog_comments-section {
                padding: 15px 12px;
            }

            .reviewhaiphong_blog_comments-title {
                font-size: 18px;
            }

            .reviewhaiphong_blog_comment-item {
                flex-direction: column;
                gap: 10px;
            }

            .reviewhaiphong_blog_comment-avatar {
                width: 35px;
                height: 35px;
            }

            .reviewhaiphong_blog_chat-bubble {
                bottom: 70px;
                right: 10px;
                width: 45px;
                height: 45px;
                font-size: 18px;
            }

            .reviewhaiphong_blog_support-button {
                bottom: 10px;
                right: 10px;
                padding: 8px 12px;
                font-size: 11px;
            }

            .reviewhaiphong_blog_right-icons {
                bottom: 125px;
                right: 10px;
                gap: 8px;
            }

            .reviewhaiphong_blog_right-icon {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }

            /* Social icons trên mobile nhỏ */
            .reviewhaiphong_blog_social-icons {
                left: 10px;
                top: 50%;
                transform: translateY(-50%);
                gap: 8px;
            }

            .reviewhaiphong_blog_social-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            /* Sidebar widgets trên mobile nhỏ */
            .reviewhaiphong_blog_sidebar-widget {
                padding: 12px;
            }

            .reviewhaiphong_blog_popular-post-item {
                padding: 6px 0;
            }

            .reviewhaiphong_blog_popular-post-image {
                width: 60px;
                height: 45px;
            }
        }

        /* Responsive - Desktop lớn */
        @media (min-width: 1400px) {
            .reviewhaiphong_blog_header-container,
            .reviewhaiphong_blog_main-wrapper {
                max-width: 1200px;
            }

            .reviewhaiphong_blog_article-content {
                font-size: 17px;
            }

            .reviewhaiphong_blog_article-title {
                font-size: 32px;
            }
        }

        /* Responsive - Landscape mobile */
        @media (max-width: 768px) and (orientation: landscape) {
            .reviewhaiphong_blog_top-header {
                padding: 8px 0;
            }

            .reviewhaiphong_blog_featured-overlay {
                bottom: 15px;
            }

            .reviewhaiphong_blog_featured-overlay h2 {
                font-size: 18px;
            }

            .reviewhaiphong_blog_featured-overlay p {
                font-size: 12px;
            }
        }

        /* Tối ưu cho touch devices */
        @media (hover: none) and (pointer: coarse) {
            .reviewhaiphong_blog_menu-item,
            .reviewhaiphong_blog_header-action,
            .reviewhaiphong_blog_comment-form button,
            .reviewhaiphong_blog_support-button {
                min-height: 44px;
                min-width: 44px;
            }

            .reviewhaiphong_blog_search-box input {
                min-height: 44px;
            }
        }

        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Tối ưu performance cho mobile */
        @media (max-width: 768px) {
            .reviewhaiphong_blog_article-content img {
                max-width: 100%;
                height: auto;
            }

            /* Tối ưu table scroll trên mobile */
            table {
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Tối ưu font rendering */
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        /* Tối ưu touch targets */
        @media (max-width: 768px) {
            a, button, input, textarea, select {
                -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
            }
        }

        /* Tối ưu sidebar scroll */
        .reviewhaiphong_blog_left-sidebar {
            -webkit-overflow-scrolling: touch;
        }

        /* Tối ưu images loading */
        .reviewhaiphong_blog_featured-image,
        .reviewhaiphong_blog_article-content img {
            loading: lazy;
        }
    </style>
</head>

<body>
    @if(isset($settings['google_tag_body']))
        {!! $settings['google_tag_body'] !!}
    @endif
    
    <!-- Header chung -->
        @include('client.templates.header')

    <!-- Social Icons Sidebar (Left) -->
    <div class="reviewhaiphong_blog_social-icons">
        @if($settings['contact_zalo'] ?? false)
        <a href="https://zalo.me/{{ $settings['contact_zalo'] }}" class="reviewhaiphong_blog_social-icon reviewhaiphong_blog_zalo" target="_blank">
            <i class="fa fa-comment"></i>
        </a>
        @endif
        @if($settings['facebook_link'] ?? false)
        <a href="{{ $settings['facebook_link'] }}" class="reviewhaiphong_blog_social-icon reviewhaiphong_blog_facebook" target="_blank">
            <i class="fa-brands fa-facebook"></i>
        </a>
        @endif
        @if($settings['contact_phone'] ?? false)
        <a href="tel:{{ $settings['contact_phone'] }}" class="reviewhaiphong_blog_social-icon reviewhaiphong_blog_phone">
            <i class="fa fa-phone"></i>
        </a>
        @endif
        @if($settings['contact_address'] ?? false)
        <a href="/lien-he" class="reviewhaiphong_blog_social-icon reviewhaiphong_blog_location">
            <i class="fa fa-map-marker-alt"></i>
        </a>
        @endif
    </div>

    <!-- Sidebar Overlay -->
    <div class="reviewhaiphong_blog_sidebar-overlay" id="reviewhaiphong_blog_sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Layout -->
    <div class="reviewhaiphong_blog_main-wrapper">
        <!-- Left Sidebar Menu -->
        <aside class="reviewhaiphong_blog_left-sidebar" id="reviewhaiphong_blog_leftSidebar">
            <nav class="reviewhaiphong_blog_sidebar-menu">
                <a href="{{ url('/') }}" class="reviewhaiphong_blog_menu-item">
                    <i class="fa fa-home"></i>
                    <span>Trang chủ</span>
                </a>

                @if(isset($categories) && count($categories) > 0)
                    @foreach($categories as $category)
                        <a href="/{{ $category->slug }}" class="reviewhaiphong_blog_menu-item">
                            <i class="fa fa-folder"></i>
                            <span>{{ $category->name }}</span>
                        </a>
                        @if(isset($category->children) && $category->children->count() > 0)
                            <div class="reviewhaiphong_blog_submenu">
                                @foreach($category->children as $child)
                                    <a href="/{{ $child->slug }}" class="reviewhaiphong_blog_menu-item">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                @endif

                <a href="/lien-he" class="reviewhaiphong_blog_menu-item">
                    <i class="fa fa-envelope"></i>
                    <span>Liên hệ</span>
                </a>
            </nav>

            <!-- Popular Posts Widget -->
            @if(isset($popularPosts) && count($popularPosts) > 0)
            <div class="reviewhaiphong_blog_sidebar-widget">
                <h4 class="reviewhaiphong_blog_sidebar-widget-title">
                    <i class="fa fa-fire"></i>
                    <span>Bài viết phổ biến</span>
                </h4>
                <div class="reviewhaiphong_blog_popular-posts">
                    @foreach($popularPosts as $popular_post)
                        <div class="reviewhaiphong_blog_popular-post-item">
                            <div class="reviewhaiphong_blog_popular-post-image">
                                @if(!empty($popular_post->seo_image))
                                    <img src="{{ asset('/client/assets/images/posts/' . $popular_post->seo_image) }}" alt="{{ $popular_post->seo_title ?? $popular_post->name }}">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #999;">
                                        <i class="fa fa-image"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="reviewhaiphong_blog_popular-post-info">
                                <h5 class="reviewhaiphong_blog_popular-post-title">
                                    <a href="/{{ $popular_post->slug }}">{{ $popular_post->seo_title ?? $popular_post->name }}</a>
                                </h5>
                                <div class="reviewhaiphong_blog_popular-post-meta">
                                    <i class="fa fa-eye"></i> {{ $popular_post->views ?? 0 }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Popular Tags Widget -->
            @if(isset($popularTags) && count($popularTags) > 0)
            <div class="reviewhaiphong_blog_sidebar-widget">
                <h4 class="reviewhaiphong_blog_sidebar-widget-title">
                    <i class="fa fa-tags"></i>
                    <span>Tags phổ biến</span>
                </h4>
                <div class="reviewhaiphong_blog_popular-tags">
                    @foreach($popularTags as $tag)
                        <a href="/tim-kiem/{{ $tag->slug }}" class="reviewhaiphong_blog_tag-item">
                            #{{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </aside>

        <!-- Content Area -->
        <main class="reviewhaiphong_blog_content-area">
            <!-- Breadcrumb -->
            <div class="reviewhaiphong_blog_breadcrumb">
                <i class="fa fa-home"></i>
                <a href="{{ url('/') }}">Trang chủ</a>
                @if(isset($post->category) && $post->category)
                    <span> >> </span>
                    <a href="/{{ $post->category->slug }}">{{ $post->category->name }}</a>
                @endif
                <span> >> </span>
                <span>{{ $post->seo_title ?? $post->name ?? 'Bài viết' }}</span>
            </div>

            <!-- Featured Image Banner -->
            @if(isset($post->seo_image) && $post->seo_image)
            <div class="reviewhaiphong_blog_featured-section">
                <img src="{{ asset('/client/assets/images/posts/' . $post->seo_image) }}" alt="{{ $post->seo_title ?? $post->name ?? 'Hình ảnh bài viết' }}" class="reviewhaiphong_blog_featured-image">
                {{-- <div class="reviewhaiphong_blog_featured-overlay">
                    <h2>{{ \Illuminate\Support\Str::limit($post->seo_title ?? $post->name ?? '', 50) }}</h2>
                    @if($post->seo_desc)
                    <p>{{ \Illuminate\Support\Str::limit($post->seo_desc, 100) }}</p>
                    @endif
                </div> --}}
            </div>
            @endif

            <!-- Article Content -->
            <article>
                <div class="reviewhaiphong_blog_article-header">
                    @if(isset($post->category) && $post->category)
                    <a href="/{{ $post->category->slug }}" class="reviewhaiphong_blog_article-category">{{ $post->category->name }}</a>
                    @endif
                    <h1 class="reviewhaiphong_blog_article-title">{{ $post->seo_title ?? $post->name ?? 'Tiêu đề bài viết' }}</h1>
                    <div class="reviewhaiphong_blog_article-date">
                        <i class="fa fa-calendar"></i> {{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') : 'N/A' }}
                        <span style="margin: 0 10px;">|</span>
                        <i class="fa fa-eye"></i> {{ $post->views ?? 0 }} lượt xem
                        <span style="margin: 0 10px;">|</span>
                        <i class="fa fa-comment"></i> {{ $post->comments ? $post->comments->count() : 0 }} bình luận
                    </div>

                    <!-- Table of Contents -->
                    <nav class="reviewhaiphong_blog_toc" id="reviewhaiphong_blog_toc">
                        <input type="checkbox" class="reviewhaiphong_blog_toc-toggle" id="toc-toggle" checked>
                        <label for="toc-toggle" class="reviewhaiphong_blog_toc-title">
                            <i class="fa fa-list"></i>
                            <span>Mục lục</span>
                        </label>
                        <ul class="reviewhaiphong_blog_toc-list" id="toc-list">
                            <!-- TOC sẽ được tạo tự động bằng JavaScript -->
                        </ul>
                    </nav>
                </div>

                <div class="reviewhaiphong_blog_article-content" id="article-content">
                    {!! $post->content ?? 'Nội dung bài viết' !!}
                </div>
            </article>

            <!-- Related Posts Section -->
            @if(isset($categoryPosts) && count($categoryPosts) > 0)
            <div class="reviewhaiphong_blog_comments-section" style="border-top: 1px solid #e5e5e5; margin-top: 0;">
                <h3 class="reviewhaiphong_blog_comments-title">
                    <i class="fa fa-list"></i>
                    <span>Bài viết liên quan</span>
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    @foreach($categoryPosts as $relatedPost)
                        <div style="border: 1px solid #e5e5e5; border-radius: 8px; overflow: hidden; transition: transform 0.3s;">
                            <a href="/{{ $relatedPost->slug }}" style="text-decoration: none; color: inherit;">
                                @if(!empty($relatedPost->seo_image))
                                <div style="width: 100%; height: 150px; overflow: hidden; background: #f0f0f0;">
                                    <img src="{{ asset('/client/assets/images/posts/' . $relatedPost->seo_image) }}" 
                                         alt="{{ $relatedPost->seo_title ?? $relatedPost->name }}" 
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                @endif
                                <div style="padding: 15px;">
                                    <h4 style="font-size: 14px; font-weight: 600; color: #333; margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $relatedPost->seo_title ?? $relatedPost->name }}
                                    </h4>
                                    <div style="font-size: 11px; color: #999; display: flex; align-items: center; gap: 10px;">
                                        <span><i class="fa fa-calendar"></i> {{ $relatedPost->published_at ? \Carbon\Carbon::parse($relatedPost->published_at)->format('d/m/Y') : 'N/A' }}</span>
                                        <span><i class="fa fa-eye"></i> {{ $relatedPost->views ?? 0 }}</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Comments Section -->
            <div class="reviewhaiphong_blog_comments-section" id="comments">
                <h3 class="reviewhaiphong_blog_comments-title">
                    <i class="fa fa-comments"></i>
                    <span>Bình luận ({{ $post->comments ? $post->comments->count() : 0 }})</span>
                </h3>

                @if(session('success'))
                    <div class="reviewhaiphong_blog_alert reviewhaiphong_blog_alert-success" id="comment-success-alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="reviewhaiphong_blog_alert reviewhaiphong_blog_alert-danger" id="comment-error-alert">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(isset($post->comments) && $post->comments->count() > 0)
                    <div id="comments-list">
                        @foreach($post->comments as $comment)
                            <div id="comment-{{ $comment->id }}" class="reviewhaiphong_blog_comment-item">
                                <div class="reviewhaiphong_blog_comment-avatar">
                                    @if($comment->account && $comment->account->profile && $comment->account->profile->avatar)
                                        <img src="{{ asset('storage/' . $comment->account->profile->avatar) }}" alt="{{ $comment->account->profile->name }}">
                                    @else
                                        <i class="fa fa-user"></i>
                                    @endif
                                </div>
                                <div class="reviewhaiphong_blog_comment-content">
                                    <div class="reviewhaiphong_blog_comment-author">{{ $comment->account->profile->name ?? 'Khách' }}</div>
                                    <div class="reviewhaiphong_blog_comment-date">{{ $comment->created_at ? \Carbon\Carbon::parse($comment->created_at)->diffForHumans() : 'N/A' }}</div>
                                    <div class="reviewhaiphong_blog_comment-text">{{ $comment->content }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="color: #999; padding: 20px 0;">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                @endif

                <form class="reviewhaiphong_blog_comment-form" method="post" action="{{ route('comments.store') }}">
                    @csrf
                    <input type="hidden" name="post_id" value="{{ $post->id }}">
                    <label for="comment-content">Viết bình luận</label>
                    <textarea id="comment-content" name="content" rows="4" required placeholder="Nhập bình luận của bạn...">{{ old('content') }}</textarea>
                    <button type="submit">Gửi bình luận</button>
                </form>
            </div>
        </main>
    </div>

    <!-- Chat Bubble -->
    <div class="reviewhaiphong_blog_chat-bubble">
        <a href="https://zalo.me/{{ $settings['contact_zalo'] }}" target="_blank">
            <i style="color: #fff;" class="fa fa-comment"></i>
        </a>
    </div>
    
    <!-- Support Button -->
    <div class="reviewhaiphong_blog_support-button">
        <a href="https://zalo.me/{{ $settings['contact_zalo'] }}" target="_blank">
            <i style="color: #fff; margin-right: 5px;" class="fa fa-headset"></i>
            <span style="color: #fff;">Hỗ trợ trực tuyến</span>
        </a>
    </div>

    <!-- Right Sidebar Icons -->
    <div class="reviewhaiphong_blog_right-icons">
        <div class="reviewhaiphong_blog_right-icon" onclick="scrollToComments()">
            <i class="fa fa-arrow-down"></i>
        </div>
        <div class="reviewhaiphong_blog_right-icon" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
            <i class="fa fa-arrow-up"></i>
        </div>
    </div>
    
    <!-- JavaScript -->
    @include('client.module.js')
    
    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('reviewhaiphong_blog_leftSidebar');
            const overlay = document.getElementById('reviewhaiphong_blog_sidebarOverlay');
            
            if (sidebar && overlay) {
                const isActive = sidebar.classList.contains('reviewhaiphong_blog_active');
                sidebar.classList.toggle('reviewhaiphong_blog_active');
                overlay.classList.toggle('active');
                
                // Prevent body scroll when sidebar is open
                if (!isActive) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('reviewhaiphong_blog_leftSidebar');
            const toggle = document.querySelector('.reviewhaiphong_blog_menu-toggle');
            const overlay = document.getElementById('reviewhaiphong_blog_sidebarOverlay');
            
            if (sidebar && toggle && overlay) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target) && event.target === overlay) {
                    sidebar.classList.remove('reviewhaiphong_blog_active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });

        // Close sidebar on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const sidebar = document.getElementById('reviewhaiphong_blog_leftSidebar');
                const overlay = document.getElementById('reviewhaiphong_blog_sidebarOverlay');
                
                if (sidebar && overlay && sidebar.classList.contains('reviewhaiphong_blog_active')) {
                    sidebar.classList.remove('reviewhaiphong_blog_active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });

        // Search form redirect
        function redirectToSearch() {
            const keyword = document.getElementById('keyword').value.trim();
            if (keyword) {
                window.location.href = '/tim-kiem/' + encodeURIComponent(keyword);
            }
            return false;
        }

        // Scroll to comments
        function scrollToComments() {
            const commentsSection = document.getElementById('comments');
            if (commentsSection) {
                commentsSection.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add scroll animation to images
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        document.querySelectorAll('.reviewhaiphong_blog_article-content img').forEach(img => {
            img.style.opacity = '0';
            img.style.transform = 'translateY(20px)';
            img.style.transition = 'opacity 0.6s, transform 0.6s';
            observer.observe(img);
        });

        // Chat bubble animation
        const chatBubble = document.querySelector('.reviewhaiphong_blog_chat-bubble');
        if (chatBubble) {
            setInterval(() => {
                chatBubble.style.animation = 'pulse 1s';
                setTimeout(() => {
                    chatBubble.style.animation = '';
                }, 1000);
            }, 5000);
        }

        // Add pulse animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
        `;
        document.head.appendChild(style);

        // Hàm chuyển tiếng Việt có dấu sang slug không dấu
        function toSlug(text) {
            const map = {
                'à': 'a', 'á': 'a', 'ạ': 'a', 'ả': 'a', 'ã': 'a', 'â': 'a', 'ầ': 'a', 'ấ': 'a', 'ậ': 'a', 'ẩ': 'a', 'ẫ': 'a',
                'ă': 'a', 'ằ': 'a', 'ắ': 'a', 'ặ': 'a', 'ẳ': 'a', 'ẵ': 'a',
                'è': 'e', 'é': 'e', 'ẹ': 'e', 'ẻ': 'e', 'ẽ': 'e', 'ê': 'e', 'ề': 'e', 'ế': 'e', 'ệ': 'e', 'ể': 'e', 'ễ': 'e',
                'ì': 'i', 'í': 'i', 'ị': 'i', 'ỉ': 'i', 'ĩ': 'i',
                'ò': 'o', 'ó': 'o', 'ọ': 'o', 'ỏ': 'o', 'õ': 'o', 'ô': 'o', 'ồ': 'o', 'ố': 'o', 'ộ': 'o', 'ổ': 'o', 'ỗ': 'o',
                'ơ': 'o', 'ờ': 'o', 'ớ': 'o', 'ợ': 'o', 'ở': 'o', 'ỡ': 'o',
                'ù': 'u', 'ú': 'u', 'ụ': 'u', 'ủ': 'u', 'ũ': 'u', 'ư': 'u', 'ừ': 'u', 'ứ': 'u', 'ự': 'u', 'ử': 'u', 'ữ': 'u',
                'ỳ': 'y', 'ý': 'y', 'ỵ': 'y', 'ỷ': 'y', 'ỹ': 'y',
                'đ': 'd',
                'À': 'a', 'Á': 'a', 'Ạ': 'a', 'Ả': 'a', 'Ã': 'a', 'Â': 'a', 'Ầ': 'a', 'Ấ': 'a', 'Ậ': 'a', 'Ẩ': 'a', 'Ẫ': 'a',
                'Ă': 'a', 'Ằ': 'a', 'Ắ': 'a', 'Ặ': 'a', 'Ẳ': 'a', 'Ẵ': 'a',
                'È': 'e', 'É': 'e', 'Ẹ': 'e', 'Ẻ': 'e', 'Ẽ': 'e', 'Ê': 'e', 'Ề': 'e', 'Ế': 'e', 'Ệ': 'e', 'Ể': 'e', 'Ễ': 'e',
                'Ì': 'i', 'Í': 'i', 'Ị': 'i', 'Ỉ': 'i', 'Ĩ': 'i',
                'Ò': 'o', 'Ó': 'o', 'Ọ': 'o', 'Ỏ': 'o', 'Õ': 'o', 'Ô': 'o', 'Ồ': 'o', 'Ố': 'o', 'Ộ': 'o', 'Ổ': 'o', 'Ỗ': 'o',
                'Ơ': 'o', 'Ờ': 'o', 'Ớ': 'o', 'Ợ': 'o', 'Ở': 'o', 'Ỡ': 'o',
                'Ù': 'u', 'Ú': 'u', 'Ụ': 'u', 'Ủ': 'u', 'Ũ': 'u', 'Ư': 'u', 'Ừ': 'u', 'Ứ': 'u', 'Ự': 'u', 'Ử': 'u', 'Ữ': 'u',
                'Ỳ': 'y', 'Ý': 'y', 'Ỵ': 'y', 'Ỷ': 'y', 'Ỹ': 'y',
                'Đ': 'd'
            };
            
            return text
                .split('')
                .map(char => map[char] || char)
                .join('')
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '') // Loại bỏ ký tự đặc biệt
                .replace(/\s+/g, '-') // Thay khoảng trắng bằng dấu gạch ngang
                .replace(/-+/g, '-') // Loại bỏ nhiều dấu gạch ngang liên tiếp
                .replace(/^-|-$/g, ''); // Loại bỏ dấu gạch ngang ở đầu và cuối
        }

        // Table of Contents (TOC) - Tự động tạo từ headings
        function initTOC() {
            const articleContent = document.getElementById('article-content');
            const tocList = document.getElementById('toc-list');
            const tocContainer = document.getElementById('reviewhaiphong_blog_toc');
            
            if (!articleContent || !tocList || !tocContainer) {
                return;
            }

            // Lấy tất cả headings (h2, h3, h4) - tìm trong toàn bộ article-content
            const headings = articleContent.querySelectorAll('h2, h3, h4');
            
            if (headings.length === 0) {
                // Ẩn TOC nếu không có headings
                tocContainer.style.display = 'none';
                return;
            }

            let tocHTML = '';
            let headingCount = 0;

            headings.forEach((heading, index) => {
                headingCount++;
                const level = parseInt(heading.tagName.charAt(1)); // 2, 3, hoặc 4
                const text = heading.textContent.trim();
                
                // Escape HTML trong text
                const escapedText = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                
                // Tạo ID cho heading - chuyển tiếng Việt có dấu sang slug không dấu
                const slug = toSlug(text);
                const id = 'heading-' + index + '-' + slug;
                
                heading.id = id;
                
                // Thêm padding-left cho heading để có offset khi scroll
                heading.style.scrollMarginTop = '80px';
                
                // Tạo TOC item - sử dụng string concatenation thay vì template string để tránh lỗi
                tocHTML += '<li class="reviewhaiphong_blog_toc-item level-' + level + '">';
                tocHTML += '<a href="#' + id + '" class="reviewhaiphong_blog_toc-link" data-heading-id="' + id + '">';
                tocHTML += escapedText;
                tocHTML += '</a>';
                tocHTML += '</li>';
            });

            tocList.innerHTML = tocHTML;

            // Xử lý click TOC links
            tocList.querySelectorAll('.reviewhaiphong_blog_toc-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    
                    if (targetElement) {
                        // Remove active class từ tất cả links
                        tocList.querySelectorAll('.reviewhaiphong_blog_toc-link').forEach(l => {
                            l.classList.remove('active');
                        });
                        
                        // Add active class cho link được click
                        this.classList.add('active');
                        
                        // Smooth scroll đến heading
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });

                        // Remove active sau 1s
                        setTimeout(() => {
                            this.classList.remove('active');
                        }, 1000);
                    }
                });
            });

            // Highlight TOC item khi scroll đến heading
            const observerOptions = {
                root: null,
                rootMargin: '-20% 0px -70% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const headingId = entry.target.id;
                        const tocLink = tocList.querySelector(`[data-heading-id="${headingId}"]`);
                        
                        // Remove active từ tất cả
                        tocList.querySelectorAll('.reviewhaiphong_blog_toc-link').forEach(l => {
                            l.classList.remove('active');
                        });
                        
                        // Add active cho link tương ứng
                        if (tocLink) {
                            tocLink.classList.add('active');
                        }
                    }
                });
            }, observerOptions);

            // Observe tất cả headings
            headings.forEach(heading => {
                observer.observe(heading);
            });
        }

        // Chạy TOC sau khi DOM load xong
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initTOC, 100);
            });
        } else {
            // DOM đã load xong
            setTimeout(initTOC, 100);
        }
        
        // Chạy lại sau khi window load để đảm bảo content đã render
        window.addEventListener('load', function() {
            setTimeout(initTOC, 200);
        });

        // Hiển thị alert khi gửi bình luận thành công
        @if(session('success'))
            // Alert từ session
            setTimeout(function() {
                alert('{{ session('success') }}');
                // Scroll đến phần comments
                const commentsSection = document.getElementById('comments');
                if (commentsSection) {
                    commentsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 100);
        @endif

        @if($errors->any())
            // Alert lỗi từ validation
            setTimeout(function() {
                const errorMessages = @json($errors->all());
                alert('Lỗi: ' + errorMessages.join('\\n'));
            }, 100);
        @endif

        // Xử lý form submit bình luận
        const commentForm = document.querySelector('.reviewhaiphong_blog_comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                const textarea = this.querySelector('textarea[name="content"]');
                const button = this.querySelector('button[type="submit"]');
                
                if (textarea && textarea.value.trim().length < 3) {
                    e.preventDefault();
                    alert('Bình luận phải có ít nhất 3 ký tự!');
                    return false;
                }
                
                // Disable button để tránh submit nhiều lần
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Đang gửi...';
                }
            });
        }
    </script>

    <!-- Footer chung -->
    @include('client.templates.footer')
</body>

</html>
