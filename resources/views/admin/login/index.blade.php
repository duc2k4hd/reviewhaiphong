<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    @include('admin.module.css')
    <title>Đăng nhập Admin</title>
</head>

<body>
    <!-- Content -->

    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <!-- Register -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">
                            <a href="/admin" class="app-brand-link gap-2">
                                <span class=""><img width="200px" height="70px" src="{{ asset('client/assets/images/logo/logo-review-hai-phong.png') }}" alt=""></span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-2">Dành riêng cho ADMIN 👋</h4>
                        <p class="mb-4">Vui lòng dùng tài khoản ADMIN để đăng nhập!</p>

                        <form id="formAuthentication" class="mb-3" action="{{ route('admin.login.post') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">Email của bạn</label>
                                <input type="text" class="form-control" id="email" name="email"
                                    placeholder="Nhập Email" autofocus />
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="password">Mật khẩu xác thực</label>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="Nhập mật khẩu của bạn!"
                                        aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember-me" />
                                    <label class="form-check-label" for="remember-me">Lưu mật khẩu</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100" type="submit">Đăng nhập</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- / Content -->
    @include('admin.module.js')
</body>

</html>
