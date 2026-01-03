<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    protected $account;

    public function __construct()
    {
        $this->loadAccount();
    }

    public function loadAccount()
    {
        $this->account = auth()->guard('web')->user();
    }

    public function index()
    {
        $settings = Setting::getSettings();
        
        $settingGroups = [
            'general' => [
                'title' => 'Cài đặt chung',
                'settings' => [
                    'site_name' => [
                        'label' => 'Tên website',
                        'type' => 'text',
                        'value' => $settings['site_name'] ?? '',
                        'placeholder' => 'Nhập tên website',
                        'required' => true
                    ],
                    'site_title' => [
                        'label' => 'Tiêu đề website',
                        'type' => 'text',
                        'value' => $settings['site_title'] ?? '',
                        'placeholder' => 'Nhập tiêu đề website'
                    ],
                    'site_description' => [
                        'label' => 'Mô tả website',
                        'type' => 'textarea',
                        'value' => $settings['site_description'] ?? '',
                        'placeholder' => 'Nhập mô tả website'
                    ],
                    'site_keywords' => [
                        'label' => 'Từ khóa SEO',
                        'type' => 'textarea',
                        'value' => $settings['site_keywords'] ?? '',
                        'placeholder' => 'Nhập từ khóa SEO, phân cách bằng dấu phẩy'
                    ],
                    'seo_keywords' => [
                        'label' => 'Từ khóa SEO (cũ)',
                        'type' => 'textarea',
                        'value' => $settings['seo_keywords'] ?? '',
                        'placeholder' => 'Từ khóa SEO cũ'
                    ],
                    'seo_author' => [
                        'label' => 'Tác giả SEO',
                        'type' => 'text',
                        'value' => $settings['seo_author'] ?? '',
                        'placeholder' => 'Nhập tác giả'
                    ],
                    'site_url' => [
                        'label' => 'URL website',
                        'type' => 'url',
                        'value' => $settings['site_url'] ?? '',
                        'placeholder' => 'https://example.com'
                    ],
                    'site_slug' => [
                        'label' => 'Slug website',
                        'type' => 'text',
                        'value' => $settings['site_slug'] ?? '',
                        'placeholder' => 'hai-phong-life'
                    ],
                    'site_timezone' => [
                        'label' => 'Múi giờ',
                        'type' => 'text',
                        'value' => $settings['site_timezone'] ?? 'Asia/Ho_Chi_Minh',
                        'placeholder' => 'Asia/Ho_Chi_Minh'
                    ],
                    'site_language' => [
                        'label' => 'Ngôn ngữ',
                        'type' => 'text',
                        'value' => $settings['site_language'] ?? 'vi',
                        'placeholder' => 'vi'
                    ],
                    'enable_ssl' => [
                        'label' => 'Bật SSL',
                        'type' => 'checkbox',
                        'value' => $settings['enable_ssl'] ?? 'true',
                        'description' => 'Kích hoạt HTTPS'
                    ],
                    'maintenance_mode' => [
                        'label' => 'Chế độ bảo trì',
                        'type' => 'checkbox',
                        'value' => $settings['maintenance_mode'] ?? 'false',
                        'description' => 'Bật chế độ bảo trì'
                    ],
                    'enable_registration' => [
                        'label' => 'Cho phép đăng ký',
                        'type' => 'checkbox',
                        'value' => $settings['enable_registration'] ?? 'true',
                        'description' => 'Cho phép người dùng đăng ký tài khoản'
                    ],
                    'enable_newsletter' => [
                        'label' => 'Bật newsletter',
                        'type' => 'checkbox',
                        'value' => $settings['enable_newsletter'] ?? 'true',
                        'description' => 'Kích hoạt tính năng newsletter'
                    ],
                    'allow_file_uploads' => [
                        'label' => 'Cho phép upload file',
                        'type' => 'checkbox',
                        'value' => $settings['allow_file_uploads'] ?? 'true',
                        'description' => 'Cho phép người dùng upload file'
                    ]
                ]
            ],
            'images' => [
                'title' => 'Hình ảnh & Media',
                'settings' => [
                    'site_logo' => [
                        'label' => 'Logo website',
                        'type' => 'image',
                        'value' => $settings['site_logo'] ?? '',
                        'placeholder' => 'Chọn logo website'
                    ],
                    'site_favicon' => [
                        'label' => 'Favicon',
                        'type' => 'image',
                        'value' => $settings['site_favicon'] ?? '',
                        'placeholder' => 'Chọn favicon'
                    ],
                    'site_image' => [
                        'label' => 'Hình ảnh website',
                        'type' => 'image',
                        'value' => $settings['site_image'] ?? '',
                        'placeholder' => 'Chọn hình ảnh website'
                    ],
                    'site_banner' => [
                        'label' => 'Banner website',
                        'type' => 'image',
                        'value' => $settings['site_banner'] ?? '',
                        'placeholder' => 'Chọn banner website'
                    ],
                    'logo_ads_1' => [
                        'label' => 'Logo quảng cáo 1',
                        'type' => 'image',
                        'value' => $settings['logo_ads_1'] ?? '',
                        'placeholder' => 'Chọn logo quảng cáo'
                    ],
                    'avatar_admin' => [
                        'label' => 'Avatar admin',
                        'type' => 'image',
                        'value' => $settings['avatar_admin'] ?? '',
                        'placeholder' => 'Chọn avatar admin'
                    ],
                    'bo_cong_thuong' => [
                        'label' => 'Logo Bộ Công Thương',
                        'type' => 'image',
                        'value' => $settings['bo_cong_thuong'] ?? '',
                        'placeholder' => 'Chọn logo Bộ Công Thương'
                    ],
                    'dmca_logo' => [
                        'label' => 'Logo DMCA',
                        'type' => 'url',
                        'value' => $settings['dmca_logo'] ?? '',
                        'placeholder' => 'Nhập URL logo DMCA'
                    ]
                ]
            ],
            'contact' => [
                'title' => 'Thông tin liên hệ',
                'settings' => [
                    'contact_email' => [
                        'label' => 'Email liên hệ',
                        'type' => 'email',
                        'value' => $settings['contact_email'] ?? '',
                        'placeholder' => 'Nhập email liên hệ'
                    ],
                    'contact_phone' => [
                        'label' => 'Số điện thoại',
                        'type' => 'text',
                        'value' => $settings['contact_phone'] ?? '',
                        'placeholder' => 'Nhập số điện thoại'
                    ],
                    'contact_address' => [
                        'label' => 'Địa chỉ',
                        'type' => 'textarea',
                        'value' => $settings['contact_address'] ?? '',
                        'placeholder' => 'Nhập địa chỉ'
                    ],
                    'contact_facebook' => [
                        'label' => 'Facebook',
                        'type' => 'url',
                        'value' => $settings['contact_facebook'] ?? '',
                        'placeholder' => 'Nhập link Facebook'
                    ],
                    'contact_youtube' => [
                        'label' => 'YouTube',
                        'type' => 'url',
                        'value' => $settings['contact_youtube'] ?? '',
                        'placeholder' => 'Nhập link YouTube'
                    ],
                    'contact_zalo' => [
                        'label' => 'Zalo',
                        'type' => 'text',
                        'value' => $settings['contact_zalo'] ?? '',
                        'placeholder' => 'Nhập số Zalo'
                    ],
                    'contact_form_recipient' => [
                        'label' => 'Email nhận form liên hệ',
                        'type' => 'email',
                        'value' => $settings['contact_form_recipient'] ?? '',
                        'placeholder' => 'support@example.com'
                    ]
                ]
            ],
            'social' => [
                'title' => 'Mạng xã hội',
                'settings' => [
                    'facebook_link' => [
                        'label' => 'Facebook',
                        'type' => 'url',
                        'value' => $settings['facebook_link'] ?? '',
                        'placeholder' => 'https://facebook.com/username'
                    ],
                    'twitter_link' => [
                        'label' => 'Twitter',
                        'type' => 'url',
                        'value' => $settings['twitter_link'] ?? '',
                        'placeholder' => 'https://twitter.com/username'
                    ],
                    'instagram_link' => [
                        'label' => 'Instagram',
                        'type' => 'url',
                        'value' => $settings['instagram_link'] ?? '',
                        'placeholder' => 'https://instagram.com/username'
                    ],
                    'telegram_link' => [
                        'label' => 'Telegram',
                        'type' => 'url',
                        'value' => $settings['telegram_link'] ?? '',
                        'placeholder' => 'https://t.me/username'
                    ],
                    'discord_link' => [
                        'label' => 'Discord',
                        'type' => 'url',
                        'value' => $settings['discord_link'] ?? '',
                        'placeholder' => 'https://discord.gg/invite'
                    ]
                ]
            ],
            'content' => [
                'title' => 'Cài đặt nội dung',
                'settings' => [
                    'posts_per_page' => [
                        'label' => 'Số bài viết mỗi trang',
                        'type' => 'number',
                        'value' => $settings['posts_per_page'] ?? '10',
                        'placeholder' => 'Nhập số bài viết mỗi trang',
                        'min' => 1,
                        'max' => 50
                    ],
                    'comments_per_page' => [
                        'label' => 'Số bình luận mỗi trang',
                        'type' => 'number',
                        'value' => $settings['comments_per_page'] ?? '20',
                        'placeholder' => 'Nhập số bình luận mỗi trang',
                        'min' => 1,
                        'max' => 100
                    ],
                    'auto_approve_comments' => [
                        'label' => 'Tự động duyệt bình luận',
                        'type' => 'checkbox',
                        'value' => $settings['auto_approve_comments'] ?? '0',
                        'description' => 'Bình luận mới sẽ được duyệt tự động'
                    ],
                    'enable_comments' => [
                        'label' => 'Bật chức năng bình luận',
                        'type' => 'checkbox',
                        'value' => $settings['enable_comments'] ?? '1',
                        'description' => 'Cho phép người dùng bình luận'
                    ],
                    'url_banner' => [
                        'label' => 'URL banner',
                        'type' => 'text',
                        'value' => $settings['url_banner'] ?? '',
                        'placeholder' => 'am-thuc'
                    ]
                ]
            ],
            'seo' => [
                'title' => 'Cài đặt SEO & Analytics',
                'settings' => [
                    'google_analytics' => [
                        'label' => 'Google Analytics Code',
                        'type' => 'textarea',
                        'value' => $settings['google_analytics'] ?? '',
                        'placeholder' => 'Nhập Google Analytics tracking code'
                    ],
                    'google_search_console' => [
                        'label' => 'Google Search Console',
                        'type' => 'text',
                        'value' => $settings['google_search_console'] ?? '',
                        'placeholder' => 'Nhập meta tag Google Search Console'
                    ],
                    'google_tag_header' => [
                        'label' => 'Google Tag Manager (Header)',
                        'type' => 'textarea',
                        'value' => $settings['google_tag_header'] ?? '',
                        'placeholder' => 'Nhập Google Tag Manager code cho header'
                    ],
                    'google_tag_body' => [
                        'label' => 'Google Tag Manager (Body)',
                        'type' => 'textarea',
                        'value' => $settings['google_tag_body'] ?? '',
                        'placeholder' => 'Nhập Google Tag Manager code cho body'
                    ],
                    'bing_tag_header' => [
                        'label' => 'Bing Webmaster Tools',
                        'type' => 'textarea',
                        'value' => $settings['bing_tag_header'] ?? '',
                        'placeholder' => 'Nhập Bing verification code'
                    ],
                    'facebook_pixel' => [
                        'label' => 'Facebook Pixel',
                        'type' => 'textarea',
                        'value' => $settings['facebook_pixel'] ?? '',
                        'placeholder' => 'Nhập Facebook Pixel code'
                    ],
                    'site_pinterest' => [
                        'label' => 'Pinterest Domain Verification',
                        'type' => 'textarea',
                        'value' => $settings['site_pinterest'] ?? '',
                        'placeholder' => 'Nhập Pinterest verification code'
                    ]
                ]
            ],
            'legal' => [
                'title' => 'Thông tin pháp lý',
                'settings' => [
                    'copyright' => [
                        'label' => 'Copyright',
                        'type' => 'textarea',
                        'value' => $settings['copyright'] ?? '',
                        'placeholder' => 'Nhập thông tin copyright'
                    ],
                    'dmca' => [
                        'label' => 'DMCA Protection',
                        'type' => 'url',
                        'value' => $settings['dmca'] ?? '',
                        'placeholder' => 'https://www.dmca.com/Protection/Status.aspx'
                    ]
                ]
            ]
        ];

        return view('admin.settings.index', compact('settingGroups'));
    }

    public function update(Request $request)
    {
        try {
            Log::info('Settings update request received', [
                'request_data' => $request->all(),
                'admin_id' => $this->account->id ?? 0
            ]);
            
            $data = $request->except(['_token', '_method']);
            
            // Lấy tất cả settings hiện tại để đảm bảo không bị null
            $currentSettings = Setting::getSettings();
            
            // Xử lý upload ảnh
            $imageFields = [
                'site_logo', 'site_favicon', 'site_image', 'site_banner', 
                'logo_ads_1', 'bo_cong_thuong'
            ];
            foreach ($imageFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $fileName = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                    
                    // Lưu ảnh vào thư mục client/assets/images/logo/
                    $logoPath = public_path('client/assets/images/logo');
                    if (!file_exists($logoPath)) {
                        mkdir($logoPath, 0755, true);
                    }
                    
                    $file->move($logoPath, $fileName);
                    
                    // Chỉ lưu tên ảnh vào database
                    $data[$field] = $fileName;
                } elseif ($request->has($field . '_current')) {
                    // Giữ lại ảnh cũ nếu không upload ảnh mới
                    $data[$field] = $request->input($field . '_current');
                } elseif (isset($currentSettings[$field])) {
                    // Giữ lại giá trị cũ nếu không có gì thay đổi
                    $data[$field] = $currentSettings[$field];
                }
            }
            
            // Xử lý riêng cho avatar_admin
            if ($request->hasFile('avatar_admin')) {
                $file = $request->file('avatar_admin');
                $fileName = time() . '_avatar_admin.' . $file->getClientOriginalExtension();
                
                // Lưu ảnh vào thư mục client/assets/images/avatar/
                $avatarPath = public_path('client/assets/images/avatar');
                if (!file_exists($avatarPath)) {
                    mkdir($avatarPath, 0755, true);
                }
                
                $file->move($avatarPath, $fileName);
                
                // Chỉ lưu tên ảnh vào database
                $data['avatar_admin'] = $fileName;
            } elseif ($request->has('avatar_admin_current')) {
                // Giữ lại ảnh cũ nếu không upload ảnh mới
                $data['avatar_admin'] = $request->input('avatar_admin_current');
            } elseif (isset($currentSettings['avatar_admin'])) {
                // Giữ lại giá trị cũ nếu không có gì thay đổi
                $data['avatar_admin'] = $currentSettings['avatar_admin'];
            }
            
            // Xử lý checkbox values
            $checkboxFields = [
                'auto_approve_comments', 'enable_comments', 'enable_ssl', 
                'maintenance_mode', 'enable_registration', 'enable_newsletter', 
                'allow_file_uploads'
            ];
            
            foreach ($checkboxFields as $field) {
                if ($request->has($field)) {
                    $data[$field] = '1';
                } else {
                    $data[$field] = '0';
                }
            }
            
            // Đảm bảo tất cả fields đều có giá trị, không được null
            foreach ($data as $key => $value) {
                if ($value === null || $value === '') {
                    // Nếu giá trị null hoặc rỗng, giữ lại giá trị cũ
                    if (isset($currentSettings[$key])) {
                        $data[$key] = $currentSettings[$key];
                    } else {
                        // Nếu không có giá trị cũ, đặt giá trị mặc định
                        $data[$key] = '';
                    }
                }
            }

            Setting::setValues($data);

            Log::info('Admin updated settings', [
                'admin_id' => $this->account->id ?? 0,
                'admin_email' => $this->account->email ?? 'unknown',
                'updated_settings' => array_keys($data)
            ]);

            return redirect()->route('admin.settings.index')
                ->with('success', 'Cập nhật cài đặt thành công!');

        } catch (\Exception $e) {
            Log::error('Error updating settings', [
                'error' => $e->getMessage(),
                'admin_id' => $this->account->id ?? 0
            ]);

            return redirect()->route('admin.settings.index')
                ->with('error', 'Có lỗi xảy ra khi cập nhật cài đặt: ' . $e->getMessage());
        }
    }

    public function reset()
    {
        try {
            Setting::truncate();

            $defaultSettings = [
                // General
                'site_name' => 'Review Hải Phòng',
                'site_title' => 'Hải Phòng Life - Trải Nghiệm và Đánh Giá Thực Tế',
                'site_description' => 'Website review ẩm thực và địa điểm tại Hải Phòng - Khám phá những quán ăn ngon, địa điểm đẹp và trải nghiệm thú vị tại thành phố Cảng',
                'site_keywords' => 'review, hải phòng, ẩm thực, địa điểm, quán ăn, du lịch, khám phá',
                'seo_keywords' => 'Hải Phòng Life, cuộc sống Hải Phòng, du lịch Hải Phòng',
                'seo_author' => 'Review Hải Phòng',
                'site_url' => 'https://reviewhaiphong.io.vn',
                'site_slug' => 'hai-phong-life',
                'site_timezone' => 'Asia/Ho_Chi_Minh',
                'site_language' => 'vi',
                'enable_ssl' => 'true',
                'maintenance_mode' => 'false',
                'enable_registration' => 'true',
                'enable_newsletter' => 'true',
                'allow_file_uploads' => 'true',
                
                // Images
                'site_logo' => 'logo.png',
                'site_favicon' => 'favicon.ico',
                'site_image' => 'logo-review-hai-phong.png',
                'site_banner' => 'banner-reviewhaiphong.webp',
                'logo_ads_1' => 'logo-vip.png',
                'avatar_admin' => 'avatar-admin.webp',
                'bo_cong_thuong' => 'bo-cong-thuong.png',
                
                // Contact
                'contact_email' => 'contact@reviewhaiphong.io.vn',
                'contact_phone' => '0123 456 789',
                'contact_address' => 'Hải Phòng, Việt Nam',
                'contact_facebook' => 'https://facebook.com/reviewhaiphong',
                'contact_youtube' => 'https://youtube.com/reviewhaiphong',
                'contact_zalo' => '0398951396',
                'contact_form_recipient' => 'support@haiphonglife.com',
                
                // Social
                'facebook_link' => 'https://www.facebook.com/ducnobi2004',
                'twitter_link' => 'https://twitter.com/',
                'instagram_link' => 'https://www.facebook.com/ducnobi2004',
                'telegram_link' => '1',
                'discord_link' => '1',
                
                // Content
                'posts_per_page' => '10',
                'comments_per_page' => '20',
                'auto_approve_comments' => '0',
                'enable_comments' => '1',
                'url_banner' => 'am-thuc',
                
                // SEO & Analytics
                'google_analytics' => '',
                'google_search_console' => '',
                'google_tag_header' => '',
                'google_tag_body' => '',
                'bing_tag_header' => '',
                'facebook_pixel' => '',
                'site_pinterest' => '',
                
                // Legal
                'copyright' => '<p>Copyright &copy; {{ ((2025 != now()->year ? 2025 . " - " : "") . now()->year) }} <a href="{{ url("/") }}">{{ $settings["site_name"] ?? "Review Hải Phòng" }}</a>. All rights reserved.</p>',
                'dmca' => ''
            ];

            Setting::setValues($defaultSettings);

            Log::info('Admin reset settings to default', [
                'admin_id' => $this->account->id ?? 0,
                'admin_email' => $this->account->email ?? 'unknown'
            ]);

            return redirect()->route('admin.settings.index')
                ->with('success', 'Đã reset cài đặt về mặc định!');

        } catch (\Exception $e) {
            Log::error('Error resetting settings', [
                'error' => $e->getMessage(),
                'admin_id' => $this->account->id ?? 0
            ]);

            return redirect()->route('admin.settings.index')
                ->with('error', 'Có lỗi xảy ra khi reset cài đặt: ' . $e->getMessage());
        }
    }
}
