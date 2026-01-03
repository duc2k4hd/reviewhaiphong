<!DOCTYPE html>
<html lang="{{ $settings['site_language'] }}">

<head>
    @include('client.module.css')
    
    <!-- Charset & Viewport -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Title -->
    <title>@yield('title', $settings['site_title'] ?? $settings['site_name'] ?? 'Review Hải Phòng')</title>

    <!-- Meta Description -->
    <meta name="description" content="@yield('meta_description', $settings['site_description'] ?? '')">

    <!-- Meta Keywords -->
    <meta name="keywords" content="@yield('meta_keywords', $settings['site_keywords'] ?? $settings['seo_keywords'] ?? '')">

    <!-- Author -->
    <meta name="author" content="@yield('meta_author', $settings['seo_author'] ?? $settings['site_name'] ?? 'Review Hải Phòng')">

    <!-- Robots -->
    <meta name="robots" content="@yield('meta_robots', 'index, follow')">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

    <!-- Language -->
    <meta name="language" content="@yield('meta_language', $settings['site_language'] ?? 'vi')">
    <meta http-equiv="content-language" content="@yield('meta_language', $settings['site_language'] ?? 'vi')">

    <!-- Copyright -->
    <meta name="copyright" content="{{ $settings['seo_author'] ?? $settings['site_name'] ?? 'Review Hải Phòng' }}">

    <!-- Revisit After -->
    <meta name="revisit-after" content="7 days">

    <!-- Distribution -->
    <meta name="distribution" content="global">

    <!-- Rating -->
    <meta name="rating" content="general">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:title" content="@yield('og_title', $settings['site_name'] ?? 'Review Hải Phòng')">
    <meta property="og:description" content="@yield('og_description', $settings['site_description'] ?? '')">
    <meta property="og:image" content="@yield('og_image', asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="@yield('og_image_alt', $settings['site_name'] ?? 'Review Hải Phòng')">
    <meta property="og:image:type" content="image/png">
    <meta property="og:site_name" content="{{ $settings['site_name'] ?? 'Review Hải Phòng' }}">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:locale:alternate" content="en_US">
    @hasSection('og_article')
    @yield('og_article')
    @endif

    <!-- Twitter Card -->
    <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
    <meta name="twitter:site" content="{{ $settings['site_name'] ?? 'Review Hải Phòng' }}">
    <meta name="twitter:creator" content="{{ $settings['site_name'] ?? 'Review Hải Phòng' }}">
    <meta name="twitter:title" content="@yield('twitter_title', $settings['site_name'] ?? 'Review Hải Phòng')">
    <meta name="twitter:description" content="@yield('twitter_description', $settings['site_description'] ?? '')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')))">
    <meta name="twitter:image:alt" content="@yield('twitter_image_alt', $settings['site_name'] ?? 'Review Hải Phòng')">

    <!-- Canonical URL -->
    <link rel="canonical" href="@yield('canonical_url', url()->current())">

    <!-- Alternate Languages -->
    <link rel="alternate" hreflang="vi" href="@yield('hreflang_vi', url()->current())">
    <link rel="alternate" hreflang="x-default" href="@yield('hreflang_default', url()->current())">

    <!-- Additional Meta Tags -->
    @hasSection('additional_meta')
    @yield('additional_meta')
    @endif

    <!-- Favicon -->
    @if($settings['site_favicon'] ?? false)
        <link rel="apple-touch-icon" sizes="180x180"
            href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}">
        <link rel="icon" type="image/png" sizes="32x32"
            href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}">
        <link rel="icon" type="image/png" sizes="16x16"
            href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}">
        <link rel="mask-icon" href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}" color="#5bbad5">
        <link rel="icon" href="{{ asset('/client/assets/images/logo/' . $settings['site_favicon']) }}"
            type="image/x-icon">
    @endif
    <meta name="theme-color" content="#ffffff">

    {{-- Google Analytics --}}
    @if($settings['google_analytics'] ?? false)
        {!! $settings['google_analytics'] !!}
    @endif

    {{-- Google Tag Manager (Header) --}}
    @if($settings['google_tag_header'] ?? false)
        {!! $settings['google_tag_header'] !!}
    @endif

    {{-- Bing Webmaster Tools --}}
    @if($settings['bing_tag_header'] ?? false)
        {!! $settings['bing_tag_header'] !!}
    @endif

    {{-- Pinterest Domain Verification --}}
    @if($settings['site_pinterest'] ?? false)
        {!! $settings['site_pinterest'] !!}
    @endif

    {{-- Google Search Console --}}
    @if($settings['google_search_console'] ?? false)
        <meta name="google-site-verification" content="{{ $settings['google_search_console'] }}" />
    @endif

    <!-- Security Headers -->
    <meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Structured Data - Base Schema (Organization, WebSite) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "Organization",
                "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#organization' }}",
                "name": "{{ $settings['site_name'] ?? 'Review Hải Phòng' }}",
                "url": "{{ $settings['site_url'] ?? url('/') }}",
                "logo": {
                    "@type": "ImageObject",
                    "url": "{{ asset('/client/assets/images/logo/' . ($settings['site_image'] ?? 'logo.png')) }}",
                    "width": 600,
                    "height": 600
                },
                @if($settings['contact_phone'] ?? false)
                "contactPoint": {
                    "@type": "ContactPoint",
                    "telephone": "{{ $settings['contact_phone'] }}",
                    "contactType": "Customer Service",
                    @if($settings['contact_email'] ?? false)
                    "email": "{{ $settings['contact_email'] }}",
                    @endif
                    "areaServed": "VN",
                    "availableLanguage": ["vi", "en"]
                },
                @endif
                "sameAs": [
                  @php $first = true; @endphp
                  @foreach (['facebook_link','twitter_link','instagram_link','telegram_link','discord_link'] as $key)
                      @if(!empty($settings[$key]))
                          @if(!$first),@endif
                          "{{ $settings[$key] }}"
                          @php $first = false; @endphp
                      @endif
                  @endforeach
              ]
            },
            {
                "@type": "WebSite",
                "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#website' }}",
                "url": "{{ $settings['site_url'] ?? url('/') }}",
                "name": "{{ $settings['site_name'] ?? 'Review Hải Phòng' }}",
                "description": "{{ $settings['site_description'] ?? '' }}",
                "publisher": {
                    "@id": "{{ ($settings['site_url'] ?? url('/')) . '/#organization' }}"
                },
                "potentialAction": {
                    "@type": "SearchAction",
                    "target": {
                        "@type": "EntryPoint",
                        "urlTemplate": "{{ ($settings['site_url'] ?? url('/')) . '/tim-kiem/{search_term_string}' }}"
                    },
                    "query-input": "required name=search_term_string"
                },
                "inLanguage": "vi"
            }
        ]
    }
    </script>

    <!-- Page-Specific Structured Data -->
    @hasSection('structured_data')
    @yield('structured_data')
    @endif


</head>

<body>
    {{-- Google Tag Manager (Body) --}}
    @if($settings['google_tag_body'] ?? false)
        {!! $settings['google_tag_body'] !!}
    @endif

    {{-- Facebook Pixel --}}
    @if($settings['facebook_pixel'] ?? false)
        {!! $settings['facebook_pixel'] !!}
    @endif

    {{-- Chỗ này để loading --}}
    {{-- @include('client.templates.loading') --}}
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
