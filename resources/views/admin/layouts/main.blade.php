@php
    use Illuminate\Support\Facades\Auth;
@endphp
<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.module.css')
    <title>@yield('title')</title>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
          <!-- Menu -->
  
          <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
            <div class="app-brand demo">
              <a href="/admin" class="app-brand-link">
                <span class="app-brand-text demo menu-text fw-bolder ms-2"><img width="180" height="60" src="{{ asset('client/assets/images/logo/'. $settings['site_logo']) }}" alt=""></span>
              </a>
  
              <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
                <i class="bx bx-chevron-left bx-sm align-middle"></i>
              </a>
            </div>
  
            <div class="menu-inner-shadow"></div>
  
            <ul class="menu-inner py-1">
              <!-- Dashboard -->
              @if(Auth::user()->role_id == 1)
                <!-- Admin Dashboard -->
                <li class="menu-item">
                  <a href="{{ route('admin.dashboard.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Admin Dashboard</div>
                  </a>
                </li>
              @elseif(Auth::user()->role_id == 2)
                <!-- Staff Dashboard -->
                <li class="menu-item">
                  <a href="{{ route('admin.staff.dashboard.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Staff Dashboard</div>
                  </a>
                </li>
              @endif
  
              <!-- Layouts -->
              @if(Auth::user()->role_id == 1)
                <!-- Admin Posts Menu -->
                <li class="menu-item">
                  <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-layout"></i>
                    <div data-i18n="Layouts">Bài viết</div>
                  </a>
      
                  <ul class="menu-sub">
                    <li class="menu-item">
                      <a href="{{ route('admin.posts.index') }}" class="menu-link">
                        <div data-i18n="Without menu">Tất cả bài viết</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="{{ route('admin.posts.new') }}" class="menu-link">
                        <div data-i18n="Without navbar">Thêm bài viết mới</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-download"></i>
                        <div data-i18n="Scraper">Cào bài viết</div>
                      </a>
                      <ul class="menu-sub">
                        <li class="menu-item">
                          <a href="{{ route('admin.mia-scraper.index') }}" class="menu-link">
                            <div data-i18n="MIA Scraper"><i class="fas fa-download"></i> Cào từ MIA.vn</div>
                          </a>
                        </li>
                        <li class="menu-item">
                          <a href="{{ route('admin.tour-pro-scraper.index') }}" class="menu-link">
                            <div data-i18n="Tour Pro Scraper"><i class="fas fa-download"></i> Cào từ Tour.Pro.vn</div>
                          </a>
                        </li>
                        <li class="menu-item">
                          <a href="{{ route('admin.vinpearl-scraper.index') }}" class="menu-link">
                            <div data-i18n="Vinpearl Scraper"><i class="fas fa-download"></i> Cào từ Vinpearl.com</div>
                          </a>
                        </li>
                        <li class="menu-item">
                          <a href="{{ route('admin.vinpearl-link-extractor.index') }}" class="menu-link">
                            <div data-i18n="Vinpearl Link Extractor"><i class="fas fa-link"></i> Trích xuất link Vinpearl</div>
                          </a>
                        </li>
                      </ul>
                    </li>
                    <li class="menu-item">
                      <a href="{{ route('admin.link-extractor.index') }}" class="menu-link">
                        <div data-i18n="Link Extractor"><i class="fas fa-link"></i> Trích xuất Link</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="{{ route('admin.posts.published') }}" class="menu-link">
                        <div data-i18n="Without navbar">Bài viết công khai</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="{{ route('admin.posts.index') }}" class="menu-link">
                        <div data-i18n="Container">Bài viết nháp</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="{{ route('admin.posts.index') }}" class="menu-link">
                        <div data-i18n="Fluid">Bài viết chờ duyệt</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="{{ route('admin.posts.index') }}" class="menu-link">
                        <div data-i18n="Blank">Bài viết đã xóa</div>
                      </a>
                    </li>
                  </ul>
                </li>
              @elseif(Auth::user()->role_id == 2)
                <!-- Staff Posts Menu -->
                <li class="menu-item">
                  <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-layout"></i>
                    <div data-i18n="Layouts">Bài viết</div>
                  </a>
      
                  <ul class="menu-sub">
                    <li class="menu-item">
                      <a href="{{ route('admin.staff.posts.index') }}" class="menu-link">
                        <div data-i18n="Without menu">Tất cả bài viết</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="{{ route('admin.staff.posts.new') }}" class="menu-link">
                        <div data-i18n="Without navbar">Thêm bài viết mới</div>
                      </a>
                    </li>
                  </ul>
                </li>
              @endif
  
              @if(Auth::user()->role_id == 1)
                <li class="menu-header small text-uppercase">
                  <span class="menu-header-text">Danh mục</span>
                </li>
                <li class="menu-item">
                  <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-dock-top"></i>
                    <div data-i18n="Account Settings">Danh mục</div>
                  </a>
                  <ul class="menu-sub">
                    <li class="menu-item">
                      <a href="{{ route('admin.categories.index') }}" class="menu-link">
                        <div data-i18n="Account">Tất cả danh mục</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="{{ route('admin.categories.create') }}" class="menu-link">
                        <div data-i18n="Account">Thêm danh mục mới</div>
                      </a>
                    </li>
                  </ul>
                </li>

                <!-- Cache Management -->
                <li class="menu-item">
                  <a href="{{ route('admin.cache.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-trash"></i>
                    <div data-i18n="Cache">🗂️ Quản lý Cache</div>
                  </a>
                </li>

                <!-- Comments Management -->
                <li class="menu-item">
                  <a href="{{ route('admin.comments.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-comment"></i>
                    <div data-i18n="Comments">💬 Quản lý bình luận</div>
                  </a>
                </li>
              @endif

              @if(Auth::user()->role_id == 1)
                <li class="menu-item">
                  <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                    <div data-i18n="Authentications">Quản lý tài khoản</div>
                  </a>
                  <ul class="menu-sub">
                    <li class="menu-item">
                      <a href="auth-login-basic.html" class="menu-link" target="_blank">
                        <div data-i18n="Basic">Tất cả tài khoản</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="auth-register-basic.html" class="menu-link" target="_blank">
                        <div data-i18n="Basic">Tạo tài khoản mới</div>
                      </a>
                    </li>
                    <li class="menu-item">
                      <a href="auth-forgot-password-basic.html" class="menu-link" target="_blank">
                        <div data-i18n="Basic">Tài khoản vi phạm</div>
                      </a>
                    </li>
                  </ul>
                </li>
              @endif
              {{-- <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                  <i class="menu-icon tf-icons bx bx-cube-alt"></i>
                  <div data-i18n="Misc">Misc</div>
                </a>
                <ul class="menu-sub">
                  <li class="menu-item">
                    <a href="pages-misc-error.html" class="menu-link">
                      <div data-i18n="Error">Error</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="pages-misc-under-maintenance.html" class="menu-link">
                      <div data-i18n="Under Maintenance">Under Maintenance</div>
                    </a>
                  </li>
                </ul>
              </li> --}}
              <!-- Components -->
              {{-- <li class="menu-header small text-uppercase"><span class="menu-header-text">Components</span></li>
              <!-- Cards -->
              <li class="menu-item">
                <a href="cards-basic.html" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-collection"></i>
                  <div data-i18n="Basic">Cards</div>
                </a>
              </li>
              <!-- User interface -->
              <li class="menu-item">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                  <i class="menu-icon tf-icons bx bx-box"></i>
                  <div data-i18n="User interface">User interface</div>
                </a>
                <ul class="menu-sub">
                  <li class="menu-item">
                    <a href="ui-accordion.html" class="menu-link">
                      <div data-i18n="Accordion">Accordion</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-alerts.html" class="menu-link">
                      <div data-i18n="Alerts">Alerts</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-badges.html" class="menu-link">
                      <div data-i18n="Badges">Badges</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-buttons.html" class="menu-link">
                      <div data-i18n="Buttons">Buttons</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-carousel.html" class="menu-link">
                      <div data-i18n="Carousel">Carousel</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-collapse.html" class="menu-link">
                      <div data-i18n="Collapse">Collapse</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-dropdowns.html" class="menu-link">
                      <div data-i18n="Dropdowns">Dropdowns</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-footer.html" class="menu-link">
                      <div data-i18n="Footer">Footer</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-list-groups.html" class="menu-link">
                      <div data-i18n="List Groups">List groups</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-modals.html" class="menu-link">
                      <div data-i18n="Modals">Modals</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-navbar.html" class="menu-link">
                      <div data-i18n="Navbar">Navbar</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-offcanvas.html" class="menu-link">
                      <div data-i18n="Offcanvas">Offcanvas</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-pagination-breadcrumbs.html" class="menu-link">
                      <div data-i18n="Pagination &amp; Breadcrumbs">Pagination &amp; Breadcrumbs</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-progress.html" class="menu-link">
                      <div data-i18n="Progress">Progress</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-spinners.html" class="menu-link">
                      <div data-i18n="Spinners">Spinners</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-tabs-pills.html" class="menu-link">
                      <div data-i18n="Tabs &amp; Pills">Tabs &amp; Pills</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-toasts.html" class="menu-link">
                      <div data-i18n="Toasts">Toasts</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-tooltips-popovers.html" class="menu-link">
                      <div data-i18n="Tooltips & Popovers">Tooltips &amp; popovers</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="ui-typography.html" class="menu-link">
                      <div data-i18n="Typography">Typography</div>
                    </a>
                  </li>
                </ul>
              </li> --}}
  
              <!-- Extended components -->
              {{-- <li class="menu-item">
                <a href="javascript:void(0)" class="menu-link menu-toggle">
                  <i class="menu-icon tf-icons bx bx-copy"></i>
                  <div data-i18n="Extended UI">Extended UI</div>
                </a>
                <ul class="menu-sub">
                  <li class="menu-item">
                    <a href="extended-ui-perfect-scrollbar.html" class="menu-link">
                      <div data-i18n="Perfect Scrollbar">Perfect scrollbar</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="extended-ui-text-divider.html" class="menu-link">
                      <div data-i18n="Text Divider">Text Divider</div>
                    </a>
                  </li>
                </ul>
              </li>
  
              <li class="menu-item">
                <a href="icons-boxicons.html" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-crown"></i>
                  <div data-i18n="Boxicons">Boxicons</div>
                </a>
              </li>
  
              <!-- Forms & Tables -->
              <li class="menu-header small text-uppercase"><span class="menu-header-text">Forms &amp; Tables</span></li>
              <!-- Forms -->
              <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                  <i class="menu-icon tf-icons bx bx-detail"></i>
                  <div data-i18n="Form Elements">Form Elements</div>
                </a>
                <ul class="menu-sub">
                  <li class="menu-item">
                    <a href="forms-basic-inputs.html" class="menu-link">
                      <div data-i18n="Basic Inputs">Basic Inputs</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="forms-input-groups.html" class="menu-link">
                      <div data-i18n="Input groups">Input groups</div>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                  <i class="menu-icon tf-icons bx bx-detail"></i>
                  <div data-i18n="Form Layouts">Form Layouts</div>
                </a>
                <ul class="menu-sub">
                  <li class="menu-item">
                    <a href="form-layouts-vertical.html" class="menu-link">
                      <div data-i18n="Vertical Form">Vertical Form</div>
                    </a>
                  </li>
                  <li class="menu-item">
                    <a href="form-layouts-horizontal.html" class="menu-link">
                      <div data-i18n="Horizontal Form">Horizontal Form</div>
                    </a>
                  </li>
                </ul>
              </li>
              <!-- Tables -->
              <li class="menu-item">
                <a href="tables-basic.html" class="menu-link">
                  <i class="menu-icon tf-icons bx bx-table"></i>
                  <div data-i18n="Tables">Tables</div>
                </a>
              </li> --}}
              @if(Auth::user()->role_id == 1)
                <!-- Setting -->
                <li class="menu-header small text-uppercase"><span class="menu-header-text">Cài đặt</span></li>
                <li class="menu-item">
                  <a
                    href="{{ route('admin.settings.index') }}"
                    target=""
                    class="menu-link"
                  >
                    <i class="menu-icon tf-icons bx bx-support"></i>
                    <div data-i18n="Support">Cài đặt chung</div>
                  </a>
                </li>
              @endif
              <li class="menu-item">
                <a
                  href=""
                  target="_blank"
                  class="menu-link"
                >
                  <i class="menu-icon tf-icons bx bx-file"></i>
                  <div data-i18n="Documentation">Đăng xuất</div>
                </a>
              </li>
            </ul>
          </aside>
          <!-- / Menu -->
  
          <!-- Layout container -->
          <div class="layout-page">
            <!-- Navbar -->
  
            <nav
              class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
              id="layout-navbar"
            >
              <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                  <i class="bx bx-menu bx-sm"></i>
                </a>
              </div>
  
              <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                <!-- Search -->
                <div class="navbar-nav align-items-center">
                  <div class="nav-item d-flex align-items-center">
                    <i class="bx bx-search fs-4 lh-0"></i>
                    <input
                      type="text"
                      class="form-control border-0 shadow-none"
                      placeholder="Tìm kiếm thứ gì đó..."
                      aria-label="Search..."
                    />
                  </div>
                </div>
                <!-- /Search -->
  
                <ul class="navbar-nav flex-row align-items-center ms-auto">
                  <!-- Place this tag where you want the button to render. -->
                  <li class="nav-item lh-1 me-3">
                    <a
                      class="github-button"
                      href="https://github.com/themeselection/sneat-html-admin-template-free"
                      data-icon="octicon-star"
                      data-size="large"
                      data-show-count="true"
                      aria-label="Star themeselection/sneat-html-admin-template-free on GitHub"
                      >Star</a
                    >
                  </li>
  
                  <!-- User -->
                  <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarAvatarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <img src="{{ asset('client/assets/images/avatar/'. (Auth::user()->profile->avatar ?? 'default.jpg')) }}" alt="Avatar"
                           class="rounded-circle" width="40" height="40">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarAvatarDropdown">
                      <li class="px-3 py-2">
                        <div class="d-flex align-items-center">
                          <img src="{{ asset('client/assets/images/avatar/'. (Auth::user()->profile->avatar ?? 'default.jpg')) }}" alt="Avatar"
                               class="rounded-circle me-2" width="40" height="40">
                          <div>
                            <div class="fw-semibold">{{ Auth::user()->profile->name ?? 'User' }}</div>
                            <small class="text-muted">
                              @if(Auth::user()->role_id == 1)
                                Admin
                              @elseif(Auth::user()->role_id == 2)
                                Staff
                              @else
                                User
                              @endif
                            </small>
                          </div>
                        </div>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <a class="dropdown-item" href="#">
                          <i class="bi bi-person me-2"></i> Trang cá nhân
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="#">
                          <i class="bi bi-gear me-2"></i> Cài đặt
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="#">
                          <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                        </a>
                      </li>
                    </ul>
                  </li>
                  
                  <!--/ User -->
                </ul>
              </div>
            </nav>
  
            <!-- / Navbar -->

            <!-- Content -->
            @yield('content')
            <!-- /Content -->


            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
                <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column">
                    <div class="mb-2 mb-md-0">
                        {!! Blade::render($settings['copyright']) !!}
                    </div>
                    <div>
                        <a href="#">{{ Auth::user()->profile->name ?? 'User' }}</a>
                        <span class="text-muted ms-2">
                            @if(Auth::user()->role_id == 1)
                                (Admin)
                            @elseif(Auth::user()->role_id == 2)
                                (Staff)
                            @else
                                (User)
                            @endif
                        </span>
                    </div>
                </div>
            </footer>
            <!-- / Footer -->

            <!-- Overlay -->
            <div class="layout-overlay layout-menu-toggle"></div>
        </div>
        <!-- / Layout wrapper -->

        <div class="buy-now">
            <button onclick="toggleMenu(this)" class="btn btn-danger btn-buy-now">Ẩn Menu</button>
        </div>
        <script>
          function toggleMenu(btn) {
              const menu = document.querySelector('#layout-menu');
              if (menu.style.display === 'none') {
                  menu.style.display = 'block';
                  btn.textContent = 'Ẩn Menu';
              } else {
                  menu.style.display = 'none';
                  btn.textContent = 'Bật Menu';
              }
          }
      </script>
      <style>
        @media (max-width: 768px) {
          .buy-now {
            display: none;
          }
        }
      </style>
        @include('admin.module.js')
        
        <!-- Quill Editor CSS -->
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
        
        <!-- Quill Editor JS -->
        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
        
        <!-- Image Context Menu CSS -->
        <style>
            .image-context-menu {
                position: fixed;
                background: white;
                border: 1px solid #ccc;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                min-width: 200px;
                padding: 8px 0;
            }
            
            .context-menu-item {
                padding: 8px 16px;
                cursor: pointer;
                transition: background-color 0.2s;
                font-size: 14px;
            }
            
            .context-menu-item:hover {
                background-color: #f8f9fa;
            }
            
            .context-menu-separator {
                height: 1px;
                background-color: #e9ecef;
                margin: 4px 0;
            }
        </style>
        
        <!-- Image Context Menu JavaScript -->
        <script>
            // Global functions for image context menu
            function showImageContextMenu(imgElement, index) {
                // Xóa context menu cũ nếu có
                const oldMenu = document.querySelector('.image-context-menu');
                if (oldMenu) oldMenu.remove();

                const menu = document.createElement('div');
                menu.className = 'image-context-menu';
                menu.innerHTML = `
                    <div class="context-menu-item" onclick="alignImage(${index}, 'left')">⬅️ Căn trái</div>
                    <div class="context-menu-item" onclick="alignImage(${index}, 'center')">⏺️ Căn giữa</div>
                    <div class="context-menu-item" onclick="alignImage(${index}, 'right')">➡️ Căn phải</div>
                    <div class="context-menu-item" onclick="alignImage(${index}, 'full')">📏 Căn đầy</div>
                    <div class="context-menu-separator"></div>
                    <div class="context-menu-item" onclick="resizeImage(${index}, 'small')">📏 Nhỏ</div>
                    <div class="context-menu-item" onclick="resizeImage(${index}, 'medium')">📏 Vừa</div>
                    <div class="context-menu-item" onclick="resizeImage(${index}, 'large')">📏 Lớn</div>
                    <div class="context-menu-separator"></div>
                    <div class="context-menu-item" onclick="removeImage(${index})">🗑️ Xóa ảnh</div>
                `;

                // Đặt vị trí menu
                const rect = imgElement.getBoundingClientRect();
                menu.style.left = rect.left + 'px';
                menu.style.top = (rect.bottom + 5) + 'px';

                document.body.appendChild(menu);

                // Đóng menu khi click ra ngoài
                setTimeout(() => {
                    document.addEventListener('click', function closeMenu() {
                        menu.remove();
                        document.removeEventListener('click', closeMenu);
                    });
                }, 100);
            }

            // Căn chỉnh ảnh
            function alignImage(index, alignment) {
                const quill = window.currentQuill || document.querySelector('#editor')?.__quill;
                if (!quill) return;
                
                const [leaf] = quill.getLeaf(index);
                if (leaf && leaf.domNode && leaf.domNode.tagName === 'IMG') {
                    const img = leaf.domNode;
                    
                    // Xóa class cũ
                    img.classList.remove('align-left', 'align-center', 'align-right', 'align-full');
                    
                    // Xóa style cũ
                    img.style.float = '';
                    img.style.display = '';
                    img.style.margin = '';
                    img.style.width = '';
                    img.style.textAlign = '';
                    
                    // Thêm inline CSS mới
                    switch(alignment) {
                        case 'left':
                            img.style.float = 'left';
                            img.style.margin = '0 20px 20px 0';
                            img.style.maxWidth = '50%';
                            break;
                        case 'center':
                            img.style.display = 'block';
                            img.style.margin = '20px auto';
                            img.style.textAlign = 'center';
                            break;
                        case 'right':
                            img.style.float = 'right';
                            img.style.margin = '0 0 20px 20px';
                            img.style.maxWidth = '50%';
                            break;
                        case 'full':
                            img.style.width = '100%';
                            img.style.height = 'auto';
                            img.style.margin = '20px 0';
                            break;
                    }
                }
            }

            // Thay đổi kích thước ảnh
            function resizeImage(index, size) {
                const quill = window.currentQuill || document.querySelector('#editor')?.__quill;
                if (!quill) return;
                
                const [leaf] = quill.getLeaf(index);
                if (leaf && leaf.domNode && leaf.domNode.tagName === 'IMG') {
                    const img = leaf.domNode;
                    
                    switch(size) {
                        case 'small':
                            img.style.width = '200px';
                            break;
                        case 'medium':
                            img.style.width = '400px';
                            break;
                        case 'large':
                            img.style.width = '600px';
                            break;
                    }
                }
            }

            // Xóa ảnh
            function removeImage(index) {
                const quill = window.currentQuill || document.querySelector('#editor')?.__quill;
                if (!quill) return;
                
                quill.deleteText(index, 1);
            }
        </script>
</body>

</html>
