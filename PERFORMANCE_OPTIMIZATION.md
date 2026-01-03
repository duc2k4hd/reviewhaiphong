# Hướng dẫn Tối ưu Performance cho Hàng Triệu Bài Viết

## 1. Database Indexes

Đã thêm các indexes quan trọng trong migration `add_indexes_for_performance.php`:

### Posts Table:
- `posts_status_published_at_index`: Tối ưu query lấy bài viết mới nhất
- `posts_category_status_index`: Tối ưu query lấy bài viết theo category
- `posts_views_index`: Tối ưu sắp xếp theo lượt xem
- `posts_slug_index`: Tối ưu lookup bài viết theo slug
- `posts_category_published_status_index`: Composite index cho query phức tạp

### Categories Table:
- `categories_slug_index`: Tối ưu lookup category
- `categories_status_index`: Tối ưu filter theo status
- `categories_parent_id_index`: Tối ưu query category con

### Comments Table:
- `comments_post_status_index`: Tối ưu query comments theo post
- `comments_created_at_index`: Tối ưu sắp xếp comments mới nhất

## 2. Caching Strategy

### Homepage (HomeController):
- **Featured Post**: Cache 6 giờ
- **Side Posts**: Cache 6 giờ
- **Main Categories**: Cache 1 ngày
- **Posts by Category**: Cache 6 giờ (riêng từng category)
- **Editor's Picks**: Cache 6 giờ
- **Hot Topics**: Cache 1 giờ (thay đổi nhanh)
- **Top Contributors**: Cache 1 ngày
- **Stories**: Cache 6 giờ
- **Recent Comments**: Cache 30 phút
- **Popular Tags**: Cache 1 ngày

### Category Page (PostsCategory):
- **Category Info**: Cache 1 ngày
- **Posts List**: Cache 6 giờ (theo page)
- **Editor's Picks**: Cache 6 giờ
- **Hot Topics**: Cache 1 giờ
- **Top Contributors**: Cache 1 ngày
- **Posts Count**: Cache 1 ngày
- **Comments Count**: Cache 1 ngày
- **Settings**: Cache 1 ngày

### Single Post (NewsDetailController):
- **Post Detail**: Cache 1 ngày
- **Related Posts**: Cache 6 giờ
- **Popular Posts**: Cache 6 giờ
- **Categories**: Cache 1 ngày
- **Popular Tags**: Cache 1 ngày

## 3. Query Optimization

### Đã áp dụng:
1. **Select specific columns**: Chỉ lấy các cột cần thiết
2. **Eager Loading**: Sử dụng `with()` để tránh N+1 queries
3. **Pagination**: Sử dụng `paginate()` thay vì `get()` cho danh sách lớn
4. **Limit results**: Sử dụng `take()` cho sidebar widgets
5. **Indexed queries**: Tất cả queries đều sử dụng indexed columns

## 4. Cache Configuration

### Sử dụng Laravel Cache:
```php
// Cache driver: Redis hoặc Memcached (khuyến nghị)
// File: config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),

// Hoặc Memcached
'default' => env('CACHE_DRIVER', 'memcached'),
```

### Redis Setup:
```bash
# Install Redis
sudo apt-get install redis-server

# Laravel .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Memcached Setup:
```bash
# Install Memcached
sudo apt-get install memcached

# Laravel .env
CACHE_DRIVER=memcached
```

## 5. Database Optimization

### MySQL/MariaDB:
```sql
-- Tối ưu InnoDB
SET GLOBAL innodb_buffer_pool_size = 2G;
SET GLOBAL innodb_log_file_size = 256M;
SET GLOBAL query_cache_size = 64M;

-- Kiểm tra slow queries
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### PostgreSQL:
```sql
-- Tối ưu PostgreSQL
shared_buffers = 256MB
effective_cache_size = 1GB
maintenance_work_mem = 64MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
```

## 6. Application Level

### Queue Jobs:
- Sử dụng queue cho các tác vụ nặng (generate sitemap, send email)
- Sử dụng Redis Queue hoặc Database Queue

### CDN:
- Sử dụng CDN cho static assets (images, CSS, JS)
- CloudFlare, AWS CloudFront, hoặc VN CDN

### Image Optimization:
- Sử dụng WebP format
- Lazy loading images
- Responsive images với srcset

## 7. Server Configuration

### PHP-FPM:
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

### Nginx:
```nginx
# Enable gzip
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;

# Cache static files
location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## 8. Monitoring

### Laravel Telescope (Development):
```bash
composer require laravel/telescope
php artisan telescope:install
```

### Laravel Debugbar (Development):
```bash
composer require barryvdh/laravel-debugbar --dev
```

### Production Monitoring:
- New Relic
- Datadog
- Sentry (error tracking)

## 9. Cache Clearing

### Clear cache khi cần:
```bash
# Clear all cache
php artisan cache:clear

# Clear specific cache key
Cache::forget('homepage_data');

# Clear cache tags (nếu dùng Redis)
Cache::tags(['posts', 'category_' . $categoryId])->flush();
```

### Auto-clear cache khi có thay đổi:
- Sử dụng Model Events (PostObserver)
- Clear cache khi post được update/publish

## 10. Scaling Strategy

### Horizontal Scaling:
1. **Load Balancer**: Nginx hoặc HAProxy
2. **Multiple App Servers**: Chạy nhiều instance Laravel
3. **Database Replication**: Master-Slave setup
4. **Redis Cluster**: Cho cache và session

### Vertical Scaling:
1. **More RAM**: Cho database và cache
2. **SSD Storage**: Cho database
3. **Better CPU**: Cho application server

## 11. Best Practices

1. **Always use indexes** cho các columns trong WHERE, ORDER BY, JOIN
2. **Use pagination** cho danh sách lớn
3. **Cache expensive queries** (aggregations, counts, joins)
4. **Use eager loading** để tránh N+1 queries
5. **Monitor slow queries** và optimize
6. **Use queue** cho background jobs
7. **Compress responses** (gzip)
8. **Use CDN** cho static assets
9. **Optimize images** (WebP, lazy loading)
10. **Database connection pooling**

## 12. Testing Performance

### Load Testing:
```bash
# Sử dụng Apache Bench
ab -n 1000 -c 10 http://your-site.com/

# Hoặc wrk
wrk -t12 -c400 -d30s http://your-site.com/
```

### Database Query Analysis:
```php
// Enable query log
DB::enableQueryLog();
// ... your code ...
dd(DB::getQueryLog());
```

## 13. Migration Commands

```bash
# Chạy migration để thêm indexes
php artisan migrate

# Rollback nếu cần
php artisan migrate:rollback
```

## 14. Estimated Performance

Với các tối ưu trên:
- **Homepage**: < 100ms (với cache)
- **Category Page**: < 150ms (với cache)
- **Single Post**: < 50ms (với cache)
- **Database Queries**: < 10 queries per page
- **Cache Hit Rate**: > 95%

## 15. Maintenance

### Daily:
- Monitor cache hit rate
- Check slow queries
- Monitor server resources

### Weekly:
- Analyze access patterns
- Optimize cache TTL
- Review indexes usage

### Monthly:
- Database optimization (ANALYZE, OPTIMIZE)
- Review and update indexes
- Performance testing

