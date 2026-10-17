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

    public function revisions()
    {
        return $this->hasMany(PostRevision::class)->orderByDesc('id');
    }

    /** The version the website is serving. */
    public function liveRevision()
    {
        return $this->hasOne(PostRevision::class)->latestOfMany();
    }

    /** Record what has just been published, so there is a way back to it. */
    public function snapshot(): PostRevision
    {
        return $this->revisions()->create(
            collect(PostRevision::FIELDS)->mapWithKeys(fn($f) => [$f => $this->{$f}])->all()
            + ['published_by' => auth()->id()]
        );
    }

    /**
     * Whether the working copy has moved away from what is live.
     *
     * Only the fields a reader sees are compared: re-saving without changing
     * anything should not announce unpublished changes.
     */
    public function differsFromLive(): bool
    {
        $live = $this->liveRevision;

        if (!$live) {
            return false;
        }

        foreach (PostRevision::FIELDS as $field) {
            $mine = $this->{$field};
            $theirs = $live->{$field};

            if ($mine instanceof \DateTimeInterface) { $mine = $mine->format('Y-m-d'); }
            if ($theirs instanceof \DateTimeInterface) { $theirs = $theirs->format('Y-m-d'); }

            if ((string) $mine !== (string) $theirs) {
                return true;
            }
        }

        return false;
    }

    /** Put the live version back over the working copy. */
    public function revertToLive(): bool
    {
        $live = $this->liveRevision;

        if (!$live) {
            return false;
        }

        $this->fill(collect(PostRevision::FIELDS)->mapWithKeys(fn($f) => [$f => $live->{$f}])->all());
        $this->save();

        return true;
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

    /**
     * The shape the website expects.
     *
     * Served from the published version where there is one, so an edit in
     * progress never reaches the site. Posts published before versions were
     * recorded fall back to their own row.
     */
    public function toFeed(): array
    {
        $live = $this->liveRevision ?: $this;

        return array_filter([
            'slug'         => $live->slug,
            'title'        => $live->title,
            'excerpt'      => $live->excerpt,
            'date'         => $live->published_on?->format('Y-m-d'),
            'updated'      => $live->reviewed_on?->format('Y-m-d'),
            'readTime'     => $live->read_time,
            'category'     => $live->category,
            'featured'     => $live->featured ?: null,
            'content'      => $live->content,
            'heroImage'    => $live->hero_image,
            'heroImageAlt' => $live->hero_image_alt,
            'metaTitle'    => $live->meta_title ?: $live->title,
            'metaDescription' => $live->meta_description ?: $live->excerpt,
            'focusKeyphrase'  => $live->focus_keyphrase,
            'canonical'    => $live->canonical_url,
            'noindex'      => $live->noindex ?: null,
        ], fn($v) => $v !== null);
    }
}
