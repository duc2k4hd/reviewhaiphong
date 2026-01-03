<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'id', 'category_id', 'account_id', 'name', 'content', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'tags', 'published_at', 'views', 'slug', 'status', 'last_updated_by', 'type'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function lastUpdatedBy() {
        return $this->belongsTo(Account::class, 'last_updated_by');
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function comments() {
        return $this->hasMany(Comment::class);
    }

    public function getViewsAttribute(): int
    {
        return (int) ($this->attributes['views'] ?? 0);
    }

    // Đảm bảo content luôn lưu ở dạng HTML thật (không entity)
    public function setContentAttribute($value): void
    {
        if (is_string($value)) {
            // Decode entity nếu bị encode (&lt;h1&gt;...)
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Loại bỏ sự kiện nguy hiểm và javascript: khỏi href/src
            $decoded = preg_replace('/\son[a-z]+\s*=\s*"[^"]*"/i', '', $decoded);
            $decoded = preg_replace("/\son[a-z]+\s*=\s*'[^']*'/i", '', $decoded);
            $decoded = preg_replace('/\s(href|src)\s*=\s*"\s*javascript:[^"]*"/i', ' $1="#"', $decoded);
            $decoded = preg_replace("/\s(href|src)\s*=\s*'\s*javascript:[^']*'/i", ' $1="#"', $decoded);
            $this->attributes['content'] = trim($decoded);
            return;
        }
        $this->attributes['content'] = $value;
    }
}
