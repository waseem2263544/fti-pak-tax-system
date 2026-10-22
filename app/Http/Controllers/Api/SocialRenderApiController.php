<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Social\SocialPostController;
use App\Models\SocialPost;
use App\Services\Social\SocialBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * API for the Social Studio render worker on the VPS.
 *
 * The worker polls next(), opens the returned render page in headless
 * Chromium, captures it (every frame for a video, one shot per slide for
 * images) and posts the files to complete(). Shared-token auth, like the
 * other machine-to-machine endpoints here.
 *
 * Images and carousels are normally exported by the reviewer's browser the
 * moment they approve; the worker only picks those up if that has not
 * happened within two minutes (a phone, a closed tab).
 */
class SocialRenderApiController extends Controller
{
    public function next(Request $request)
    {
        $this->authorise($request);
        SocialBrand::put('worker_seen_at', now()->toDateTimeString());

        $post = DB::transaction(function () {
            $post = SocialPost::where('status', 'approved')
                ->where('render_status', 'queued')
                ->where(function ($q) {
                    $q->where('format', 'video')
                      ->orWhere('approved_at', '<', now()->subMinutes(2));
                })
                ->orderByRaw("CASE WHEN format = 'video' THEN 0 ELSE 1 END")
                ->orderBy('approved_at')
                ->lockForUpdate()
                ->first();

            $post?->update(['render_status' => 'rendering', 'render_claimed_at' => now()]);

            return $post;
        });

        if (!$post) {
            return response()->json(['job' => null]);
        }

        $music = $post->isVideo() && SocialBrand::musicPath() ? route('social.api.music', ['token' => SocialBrand::renderToken(), 'v' => md5(SocialBrand::musicPath())]) : null;

        return response()->json(['job' => [
            'id'         => $post->id,
            'revision'   => $post->revision,
            'kind'       => $post->isVideo() ? 'video' : 'stills',
            'format'     => $post->format,
            'slides'     => count($post->slides ?? []),
            'duration'   => $post->duration(),
            'render_url' => route('social.render-page', ['post' => $post->id, 'token' => SocialBrand::renderToken()]),
            'music_url'  => $music,
        ]]);
    }

    /**
     * Receive rendered files. Shared hosts cap uploads at a few MB, so the
     * worker sends a video in 1 MB chunks (chunk = 0..chunks-1, appended in
     * order) and still images one per request (index of total).
     */
    public function complete(Request $request, SocialPost $post)
    {
        $this->authorise($request);
        $request->validate([
            'revision' => 'required|integer',
            'chunk'    => 'nullable|integer|min:0',
            'chunks'   => 'nullable|integer|min:1|max:500',
            'part'     => 'nullable|file',
            'index'    => 'nullable|integer|min:0|max:7',
            'total'    => 'nullable|integer|min:1|max:8',
            'image'    => 'nullable|file',
        ]);

        // Edited or re-sent for changes since the job was handed out: drop it.
        if ((int) $request->input('revision') !== $post->revision || $post->status !== 'approved') {
            return response()->json(['ok' => false, 'stale' => true]);
        }

        $disk = Storage::disk('local');

        if ($post->isVideo()) {
            abort_unless($request->hasFile('part') && $request->filled('chunks'), 422, 'part and chunks are required');
            $chunk = (int) $request->input('chunk', 0);
            $chunks = (int) $request->input('chunks');
            $partial = sprintf('social/tmp/post-%d-r%d.mp4.part', $post->id, $post->revision);
            $disk->makeDirectory('social/tmp');
            $target = $disk->path($partial);

            if ($chunk === 0 && file_exists($target)) {
                unlink($target);
            }
            abort_unless($chunk === 0 || file_exists($target), 409, 'Chunk out of order; restart from 0.');
            file_put_contents($target, file_get_contents($request->file('part')->getRealPath()), FILE_APPEND | LOCK_EX);

            if ($chunk + 1 < $chunks) {
                return response()->json(['ok' => true, 'received' => $chunk]);
            }

            $final = sprintf('social/videos/post-%d-r%d.mp4', $post->id, $post->revision);
            $disk->delete($final);
            $disk->move($partial, $final);
            abort_unless(file_get_contents($disk->path($final), false, null, 4, 4) === 'ftyp', 422, 'Not an MP4.');
            $post->update(['video_path' => $final, 'render_status' => 'done', 'render_error' => null]);

            return response()->json(['ok' => true, 'done' => true]);
        }

        abort_unless($request->hasFile('image') && $request->filled('total'), 422, 'image, index and total are required');
        $png = file_get_contents($request->file('image')->getRealPath());
        abort_unless(substr($png, 0, 8) === "\x89PNG\r\n\x1a\n", 422, 'Not a PNG.');
        SocialPostController::saveSlide($post, (int) $request->input('index', 0), (int) $request->input('total'), $png);

        return response()->json(['ok' => true]);
    }

    public function fail(Request $request, SocialPost $post)
    {
        $this->authorise($request);
        $post->update(['render_status' => 'failed', 'render_error' => mb_substr((string) $request->input('error', 'Unknown error'), 0, 2000)]);

        return response()->json(['ok' => true]);
    }

    public function music(Request $request)
    {
        $this->authorise($request);
        $path = SocialBrand::musicPath();
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }

    /**
     * The bare page the worker captures: one stage, no app chrome. Also
     * handy for checking a render by hand (it needs the token).
     */
    public function renderPage(Request $request, SocialPost $post)
    {
        $this->authorise($request);

        return view('social.render', ['post' => $post, 'brand' => SocialBrand::kit()]);
    }

    private function authorise(Request $request): void
    {
        $given = (string) ($request->bearerToken() ?: $request->query('token', ''));
        abort_unless($given !== '' && hash_equals(SocialBrand::renderToken(), $given), 401, 'Bad render token');
    }
}
