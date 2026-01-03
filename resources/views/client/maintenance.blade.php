<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings['site_name'] ?? 'Review Hải Phòng' }} - Đang bảo trì</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            margin: 2rem;
        }
        .maintenance-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .contact-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        .contact-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .contact-info p {
            margin: 0.25rem 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <h1>Đang bảo trì</h1>
        <p>Website đang được bảo trì để cải thiện trải nghiệm người dùng. Vui lòng quay lại sau!</p>
        
        <div class="contact-info">
            <h3>Liên hệ hỗ trợ</h3>
            @if($settings['contact_email'] ?? false)
                <p>📧 Email: {{ $settings['contact_email'] }}</p>
            @endif
            @if($settings['contact_phone'] ?? false)
                <p>📞 Điện thoại: {{ $settings['contact_phone'] }}</p>
            @endif
            @if($settings['contact_facebook'] ?? false)
                <p>📘 Facebook: <a href="{{ $settings['contact_facebook'] }}" target="_blank">Theo dõi chúng tôi</a></p>
            @endif
        </div>
    </div>
</body>
</html>

