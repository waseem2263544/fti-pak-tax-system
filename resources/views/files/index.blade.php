@extends('layouts.app')
@section('title', 'File Management')
@section('page-title', 'File Management')

@section('styles')
<style>
    .fm-tab { padding: 12px 24px; font-size: 0.88rem; font-weight: 600; border: none; background: none; color: var(--n-400); cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; text-decoration: none; display: inline-block; }
    .fm-tab:hover { color: var(--primary); }
    .fm-tab.active { color: var(--primary); border-bottom-color: var(--accent); }
    .fm-count { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 22px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; margin-left: 6px; padding: 0 6px; }
</style>
@endsection

@section('content')
<!-- Tabs -->
<div class="card mb-4">
    <div class="d-flex" style="border-bottom: 1px solid var(--n-100); padding: 0 20px;">
        <a href="{{ route('files.index', ['tab' => 'files']) }}" class="fm-tab {{ $tab == 'files' ? 'active' : '' }}">
            <i class="bi bi-folder2 me-1"></i> File Numbers
            <span class="fm-count" style="background: {{ $tab == 'files' ? 'var(--accent-glow)' : 'var(--n-50)' }}; color: {{ $tab == 'files' ? 'var(--accent-dark)' : 'var(--n-400)' }};">{{ $fileNumbers->total() }}</span>
        </a>
        <a href="{{ route('files.index', ['tab' => 'letters']) }}" class="fm-tab {{ $tab == 'letters' ? 'active' : '' }}">
            <i class="bi bi-envelope-paper me-1"></i> Letter Numbers
            <span class="fm-count" style="background: {{ $tab == 'letters' ? 'var(--accent-glow)' : 'var(--n-50)' }}; color: {{ $tab == 'letters' ? 'var(--accent-dark)' : 'var(--n-400)' }};">{{ $letterNumbers->total() }}</span>
        </a>
    </div>
</div>

@if($tab == 'files')
{{-- ═══ FILE NUMBERS TAB ═══ --}}

<!-- Add New File Number -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-plus-circle me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">Add New File Number</span></div>
    <div class="card-body" style="padding: 20px;">
        <form method="POST" action="{{ route('files.store-file') }}">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">File No.</label>
                    <input type="text" name="file_no" class="form-control" value="{{ $nextFileNo }}" placeholder="{{ $nextFileNo }}" style="font-weight: 700; font-size: 1.1rem; text-align: center; color: var(--primary);">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select">
                        <option value="">Not a client on file</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">…or a name</label>
                    <input type="text" name="client_name" class="form-control" value="{{ old('client_name') }}" placeholder="Name on the file">
                    <div class="form-text">Used only when no client is selected.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="What the file is about">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search -->
<form method="GET" action="{{ route('files.index') }}" class="d-flex gap-2 mb-3">
    <input type="hidden" name="tab" value="files">
    <input type="search" name="q" value="{{ $search ?? '' }}" class="form-control form-control-sm"
           placeholder="Search by file number, name, or description…" style="max-width: 420px;">
    <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    @if(($search ?? '') !== '')
        <a href="{{ route('files.index', ['tab' => 'files']) }}" class="btn btn-outline-primary btn-sm" title="Clear"><i class="bi bi-x-lg"></i></a>
    @endif
</form>

<!-- File Numbers List -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 100px;">File No.</th>
                    <th>Client Name</th>
                    <th>Description</th>
                    <th>Date Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($fileNumbers as $file)
                <tr>
                    <td>
                        <span style="font-weight: 800; font-size: 1rem; color: var(--primary); background: var(--n-100); padding: 4px 12px; border-radius: 6px;">{{ $file->file_no }}</span>
                    </td>
                    <td>
                        @if($file->client)
                            <a href="{{ route('clients.show', $file->client) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ $file->client->name }}</a>
                        @else
                            <span style="font-weight: 600;">{{ $file->client_name ?: '—' }}</span>
                            <span class="badge bg-secondary ms-1" style="font-size: 0.62rem;" title="Not linked to a client record">unlinked</span>
                        @endif
                    </td>
                    <td style="color: var(--n-500); font-size: 0.85rem;">{{ $file->description ?: '-' }}</td>
                    <td style="color: var(--n-400); font-size: 0.82rem;">{{ $file->created_at->format('M d, Y') }}</td>
                    <td class="text-end">
                        <form action="{{ route('files.destroy-file', $file) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this file number?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5" style="color: var(--n-400);">
                        <i class="bi bi-folder2" style="font-size: 2.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        @if(($search ?? '') !== '')
                            Nothing matches “{{ $search }}”.
                        @else
                            No file numbers yet. Add your first one above.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $fileNumbers->appends(['tab' => 'files'])->links() }}</div>

@else
{{-- ═══ LETTER NUMBERS TAB ═══ --}}

<!-- Add New Letter Number -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-plus-circle me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">Add New Letter Number</span></div>
    <div class="card-body" style="padding: 20px;">
        <form method="POST" action="{{ route('files.store-letter') }}">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control" value="{{ $nextLetterRef }}" placeholder="{{ $nextLetterRef }}" style="font-weight: 700; font-family: monospace; color: var(--primary);">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Client Name</label>
                    <select name="client_id" class="form-select" required>
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Document Description</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g. Reply to Notice u/s 122(9)" required>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Letter Numbers List -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Client Name</th>
                    <th>Document Description</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($letterNumbers as $letter)
                <tr>
                    <td style="font-size: 0.85rem; color: var(--n-500);">{{ $letter->date->format('M d, Y') }}</td>
                    <td>
                        <span style="font-weight: 700; font-family: monospace; font-size: 0.9rem; color: var(--primary); background: var(--accent-glow); padding: 4px 12px; border-radius: 6px;">{{ $letter->reference }}</span>
                    </td>
                    <td>
                        <a href="{{ route('clients.show', $letter->client) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ $letter->client->name }}</a>
                    </td>
                    <td style="font-size: 0.85rem; color: var(--n-600);">{{ $letter->description }}</td>
                    <td class="text-end">
                        <form action="{{ route('files.destroy-letter', $letter) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this letter number?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5" style="color: var(--n-400);">
                        <i class="bi bi-envelope-paper" style="font-size: 2.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No letter numbers yet. Add your first one above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $letterNumbers->appends(['tab' => 'letters'])->links() }}</div>

@endif
@endsection
