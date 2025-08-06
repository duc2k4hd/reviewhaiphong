<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'category_id', 'account_id', 'name', 'slug', 'content', 
        'status', 'views', 'seo_title', 'seo_desc', 'seo_image', 
        'seo_keywords', 'tags', 'published_at', 'last_updated_by'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views' => 'integer',
        'tags' => 'array'
    ];

    // Query Scopes for better performance
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeByCategory(Builder $query, $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('views', 'desc')->limit($limit);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    // Optimized relationships
    public function account() {
        return $this->belongsTo(Account::class)->select(['id', 'username', 'email']);
    }

    public function lastUpdatedBy() {
        return $this->belongsTo(Account::class, 'last_updated_by')->select(['id', 'username']);
    }

    public function category() {
        return $this->belongsTo(Category::class)->select(['id', 'name', 'slug']);
    }

    public function comments() {
        return $this->hasMany(Comment::class)->where('status', 'approved');
    }

    // Helper methods
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
