<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    public function posts() {
        return $this->hasMany(Post::class);
    }

    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
