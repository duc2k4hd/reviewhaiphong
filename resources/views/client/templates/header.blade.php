<header class="header">
    <div class="header-top">
        <div class="date-location">
            <div class="location">
                <p>Hải Phòng</p>
            </div>
            <div class="temperature">
                <p>30°C</p>
            </div>
            <div class="date">
                <p>Thứ 2, 05/05/2025</p>
            </div>
        </div>
        <div class="social">
            <ul>
                <li><a target="_blank" href="{{ $settings['facebook_link'] }}"><i class="fa-brands fa-facebook"></i></a></li>
                <li><a target="_blank" href="{{ $settings['twitter_link'] }}"><i class="fa-brands fa-twitter"></i></a></li>
                <li><a target="_blank" href="{{ $settings['instagram_link'] }}"><i class="fa-brands fa-square-instagram"></i></a></li>
                <li><a target="_blank" href="#"><i class="fa-brands fa-youtube"></i></a></li>
            </ul>
        </div>
    </div>
    <div class="header-logo">
        <div class="logo">
            <img src="{{ asset('/client/assets/images/logo/'. $settings['site_logo']) }}" alt="{{ $settings['site_name'] }}">
        </div>
        <div class="bars-mobile"><i class="fa-solid fa-magnifying-glass"></i><i class="fa-solid fa-bars"></i></div>
        <div class="advertisement">
            <a href=""><img src="{{ asset('/client/assets/images/ads/'. $settings['logo_ads_1']) }}" alt="{{ $settings['site_name'] }}"></a>
        </div>
    </div>
    
    <div class="header-menu">
        <div class="menu">
            
        </div>
        <div class="search">
            <form id="search-form" onsubmit="return redirectToSearch()">
                <input type="text" id="keyword" placeholder="Tìm kiếm bài viết">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
        <div class="close-mobile"><i class="fa-solid fa-xmark"></i></div>
    </div>
</header>