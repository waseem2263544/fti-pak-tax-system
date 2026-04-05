<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\MicrosoftEmailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ClientDocumentController extends Controller
{
    private $sharepointBasePath = '/sites/FairTaxInternational723/Shared Documents/Operations/3. Clients';

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'linked');

        $linked = Client::whereNotNull('folder_link')
            ->where('folder_link', '!=', '')
            ->orderBy('name')->get();

        $notLinked = Client::where(function ($q) {
            $q->whereNull('folder_link')->orWhere('folder_link', '');
        })->orderBy('name')->get();

        $sharepointFolders = [];
        $unlinked = collect();

        if ($tab === 'sharepoint') {
            $sharepointFolders = $this->fetchSharePointFolders();
            if ($sharepointFolders !== null) {
                // Find folders that don't match any client
                $clientNames = Client::pluck('name')->map(fn($n) => strtolower(trim($n)))->toArray();
                $linkedPaths = Client::whereNotNull('folder_link')->pluck('folder_link')
                    ->map(fn($l) => strtolower(basename(trim($l))))->toArray();

                $unlinked = collect($sharepointFolders)->filter(function ($folder) use ($clientNames, $linkedPaths) {
                    $folderLower = strtolower($folder['name']);
                    // Check if any client name matches this folder
                    foreach ($clientNames as $name) {
                        if (similar_text($folderLower, $name, $pct) && $pct > 70) return false;
                        if (strpos($folderLower, $name) !== false) return false;
                        if (strpos($name, $folderLower) !== false) return false;
                    }
                    // Check linked paths
                    foreach ($linkedPaths as $path) {
                        if (strpos($path, $folderLower) !== false) return false;
                    }
                    return true;
                });
            }
        }

        $clients = Client::orderBy('name')->get();

        return view('client-documents.index', compact('tab', 'linked', 'notLinked', 'sharepointFolders', 'unlinked', 'clients'));
    }

    public function updateLink(Request $request, Client $client)
    {
        $request->validate(['folder_link' => 'required|string|max:500']);
        $client->update(['folder_link' => $request->folder_link]);
        return back()->with('success', "Folder link updated for {$client->name}");
    }

    public function linkFolder(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'folder_path' => 'required|string',
        ]);

        Client::where('id', $request->client_id)->update(['folder_link' => $request->folder_path]);
        return redirect()->route('client-documents.index', ['tab' => 'sharepoint'])
            ->with('success', 'Folder linked to client');
    }

    private function fetchSharePointFolders()
    {
        $settings = MicrosoftEmailSettings::first();
        if (!$settings) return null;

        // Auto-refresh token
        if ($settings->isTokenExpired()) {
            $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'client_id' => env('MICROSOFT_CLIENT_ID', ''),
                'client_secret' => env('MICROSOFT_CLIENT_SECRET', ''),
                'refresh_token' => $settings->refresh_token,
                'grant_type' => 'refresh_token',
                'scope' => 'openid profile email Mail.Read Sites.Read.All offline_access',
            ]);
            if ($response->successful()) {
                $data = $response->json();
                $settings->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $settings->refresh_token,
                    'token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
                ]);
                $settings->refresh();
            } else {
                return null;
            }
        }

        // Fetch folders from SharePoint
        $siteId = $this->getSiteId($settings);
        if (!$siteId) return null;

        $driveId = $this->getDriveId($settings, $siteId);
        if (!$driveId) return null;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->access_token,
        ])->get("https://graph.microsoft.com/v1.0/drives/{$driveId}/root:/Operations/3. Clients:/children", [
            '$filter' => "folder ne null",
            '$select' => 'name,webUrl,lastModifiedDateTime',
            '$top' => 200,
        ]);

        if (!$response->successful()) return null;

        $items = $response->json()['value'] ?? [];
        return collect($items)->map(fn($item) => [
            'name' => $item['name'],
            'url' => $item['webUrl'] ?? '',
            'modified' => isset($item['lastModifiedDateTime']) ? Carbon::parse($item['lastModifiedDateTime'])->diffForHumans() : '',
        ])->sortBy('name')->values()->toArray();
    }

    private function getSiteId($settings)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->access_token,
        ])->get('https://graph.microsoft.com/v1.0/sites?search=FairTaxInternational723');

        if (!$response->successful()) return null;
        $sites = $response->json()['value'] ?? [];
        return $sites[0]['id'] ?? null;
    }

    private function getDriveId($settings, $siteId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->access_token,
        ])->get("https://graph.microsoft.com/v1.0/sites/{$siteId}/drives");

        if (!$response->successful()) return null;
        $drives = $response->json()['value'] ?? [];
        foreach ($drives as $drive) {
            if (stripos($drive['name'] ?? '', 'Documents') !== false || stripos($drive['name'] ?? '', 'Shared') !== false) {
                return $drive['id'];
            }
        }
        return $drives[0]['id'] ?? null;
    }
}
