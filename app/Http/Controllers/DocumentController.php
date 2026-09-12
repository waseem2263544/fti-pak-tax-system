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

            return $item;
        });

        return view('documents.index', compact('items', 'breadcrumb', 'folder', 'error', 'search'));
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

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'file'   => 'required|file|max:4096',
            'folder' => 'nullable|string',
        ]);

        $file = $request->file('file');

        try {
            $item = $this->sharepoint->upload(
                $validated['folder'] ?? null,
                $file->getClientOriginalName(),
                file_get_contents($file->getRealPath())
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Uploaded {$item['name']}.");
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
}
