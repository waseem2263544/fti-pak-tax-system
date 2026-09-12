@extends('layouts.app')
@section('title', 'Documents')
@section('page-title', 'Documents')

@section('content')
@php
    use App\Http\Controllers\DocumentController;

    $typeStyle = [
        'folder' => ['bi-folder-fill',              '#f0ad4e'],
        'word'   => ['bi-file-earmark-word-fill',   '#2b579a'],
        'excel'  => ['bi-file-earmark-excel-fill',  '#217346'],
        'pdf'    => ['bi-file-earmark-pdf-fill',    '#d9534f'],
        'image'  => ['bi-file-earmark-image-fill',  '#6f42c1'],
        'other'  => ['bi-file-earmark-fill',        '#8a94a6'],
    ];

    $icon = fn($item) => $typeStyle[$item['_type'] ?? 'other'] ?? $typeStyle['other'];

    // Word and Excel open in Office for the web; everything else just opens.
    $isOffice = fn($item) => in_array($item['_type'] ?? '', ['word', 'excel'], true);

    $keep = fn(array $extra) => array_filter(
        array_merge(['folder' => $folder, 'q' => $search ?: null, 'type' => $type], $extra),
        fn($v) => $v !== null && $v !== ''
    );
@endphp

@if($error)
<div class="alert alert-danger d-flex align-items-start">
    <i class="bi bi-exclamation-octagon me-2 mt-1"></i>
    <div>{{ $error }}</div>
</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <nav style="font-size: 0.9rem;">
        @if(($search ?? '') !== '')
            <span class="fw-semibold">{{ $items->count() }} result{{ $items->count() === 1 ? '' : 's' }}</span>
            <span class="text-muted">for &ldquo;{{ $search }}&rdquo;</span>
        @else
        <a href="{{ route('documents.index') }}" class="text-decoration-none">
            <i class="bi bi-folder2-open me-1"></i> {{ config('services.sharepoint.root_label', 'Documents') }}
        </a>
        @foreach($breadcrumb as $crumb)
            <span class="text-muted mx-1">/</span>
            @if($loop->last)
                <span class="fw-semibold">{{ $crumb['name'] }}</span>
            @else
                <a href="{{ route('documents.index', ['folder' => $crumb['id']]) }}" class="text-decoration-none">{{ $crumb['name'] }}</a>
            @endif
        @endforeach
        @endif
    </nav>

    <form method="GET" class="d-flex gap-2 position-relative" id="searchForm"
          style="flex: 1; max-width: 460px; margin: 0 16px;" autocomplete="off">
        <input type="hidden" name="folder" value="{{ $folder }}">
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="position-relative flex-grow-1">
            <input type="search" name="q" id="searchInput" class="form-control form-control-sm"
                   value="{{ $search ?? '' }}" placeholder="Search files and folders…"
                   role="combobox" aria-expanded="false" aria-controls="suggestBox" aria-autocomplete="list">
            <div id="suggestBox" class="card shadow position-absolute w-100 d-none"
                 style="top: 100%; left: 0; z-index: 1050; margin-top: 4px; max-height: 380px; overflow-y: auto;"
                 role="listbox"></div>
        </div>
        <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
        @if(($search ?? '') !== '')
            <a href="{{ route('documents.index') }}" class="btn btn-outline-primary btn-sm" title="Clear"><i class="bi bi-x-lg"></i></a>
        @endif
    </form>

    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#folderModal">
            <i class="bi bi-folder-plus me-1"></i> New folder
        </button>
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="bi bi-upload me-1"></i> Upload
        </button>
        <button class="btn btn-accent btn-sm" onclick="newOffice('docx')">
            <i class="bi bi-file-earmark-word me-1"></i> New Word
        </button>
        <button class="btn btn-accent btn-sm" onclick="newOffice('xlsx')">
            <i class="bi bi-file-earmark-excel me-1"></i> New Excel
        </button>
    </div>
</div>

@if(!empty($typeCounts))
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <span class="text-muted" style="font-size: 0.8rem;">Show:</span>
    <a href="{{ route('documents.index', $keep(['type' => null])) }}"
       class="btn btn-sm btn-{{ $type ? 'outline-primary' : 'primary' }}">
        All <span class="opacity-75">{{ array_sum($typeCounts) }}</span>
    </a>
    @foreach(DocumentController::TYPES as $key => $label)
        @php [$ic, $colour] = $typeStyle[$key]; @endphp
        <a href="{{ route('documents.index', $keep(['type' => $key])) }}"
           class="btn btn-sm btn-{{ $type === $key ? 'primary' : 'outline-primary' }} {{ ($typeCounts[$key] ?? 0) === 0 ? 'disabled opacity-50' : '' }}">
            <i class="bi {{ $ic }} me-1" @if($type !== $key) style="color: {{ $colour }};" @endif></i>
            {{ $label }} <span class="opacity-75">{{ $typeCounts[$key] ?? 0 }}</span>
        </a>
    @endforeach
