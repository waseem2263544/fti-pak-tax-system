@extends('layouts.app')
@section('title', 'Search Results')
@section('page-title', 'Search Results')

@section('content')
<div class="mb-4">
    <form method="GET" action="{{ route('search') }}">
        <div style="position: relative; max-width: 600px;">
            <i class="bi bi-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search clients, tasks, proceedings, notices..." style="padding-left: 44px; font-size: 1rem; padding: 14px 16px 14px 44px;" autofocus>
        </div>
    </form>
</div>

@if(strlen($q) >= 2)
<p style="color: #6b7280; font-size: 0.85rem; margin-bottom: 20px;">
    Found <strong>{{ $totalResults ?? 0 }}</strong> results for "<strong>{{ $q }}</strong>"
</p>

@if(isset($results['clients']) && $results['clients']->count())
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-people-fill me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">Clients ({{ $results['clients']->count() }})</span></div>
    <div class="p-0">
        @foreach($results['clients'] as $client)
        <a href="{{ route('clients.show', $client) }}" class="d-flex align-items-center gap-3 px-4 py-3 text-decoration-none" style="border-bottom: 1px solid #f5f6f8; color: var(--primary);">
            <div style="width: 36px; height: 36px; background: rgba(48,58,80,0.06); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.7rem; flex-shrink: 0;">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
            <div>
                <div style="font-weight: 600;">{{ $client->name }}</div>
                <div style="font-size: 0.75rem; color: #9ca3af;">{{ $client->email ?: $client->contact_no ?: $client->status }}</div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

@if(isset($results['tasks']) && $results['tasks']->count())
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-check2-square me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">Tasks ({{ $results['tasks']->count() }})</span></div>
    <div class="p-0">
        @foreach($results['tasks'] as $task)
        <a href="{{ route('tasks.show', $task) }}" class="d-flex justify-content-between align-items-center px-4 py-3 text-decoration-none" style="border-bottom: 1px solid #f5f6f8; color: var(--primary);">
            <div>
                <div style="font-weight: 600;">{{ $task->title }}</div>
                <div style="font-size: 0.75rem; color: #9ca3af;">{{ $task->client->name ?? '' }} {{ $task->due_date ? '· Due ' . $task->due_date->format('M d') : '' }}</div>
            </div>
            <span class="badge" style="background: {{ $task->status == 'completed' ? '#d1fae5' : ($task->status == 'overdue' ? '#fef2f2' : '#fef3c7') }}; color: {{ $task->status == 'completed' ? '#065f46' : ($task->status == 'overdue' ? '#dc2626' : '#92400e') }};">{{ ucfirst($task->status) }}</span>
        </a>
        @endforeach
    </div>
</div>
@endif

@if(isset($results['proceedings']) && $results['proceedings']->count())
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-bank2 me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">Proceedings ({{ $results['proceedings']->count() }})</span></div>
    <div class="p-0">
        @foreach($results['proceedings'] as $proc)
        <a href="{{ route('proceedings.show', $proc) }}" class="d-flex justify-content-between align-items-center px-4 py-3 text-decoration-none" style="border-bottom: 1px solid #f5f6f8; color: var(--primary);">
            <div>
                <div style="font-weight: 600;">{{ $proc->title }}</div>
                <div style="font-size: 0.75rem; color: #9ca3af;">{{ $proc->client->name ?? '' }} · {{ $proc->section ?? '' }} · {{ $proc->tax_year ?? '' }}</div>
            </div>
            <span class="badge" style="background: #dbeafe; color: #1e40af;">{{ str_replace('_', ' ', ucfirst($proc->stage)) }}</span>
        </a>
        @endforeach
    </div>
</div>
@endif

@if(isset($results['processes']) && $results['processes']->count())
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-arrow-repeat me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">Processes ({{ $results['processes']->count() }})</span></div>
    <div class="p-0">
        @foreach($results['processes'] as $proc)
        <a href="{{ route('processes.show', $proc) }}" class="d-flex justify-content-between align-items-center px-4 py-3 text-decoration-none" style="border-bottom: 1px solid #f5f6f8; color: var(--primary);">
            <div>
                <div style="font-weight: 600;">{{ $proc->title }}</div>
                <div style="font-size: 0.75rem; color: #9ca3af;">{{ $proc->client->name ?? '' }} · {{ $proc->service->display_name ?? '' }}</div>
            </div>
            <span class="badge" style="background: var(--accent-glow); color: #5c6300;">{{ ucfirst(str_replace('_', ' ', $proc->stage)) }}</span>
        </a>
        @endforeach
    </div>
</div>
@endif

@if(isset($results['notices']) && $results['notices']->count())
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-envelope-paper-fill me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">FBR Notifications ({{ $results['notices']->count() }})</span></div>
    <div class="p-0">
        @foreach($results['notices'] as $notice)
        <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f5f6f8;">
            <div>
                <div style="font-weight: 600; color: var(--primary);">{{ $notice->subject }}</div>
                <div style="font-size: 0.75rem; color: #9ca3af;">{{ $notice->client->name ?? 'Unassigned' }} · {{ $notice->notice_section }} · {{ $notice->tax_year }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if(isset($results['documents']) && $results['documents']->count())
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-folder2-open me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">Client Document Folders ({{ $results['documents']->count() }})</span></div>
    <div class="p-0">
        @foreach($results['documents'] as $client)
        <a href="{{ $client->folder_link }}" target="_blank" class="d-flex justify-content-between align-items-center px-4 py-3 text-decoration-none" style="border-bottom: 1px solid #f5f6f8; color: var(--primary);">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-folder-fill" style="color: #2563eb; font-size: 1.1rem;"></i>
                <div>
                    <div style="font-weight: 600;">{{ $client->name }}</div>
                    <div style="font-size: 0.72rem; color: #9ca3af;">{{ Str::limit($client->folder_link, 50) }}</div>
                </div>
            </div>
            <i class="bi bi-box-arrow-up-right" style="color: #9ca3af;"></i>
        </a>
        @endforeach
    </div>
</div>
@endif

@if(($totalResults ?? 0) === 0)
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-search" style="font-size: 2.5rem; color: #e5e7eb;"></i>
        <h5 class="mt-3" style="color: var(--primary);">No results found</h5>
        <p style="color: #9ca3af; font-size: 0.85rem;">Try a different search term</p>
    </div>
</div>
@endif

@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-search" style="font-size: 2.5rem; color: #e5e7eb;"></i>
        <p style="color: #9ca3af; font-size: 0.85rem; margin-top: 12px;">Type at least 2 characters to search</p>
    </div>
</div>
@endif
@endsection
