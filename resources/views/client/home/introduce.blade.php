@extends('client.layouts.main')

@section('title', 'Review Hải Phòng – Giới thiệu du lịch & ẩm thực thành phố Cảng')

@section('header')
    @include('client.templates.header')
@endsection

@section('content')
<div class="reviewhaiphong_home_main_wrap">
    <div class="reviewhaiphong_home_container">
        <nav aria-label="breadcrumb" class="mb-20">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/"><i class="fa-solid fa-house"></i> {{ $settings['site_name'] ?? 'Trang chủ' }}</a></li>
                <li class="breadcrumb-item active">Giới thiệu</li>
            </ol>
        </nav>

        <div class="reviewhaiphong_home_main_layout">
            <!-- LEFT MAIN CONTENT -->
            <div class="rhp-about-content">
                
                <!-- Hero Section -->
                <div class="rhp-about-hero anim-zoom">
                    <img src="https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=1200&q=80" alt="Hải Phòng" class="rhp-about-hero-img">
                    <div class="rhp-about-hero-overlay">
                        <h1>Khám phá trọn vẹn<br><span>Thành Phố Cảng</span></h1>
                        <p>Hành trình tìm kiếm hương vị thật, trải nghiệm thật cùng Review Hải Phòng</p>
                    </div>
                </div>

                <!-- Introduction -->
                <div class="rhp-about-section anim-fade-up">
                    <h2 class="rhp-about-heading">Về chúng tôi</h2>
                    <div class="rhp-about-text">
                        <p><strong>Review Hải Phòng</strong> là nền tảng thông tin chuyên sâu về <strong>du lịch và ẩm thực Hải Phòng</strong>. Nơi đây mang đến những bài review chân thực, gần gũi và được cập nhật liên tục dành cho cả người dân địa phương lẫn du khách tứ phương.</p>
                        <p>Với kim chỉ nam <em>"Trải nghiệm thật – Chia sẻ thật"</em>, chúng tôi mong muốn trở thành cuốn cẩm nang bỏ túi hoàn hảo, giúp bạn <strong>tìm ra quán ngon đúng vị, chốn chill cực nghệ, và những điểm đến đầy cảm hứng</strong> chỉ với vài thao tác chạm.</p>
                    </div>
                </div>

                <!-- Vision & Mission -->
                <div class="rhp-about-grid mb-30">
                    <div class="rhp-about-card anim-slide-left">
                        <div class="rhp-about-icon"><i class="fa-solid fa-bullseye"></i></div>
                        <h3>Sứ mệnh</h3>
                        <p>Kết nối du khách và người dân với những tinh hoa ẩm thực, văn hóa đặc sắc nhất của Hải Phòng. Quảng bá hình ảnh một thành phố Cảng năng động, mến khách đến bạn bè bốn phương.</p>
                    </div>
                    <div class="rhp-about-card anim-slide-right anim-d-1">
                        <div class="rhp-about-icon"><i class="fa-solid fa-eye"></i></div>
                        <h3>Tầm nhìn</h3>
                        <p>Trở thành nền tảng thông tin số 1 về trải nghiệm sống, du lịch và giải trí tại Hải Phòng. Một hệ sinh thái tin cậy, khách quan và minh bạch cho cộng đồng.</p>
                    </div>
                </div>

                <!-- Categories -->
                <div class="rhp-about-section anim-fade-up">
                    <h2 class="rhp-about-heading">Chuyên mục nổi bật</h2>
                    <div class="rhp-about-cat-grid">
                        <div class="rhp-about-cat-card">
                            <i class="fa-solid fa-bowl-food text-rhp-red"></i>
                            <h4>Ẩm thực</h4>
                            <p>Khám phá bánh đa cua, nem cua bể, bún cá cay và thế giới đồ ăn vặt vỉa hè trứ danh.</p>
                        </div>
                        <div class="rhp-about-cat-card">
                            <i class="fa-solid fa-plane text-rhp-blue"></i>
                            <h4>Du lịch</h4>
                            <p>Tour khám phá vịnh Lan Hạ, đảo Cát Bà, biển Đồ Sơn và các di tích lịch sử nội đô.</p>
                        </div>
                        <div class="rhp-about-cat-card">
                            <i class="fa-solid fa-camera text-rhp-orange"></i>
                            <h4>Check-in</h4>
                            <p>Góc sống ảo cực chất tại các quán cafe, nhà hát lớn, và những con phố nghệ thuật.</p>
                        </div>
                        <div class="rhp-about-cat-card">
                            <i class="fa-solid fa-concierge-bell text-rhp-green"></i>
                            <h4>Dịch vụ</h4>
                            <p>Review từ tâm các dịch vụ spa, studio, lưu trú, và tiện ích cuộc sống hàng ngày.</p>
                        </div>
                    </div>
                </div>

                <!-- Target Audience -->
                <div class="rhp-about-section anim-fade-up" style="background: rgba(230, 57, 70, 0.03); padding: 25px; border-radius: 12px; border-left: 4px solid var(--rhp-red);">
                    <h2 class="rhp-about-heading" style="margin-top: 0;">Người bạn đồng hành của ai?</h2>
                    <ul class="rhp-about-list">
                        <li><i class="fa-solid fa-check text-rhp-red"></i> <strong>Người dân Hải Phòng:</strong> Tìm kiếm địa điểm ăn chơi mới lạ vào dịp cuối tuần.</li>
                        <li><i class="fa-solid fa-check text-rhp-red"></i> <strong>Du khách:</strong> Dễ dàng lên lịch trình tự túc, ăn đúng chỗ ngon, chơi đúng chỗ vui.</li>
                        <li><i class="fa-solid fa-check text-rhp-red"></i> <strong>Giới trẻ:</strong> Nơi cập nhật các hot trend, góc chụp hình siêu "cháy".</li>
                        <li><i class="fa-solid fa-check text-rhp-red"></i> <strong>Doanh nghiệp/Quán ăn:</strong> Kênh truyền thông hiệu quả đến đúng tệp khách hàng tiềm năng.</li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="rhp-about-section anim-fade-up">
                    <h2 class="rhp-about-heading">Liên hệ với chúng tôi</h2>
                    <div class="rhp-about-contact">
                        <p>Review Hải Phòng luôn sẵn sàng lắng nghe mọi góp ý, hợp tác và chia sẻ từ bạn!</p>
                        <div class="rhp-contact-item">
                            <i class="fa-solid fa-globe"></i> <span>Website: <a href="{{ $settings['site_url'] ?? '/' }}" target="_blank">{{ $settings['site_url'] ?? 'reviewhaiphong.vn' }}</a></span>
                        </div>
                        <div class="rhp-contact-item">
                            <i class="fa-solid fa-envelope"></i> <span>Email: <a href="mailto:{{ $settings['contact_email'] ?? 'contact@reviewhaiphong.vn' }}">{{ $settings['contact_email'] ?? 'contact@reviewhaiphong.vn' }}</a></span>
                        </div>
                        <div class="rhp-contact-item">
                            <i class="fa-solid fa-phone"></i> <span>Hotline: <strong>{{ $settings['contact_phone'] ?? '0123.456.789' }}</strong></span>
                        </div>
                        <div class="rhp-contact-item">
                            <i class="fa-solid fa-location-dot"></i> <span>Địa chỉ: Hải Phòng, Việt Nam</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT SIDEBAR -->
            <aside class="reviewhaiphong_home_sidebar">
                @include('client.templates.featured')
            </aside>

        </div>
    </div>
