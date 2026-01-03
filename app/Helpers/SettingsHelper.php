<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    /**
     * Lấy setting value
     */
    public static function get($key, $default = null)
    {
        return Setting::getValue($key, $default);
    }

    /**
     * Lấy tất cả settings
     */
    public static function all()
    {
        return Setting::getSettings();
    }

    /**
     * Set setting value
     */
    public static function set($key, $value)
    {
        return Setting::setValue($key, $value);
    }

    /**
     * Lấy site name
     */
    public static function siteName()
    {
        return self::get('site_name', 'Review Hải Phòng');
    }

    /**
     * Lấy site description
     */
    public static function siteDescription()
    {
        return self::get('site_description', 'Website review ẩm thực và địa điểm tại Hải Phòng');
    }

    /**
     * Lấy site keywords
     */
    public static function siteKeywords()
    {
        return self::get('site_keywords', 'review, hải phòng, ẩm thực');
    }

    /**
     * Lấy contact email
     */
    public static function contactEmail()
    {
        return self::get('contact_email', 'contact@reviewhaiphong.io.vn');
    }

    /**
     * Lấy contact phone
     */
    public static function contactPhone()
    {
        return self::get('contact_phone', '');
    }

    /**
     * Lấy contact address
     */
    public static function contactAddress()
    {
        return self::get('contact_address', '');
    }

    /**
     * Lấy Facebook URL
     */
    public static function facebookUrl()
    {
        return self::get('contact_facebook', '');
    }

    /**
     * Lấy YouTube URL
     */
    public static function youtubeUrl()
    {
        return self::get('contact_youtube', '');
    }

    /**
     * Lấy số bài viết mỗi trang
     */
    public static function postsPerPage()
    {
        return (int) self::get('posts_per_page', 10);
    }

    /**
     * Lấy số bình luận mỗi trang
     */
    public static function commentsPerPage()
    {
        return (int) self::get('comments_per_page', 20);
    }

    /**
     * Kiểm tra có tự động duyệt bình luận không
     */
    public static function autoApproveComments()
    {
        return self::get('auto_approve_comments', '0') === '1';
    }

    /**
     * Kiểm tra có bật bình luận không
     */
    public static function enableComments()
    {
        return self::get('enable_comments', '1') === '1';
    }

    /**
     * Lấy Google Analytics code
     */
    public static function googleAnalytics()
    {
        return self::get('google_analytics', '');
    }

    /**
     * Lấy Google Search Console code
     */
    public static function googleSearchConsole()
    {
        return self::get('google_search_console', '');
    }

    /**
     * Lấy Facebook Pixel code
     */
    public static function facebookPixel()
    {
        return self::get('facebook_pixel', '');
    }

    /**
     * Lấy site logo
     */
    public static function siteLogo()
    {
        $logo = self::get('site_logo', '');
        return $logo ? asset('/client/assets/images/logo/' . $logo) : '';
    }

    /**
     * Lấy site favicon
     */
    public static function siteFavicon()
    {
        $favicon = self::get('site_favicon', '');
        return $favicon ? asset('/client/assets/images/logo/' . $favicon) : '';
    }

    /**
     * Lấy site title
     */
    public static function siteTitle()
    {
        return self::get('site_title', 'Review Hải Phòng');
    }

    /**
     * Lấy site URL
     */
    public static function siteUrl()
    {
        return self::get('site_url', 'https://reviewhaiphong.io.vn');
    }

    /**
     * Lấy site slug
     */
    public static function siteSlug()
    {
        return self::get('site_slug', 'hai-phong-life');
    }

    /**
     * Lấy site timezone
     */
    public static function siteTimezone()
    {
        return self::get('site_timezone', 'Asia/Ho_Chi_Minh');
    }

    /**
     * Lấy site language
     */
    public static function siteLanguage()
    {
        return self::get('site_language', 'vi');
    }

    /**
     * Kiểm tra SSL
     */
    public static function enableSsl()
    {
        return self::get('enable_ssl', 'true') === 'true';
    }

    /**
     * Kiểm tra maintenance mode
     */
    public static function maintenanceMode()
    {
        return self::get('maintenance_mode', 'false') === 'true';
    }

    /**
     * Kiểm tra cho phép đăng ký
     */
    public static function enableRegistration()
    {
        return self::get('enable_registration', 'true') === 'true';
    }

    /**
     * Kiểm tra newsletter
     */
    public static function enableNewsletter()
    {
        return self::get('enable_newsletter', 'true') === 'true';
    }

    /**
     * Kiểm tra cho phép upload file
     */
    public static function allowFileUploads()
    {
        return self::get('allow_file_uploads', 'true') === 'true';
    }

    /**
     * Lấy contact Zalo
     */
    public static function contactZalo()
    {
        return self::get('contact_zalo', '');
    }

    /**
     * Lấy contact form recipient
     */
    public static function contactFormRecipient()
    {
        return self::get('contact_form_recipient', 'support@haiphonglife.com');
    }

    /**
     * Lấy Facebook link
     */
    public static function facebookLink()
    {
        return self::get('facebook_link', '');
    }

    /**
     * Lấy Twitter link
     */
    public static function twitterLink()
    {
        return self::get('twitter_link', '');
    }

    /**
     * Lấy Instagram link
     */
    public static function instagramLink()
    {
        return self::get('instagram_link', '');
    }

    /**
     * Lấy Telegram link
     */
    public static function telegramLink()
    {
        return self::get('telegram_link', '');
    }

    /**
     * Lấy Discord link
     */
    public static function discordLink()
    {
        return self::get('discord_link', '');
    }

    /**
     * Lấy URL banner
     */
    public static function urlBanner()
    {
        return self::get('url_banner', 'am-thuc');
    }

    /**
     * Lấy Google Tag Manager Header
     */
    public static function googleTagHeader()
    {
        return self::get('google_tag_header', '');
    }

    /**
     * Lấy Google Tag Manager Body
     */
    public static function googleTagBody()
    {
        return self::get('google_tag_body', '');
    }

    /**
     * Lấy Bing Tag Header
     */
    public static function bingTagHeader()
    {
        return self::get('bing_tag_header', '');
    }

    /**
     * Lấy Pinterest verification
     */
    public static function sitePinterest()
    {
        return self::get('site_pinterest', '');
    }

    /**
     * Lấy copyright
     */
    public static function copyright()
    {
        return self::get('copyright', '');
    }

    /**
     * Lấy DMCA
     */
    public static function dmca()
    {
        return self::get('dmca', '');
    }

    /**
     * Lấy site image
     */
    public static function siteImage()
    {
        $image = self::get('site_image', '');
        return $image ? asset('/client/assets/images/logo/' . $image) : '';
    }

    /**
     * Lấy site banner
     */
    public static function siteBanner()
    {
        $banner = self::get('site_banner', '');
        return $banner ? asset('/client/assets/images/logo/' . $banner) : '';
    }

    /**
     * Lấy logo ads 1
     */
    public static function logoAds1()
    {
        $logo = self::get('logo_ads_1', '');
        return $logo ? asset('/client/assets/images/logo/' . $logo) : '';
    }

    /**
     * Lấy avatar admin
     */
    public static function avatarAdmin()
    {
        $avatar = self::get('avatar_admin', '');
        return $avatar ? asset('/client/assets/images/avatar/' . $avatar) : '';
    }

    /**
     * Lấy bo cong thuong
     */
    public static function boCongThuong()
    {
        $logo = self::get('bo_cong_thuong', '');
        return $logo ? asset('/client/assets/images/logo/' . $logo) : '';
    }

    /**
     * Lấy DMCA logo
     */
    public static function dmcaLogo()
    {
        return self::get('dmca_logo', '');
    }
}
