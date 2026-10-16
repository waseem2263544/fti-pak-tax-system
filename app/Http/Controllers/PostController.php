<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        return view('blog.edit', ['post' => $post, 'usedKeyphrases' => $this->usedKeyphrases()]);
    }

    public function edit(Post $post)
    {
        return view('blog.edit', ['post' => $post, 'usedKeyphrases' => $this->usedKeyphrases($post)]);
    }

    /**
     * Keyphrases already spoken for, so the editor can warn about competing
     * with your own page for the same search.
     */
    private function usedKeyphrases(?Post $post = null): array
    {
        return Post::whereNotNull('focus_keyphrase')
            ->where('focus_keyphrase', '!=', '')
            ->when($post?->exists, fn($q) => $q->where('id', '!=', $post->id))
            ->get(['id', 'title', 'slug', 'region', 'focus_keyphrase'])
            ->map(fn($p) => [
                'phrase' => mb_strtolower(trim($p->focus_keyphrase)),
                'title'  => $p->title,
                'url'    => route('blog.edit', $p),
            ])->all();
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
     * Take an image for the body of a post.
     *
     * Files land in public/uploads/blog and are served straight off this
     * domain. They stay out of git on purpose - the deploy copies files in and
     * never removes any, so uploads survive a deploy without being carried
     * around in the repository.
     *
     * Anything wider than 1600px is scaled down. A phone photo is 4000px wide
     * and a column in the blog is 700; shipping the original would cost the
     * reader several megabytes for nothing.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file'   => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:12288',
            'folder' => 'nullable|string|max:80|regex:/^[a-z0-9][a-z0-9\-\/]*$/',
        ], [
            'file.mimes'    => 'Images only - jpg, png, gif or webp.',
            'file.max'      => 'That file is over 12 MB.',
            'folder.regex'  => 'A folder may contain only lowercase letters, numbers, hyphens and slashes.',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        // The folder is the writer's to choose - usually the post's own slug,
        // so a post's screenshots stay together and can be found again by
        // name a year later. Date folders are the fallback, not the rule.
        $folder = trim((string) $request->input('folder'), '/');
        $folder = $folder === ''
            ? now()->format('Y/m')
            : implode('/', array_slice(array_filter(explode('/', $folder)), 0, 3));

        $dir = public_path('uploads/blog/' . $folder);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return response()->json(['message' => 'The uploads folder could not be created on the server.'], 500);
        }

        $this->protectUploads();

        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';
        $extension = strtolower($file->getClientOriginalExtension());
        $name = substr($base, 0, 60) . '.' . $extension;

        // Keep the chosen name where it is free, since the file name is read
        // by image search; fall back to a suffix rather than overwriting
        // whatever is already there.
        if (file_exists($dir . '/' . $name)) {
            $name = substr($base, 0, 60) . '-' . Str::lower(Str::random(5)) . '.' . $extension;
        }

        $file->move($dir, $name);
        $path = $dir . '/' . $name;

        [$width, $height] = $this->shrink($path, 1600);

        $relative = 'uploads/blog/' . $folder . '/' . $name;
        $mediaBase = rtrim((string) config('services.site.media_base'), '/');

        return response()->json([
            'url'    => $mediaBase !== ''
                ? $mediaBase . '/' . $folder . '/' . $name   // served under the website's own domain
                : asset($relative),
            'path'   => '/' . $relative,
            'folder' => $folder,
            'width'  => $width,
            'height' => $height,
            'name'   => $name,
        ]);
    }

    /**
     * Tell Apache to cache uploads hard and never run anything in there.
     *
     * The folder is written to by an upload form, so a .php file arriving by
     * some route nobody thought of must not be executable.
     */
    private function protectUploads(): void
    {
        $file = public_path('uploads/.htaccess');
        if (file_exists($file)) {
            return;
        }

        @file_put_contents($file, <<<'HTACCESS'
        <IfModule mod_headers.c>
            Header set Cache-Control "public, max-age=31536000, immutable"
        </IfModule>
        <IfModule mod_authz_core.c>
            <FilesMatch "\.(php|phtml|phar|cgi|pl|py|sh)$">
                Require all denied
            </FilesMatch>
        </IfModule>
        HTACCESS);
    }

    /**
     * Scale an image down in place, and report its dimensions either way.
     *
     * The dimensions matter beyond page weight: width and height on the img
     * tag are what stop the page jumping about as images load, which is a
     * ranking signal in its own right.
     */
    private function shrink(string $path, int $maxWidth): array
    {
        $size = @getimagesize($path);
        if (!$size) {
            return [null, null];
        }

        [$width, $height, $type] = $size;

        if ($width <= $maxWidth || !function_exists('imagecreatetruecolor') || $type === IMAGETYPE_GIF) {
            return [$width, $height];
        }

        $readers = [
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG  => 'imagecreatefrompng',
            IMAGETYPE_WEBP => 'imagecreatefromwebp',
        ];

        if (!isset($readers[$type]) || !function_exists($readers[$type])) {
            return [$width, $height];
        }

        try {
            $source = $readers[$type]($path);
            if (!$source) {
                return [$width, $height];
            }

            $newWidth  = $maxWidth;
            $newHeight = (int) round($height * ($maxWidth / $width));
            $target = imagecreatetruecolor($newWidth, $newHeight);

            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
                imagealphablending($target, false);
                imagesavealpha($target, true);
            }

            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            match ($type) {
                IMAGETYPE_JPEG => imagejpeg($target, $path, 82),
                IMAGETYPE_PNG  => imagepng($target, $path, 7),
                IMAGETYPE_WEBP => imagewebp($target, $path, 82),
            };

            imagedestroy($source);
            imagedestroy($target);

            return [$newWidth, $newHeight];
        } catch (\Throwable $e) {
            Log::warning('Image resize failed', ['path' => $path, 'error' => $e->getMessage()]);

            return [$width, $height];
        }
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

            if ($response->status() === 503) {
                return 'The website has no revalidation secret set, so it was not told. It will catch up within five minutes.';
            }

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
            'focus_keyphrase'    => 'nullable|string|max:200',
            'related_keyphrases' => 'nullable|string|max:500',
            'meta_title'         => 'nullable|string|max:300',
            'meta_description'   => 'nullable|string|max:400',
            'canonical_url'      => 'nullable|string|max:400',
            'noindex'            => 'nullable|boolean',
            'seo_score'          => 'nullable|integer|min:0|max:100',
            'readability_score'  => 'nullable|integer|min:0|max:100',
            'status'         => 'required|in:draft,published',
            'published_on'   => 'nullable|date',
            'reviewed_on'    => 'nullable|date',
            'featured'       => 'nullable|boolean',
        ], [
            'slug.regex' => 'A slug may contain only lowercase letters, numbers and hyphens.',
        ]);

        $data['featured'] = $request->boolean('featured');
        $data['noindex'] = $request->boolean('noindex');

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
