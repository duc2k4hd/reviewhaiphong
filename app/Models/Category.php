<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name', 'slug', 'description', 'parent_id', 'sort_order', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Query Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    // Optimized relationships
    public function posts() {
        return $this->hasMany(Post::class)->published()->recent();
    }

    public function allPosts() {
        return $this->hasMany(Post::class);
    }

    public function publishedPosts() {
        return $this->hasMany(Post::class)->published();
    }

    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id')->select(['id', 'name', 'slug']);
    }

    public function children() {
        return $this->hasMany(Category::class, 'parent_id')->active();
    }

    // Helper methods
    public function getPostsCount(): int
    {
        return $this->posts()->count();
    }
}
