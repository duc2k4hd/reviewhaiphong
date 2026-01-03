<div class="related-posts-container">
    <div class="related-posts-header">
        <span class="icon"><i class="fas fa-fire"></i></span>
        <h3>BÀI VIẾT LIÊN QUAN</h3>
    </div>

    <div class="related-posts-list">
        @if(isset($categoryPosts) && count($categoryPosts) > 0)
            @foreach ($categoryPosts as $relatedPost)
                <a href="/bai-viet/{{ $relatedPost->slug }}" class="post-item">
                    <div class="post-image">
                        @if($relatedPost->seo_image)
                            <img src="{{ asset('/client/assets/images/posts/'. $relatedPost->seo_image) }}" 
                                 title="{{ $relatedPost->seo_title ?? $relatedPost->name }}" 
                                 alt="{{ $relatedPost->seo_title ?? $relatedPost->name }}">
                        @else
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                            </div>
                        @endif
                    </div>
                    <div class="post-content">
                        <h3 class="post-title">{{ $relatedPost->seo_title ?? $relatedPost->name }}</h3>
                        <div class="post-meta">
                            <span class="post-date">
                                <i class="far fa-calendar-alt"></i> 
                                {{ $relatedPost->published_at ? \Carbon\Carbon::parse($relatedPost->published_at)->format("d/m/Y") : 'N/A' }}
                            </span>
                            <span class="post-author">
                                <i class="far fa-user"></i> 
                                {{ $relatedPost->account->profile->name ?? 'Tác giả' }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        @else
            <p class="no-related-posts">Chưa có bài viết liên quan.</p>
        @endif
    </div>
</div>