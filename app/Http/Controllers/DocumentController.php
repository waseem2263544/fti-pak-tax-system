<?php

namespace App\Http\Controllers;

use App\Services\Microsoft\BlankDocument;
use App\Services\Microsoft\SharePointClient;
use Illuminate\Http\Request;

/**
 * Browse and work on the SharePoint document library from inside the app.
 *
 * Editing happens in Office for the web, opened in a new tab against the user's
 * own Microsoft session — so changes are attributed to the person and saved
 * straight back to SharePoint. The app lists, creates, uploads and deletes; it
 * deliberately does not proxy file contents for editing, because a copy served
 * through here would be a second copy and lose co-authoring.
 */
class DocumentController extends Controller
{
    public function __construct(private SharePointClient $sharepoint)
    {
    }

    /** The type buckets the browser filters by, in display order. */
    public const TYPES = [
        'folder' => 'Folders',
        'word'   => 'Word',
        'excel'  => 'Excel',
        'pdf'    => 'PDF',
        'image'  => 'Images',
        'other'  => 'Other',
    ];

    private const EXTENSIONS = [
        'word'  => ['doc', 'docx', 'docm', 'dot', 'dotx', 'rtf', 'odt'],
        'excel' => ['xls', 'xlsx', 'xlsm', 'xlsb', 'csv', 'ods'],
        'pdf'   => ['pdf'],
        'image' => ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'heic', 'svg', 'tif', 'tiff'],
    ];

    /**
     * Which bucket an item belongs to. Classified once here rather than in the
     * template, so the filter and the icon can never disagree.
     */
    public static function classify(array $item): string
    {
        if (isset($item['folder'])) {
            return 'folder';
        }

        $ext = strtolower(pathinfo($item['name'] ?? '', PATHINFO_EXTENSION));

        foreach (self::EXTENSIONS as $type => $extensions) {
            if (in_array($ext, $extensions, true)) {
                return $type;
            }
        }

        return 'other';
    }

    public function index(Request $request)
    {
        // With a root folder configured the browser opens there and stays
        // inside it, rather than at the top of the whole library.
        $folder = $request->get('folder') ?: $this->sharepoint->rootFolder();

        if (!$this->sharepoint->connected()) {
            return view('documents.index', [
                'error'      => 'No Microsoft account is connected. Connect one under Settings → Email.',
                'items'      => collect(),
                'breadcrumb' => [],
                'folder'     => null,
                'search'     => '',
                'type'       => null,
                'typeCounts' => [],
                'folderUrl'  => null,
            ]);
        }

        $search = trim((string) $request->get('q', ''));

        try {
            $items = $search !== ''
                ? collect($this->sharepoint->search($search, $this->sharepoint->rootFolder()))
                : collect($this->sharepoint->children($folder));

            $breadcrumb = $search !== '' ? [] : $this->sharepoint->breadcrumb($folder);
            $error = null;
        } catch (\Throwable $e) {
            $items = collect();
            $breadcrumb = [];
            $error = $e->getMessage();
        }

        // The breadcrumb already fetched this folder, so its URL is free below
        // the root; only the root itself needs a lookup, and that is cached.
        $folderUrl = $error
            ? null
            : (end($breadcrumb)['webUrl'] ?? $this->sharepoint->folderUrl($folder));

        // Folders first, then files, each alphabetically.
        $items = $items->sortBy([
            fn($a, $b) => (isset($b['folder']) <=> isset($a['folder'])),
            fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''),
        ])->values();

        // Attach a readable location to every row, so search results say where
        // they came from without the caller parsing Graph paths in a template.
        $sp = $this->sharepoint;

        // Search results have no parentReference.path, so those are resolved by
        // looking their parents up once each.
        $parentPaths = [];

        if ($search !== '' && $items->isNotEmpty() && !$error) {
            try {
                $parentPaths = $sp->resolveParentPaths($items->all());
            } catch (\Throwable) {
                $parentPaths = [];
            }
        }

        $items = $items->map(function ($item) use ($sp, $parentPaths) {
            $item['_path'] = $sp->relativePath($item)
                ?: ($parentPaths[$item['parentReference']['id'] ?? ''] ?? '');
            $item['_type'] = self::classify($item);

            return $item;
        });

        // Counted before filtering, so every button shows what it would give.
        $typeCounts = collect(self::TYPES)
            ->map(fn($label, $type) => $items->where('_type', $type)->count())
            ->all();

        $type = $request->get('type');

        if ($type && array_key_exists($type, self::TYPES)) {
            $items = $items->where('_type', $type)->values();
        } else {
            $type = null;
        }

        return view('documents.index', compact('items', 'breadcrumb', 'folder', 'error', 'search', 'type', 'typeCounts', 'folderUrl'));
    }

    /**
     * Type-ahead suggestions for the search box.
     *
     * Kept deliberately small and briefly cached: this runs on every keystroke
     * once debounced, and each call is a Graph round trip. Parent paths are
     * resolved with a low cap since suggestions share a handful of folders.
     */
    public function suggest(Request $request)
    {
        $query = trim((string) $request->get('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['items' => []]);
        }

        $key = 'sp_suggest:' . md5($query);

        try {
            $items = \Illuminate\Support\Facades\Cache::remember($key, now()->addSeconds(60), function () use ($query) {
                $results = collect($this->sharepoint->search($query, $this->sharepoint->rootFolder()))
                    ->take(8)
                    ->values();

                $paths = $results->isNotEmpty()
                    ? $this->sharepoint->resolveParentPaths($results->all(), 5)
                    : [];

                return $results->map(function ($item) use ($paths) {
                    $type = self::classify($item);

                    return [
                        'id'     => $item['id'] ?? null,
                        'name'   => $item['name'] ?? '',
                        'type'   => $type,
                        'folder' => $type === 'folder',
                        'path'   => $this->sharepoint->relativePath($item)
                                    ?: ($paths[$item['parentReference']['id'] ?? ''] ?? ''),
                        'url'    => $item['webUrl'] ?? null,
                    ];
                })->all();
            });
        } catch (\Throwable $e) {
            return response()->json(['items' => [], 'error' => $e->getMessage()]);
        }

        return response()->json(['items' => $items]);
    }

    /** New blank Word or Excel document, created then opened for editing. */
    public function createOffice(Request $request)
    {
        $validated = $request->validate([
            'type'   => 'required|in:docx,xlsx',
            'name'   => 'required|string|max:200',
            'folder' => 'nullable|string',
        ]);

        $name = trim($validated['name']);
        $ext = $validated['type'];

        if (!str_ends_with(strtolower($name), '.' . $ext)) {
            $name .= '.' . $ext;
        }

        try {
            $item = $this->sharepoint->upload(
                $validated['folder'] ?? null,
                $name,
                BlankDocument::forExtension($ext)
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('documents.index', ['folder' => $validated['folder']])
            ->with('success', "Created {$item['name']}.")
            ->with('open_url', $item['webUrl'] ?? null);
    }

    public function createFolder(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:200',
            'folder' => 'nullable|string',
        ]);

        try {
            $this->sharepoint->createFolder($validated['folder'] ?? null, trim($validated['name']));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Folder created.');
    }

    /**
     * What PHP will actually accept, in kilobytes and file count.
     *
     * The form says the real limits rather than a number written into the
     * template, because they are set by the host and change without us.
     */
    public static function uploadLimits(): array
    {
        $toKb = function (string $value): int {
            $value = trim($value);
            $unit  = strtolower(substr($value, -1));
            $n     = (int) $value;

            return match ($unit) {
                'g' => $n * 1024 * 1024,
                'm' => $n * 1024,
                'k' => $n,
                default => (int) ($n / 1024),
            };
        };

        $perFile = $toKb((string) ini_get('upload_max_filesize'));
        $post    = $toKb((string) ini_get('post_max_size'));
        $count   = (int) ini_get('max_file_uploads') ?: 20;

        return [
            'per_file' => $perFile > 0 ? $perFile : 2048,
            'total'    => $post > 0 ? $post : 8192,
            'count'    => $count,
        ];
    }

    /**
     * Upload one file or many.
     *
     * Each file is sent to Graph on its own, and one failing does not stop the
     * rest: a batch of twenty where the third is rejected should still leave
     * nineteen in SharePoint, and say which one did not make it.
     */
    public function upload(Request $request)
    {
        $limits = self::uploadLimits();

        $validated = $request->validate([
            'files'   => 'required|array|min:1|max:' . $limits['count'],
            'files.*' => 'file|max:' . $limits['per_file'],
            'folder'  => 'nullable|string',
        ], [
            'files.required' => 'Choose at least one file.',
            'files.*.max'    => 'Each file must be under ' . round($limits['per_file'] / 1024, 1) . ' MB.',
        ]);

        $folder = $validated['folder'] ?? null;
        $done = [];
        $failed = [];

        foreach ($request->file('files') as $file) {
            $name = $file->getClientOriginalName();

            try {
                $item = $this->sharepoint->upload($folder, $name, file_get_contents($file->getRealPath()));
                $done[] = $item['name'] ?? $name;
            } catch (\Throwable $e) {
                $failed[$name] = $e->getMessage();
            }
        }

        if ($done && !$failed) {
            return back()->with('success', count($done) === 1
                ? "Uploaded {$done[0]}."
                : 'Uploaded ' . count($done) . ' files.');
        }

        if ($done && $failed) {
            return back()->with('error', sprintf(
                'Uploaded %d of %d. These did not go up: %s',
                count($done),
                count($done) + count($failed),
                collect($failed)->map(fn($why, $name) => "{$name} ({$why})")->implode('; ')
            ));
        }

        return back()->with('error', 'Nothing was uploaded. ' .
            collect($failed)->map(fn($why, $name) => "{$name}: {$why}")->implode('; '));
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'id'   => 'required|string',
            'name' => 'required|string',
        ]);

        try {
            $bytes = $this->sharepoint->download($validated['id']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()->streamDownload(fn() => print($bytes), $validated['name']);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'id'     => 'required|string',
            'folder' => 'nullable|string',
        ]);

        try {
            $this->sharepoint->delete($validated['id']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Deleted.');
    }

    public function rename(Request $request)
    {
        $validated = $request->validate([
            'id'   => 'required|string',
            'name' => 'required|string|max:200',
        ]);

        try {
            $this->sharepoint->rename($validated['id'], trim($validated['name']));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Renamed.');
    }

    /**
     * Sub-folders of a folder, for the move and copy picker to browse.
     *
     * Returns the destination's own id and name too, so the dialog can show
     * where you currently are and offer it as the target.
     */
    public function folders(Request $request)
    {
        $id = $request->get('id') ?: $this->sharepoint->rootFolder();

        try {
            $folders = collect($this->sharepoint->folders($id))
                ->map(fn($f) => [
                    'id'       => $f['id'],
                    'name'     => $f['name'],
                    'children' => $f['folder']['childCount'] ?? 0,
                ])
                ->values();

            return response()->json([
                'current'    => $id,
                'breadcrumb' => $this->sharepoint->breadcrumb($id),
                'folders'    => $folders,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function move(Request $request)
    {
        $validated = $request->validate([
            'id'     => 'required|string',
            'target' => 'required|string',
        ]);

        if ($validated['id'] === $validated['target']) {
            return back()->with('error', 'A folder cannot be moved into itself.');
        }

        try {
            $this->sharepoint->move($validated['id'], $validated['target']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Moved.');
    }

    public function copy(Request $request)
    {
        $validated = $request->validate([
            'id'     => 'required|string',
            'target' => 'required|string',
            'name'   => 'nullable|string|max:200',
        ]);

        try {
            $this->sharepoint->copy($validated['id'], $validated['target'], $validated['name'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        // Graph copies in the background, so the new item may not be listed yet.
        return back()->with('success', 'Copy started. Large folders can take a moment to appear.');
    }
}
