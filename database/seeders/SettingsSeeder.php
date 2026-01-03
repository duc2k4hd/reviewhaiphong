<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

        foreach ($defaultSettings as $key => $value) {
            Setting::updateOrCreate(
                ['name' => $key],
                ['value' => $value]
            );
        }

        $this->command->info('Settings seeded successfully!');
    }
}