</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Modified</th>
                    <th>By</th>
                    <th class="text-end">Size</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                @php [$ic, $colour] = $icon($item); @endphp
                <tr>
                    <td>
                        <i class="bi {{ $ic }} me-2" style="color: {{ $colour }}; font-size: 1.1rem;"></i>
                        @if(isset($item['folder']))
                            <a href="{{ route('documents.index', ['folder' => $item['id']]) }}" class="text-decoration-none fw-semibold">
                                {{ $item['name'] }}
                            </a>
                            @isset($item['folder']['childCount'])
                                <span class="text-muted ms-1" style="font-size: 0.76rem;">
                                    {{ $item['folder']['childCount'] }} {{ $item['folder']['childCount'] === 1 ? 'item' : 'items' }}
                                </span>
                            @endisset
                        @else
                            <a href="{{ $item['webUrl'] }}" target="_blank" class="text-decoration-none">{{ $item['name'] }}</a>
                        @endif
                        @if(($search ?? '') !== '' && !empty($item['_path']))
                            <div style="font-size: 0.75rem;">
                                <a href="{{ route('documents.index', ['folder' => $item['parentReference']['id'] ?? null]) }}"
                                   class="text-muted text-decoration-none">
                                    <i class="bi bi-folder me-1"></i>{{ $item['_path'] }}
                                </a>
                            </div>
                        @endif
                    </td>
                    <td style="font-size: 0.82rem;">
                        {{ isset($item['lastModifiedDateTime']) ? \Illuminate\Support\Carbon::parse($item['lastModifiedDateTime'])->format('d M Y, H:i') : '—' }}
                    </td>
                    <td style="font-size: 0.82rem;">
                        {{ $item['lastModifiedBy']['user']['displayName'] ?? '—' }}
                    </td>
                    <td class="text-end" style="font-size: 0.82rem;">
                        {{ isset($item['folder']) ? '—' : number_format(($item['size'] ?? 0) / 1024, 0) . ' KB' }}
                    </td>
                    <td class="text-end text-nowrap">
                        @unless(isset($item['folder']))
                            @if($isOffice($item))
                                <a href="{{ $item['webUrl'] }}" target="_blank" class="btn btn-sm btn-primary" title="Edit in Office for the web">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </a>
                            @else
                                <a href="{{ $item['webUrl'] }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Open">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            @endif
                            <a href="{{ route('documents.download', ['id' => $item['id'], 'name' => $item['name']]) }}"
                               class="btn btn-sm btn-outline-primary" title="Download"><i class="bi bi-download"></i></a>
                        @endunless
                        <button class="btn btn-sm btn-outline-primary" title="Rename"
                                onclick='renameItem(@json($item["id"]), @json($item["name"]))'><i class="bi bi-input-cursor-text"></i></button>
                        <form method="POST" action="{{ route('documents.destroy') }}" class="d-inline"
                              onsubmit="return confirm('Delete {{ addslashes($item['name']) }} from SharePoint?')">
                            @csrf
                            <input type="hidden" name="id" value="{{ $item['id'] }}">
                            <input type="hidden" name="folder" value="{{ $folder }}">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-5">
                    @if($error)
                        Could not read this folder.
                    @elseif(($search ?? '') !== '')
                        Nothing matched &ldquo;{{ $search }}&rdquo;@if($type) in {{ strtolower(DocumentController::TYPES[$type]) }}@endif.
                    @elseif($type)
                        No {{ strtolower(DocumentController::TYPES[$type]) }} in this folder.
                    @else
                        This folder is empty.
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted mt-3" style="font-size: 0.82rem;">
    <i class="bi bi-info-circle me-1"></i>
    Files live in SharePoint. <strong>Edit</strong> opens Office for the web in a new tab under your own
    Microsoft sign-in, so edits are saved straight back and several people can work on the same file at once.
</p>

{{-- New Office document --}}
<div class="modal fade" id="officeModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('documents.create-office') }}">
            @csrf
            <input type="hidden" name="folder" value="{{ $folder }}">
            <input type="hidden" name="type" id="officeType">
            <div class="modal-header"><h5 class="modal-title" id="officeTitle">New document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">File name</label>
                <input type="text" name="name" id="officeName" class="form-control" required>
                <div class="form-text">Created here, then opened in Office for the web.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create &amp; open</button>
            </div>
        </form>
    </div>
</div>

{{-- New folder --}}
<div class="modal fade" id="folderModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('documents.create-folder') }}">
            @csrf
            <input type="hidden" name="folder" value="{{ $folder }}">
            <div class="modal-header"><h5 class="modal-title">New folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Folder name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

