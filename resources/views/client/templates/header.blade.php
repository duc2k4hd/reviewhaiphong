<header class="modern-header">
    <!-- Main Header -->
    <div class="main-header">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <div class="logo-section">
                    <a href="/" class="logo">
                        <img src="{{ asset('/client/assets/images/logo/'. ($settings['site_logo'] ?? 'logo.png')) }}" 
                             alt="{{ $settings['site_name'] ?? 'Review Hải Phòng' }}" 
                             class="logo-img">
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <form class="search-form" id="search-form" onsubmit="return redirectToSearch()">
                        <div class="search-input-group">
                            <input type="text" 
                                   id="keyword" 
                                   placeholder="Tìm kiếm bài viết, địa điểm, review..." 
                                   class="search-input">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Mobile Menu Toggle -->
                <div class="mobile-menu-toggle">
                    <button class="menu-toggle-btn" id="mobile-menu-toggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="main-navigation">
        <div class="container">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/" class="nav-link {{ request()->is('/') ? 'active' : '' }}">
                        <i class="fas fa-home"></i>
                        <span>Trang chủ</span>
                    </a>
                </li>
                @if(isset($navCategories) && count($navCategories) > 0)
                    @foreach($navCategories as $category)
                        @php
                            $isActive = request()->is($category->slug . '*');
                            $hasChildren = isset($category->children) && $category->children->count() > 0;
                        @endphp
                        @if ($category->slug == 'hai-phong-life')
                            <li class="nav-item">
                                <a href="https://haiphonglife.com/shop" class="nav-link {{ $isActive ? 'active' : '' }}">
                                    <span>{{ $category->name }}</span>
                                </a>
                            </li>
                            @break
                        @endif
                        <li class="nav-item {{ $hasChildren ? 'dropdown' : '' }}">
                            <a href="/{{ $category->slug }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                                <span>{{ $category->name }}</span>
                            </a>
                            @if($hasChildren)
                                <div class="dropdown-menu">
                                    @foreach($category->children as $child)
                                        <a href="/{{ $child->slug }}" class="dropdown-item {{ request()->is($child->slug . '*') ? 'active' : '' }}">{{ $child->name }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                        
                    @endforeach
                @else
                    <!-- Fallback navigation -->
                    <li class="nav-item">
                        <a href="/am-thuc" class="nav-link {{ request()->is('am-thuc*') ? 'active' : '' }}">
                            <span>Ẩm thực</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/du-lich" class="nav-link {{ request()->is('du-lich*') ? 'active' : '' }}">
                            <span>Du lịch</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/check-in" class="nav-link {{ request()->is('check-in*') ? 'active' : '' }}">
                            <span>Check-in</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/tin-tuc" class="nav-link {{ request()->is('tin-tuc*') ? 'active' : '' }}">
                            <span>Tin tức</span>
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a href="/lien-he" class="nav-link {{ request()->is('lien-he*') ? 'active' : '' }}">
                        <i class="fas fa-envelope"></i>
                        <span>Liên hệ</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</header>