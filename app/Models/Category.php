<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'status' => 'string',
        'sort_order' => 'integer',
        'parent_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mutator để đảm bảo status luôn là string
     */
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = (string) $value;
    }

    protected $attributes = [
        'status' => 'active',
        'sort_order' => 0,
        'description' => '',
        'meta_title' => '',
        'meta_description' => '',
        'meta_keywords' => '',
    ];

    public function posts() {
        return $this->hasMany(Post::class);
    }

    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Scope để lấy categories active
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope để lấy categories parent (không có parent_id)
     */
    public function scopeParent($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope để lấy categories con
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Kiểm tra xem category có phải là parent không
     */
    public function isParent()
    {
        return is_null($this->parent_id);
    }

    /**
     * Kiểm tra xem category có phải là con không
     */
    public function isChild()
    {
        return !is_null($this->parent_id);
    }

    /**
     * Lấy tên parent category
     */
    public function getParentNameAttribute()
    {
        return $this->parent ? $this->parent->name : 'Danh mục gốc';
    }

    /**
     * Lấy số lượng bài viết
     */
    public function getPostsCountAttribute()
    {
        return $this->posts()->count();
    }

    /**
     * Lấy số lượng categories con
     */
    public function getChildrenCountAttribute()
    {
        return $this->children()->count();
    }
}
