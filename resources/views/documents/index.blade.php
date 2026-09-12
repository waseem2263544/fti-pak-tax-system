@extends('layouts.app')
@section('title', 'Documents')
@section('page-title', 'Documents')

@section('content')
@php
    $officeExt = ['docx','doc','xlsx','xls','pptx','ppt'];
    $icon = function ($item) use ($officeExt) {
        if (isset($item['folder'])) return ['bi-folder-fill', '#f0ad4e'];
        $ext = strtolower(pathinfo($item['name'] ?? '', PATHINFO_EXTENSION));
        return match (true) {
            in_array($ext, ['docx','doc'])  => ['bi-file-earmark-word-fill', '#2b579a'],
            in_array($ext, ['xlsx','xls'])  => ['bi-file-earmark-excel-fill', '#217346'],
            in_array($ext, ['pptx','ppt'])  => ['bi-file-earmark-ppt-fill', '#d24726'],
            $ext === 'pdf'                  => ['bi-file-earmark-pdf-fill', '#d9534f'],
            in_array($ext, ['png','jpg','jpeg','gif']) => ['bi-file-earmark-image-fill', '#6f42c1'],
            default                         => ['bi-file-earmark-fill', '#8a94a6'],
        };
    };
    $isOffice = fn($item) => !isset($item['folder'])
        && in_array(strtolower(pathinfo($item['name'] ?? '', PATHINFO_EXTENSION)), $officeExt);
@endphp

@if($error)
<div class="alert alert-danger d-flex align-items-start">
    <i class="bi bi-exclamation-octagon me-2 mt-1"></i>
    <div>{{ $error }}</div>
</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <nav style="font-size: 0.9rem;">
        <a href="{{ route('documents.index') }}" class="text-decoration-none">
            <i class="bi bi-hdd-stack me-1"></i> Documents
        </a>
        @foreach($breadcrumb as $crumb)
            <span class="text-muted mx-1">/</span>
            @if($loop->last)
                <span class="fw-semibold">{{ $crumb['name'] }}</span>
            @else
                <a href="{{ route('documents.index', ['folder' => $crumb['id']]) }}" class="text-decoration-none">{{ $crumb['name'] }}</a>
            @endif
        @endforeach
    </nav>

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
                            <span class="text-muted ms-1" style="font-size: 0.76rem;">
                                {{ $item['folder']['childCount'] ?? 0 }} items
                            </span>
                        @else
                            <a href="{{ $item['webUrl'] }}" target="_blank" class="text-decoration-none">{{ $item['name'] }}</a>
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
                    {{ $error ? 'Could not read this folder.' : 'This folder is empty.' }}
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