</div>

<style>
/* BREADCRUMB STYLES */
.breadcrumb {
    display: flex;
    align-items: center;
    list-style: none;
    padding: 0;
    margin: 0 0 15px 0;
    background: transparent;
    font-size: 0.9rem;
}
.breadcrumb-item {
    display: flex;
    align-items: center;
}
.breadcrumb-item + .breadcrumb-item::before {
    content: "\f105";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    font-size: 0.75rem;
    color: #999;
    margin: 0 10px;
}
.breadcrumb-item a {
    color: #666;
    text-decoration: none;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.breadcrumb-item a:hover {
    color: var(--rhp-red);
}
.breadcrumb-item.active {
    color: #333;
    font-weight: 600;
}

/* ABOUT PAGE STYLES */
.rhp-about-content {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}

.rhp-about-hero {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 40px;
    height: 300px;
}


.reviewhaiphong_home_main_layout {
    padding: 0;
}

.rhp-about-hero-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.rhp-about-hero:hover .rhp-about-hero-img {
    transform: scale(1.05);
}

.rhp-about-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, rgba(0,0,0,0.8), rgba(0,0,0,0.3));
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 40px;
    color: #fff;
}

.rhp-about-hero-overlay h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 10px;
    line-height: 1.2;
}

.rhp-about-hero-overlay h1 span {
    color: var(--rhp-red);
}

.rhp-about-hero-overlay p {
    font-size: 1.1rem;
    max-width: 500px;
    opacity: 0.9;
}

.rhp-about-section {
    margin-bottom: 40px;
}

.rhp-about-heading {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--rhp-red);
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 10px;
}

.rhp-about-heading::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--rhp-red);
    border-radius: 2px;
}

.rhp-about-text p {
    font-size: 1.05rem;
    line-height: 1.7;
    color: #444;
    margin-bottom: 15px;
    text-align: justify;
}

.rhp-about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.rhp-about-card {
    background: #f9f9fa;
    border-radius: 12px;
    padding: 25px;
    border: 1px solid #eee;
    transition: all 0.3s ease;
}

.rhp-about-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    border-color: rgba(230, 57, 70, 0.3);
}

.rhp-about-icon {
    font-size: 2.5rem;
    color: var(--rhp-red);
    margin-bottom: 15px;
}

.rhp-about-card h3 {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 10px;
    color: #222;
}

.rhp-about-card p {
    color: #555;
    line-height: 1.6;
}

.rhp-about-cat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.rhp-about-cat-card {
    padding: 20px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #eaeaea;
    transition: all 0.3s;
}

.rhp-about-cat-card:hover {
    border-color: #ddd;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.rhp-about-cat-card i {
    font-size: 2rem;
    margin-bottom: 15px;
}

.rhp-about-cat-card h4 {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 8px;
    color: #333;
}

.rhp-about-cat-card p {
    color: #666;
    line-height: 1.5;
    font-size: 0.95rem;
}

.rhp-about-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.rhp-about-list li {
    margin-bottom: 12px;
    font-size: 1.05rem;
    color: #444;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.rhp-about-list li i {
    margin-top: 5px;
}

.rhp-contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    font-size: 1.1rem;
    color: #333;
}

.rhp-contact-item i {
    width: 40px;
    height: 40px;
    background: rgba(230, 57, 70, 0.1);
    color: var(--rhp-red);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 1.1rem;
}

.rhp-contact-item a {
    color: var(--rhp-red);
    text-decoration: none;
    font-weight: 600;
}

.rhp-contact-item a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .rhp-about-content {
        padding: 20px;
    }
    
    .rhp-about-hero {
        height: 250px;
    }

    .rhp-about-hero-overlay {
        padding: 20px;
    }

    .rhp-about-hero-overlay h1 {
        font-size: 2rem;
    }
    
    .rhp-about-grid, .rhp-about-cat-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
