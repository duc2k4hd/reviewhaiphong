@extends('client.layouts.main')

@section('header')
    @include('client.templates.header')
@endsection

@section('content')
<section class="contact-page container" style="max-width:1024px;margin:24px auto;">
    <h1 style="font-size:28px;margin-bottom:12px;">Liên hệ</h1>
    <p style="color:#555;margin-bottom:24px;">Nếu bạn có góp ý, hợp tác hoặc cần hỗ trợ, vui lòng liên hệ theo thông tin dưới đây.</p>

    <div class="contact-grid" style="display:grid;grid-template-columns:1fr;gap:24px;">
        <div class="contact-info" style="background:#fff;border:1px solid #eee;border-radius:8px;padding:16px;">
            <ul style="list-style:none;padding:0;margin:0;line-height:1.8;">
                <li><strong>Email:</strong> {{ $settings['contact_email'] ?? 'support@example.com' }}</li>
                <li><strong>Điện thoại:</strong> {{ $settings['contact_phone'] ?? '0123 456 789' }}</li>
                <li><strong>Địa chỉ:</strong> {{ $settings['contact_address'] ?? 'Hải Phòng, Việt Nam' }}</li>
                <li><strong>Facebook:</strong> <a href="{{ $settings['facebook_link'] ?? '#' }}" rel="nofollow noopener">Facebook</a></li>
            </ul>
        </div>

        <div class="contact-form" style="background:#fff;border:1px solid #eee;border-radius:8px;padding:16px;">
            <form method="post" action="#" onsubmit="event.preventDefault(); alert('Đã ghi nhận!');" novalidate>
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label for="name">Họ tên</label>
                        <input id="name" name="name" type="text" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label for="message">Nội dung</label>
                    <textarea id="message" name="message" rows="5" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;"></textarea>
                </div>
                <button type="submit" class="btn primary" style="margin-top:12px;padding:10px 16px;border-radius:6px;background:#0ea5e9;color:#fff;border:none;cursor:pointer;">Gửi</button>
            </form>
        </div>
    </div>
</section>
@endsection




