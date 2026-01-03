<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @include('client.module.css')
  <title>Bài viết mới nhất | {{ $settings['site_name'] ?? 'Review Hải Phòng' }}</title>
</head>
<body>
  @include('client.templates.header')
  <main class="container">
    <h1 style="text-align:center;margin:20px 0;">Bài viết mới nhất</h1>
    @if($posts->count())
      <div class="posts-category">
        <main class="news-feed">
          @foreach($posts as $item)
            <article class="news-item">
              <div class="news-image">
                <a href="/bai-viet/{{ $item->slug }}">
                  <img src="/client/assets/images/posts/{{ $item->seo_image }}" alt="{{ $item->seo_title }}" title="{{ $item->seo_title }}">
                </a>
              </div>
              <div class="news-content">
                <h3>{{ $item->seo_title }}</h3>
                <p>{{ $item->seo_desc }}</p>
                <div class="news-meta">
                  <span class="author">{{ $item->account->profile->name ?? 'Tác giả' }}</span>
                  <span class="date">{{ $item->published_at ? \Carbon\Carbon::parse($item->published_at)->format('d/m/Y') : 'N/A' }}</span>
                </div>
              </div>
            </article>
          @endforeach
          <div class="pagination">
            {{ $posts->links() }}
          </div>
        </main>
        <aside class="sidebar">
          <div class="sidebar-section">
            <h2 class="sidebar-title">Bài viết nổi bật</h2>
            @foreach ($postsViews as $item)
              <div class="sidebar-item">
                <div class="sidebar-image">
                  <a href="/bai-viet/{{ $item->slug }}"><img src="/client/assets/images/posts/{{ $item->seo_image }}" alt="{{ $item->seo_title }}" title="{{ $item->seo_title }}"></a>
                </div>
                <div class="sidebar-content">
                  <h4>{{ $item->seo_title }}</h4>
                </div>
              </div>
            @endforeach
          </div>
        </aside>
      </div>
    @else
      <p style="text-align:center">Chưa có bài viết.</p>
    @endif
  </main>
  @include('client.templates.footer')
  @include('client.module.js')
</body>
</html>
