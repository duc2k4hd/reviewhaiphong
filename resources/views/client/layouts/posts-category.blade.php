<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="mask-icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">
    <link rel="alternate" hreflang="vi" href="{{ url()->current() }}" />
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}" />
    <link rel="icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" type="image/x-icon">
    @include('client.module.css')
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Khám phá {{ $category->name }}</title>
</head>

<body>
    @include('client.templates.loading')
    <div class="review-haiphong">
        @include('client.templates.header')
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/{{ $settings['site_slug'] }}">{{ $settings['site_name'] }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
            </ol>
        </nav>
        <h1 style="text-align: center; font-size: 22px; margin: 20px 0;">Khám phá {{ $category->name }}</h1>
        @if (count($posts) > 0)
            <div class="posts-category">
                <main class="news-feed">
                    <h2 class="section-title">Bài viết {{ $category->name }}</h2>
                    @php
                        $rand = rand(0, count($posts) - 1);
                        $post = $posts[$rand];
                    @endphp
                    <article class="featured-news">
                        <div class="news-image">
                            <a href="/{{ $category->slug . '/' . $post->slug }}"><img
                                    src="/client/assets/images/posts/{{ $post->seo_image }}"
                                    alt="{{ $post->seo_title }}" title="{{ $post->seo_title }}"></a>
                        </div>
                        <div class="news-content">
                            <h3>{{ $post->seo_title }}</h3>
                            <p>{{ $post->seo_desc }}</p>
                            <div class="news-meta">
                                <span class="author">{{ $post->account->profile->name }}</span>
                                <span
                                    class="date">{{ \Carbon\Carbon::parse($post->published_at)->format('d/m/y') }}</span>
                            </div>
                        </div>
                    </article>
                    @php
                        $items = $posts->items();
                        unset($items[$rand]);
                        $postItems = array_values($items);
                    @endphp

                    @foreach ($postItems as $item)
                        <article class="news-item">
                            <div class="news-image">
                                <a href="/{{ $category->slug . '/' . $item->slug }}"><img
                                        src="/client/assets/images/posts/{{ $item->seo_image }}"
                                        alt="{{ $item->seo_title }}" title="{{ $item->seo_title }}"></a>
                            </div>
                            <div class="news-content">
                                <h3>{{ $item->seo_title }}</h3>
                                <p>{{ $item->seo_desc }}</p>
                                <div class="news-meta">
                                    <span class="author">{{ $item->account->profile->name }}</span>
                                    <span
                                        class="date">{{ \Carbon\Carbon::parse($item->published_at)->format('d/m/y') }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                    <div class="pagination">
                        {{-- Previous button --}}
                        @if ($posts->currentPage() > 1)
                            <a href="{{ $posts->previousPageUrl() }}" class="prev-page">Trước</a>
                        @endif

                        {{-- Current page status (luôn hiển thị) --}}
                        <span class="current-page">
                            Trang {{ $posts->currentPage() }} / {{ $posts->lastPage() }}
                        </span>

                        {{-- Next button --}}
                        @if ($posts->hasMorePages())
                            <a href="{{ $posts->nextPageUrl() }}" class="next-page">Sau</a>
                        @endif
                    </div>
                    <style>
                        .pagination {
                            margin: 30px auto !important;
                            text-align: center;
                        }

                        .pagination a,
                        .pagination span {
                            display: inline-block;
                            margin: 0 5px;
                            padding: 6px 12px;
                            color: #0e0620;
                            background: #f0f0f0;
                            text-decoration: none;
                            border-radius: 4px;
                            font-weight: bold;
                        }

                        .pagination a:hover {
                            background: #2ccf6d;
                            color: #fff;
                        }

                        .pagination .current-page {
                            background: #2ccf6d;
                            color: #fff;
                        }
                    </style>

                </main>

                <aside class="sidebar">
                    <div class="sidebar-section">
                        <h2 class="sidebar-title">Bài viết nổi bật</h2>

                        @foreach ($postsViews as $item)
                            <div class="sidebar-item">
                                <div class="sidebar-image">
                                    <a href="/{{ $category->slug . '/' . $item->slug }}"><img
                                            src="/client/assets/images/posts/{{ $item->seo_image }}"
                                            alt="{{ $item->seo_title }}" title="{{ $item->seo_title }}"></a>
                                </div>
                                <div class="sidebar-content">
                                    <h4>{{ $item->seo_title }}</h4>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="sidebar-ad">
                        <a href="#" class="ad-banner">
                            <img src="/client/assets/images/ads/{{ $settings['logo_ads_1'] }}" alt="Liên hệ booking"
                                title="Liên hệ booking">
                        </a>
                    </div>
                </aside>
            </div>
        @else
            <div class="alert alert-warning" role="alert">
                Không tìm thấy bài viết nào phù hợp với yêu cầu của bạn.
            </div>
        @endif
        @include('client.templates.footer')
    </div>
    @include('client.templates.chat')
    @include('client.module.js')
</body>

</html>
