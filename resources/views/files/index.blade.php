@extends('layouts.app')
@section('title', 'File Management')
@section('page-title', 'File Management')

@section('styles')
<style>
    .fm-tab { padding: 12px 24px; font-size: 0.88rem; font-weight: 600; border: none; background: none; color: #9ca3af; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; text-decoration: none; display: inline-block; }
    .fm-tab:hover { color: var(--primary); }
    .fm-tab.active { color: var(--primary); border-bottom-color: var(--accent); }
    .fm-count { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 22px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; margin-left: 6px; padding: 0 6px; }
</style>
@endsection

@section('content')
<!-- Tabs -->
<div class="card mb-4">
    <div class="d-flex" style="border-bottom: 1px solid #f0f2f5; padding: 0 20px;">
        <a href="{{ route('files.index', ['tab' => 'files']) }}" class="fm-tab {{ $tab == 'files' ? 'active' : '' }}">
            <i class="bi bi-folder2 me-1"></i> File Numbers
            <span class="fm-count" style="background: {{ $tab == 'files' ? 'var(--accent-glow)' : '#f3f4f6' }}; color: {{ $tab == 'files' ? '#5c6300' : '#9ca3af' }};">{{ $fileNumbers->total() }}</span>
        </a>
        <a href="{{ route('files.index', ['tab' => 'letters']) }}" class="fm-tab {{ $tab == 'letters' ? 'active' : '' }}">
            <i class="bi bi-envelope-paper me-1"></i> Letter Numbers
            <span class="fm-count" style="background: {{ $tab == 'letters' ? 'var(--accent-glow)' : '#f3f4f6' }}; color: {{ $tab == 'letters' ? '#5c6300' : '#9ca3af' }};">{{ $letterNumbers->total() }}</span>
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
                <div class="col-md-4">
                    <label class="form-label">Client Name</label>
                    <select name="client_id" class="form-select" required>
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Optional description...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg me-1"></i>Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

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
                        <span style="font-weight: 800; font-size: 1rem; color: var(--primary); background: rgba(48,58,80,0.06); padding: 4px 12px; border-radius: 6px;">{{ $file->file_no }}</span>
                    </td>
                    <td>
                        <a href="{{ route('clients.show', $file->client) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ $file->client->name }}</a>
                    </td>
                    <td style="color: #6b7280; font-size: 0.85rem;">{{ $file->description ?: '-' }}</td>
                    <td style="color: #9ca3af; font-size: 0.82rem;">{{ $file->created_at->format('M d, Y') }}</td>
                    <td class="text-end">
                        <form action="{{ route('files.destroy-file', $file) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this file number?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-folder2" style="font-size: 2.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No file numbers yet. Add your first one above.
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
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $letter->date->format('M d, Y') }}</td>
                    <td>
                        <span style="font-weight: 700; font-family: monospace; font-size: 0.9rem; color: var(--primary); background: var(--accent-glow); padding: 4px 12px; border-radius: 6px;">{{ $letter->reference }}</span>
                    </td>
                    <td>
                        <a href="{{ route('clients.show', $letter->client) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ $letter->client->name }}</a>
                    </td>
                    <td style="font-size: 0.85rem; color: #4b5563;">{{ $letter->description }}</td>
                    <td class="text-end">
                        <form action="{{ route('files.destroy-letter', $letter) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this letter number?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5" style="color: #9ca3af;">
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
