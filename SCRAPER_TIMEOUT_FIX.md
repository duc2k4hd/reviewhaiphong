# Hướng dẫn xử lý timeout khi cào nhiều bài viết

## Vấn đề
Khi cào hơn 100 bài viết, có thể gặp lỗi 500 do timeout của web server (Apache/Nginx), không phải PHP timeout.

## Giải pháp đã áp dụng

### 1. Tăng PHP limits trong code
- `set_time_limit(0)` - Không giới hạn thời gian thực thi
- `ini_set('memory_limit', '1024M')` - Tăng memory limit lên 1GB

### 2. Thêm delay giữa các request
- Delay 0.5 giây giữa các URL
- Delay 1 giây giữa các Gemini API calls
- Tránh rate limit và quá tải server

### 3. Retry logic
- Retry 3 lần cho HTTP requests
- Retry 2 lần cho Gemini API calls
- Tự động retry khi gặp lỗi network

### 4. Tăng timeout
- HTTP requests: 60 giây (từ 30 giây)
- Gemini API: 60 giây (từ 30 giây)
- Retry với delay

### 5. Flush output
- Flush output buffer để tránh timeout của web server
- Log tiến trình để theo dõi

## Cấu hình cPanel (đã set)
- max_execution_time: 3000
- max_input_time: 6000
- memory_limit: 512M
- post_max_size: 128M
- upload_max_filesize: 1G

## Nếu vẫn bị timeout

### Giải pháp 1: Chia nhỏ batch
Thay vì cào 100 bài một lúc, chia thành các batch nhỏ hơn (20-30 bài/batch).

### Giải pháp 2: Tạo command để chạy background
```bash
php artisan scraper:run {urls_file}
```

### Giải pháp 3: Tăng timeout của web server
Nếu có quyền truy cập server:
- Apache: Thêm `Timeout 600` vào httpd.conf
- Nginx: Thêm `proxy_read_timeout 600;` vào config

### Giải pháp 4: Sử dụng queue jobs
Chuyển scraping sang queue để xử lý background, tránh timeout của web request.

## Kiểm tra logs
Xem logs tại: `storage/logs/laravel.log` để biết lỗi cụ thể.

