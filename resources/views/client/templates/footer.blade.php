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
                            <img src="/client/assets/images/logo/{{ $settings['site_logo'] }}" alt="Logo Review Hải Phòng">
                        </div>
                    </div>
                    <div class="social-icons">
                        <a href="{{ $settings['facebook_link'] }}"><i class="fab fa-facebook-f"></i></a>
                        <a href="{{ $settings['twitter_link'] }}"><i class="fab fa-twitter"></i></a>
                        <a href="{{ $settings['telegram_link'] }}"><i class="fab fa-telegram"></i></a>
                        <a href="{{ $settings['discord_link'] }}"><i class="fab fa-discord"></i></a>
                        <a href="{{ $settings['instagram_link'] }}"><i class="fab fa-instagram"></i></a>
                    </div>
                    @include('client.templates.dmca')
                </div>

                <div class="footer-col">
                    <h6 class="footer-heading">Danh mục</h4>
                    <ul class="footer-links">
                        <li><a href="/">Reiew Hải Phòng</a></li>
                        <li><a href="/dich-vu">Dịch vụ</a></li>
                        <li><a href="/am-thuc">Ẩm thực</a></li>
                        <li><a href="/check-in">Check in</a></li>
                        <li><a href="/review-tong-hop">Review tổng hợp</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h6 class="footer-heading">Dịch vụ</h4>
                    <ul class="footer-links">
                        <li><a href="#">Review cửa hàng</a></li>
                        <li><a href="#">Review quán cà phê</a></li>
                        <li><a href="#">Review view doanh nghiệp</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h6 class="footer-heading">Review Hải Phòng</h4>
                    <ul class="footer-links">
                        <li><a href="gioi-thieu">Giới thiệu</a></li>
                        <li><a href="tin-tuc">Tin tức</a></li>
                        <li><a href="lien-he">Liên hệ</a></li>
                    </ul>
                </div>

                <div class="footer-col contact-col">
                    <h6 class="footer-heading">Liên hệ</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>Hải Phòng</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <p>{{ $settings['contact_email'] }}</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <p>{{ $settings['contact_phone'] }}</p>
                        </div>
                        <div class="contact-item">
                            <p><img style="margin-top: 10px;" alt="Bộ công thương đã duyệt" title="Bộ công thương đã duyệt" width="150px" height="55px" src="/client/assets/images/logo/{{ $settings['bo_cong_thuong'] }}"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="copyright-section">
        <div class="container">
            <p>&copy; {{ ((2025 != now()->year & 2025 < now()->year) ? '2025 - ' : ''). now()->year }} All Rights Reserved</p>
        </div>
    </div>
</footer>