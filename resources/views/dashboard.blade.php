@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(48,58,80,0.08); display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                    <i class="bi bi-people-fill" style="font-size: 1.3rem; color: var(--primary);"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $totalClients }}</div>
                    <div class="stat-label">Clients</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(215,223,39,0.15); display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                    <i class="bi bi-briefcase-fill" style="font-size: 1.3rem; color: #8b9a00;"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $activeServices }}</div>
                    <div class="stat-label">Active Services</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245,158,11,0.1); display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                    <i class="bi bi-clock-fill" style="font-size: 1.3rem; color: #f59e0b;"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $pendingTasks }}</div>
                    <div class="stat-label">Pending Tasks</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(239,68,68,0.1); display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                    <i class="bi bi-envelope-exclamation-fill" style="font-size: 1.3rem; color: #ef4444;"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $newFbrNotices }}</div>
                    <div class="stat-label">New FBR Notices</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Tasks & FBR Notices -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-check2-square me-2" style="color: var(--accent);"></i>My Tasks</span>
                <a href="{{ route('tasks.index') }}" class="small text-decoration-none" style="color: var(--primary);">View all</a>
            </div>
            <div class="card-body p-0">
                @forelse($myTasks as $task)
                <div class="d-flex justify-content-between align-items-center px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div>
                        <div class="fw-500" style="font-size: 0.875rem;">{{ $task->title }}</div>
                        <small class="text-muted">{{ $task->client?->name }} {{ $task->due_date ? '- Due ' . $task->due_date->format('M d') : '' }}</small>
                    </div>
                    @if($task->status == 'pending')
                        <span class="badge bg-warning text-dark">Pending</span>
                    @elseif($task->status == 'in_progress')
                        <span class="badge" style="background: var(--primary); color: #fff;">In Progress</span>
                    @else
                        <span class="badge bg-danger">Overdue</span>
                    @endif
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-check-circle" style="font-size: 2rem; opacity: 0.3;"></i>
                    <p class="mt-2 mb-0 small">No pending tasks</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-envelope-paper-fill me-2" style="color: var(--accent);"></i>Recent FBR Notices</span>
                <a href="{{ route('fbr-notices.index') }}" class="small text-decoration-none" style="color: var(--primary);">View all</a>
            </div>
            <div class="card-body p-0">
                @forelse($recentNotices as $notice)
                <div class="d-flex justify-content-between align-items-center px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }} {{ $notice->is_escalated ? 'escalated' : '' }}">
                    <div>
                        <div class="fw-500" style="font-size: 0.875rem;">{{ Str::limit($notice->subject, 35) }}</div>
                        <small class="text-muted">{{ $notice->notice_section }} {{ $notice->tax_year ? '(' . $notice->tax_year . ')' : '' }}</small>
                    </div>
                    @if($notice->status == 'new')
                        <span class="badge" style="background: var(--accent); color: var(--primary);">New</span>
                    @elseif($notice->is_escalated)
                        <span class="badge bg-danger">Escalated</span>
                    @else
                        <span class="badge bg-secondary">{{ ucfirst($notice->status) }}</span>
                    @endif
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-envelope-check" style="font-size: 2rem; opacity: 0.3;"></i>
                    <p class="mt-2 mb-0 small">No notices</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Recent Clients -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people-fill me-2" style="color: var(--accent);"></i>Recent Clients</span>
        <a href="{{ route('clients.index') }}" class="small text-decoration-none" style="color: var(--primary);">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentClients as $client)
                <tr>
                    <td class="fw-500">{{ $client->name }}</td>
                    <td>{{ $client->email }}</td>
                    <td><span class="badge" style="background: rgba(48,58,80,0.08); color: var(--primary);">{{ $client->status }}</span></td>
                    <td>
                        <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">No clients yet. <a href="{{ route('clients.create') }}">Add your first client</a></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
