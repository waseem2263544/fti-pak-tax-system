@extends('layouts.app')
@section('title', 'Processes')
@section('page-title', 'Processes')

@section('content')
<!-- Pipeline Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card" style="border-left: 4px solid #9ca3af;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(156,163,175,0.1);"><i class="bi bi-inbox" style="color: #9ca3af;"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['intake'] }}</div>
                    <div class="stat-label">Intake</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(59,130,246,0.08);"><i class="bi bi-gear-wide-connected" style="color: #3b82f6;"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['in_progress'] }}</div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(245,158,11,0.08);"><i class="bi bi-search" style="color: #f59e0b;"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['review'] }}</div>
                    <div class="stat-label">Under Review</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card" style="border-left: 4px solid #10b981;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(16,185,129,0.08);"><i class="bi bi-check-circle" style="color: #10b981;"></i></div>
                <div>
                    <div class="stat-value">{{ $stats['completed'] }}</div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2">
        <a href="{{ route('processes.index') }}" class="btn btn-sm {{ !request('stage') ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
        <a href="{{ route('processes.index', ['stage' => 'intake']) }}" class="btn btn-sm {{ request('stage') == 'intake' ? 'btn-primary' : 'btn-outline-primary' }}">Intake</a>
        <a href="{{ route('processes.index', ['stage' => 'in_progress']) }}" class="btn btn-sm {{ request('stage') == 'in_progress' ? 'btn-primary' : 'btn-outline-primary' }}">In Progress</a>
        <a href="{{ route('processes.index', ['stage' => 'review']) }}" class="btn btn-sm {{ request('stage') == 'review' ? 'btn-primary' : 'btn-outline-primary' }}">Review</a>
        <a href="{{ route('processes.index', ['stage' => 'completed']) }}" class="btn btn-sm {{ request('stage') == 'completed' ? 'btn-primary' : 'btn-outline-primary' }}">Completed</a>
    </div>
    <a href="{{ route('processes.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1"></i> New Process</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Process</th>
                    <th>Client</th>
                    <th>Service</th>
                    <th>Stage</th>
                    <th>Assigned To</th>
                    <th>Due Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($processes as $process)
                <tr>
                    <td>
                        <a href="{{ route('processes.show', $process) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ $process->title }}</a>
                    </td>
                    <td>{{ $process->client->name }}</td>
                    <td><span class="badge" style="background: var(--accent-glow); color: #5c6300;">{{ $process->service->display_name }}</span></td>
                    <td>
                        @if($process->stage == 'intake')
                            <span class="badge" style="background: #f3f4f6; color: #6b7280;">Intake</span>
                        @elseif($process->stage == 'in_progress')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">In Progress</span>
                        @elseif($process->stage == 'review')
                            <span class="badge" style="background: #fef3c7; color: #92400e;">Review</span>
                        @else
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Completed</span>
                        @endif
                    </td>
                    <td>{{ $process->assignedTo->name ?? '-' }}</td>
                    <td>{{ $process->due_date ? $process->due_date->format('M d, Y') : '-' }}</td>
                    <td class="text-end">
                        <a href="{{ route('processes.edit', $process) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-5" style="color: #9ca3af;">No processes yet. <a href="{{ route('processes.create') }}" style="color: var(--primary); font-weight: 600;">Create your first process</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $processes->links() }}</div>
@endsection
