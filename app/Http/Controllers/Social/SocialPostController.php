<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\SocialIdea;
use App\Models\SocialPost;
use App\Services\Social\SocialBrand;
use App\Services\Social\SocialContentGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Social Studio: the review queue for TikTok posts.
 *
 * Claude drafts; a person reads every slide and every fact-check line, edits
 * or sends it back with notes, and approves. Approval renders the files:
 * images and carousels in the reviewer's browser (renderer.js), videos on the
 * VPS render worker. Nothing is posted to TikTok from here - approved files
 * are downloaded and uploaded by hand.
 */
class SocialPostController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists($request->get('status'), SocialPost::STATUSES) ? $request->get('status') : null;
        $format = array_key_exists($request->get('format'), SocialPost::FORMATS) ? $request->get('format') : null;

        $posts = SocialPost::with('approver')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when(!$status, fn ($q) => $q->whereNotIn('status', ['rejected']))
            ->when($format, fn ($q) => $q->where('format', $format))
            ->orderByRaw("CASE status WHEN 'review' THEN 1 WHEN 'generating' THEN 2 WHEN 'changes' THEN 3 WHEN 'failed' THEN 4 WHEN 'approved' THEN 5 WHEN 'posted' THEN 6 ELSE 7 END")
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        $counts = SocialPost::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('social.index', [
            'posts'      => $posts,
            'counts'     => $counts,
            'status'     => $status,
            'format'     => $format,
            'brand'      => SocialBrand::kit(),
            'hasKey'     => (bool) SocialBrand::apiKey(),
            'ideasCount' => SocialIdea::where('status', 'new')->count(),
        ]);
    }

    public function create(Request $request)
    {
        $idea = $request->get('idea') ? SocialIdea::find($request->get('idea')) : null;

        return view('social.create', [
            'idea'     => $idea,
            'ideas'    => SocialIdea::where('status', 'new')->oldest()->limit(30)->get(),
            'audience' => SocialBrand::defaultAudience(),
            'hasKey'   => (bool) SocialBrand::apiKey(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'formats'     => 'required|array|min:1',
            'formats.*'   => 'in:image,carousel,video',
            'topic'       => 'required|string|max:500',
            'audience'    => 'nullable|string|max:200',
            'brief'       => 'nullable|string|max:3000',
            'planned_for' => 'nullable|date',
            'idea_id'     => 'nullable|integer',
        ]);

        $idea = !empty($data['idea_id']) ? SocialIdea::find($data['idea_id']) : null;
        $posts = collect(array_unique($data['formats']))->map(fn ($format) => SocialPost::create([
            'format'      => $format,
            'status'      => 'generating',
            'topic'       => $data['topic'],
            'audience'    => $data['audience'] ?? null,
            'brief'       => $data['brief'] ?? null,
            'planned_for' => $data['planned_for'] ?? null,
            'idea_id'     => $idea?->id,
            'created_by'  => $request->user()->id,
        ]));

        $idea?->update(['status' => 'used', 'used_at' => now(), 'post_id' => $posts->first()->id]);

        if ($posts->count() === 1) {
            return redirect()->route('social.show', $posts->first());
        }

        return redirect()->route('social.index')
            ->with('success', $posts->count() . ' posts are being written. They move to "Needs review" as each one finishes.');
    }

    public function show(SocialPost $post)
    {
        return view('social.show', [
            'post'  => $post->load('approver', 'creator'),
            'brand' => SocialBrand::kit(),
            'prev'  => SocialPost::where('status', 'review')->where('id', '<', $post->id)->max('id'),
            'next'  => SocialPost::where('status', 'review')->where('id', '>', $post->id)->min('id'),
        ]);
    }

    /**
     * Runs Claude for a post in "generating" state. The page that asked waits
     * on this request; if it is cut off, social:run finishes the job.
     */
    public function generate(SocialPost $post, SocialContentGenerator $generator)
    {
        if ($post->status !== 'generating') {
            return response()->json(['status' => $post->status]);
        }

        $lock = Cache::lock('social-generate-' . $post->id, 330);
        if (!$lock->get()) {
            return response()->json(['status' => 'generating', 'busy' => true]);
        }

        try {
            @set_time_limit(330);
            ignore_user_abort(true);
            $post->touch();
            $generator->generate($post);
        } finally {
            $lock->release();
        }

        return response()->json(['status' => $post->fresh()->status]);
    }

    public function status(SocialPost $post)
    {
        return response()->json([
            'status'        => $post->status,
            'render_status' => $post->render_status,
            'has_files'     => $post->hasFiles(),
            'error'         => $post->error,
        ]);
    }

    /** Save edits made in the review page's editor. */
    public function update(Request $request, SocialPost $post)
    {
        abort_if(in_array($post->status, ['generating', 'posted'], true), 422, 'This post cannot be edited now.');

        $data = $request->validate([
            'slides'     => 'required|array|min:1|max:8',
            'caption'    => 'nullable|string|max:2200',
            'hashtags'   => 'nullable|string|max:500',
            'title'      => 'nullable|string|max:300',
        ]);

        $slides = array_map(fn ($s) => SocialContentGenerator::cleanSlide((array) $s, $post->format), $data['slides']);
        if ($post->format === 'image') {
            $slides = array_slice($slides, 0, 1);
        }

        $post->clearRenders();
        $post->fill([
            'slides'   => $slides,
            'caption'  => trim((string) ($data['caption'] ?? '')),
            'hashtags' => $this->parseTags($data['hashtags'] ?? ''),
            'title'    => $data['title'] ?? $post->title,
        ]);
        if ($post->status === 'approved') {
            // An approved post that changes needs approving again.
            $post->status = 'review';
            $post->approved_at = null;
            $post->approved_by = null;
        }
        $post->save();

        return response()->json(['ok' => true, 'status' => $post->status]);
    }

    public function approve(Request $request, SocialPost $post)
    {
        abort_unless(in_array($post->status, ['review', 'changes'], true), 422, 'Only a post waiting for review can be approved.');

        $post->clearRenders();
        $post->fill([
            'status'        => 'approved',
            'approved_by'   => $request->user()->id,
            'approved_at'   => now(),
            'feedback'      => null,
            'render_status' => 'queued',
        ])->save();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('social.show', $post)->with('success', $post->isVideo()
            ? 'Approved. The video is queued for rendering.'
            : 'Approved. Preparing the image files...');
    }

    public function reject(SocialPost $post)
    {
        $post->clearRenders();
        $post->fill(['status' => 'rejected'])->save();

        return redirect()->route('social.index')->with('success', 'Post rejected.');
    }

    /** Send back with notes and let Claude rewrite it. */
    public function requestChanges(Request $request, SocialPost $post)
    {
        $data = $request->validate(['feedback' => 'required|string|max:3000']);

        $post->clearRenders();
        $post->fill([
            'feedback'    => $data['feedback'],
            'status'      => 'generating',
            'revision'    => $post->revision + 1,
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        return redirect()->route('social.show', $post);
    }

    /** Start again from the topic, ignoring the current draft. */
    public function regenerate(SocialPost $post)
    {
        abort_if($post->status === 'generating', 422);

        $post->clearRenders();
        $post->fill(['feedback' => null, 'status' => 'generating', 'revision' => $post->revision + 1, 'error' => null])->save();

        return redirect()->route('social.show', $post);
    }

    /**
     * One rendered slide from the reviewer's browser, as a PNG data URL.
     * Sent one slide per request to stay under the host's post size limit.
     */
    public function storeAsset(Request $request, SocialPost $post)
    {
        abort_unless($post->status === 'approved' && !$post->isVideo(), 422);

        $data = $request->validate([
            'index' => 'required|integer|min:0|max:7',
            'total' => 'required|integer|min:1|max:8',
            'png'   => 'required|string',
        ]);
        abort_unless(str_starts_with($data['png'], 'data:image/png;base64,'), 422, 'Expected a PNG.');

        $binary = base64_decode(substr($data['png'], strlen('data:image/png;base64,')), true);
        abort_unless($binary !== false && substr($binary, 0, 8) === "\x89PNG\r\n\x1a\n", 422, 'Not a PNG.');

        $this->saveSlide($post, (int) $data['index'], (int) $data['total'], $binary);

        return response()->json(['ok' => true, 'has_files' => $post->fresh()->hasFiles()]);
    }

    /** Shared with the render API: keep one slide image, finish when all are in. */
    public static function saveSlide(SocialPost $post, int $index, int $total, string $binary): void
    {
        $path = sprintf('social/assets/%d/r%d-slide-%02d.png', $post->id, $post->revision, $index + 1);
        Storage::disk('local')->put($path, $binary);

        $assets = $post->fresh()->assets ?? [];
        $assets[$index] = $path;
        ksort($assets);
        $post->assets = $assets;

        if (count(array_filter($assets)) >= $total) {
            $post->assets = array_values($assets);
            $post->render_status = 'done';
            $post->render_error = null;
        }
        $post->save();
    }

    public function rerender(SocialPost $post)
    {
        abort_unless($post->status === 'approved', 422);
        $post->clearRenders();
        $post->render_status = 'queued';
        $post->save();

        return redirect()->route('social.show', $post)->with('success', 'Queued for rendering again.');
    }

    public function download(SocialPost $post, Request $request)
    {
        abort_unless($post->hasFiles(), 404, 'The files are not ready yet.');
        $base = $this->fileBase($post);

        if ($post->isVideo()) {
            return Storage::disk('local')->download($post->video_path, $base . '.mp4');
        }

        $assets = $post->assets ?? [];
        if ($request->has('slide')) {
            $i = (int) $request->get('slide');
            abort_unless(isset($assets[$i]), 404);

            return Storage::disk('local')->download($assets[$i], sprintf('%s-%02d.png', $base, $i + 1));
        }
        if (count($assets) === 1) {
            return Storage::disk('local')->download($assets[0], $base . '.png');
        }

        Storage::disk('local')->makeDirectory('social/tmp');
        $zipPath = Storage::disk('local')->path('social/tmp/' . $base . '-' . Str::random(6) . '.zip');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($assets as $i => $path) {
            $zip->addFile(Storage::disk('local')->path($path), sprintf('%s-%02d.png', $base, $i + 1));
        }
        $zip->addFromString('caption.txt', $post->captionWithTags());
        $zip->close();

        return response()->download($zipPath, $base . '.zip')->deleteFileAfterSend(true);
    }

    /** A rendered slide image, for thumbnails on the board. */
    public function asset(SocialPost $post, int $index)
    {
        $path = ($post->assets ?? [])[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'private, max-age=86400']);
    }

    public function video(SocialPost $post)
    {
        abort_unless($post->isVideo() && $post->hasFiles(), 404);

        return response()->file(Storage::disk('local')->path($post->video_path), ['Content-Type' => 'video/mp4']);
    }

    public function markPosted(SocialPost $post)
    {
        abort_unless(in_array($post->status, ['approved', 'posted'], true), 422);
        $post->update(['status' => $post->status === 'posted' ? 'approved' : 'posted', 'posted_at' => $post->status === 'posted' ? null : now()]);

        return back()->with('success', $post->status === 'posted' ? 'Marked as posted on TikTok.' : 'Marked as not posted.');
    }

    public function destroy(SocialPost $post)
    {
        $post->clearRenders();
        Storage::disk('local')->deleteDirectory('social/assets/' . $post->id);
        $post->delete();

        return redirect()->route('social.index')->with('success', 'Post deleted.');
    }

    private function fileBase(SocialPost $post): string
    {
        return 'fti-tiktok-' . $post->id . '-' . (Str::slug(Str::limit($post->title ?: $post->topic, 40, '')) ?: $post->format);
    }

    private function parseTags(string $raw): array
    {
        return collect(preg_split('/[\s,]+/u', $raw))
            ->map(fn ($t) => ltrim(trim($t), '#'))
            ->filter()->unique()->take(12)->values()->all();
    }
}
