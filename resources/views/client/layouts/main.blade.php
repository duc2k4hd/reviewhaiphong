<!DOCTYPE html>
<html lang="{{ $settings['site_language'] }}">

<head>
    @include('client.module.css')
    <title>{{ $settings['site_name'] }}</title>

    <!-- Từ khóa SEO (nên có các từ khóa phù hợp với nội dung trang) -->
    <meta name="keywords" content="{{ $settings['seo_keywords'] }}">

    <!-- Tên tác giả của trang -->
    <meta name="author" content="{{ $settings['seo_author'] }}">

    <!-- Thẻ Robots giúp chỉ định các công cụ tìm kiếm có thể làm gì với trang này -->
    <meta name="robots" content="index, follow">
    <!-- "noindex, nofollow" nếu không muốn công cụ tìm kiếm đánh chỉ mục -->

    <meta name="description" content="{{ $settings['site_description'] }}">

    <!-- Thời gian khi trang được tạo -->
    <meta http-equiv="date" content="{{ \Carbon\Carbon::parse($settings['created_at'])->format('d/m/y') }}" />

    <!-- Đảm bảo trang hỗ trợ charset UTF-8 -->
    <meta charset="UTF-8">

    <!-- Đặt cài đặt viewport cho các thiết bị di động (responsive) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Thẻ ngôn ngữ trang -->
    <meta name="language" content="{{ $settings['site_language'] }}">

    <!-- Thẻ bản quyền -->
    <meta name="copyright" content="{{ $settings['seo_author'] }}">

    <!-- Cảnh báo về nội dung nhạy cảm (nếu có) -->
    <meta name="robots" content="noarchive">

    <!-- Open Graph Title -->
    <meta property="og:title" content="{{ $settings['site_title'] }}">

    <!-- Open Graph Description -->
    <meta property="og:description" content="{{ $settings['site_description'] }}">

    <!-- Open Graph URL (URL của trang hiện tại) -->
    <meta property="og:url" content="{{ url()->current() }}">

    <!-- Open Graph Image (Hình ảnh khi chia sẻ trên mạng xã hội) -->
    <meta property="og:image"
        content="{{ asset('/client/assets/images/logo/' . ($settings['site_image'] ?? ($settings['avatar_admin'] ?? $settings['site_logo']))) }}">

    <!-- Open Graph Type (Loại trang, ví dụ: website, article, product, etc.) -->
    <meta property="og:type" content="website">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $settings['site_name'] }}" />
    <meta property="og:image:type" content="image/png" />

    <!-- Open Graph Site Name (Tên website) -->
    <meta property="og:site_name" content="{{ $settings['site_name'] }}">

    <!-- Open Graph Locale (Ngôn ngữ của trang) -->
    <meta property="og:locale" content="vi_VN">

    <!-- Twitter Card Type (summary hoặc summary_large_image) -->
    <meta name="twitter:card" content="">

    <!-- Tên trang web -->
    <meta name="twitter:site" content="{{ $settings['site_name'] }}">

    <!-- Twitter Title -->
    <meta name="twitter:title" content="{{ $settings['site_title'] }}">

    <!-- Twitter Description -->
    <meta name="twitter:description" content="{{ $settings['site_description'] }}">

    <!-- Twitter Image (Hình ảnh khi chia sẻ trên Twitter) -->
    <meta name="twitter:image" content="{{ asset('/client/assets/images/logo/' . $settings['site_image']) }}">

    <!-- Twitter Creator (Tác giả của trang, nếu có) -->
    <meta name="twitter:creator" content="{{ $settings['site_name'] }}">

    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Thẻ HrefLang (Để hỗ trợ các ngôn ngữ khác nhau) --}}
    <link rel="alternate" hreflang="vi" href="{{ url()->current() }}" />
    <link rel="alternate" hreflang="x-default" href="{{ url()->current() }}" />

    <!-- Thẻ Meta cho Twitter và Facebook Favicon (tùy chọn) -->
    <link rel="apple-touch-icon" sizes="180x180"
        href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="32x32"
        href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="16x16"
        href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}">
    <link rel="mask-icon" href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}"
        type="image/x-icon">

    {{--  Cấu hình các thẻ cho bảo mật --}}
    <meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@graph": [
          {
            "@type": "Organization",
            "@id": "{{ $settings['site_url'] }}/#organization",
            "name": "{{ $settings['site_name'] }}",
            "url": "{{ $settings['site_url'] }}",
            "logo": {
              "@type": "ImageObject",
              "url": "{{ asset('/client/assets/images/logo/' . $settings['site_image']) }}"
            },
            "contactPoint": {
              "@type": "ContactPoint",
              "telephone": "{{ $settings['contact_phone'] }}",
              "contactType": "Customer Service",
              "email": "{{ $settings['contact_email'] }}",
              "areaServed": "VN",
              "availableLanguage": ["vi", "en"]
            },
            "sameAs": [
              "{{ $settings['facebook_link'] }}",
              "{{ $settings['twitter_link'] }}",
              "{{ $settings['instagram_link'] }}",
              "{{ $settings['telegram_link'] }}",
              "{{ $settings['discord_link'] }}"
            ]
          },
          {
            "@type": "WebSite",
            "@id": "{{ $settings['site_url'] }}/#website",
            "url": "{{ $settings['site_url'] }}",
            "name": "{{ $settings['site_name'] }}",
            "description": "{{ $settings['site_description'] }}",
            "publisher": {
              "@id": "{{ $settings['site_url'] }}/#organization"
            },
            "potentialAction": {
              "@type": "SearchAction",
              "target": "{{ $settings['site_url'] }}/tim-kiem/{search_term_string}",
              "query-input": "required name=search_term_string"
            }
          },
          {
            "@type": "WebPage",
            "@id": "{{ url()->current() }}/#webpage",
            "url": "{{ url()->current() }}",
            "name": "{{ $settings['site_name'] }}",
            "isPartOf": {
              "@id": "{{ $settings['site_url'] }}/#website"
            },
            "about": {
              "@id": "{{ $settings['site_url'] }}/#organization"
            },
            "inLanguage": "vi",
            "datePublished": "2025-03-28T08:27:14+07:00",
            "dateModified": "2025-03-30T22:19:16+07:00"
          },
          {
            "@type": "LocalBusiness",
            "@id": "{{ $settings['site_url'] }}/#localbusiness",
            "name": "{{ $settings['site_name'] }}",
            "image": "{{ asset('/client/assets/images/logo/' . $settings['site_image']) }}",
            "url": "{{ $settings['site_url'] }}",
            "telephone": "{{ $settings['contact_phone'] }}",
            "priceRange": "VNĐ",
            "address": {
              "@type": "PostalAddress",
              "streetAddress": "{{ $settings['contact_address'] }}",
              "addressLocality": "Hải Phòng",
              "postalCode": "180000",
              "addressCountry": "VN"
            },
            "geo": {
              "@type": "GeoCoordinates",
              "latitude": 20.8584917,
              "longitude": 106.6844285
            },
            "openingHoursSpecification": [
              {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": [
                  "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"
                ],
                "opens": "00:00",
                "closes": "23:59"
              }
            ],
            "sameAs": [
              "{{ $settings['facebook_link'] }}",
              "{{ $settings['twitter_link'] }}",
              "{{ $settings['instagram_link'] }}",
              "{{ $settings['telegram_link'] }}",
              "{{ $settings['discord_link'] }}"
            ]
          },
          {
            "@type": "Person",
            "@id": "{{ $settings['site_url'] }}/user/admin",
            "name": "{{ $settings['site_name'] }}",
            "url": "{{ $settings['site_url'] }}/user/admin",
            "image": {
              "@type": "ImageObject",
              "url": "{{ asset('/client/assets/images/logo/' . $settings['avatar_admin']) }}",
              "caption": "{{ $settings['site_name'] }}",
              "inLanguage": "vi"
            },
            "worksFor": {
              "@id": "{{ $settings['site_url'] }}/#organization"
            }
          },
          {
            "@type": "Article",
            "@id": "{{ url()->current() }}/#richSnippet",
            "headline": "{{ $settings['site_name'] }}",
            "keywords": "{{ $settings['site_name'] }}",
            "datePublished": "2025-03-28T08:27:14+07:00",
            "dateModified": "2025-03-30T22:19:16+07:00",
            "author": {
              "@id": "{{ $settings['site_url'] }}/user/admin"
            },
            "publisher": {
              "@id": "{{ $settings['site_url'] }}/#organization"
            },
            "description": "{{ $settings['site_description'] }}",
            "name": "{{ $settings['site_name'] }}",
            "isPartOf": {
              "@id": "{{ url()->current() }}/#webpage"
            },
            "inLanguage": "vi",
            "mainEntityOfPage": {
              "@id": "{{ url()->current() }}/#webpage"
            }
          },
          {
            "@type": "BlogPosting",
            "@id": "{{ url()->current() }}/#blogposting",
            "headline": "{{ $settings['site_name'] }}",
            "alternativeHeadline": "{{ $settings['site_name'] }}",
            "image": "{{ asset('/client/assets/images/logo/' . $settings['site_image']) }}",
            "datePublished": "2025-03-28T08:27:14+07:00",
            "dateModified": "2025-03-30T22:19:16+07:00",
            "author": {
              "@id": "{{ $settings['site_url'] }}/user/admin"
            },
            "publisher": {
              "@id": "{{ $settings['site_url'] }}/#organization"
            },
            "mainEntityOfPage": {
              "@id": "{{ url()->current() }}/#webpage"
            },
            "inLanguage": "vi"
          }
        ]
      }
      </script>


</head>

<body>
    {!! $settings['google_tag_body'] !!}
    {{-- Chỗ này để loading --}}
    @include('client.templates.loading')
    <div class="review-haiphong">

        <!-- Chỗ này để header -->
        @yield('header')

        <!-- Chỗ này để main content -->
        <main class="main">
            @yield('content')
        </main>

        <!-- Chỗ này để footer -->
        @include('client.templates.footer')
    </div>
    <!-- <form class="send" style="position: absolute; top: 0; left: 0;" action="">
        <input type="text" name="send" id="send" placeholder="Gửi tin nhắn...">
        <button type="submit">Gửi</button>
    </form> -->
    @include('client.module.js')
    @include('client.templates.chat')
</body>

</html>
