<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="{{ asset('/client/assets/images/'. $settings['site_favicon']) }}" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="mask-icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" type="image/x-icon">
    @include('client.module.css')
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Tìm kiếm</title>
</head>

<body>
    @include('client.templates.loading')
    <div class="review-haiphong">
        @include('client.templates.header')
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/{{ $settings['site_slug'] }}">{{ $settings['site_name'] }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Kết quả tìm kiếm cho từ khóa "{{ $keyword }}"</li>
            </ol>
        </nav>
        @if (count($posts) > 0)
            <div class="posts-category">
                <main class="news-feed">
                    <h2 class="section-title">Kết quả tìm kiếm cho từ khóa "{{ $keyword }}"</h2>

                    @foreach ($posts as $item)
                        <article class="news-item">
                            <div class="news-image">
                                <a href="/{{ $item->category->slug . '/' . $item->slug }}"><img
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
                </main>

                <aside class="sidebar">
                    <div class="sidebar-section">
                        <h2 class="sidebar-title">Bài viết nổi bật</h2>

                        @foreach ($postsViews as $item)
                            <div class="sidebar-item">
                                <div class="sidebar-image">
                                    <a href="/{{ $item->category->slug . '/' . $item->slug }}"><img
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
