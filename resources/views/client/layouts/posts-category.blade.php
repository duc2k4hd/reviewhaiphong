@extends('client.layouts.main')

@section('title', $category->name . ' - ' . ($settings['site_name'] ?? 'Review Hải Phòng'))

@section('meta_description', $category->description ?? $category->meta_description ?? 'Khám phá các bài viết về ' . $category->name . '. Tổng hợp bài viết, đánh giá và chia sẻ kinh nghiệm.')

@section('meta_keywords', $category->meta_keywords ?? $category->name . ', ' . ($settings['site_keywords'] ?? ''))

@section('meta_author', $settings['seo_author'] ?? $settings['site_name'] ?? 'Review Hải Phòng')

@section('og_type', 'website')

@section('og_url', url('/' . $category->slug))

@section('og_title', $category->name . ' - ' . ($settings['site_name'] ?? 'Review Hải Phòng'))

@section('og_description', $category->description ?? $category->meta_description ?? 'Khám phá các bài viết về ' . $category->name . '.')

@section('og_image', $category->meta_image ? asset('/client/assets/images/categories/' . $category->meta_image) : asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')))

@section('og_image_alt', $category->name)

@section('twitter_title', $category->name . ' - ' . ($settings['site_name'] ?? 'Review Hải Phòng'))

@section('twitter_description', $category->description ?? $category->meta_description ?? 'Khám phá các bài viết về ' . $category->name . '.')

@section('twitter_image', $category->meta_image ? asset('/client/assets/images/categories/' . $category->meta_image) : asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')))

@section('twitter_image_alt', $category->name)

@section('canonical_url', url('/' . $category->slug))

@section('hreflang_vi', url('/' . $category->slug))

@section('hreflang_default', url('/' . $category->slug))

@section('structured_data')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "CollectionPage",
            "@id": "{{ url('/' . $category->slug) . '/#webpage' }}",
            "url": "{{ url('/' . $category->slug) }}",
            "name": "{{ $category->name }}",
            "description": "{{ $category->description ?? $category->meta_description ?? 'Tổng hợp các bài viết về ' . $category->name }}",
            "isPartOf": {
                "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#website' }}"
            },
            "about": {
                "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#organization' }}"
            },
            "primaryImageOfPage": {
                "@type": "ImageObject",
                "url": "{{ $category->meta_image ? asset('/client/assets/images/categories/' . $category->meta_image) : asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')) }}"
            },
            "datePublished": "{{ $category->created_at ? \Carbon\Carbon::parse($category->created_at)->toIso8601String() : \Carbon\Carbon::now()->toIso8601String() }}",
            "dateModified": "{{ $category->updated_at ? \Carbon\Carbon::parse($category->updated_at)->toIso8601String() : \Carbon\Carbon::now()->toIso8601String() }}",
            "inLanguage": "vi",
            "breadcrumb": {
                "@id": "{{ url('/' . $category->slug) . '/#breadcrumb' }}"
            },
            @if($posts->count() > 0)
            "mainEntity": {
                "@type": "ItemList",
                "itemListElement": [
                    @foreach($posts->items() as $index => $post)
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
                            },
                            "articleSection": "{{ $category->name }}"
                        }
                    }@if(!$loop->last),@endif
                    @endforeach
                ],
                "numberOfItems": {{ $postsCount ?? $posts->total() }}
            },
            @endif
            "aggregateRating": {
                "@type": "AggregateRating",
                "ratingValue": "4.5",
                "reviewCount": "{{ $postsCount ?? 0 }}"
            }
        },
        {
            "@type": "BreadcrumbList",
            "@id": "{{ url('/' . $category->slug) . '/#breadcrumb' }}",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "{{ $settings['site_name'] ?? 'Review Hải Phòng' }}",
                    "item": "{{ url('/') }}"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "{{ $category->name }}",
                    "item": "{{ url('/' . $category->slug) }}"
                }
            ]
        }
    ]
}
</script>
@endsection

@section('header')
    @include('client.templates.header')
@endsection

