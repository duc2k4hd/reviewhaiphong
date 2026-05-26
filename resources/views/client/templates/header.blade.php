@push('styles')
    <!-- CSS is now handled in module/css.blade.php globally -->
@endpush

<!-- TOP BAR -->
<div class="reviewhaiphong_home_topbar">
    <div class="reviewhaiphong_home_topbar_inner">
        <div class="reviewhaiphong_home_topbar_links">
            <a href="/">Trang chủ</a>
            <a href="/gioi-thieu">Về chúng tôi</a>
            <a href="/lien-he">Liên hệ</a>
            <a href="/lien-he">Quảng cáo</a>
        </div>
        <div class="reviewhaiphong_home_topbar_social">
            @if(isset($settings['facebook_link']) && $settings['facebook_link'])
                <a href="{{ $settings['facebook_link'] }}" target="_blank">📘 Facebook</a>
            @endif
            @if(isset($settings['instagram_link']) && $settings['instagram_link'])
                <a href="{{ $settings['instagram_link'] }}" target="_blank">📸 Instagram</a>
            @endif
            @if(isset($settings['youtube_link']) && $settings['youtube_link'])
                <a href="{{ $settings['youtube_link'] }}" target="_blank">🎬 YouTube</a>
            @endif
        </div>
    </div>
</div>

<!-- HEADER -->
<header class="reviewhaiphong_home_header">
    <div class="reviewhaiphong_home_header_inner">
        <!-- Logo -->
        <a href="/" class="reviewhaiphong_home_logo">
            <img src="{{ asset('client/assets/images/logo/logo-review-hai-phong.png') }}" alt="Review Hải Phòng" style="max-height: 55px; width: auto;">
        </a>
        <!-- Search -->
        <div class="reviewhaiphong_home_search_wrap">
            <form id="search-form" class="reviewhaiphong_home_search_form" onsubmit="return redirectToSearch()">
                <input class="reviewhaiphong_home_search_input" type="text" id="keyword" placeholder="Tìm kiếm địa điểm, quán ăn...">
                <button type="submit" class="reviewhaiphong_home_search_btn">🔍</button>
            </form>
        </div>
        <!-- Actions -->
        <div class="reviewhaiphong_home_header_actions">
            <a href="/" class="reviewhaiphong_home_header_action_btn">
                <span class="reviewhaiphong_home_icon">🏠</span>
                <span class="reviewhaiphong_home_action_label">Trang chủ</span>
            </a>
            <a href="/am-thuc" class="reviewhaiphong_home_header_action_btn">
                <span class="reviewhaiphong_home_icon">🍜</span>
                <span class="reviewhaiphong_home_action_label">Ẩm thực</span>
            </a>
            <a href="/du-lich" class="reviewhaiphong_home_header_action_btn">
                <span class="reviewhaiphong_home_icon">✈️</span>
                <span class="reviewhaiphong_home_action_label">Du lịch</span>
            </a>
            <a href="/check-in" class="reviewhaiphong_home_header_action_btn">
                <span class="reviewhaiphong_home_icon">📸</span>
                <span class="reviewhaiphong_home_action_label">Check-in</span>
            </a>
            <!-- Mobile hamburger -->
            <button class="reviewhaiphong_home_menu_toggle" id="reviewhaiphong_home_menu_toggle"
                aria-label="Mở menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>

