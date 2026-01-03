<div class="modern-article-container">
    <div class="container">
        <div class="article-layout">
            <!-- Main Article Content -->
            <main class="article-main">
                <article class="modern-article">
                    <!-- Article Header -->
                    <header class="article-header">
                        <div class="article-meta">
                            @if(isset($post->category) && $post->category)
                                <div class="meta-category">
                                    <i class="fas fa-folder"></i>
                                    <a href="/{{ $post->category->slug }}" class="category-badge">
                                        {{ $post->category->name }}
                                    </a>
                                </div>
                            @endif
                            <div class="meta-date">
                                <i class="fas fa-calendar-alt"></i>
                                <span>{{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') : 'N/A' }}</span>
                            </div>
                        </div>

                        <h1 class="article-title">{{ $post->seo_title ?? $post->name ?? 'Tiêu đề bài viết' }}</h1>
                        
                        @if(isset($post->seo_desc) && $post->seo_desc)
                            <div class="article-excerpt">{{ $post->seo_desc }}</div>
                        @endif

                        <!-- Author Info -->
                        <div class="article-author">
                            <div class="author-avatar">
                         @if(isset($post->account) && isset($post->account->profile) && $post->account->profile->avatar)
                                     <img src="{{ asset('storage/' . $post->account->profile->avatar) }}" 
                                          alt="{{ $post->account->profile->name }}" 
                                         class="avatar-img">
                                @else
                                    <i class="fas fa-user avatar-img"></i>
                                @endif
                            </div>
                            <div class="author-info">
                                <div class="author-name">
                                     {{ $post->account->profile->name ?? 'Tác giả' }}
                                </div>
                                <div class="author-title">
                                     {{ $post->account->profile->title ?? 'Cộng tác viên' }}
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Featured Image -->
                     @if(isset($post->seo_image) && $post->seo_image)
                        <div class="article-featured-image">
                             <img src="/client/assets/images/posts/{{ $post->seo_image }}" 
                                  alt="{{ $post->seo_title ?? $post->name ?? 'Hình ảnh bài viết' }}" 
                                 class="featured-img">
                        </div>
                    @endif

                    <!-- Article Body -->
                    <div class="article-body">
                         {!! $post->content ?? 'Nội dung bài viết' !!}
                    </div>

                    <!-- Article Stats -->
                    <div class="article-stats">
                        <div class="stat-item">
                            <i class="fas fa-eye"></i>
                             <span>{{ $post->views ?? 0 }} lượt xem</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-heart"></i>
                            <span>{{ $post->likes ?? 0 }} lượt thích</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-comment"></i>
                            <span>{{ $post->comments ? $post->comments->count() : 0 }} bình luận</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-share"></i>
                            <span>{{ $post->shares ?? 0 }} lượt chia sẻ</span>
                        </div>
                    </div>

                    <!-- Article Tags -->
                    @php
                        $normalizedTags = [];
                        $rawTags = $post->tags ?? '';
                        if (is_string($rawTags) && !empty($rawTags)) {
                            foreach (array_filter(array_map('trim', explode(',', $rawTags))) as $t) {
                                $normalizedTags[] = ['slug' => \Illuminate\Support\Str::slug($t), 'name' => $t];
                            }
                        }
                    @endphp
                    @if(count($normalizedTags) > 0)
                        <div class="article-tags">
                            <div class="tags-title">
                                <i class="fas fa-tags"></i>
                                <span>Tags:</span>
                            </div>
                            <div class="tags-list">
                                @foreach($normalizedTags as $tag)
                                    <a href="/tim-kiem/{{ $tag['slug'] }}" class="tag-item">
                                        #{{ $tag['name'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Social Share -->
                    <div class="article-share">
                        <div class="share-title">
                            <i class="fas fa-share-alt"></i>
                            <span>Chia sẻ bài viết:</span>
                        </div>
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" 
                               target="_blank" 
                               class="share-btn facebook">
                                <i class="fab fa-facebook-f"></i>
                                Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($post->seo_title ?? $post->name ?? '') }}" 
                               target="_blank" 
                               class="share-btn twitter">
                                <i class="fab fa-twitter"></i>
                                Twitter
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(request()->url()) }}" 
                               target="_blank" 
                               class="share-btn linkedin">
                                <i class="fab fa-linkedin-in"></i>
                                LinkedIn
                            </a>
                            <a href="https://wa.me/?text={{ urlencode(($post->seo_title ?? $post->name ?? '') . ' ' . request()->url()) }}" 
                               target="_blank" 
                               class="share-btn whatsapp">
                                <i class="fab fa-whatsapp"></i>
                                WhatsApp
                            </a>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="comments-section">
                        <div class="comments-title">
                            <i class="fas fa-comments"></i>
                            <span>Bình luận ({{ $post->comments ? $post->comments->count() : 0 }})</span>
                        </div>
                        @if(session('success'))
                            <div class="alert alert-success" style="margin-top:10px;padding:10px 12px;border-radius:6px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger" style="margin-top:10px;padding:10px 12px;border-radius:6px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;">
                                <ul style="margin:0;padding-left:18px;">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(isset($post->comments) && $post->comments->count() > 0)
                            <div id="comments-list" class="comments-list">
                                @foreach($post->comments as $comment)
                                    <div id="comment-{{ $comment->id }}" class="comment-item" style="display:block;">
                                        <div class="comment-avatar">
                                            @if($comment->account && $comment->account->profile && $comment->account->profile->avatar)
                                                <img src="{{ asset('storage/' . $comment->account->profile->avatar) }}" alt="{{ $comment->account->profile->name }}" class="comment-avatar-img">
                                            @else
                                                <i class="fas fa-user comment-avatar-img"></i>
                                            @endif
                                        </div>
                                        <div class="comment-content">
                                            <div class="comment-header">
                                                <div class="comment-author">{{ $comment->account->profile->name ?? 'Khách' }}</div>
                                                <div class="comment-date">{{ $comment->created_at ? \Carbon\Carbon::parse($comment->created_at)->diffForHumans() : 'N/A' }}</div>
                                            </div>
                                            <div class="comment-text">{{ $comment->content }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div id="comments-pagination" class="comments-pagination" style="margin-top:12px;display:none;gap:8px;align-items:center;">
                                <button type="button" id="comments-prev" class="btn" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;background:#fff;cursor:pointer;">Trước</button>
                                <span id="comments-page-info" style="font-size:14px;color:#555;">Trang 1/1</span>
                                <button type="button" id="comments-next" class="btn" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;background:#fff;cursor:pointer;">Sau</button>
                            </div>
                        @else
                            <p class="no-comments">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                        @endif

                        <form class="comment-form" method="post" action="{{ route('comments.store') }}" style="margin-top:16px;">
                            @csrf
                            <input type="hidden" name="post_id" value="{{ $post->id }}">
                            <label for="comment-content">Viết bình luận</label>
                            <textarea id="comment-content" name="content" rows="3" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;">{{ old('content') }}</textarea>
                            <button type="submit" class="btn primary" style="margin-top:8px;padding:10px 16px;border-radius:6px;background:#0ea5e9;color:#fff;border:none;cursor:pointer;">Gửi bình luận</button>
                        </form>

                        <script>
                            (function(){
                                const list = document.getElementById('comments-list');
                                if (!list) return;
                                const items = Array.from(list.querySelectorAll('.comment-item'));
                                const perPage = 5;
                                let current = 1;

                                function render() {
                                    const total = items.length;
                                    const totalPages = Math.max(1, Math.ceil(total / perPage));
                                    if (current > totalPages) current = totalPages;
                                    const start = (current - 1) * perPage;
                                    const end = start + perPage;
                                    items.forEach((el, idx) => {
                                        el.style.display = (idx >= start && idx < end) ? 'flex' : 'none';
                                    });
                                    const pager = document.getElementById('comments-pagination');
                                    const info = document.getElementById('comments-page-info');
                                    const prev = document.getElementById('comments-prev');
                                    const next = document.getElementById('comments-next');
                                    if (total > perPage) {
                                        pager.style.display = 'flex';
                                        info.textContent = `Trang ${current}/${totalPages}`;
                                        prev.disabled = current === 1;
                                        next.disabled = current === totalPages;
                                    } else {
                                        pager.style.display = 'none';
                                    }
                                }

                                const prevBtn = document.getElementById('comments-prev');
                                const nextBtn = document.getElementById('comments-next');
                                if (prevBtn && nextBtn) {
                                    prevBtn.addEventListener('click', function(){ if (current > 1) { current--; render(); }});
                                    nextBtn.addEventListener('click', function(){ current++; render(); });
                                }

                                render();
                            })();
                        </script>
                    </div>
                </article>
            </main>

            <!-- Sidebar -->
            <aside class="article-sidebar">
                <!-- Categories Widget -->
                <div class="sidebar-widget">
                    <h4 class="widget-title">
                        <i class="fas fa-folder"></i>
                        <span>Danh mục</span>
                    </h4>
                    <ul class="categories-list">
                        @if(isset($categories) && count($categories) > 0)
                            @foreach($categories as $category)
                                <li>
                                    <a href="/{{ $category->slug }}">
                                        {{ $category->name }}
                                        <span class="category-count">({{ $category->posts_count ?? 0 }})</span>
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li><a href="/am-thuc">Ẩm thực</a></li>
                            <li><a href="/di-choi">Đi chơi</a></li>
                            <li><a href="/cuoc-song">Cuộc sống</a></li>
                            <li><a href="/tin-tuc">Tin tức</a></li>
                        @endif
                    </ul>
                </div>

                <!-- Popular Posts Widget -->
                <div class="sidebar-widget">
                    <h4 class="widget-title">
                        <i class="fas fa-fire"></i>
                        <span>Bài viết phổ biến</span>
                    </h4>
                    @if(isset($popularPosts) && count($popularPosts) > 0)
                        <div class="popular-posts">
                            @foreach($popularPosts as $popular_post)
                                <div class="popular-post-item">
                                    <a href="/{{ $popular_post->slug }}" class="popular-post-link">
                                        <div class="popular-post-image">
                                            @if(!empty($popular_post->seo_image))
                                                <img src="/client/assets/images/posts/{{ $popular_post->seo_image }}" 
                                                     alt="{{ $popular_post->seo_title }}">
                                            @else
                                                <div class="no-image">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="popular-post-info">
                                            <h5 class="popular-post-title">{{ $popular_post->seo_title ?? $popular_post->name }}</h5>
                                            <div class="popular-post-meta">
                                                <span class="post-date">{{ $popular_post->published_at ? \Carbon\Carbon::parse($popular_post->published_at)->format('d/m/Y') : 'N/A' }}</span>
                                                <span class="post-views">{{ $popular_post->views ?? 0 }} lượt xem</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="no-popular-posts">Chưa có bài viết phổ biến.</p>
                    @endif
                </div>

                <!-- Tags Widget -->
                <div class="sidebar-widget">
                    <h4 class="widget-title">
                        <i class="fas fa-tags"></i>
                        <span>Tags phổ biến</span>
                    </h4>
                    @if(isset($popularTags) && count($popularTags) > 0)
                        <div class="popular-tags" style="display: flex; flex-wrap: wrap; gap: 8px;">
                            @foreach($popularTags as $tag)
                                <a href="/tim-kiem/{{ $tag->slug }}" class="tag-item">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="no-tags">Chưa có tags.</p>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>