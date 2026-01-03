<nav class="container breadcrumb" aria-label="breadcrumb">
    <ol class="">
        <li class="breadcrumb-item"><a href="/">{{ $settings['site_name'] ?? 'Review Hải Phòng' }}</a></li>
        @if(isset($post->category) && $post->category)
            <li class="breadcrumb-item"><a href="/{{ $post->category->slug }}">{{ $post->category->name }}</a></li>
        @endif
        <li class="breadcrumb-item active" aria-current="page">{{ $post->seo_title ?? $post->name ?? 'Bài viết' }}</li>
    </ol>
</nav>
  