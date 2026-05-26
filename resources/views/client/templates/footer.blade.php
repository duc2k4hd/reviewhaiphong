<footer class="reviewhaiphong_home_footer">
    <div class="reviewhaiphong_home_footer_grid">
        <div class="anim-fade-up">
            <div class="reviewhaiphong_home_footer_logo">Review<span>HảiPhòng</span></div>
            <p class="reviewhaiphong_home_footer_desc">{{ $settings['site_description'] ?? 'Website review địa điểm ăn uống, du lịch, cafe và các trải nghiệm địa phương tại thành phố Hải Phòng - Thành phố hoa phượng đỏ. Cung cấp thông tin chính xác, trung thực và hữu ích nhất cho người dùng.' }}</p>
            <div class="reviewhaiphong_home_footer_socials">
                @if(isset($settings['facebook_link']) && $settings['facebook_link'])
                    <a href="{{ $settings['facebook_link'] }}" target="_blank" class="rhp-btn-fb">📘 Facebook</a>
                @endif
                @if(isset($settings['instagram_link']) && $settings['instagram_link'])
                    <a href="{{ $settings['instagram_link'] }}" target="_blank" class="rhp-btn-ig">📸 Instagram</a>
                @endif
            </div>
            @if($settings['bo_cong_thuong'] ?? false)
                <div class="mt-15">
                    <img alt="Bộ công thương đã duyệt" title="Bộ công thương đã duyệt" width="150px" height="55px" src="{{ asset('/client/assets/images/logo/' . $settings['bo_cong_thuong']) }}">
                </div>
            @endif
        </div>
        <div class="anim-fade-up anim-d-1">
            <div class="reviewhaiphong_home_footer_col_title">Chuyên mục</div>
            <div class="reviewhaiphong_home_footer_links">
                <a href="/am-thuc">Ẩm thực Hải Phòng</a>
                <a href="/du-lich">Du lịch - Check-in</a>
                <a href="/check-in">Cafe & Trà sữa</a>
                <a href="/review-tong-hop">Khách sạn - Resort</a>
                <a href="/dich-vu">Dịch vụ</a>
                <a href="/tin-tuc">Tin tức địa phương</a>
            </div>
        </div>
        <div class="anim-fade-up anim-d-2">
            <div class="reviewhaiphong_home_footer_col_title">Địa điểm nổi bật</div>
            <div class="reviewhaiphong_home_footer_links">
                <a href="/search?keyword=Cát+Bà">Đảo Cát Bà</a>
                <a href="/search?keyword=Đồ+Sơn">Bãi biển Đồ Sơn</a>
                <a href="/search?keyword=Phố+cổ">Phố cổ Hải Phòng</a>
                <a href="/search?keyword=Tam+Bạc">Chợ Tam Bạc</a>
                <a href="/search?keyword=Núi+Voi">Núi Voi Kiến An</a>
                <a href="/search?keyword=Bạch+Long+Vỹ">Bạch Long Vỹ</a>
            </div>
        </div>
        <div class="anim-fade-up anim-d-3">
            <div class="reviewhaiphong_home_footer_col_title">Liên hệ</div>
            <div class="reviewhaiphong_home_footer_links">
                <a href="mailto:{{ $settings['contact_email'] ?? 'contact@reviewhaiphong.vn' }}">📧 {{ $settings['contact_email'] ?? 'contact@reviewhaiphong.vn' }}</a>
                <a href="tel:{{ $settings['contact_phone'] ?? '0901 234 567' }}">📞 {{ $settings['contact_phone'] ?? '0901 234 567' }}</a>
                <a href="#">📍 {{ $settings['contact_address'] ?? 'Hải Phòng' }}</a>
                <a href="/lien-he">🤝 Hợp tác quảng cáo</a>
                <a href="/gioi-thieu">❓ Về chúng tôi</a>
            </div>
        </div>
    </div>
    <div class="reviewhaiphong_home_footer_bottom">
        @if(isset($settings['copyright']) && $settings['copyright'])
            {!! Blade::render($settings['copyright']) !!}
        @else
            © {{ date('Y') }} <a href="/">ReviewHảiPhòng.vn</a> · Bản quyền thuộc về Review Hải Phòng · Thiết kế bởi team Review HP
        @endif
    </div>
</footer>

@push('scripts')
    <script src="{{ asset('client/assets/js/home_new.js') }}"></script>
@endpush