@section('content')
<style>
    /* Category Page Styles */
    .reviewhaiphong_category_container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Agency Header Banner */
    .reviewhaiphong_category_agency-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 0;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .reviewhaiphong_category_agency-banner {
        display: flex;
        align-items: center;
        gap: 30px;
    }

    .reviewhaiphong_category_agency-info {
        flex: 1;
        color: #fff;
    }

    .reviewhaiphong_category_agency-badge {
        background: rgba(255,255,255,0.2);
        color: #fff;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    .reviewhaiphong_category_agency-title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .reviewhaiphong_category_agency-subtitle {
        font-size: 16px;
        opacity: 0.9;
    }

    .reviewhaiphong_category_agency-logo {
        width: 150px;
        height: 150px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 60px;
        font-weight: 700;
        color: #667eea;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        flex-shrink: 0;
    }

    .reviewhaiphong_category_agency-image {
        flex: 1;
        text-align: right;
    }

    .reviewhaiphong_category_agency-image img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }

    /* Agency Description */
    .reviewhaiphong_category_agency-desc {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
        text-align: center;
    }

    .reviewhaiphong_category_agency-desc h2 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 15px;
        color: #333;
    }

    .reviewhaiphong_category_agency-desc p {
        font-size: 15px;
        line-height: 1.8;
        color: #666;
        max-width: 800px;
        margin: 0 auto 20px;
    }

    .reviewhaiphong_category_follow-btn {
        background: #3b5998;
        color: #fff;
        padding: 10px 25px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: background 0.3s;
    }

    .reviewhaiphong_category_follow-btn:hover {
        background: #2d4373;
    }

    .reviewhaiphong_category_stats {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-top: 20px;
    }

    .reviewhaiphong_category_stat {
        text-align: center;
    }

    .reviewhaiphong_category_stat-number {
        font-size: 24px;
        font-weight: 700;
        color: #333;
    }

    .reviewhaiphong_category_stat-label {
        font-size: 13px;
        color: #999;
    }

    /* Main Content Layout */
    .reviewhaiphong_category_main-content {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 30px;
    }

    /* Post List */
    .reviewhaiphong_category_posts-count {
        background: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        color: #e74c3c;
        font-weight: 600;
    }

    .reviewhaiphong_category_post-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .reviewhaiphong_category_post-item {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        display: flex;
        gap: 20px;
        padding: 20px;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .reviewhaiphong_category_post-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .reviewhaiphong_category_post-thumbnail {
        width: 200px;
        flex-shrink: 0;
        position: relative;
    }

    .reviewhaiphong_category_post-thumbnail img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 6px;
    }

    .reviewhaiphong_category_post-tag {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #e74c3c;
        color: #fff;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .reviewhaiphong_category_post-content {
        flex: 1;
    }

    .reviewhaiphong_category_post-title {
        font-size: 18px;
        font-weight: 700;
        line-height: 1.4;
        margin-bottom: 10px;
    }

    .reviewhaiphong_category_post-title a {
        color: #333;
        text-decoration: none;
    }

    .reviewhaiphong_category_post-title a:hover {
        color: #e74c3c;
    }

    .reviewhaiphong_category_post-excerpt {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .reviewhaiphong_category_post-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 12px;
        color: #999;
        flex-wrap: wrap;
    }

    .reviewhaiphong_category_post-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .reviewhaiphong_category_post-author {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #667eea;
        font-weight: 600;
    }

    .reviewhaiphong_category_post-author i {
        font-size: 14px;
    }

    /* Pagination */
    .reviewhaiphong_category_pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 40px;
        padding: 20px 0;
        flex-wrap: wrap;
    }

    .reviewhaiphong_category_page-btn {
        min-width: 40px;
        height: 40px;
        border: 1px solid #e5e5e5;
        background: #fff;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #333;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        text-decoration: none;
        padding: 0 12px;
    }

    .reviewhaiphong_category_page-btn:hover,
    .reviewhaiphong_category_page-btn.reviewhaiphong_category_active {
        background: #e74c3c;
        color: #fff;
        border-color: #e74c3c;
    }

    .reviewhaiphong_category_page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Sidebar */
    .reviewhaiphong_category_sidebar {
        position: sticky;
        top: 80px;
        height: fit-content;
    }

    .reviewhaiphong_category_widget {
        background: #fff;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 25px;
    }

    .reviewhaiphong_category_widget-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #333;
    }

    /* Editor's Pick */
    .reviewhaiphong_category_editor-pick {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f0f0f0;
    }

    .reviewhaiphong_category_editor-pick:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .reviewhaiphong_category_editor-pick img {
        width: 80px;
        height: 80px;
        border-radius: 6px;
        object-fit: cover;
        flex-shrink: 0;
    }

    .reviewhaiphong_category_editor-pick-content {
        flex: 1;
    }

    .reviewhaiphong_category_editor-pick-title {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
        margin-bottom: 5px;
    }

    .reviewhaiphong_category_editor-pick-title a {
        color: #333;
        text-decoration: none;
    }

    .reviewhaiphong_category_editor-pick-title a:hover {
        color: #e74c3c;
    }

    .reviewhaiphong_category_editor-pick-meta {
        font-size: 11px;
        color: #999;
    }

    /* Hot Topics */
    .reviewhaiphong_category_hot-topics {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .reviewhaiphong_category_hot-topic {
        padding: 15px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .reviewhaiphong_category_hot-topic:last-child {
        border-bottom: none;
    }

    .reviewhaiphong_category_hot-topic-number {
        font-size: 24px;
        font-weight: 700;
        color: #e74c3c;
        margin-bottom: 8px;
    }

    .reviewhaiphong_category_hot-topic-title {
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
        margin-bottom: 5px;
    }

    .reviewhaiphong_category_hot-topic-title a {
        color: #333;
        text-decoration: none;
    }

    .reviewhaiphong_category_hot-topic-title a:hover {
        color: #e74c3c;
    }

    .reviewhaiphong_category_hot-topic-excerpt {
        font-size: 12px;
        color: #666;
        line-height: 1.5;
    }

    /* Top Contributors */
    .reviewhaiphong_category_contributor {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .reviewhaiphong_category_contributor:last-child {
        border-bottom: none;
    }

    .reviewhaiphong_category_contributor-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .reviewhaiphong_category_contributor-info h4 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 3px;
        color: #333;
    }

    .reviewhaiphong_category_contributor-info p {
        font-size: 11px;
        color: #999;
        margin: 0;
    }

    /* Back to Top Button */
    .reviewhaiphong_category_back-to-top {
        position: fixed;
        bottom: 25px;
        left: 30px;
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

    .reviewhaiphong_category_back-to-top:hover {
        background: #c0392b;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }

    .reviewhaiphong_category_back-to-top.show {
        display: flex;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .reviewhaiphong_category_main-content {
            grid-template-columns: 1fr;
        }

        .reviewhaiphong_category_sidebar {
            position: static;
        }
    }

    @media (max-width: 768px) {
        .reviewhaiphong_category_post-item {
            flex-direction: column;
        }

        .reviewhaiphong_category_post-thumbnail {
            width: 100%;
        }

        .reviewhaiphong_category_agency-banner {
            flex-direction: column;
            text-align: center;
        }

        .reviewhaiphong_category_agency-logo {
            width: 120px;
            height: 120px;
            font-size: 50px;
        }

        .reviewhaiphong_category_agency-image {
            text-align: center;
        }

        .reviewhaiphong_category_back-to-top {
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
        }
    }
</style>

<!-- Agency Header Banner -->
{{-- <div class="reviewhaiphong_category_agency-header">
    <div class="reviewhaiphong_category_container">
        <div class="reviewhaiphong_category_agency-banner">
            <div class="reviewhaiphong_category_agency-info">
                <span class="reviewhaiphong_category_agency-badge">DANH MỤC</span>
                <h1 class="reviewhaiphong_category_agency-title">{{ strtoupper($category->name) }}</h1>
                <p class="reviewhaiphong_category_agency-subtitle">{{ $category->description ?? 'Khám phá các bài viết về ' . $category->name }}</p>
            </div>
            <div class="reviewhaiphong_category_agency-logo">
                {{ strtoupper(mb_substr($category->name, 0, 1)) }}
            </div>
            <div class="reviewhaiphong_category_agency-image">
                @if($category->meta_image ?? false)
                <img src="{{ asset('/client/assets/images/categories/' . $category->meta_image) }}" alt="{{ $category->name }}">
                @else
                <img src="https://images.unsplash.com/photo-1557804506-669a67965ba0?w=400&h=200&fit=crop" alt="{{ $category->name }}">
                @endif
            </div>
        </div>
    </div>
</div> --}}

<!-- Agency Description -->
<div class="reviewhaiphong_category_container">
    {{-- <div class="reviewhaiphong_category_agency-desc">
        <h2>{{ $category->name }}</h2>
        <p>{{ $category->description ?? 'Khám phá các bài viết, đánh giá và chia sẻ kinh nghiệm về ' . $category->name . '. Tổng hợp những thông tin hữu ích và cập nhật nhất.' }}</p>
        
        @if($settings['facebook_link'] ?? false)
        <a href="{{ $settings['facebook_link'] }}" target="_blank" class="reviewhaiphong_category_follow-btn">
            <i class="fa fa-facebook"></i>
            Theo dõi
        </a>
        @endif

        <div class="reviewhaiphong_category_stats">
            <div class="reviewhaiphong_category_stat">
                <div class="reviewhaiphong_category_stat-number">{{ number_format($postsCount ?? 0) }}</div>
                <div class="reviewhaiphong_category_stat-label">Bài viết</div>
            </div>
            <div class="reviewhaiphong_category_stat">
                <div class="reviewhaiphong_category_stat-number">{{ number_format($commentsCount ?? 0) }}</div>
                <div class="reviewhaiphong_category_stat-label">Bình luận</div>
            </div>
        </div>
    </div> --}}

    <!-- Main Content -->
    <div class="reviewhaiphong_category_main-content">
        <!-- Posts List -->
        <div>
            <div class="reviewhaiphong_category_posts-count">+ {{ number_format($postsCount ?? 0) }} bài viết</div>

            @if($posts->count() > 0)
            <div class="reviewhaiphong_category_post-list">
                @foreach($posts as $post)
                <article class="reviewhaiphong_category_post-item">
                    <div class="reviewhaiphong_category_post-thumbnail">
                        <a href="/bai-viet/{{ $post->slug }}">
                            <img src="{{ $post->seo_image ? asset('/client/assets/images/posts/' . $post->seo_image) : asset('/client/assets/images/default-post.jpg') }}" alt="{{ $post->seo_title ?? 'Post' }}" loading="lazy">
                        </a>
                        <span class="reviewhaiphong_category_post-tag">{{ strtoupper($category->name) }}</span>
                    </div>
                    <div class="reviewhaiphong_category_post-content">
                        <h3 class="reviewhaiphong_category_post-title">
                            <a href="/bai-viet/{{ $post->slug }}">{{ $post->seo_title ?? 'Tiêu đề bài viết' }}</a>
                        </h3>
                        <p class="reviewhaiphong_category_post-excerpt">
                            {{ \Illuminate\Support\Str::limit(strip_tags($post->seo_desc ?? ''), 150) }}
                        </p>
                        <div class="reviewhaiphong_category_post-meta">
                            <span class="reviewhaiphong_category_post-author">
                                <i class="fa fa-user"></i>
                                {{ $post->account->profile->name ?? $post->account->username ?? 'Admin' }}
                            </span>
                            <span><i class="fa fa-calendar"></i> {{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') : 'N/A' }}</span>
                            <span><i class="fa fa-eye"></i> {{ number_format($post->views ?? 0) }} lượt xem</span>
                            @php
                                $commentCount = \App\Models\Comment::where('post_id', $post->id)->count();
                            @endphp
                            <span><i class="fa fa-comment"></i> {{ $commentCount }} bình luận</span>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="reviewhaiphong_category_pagination">
                @if($posts->onFirstPage())
                <span class="reviewhaiphong_category_page-btn" style="opacity: 0.5; cursor: not-allowed;"><i class="fa fa-chevron-left"></i></span>
                @else
                <a href="{{ $posts->previousPageUrl() }}" class="reviewhaiphong_category_page-btn"><i class="fa fa-chevron-left"></i></a>
                @endif

                @foreach(range(1, $posts->lastPage()) as $page)
                    @if($page == $posts->currentPage())
                    <span class="reviewhaiphong_category_page-btn reviewhaiphong_category_active">{{ $page }}</span>
                    @elseif($page == 1 || $page == $posts->lastPage() || ($page >= $posts->currentPage() - 2 && $page <= $posts->currentPage() + 2))
                    <a href="{{ $posts->url($page) }}" class="reviewhaiphong_category_page-btn">{{ $page }}</a>
                    @elseif($page == $posts->currentPage() - 3 || $page == $posts->currentPage() + 3)
                    <span class="reviewhaiphong_category_page-btn" style="cursor: default;">...</span>
                    @endif
                @endforeach

                @if($posts->hasMorePages())
                <a href="{{ $posts->nextPageUrl() }}" class="reviewhaiphong_category_page-btn"><i class="fa fa-chevron-right"></i></a>
                @else
                <span class="reviewhaiphong_category_page-btn" style="opacity: 0.5; cursor: not-allowed;"><i class="fa fa-chevron-right"></i></span>
                @endif
            </div>
            @else
            <div style="background: #fff; padding: 40px; border-radius: 8px; text-align: center;">
                <h3 style="color: #666; margin-bottom: 15px;">Chưa có bài viết nào trong danh mục {{ $category->name }}</h3>
                <p style="color: #999; margin-bottom: 20px;">Chúng tôi đang chuẩn bị nội dung thú vị cho danh mục này. Hãy quay lại sau nhé!</p>
                <a href="/" style="display: inline-block; padding: 12px 25px; background: #e74c3c; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600;">Về trang chủ</a>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <aside class="reviewhaiphong_category_sidebar">
            <!-- Editor's Picks Widget -->
            @if(isset($editorPicks) && $editorPicks->count() > 0)
            <div class="reviewhaiphong_category_widget">
                <h3 class="reviewhaiphong_category_widget-title"><i class="fa fa-star"></i> Lựa chọn của biên tập</h3>

                @foreach($editorPicks as $pick)
                <div class="reviewhaiphong_category_editor-pick">
                    <a href="/bai-viet/{{ $pick->slug }}">
                        <img src="{{ $pick->seo_image ? asset('/client/assets/images/posts/' . $pick->seo_image) : asset('/client/assets/images/default-post.jpg') }}" alt="{{ $pick->seo_title ?? 'Pick' }}" loading="lazy">
                    </a>
                    <div class="reviewhaiphong_category_editor-pick-content">
                        <h4 class="reviewhaiphong_category_editor-pick-title">
                            <a href="/bai-viet/{{ $pick->slug }}">{{ \Illuminate\Support\Str::limit($pick->seo_title ?? 'Tiêu đề bài viết', 60) }}</a>
                        </h4>
                        <div class="reviewhaiphong_category_editor-pick-meta">
                            {{ $pick->published_at ? \Carbon\Carbon::parse($pick->published_at)->format('d/m/Y') : 'N/A' }} • {{ number_format($pick->views ?? 0) }} lượt xem
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Hot Topics Widget -->
            @if(isset($hotTopics) && $hotTopics->count() > 0)
            <div class="reviewhaiphong_category_widget">
                <h3 class="reviewhaiphong_category_widget-title"><i class="fa fa-fire"></i> Bài hot trong tuần</h3>

                <ul class="reviewhaiphong_category_hot-topics">
                    @foreach($hotTopics as $index => $topic)
                    <li class="reviewhaiphong_category_hot-topic">
                        <div class="reviewhaiphong_category_hot-topic-number">#{{ $index + 1 }}</div>
                        <h4 class="reviewhaiphong_category_hot-topic-title">
                            <a href="/bai-viet/{{ $topic->slug }}">{{ \Illuminate\Support\Str::limit($topic->seo_title ?? 'Tiêu đề bài viết', 80) }}</a>
                        </h4>
                        <p class="reviewhaiphong_category_hot-topic-excerpt">{{ \Illuminate\Support\Str::limit($topic->seo_desc ?? '', 100) }}</p>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Top Contributors Widget -->
            @if(isset($topContributors) && $topContributors->count() > 0)
            <div class="reviewhaiphong_category_widget">
                <h3 class="reviewhaiphong_category_widget-title"><i class="fa fa-users"></i> Tác giả nổi bật</h3>

                @foreach($topContributors as $contributor)
                <div class="reviewhaiphong_category_contributor">
                    @if($contributor->profile && $contributor->profile->avatar)
                    <img src="{{ asset('storage/' . $contributor->profile->avatar) }}" alt="{{ $contributor->profile->name ?? $contributor->username }}" class="reviewhaiphong_category_contributor-avatar">
                    @else
                    <img src="https://i.pravatar.cc/100?img={{ $loop->index + 1 }}" alt="{{ $contributor->profile->name ?? $contributor->username }}" class="reviewhaiphong_category_contributor-avatar">
                    @endif
                    <div class="reviewhaiphong_category_contributor-info">
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
<div class="reviewhaiphong_category_back-to-top" id="backToTop">
    <i class="fa fa-arrow-up"></i>
</div>

<script>
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

    // Lazy Loading Images
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
    document.querySelectorAll('.reviewhaiphong_category_post-meta span').forEach(span => {
        const text = span.textContent;
        if (text.includes('lượt xem')) {
            const viewNumber = parseInt(text.replace(/\D/g, ''));
            if (viewNumber >= 1000) {
                span.innerHTML = '<i class="fa fa-eye"></i> ' + (viewNumber / 1000).toFixed(1) + 'K lượt xem';
            }
        }
    });
</script>
@endsection
