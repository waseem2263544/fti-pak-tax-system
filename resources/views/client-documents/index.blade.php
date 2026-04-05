@extends('layouts.app')
@section('title', 'Client Documents')
@section('page-title', 'Client Documents')

@section('styles')
<style>
    .doc-tab { padding: 12px 24px; font-size: 0.85rem; font-weight: 600; border: none; background: none; color: #9ca3af; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; text-decoration: none; display: inline-block; }
    .doc-tab:hover { color: var(--primary); }
    .doc-tab.active { color: var(--primary); border-bottom-color: var(--accent); }
    .doc-count { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 22px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; margin-left: 6px; padding: 0 8px; }
    .quick-link-form { display: none; }
    .quick-link-form.show { display: flex; }
</style>
@endsection

@section('content')
<!-- Tabs -->
<div class="card mb-4">
    <div class="d-flex" style="border-bottom: 1px solid #f0f2f5; padding: 0 20px;">
        <a href="{{ route('client-documents.index', ['tab' => 'linked']) }}" class="doc-tab {{ $tab == 'linked' ? 'active' : '' }}">
            <i class="bi bi-link-45deg me-1"></i>Linked
            <span class="doc-count" style="background: {{ $tab == 'linked' ? '#d1fae5' : '#f3f4f6' }}; color: {{ $tab == 'linked' ? '#065f46' : '#9ca3af' }};">{{ $linked->count() }}</span>
        </a>
        <a href="{{ route('client-documents.index', ['tab' => 'missing']) }}" class="doc-tab {{ $tab == 'missing' ? 'active' : '' }}">
            <i class="bi bi-folder-x me-1"></i>Missing Folders
            <span class="doc-count" style="background: {{ $tab == 'missing' ? '#fef3c7' : '#f3f4f6' }}; color: {{ $tab == 'missing' ? '#92400e' : '#9ca3af' }};">{{ $notLinked->count() }}</span>
        </a>
        <a href="{{ route('client-documents.index', ['tab' => 'sharepoint']) }}" class="doc-tab {{ $tab == 'sharepoint' ? 'active' : '' }}">
            <i class="bi bi-cloud me-1"></i>SharePoint Sync
        </a>
    </div>
</div>

@if($tab == 'linked')
{{-- ═══ LINKED CLIENTS ═══ --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Folder Link</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($linked as $client)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 32px; height: 32px; background: rgba(48,58,80,0.06); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.65rem; color: var(--primary);">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
                            <div>
                                <a href="{{ route('clients.show', $client) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.88rem;">{{ $client->name }}</a>
                                <div style="font-size: 0.72rem; color: #9ca3af;">{{ $client->status }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="{{ $client->sharePointUrl }}" target="_blank" style="font-size: 0.8rem; color: #2563eb; text-decoration: none; word-break: break-all;">
                            <i class="bi bi-folder2-open me-1"></i>{{ Str::limit($client->folder_link, 60) }}
                        </a>
                    </td>
                    <td class="text-end">
                        <a href="{{ $client->sharePointUrl }}" target="_blank" class="btn btn-sm btn-accent"><i class="bi bi-box-arrow-up-right me-1"></i>Open</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center py-5" style="color: #9ca3af;">No clients with linked folders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@elseif($tab == 'missing')
{{-- ═══ MISSING FOLDERS ═══ --}}
<div class="mb-3" style="font-size: 0.85rem; color: #6b7280;">
    These clients don't have a SharePoint folder link. Click "Add Link" to paste the folder path.
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Contact</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($notLinked as $client)
                <tr>
                    <td>
                        <a href="{{ route('clients.show', $client) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ $client->name }}</a>
                    </td>
                    <td><span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">{{ $client->status }}</span></td>
                    <td style="color: #6b7280; font-size: 0.85rem;">{{ $client->email ?: $client->contact_no ?: '-' }}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" onclick="showLinkForm({{ $client->id }})"><i class="bi bi-link-45deg me-1"></i>Add Link</button>
                        <form method="POST" action="{{ route('client-documents.update-link', $client) }}" class="quick-link-form mt-2" id="link-form-{{ $client->id }}">
                            @csrf
                            <input type="text" name="folder_link" class="form-control form-control-sm" placeholder="Paste SharePoint folder path..." style="min-width: 250px;" required>
                            <button type="submit" class="btn btn-sm btn-accent ms-2"><i class="bi bi-check-lg"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="hideLinkForm({{ $client->id }})"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-5" style="color: #9ca3af;">
                    <i class="bi bi-check-circle" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                    All clients have folder links!
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
{{-- ═══ SHAREPOINT SYNC ═══ --}}
@if($sharepointFolders === null || !is_array($sharepointFolders))
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-cloud-slash" style="font-size: 2.5rem; color: #e5e7eb;"></i>
        <h5 class="mt-3" style="color: var(--primary);">Could not connect to SharePoint</h5>
        <p style="color: #9ca3af; font-size: 0.85rem;">Make sure your Microsoft account is connected in Settings > Email Integration and has Sites.Read.All permission.</p>
        <a href="{{ route('settings.email') }}" class="btn btn-outline-primary btn-sm">Go to Settings</a>
    </div>
</div>
@else
<div class="mb-3 d-flex justify-content-between align-items-center">
    <span style="font-size: 0.85rem; color: #6b7280;">
        Found <strong>{{ count($sharepointFolders) }}</strong> folders in SharePoint &middot;
        <strong>{{ $unlinked->count() }}</strong> not linked to any client
    </span>
</div>

@if($unlinked->count())
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-exclamation-circle me-2" style="color: #f59e0b;"></i>
        <span style="font-weight: 700;">Unlinked SharePoint Folders ({{ $unlinked->count() }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Folder Name</th>
                    <th>Last Modified</th>
                    <th>Link to Client</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($unlinked as $folder)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-folder-fill" style="color: #f59e0b; font-size: 1.1rem;"></i>
                            <span style="font-weight: 600; color: var(--primary);">{{ $folder['name'] }}</span>
                        </div>
                    </td>
                    <td style="font-size: 0.82rem; color: #9ca3af;">{{ $folder['modified'] }}</td>
                    <td>
                        <form method="POST" action="{{ route('client-documents.link-folder') }}" class="d-flex gap-2 align-items-center">
                            @csrf
                            <input type="hidden" name="folder_path" value="{{ $folder['url'] ?: $folder['name'] }}">
                            <select name="client_id" class="form-select form-select-sm" style="min-width: 200px;" required>
                                <option value="">Select client...</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-accent"><i class="bi bi-link-45deg"></i></button>
                        </form>
                    </td>
                    <td class="text-end">
                        @if($folder['url'])
                        <a href="{{ $folder['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <i class="bi bi-cloud-check me-2" style="color: #10b981;"></i>
        <span style="font-weight: 700;">All SharePoint Folders ({{ count($sharepointFolders) }})</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Folder Name</th><th>Last Modified</th><th></th></tr></thead>
            <tbody>
                @foreach($sharepointFolders as $folder)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-folder-fill" style="color: #2563eb; font-size: 1rem;"></i>
                            <span style="font-weight: 500; font-size: 0.88rem;">{{ $folder['name'] }}</span>
                        </div>
                    </td>
                    <td style="font-size: 0.82rem; color: #9ca3af;">{{ $folder['modified'] }}</td>
                    <td class="text-end">
                        @if($folder['url'])
                        <a href="{{ $folder['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i></a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endif
@endsection

@section('scripts')
<script>
function showLinkForm(id) {
    document.getElementById('link-form-' + id).classList.add('show');
}
function hideLinkForm(id) {
    document.getElementById('link-form-' + id).classList.remove('show');
}
</script>
@endsection
