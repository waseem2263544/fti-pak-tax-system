<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A blog post, as the website renders it.
 *
 * The field names match the BlogPost interface in the Next.js site, so the
 * public feed can be handed over without the site's templates changing.
 */
class Post extends Model
{
    protected $fillable = [
        'region', 'slug', 'title', 'excerpt', 'content', 'category', 'read_time',
        'hero_image', 'hero_image_alt', 'featured', 'status',
        'published_on', 'reviewed_on', 'sort_order', 'author_id',
        'focus_keyphrase', 'related_keyphrases', 'meta_title', 'meta_description',
        'canonical_url', 'noindex', 'seo_score', 'readability_score',
    ];

    protected $casts = [
        'featured'     => 'boolean',
        'noindex'      => 'boolean',
        'published_on' => 'date',
        'reviewed_on'  => 'date',
    ];

    public const REGIONS = ['pk' => 'Pakistan', 'ae' => 'UAE'];
    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'published');
    }

    /** A slug the site can use: lowercase, hyphenated, unique within its region. */
    public static function uniqueSlug(string $title, string $region, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $n = 2;

        while (static::where('region', $region)->where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    /**
     * Reading time from the body.
     *
     * Only filled in when the writer has not set one; a figure they chose
     * deliberately should not be overwritten by an estimate.
     */
    public function estimateReadTime(): string
    {
        $words = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) round($words / 220)) . ' min read';
    }

    /**
     * The title and description a search result shows.
     *
     * Yoast's habit, and a good one: the SEO title is written for the search
     * page and the headline for the reader, so they are allowed to differ.
     * Left blank, the page's own title and excerpt stand in.
     */
    public function metaTitle(): string
    {
        return $this->meta_title ?: (string) $this->title;
    }

    public function metaDescription(): string
    {
        return $this->meta_description ?: (string) $this->excerpt;
    }

    /** The shape the website expects. */
    public function toFeed(): array
    {
        return array_filter([
            'slug'         => $this->slug,
            'title'        => $this->title,
            'excerpt'      => $this->excerpt,
            'date'         => $this->published_on?->format('Y-m-d'),
            'updated'      => $this->reviewed_on?->format('Y-m-d'),
            'readTime'     => $this->read_time,
            'category'     => $this->category,
            'featured'     => $this->featured ?: null,
            'content'      => $this->content,
            'heroImage'    => $this->hero_image,
            'heroImageAlt' => $this->hero_image_alt,
            'metaTitle'    => $this->metaTitle(),
            'metaDescription' => $this->metaDescription(),
            'focusKeyphrase'  => $this->focus_keyphrase,
            'canonical'    => $this->canonical_url,
            'noindex'      => $this->noindex ?: null,
        ], fn($v) => $v !== null);
    }
}
