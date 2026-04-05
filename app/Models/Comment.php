<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['user_id', 'commentable_type', 'commentable_id', 'body'];

    public function user() { return $this->belongsTo(User::class); }
    public function commentable() { return $this->morphTo(); }

    public function getRenderedBodyAttribute()
    {
        $body = e($this->body);
        // Replace @[user:ID]Name with styled mention tag
        $body = preg_replace_callback('/@\[user:(\d+)\]([^@\s][^\s]*)/', function ($matches) {
            $name = $matches[2];
            return '<span class="mention-tag">@' . $name . '</span>';
        }, $body);
        return $body;
    }
}
