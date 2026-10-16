<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Blog posts for fairtaxint.com.
 *
 * The website reads the published feed from here. Publishing also asks Vercel
 * to regenerate that page, so a correction is live in seconds rather than
 * waiting on a deploy - which is the difference between fixing a wrong tax
 * figure now and fixing it after lunch.
 */
class PostController extends Controller
{
    public function index(Request $request)
    {
        $region = in_array($request->get('region'), ['pk', 'ae'], true) ? $request->get('region') : null;
        $search = trim((string) $request->get('q', ''));

        $posts = Post::with('author')
            ->when($region, fn($q) => $q->where('region', $region))
            ->when($request->get('status'), fn($q, $s) => $q->where('status', $s))
            ->when($search !== '', fn($q) => $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            }))
            ->orderByRaw("status = 'draft' DESC")
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'draft'     => Post::where('status', 'draft')->count(),
            'published' => Post::live()->count(),
        ];

        return view('blog.index', compact('posts', 'region', 'search', 'counts'));
    }

    public function create()
    {
        $post = new Post(['region' => 'pk', 'status' => 'draft', 'published_on' => now()]);

        return view('blog.edit', compact('post'));
    }

    public function edit(Post $post)
    {
        return view('blog.edit', compact('post'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['slug'] = $data['slug'] ?: Post::uniqueSlug($data['title'], $data['region']);
        $data['author_id'] = auth()->id();

        $post = Post::create($data);

        if (!$post->read_time) {
            $post->update(['read_time' => $post->estimateReadTime()]);
        }

        return redirect()->route('blog.edit', $post)->with('success', 'Post created.');
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request, $post);
        $wasLive = $post->status === 'published';

        $data['slug'] = $data['slug'] ?: Post::uniqueSlug($data['title'], $data['region'], $post->id);

        $post->update($data);

        if (!$post->read_time) {
            $post->update(['read_time' => $post->estimateReadTime()]);
        }

        // Regenerate the page when something live has changed, and when a post
        // has just gone live. An edit nobody can see needs no revalidation.
        $message = 'Saved.';

        if ($post->status === 'published' || $wasLive) {
            $message .= ' ' . $this->revalidate($post);
        }

        return back()->with('success', $message);
    }

    public function destroy(Post $post)
    {
        $region = $post->region;
        $slug = $post->slug;
        $wasLive = $post->status === 'published';

        $post->delete();

        if ($wasLive) {
            $this->ping(['/' . $region . '/blog', '/' . $region . '/blog/' . $slug]);
        }

        return redirect()->route('blog.index')->with('success', 'Post deleted.');
    }

    /**
     * Ask Vercel to rebuild the pages this post appears on.
     *
     * Both the post itself and the index that lists it, since a new title or
     * excerpt shows in two places.
     */
    private function revalidate(Post $post): string
    {
        return $this->ping([
            '/' . $post->region . '/blog',
            '/' . $post->region . '/blog/' . $post->slug,
        ]);
    }

    private function ping(array $paths): string
    {
        $endpoint = (string) config('services.vercel.revalidate_url');
        $secret   = (string) config('services.vercel.revalidate_secret');

        if ($endpoint === '' || $secret === '') {
            return 'The website was not told — no revalidation endpoint is configured yet.';
        }

        try {
            $response = Http::timeout(15)->post($endpoint, [
                'secret' => $secret,
                'paths'  => $paths,
            ]);

            if ($response->successful()) {
                return 'The website has been asked to refresh those pages.';
            }

            Log::warning('Vercel revalidation refused', ['status' => $response->status(), 'paths' => $paths]);

            return 'The website refused the refresh (' . $response->status() . '). It will catch up on its own schedule.';
        } catch (\Throwable $e) {
            Log::warning('Vercel revalidation failed', ['error' => $e->getMessage()]);

            return 'The website could not be reached. It will catch up on its own schedule.';
        }
    }

    private function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'region'         => 'required|in:pk,ae',
            'title'          => 'required|string|max:300',
            'slug'           => 'nullable|string|max:200|regex:/^[a-z0-9-]+$/',
            'excerpt'        => 'nullable|string|max:1000',
            'content'        => 'nullable|string',
            'category'       => 'nullable|string|max:80',
            'read_time'      => 'nullable|string|max:30',
            'hero_image'     => 'nullable|string|max:400',
            'hero_image_alt' => 'nullable|string|max:300',
            'status'         => 'required|in:draft,published',
            'published_on'   => 'nullable|date',
            'reviewed_on'    => 'nullable|date',
            'featured'       => 'nullable|boolean',
        ], [
            'slug.regex' => 'A slug may contain only lowercase letters, numbers and hyphens.',
        ]);

        $data['featured'] = $request->boolean('featured');

        return $data;
    }

    /**
     * The feed the website reads.
     *
     * Open on purpose: it is the content of a public blog, and requiring a
     * token would only mean putting one in the site's build environment to
     * fetch what anyone can already read.
     */
    public function feed(Request $request)
    {
        $region = in_array($request->get('region'), ['pk', 'ae'], true) ? $request->get('region') : null;

        $posts = Post::live()
            ->when($region, fn($q) => $q->where('region', $region))
            ->orderByDesc('published_on')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'count'        => $posts->count(),
            'posts'        => $region
                ? $posts->map->toFeed()->values()
                : $posts->groupBy('region')->map(fn($g) => $g->map->toFeed()->values()),
        ])->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }
}
