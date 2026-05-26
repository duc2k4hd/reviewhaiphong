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
                        "@id": "{{ url('/' . $post->slug) }}",
                        "name": {!! json_encode($post->seo_title ?? $post->name ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                        "headline": {!! json_encode($post->seo_title ?? $post->name ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                        "description": {!! json_encode(\Illuminate\Support\Str::limit(strip_tags($post->seo_desc ?? ''), 200), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
                        "image": "{{ $post->seo_image ? asset('/client/assets/images/posts/' . $post->seo_image) : '' }}",
                        "url": "{{ url('/' . $post->slug) }}",
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

<!-- MAIN CONTENT -->
<div class="reviewhaiphong_home_main_layout">
    <!-- LEFT COLUMN -->
    <main class="reviewhaiphong_home_content_col">

        <!-- FEATURED HERO -->
        <div class="reviewhaiphong_home_section_block anim-fade-up reviewhaiphong_home_hero_block">
            <div class="reviewhaiphong_home_section_title reviewhaiphong_home_hero_title">
                <div class="reviewhaiphong_home_section_title_bar"></div>
                <span class="reviewhaiphong_home_section_title_text">Tin nổi bật</span>
                <a href="/tin-tuc" class="reviewhaiphong_home_section_title_link">Xem tất cả »</a>
            </div>
            <div class="reviewhaiphong_home_hero_grid">
                @if(isset($featuredPost) && $featuredPost)
                <a href="/{{ $featuredPost->slug }}" class="reviewhaiphong_home_hero_main">
                    <img src="{{ asset('/client/assets/images/posts/' . $featuredPost->seo_image) }}" alt="{{ $featuredPost->name }}" class="reviewhaiphong_home_hero_main_img">
                    <div class="reviewhaiphong_home_hero_main_caption">
                        <span class="reviewhaiphong_home_hero_main_tag">{{ $featuredPost->category->name ?? 'Tin tức' }}</span>
                        <h2 class="reviewhaiphong_home_hero_main_title">{{ $featuredPost->name }}</h2>
                    </div>
                </a>
                @endif
                <div class="reviewhaiphong_home_hero_sub">
                    @if(isset($sidePosts) && count($sidePosts) > 0)
                        @foreach($sidePosts->take(2) as $post)
                        <a href="/{{ $post->slug }}" class="reviewhaiphong_home_hero_sub_item">
                            <img src="{{ asset('/client/assets/images/posts/' . $post->seo_image) }}" alt="{{ $post->name }}" class="reviewhaiphong_home_hero_sub_img">
                            <div class="reviewhaiphong_home_hero_sub_caption">{{ $post->name }}</div>
                        </a>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        @if(isset($mainCategories) && count($mainCategories) > 0)
            @foreach($mainCategories as $index => $category)
            @php
                $posts = isset($postsByCategory[$category->slug]) ? $postsByCategory[$category->slug] : collect([]);
                $delay = $index % 3 + 1; // 1, 2, 3 for animation delays
            @endphp
            @if($posts->count() > 0)
            <div class="reviewhaiphong_home_section_block anim-fade-up anim-d-{{ $delay }}">
                <div class="reviewhaiphong_home_section_title">
                    <div class="reviewhaiphong_home_section_title_bar"></div>
                    <span class="reviewhaiphong_home_section_title_text">📌 {{ $category->name }}</span>
                    <a href="/{{ $category->slug }}" class="reviewhaiphong_home_section_title_link">Xem thêm »</a>
                </div>
                
                <div class="reviewhaiphong_home_card_grid">
                    @foreach($posts->take(3) as $post)
                    <a href="/{{ $post->slug }}" class="reviewhaiphong_home_card">
                        <div class="reviewhaiphong_home_card_img_wrap">
                            <img src="{{ asset('/client/assets/images/posts/' . $post->seo_image) }}" alt="{{ $post->name }}" class="reviewhaiphong_home_card_img">
                            <span class="reviewhaiphong_home_card_tag">{{ $category->name }}</span>
                        </div>
                        <div class="reviewhaiphong_home_card_body">
                            <div class="reviewhaiphong_home_card_title">{{ $post->name }}</div>
                            <div class="reviewhaiphong_home_card_meta">📅 {{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') }} · ⏱ {{ $post->reading_time }} phút đọc</div>
                            <div class="reviewhaiphong_home_card_excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($post->content), 100) }}</div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @if($posts->count() > 3)
                <div class="reviewhaiphong_home_loadmore_wrap">
                    <button class="reviewhaiphong_home_loadmore_btn" data-section="reviewhaiphong_home_{{ $category->slug }}_more">Xem thêm ▼</button>
                </div>
                <div id="reviewhaiphong_home_{{ $category->slug }}_more" style="display:none;">
                    <div class="reviewhaiphong_home_card_grid mt-10">
                        @foreach($posts->skip(3)->take(6) as $post)
                        <a href="/{{ $post->slug }}" class="reviewhaiphong_home_card">
                            <div class="reviewhaiphong_home_card_img_wrap">
                                <img src="{{ asset('/client/assets/images/posts/' . $post->seo_image) }}" alt="{{ $post->name }}" class="reviewhaiphong_home_card_img">
                                <span class="reviewhaiphong_home_card_tag">{{ $category->name }}</span>
                            </div>
                            <div class="reviewhaiphong_home_card_body">
                                <div class="reviewhaiphong_home_card_title">{{ $post->name }}</div>
                                <div class="reviewhaiphong_home_card_meta">📅 {{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') }}</div>
                                <div class="reviewhaiphong_home_card_excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($post->content), 80) }}</div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endif
            @endforeach
        @endif

    </main>

    <!-- RIGHT SIDEBAR -->
    <aside class="reviewhaiphong_home_sidebar anim-slide-right">
        <!-- Trending -->
        @if(isset($hotTopics) && count($hotTopics) > 0)
        <div class="reviewhaiphong_home_sidebar_box">
            <div class="reviewhaiphong_home_sidebar_title">🔥 Xem nhiều nhất</div>
            @foreach($hotTopics->take(10) as $index => $topic)
            <div class="reviewhaiphong_home_sidebar_trending_item">
                <div class="reviewhaiphong_home_sidebar_trending_num">{{ $index + 1 }}</div>
                <a href="/{{ $topic->slug }}" class="reviewhaiphong_home_sidebar_trending_title">{{ $topic->name }}</a>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Banner -->
        <div class="reviewhaiphong_home_sidebar_banner anim-zoom">
            <div class="reviewhaiphong_home_sidebar_banner_title">🏨 Đặt Phòng<br>Khách Sạn</div>
            <div class="reviewhaiphong_home_sidebar_banner_sub">Toàn quốc - Giá tốt nhất</div>
            <div class="reviewhaiphong_home_sidebar_banner_badge">Giảm 15–45%</div>
            <div style="font-size:0.72rem;opacity:0.85;margin-bottom:4px;">Áp dụng cho đặt phòng online</div>
            <a href="/khach-san" class="reviewhaiphong_home_sidebar_banner_btn">Đặt ngay →</a>
        </div>

        <!-- Editor Picks (Recent Posts) -->
        @if(isset($editorPicks) && count($editorPicks) > 0)
        <div class="reviewhaiphong_home_sidebar_box">
            <div class="reviewhaiphong_home_sidebar_title">📌 Lựa chọn của Biên tập viên</div>
            @foreach($editorPicks->take(4) as $post)
            <div class="reviewhaiphong_home_sidebar_thumb_item">
                <img src="{{ asset('/client/assets/images/posts/' . $post->seo_image) }}" alt="{{ $post->name }}" class="reviewhaiphong_home_sidebar_thumb_img">
                <div>
                    <a href="/{{ $post->slug }}" class="reviewhaiphong_home_sidebar_thumb_title">{{ $post->name }}</a>
                    <div class="reviewhaiphong_home_sidebar_thumb_meta">📅 {{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Tags -->
        @if(isset($popularTags) && count($popularTags) > 0)
        <div class="reviewhaiphong_home_sidebar_box">
            <div class="reviewhaiphong_home_sidebar_title">🏷️ Chủ đề hot</div>
            <div class="reviewhaiphong_home_tags_cloud">
                @foreach($popularTags as $tag)
                <a href="/search?keyword={{ urlencode($tag->name) }}" class="reviewhaiphong_home_tag_item">{{ $tag->name }}</a>
                @endforeach
            </div>
        </div>
        @endif
        
        <!-- Second Banner -->
        <div class="reviewhaiphong_home_sidebar_banner anim-zoom reviewhaiphong_home_tour_banner">
            <div style="font-size:0.75rem;opacity:0.9;margin-bottom:4px;">🌏 TOUR KHÁM PHÁ</div>
            <div class="reviewhaiphong_home_sidebar_banner_title">Cát Bà - Lan Hạ<br>Trọn gói 2N1Đ</div>
            <div class="reviewhaiphong_home_sidebar_banner_sub">Xuất phát từ Hải Phòng</div>
            <div class="reviewhaiphong_home_sidebar_banner_badge text-rhp-orange">Từ 890K/người
            </div>
            <a href="/du-lich" class="reviewhaiphong_home_sidebar_banner_btn text-rhp-orange">Đặt tour
                ngay →</a>
        </div>
    </aside>
</div>

<!-- TOUR DESTINATIONS SECTION -->
<div class="reviewhaiphong_home_tour_section anim-fade-up mb-20">
    <div class="reviewhaiphong_home_container">
        <div class="reviewhaiphong_home_tour_title">🗺️ Tour trọn gói - Vòng quanh thế giới</div>
        <div class="reviewhaiphong_home_tour_grid">
            <a href="/search?keyword=Nhật+Bản" class="reviewhaiphong_home_tour_item">
                <img src="https://images.unsplash.com/photo-1528360983277-13d401cdc186?w=300&q=80" alt="Nhật Bản"
                    class="reviewhaiphong_home_tour_item_img">
                <div class="reviewhaiphong_home_tour_item_label">🇯🇵 Nhật Bản</div>
            </a>
            <a href="/search?keyword=Hàn+Quốc" class="reviewhaiphong_home_tour_item">
                <img src="https://images.unsplash.com/photo-1538970272646-f61fabb3a8a2?w=300&q=80" alt="Hàn Quốc"
                    class="reviewhaiphong_home_tour_item_img">
                <div class="reviewhaiphong_home_tour_item_label">🇰🇷 Hàn Quốc</div>
            </a>
            <a href="/search?keyword=Thái+Lan" class="reviewhaiphong_home_tour_item">
                <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=300&q=80" alt="Thái Lan"
                    class="reviewhaiphong_home_tour_item_img">
                <div class="reviewhaiphong_home_tour_item_label">🇹🇭 Thái Lan</div>
            </a>
            <a href="/search?keyword=Singapore" class="reviewhaiphong_home_tour_item">
                <img src="https://images.unsplash.com/photo-1570168007204-dfb528c6958f?w=300&q=80" alt="Singapore"
                    class="reviewhaiphong_home_tour_item_img">
                <div class="reviewhaiphong_home_tour_item_label">🇸🇬 Singapore</div>
            </a>
            <a href="/search?keyword=Châu+Âu" class="reviewhaiphong_home_tour_item">
                <img src="https://images.unsplash.com/photo-1488085061387-422e29b40080?w=300&q=80" alt="Châu Âu"
                    class="reviewhaiphong_home_tour_item_img">
                <div class="reviewhaiphong_home_tour_item_label">🌍 Châu Âu</div>
            </a>
            <a href="/search?keyword=Maldives" class="reviewhaiphong_home_tour_item">
                <img src="https://images.unsplash.com/photo-1510414842594-a61c69b5ae57?w=300&q=80" alt="Maldives"
                    class="reviewhaiphong_home_tour_item_img">
                <div class="reviewhaiphong_home_tour_item_label">🏝️ Maldives</div>
            </a>
        </div>
    </div>
</div>

@endsection

@section('footer')
    @include('client.templates.footer')
@endsection
