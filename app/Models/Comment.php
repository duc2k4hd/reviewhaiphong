<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'comments';

    public function post() {
        return $this->belongsTo(Post::class);
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }
}
