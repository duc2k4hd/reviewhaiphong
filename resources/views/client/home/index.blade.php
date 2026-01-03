@extends('client.layouts.main')

@section('title', ($settings['site_name'] ?? 'Review Hải Phòng') . ' - ' . ($settings['site_description'] ?? 'Trang chủ'))

@section('meta_description', $settings['site_description'] ?? 'Khám phá các bài viết, đánh giá và chia sẻ kinh nghiệm về Hải Phòng. Tổng hợp thông tin hữu ích và cập nhật nhất.')

@section('meta_keywords', $settings['site_keywords'] ?? $settings['seo_keywords'] ?? 'review hải phòng, đánh giá, chia sẻ, kinh nghiệm')

@section('meta_author', $settings['seo_author'] ?? $settings['site_name'] ?? 'Review Hải Phòng')

@section('og_type', 'website')

@section('og_url', url('/'))

@section('og_title', $settings['site_name'] ?? 'Review Hải Phòng')

@section('og_description', $settings['site_description'] ?? 'Khám phá các bài viết, đánh giá và chia sẻ kinh nghiệm về Hải Phòng.')

@section('og_image', asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')))

@section('og_image_alt', $settings['site_name'] ?? 'Review Hải Phòng')

@section('twitter_title', $settings['site_name'] ?? 'Review Hải Phòng')

@section('twitter_description', $settings['site_description'] ?? 'Khám phá các bài viết, đánh giá và chia sẻ kinh nghiệm về Hải Phòng.')

@section('twitter_image', asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')))

@section('twitter_image_alt', $settings['site_name'] ?? 'Review Hải Phòng')

@section('canonical_url', url('/'))

@section('hreflang_vi', url('/'))

@section('hreflang_default', url('/'))

@section('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "WebPage",
            "@id": "{{ url('/') . '/#webpage' }}",
            "url": "{{ url('/') }}",
            "name": "{{ $settings['site_name'] ?? 'Review Hải Phòng' }}",
            "description": "{{ $settings['site_description'] ?? '' }}",
            "isPartOf": {
                "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#website' }}"
            },
            "about": {
                "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#organization' }}"
            },
            "primaryImageOfPage": {
                "@type": "ImageObject",
                "url": "{{ asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')) }}"
            },
            "datePublished": "{{ \Carbon\Carbon::now()->toIso8601String() }}",
            "dateModified": "{{ \Carbon\Carbon::now()->toIso8601String() }}",
            "inLanguage": "vi",
            "breadcrumb": {
                "@id": "{{ url('/') . '/#breadcrumb' }}"
            }
        },
        {
            "@type": "BreadcrumbList",
            "@id": "{{ url('/') . '/#breadcrumb' }}",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "{{ $settings['site_name'] ?? 'Review Hải Phòng' }}",
                    "item": "{{ url('/') }}"
                }
            ]
        }
        @if(isset($featuredPost) && $featuredPost)
        ,{
            "@type": "ItemList",
            "itemListElement": [
                @foreach([$featuredPost] as $index => $post)
                {
                    "@type": "ListItem",
                    "position": {{ $index + 1 }},
                    "item": {
                        "@type": "Article",
                        "@id": "{{ url('/bai-viet/' . $post->slug) }}",
                        "name": {!! json_encode($post->seo_title ?? $post->name ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                        "headline": {!! json_encode($post->seo_title ?? $post->name ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                        "description": {!! json_encode(\Illuminate\Support\Str::limit(strip_tags($post->seo_desc ?? ''), 200), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                        "image": "{{ $post->seo_image ? asset('/client/assets/images/posts/' . $post->seo_image) : '' }}",
                        "url": "{{ url('/bai-viet/' . $post->slug) }}",
                        "datePublished": "{{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->toIso8601String() : '' }}",
                        "dateModified": "{{ $post->updated_at ? \Carbon\Carbon::parse($post->updated_at)->toIso8601String() : '' }}",
                        "author": {
                            "@type": "Person",
                            "name": "{{ $post->account->profile->name ?? $post->account->username ?? 'Admin' }}"
                        },
                        "publisher": {
                            "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#organization' }}"
                        }
                    }
                }
                @endforeach
            ]
        }
        @endif
    ]
}
</script>
@endsection

@section('header')
    @include('client.templates.header')
@endsection

@section('content')
<style>
    /* Home Page Styles */
    .reviewhaiphong_home_container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Main Content */
    .reviewhaiphong_home_main-content {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 30px;
        padding: 10px 0;
    }

    /* Hero Section */
    .reviewhaiphong_home_hero {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }

    .reviewhaiphong_home_featured-post {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        height: 450px;
    }

    .reviewhaiphong_home_featured-post img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .reviewhaiphong_home_featured-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 30px;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        color: #fff;
    }

    .reviewhaiphong_home_category-tag {
        background: #e74c3c;
        color: #fff;
        padding: 5px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .reviewhaiphong_home_featured-title {
        font-size: 24px;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 10px;
    }

    .reviewhaiphong_home_featured-title a {
        color: #fff;
        text-decoration: none;
    }

    .reviewhaiphong_home_featured-meta {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: rgba(255,255,255,0.8);
    }

    .reviewhaiphong_home_featured-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Side Posts */
    .reviewhaiphong_home_side-posts {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .reviewhaiphong_home_side-post {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .reviewhaiphong_home_side-post img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .reviewhaiphong_home_side-post-content {
        padding: 15px;
    }

    .reviewhaiphong_home_side-post-title {
        font-size: 15px;
        font-weight: 600;
        line-height: 1.4;
        margin-bottom: 8px;
        color: #333;
    }

    .reviewhaiphong_home_side-post-title a {
        color: #333;
        text-decoration: none;
    }

    .reviewhaiphong_home_side-post-title a:hover {
        color: #e74c3c;
    }

    .reviewhaiphong_home_post-meta {
        display: flex;
        gap: 10px;
        font-size: 11px;
        color: #999;
    }

    /* Posts Grid */
    .reviewhaiphong_home_posts-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .reviewhaiphong_home_post-card {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .reviewhaiphong_home_post-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .reviewhaiphong_home_post-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .reviewhaiphong_home_post-card-content {
        padding: 20px;
    }

    .reviewhaiphong_home_post-card-title {
        font-size: 16px;
        font-weight: 600;
        line-height: 1.4;
        margin-bottom: 10px;
        color: #333;
    }

    .reviewhaiphong_home_post-card-title a {
        color: #333;
        text-decoration: none;
    }

    .reviewhaiphong_home_post-card-title a:hover {
        color: #e74c3c;
    }

    /* Section Header */
    .reviewhaiphong_home_section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e5e5e5;
    }

    .reviewhaiphong_home_section-title {
        font-size: 20px;
        font-weight: 700;
        color: #333;
    }

    .reviewhaiphong_home_view-all {
        font-size: 13px;
        color: #666;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .reviewhaiphong_home_view-all:hover {
        color: #e74c3c;
    }

    /* Sidebar */
    .reviewhaiphong_home_sidebar {
        position: sticky;
        top: 80px;
        height: fit-content;
    }

    .reviewhaiphong_home_widget {
        background: #fff;
        border-radius: 8px;
        padding: 5px 25px;
        margin-bottom: 25px;
    }

    .reviewhaiphong_home_widget-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #333;
    }

    /* Editor's Pick */
    .reviewhaiphong_home_editor-pick {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .reviewhaiphong_home_editor-pick:last-child {
        margin-bottom: 0;
    }

    .reviewhaiphong_home_editor-pick img {
        width: 80px;
        height: 80px;
        border-radius: 6px;
        object-fit: cover;
    }

    .reviewhaiphong_home_editor-pick-content {
        flex: 1;
    }

    .reviewhaiphong_home_editor-pick-title {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
        margin-bottom: 5px;
    }

    .reviewhaiphong_home_editor-pick-title a {
        color: #333;
        text-decoration: none;
    }

    .reviewhaiphong_home_editor-pick-title a:hover {
        color: #e74c3c;
    }

    /* Hot Topics */
    .reviewhaiphong_home_hot-topics {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .reviewhaiphong_home_hot-topic {
        padding: 15px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .reviewhaiphong_home_hot-topic:last-child {
        border-bottom: none;
    }

    .reviewhaiphong_home_hot-topic-number {
        font-size: 24px;
        font-weight: 700;
        color: #e74c3c;
        margin-bottom: 8px;
    }

    .reviewhaiphong_home_hot-topic-title {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
        margin-bottom: 5px;
    }

    .reviewhaiphong_home_hot-topic-title a {
        color: #333;
        text-decoration: none;
    }

    .reviewhaiphong_home_hot-topic-title a:hover {
        color: #e74c3c;
    }

    .reviewhaiphong_home_hot-topic-excerpt {
        font-size: 12px;
        color: #666;
        line-height: 1.5;
    }

    /* Top Contributors */
    .reviewhaiphong_home_contributor {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .reviewhaiphong_home_contributor:last-child {
        border-bottom: none;
    }

    .reviewhaiphong_home_contributor-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .reviewhaiphong_home_contributor-info h4 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 3px;
        color: #333;
    }

    .reviewhaiphong_home_contributor-info p {
        font-size: 11px;
        color: #999;
        margin: 0;
    }

    /* Tabs */
    .reviewhaiphong_home_tabs {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
        border-bottom: 2px solid #e5e5e5;
    }

    .reviewhaiphong_home_tab {
        padding: 12px 20px;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #666;
        position: relative;
        transition: color 0.3s;
    }

    .reviewhaiphong_home_tab:hover,
    .reviewhaiphong_home_tab.reviewhaiphong_home_active {
        color: #e74c3c;
    }

    .reviewhaiphong_home_tab.reviewhaiphong_home_active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e74c3c;
    }

    /* Featured Banner */
    .reviewhaiphong_home_featured-banner {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px;
        color: #fff;
    }

    .reviewhaiphong_home_featured-banner h2 {
        font-size: 32px;
        margin-bottom: 15px;
    }

    .reviewhaiphong_home_featured-banner p {
        font-size: 16px;
        opacity: 0.9;
    }

    /* Stories Section */
    .reviewhaiphong_home_stories-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 40px;
    }

    .reviewhaiphong_home_story-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        height: 200px;
        cursor: pointer;
    }

    .reviewhaiphong_home_story-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .reviewhaiphong_home_story-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 20px;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        color: #fff;
    }

    .reviewhaiphong_home_story-title {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.3;
        margin: 0;
    }

    .reviewhaiphong_home_story-title a {
        color: #fff;
        text-decoration: none;
    }

    /* Recent Comments Widget */
    .reviewhaiphong_home_recent-comments {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .reviewhaiphong_home_recent-comment {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .reviewhaiphong_home_recent-comment:last-child {
        border-bottom: none;
    }

    .reviewhaiphong_home_recent-comment-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        overflow: hidden;
    }

    .reviewhaiphong_home_recent-comment-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .reviewhaiphong_home_recent-comment-avatar i {
        color: #999;
        font-size: 18px;
    }

    .reviewhaiphong_home_recent-comment-content {
        flex: 1;
    }

    .reviewhaiphong_home_recent-comment-author {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }

    .reviewhaiphong_home_recent-comment-text {
        font-size: 12px;
        color: #666;
        line-height: 1.5;
        margin-bottom: 4px;
    }

    .reviewhaiphong_home_recent-comment-text a {
        color: #666;
        text-decoration: none;
    }

    .reviewhaiphong_home_recent-comment-text a:hover {
        color: #e74c3c;
    }

    .reviewhaiphong_home_recent-comment-meta {
        font-size: 11px;
        color: #999;
    }

    /* Popular Tags Widget */
    .reviewhaiphong_home_popular-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .reviewhaiphong_home_tag-item {
        display: inline-block;
        padding: 6px 12px;
        background: #f5f5f5;
        color: #666;
        border-radius: 20px;
        font-size: 12px;
        text-decoration: none;
        transition: all 0.3s;
    }

    .reviewhaiphong_home_tag-item:hover {
        background: #e74c3c;
        color: #fff;
        transform: translateY(-2px);
    }

    .reviewhaiphong_home_tag-count {
        font-size: 11px;
        opacity: 0.7;
    }

    /* Newsletter Widget */
    .reviewhaiphong_home_newsletter-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .reviewhaiphong_home_newsletter-input {
        width: 100%;
        padding: 12px;
        border: 1px solid #e5e5e5;
        border-radius: 6px;
        font-size: 13px;
        outline: none;
        transition: border-color 0.3s;
    }

    .reviewhaiphong_home_newsletter-input:focus {
        border-color: #e74c3c;
    }

    .reviewhaiphong_home_newsletter-btn {
        padding: 12px 20px;
        background: #e74c3c;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .reviewhaiphong_home_newsletter-btn:hover {
        background: #c0392b;
    }

    /* Back to Top Button */
    .reviewhaiphong_home_back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: #e74c3c;
        color: #fff;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.3s;
        z-index: 1000;
    }

    .reviewhaiphong_home_back-to-top:hover {
        background: #c0392b;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }

    .reviewhaiphong_home_back-to-top.show {
        display: flex;
    }

    /* Reading Time Badge */
    .reviewhaiphong_home_reading-time {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        background: rgba(255,255,255,0.2);
        border-radius: 12px;
        font-size: 11px;
        color: rgba(255,255,255,0.9);
    }

    /* Social Share Buttons */
    .reviewhaiphong_home_social-share {
        display: flex;
        gap: 8px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e5e5e5;
    }

    .reviewhaiphong_home_social-share-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        transition: transform 0.3s;
    }

    .reviewhaiphong_home_social-share-btn:hover {
        transform: scale(1.1);
    }

    .reviewhaiphong_home_social-share-btn.facebook { background: #3b5998; }
    .reviewhaiphong_home_social-share-btn.twitter { background: #1da1f2; }
    .reviewhaiphong_home_social-share-btn.linkedin { background: #0077b5; }
    .reviewhaiphong_home_social-share-btn.zalo { background: #0068ff; }

    /* Lazy Loading */
    .reviewhaiphong_home_post-card img,
    .reviewhaiphong_home_story-card img,
    .reviewhaiphong_home_featured-post img,
    .reviewhaiphong_home_side-post img,
    .reviewhaiphong_home_editor-pick img {
        loading: lazy;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .reviewhaiphong_home_post-card,
    .reviewhaiphong_home_story-card {
        animation: fadeIn 0.5s ease-out;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .reviewhaiphong_home_main-content {
            padding: 0;
            grid-template-columns: 1fr;
        }

        .reviewhaiphong_home_sidebar {
            position: static;
        }

        .reviewhaiphong_home_posts-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .reviewhaiphong_home_hero {
            grid-template-columns: 1fr;
        }

        .reviewhaiphong_home_stories-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .reviewhaiphong_home_posts-grid {
            grid-template-columns: 1fr;
        }

        .reviewhaiphong_home_stories-grid {
            grid-template-columns: 1fr;
        }

        .reviewhaiphong_home_back-to-top {
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
        }
    }
</style>

<!-- Main Content -->
<div class="reviewhaiphong_home_container">
    <div class="reviewhaiphong_home_main-content">
        <!-- Left Content -->
        <div>
            <!-- Hero Section -->
            <div class="reviewhaiphong_home_hero">
                @if($featuredPost)
                <div class="reviewhaiphong_home_featured-post">
                    <a href="/bai-viet/{{ $featuredPost->slug }}">
                        <img src="{{ $featuredPost->seo_image ? asset('/client/assets/images/posts/' . $featuredPost->seo_image) : asset('/client/assets/images/default-post.jpg') }}" alt="{{ $featuredPost->seo_title ?? 'Featured Post' }}">
                        <div class="reviewhaiphong_home_featured-overlay">
                            @if($featuredPost->category)
                            <span class="reviewhaiphong_home_category-tag">{{ $featuredPost->category->name }}</span>
                            @endif
                            <h2 class="reviewhaiphong_home_featured-title">
                                <a href="/bai-viet/{{ $featuredPost->slug }}">{{ $featuredPost->seo_title ?? 'Tiêu đề bài viết' }}</a>
                            </h2>
                            <div class="reviewhaiphong_home_featured-meta">
                                <span><i class="fa fa-user"></i> {{ $featuredPost->account->profile->name ?? $featuredPost->account->username ?? 'Admin' }}</span>
                                <span><i class="fa fa-calendar"></i> {{ $featuredPost->published_at ? \Carbon\Carbon::parse($featuredPost->published_at)->format('d/m/Y') : 'N/A' }}</span>
                                <span><i class="fa fa-eye"></i> {{ number_format($featuredPost->views ?? 0) }} lượt xem</span>
                                @if($featuredPost->seo_desc)
                                <span class="reviewhaiphong_home_reading-time">
                                    <i class="fa fa-clock-o"></i> {{ ceil(str_word_count(strip_tags($featuredPost->seo_desc)) / 200) }} phút đọc
                                </span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                <div class="reviewhaiphong_home_side-posts">
                    @foreach($sidePosts as $sidePost)
                    <div class="reviewhaiphong_home_side-post">
                        <a href="/bai-viet/{{ $sidePost->slug }}">
                            <img src="{{ $sidePost->seo_image ? asset('/client/assets/images/posts/' . $sidePost->seo_image) : asset('/client/assets/images/default-post.jpg') }}" alt="{{ $sidePost->seo_title ?? 'Post' }}">
                        </a>
                        <div class="reviewhaiphong_home_side-post-content">
                            <h3 class="reviewhaiphong_home_side-post-title">
                                <a href="/bai-viet/{{ $sidePost->slug }}">{{ $sidePost->seo_title ?? 'Tiêu đề bài viết' }}</a>
                            </h3>
                        <div class="reviewhaiphong_home_post-meta">
                            <span><i class="fa fa-user"></i> {{ $sidePost->account->profile->name ?? $sidePost->account->username ?? 'Admin' }}</span>
                            <span><i class="fa fa-calendar"></i> {{ $sidePost->published_at ? \Carbon\Carbon::parse($sidePost->published_at)->format('d/m/Y') : 'N/A' }}</span>
                        </div>
                        </div>
        </div>
                    @endforeach
        </div>
    </div>

            <!-- Posts by Category -->
            @foreach($mainCategories as $index => $category)
            @if(isset($postsByCategory[$category->slug]) && $postsByCategory[$category->slug]->count() > 0)
            <div class="reviewhaiphong_home_section-header">
                <h2 class="reviewhaiphong_home_section-title">{{ $category->name }}</h2>
                <a href="/{{ $category->slug }}" class="reviewhaiphong_home_view-all">Xem tất cả →</a>
            </div>

            <div class="reviewhaiphong_home_posts-grid">
                @foreach($postsByCategory[$category->slug] as $post)
                <div class="reviewhaiphong_home_post-card">
                    <a href="/bai-viet/{{ $post->slug }}">
                        <img src="{{ $post->seo_image ? asset('/client/assets/images/posts/' . $post->seo_image) : asset('/client/assets/images/default-post.jpg') }}" alt="{{ $post->seo_title ?? 'Post' }}">
                    </a>
                        <div class="reviewhaiphong_home_post-card-content">
                        <h3 class="reviewhaiphong_home_post-card-title">
                            <a href="/bai-viet/{{ $post->slug }}">{{ $post->seo_title ?? 'Tiêu đề bài viết' }}</a>
                        </h3>
                        @if($post->seo_desc)
                        <p style="font-size: 13px; color: #666; margin: 8px 0; line-height: 1.5;">{{ \Illuminate\Support\Str::limit(strip_tags($post->seo_desc), 100) }}</p>
                        @endif
                        <div class="reviewhaiphong_home_post-meta">
                            <span><i class="fa fa-calendar"></i> {{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') : 'N/A' }}</span>
                            <span><i class="fa fa-eye"></i> {{ number_format($post->views ?? 0) }} lượt xem</span>
                        </div>
                        <div class="reviewhaiphong_home_social-share">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url('/bai-viet/' . $post->slug)) }}" target="_blank" class="reviewhaiphong_home_social-share-btn facebook" title="Chia sẻ lên Facebook">
                                <i class="fa fa-facebook"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode(url('/bai-viet/' . $post->slug)) }}&text={{ urlencode($post->seo_title ?? '') }}" target="_blank" class="reviewhaiphong_home_social-share-btn twitter" title="Chia sẻ lên Twitter">
                                <i class="fa fa-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url('/bai-viet/' . $post->slug)) }}" target="_blank" class="reviewhaiphong_home_social-share-btn linkedin" title="Chia sẻ lên LinkedIn">
                                <i class="fa fa-linkedin"></i>
                            </a>
                            <a href="https://zalo.me/share?url={{ urlencode(url('/bai-viet/' . $post->slug)) }}" target="_blank" class="reviewhaiphong_home_social-share-btn zalo" title="Chia sẻ lên Zalo">
                                <i class="fa fa-comment"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            @endforeach

            <!-- Featured Banner -->
            <div class="reviewhaiphong_home_featured-banner">
                <h2>Sự kiện & Hội thảo</h2>
                <p>Tham gia các sự kiện và hội thảo hàng đầu trong ngành Marketing & Creative</p>
            </div>

            <!-- Stories Section -->
            @if($stories->count() > 0)
            <div class="reviewhaiphong_home_section-header">
                <h2 class="reviewhaiphong_home_section-title">Câu chuyện nổi bật</h2>
                <a href="#" class="reviewhaiphong_home_view-all">Xem tất cả →</a>
            </div>

            <div class="reviewhaiphong_home_stories-grid">
                @foreach($stories as $story)
                <div class="reviewhaiphong_home_story-card">
                    <a href="/bai-viet/{{ $story->slug }}">
                        <img src="{{ $story->seo_image ? asset('/client/assets/images/posts/' . $story->seo_image) : asset('/client/assets/images/default-post.jpg') }}" alt="{{ $story->seo_title ?? 'Story' }}">
                        <div class="reviewhaiphong_home_story-overlay">
                            <p class="reviewhaiphong_home_story-title">
                                <a href="/bai-viet/{{ $story->slug }}">{{ \Illuminate\Support\Str::limit($story->seo_title ?? 'Story Title', 50) }}</a>
                            </p>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <aside class="reviewhaiphong_home_sidebar">
            <!-- Editor's Picks Widget -->
            @if($editorPicks->count() > 0)
            <div class="reviewhaiphong_home_widget">
                <h3 class="reviewhaiphong_home_widget-title"><i class="fa fa-star"></i> Lựa chọn của biên tập</h3>

                @foreach($editorPicks as $pick)
                <div class="reviewhaiphong_home_editor-pick">
                    <a href="/bai-viet/{{ $pick->slug }}">
                        <img src="{{ $pick->seo_image ? asset('/client/assets/images/posts/' . $pick->seo_image) : asset('/client/assets/images/default-post.jpg') }}" alt="{{ $pick->seo_title ?? 'Pick' }}">
                    </a>
                    <div class="reviewhaiphong_home_editor-pick-content">
                        <h4 class="reviewhaiphong_home_editor-pick-title">
                            <a href="/bai-viet/{{ $pick->slug }}">{{ \Illuminate\Support\Str::limit($pick->seo_title ?? 'Tiêu đề bài viết', 60) }}</a>
                        </h4>
                        <div class="reviewhaiphong_home_post-meta">
                            <span><i class="fa fa-calendar"></i> {{ $pick->published_at ? \Carbon\Carbon::parse($pick->published_at)->format('d/m/Y') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Hot Topics Widget -->
            @if($hotTopics->count() > 0)
            <div class="reviewhaiphong_home_widget">
                <h3 class="reviewhaiphong_home_widget-title">Bài hot trong tuần</h3>

                <ul class="reviewhaiphong_home_hot-topics">
                    @foreach($hotTopics as $index => $topic)
                    <li class="reviewhaiphong_home_hot-topic">
                        <div class="reviewhaiphong_home_hot-topic-number">#{{ $index + 1 }}</div>
                        <h4 class="reviewhaiphong_home_hot-topic-title">
                            <a href="/bai-viet/{{ $topic->slug }}">{{ \Illuminate\Support\Str::limit($topic->seo_title ?? 'Tiêu đề bài viết', 80) }}</a>
                        </h4>
                        <p class="reviewhaiphong_home_hot-topic-excerpt">{{ \Illuminate\Support\Str::limit($topic->seo_desc ?? '', 100) }}</p>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Recent Comments Widget -->
            @if(isset($recentComments) && $recentComments->count() > 0)
            <div class="reviewhaiphong_home_widget">
                <h3 class="reviewhaiphong_home_widget-title"><i class="fa fa-comments"></i> Bình luận mới nhất</h3>
                <div class="reviewhaiphong_home_recent-comments">
                    @foreach($recentComments as $comment)
                    <div class="reviewhaiphong_home_recent-comment">
                        <div class="reviewhaiphong_home_recent-comment-avatar">
                            @if($comment->account && $comment->account->profile && $comment->account->profile->avatar)
                            <img src="{{ asset('storage/' . $comment->account->profile->avatar) }}" alt="{{ $comment->account->profile->name ?? 'User' }}">
                            @else
                            <i class="fa fa-user"></i>
                            @endif
                        </div>
                        <div class="reviewhaiphong_home_recent-comment-content">
                            <div class="reviewhaiphong_home_recent-comment-author">{{ $comment->account->profile->name ?? $comment->account->username ?? 'Khách' }}</div>
                            <div class="reviewhaiphong_home_recent-comment-text">
                                <a href="/bai-viet/{{ $comment->post->slug ?? '#' }}#comment-{{ $comment->id }}">
                                    {{ \Illuminate\Support\Str::limit($comment->content, 80) }}
                                </a>
                            </div>
                            <div class="reviewhaiphong_home_recent-comment-meta">
                                <span><i class="fa fa-clock-o"></i> {{ $comment->created_at ? \Carbon\Carbon::parse($comment->created_at)->diffForHumans() : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Popular Tags Widget -->
            @if(isset($popularTags) && $popularTags->count() > 0)
            <div class="reviewhaiphong_home_widget">
                <h3 class="reviewhaiphong_home_widget-title"><i class="fa fa-tags"></i> Tags phổ biến</h3>
                <div class="reviewhaiphong_home_popular-tags">
                    @foreach($popularTags as $tag)
                    <a href="/tim-kiem/{{ $tag->slug }}" class="reviewhaiphong_home_tag-item">
                        #{{ $tag->name }} <span class="reviewhaiphong_home_tag-count">({{ $tag->count }})</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Newsletter Widget -->
            <div class="reviewhaiphong_home_widget">
                <h3 class="reviewhaiphong_home_widget-title"><i class="fa fa-envelope"></i> Đăng ký nhận tin</h3>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Nhận thông tin mới nhất về các bài viết và sự kiện</p>
                <form class="reviewhaiphong_home_newsletter-form" onsubmit="return false;">
                    <input type="email" placeholder="Nhập email của bạn" required class="reviewhaiphong_home_newsletter-input">
                    <button type="submit" class="reviewhaiphong_home_newsletter-btn">
                        <i class="fa fa-paper-plane"></i> Đăng ký
                    </button>
                </form>
            </div>

            <!-- Top Contributors Widget -->
            @if($topContributors->count() > 0)
            <div class="reviewhaiphong_home_widget">
                <h3 class="reviewhaiphong_home_widget-title"><i class="fa fa-users"></i> Tác giả nổi bật</h3>

                @foreach($topContributors as $contributor)
                <div class="reviewhaiphong_home_contributor">
                    @if($contributor->profile && $contributor->profile->avatar)
                    <img src="{{ asset('storage/' . $contributor->profile->avatar) }}" alt="{{ $contributor->profile->name ?? $contributor->username }}" class="reviewhaiphong_home_contributor-avatar">
                    @else
                    <img src="https://i.pravatar.cc/100?img={{ $loop->index + 1 }}" alt="{{ $contributor->profile->name ?? $contributor->username }}" class="reviewhaiphong_home_contributor-avatar">
                    @endif
                    <div class="reviewhaiphong_home_contributor-info">
                        <h4>{{ $contributor->profile->name ?? $contributor->username }}</h4>
                        <p>{{ $contributor->posts_count ?? 0 }} bài viết</p>
                    </div>
                </div>
                @endforeach
        </div>
            @endif
        </aside>
    </div>
</div>

<!-- Back to Top Button -->
<div class="reviewhaiphong_home_back-to-top" id="backToTop">
    <i class="fa fa-arrow-up"></i>
</div>

<script>
    // Tabs functionality
    document.querySelectorAll('.reviewhaiphong_home_tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.reviewhaiphong_home_tab').forEach(t => {
                t.classList.remove('reviewhaiphong_home_active');
            });
            this.classList.add('reviewhaiphong_home_active');
        });
    });

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

    // Back to Top Button
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });

        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Newsletter Form
    const newsletterForm = document.querySelector('.reviewhaiphong_home_newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('.reviewhaiphong_home_newsletter-input').value;
            if (email) {
                alert('Cảm ơn bạn đã đăng ký nhận tin! Chúng tôi sẽ gửi email cho bạn sớm nhất.');
                this.querySelector('.reviewhaiphong_home_newsletter-input').value = '';
            }
        });
    }

    // Lazy Loading Images với Intersection Observer
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Format số lượt xem
    document.querySelectorAll('.reviewhaiphong_home_post-meta').forEach(meta => {
        const viewSpan = meta.querySelector('span:last-child');
        if (viewSpan && viewSpan.textContent.includes('lượt xem')) {
            const viewText = viewSpan.textContent;
            const viewNumber = parseInt(viewText.replace(/\D/g, ''));
            if (viewNumber >= 1000) {
                viewSpan.innerHTML = '<i class="fa fa-eye"></i> ' + (viewNumber / 1000).toFixed(1) + 'K lượt xem';
            }
        }
    });
</script>
@endsection
