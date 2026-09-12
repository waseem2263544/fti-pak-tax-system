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
                    <select name="client_id" class="form-select party-select">
                        <option value="">Not a client on file</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 party-name d-none">
                    <label class="form-label">Name</label>
                    <input type="text" name="client_name" class="form-control" value="{{ old('client_name') }}" placeholder="Name on the file">
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
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" title="Edit"
                                onclick='editFile(@json($file->id), @json($file->file_no), @json($file->client_id), @json($file->client_name), @json($file->description))'><i class="bi bi-pencil"></i></button>
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
                <div class="col-md-2">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select party-select">
                        <option value="">Not a client on file</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 party-name d-none">
                    <label class="form-label">Name</label>
                    <input type="text" name="client_name" class="form-control" value="{{ old('client_name') }}" placeholder="Name on the letter">
                </div>
                <div class="col-md-2">
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

<!-- Search -->
<form method="GET" action="{{ route('files.index') }}" class="d-flex gap-2 mb-3">
    <input type="hidden" name="tab" value="letters">
    <input type="search" name="q" value="{{ $search ?? '' }}" class="form-control form-control-sm"
           placeholder="Search by reference, name, or description…" style="max-width: 420px;">
    <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    @if(($search ?? '') !== '')
        <a href="{{ route('files.index', ['tab' => 'letters']) }}" class="btn btn-outline-primary btn-sm" title="Clear"><i class="bi bi-x-lg"></i></a>
    @endif
</form>

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
                    <td style="font-size: 0.85rem; color: var(--n-500); white-space: nowrap;">
                        @if($letter->date)
                            {{ $letter->date->format('M d, Y') }}
                        @elseif($letter->raw_date)
                            {{-- Kept as typed: the sheet recorded a date no reading could make sense of. --}}
                            <span class="text-muted" title="Recorded in the register as “{{ $letter->raw_date }}”, which is not a readable date">{{ $letter->raw_date }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span style="font-weight: 700; font-family: monospace; font-size: 0.9rem; color: var(--primary); background: var(--accent-glow); padding: 4px 12px; border-radius: 6px;">{{ $letter->reference }}</span>
                    </td>
                    <td>
                        @if($letter->client)
                            <a href="{{ route('clients.show', $letter->client) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ $letter->client->name }}</a>
                        @else
                            <span style="font-weight: 600;">{{ $letter->client_name ?: '—' }}</span>
                            <span class="badge bg-secondary ms-1" style="font-size: 0.62rem;" title="Not linked to a client record">unlinked</span>
                        @endif
                    </td>
                    <td style="font-size: 0.85rem; color: var(--n-600);">{{ $letter->description }}</td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" title="Edit"
                                onclick='editLetter(@json($letter->id), @json($letter->reference), @json(optional($letter->date)->format("Y-m-d")), @json($letter->client_id), @json($letter->client_name), @json($letter->description))'><i class="bi bi-pencil"></i></button>
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

{{-- ═══ EDIT DIALOGS ═══ --}}
<div class="modal fade" id="editFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="editFileForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit file number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-4">
                        <label class="form-label" for="ef_no">File No.</label>
                        <input type="number" min="1" name="file_no" id="ef_no" class="form-control" required>
                    </div>
                    <div class="col-8">
                        <label class="form-label" for="ef_client">Client</label>
                        <select name="client_id" id="ef_client" class="form-select party-select">
                            <option value="">Not a client on file</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 party-name d-none">
                        <label class="form-label" for="ef_name">Name</label>
                        <input type="text" name="client_name" id="ef_name" class="form-control" placeholder="Name on the file">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="ef_desc">Description</label>
                        <input type="text" name="description" id="ef_desc" class="form-control" placeholder="What the file is about">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editLetterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" id="editLetterForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit letter number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-7">
                        <label class="form-label" for="el_ref">Reference</label>
                        <input type="text" name="reference" id="el_ref" class="form-control"
                               style="font-family: monospace; font-weight: 600;" required>
                        <div class="form-text">Changing this re-derives the number and year the list sorts by.</div>
                    </div>
                    <div class="col-5">
                        <label class="form-label" for="el_date">Date</label>
                        <input type="date" name="date" id="el_date" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="el_client">Client</label>
                        <select name="client_id" id="el_client" class="form-select party-select">
                            <option value="">Not a client on file</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 party-name d-none">
                        <label class="form-label" for="el_name">Name</label>
                        <input type="text" name="client_name" id="el_name" class="form-control" placeholder="Name on the letter">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="el_desc">Document description</label>
                        <input type="text" name="description" id="el_desc" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
(function () {
    // A typed name only makes sense when the row is not tied to a client, so
    // the field appears only then rather than sitting there contradicting the
    // dropdown.
    function sync(select) {
        var group = select.closest('.row') || select.closest('form');
        if (!group) return;
        var field = group.querySelector('.party-name');
        if (!field) return;
        var free = select.value === '';
        field.classList.toggle('d-none', !free);
        var input = field.querySelector('input');
        if (input && !free) { input.value = ''; }
    }

    document.querySelectorAll('.party-select').forEach(function (sel) {
        sync(sel);
        sel.addEventListener('change', function () { sync(sel); });
    });

    var fileModal   = new bootstrap.Modal(document.getElementById('editFileModal'));
    var letterModal = new bootstrap.Modal(document.getElementById('editLetterModal'));

    window.editFile = function (id, fileNo, clientId, clientName, description) {
        document.getElementById('editFileForm').action = '{{ url('file-management/files') }}/' + id;
        document.getElementById('ef_no').value     = fileNo || '';
        document.getElementById('ef_client').value = clientId || '';
        document.getElementById('ef_name').value   = clientName || '';
        document.getElementById('ef_desc').value   = description || '';
        sync(document.getElementById('ef_client'));
        // sync() clears the name when a client is set; restore it for the
        // unlinked case it just hid and re-showed.
        if (!clientId) { document.getElementById('ef_name').value = clientName || ''; }
        fileModal.show();
    };

    window.editLetter = function (id, reference, dateIso, clientId, clientName, description) {
        document.getElementById('editLetterForm').action = '{{ url('file-management/letters') }}/' + id;
        document.getElementById('el_ref').value    = reference || '';
        document.getElementById('el_date').value   = dateIso || '';
        document.getElementById('el_client').value = clientId || '';
        document.getElementById('el_name').value   = clientName || '';
        document.getElementById('el_desc').value   = description || '';
        sync(document.getElementById('el_client'));
        if (!clientId) { document.getElementById('el_name').value = clientName || ''; }
        letterModal.show();
    };
})();
</script>
@endsection
