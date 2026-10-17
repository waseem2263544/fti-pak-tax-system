<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A version of a post as it was published.
 *
 * The website is served the latest of these, not the post row, so a draft
 * can be worked on without the public page changing underneath it.
 */
class PostRevision extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'featured'     => 'boolean',
        'noindex'      => 'boolean',
        'published_on' => 'date',
        'reviewed_on'  => 'date',
    ];

    /** The fields a published version carries. */
    public const FIELDS = [
        'slug', 'title', 'excerpt', 'content', 'category', 'read_time', 'hero_image', 'hero_image_alt',
        'focus_keyphrase', 'meta_title', 'meta_description', 'canonical_url', 'noindex',
        'featured', 'published_on', 'reviewed_on', 'seo_score', 'readability_score',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
