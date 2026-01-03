# Sitemap Động - Hướng Dẫn Sử Dụng

## Tổng Quan
Hệ thống sitemap động tự động chia nhỏ sitemap khi có nhiều hơn 200 bài viết để tối ưu hiệu suất và tuân thủ chuẩn Google.

## Cách Hoạt Động

### 1. Khi có ít hơn 200 bài viết:
- Tạo 1 file: `sitemap.xml`
- Chứa tất cả: trang chủ, danh mục, bài viết

### 2. Khi có nhiều hơn 200 bài viết:
- Tạo sitemap index: `sitemap.xml`
- Tạo sitemap chính: `sitemap-main.xml` (trang chủ, danh mục)
- Tạo sitemap bài viết: `sitemap-posts-1.xml`, `sitemap-posts-2.xml`, ...

## Cách Sử Dụng

### 1. Tạo sitemap thủ công:
```bash
php artisan sitemap:generate
```

### 2. Truy cập sitemap:
- **Sitemap index**: `http://yourdomain.com/sitemap.xml`
- **Sitemap chính**: `http://yourdomain.com/sitemap-main.xml`
- **Sitemap bài viết**: `http://yourdomain.com/sitemap-posts-1.xml`

### 3. Tự động tạo sitemap:
Sitemap sẽ được tạo tự động hàng ngày lúc 2:00 sáng.

## Cấu Trúc File

### Sitemap Index (khi có >200 bài viết):
```xml
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>http://yourdomain.com/sitemap-main.xml</loc>
        <lastmod>2025-08-13T07:21:13+00:00</lastmod>
    </sitemap>
    <sitemap>
        <loc>http://yourdomain.com/sitemap-posts-1.xml</loc>
        <lastmod>2025-08-13T07:21:13+00:00</lastmod>
    </sitemap>
    <!-- Thêm sitemap-posts-2.xml, sitemap-posts-3.xml, ... -->
</sitemapindex>
```

### Sitemap Chính (trang chủ, danh mục):
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>http://yourdomain.com/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>http://yourdomain.com/review-hai-phong</loc>
        <lastmod>2025-08-13T07:21:13+00:00</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <!-- Các danh mục khác -->
</urlset>
```

### Sitemap Bài Viết:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>http://yourdomain.com/bai-viet/ten-bai-viet</loc>
        <lastmod>2025-08-13T07:21:13+00:00</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <!-- Tối đa 200 bài viết mỗi file -->
</urlset>
```

## Cấu Hình

### Giới hạn bài viết mỗi sitemap:
- Mặc định: 200 bài viết
- Có thể thay đổi trong code: `$postsPerSitemap = 200;`

### Danh mục được thêm vào sitemap:
- `review-tong-hop`
- `du-lich`
- `am-thuc`
- `check-in`
- `dich-vu`
- `tin-tuc`
- `gioi-thieu`

### Priority và Change Frequency:
- **Trang chủ**: Priority 1.0, Change Frequency daily
- **Review Hải Phòng**: Priority 0.9, Change Frequency weekly
- **Giới thiệu**: Priority 0.7, Change Frequency monthly
- **Danh mục**: Priority 0.6, Change Frequency monthly
- **Bài viết**: Priority 0.6, Change Frequency monthly

## Lưu Ý

1. **Chỉ bài viết đã xuất bản** (`status = 'published'`) được thêm vào sitemap
2. **Bài viết được sắp xếp** theo `published_at` giảm dần (mới nhất trước)
3. **Sitemap tự động cập nhật** khi có bài viết mới
4. **XSL stylesheet** được thêm vào để hiển thị đẹp khi truy cập trực tiếp

## Troubleshooting

### Lỗi thường gặp:
1. **File không được tạo**: Kiểm tra quyền ghi trong thư mục `public/`
2. **Route không hoạt động**: Chạy `php artisan route:clear`
3. **Sitemap trống**: Kiểm tra có bài viết nào có `status = 'published'` không

### Debug:
```bash
# Xem log sitemap
tail -f storage/logs/laravel.log | grep sitemap

# Test command
php artisan sitemap:generate --verbose

# Kiểm tra file đã tạo
ls -la public/sitemap*.xml
```
