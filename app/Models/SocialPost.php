<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A TikTok post in Social Studio.
 *
 * Lifecycle:
 *   generating  -> Claude is writing it
 *   review      -> waiting for a person
 *   changes     -> sent back with feedback; "Regenerate" rewrites it
 *   approved    -> signed off; files are rendered and can be downloaded
 *   posted      -> marked as uploaded to TikTok
 *   rejected    -> dropped
 *   failed      -> generation errored (see `error`)
 *
 * `slides` holds the on-screen content. For an image it is one slide, for a
 * carousel 4-8, for a video 5-8 scenes, each with a duration in seconds. All
 * three render through public/social-assets/renderer.js.
 */
class SocialPost extends Model
{
    protected $fillable = [
        'format', 'status', 'topic', 'audience', 'brief', 'title', 'caption', 'hashtags',
        'slides', 'fact_checks', 'feedback', 'revision', 'assets', 'video_path',
        'render_status', 'render_error', 'render_claimed_at', 'error', 'ai_model', 'ai_usage',
        'idea_id', 'created_by', 'approved_by', 'approved_at', 'planned_for', 'posted_at',
    ];

    protected $casts = [
        'hashtags'          => 'array',
        'slides'            => 'array',
        'fact_checks'       => 'array',
        'assets'            => 'array',
        'ai_usage'          => 'array',
        'approved_at'       => 'datetime',
        'posted_at'         => 'datetime',
        'render_claimed_at' => 'datetime',
        'planned_for'       => 'date',
    ];

    public const FORMATS = [
        'image'    => 'Image',
        'carousel' => 'Carousel',
        'video'    => 'Video',
    ];

    public const STATUSES = [
        'generating' => 'Generating',
        'review'     => 'Needs review',
        'changes'    => 'Changes requested',
        'approved'   => 'Approved',
        'posted'     => 'Posted',
        'rejected'   => 'Rejected',
        'failed'     => 'Failed',
    ];

    public const STATUS_COLORS = [
        'generating' => 'info',
        'review'     => 'warning',
        'changes'    => 'secondary',
        'approved'   => 'success',
        'posted'     => 'primary',
        'rejected'   => 'dark',
        'failed'     => 'danger',
    ];

    public const FORMAT_ICONS = [
        'image'    => 'bi-image',
        'carousel' => 'bi-collection',
        'video'    => 'bi-camera-reels',
    ];

    /** Slide layouts the renderer understands. */
    public const LAYOUTS = ['cover', 'point', 'list', 'stat', 'warning', 'steps', 'cta'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function idea()
    {
        return $this->belongsTo(SocialIdea::class, 'idea_id');
    }

    public function isVideo(): bool
    {
        return $this->format === 'video';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['review', 'changes', 'failed'], true);
    }

    /** Total running time of a video, in seconds. */
    public function duration(): float
    {
        return collect($this->slides ?? [])->sum(fn ($s) => (float) ($s['duration'] ?? 3));
    }

    /** True once the downloadable files exist. */
    public function hasFiles(): bool
    {
        if ($this->isVideo()) {
            return $this->video_path && Storage::disk('local')->exists($this->video_path);
        }

        return !empty($this->assets);
    }

    public function captionWithTags(): string
    {
        $tags = collect($this->hashtags ?? [])
            ->map(fn ($t) => '#' . ltrim(trim($t), '#'))
            ->filter(fn ($t) => $t !== '#')
            ->implode(' ');

        return trim(($this->caption ?? '') . ($tags ? "\n\n" . $tags : ''));
    }

    /** Forget rendered files, e.g. after the content was edited. */
    public function clearRenders(): void
    {
        foreach ($this->assets ?? [] as $path) {
            Storage::disk('local')->delete($path);
        }
        if ($this->video_path) {
            Storage::disk('local')->delete($this->video_path);
        }
        $this->assets = null;
        $this->video_path = null;
        $this->render_status = null;
        $this->render_error = null;
        $this->render_claimed_at = null;
    }

    /** What the renderer needs, in one plain array. */
    public function renderPayload(): array
    {
        return [
            'id'     => $this->id,
            'format' => $this->format,
            'slides' => $this->slides ?? [],
        ];
    }
}