{{-- Upload --}}
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('documents.upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="folder" value="{{ $folder }}">
            <div class="modal-header"><h5 class="modal-title">Upload a file</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="file" name="file" class="form-control" required>
                <div class="form-text">Up to 4 MB. Larger files are best added in SharePoint directly.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

{{-- Rename --}}
<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('documents.rename') }}">
            @csrf
            <input type="hidden" name="id" id="renameId">
            <div class="modal-header"><h5 class="modal-title">Rename</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="text" name="name" id="renameName" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Rename</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ── Search suggestions ──────────────────────────────────────────────────────
(function () {
    const input = document.getElementById('searchInput');
    const box = document.getElementById('suggestBox');

    if (!input || !box) return;

    const ICONS = {
        folder: ['bi-folder-fill', '#f0ad4e'],
        word:   ['bi-file-earmark-word-fill', '#2b579a'],
        excel:  ['bi-file-earmark-excel-fill', '#217346'],
        pdf:    ['bi-file-earmark-pdf-fill', '#d9534f'],
        image:  ['bi-file-earmark-image-fill', '#6f42c1'],
        other:  ['bi-file-earmark-fill', '#8a94a6'],
    };

    let timer = null;
    let pending = null;
    let items = [];
    let active = -1;

    const escape = s => String(s ?? '').replace(/[&<>"']/g, c =>
        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    // Show where the term matched, since Graph also searches file contents.
    function highlight(name, term) {
        const safe = escape(name);
        if (!term) return safe;
        const i = safe.toLowerCase().indexOf(term.toLowerCase());
        if (i < 0) return safe;
        return safe.slice(0, i) + '<strong>' + safe.slice(i, i + term.length) + '</strong>' + safe.slice(i + term.length);
    }

    function close() {
        box.classList.add('d-none');
        input.setAttribute('aria-expanded', 'false');
        active = -1;
    }

    function render(term) {
        if (!items.length) {
            box.innerHTML = '<div class="px-3 py-2 text-muted" style="font-size:0.84rem;">No matches</div>';
        } else {
            box.innerHTML = items.map((it, i) => {
                const [ic, colour] = ICONS[it.type] || ICONS.other;
                return `<a href="${escape(it.folder
                            ? '{{ route('documents.index') }}?folder=' + encodeURIComponent(it.id)
                            : it.url)}"
                           ${it.folder ? '' : 'target="_blank" rel="noopener"'}
                           class="d-block px-3 py-2 text-decoration-none suggest-item ${i === active ? 'bg-light' : ''}"
                           data-index="${i}" role="option">
                          <i class="bi ${ic} me-2" style="color:${colour};"></i>
                          <span style="font-size:0.88rem;">${highlight(it.name, term)}</span>
                          ${it.path ? `<div class="text-muted" style="font-size:0.73rem; padding-left:1.6rem;">
                              <i class="bi bi-folder me-1"></i>${escape(it.path)}</div>` : ''}
                        </a>`;
            }).join('');
        }

        box.classList.remove('d-none');
        input.setAttribute('aria-expanded', 'true');
    }

    function move(step) {
        if (box.classList.contains('d-none') || !items.length) return;
        active = (active + step + items.length) % items.length;
        render(input.value.trim());
        box.querySelector(`[data-index="${active}"]`)?.scrollIntoView({block: 'nearest'});
    }

    input.addEventListener('input', () => {
        const term = input.value.trim();
        clearTimeout(timer);

        if (term.length < 2) { close(); return; }

        // Debounced: this is a Graph round trip per call.
        timer = setTimeout(async () => {
            if (pending) pending.abort();
            pending = new AbortController();

            try {
                const res = await fetch('{{ route('documents.suggest') }}?q=' + encodeURIComponent(term),
                    {signal: pending.signal, headers: {'Accept': 'application/json'}});
                if (!res.ok) return;
                const data = await res.json();
                items = data.items || [];
                active = -1;
                render(term);
            } catch (e) {
                // aborted or offline — leave whatever is on screen
            }
        }, 300);
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
        else if (e.key === 'Escape') { close(); }
        else if (e.key === 'Enter' && active >= 0) {
            e.preventDefault();
            box.querySelector(`[data-index="${active}"]`)?.click();
        }
    });

    document.addEventListener('click', e => {
        if (!box.contains(e.target) && e.target !== input) close();
    });

    input.addEventListener('focus', () => { if (items.length) render(input.value.trim()); });
})();

const officeModal = new bootstrap.Modal(document.getElementById('officeModal'));
const renameModal = new bootstrap.Modal(document.getElementById('renameModal'));

function newOffice(type) {
    document.getElementById('officeType').value = type;
    document.getElementById('officeTitle').textContent = type === 'docx' ? 'New Word document' : 'New Excel workbook';
    document.getElementById('officeName').value = '';
    officeModal.show();
}

function renameItem(id, name) {
    document.getElementById('renameId').value = id;
    document.getElementById('renameName').value = name;
    renameModal.show();
}

// A document just created is opened straight away, so the user lands in Office.
@if(session('open_url'))
window.open(@json(session('open_url')), '_blank');
@endif
</script>
@endsection
