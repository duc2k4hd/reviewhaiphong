<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">{{ $settings['site_name'] }}</a></li>
        <li class="breadcrumb-item"><a href="/{{ $post->category->slug }}">{{ $post->category->name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $post->name }}</li>
    </ol>
</nav>
  