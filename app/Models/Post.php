<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts';

    protected $fillable = [
        'id', 'category_id', 'account_id', 'name', 'views', 'content', 'seo_title', 'seo_desc', 'seo_image', 'seo_keywords', 'tags', 'published_at'
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
}
