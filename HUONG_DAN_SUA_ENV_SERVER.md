# Hướng dẫn sửa lỗi Database Connection trên Server (Shared Hosting)

## Lỗi hiện tại:
```
Access denied for user 'root'@'localhost' (using password: NO)
```

## Nguyên nhân:
File `.env` trên server thiếu hoặc sai cấu hình `DB_PASSWORD`.

## Các bước sửa:

### Bước 1: Đăng nhập vào cPanel
1. Đăng nhập vào cPanel của hosting
2. Tìm phần **"File Manager"** hoặc **"Quản lý File"**

### Bước 2: Tìm và mở file `.env`
1. Trong File Manager, điều hướng đến thư mục `public_html` (hoặc thư mục gốc của dự án)
2. Tìm file `.env` (có thể bị ẩn, cần bật "Show Hidden Files")
3. Click chuột phải → **Edit** hoặc **Chỉnh sửa**

### Bước 3: Lấy thông tin Database từ cPanel
1. Trong cPanel, tìm phần **"MySQL Databases"** hoặc **"Quản lý MySQL"**
2. Ghi lại các thông tin sau:
   - **Database Name**: Tên database (thường có prefix, ví dụ: `cqyfjgru_review`)
   - **Database User**: Tên user (thường có prefix, ví dụ: `cqyfjgru_user`)
   - **Database Password**: Mật khẩu (nếu quên, có thể reset trong cPanel)
   - **Host**: Thường là `localhost` hoặc `127.0.0.1`

### Bước 4: Sửa file `.env`
Mở file `.env` và sửa các dòng sau (điền đúng thông tin từ Bước 3):

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cqyfjgru_review
DB_USERNAME=cqyfjgru_user
DB_PASSWORD=mat_khau_database_cua_ban
```

**QUAN TRỌNG:**
- `DB_PASSWORD` KHÔNG được để trống
- Nếu password có ký tự đặc biệt, không cần escape
- Sau khi sửa, nhớ **Save** (Lưu)

### Bước 5: Clear cache (nếu có SSH access)
Nếu có quyền SSH, chạy:
```bash
cd /home/cqyfjgru/public_html
php artisan config:clear
php artisan cache:clear
```

Nếu không có SSH, có thể:
- Xóa thủ công thư mục `bootstrap/cache/config.php` (nếu có)
- Hoặc đợi vài phút để cache tự động refresh

### Bước 6: Kiểm tra lại
Thử truy cập lại website hoặc chạy migration:
```bash
php artisan migrate:status
```

## Nếu vẫn lỗi:

### Kiểm tra 1: File `.env` có tồn tại không?
- Trong File Manager, đảm bảo file `.env` có trong thư mục gốc
- Nếu không có, tạo file mới từ `.env.example`:
  1. Copy file `.env.example` → đổi tên thành `.env`
  2. Sửa các thông tin database như Bước 4

### Kiểm tra 2: Quyền truy cập file
- File `.env` nên có quyền `644` hoặc `600`
- Trong File Manager, click chuột phải → **Change Permissions** → đặt `644`

### Kiểm tra 3: User database có quyền không?
- Trong cPanel → MySQL Databases
- Đảm bảo user đã được gán quyền **ALL PRIVILEGES** cho database

### Kiểm tra 4: Thông tin database có đúng không?
- Thử kết nối bằng phpMyAdmin trong cPanel
- Nếu kết nối được trong phpMyAdmin nhưng không được trong Laravel → kiểm tra lại `.env`

## Lưu ý đặc biệt cho Shared Hosting:

1. **Database name và username thường có prefix**: 
   - Ví dụ: `cqyfjgru_review` thay vì chỉ `review`
   - Lấy chính xác từ cPanel → MySQL Databases

2. **Host thường là `localhost`**:
   - Không dùng `127.0.0.1` trên một số hosting
   - Thử cả hai nếu `localhost` không hoạt động

3. **Port thường là `3306`**:
   - Một số hosting dùng port khác, kiểm tra trong cPanel

4. **File `.env` có thể bị ẩn**:
   - Trong File Manager, bật "Show Hidden Files" (Ctrl+H hoặc Settings → Show Hidden Files)

## Template file `.env` đầy đủ:

```env
APP_NAME="Review Hải Phòng"
APP_ENV=production
APP_KEY=base64:... (key từ local)
APP_DEBUG=false
APP_URL=https://reviewhaiphong.io.vn

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cqyfjgru_review
DB_USERNAME=cqyfjgru_user
DB_PASSWORD=mat_khau_cua_ban

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

# Gemini AI (nếu có)
GEMINI_API_KEY=your_key_here
GEMINI_MODEL=gemini-pro
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models
```

