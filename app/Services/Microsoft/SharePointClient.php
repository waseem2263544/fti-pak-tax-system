<?php

namespace App\Services\Microsoft;

use App\Models\MicrosoftEmailSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin Microsoft Graph client for the SharePoint document library.
 *
 * Acts as the single connected Microsoft account, the same one the FBR notice
 * fetcher and the client folder listing already use. People editing a document
 * do so in their own browser session against Office for the web — the app only
 * ever hands them the link, so edits are attributed to the person, not to this
 * account.
 */
class SharePointClient
{
    private const GRAPH = 'https://graph.microsoft.com/v1.0';

    /** Writing needs these; without them Graph answers 403 on create and upload. */
    public const SCOPES = 'openid profile email Mail.Read Sites.Read.All Files.ReadWrite.All offline_access';

    private ?MicrosoftEmailSettings $settings = null;

    public function connected(): bool
    {
        return (bool) $this->settings();
    }

    private function settings(): ?MicrosoftEmailSettings
    {
        return $this->settings ??= MicrosoftEmailSettings::first();
    }

    /**
     * A usable access token, refreshed if the stored one has expired.
     */
    public function token(): ?string
    {
        $settings = $this->settings();

        if (!$settings) {
            return null;
        }

        if (!$settings->isTokenExpired()) {
            return $settings->access_token;
        }

        $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'client_id'     => env('MICROSOFT_CLIENT_ID', ''),
            'client_secret' => env('MICROSOFT_CLIENT_SECRET', ''),
            'refresh_token' => $settings->refresh_token,
            'grant_type'    => 'refresh_token',
            'scope'         => self::SCOPES,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        $settings->update([
            'access_token'     => $data['access_token'],
            'refresh_token'    => $data['refresh_token'] ?? $settings->refresh_token,
            'token_expires_at' => Carbon::now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $settings->fresh()->access_token;
    }

    private function request()
    {
        $token = $this->token();

        if (!$token) {
            throw new \RuntimeException('Microsoft account is not connected, or its token could not be refreshed.');
        }

        return Http::withToken($token)->acceptJson();
    }

    /** Site and drive ids are stable, so they are worth caching. */
    public function driveId(): string
    {
        return Cache::remember('sp_drive_id', now()->addDay(), function () {
            $site = $this->request()->get(self::GRAPH . '/sites', ['search' => config('services.sharepoint.site', 'FairTaxInternational723')]);

            $siteId = $site->json('value.0.id');

            if (!$siteId) {
                throw new \RuntimeException('Could not find the SharePoint site.');
            }

            $drives = $this->request()->get(self::GRAPH . "/sites/{$siteId}/drives");

            $driveId = $drives->json('value.0.id');

            if (!$driveId) {
                throw new \RuntimeException('The SharePoint site has no document library.');
            }

            return $driveId;
        });
    }

    /** Folder contents. Pass null for the library root. */
    public function children(?string $itemId = null): array
    {
        $drive = $this->driveId();
        $path = $itemId ? "/drives/{$drive}/items/{$itemId}/children" : "/drives/{$drive}/root/children";

        // No $select here on purpose: projecting fields makes Graph return the
        // folder facet without childCount, so every folder reported "0 items".
        // The default response carries everything this page uses.
        $response = $this->request()->get(self::GRAPH . $path, [
            '$top'     => 500,
            '$orderby' => 'name',
        ]);

        $this->guard($response);

        return $response->json('value', []);
    }

    /**
     * Search names and content, scoped to a folder when one is given.
     *
     * Graph's search is fuzzy and covers file contents as well as names, so a
     * hit may not have the term in its title — results carry their parent path
     * so the user can see where each one actually lives.
     */
    public function search(string $query, ?string $scopeId = null): array
    {
        $drive = $this->driveId();
        $q = str_replace("'", "''", $query);
        $base = $scopeId ? "/drives/{$drive}/items/{$scopeId}" : "/drives/{$drive}/root";

        $response = $this->request()->get(
            self::GRAPH . $base . "/search(q='" . rawurlencode($q) . "')",
            ['$top' => 200]
        );

        $this->guard($response);

        // Search returns a thinner item than a folder listing does: childCount is
        // present but always 0, and parentReference carries no path. Strip the
        // count so nothing downstream reports a folder as empty when it is not.
        return array_map(function ($item) {
            if (isset($item['folder'])) {
                unset($item['folder']['childCount']);
            }

            return $item;
        }, $response->json('value', []));
    }

    /**
     * Readable paths for a set of search results, resolved from their parent ids.
     *
     * Search omits parentReference.path, so the parent has to be fetched. Results
     * overwhelmingly share a handful of parents, so they are looked up once each
     * and capped — a search returning fifty folders costs one or two calls, not
     * fifty.
     */
    public function resolveParentPaths(array $items, int $maxLookups = 25): array
    {
        $ids = collect($items)
            ->pluck('parentReference.id')
            ->filter()
            ->unique()
            ->take($maxLookups);

        $paths = [];

        foreach ($ids as $id) {
            try {
                $parent = $this->item($id);
                $own = $this->relativePath($parent);
                $name = $parent['name'] ?? '';

                // relativePath gives where the PARENT sits; append its own name.
                $label = $own === config('services.sharepoint.root_label', 'Clients')
                    ? $name
                    : trim($own . '/' . $name, '/');

                $paths[$id] = $label ?: config('services.sharepoint.root_label', 'Clients');
            } catch (\Throwable) {
                // A parent we cannot read just means no path for those rows.
            }
        }

        return $paths;
    }

    public function item(string $itemId): array
    {
        $response = $this->request()->get(self::GRAPH . "/drives/{$this->driveId()}/items/{$itemId}");

        $this->guard($response);

        return $response->json();
    }

    /** The folder the browser treats as its root, if one is configured. */
    public function rootFolder(): ?string
    {
        return config('services.sharepoint.root_folder') ?: null;
    }

    /**
     * Walk up the parent chain so the browser can show where you are, stopping
     * at the configured root so the trail does not expose the whole library.
     */
    public function breadcrumb(?string $itemId): array
    {
        $root = $this->rootFolder();
        $trail = [];
        $guard = 0;

        while ($itemId && $guard++ < 20) {
            // The root is represented by the browser's own home link.
            if ($root && $itemId === $root) {
                break;
            }

            try {
                $item = $this->item($itemId);
            } catch (\Throwable) {
                break;
            }

            array_unshift($trail, ['id' => $item['id'], 'name' => $item['name']]);
            $itemId = $item['parentReference']['id'] ?? null;

            // The drive root has a parent with no id of its own.
            if (($item['parentReference']['path'] ?? '') === '/drive/root:') {
                break;
            }
        }

        return $trail;
    }

    public function createFolder(?string $parentId, string $name): array
    {
        $drive = $this->driveId();
        $path = $parentId ? "/drives/{$drive}/items/{$parentId}/children" : "/drives/{$drive}/root/children";

        $response = $this->request()->post(self::GRAPH . $path, [
            'name'                              => $name,
            'folder'                            => new \stdClass(),
            '@microsoft.graph.conflictBehavior' => 'rename',
        ]);

        $this->guard($response);

        return $response->json();
    }

    /**
     * Upload a file. Graph's simple upload tops out at 4 MB, which covers a new
     * blank document and most things people drag in.
     */
    public function upload(?string $parentId, string $name, string $contents): array
    {
        $drive = $this->driveId();
        $encoded = rawurlencode($name);
        $path = $parentId
            ? "/drives/{$drive}/items/{$parentId}:/{$encoded}:/content"
            : "/drives/{$drive}/root:/{$encoded}:/content";

        $response = Http::withToken($this->token())
            ->withBody($contents, 'application/octet-stream')
            ->put(self::GRAPH . $path . '?@microsoft.graph.conflictBehavior=rename');

        $this->guard($response);

        return $response->json();
    }

    public function download(string $itemId): string
    {
        $response = Http::withToken($this->token())
            ->get(self::GRAPH . "/drives/{$this->driveId()}/items/{$itemId}/content");

        $this->guard($response);

        return $response->body();
    }

    public function delete(string $itemId): void
    {
        $this->guard($this->request()->delete(self::GRAPH . "/drives/{$this->driveId()}/items/{$itemId}"));
    }

    public function rename(string $itemId, string $name): array
    {
        $response = $this->request()->patch(self::GRAPH . "/drives/{$this->driveId()}/items/{$itemId}", [
            'name' => $name,
        ]);

        $this->guard($response);

        return $response->json();
    }

    /**
     * Where an item sits, written relative to the browser's root.
     *
     * Graph gives the full drive path — /drives/{id}/root:/Operations/3. Clients/Acme —
     * which is noise. This trims it back to what the user recognises.
     */
    public function relativePath(array $item): string
    {
        $raw = $item['parentReference']['path'] ?? '';

        if ($raw === '') {
            return '';
        }

        // Everything after "root:" is the path within the library.
        $path = \Illuminate\Support\Str::after($raw, 'root:');
        $path = rawurldecode($path);

        $rootName = trim((string) config('services.sharepoint.root_path', 'Operations/3. Clients'), '/');

        if ($rootName !== '' && str_starts_with(ltrim($path, '/'), $rootName)) {
            $path = substr(ltrim($path, '/'), strlen($rootName));
        }

        $path = trim($path, '/');

        return $path === '' ? config('services.sharepoint.root_label', 'Clients') : $path;
    }

    /** Turn Graph's errors into something the user can act on. */
    private function guard($response): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('error.message') ?? $response->body();

        if ($response->status() === 403) {
            throw new \RuntimeException(
                'Microsoft refused the request. The connected account is probably missing the '
                . 'Files.ReadWrite.All permission — reconnect it under Settings → Email to grant it. '
                . '(' . $message . ')'
            );
        }

        if ($response->status() === 401) {
            throw new \RuntimeException('Microsoft rejected the token. Reconnect the account under Settings → Email.');
        }

        throw new \RuntimeException('Microsoft Graph error ' . $response->status() . ': ' . $message);
    }
}
