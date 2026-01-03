# Hướng dẫn sửa lỗi Redirect Loop giữa /admin/login và /admin/dashboard

## Vấn đề:
Redirect loop giữa `/admin/login` và `/admin/dashboard` trên server.

## Nguyên nhân:
1. Session không lưu được trên server (permission issue)
2. `Auth::check()` không nhất quán giữa các request
3. Cookie/Session domain không đúng với HTTPS

## Các bước sửa:

### Bước 1: Kiểm tra quyền thư mục session
SSH vào server và chạy:
```bash
cd /home/cqyfjgru/public_html
chmod -R 775 storage/framework/sessions
chown -R www-data:www-data storage/framework/sessions
```

Hoặc trong cPanel File Manager:
- Thư mục `storage/framework/sessions` → Change Permissions → `775`

### Bước 2: Kiểm tra file `.env` trên server
Đảm bảo có các cấu hình sau:

```env
APP_URL=https://reviewhaiphong.io.vn
SESSION_DRIVER=file
SESSION_DOMAIN=.reviewhaiphong.io.vn
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

**Lưu ý:**
- `APP_URL` phải đúng với domain (có `https://`)
- `SESSION_DOMAIN` nên để `.reviewhaiphong.io.vn` (có dấu chấm đầu) hoặc để trống
- `SESSION_SECURE_COOKIE=true` nếu dùng HTTPS
- `SESSION_SAME_SITE=lax` hoặc `none` (nếu `none` thì phải có `SESSION_SECURE_COOKIE=true`)

### Bước 3: Clear cache và config
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
```

### Bước 4: Kiểm tra lại
1. Xóa cookies của domain `reviewhaiphong.io.vn` trong browser
2. Truy cập lại `/admin/login`
3. Đăng nhập lại

## Nếu vẫn lỗi:

### Giải pháp 1: Đổi session driver sang database
1. Tạo migration cho sessions table:
```bash
php artisan session:table
php artisan migrate
```

2. Sửa `.env`:
```env
SESSION_DRIVER=database
```

3. Clear cache:
```bash
php artisan config:clear
php artisan config:cache
```

### Giải pháp 2: Kiểm tra PHP session settings
Trong cPanel → Select PHP Version → Options → tìm:
- `session.save_path` - phải có quyền ghi
- `session.cookie_secure` - nên là `1` nếu dùng HTTPS
- `session.cookie_httponly` - nên là `1`

### Giải pháp 3: Kiểm tra .htaccess
Đảm bảo không có rule nào chặn session hoặc cookie.

## Code đã được sửa:
1. `LoginController::index()` - Kiểm tra kỹ hơn trước khi redirect
2. `CheckLogin` middleware - Xóa session cũ nếu không hợp lệ

## Test:
1. Xóa tất cả cookies của domain
2. Truy cập `/admin/login`
3. Đăng nhập
4. Kiểm tra xem có redirect loop không

