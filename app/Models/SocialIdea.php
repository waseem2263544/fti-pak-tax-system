<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A topic waiting to become a TikTok post.
 *
 * Added by hand or suggested by Claude. The daily run takes the oldest "new"
 * ideas first, so the bank doubles as a content calendar.
 */
class SocialIdea extends Model
{
    protected $fillable = ['topic', 'angle', 'audience', 'format', 'status', 'source', 'post_id', 'used_at'];

    protected $casts = ['used_at' => 'datetime'];

    public function post()
    {
        return $this->belongsTo(SocialPost::class, 'post_id');
    }
}