<!-- NAV BAR -->
<nav class="reviewhaiphong_home_navbar">
    <div class="reviewhaiphong_home_navbar_inner">
        <ul class="reviewhaiphong_home_nav_list">
            <li class="reviewhaiphong_home_nav_item reviewhaiphong_home_nav_home {{ request()->is('/') ? 'reviewhaiphong_home_nav_current' : '' }}">
                <a href="/">🏠 Trang chủ</a>
            </li>
            <li class="reviewhaiphong_home_nav_item {{ request()->is('gioi-thieu') ? 'reviewhaiphong_home_nav_current' : '' }}">
                <a href="/gioi-thieu">Giới thiệu</a>
            </li>
            @if(isset($navCategories) && count($navCategories) > 0)
                @foreach($navCategories as $index => $category)
                    @if($index == 6)
                        <!-- Khám phá thêm Mega Menu -->
                        <li class="reviewhaiphong_home_nav_item reviewhaiphong_has_dropdown reviewhaiphong_mega_menu_parent">
                            <a href="javascript:void(0)">Khám phá thêm <i class="fa-solid fa-angle-down" style="font-size: 0.8em; margin-left: 2px;"></i></a>
                            <ul class="reviewhaiphong_home_nav_dropdown reviewhaiphong_mega_menu">
                                <div class="reviewhaiphong_mega_menu_inner">
                    @endif

                    @if($index >= 6)
                                    <div class="reviewhaiphong_mega_menu_column">
                                        <a href="/{{ $category->slug }}" class="reviewhaiphong_mega_menu_title">{{ $category->name }}</a>
                                        @if(isset($category->children) && count($category->children) > 0)
                                            <ul class="reviewhaiphong_mega_menu_list">
                                                @foreach($category->children as $child)
                                                    <li><a href="/{{ $child->slug }}">{{ $child->name }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                    @else
                        @php
                            $isActive = request()->is($category->slug . '*');
                        @endphp
                        @if ($category->slug == 'hai-phong-life')
                            <li class="reviewhaiphong_home_nav_item {{ $isActive ? 'reviewhaiphong_home_nav_current' : '' }}">
                                <a href="https://haiphonglife.com/shop">{{ $category->name }}</a>
                            </li>
                            @continue
                        @endif
                        <li class="reviewhaiphong_home_nav_item {{ $isActive ? 'reviewhaiphong_home_nav_current' : '' }} {{ isset($category->children) && count($category->children) > 0 ? 'reviewhaiphong_has_dropdown' : '' }}">
                            <a href="/{{ $category->slug }}">{{ $category->name }} @if(isset($category->children) && count($category->children) > 0)<i class="fa-solid fa-angle-down" style="font-size: 0.8em; margin-left: 2px;"></i>@endif</a>
                            @if(isset($category->children) && count($category->children) > 0)
                                <ul class="reviewhaiphong_home_nav_dropdown">
                                    @foreach($category->children as $child)
                                        <li><a href="/{{ $child->slug }}">{{ $child->name }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endif
                @endforeach
                @if(count($navCategories) > 6)
                                </div>
                            </ul>
                        </li>
                @endif
            @else
                <li class="reviewhaiphong_home_nav_item {{ request()->is('am-thuc*') ? 'reviewhaiphong_home_nav_current' : '' }}"><a href="/am-thuc">Ẩm thực</a></li>
                <li class="reviewhaiphong_home_nav_item {{ request()->is('du-lich*') ? 'reviewhaiphong_home_nav_current' : '' }}"><a href="/du-lich">Du lịch</a></li>
                <li class="reviewhaiphong_home_nav_item {{ request()->is('check-in*') ? 'reviewhaiphong_home_nav_current' : '' }}"><a href="/check-in">Check-in</a></li>
                <li class="reviewhaiphong_home_nav_item {{ request()->is('tin-tuc*') ? 'reviewhaiphong_home_nav_current' : '' }}"><a href="/tin-tuc">Tin tức HP</a></li>
            @endif
        </ul>
    </div>
</nav>

<!-- MOBILE NAV -->
<div class="reviewhaiphong_home_mobile_nav" id="reviewhaiphong_home_mobile_nav">
    <div class="reviewhaiphong_home_mobile_nav_panel">
        <div class="reviewhaiphong_home_mobile_nav_head">
            <span>ReviewHảiPhòng</span>
            <button class="reviewhaiphong_home_mobile_nav_close" id="reviewhaiphong_home_mobile_close">✕</button>
        </div>
        <ul class="reviewhaiphong_home_mobile_nav_list">
            <li><a href="/">🏠 Trang chủ</a></li>
            <li><a href="/gioi-thieu">ℹ️ Giới thiệu</a></li>
            @if(isset($navCategories) && count($navCategories) > 0)
                @foreach($navCategories as $category)
                    @if ($category->slug == 'hai-phong-life')
                        <li><a href="https://haiphonglife.com/shop">🛍️ {{ $category->name }}</a></li>
                        @continue
                    @endif
                    <li><a href="/{{ $category->slug }}">🔹 {{ $category->name }}</a></li>
                @endforeach
            @else
                <li><a href="/am-thuc">🍜 Ẩm thực</a></li>
                <li><a href="/du-lich">✈️ Du lịch</a></li>
                <li><a href="/check-in">📸 Check-in</a></li>
                <li><a href="/tin-tuc">📰 Tin tức HP</a></li>
            @endif
        </ul>
    </div>
</div>