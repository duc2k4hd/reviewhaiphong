<div class="related-posts-container">
    <div class="related-posts-header">
        <span class="icon"><i class="fas fa-fire"></i></span>
        <h3>BÀI VIẾT LIÊN QUAN</h3>
    </div>

    <div class="related-posts-list">
        @foreach ($categoryPosts->posts as $post)
            <a href="/{{ $categoryPosts->slug. '/'. $post->slug }}" class="post-item">
                <div class="post-image">
                    <img src="{{ asset('/client/assets/images/posts/'. $post->seo_image) }}" title="{{ $post->seo_title }}" alt="{{ $post->seo_title }}">
                </div>
                <div class="post-content">
                    <h3 class="post-title">{{ $post->seo_title }}</h3>
                    <div class="post-meta">
                        <span class="post-date"><i class="far fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($post->published_at)->format("d/m/y") }}</span>
                        <span class="post-author"><i class="far fa-user"></i> {{ $post->account->profile->name }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>