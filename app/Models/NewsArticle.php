<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    protected $fillable = [
        'title', 'url', 'source', 'category', 'summary',
        'published_at', 'also_reported_by', 'is_pinned',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'also_reported_by' => 'array',
        'is_pinned' => 'boolean',
    ];

    /**
     * Check if a similar title already exists within the last 7 days.
     * Returns the existing article if found, null otherwise.
     */
    public static function findSimilar($title, $days = 7)
    {
        $recent = self::where('published_at', '>=', now()->subDays($days))->get();
        $cleanNew = self::normalizeTitle($title);

        foreach ($recent as $article) {
            $cleanExisting = self::normalizeTitle($article->title);
            $similarity = 0;
            similar_text($cleanNew, $cleanExisting, $similarity);
            if ($similarity >= 75) {
                return $article;
            }
        }
        return null;
    }

    private static function normalizeTitle($title)
    {
        $title = strtolower(trim($title));
        $title = preg_replace('/[^a-z0-9\s]/', '', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        return $title;
    }
}
