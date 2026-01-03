@extends('client.layouts.main')

@section('title', 'Giới thiệu tổng quát về Review Hải Phòng')

@section('header')
    @include('client.templates.header')
@endsection

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">{{ $settings['site_name'] }}</a></li>
            <li class="breadcrumb-item active">Giới thiệu</li>
        </ol>
    </nav>
    <div class="ỉntroduce">
        <div>
            <h1>Review Hải Phòng – Khám phá du lịch &amp; ẩm thực thành phố Cảng</h1>
            <p><strong>Review Hải Phòng</strong> là website chuyên sâu về <strong>du lịch và ẩm thực Hải Phòng</strong>, nơi
                mang đến những bài review chân thực, gần gũi và cập nhật liên tục dành cho người dân địa phương và du khách
                gần xa. Với định hướng "Trải nghiệm thật – Chia sẻ thật", Review Hải Phòng giúp bạn <strong>tìm ra quán
                    ngon, chốn chill, điểm đến thú vị</strong> chỉ với vài cú click chuột.</p>

            <hr>

            <h2>Vì sao nên chọn Review Hải Phòng?</h2>
            <h3>🎯 Tập trung vào du lịch và ẩm thực</h3>
            <p>Từ <strong>quán ăn vỉa hè bình dân</strong> đến <strong>nhà hàng cao cấp</strong>, từ <strong>điểm du lịch
                    nổi tiếng</strong> đến <strong>góc sống ảo ít người biết</strong> – tất cả đều được Review Hải Phòng
                trải nghiệm và chia sẻ chi tiết. Chúng tôi không chỉ review địa điểm, mà còn cung cấp:</p>

            <ul>
                <li>Mức giá tham khảo</li>
                <li>Chất lượng món ăn / dịch vụ</li>
                <li>Đánh giá thật không quảng cáo</li>
                <li>Gợi ý lịch trình tham quan – ăn uống theo từng khu vực</li>
            </ul>

            <h3>📍 Check-in đẹp – nội dung phụ nhưng chất</h3>
            <p>Bạn mê sống ảo? Tụi mình cũng vậy! Mục check-in tuy là chuyên mục phụ nhưng vẫn đầu tư chỉnh chu với các địa
                điểm đẹp, dễ di chuyển, phù hợp với mọi độ tuổi. Chúng tôi sẽ chỉ bạn <strong>điểm nào đang hot</strong>,
                <strong>góc chụp nào lên ảnh đẹp</strong>, và cả <strong>khung giờ ít người</strong> để tha hồ "chụp cháy
                máy".
            </p>

            <hr>

            <h2>Các chuyên mục chính trên Review Hải Phòng</h2>
            <table>
                <thead>
                    <tr>
                        <th>Danh mục</th>
                        <th>Nội dung nổi bật</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><a href="/am-thuc" target="_blank">Ẩm thực</a></strong></td>
                        <td>
                            Khám phá thế giới ẩm thực đa dạng của Hải Phòng với những món ngon đường phố trứ danh như bánh
                            đa cua, nem cua bể, bún cá cay; các quán ăn gia đình truyền thống, tiệm cafe chill, đồ ăn vặt
                            học sinh và cả những nhà hàng sang trọng đẳng cấp.
                        </td>
                    </tr>
                    <tr>
                        <td><strong><a href="/du-lich" target="_blank">Du lịch</a></strong></td>
                        <td>
                            Trải nghiệm những chuyến đi đầy thú vị – từ tour khám phá đảo Cát Bà, Đồ Sơn đến các địa điểm
                            gần trung tâm thành phố, thích hợp cho kỳ nghỉ ngắn ngày hay chuyến đi cuối tuần nhẹ nhàng, thư
                            giãn.
                        </td>
                    </tr>
                    <tr>
                        <td><strong><a href="/check-in"
                                    target="_blank">Check-in</a></strong></td>
                        <td>
                            Những điểm sống ảo mới toanh, concept độc đáo, góc chụp outfit cực đỉnh. Cập nhật trend check-in
                            tại các quán cafe, khu vui chơi, công viên, bãi biển và các địa điểm chụp hình nghệ thuật cực
                            đẹp.
                        </td>
                    </tr>
                    <tr>
                        <td><strong><a href="/dich-vu" target="_blank">Dịch vụ</a></strong></td>
                        <td>
                            Tổng hợp và đánh giá các dịch vụ chất lượng cao tại Hải Phòng: spa, studio ảnh, giao hàng, làm
                            đẹp, sửa chữa, tổ chức sự kiện... Đầy đủ thông tin liên hệ, bảng giá và trải nghiệm thực tế.
                        </td>
                    </tr>
                </tbody>

            </table>

            <hr>

            <h2>Đối tượng mà Review Hải Phòng hướng đến</h2>
            <ul>
                <li><strong>Người dân Hải Phòng</strong> muốn tìm địa điểm ăn ngon – chỗ chơi mới</li>
                <li><strong>Du khách đến Hải Phòng</strong> muốn lên lịch trình dễ dàng</li>
                <li><strong>Bạn trẻ thích khám phá</strong>, mê ăn uống, đam mê review</li>
                <li><strong>Doanh nghiệp và quán ăn</strong> muốn được giới thiệu đến đúng tệp khách hàng</li>
            </ul>

            <hr>

            <h2>Liên hệ Review Hải Phòng bằng cách nào</h2>
            <ul>
                <li>🌐 Website: <a href="{{ $settings['site_url'] }}" target="_blank"
                        rel="noopener">{{ $settings['site_url'] }}</a></li>
                <li>📧 Email: <a href="mailto:{{ $settings['contact_email'] }}">{{ $settings['contact_email'] }}</a></li>
                <li>📞 Hotline: {{ $settings['contact_phone'] }}</li>
                <li>📍 Địa điểm hoạt động: Hải Phòng – Việt Nam</li>
            </ul>

            <hr>

            <h2>Cảm nhận về Review Hải Phòng</h2>
            <p><strong>Review Hải Phòng</strong> không đơn thuần là một website review – mà là <strong>người bạn đồng
                    hành</strong> giúp bạn khám phá trọn vẹn vẻ đẹp, ẩm thực và trải nghiệm tại thành phố Cảng. Hãy ghé <a
                    href="https://reviewhaiphong.io.vn" target="_blank" rel="noopener">reviewhaiphong.io.vn</a> mỗi ngày để
                biết Hải Phòng hôm nay có gì vui, có gì ngon bạn nhé!</p>

        </div>
        @include('client.templates.featured')
        <style>
            .ỉntroduce {
                width: 90%;
                margin: 30px auto;
                padding: 20px;
                font-family: "Segoe UI", sans-serif;
                color: #333;
                background-color: #fff;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
                border-radius: 8px;
                line-height: 1.8;
                display: grid;
                grid-template-columns: 60% auto;
                column-gap: 20px
            }

            .ỉntroduce h1 {
                font-size: 2.2rem;
                color: #cc0000;
                margin-bottom: 20px;
                line-height: 1.3;
                text-align: start;
            }

            .ỉntroduce h2 {
                font-size: 1.6rem;
                color: #cc0000;
                margin-top: 30px;
                margin-bottom: 10px;
                border-left: 4px solid #cc0000;
                padding-left: 10px;
            }

            .ỉntroduce h3 {
                font-size: 1.3rem;
                color: #007BFF;
                margin-top: 20px;
            }

            .ỉntroduce p {
                margin-bottom: 15px;
                text-align: justify;
            }

            .ỉntroduce ul {
                padding-left: 20px;
                margin-bottom: 20px;
            }

            .ỉntroduce ul li {
                margin-bottom: 8px;
                list-style: disc;
            }

            .ỉntroduce a {
                color: #007BFF;
                text-decoration: none;
            }

            .ỉntroduce a:hover {
                text-decoration: underline;
            }

            .ỉntroduce hr {
                border: none;
                height: 1px;
                background-color: #eee;
                margin: 30px 0;
            }

            .ỉntroduce table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }

            .ỉntroduce table th,
            .ỉntroduce table td {
                padding: 12px 15px;
                border: 1px solid #ddd;
                text-align: left;
            }

            .ỉntroduce table thead {
                background-color: #f5f5f5;
            }

            .ỉntroduce table td:first-child {
                font-weight: bold;
                color: #555;
            }

            @media (max-width: 768px) {
                .ỉntroduce {
                    padding: 15px;
                    grid-template-columns: 1fr;
                }

                .ỉntroduce h1 {
                    font-size: 1.8rem;
                }

                .ỉntroduce h2 {
                    font-size: 1.4rem;
                }

                .ỉntroduce h3 {
                    font-size: 1.2rem;
                }

                .ỉntroduce table th,
                .ỉntroduce table td {
                    font-size: 0.95rem;
                }
            }

            @media (max-width: 480px) {
                .ỉntroduce {
                    width: 95% !important;
                    margin: 10px auto !important;
                    padding: 10px !important;
                }
            }
        </style>
    </div>
@endsection
