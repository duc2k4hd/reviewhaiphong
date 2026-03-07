<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapedUrl extends Model
{
    protected $fillable = [
        'url',
        'source',
        'post_id',
        'scraped_at'
    ];

    protected $casts = [
        'scraped_at' => 'datetime',
    ];

    /**
     * Kiểm tra xem URL đã được cào chưa
     */
    public static function isUrlScraped(string $url, string $source = 'vinpearl'): bool
    {
        $normalizedUrl = self::normalizeUrl($url);
        return self::where('url', $normalizedUrl)
            ->where('source', $source)
            ->exists();
    }

    /**
     * Lưu URL đã cào vào database
     */
    public static function markAsScraped(string $url, string $source = 'vinpearl', ?int $postId = null): self
    {
        $normalizedUrl = self::normalizeUrl($url);
        
        return self::updateOrCreate(
            [
                'url' => $normalizedUrl,
                'source' => $source
            ],
            [
                'post_id' => $postId,
                'scraped_at' => now()
            ]
        );
    }

    /**
     * Normalize URL để so sánh chính xác
     */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        // Loại bỏ trailing slash
        $url = rtrim($url, '/');
        // Chuyển về lowercase để so sánh
        $url = strtolower($url);
        return $url;
    }

    /**
     * Xóa tất cả URL đã cào của một source
     */
    public static function clearBySource(string $source): int
    {
        return self::where('source', $source)->delete();
    }

    /**
     * Đếm số lượng URL đã cào của một source
     */
    public static function countBySource(string $source): int
    {
        return self::where('source', $source)->count();
    }

    /**
     * Relationship với Post
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
