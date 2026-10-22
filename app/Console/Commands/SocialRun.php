<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\SocialIdea;
use App\Models\SocialPost;
use App\Services\Social\SocialBrand;
use App\Services\Social\SocialContentGenerator;
use Illuminate\Console\Command;

/**
 * Social Studio housekeeping. Run hourly from cron:
 *
 *   cd /home/USER/public_html && php artisan social:run
 *
 * 1. Once a day (from the hour set in Settings) drafts the day's posts from
 *    the idea bank, asking Claude for ideas first if the bank is empty.
 * 2. Finishes posts left in "generating" - a browser tab closed or the host
 *    stopped the web request before Claude answered.
 * 3. Puts video renders the VPS worker claimed but never finished back in
 *    the queue.
 */
class SocialRun extends Command
{
    protected $signature = 'social:run {--daily : Run the daily batch now, whatever the time}';

    protected $description = 'Draft the daily TikTok posts and tidy up stuck Social Studio jobs';

    public function handle(SocialContentGenerator $generator): int
    {
        @set_time_limit(0);

        $this->resetStaleRenders();
        $this->finishStuckGenerations($generator);

        if ($this->option('daily') || $this->dailyDue()) {
            $this->dailyBatch($generator);
        }

        return self::SUCCESS;
    }

    private function dailyDue(): bool
    {
        if (!SocialBrand::dailyEnabled() || !SocialBrand::apiKey()) {
            return false;
        }
        $now = now('Asia/Karachi');

        return $now->hour >= (int) SocialBrand::get('daily_hour', 9)
            && SocialBrand::get('daily_last') !== $now->toDateString();
    }

    private function dailyBatch(SocialContentGenerator $generator): void
    {
        SocialBrand::put('daily_last', now('Asia/Karachi')->toDateString());

        $count = SocialBrand::dailyCount();
        $formats = SocialBrand::dailyFormats();

        if (SocialIdea::where('status', 'new')->count() < $count) {
            try {
                $generator->suggestIdeas(max(10, $count * 3));
            } catch (\Throwable $e) {
                report($e);
                $this->error('Could not get ideas: ' . $e->getMessage());
            }
        }

        $ideas = SocialIdea::where('status', 'new')->oldest()->limit($count)->get();
        $offset = (int) SocialBrand::get('daily_format_cursor', 0);

        foreach ($ideas as $i => $idea) {
            $format = $idea->format && in_array($idea->format, $formats, true)
                ? $idea->format
                : $formats[($offset + $i) % count($formats)];

            $post = SocialPost::create([
                'format'      => $format,
                'status'      => 'generating',
                'topic'       => $idea->topic,
                'audience'    => $idea->audience,
                'brief'       => $idea->angle,
                'idea_id'     => $idea->id,
                'planned_for' => now('Asia/Karachi')->toDateString(),
            ]);
            $idea->update(['status' => 'used', 'used_at' => now(), 'post_id' => $post->id]);

            $generator->generate($post);
            $this->line("#{$post->id} {$format}: {$post->fresh()->status}");
        }

        SocialBrand::put('daily_format_cursor', $offset + $ideas->count());
    }

    private function finishStuckGenerations(SocialContentGenerator $generator): void
    {
        SocialPost::where('status', 'generating')
            ->where('updated_at', '<', now()->subMinutes(10))
            ->limit(5)
            ->get()
            ->each(function (SocialPost $post) use ($generator) {
                $post->touch();
                $generator->generate($post);
                $this->line("Finished stuck #{$post->id}: {$post->fresh()->status}");
            });
    }

    private function resetStaleRenders(): void
    {
        $n = SocialPost::where('render_status', 'rendering')
            ->where('render_claimed_at', '<', now()->subMinutes(30))
            ->update(['render_status' => 'queued', 'render_claimed_at' => null]);
        if ($n) {
            $this->line("Re-queued {$n} stalled render(s).");
        }
    }
}
