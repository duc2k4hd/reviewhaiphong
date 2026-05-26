<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'id', 'category_id', 'account_id', 'name', 'content', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'tags', 'published_at', 'views', 'slug', 'status', 'last_updated_by', 'type'
    ];

    protected $appends = ['word_count', 'reading_time'];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Post $post) {
            $postsDir = public_path('client/assets/images/posts');

            if (!empty($post->seo_image)) {
                $imagePath = $postsDir . DIRECTORY_SEPARATOR . basename($post->seo_image);
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                }
            }

            // Also delete images embedded in content HTML. Scraper stores them as
            // /client/assets/images/posts/<filename>, so pull every matching src
            // and unlink the physical file.
            if (!empty($post->content)) {
                $rawContent = $post->getAttributes()['content'] ?? $post->content;
                if (preg_match_all('~<img\b[^>]*\bsrc=["\']([^"\']+)["\']~i', $rawContent, $matches)) {
                    foreach (array_unique($matches[1]) as $src) {
                        if (!preg_match('~/client/assets/images/posts/([^/?#"\'\s]+)~i', $src, $m)) {
                            continue;
                        }
                        $filename = urldecode($m[1]);
                        if ($filename === '' || $filename === basename($post->seo_image ?? '')) {
                            continue;
                        }
                        $path = $postsDir . DIRECTORY_SEPARATOR . $filename;
                        if (File::exists($path)) {
                            File::delete($path);
                        }
                    }
                }
            }
        });
    }

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

    public function getWordCountAttribute()
    {
        if (empty($this->content)) {
            return 0;
        }
        $text = strip_tags($this->content);
        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
    }

    public function getReadingTimeAttribute()
    {
        $minutes = ceil($this->word_count / 200);
        return $minutes > 0 ? $minutes : 1;
    }
}
