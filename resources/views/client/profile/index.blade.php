<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user->profile->name }}</title>
    <link rel="stylesheet" href="{{ asset('/client/assets/css/profile.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}">
    <link rel="mask-icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="{{ asset('/client/assets/images/logo/'. $settings['site_favicon']) }}" type="image/x-icon">
</head>
<body>
    <div class="overlay"></div>
    <div class="back-to-home"><i class="fa-solid fa-circle-left fa-shake" style="color: #ffffff;"></i> Quay lại</div>
    <div class="profile-container">
        <header style="background-image: url({{ asset('/client/assets/images/avatar/'. $user->profile->cover_photo) }});" class="profile-header">

        </header>
        <main class="profile-main">
            <div class="profile-row">
                <div class="profile-left">
                    <div class="profile-photo-left">
                        <img class="profile-photo"
                            src="{{ asset('/client/assets/images/avatar/'. $user->profile->avatar) }}" />
                        <div class="profile-active"></div>
                    </div>
                    <h4 class="profile-name">{{ $user->profile->name }}</h4>
                    <p class="profile-info">{{ $user->role->name }}</p>
                    <p class="profile-info">{{ $user->email }}</p>
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <p class="profile-number-stat">3,619</p>
                            <p class="profile-desc-stat">Người theo dõi</p>
                        </div>
                        <div class="profile-stat">
                            <p class="profile-number-stat">42</p>
                            <p class="profile-desc-stat">Đang theo dõi</p>
                        </div>
                        <div class="profile-stat">
                            <p class="profile-number-stat">{{ count($user->posts) }}</p>
                            <p class="profile-desc-stat">Bài đăng</p>
                        </div>
                        <div class="profile-stat">
                            <p class="profile-btn-follow">Follow</p>
                            <p class="profile-desc-stat">{{ $user->profile->name }}</p>
                        </div>
                    </div>
                    <p class="profile-desc">{{ $user->profile->bio }}</p>
                    <div class="profile-social">
                        <i class="fa-brands fa-facebook"></i>
                        <i class="fa-brands fa-tiktok"></i>
                        <i class="fa-brands fa-square-x-twitter"></i>
                        <i class="fa-brands fa-pinterest"></i>
                    </div>
                    <div class="edit-profile"><i class="fa-solid fa-pen-to-square fa-bounce" style="color: white;"></i> Sửa trang cá nhân</div>
                </div>
                <div class="profile-right">
                    @if ($user->role->slug !== 'user')
                    <div class="admin-posts-container">
                        <div class="admin-posts-header">
                            <span class="icon"><i class="fas fa-fire"></i></span>
                            <h3>BÀI VIẾT THUỘC VỀ <a href="/user/{{ $user->username }}" target="_blank" rel="noopener noreferrer">{{ $user->profile->name }}</a></h3>
                        </div>
                    
                        <div class="admin-posts-list">
                            @foreach ($user->posts as $post)
                                <a href="/{{ $post->slug }}" class="post-item">
                                    <div class="post-image">
                                        <img src="{{ asset('/client/assets/images/posts/'. $post->seo_image) }}" title="{{ $post->seo_title }}" alt="{{ $post->seo_title }}">
                                        <p class="post-category">{{ $post->category->name }}</p>
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
                    @else
                        
                    @endif
                </div>
            </div>
        </main>
    </div>
    <div class="back-to-home"><i class="fa-solid fa-circle-left fa-shake" style="color: #ffffff;"></i> Quay lại</div>

    <script>
        const backToHome = document.querySelector('.back-to-home');
        backToHome.addEventListener('click', () => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = "{{ $settings['site_url'] }}";
            }
            
        });

        const editProfile = document.querySelector('.edit-profile');
        editProfile.addEventListener('click', () => {
            alert('Sửa cái chóa gì?');
        });

        const followBtn = document.querySelector('.profile-btn-follow');
        followBtn.addEventListener('click', () => {
            alert('Follow thành công!');
        });

        const profilePhoto = document.querySelector('.profile-photo');
        const overlay = document.querySelector('.overlay');
        profilePhoto.addEventListener('click', () => {
            overlay.style.display = 'block';
            profilePhoto.classList.add('live-photo')
            profilePhoto.classList.remove('profile-photo')
        });
        overlay.addEventListener('click', () => {
            overlay.style.display = 'none';
            profilePhoto.classList.remove('live-photo');
            profilePhoto.classList.add('profile-photo');
        });

    </script>
</body>

</html>
