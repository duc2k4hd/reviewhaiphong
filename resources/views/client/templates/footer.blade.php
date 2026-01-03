<footer class="footer">
    <!-- Subscribe Section -->
    <div class="subscribe-section">
        <div class="container">
            <div class="subscribe-form">
                <input type="email" placeholder="Nhập email của bạn...">
                <button type="submit">Đăng ký nhận thông báo</button>
            </div>
            <p class="subscribe-text">Đăng ký để theo dõi những thiết kế web mới và các cập nhật mới nhất. Hãy bắt đầu thôi!</p>
        </div>
    </div>

    <div class="footer-content">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col logo-col">
                    <div class="site-logo">
                        <div class="logo">
                            @if($settings['site_logo'] ?? false)
                                <img src="{{ asset('/client/assets/images/logo/' . $settings['site_logo']) }}" alt="{{ $settings['site_name'] ?? 'Review Hải Phòng' }}">
                            @else
                                <h3>{{ $settings['site_name'] ?? 'Review Hải Phòng' }}</h3>
                            @endif
                        </div>
                    </div>
                    <div class="social-icons">
                        @if(isset($settings['facebook_link']) && $settings['facebook_link'])
                            <a href="{{ $settings['facebook_link'] }}"><i class="fab fa-facebook-f"></i></a>
                        @endif
                        @if(isset($settings['twitter_link']) && $settings['twitter_link'])
                            <a href="{{ $settings['twitter_link'] }}"><i class="fab fa-twitter"></i></a>
                        @endif
                        @if(isset($settings['telegram_link']) && $settings['telegram_link'])
                            <a href="{{ $settings['telegram_link'] }}"><i class="fab fa-telegram"></i></a>
                        @endif
                        @if(isset($settings['discord_link']) && $settings['discord_link'])
                            <a href="{{ $settings['discord_link'] }}"><i class="fab fa-discord"></i></a>
                        @endif
                        @if(isset($settings['instagram_link']) && $settings['instagram_link'])
                            <a href="{{ $settings['instagram_link'] }}"><i class="fab fa-instagram"></i></a>
                        @endif
                    </div>
                    @include('client.templates.dmca')
                </div>

                <div class="footer-col">
                    <h6 class="footer-heading">Danh mục</h6>
                    <ul class="footer-links">
                        <li><a href="/">Review Hải Phòng</a></li>
                        <li><a href="/dich-vu">Dịch vụ</a></li>
                        <li><a href="/am-thuc">Ẩm thực</a></li>
                        <li><a href="/check-in">Check in</a></li>
                        <li><a href="/review-tong-hop">Review tổng hợp</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h6 class="footer-heading">Dịch vụ</h6>
                    <ul class="footer-links">
                        <li><a href="#">Review cửa hàng</a></li>
                        <li><a href="#">Review quán cà phê</a></li>
                        <li><a href="#">Review doanh nghiệp</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h6 class="footer-heading">Review Hải Phòng</h6>
                    <ul class="footer-links">
                        <li><a href="/gioi-thieu">Giới thiệu</a></li>
                        <li><a href="/tin-tuc">Tin tức</a></li>
                        <li><a href="/lien-he">Liên hệ</a></li>
                    </ul>
                </div>

                <div class="footer-col contact-col">
                    <h6 class="footer-heading">Liên hệ</h6>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>{{ $settings['contact_address'] ?? 'Hải Phòng' }}</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <p>{{ $settings['contact_email'] ?? 'contact@reviewhaiphong.io.vn' }}</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <p>{{ $settings['contact_phone'] ?? '0123 456 789' }}</p>
                        </div>
                        @if($settings['bo_cong_thuong'] ?? false)
                            <div class="contact-item">
                                <p><img style="margin-top: 10px;" alt="Bộ công thương đã duyệt" title="Bộ công thương đã duyệt" width="150px" height="55px" src="{{ asset('/client/assets/images/logo/' . $settings['bo_cong_thuong']) }}"></p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="copyright-section">
        <div class="container">
            @if(isset($settings['copyright']) && $settings['copyright'])
                {!! Blade::render($settings['copyright']) !!}
            @else
                <p>&copy; {{ date('Y') }} Review Hải Phòng. Tất cả quyền được bảo lưu.</p>
            @endif
        </div>
    </div>
</footer>