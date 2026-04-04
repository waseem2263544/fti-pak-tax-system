@extends('layouts.app')
@section('title', 'File Management')
@section('page-title', 'File Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
            <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 180px;">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                @endforeach
            </select>
            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 150px;">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <a href="{{ route('files.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-upload me-1"></i> Upload File</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Client</th>
                    <th>Category</th>
                    <th>Size</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($files as $file)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(48,58,80,0.06); display: flex; align-items: center; justify-content: center;">
                                @if(str_contains($file->mime_type ?? '', 'pdf'))
                                    <i class="bi bi-file-earmark-pdf" style="color: #dc2626; font-size: 1.1rem;"></i>
                                @elseif(str_contains($file->mime_type ?? '', 'image'))
                                    <i class="bi bi-file-earmark-image" style="color: #2563eb; font-size: 1.1rem;"></i>
                                @elseif(str_contains($file->mime_type ?? '', 'spreadsheet') || str_contains($file->mime_type ?? '', 'excel'))
                                    <i class="bi bi-file-earmark-excel" style="color: #16a34a; font-size: 1.1rem;"></i>
                                @elseif(str_contains($file->mime_type ?? '', 'word') || str_contains($file->mime_type ?? '', 'document'))
                                    <i class="bi bi-file-earmark-word" style="color: #2563eb; font-size: 1.1rem;"></i>
                                @else
                                    <i class="bi bi-file-earmark" style="color: #6b7280; font-size: 1.1rem;"></i>
                                @endif
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--primary);">{{ $file->original_name }}</div>
                                @if($file->notes) <div style="font-size: 0.75rem; color: #9ca3af;">{{ Str::limit($file->notes, 40) }}</div> @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $file->client->name }}</td>
                    <td>
                        @if($file->category)
                            <span class="badge" style="background: var(--accent-glow); color: #5c6300;">{{ $file->category }}</span>
                        @else
                            <span style="color: #d1d5db;">-</span>
                        @endif
                    </td>
                    <td style="color: #6b7280; font-size: 0.82rem;">{{ $file->sizeFormatted() }}</td>
                    <td>{{ $file->uploadedBy->name }}</td>
                    <td style="color: #6b7280; font-size: 0.82rem;">{{ $file->created_at->format('M d, Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('files.download', $file) }}" class="btn btn-sm btn-outline-primary" title="Download"><i class="bi bi-download"></i></a>
                        <form action="{{ route('files.destroy', $file) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this file?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-5" style="color: #9ca3af;">
                    <i class="bi bi-folder2-open" style="font-size: 2.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                    No files yet. <a href="{{ route('files.create') }}" style="color: var(--primary); font-weight: 600;">Upload your first file</a>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $files->links() }}</div>
@endsection
