<div class="single-page">
    <div class="single">
        <h1 class="title">{{ $post->seo_title }}</h1>
        <div class="meta">
            <div class="author">
                <i class="fa-solid fa-user"></i>
                <span>Người đăng: <a href="/user/{{ $post->account->username }}">{{ $post->account->profile->name }}</a></span>
            </div>
            <div class="date">
                <i class="fa-solid fa-calendar"></i>
                <span>Ngày đăng: {{ \Carbon\Carbon::parse($post->published_at)->format('d/m/Y') }}</span>
            </div>
            <div class="view">
                <i class="fa-solid fa-eye"></i>
                <span>Lượt xem: {{ ($post->views < 0 || $post->views === 0) ? 'Chưa có lượt xem' : $post->views }}</span>
            </div>
            <div class="comment">
                <i class="fa-solid fa-comment"></i>
                <span>Lượt bình luận: <a href="#comment-section">{{ count($post->comments) }}</a></span>
            </div>
            <div class="rating">
                <i class="fa-solid fa-star"></i>
                <span style="margin-left: 5px;">Đánh giá: <span class="star"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span></span>
            </div>
        </div>
        <div class="short-description">
            <p>{{ $post->seo_desc }}</p>
        </div>
        <div class="image">
            <img src="{{ asset('/client/assets/images/posts/'. $post->seo_image) }}" title="{!! $post->seo_title !!}" alt="{!! $post->seo_title !!}">
            <figcaption>{!! $post->name !!}</figcaption>
        </div>
        <div class="content">
            <p>{!! $post->content !!}</p>
        </div>
        <div class="tag">
            <span>Tag: </span>
            @php
                $tags = explode(',', $post->tags);
            @endphp
            @foreach($tags as $tag)
                <a href="/tim-kiem/{{ trim($tag) }}">#{{ trim($tag) }}</a>
            @endforeach
        </div>
        <div class="share">
            <span>Chia sẻ: </span>
            <a href="{!! $settings['facebook_link'] !!}"><i class="fa-brands fa-facebook"></i></a>
            <a href="{!! $settings['twitter_link'] !!}"><i class="fa-brands fa-twitter"></i></a>
            <a href="{!! $settings['instagram_link'] !!}"><i class="fa-brands fa-square-instagram"></i></a>
            <a href="{!! $settings['facebook_link'] !!}"><i class="fa-brands fa-youtube"></i></a>
        </div>
    </div>
    <div class="sidebar">
        {{-- @include('client.templates.TOC') --}}
        @include('client.templates.related')
    </div>
    <div id="comment-section" class="comment-section">
        <h2>Bình luận</h2>
        @if($settings['enable_comments'] == 'true')
            <div class="comment-list">
                @foreach($post->comments as $comment)
                    <div class="comment-item">
                        <div class="comment-avatar">
                            <img width="100%" height="100%" src="{{ $post->account->profile->avatar === null ? $post->account->profile->avatar : asset('/client/assets/images/logo/icon-review-hai-phong.png') }}" alt="Ảnh đại diện" title="Ảnh đại diện">
                        </div>
                        <div class="comment-content">
                            <div class="comment-meta">
                                <span class="comment-author"><a href="/user/{{ $post->account->username }}">{{ $post->account->profile->name }}</a></a></span>
                                <span class="comment-date">{{ \Carbon\Carbon::parse($comment->created_at)->format('d/m/Y') }}</span>
                            </div>
                            <div class="comment-text">
                                <p>{!! $comment->content !!}</p>
                            </div>
                            <div class="comment-actions">
                                <button class="like-btn" onclick="toggleLike(this)">Thích</button>
                                <button class="reply-btn" onclick="toggleReplyForm(this)">Trả lời</button>
                            </div>
                            <div class="reply-form" style="display: none;">
                                <textarea placeholder="Nhập câu trả lời..."></textarea>
                                <button class="submit-reply">Gửi</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="background: linear-gradient(135deg, #ff6a00, #ee0979); color: white; padding: 10px 20px; border-radius: 12px; text-align: center; font-family: Arial, sans-serif; box-shadow: 0 4px 10px rgba(0,0,0,0.2); max-width: 500px; margin: 30px auto; font-size: 18px;">
                <p style="margin: 0;">Tính năng Bình luận đang nâng cấp</p>
            </div>
        @endif
    </div>
</div>