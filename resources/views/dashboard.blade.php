@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')
<!-- Welcome -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight: 800; color: var(--primary); margin: 0;">Good {{ date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') }}, {{ explode(' ', Auth::user()->name)[0] }}</h4>
        <p style="color: #9ca3af; font-size: 0.85rem; margin: 4px 0 0;">Here's what's happening with your practice today.</p>
    </div>
    <a href="{{ route('clients.create') }}" class="btn btn-accent">
        <i class="bi bi-plus-lg me-1"></i> New Client
    </a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(48,58,80,0.06);">
                    <i class="bi bi-people-fill" style="color: var(--primary);"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $totalClients }}</div>
                    <div class="stat-label">Total Clients</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: var(--accent-glow);">
                    <i class="bi bi-briefcase-fill" style="color: #8b9a00;"></i>
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
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(245,158,11,0.08);">
                    <i class="bi bi-hourglass-split" style="color: #d97706;"></i>
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
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(239,68,68,0.07);">
                    <i class="bi bi-envelope-exclamation-fill" style="color: #dc2626;"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $newFbrNotices }}</div>
                    <div class="stat-label">FBR Notices</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tasks & Notices -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
                    My Tasks
                </div>
                <a href="{{ route('tasks.index') }}" style="color: var(--primary); font-size: 0.78rem; font-weight: 600; text-decoration: none;">View All <i class="bi bi-chevron-right" style="font-size: 0.65rem;"></i></a>
            </div>
            <div class="card-body p-0">
                @forelse($myTasks as $task)
                <div class="d-flex justify-content-between align-items-center px-3 py-3 {{ !$loop->last ? '' : '' }}" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: {{ $task->status == 'overdue' ? '#ef4444' : ($task->status == 'in_progress' ? 'var(--accent)' : '#d1d5db') }};"></div>
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 600; color: var(--primary);">{{ $task->title }}</div>
                            <div style="font-size: 0.75rem; color: #9ca3af;">{{ $task->client?->name }}{{ $task->due_date ? ' &middot; Due ' . $task->due_date->format('M d') : '' }}</div>
                        </div>
                    </div>
                    @if($task->status == 'pending')
                        <span class="badge" style="background: #fef3c7; color: #92400e;">Pending</span>
                    @elseif($task->status == 'in_progress')
                        <span class="badge" style="background: var(--accent-glow); color: #5c6300;">Active</span>
                    @else
                        <span class="badge" style="background: #fef2f2; color: #dc2626;">Overdue</span>
                    @endif
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #e5e7eb;"></i>
                    <p style="color: #9ca3af; font-size: 0.85rem; margin: 12px 0 0;">All caught up!</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
                    Recent FBR Notices
                </div>
                <a href="{{ route('fbr-notices.index') }}" style="color: var(--primary); font-size: 0.78rem; font-weight: 600; text-decoration: none;">View All <i class="bi bi-chevron-right" style="font-size: 0.65rem;"></i></a>
            </div>
            <div class="card-body p-0">
                @forelse($recentNotices as $notice)
                <div class="d-flex justify-content-between align-items-center px-3 py-3 {{ $notice->is_escalated ? 'escalated' : '' }}" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
                    <div>
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--primary);">{{ Str::limit($notice->subject, 40) }}</div>
                        <div style="font-size: 0.75rem; color: #9ca3af;">{{ $notice->notice_section }}{{ $notice->tax_year ? ' &middot; ' . $notice->tax_year : '' }}</div>
                    </div>
                    @if($notice->status == 'new')
                        <span class="badge" style="background: var(--accent); color: var(--primary); font-weight: 700;">New</span>
                    @elseif($notice->is_escalated)
                        <span class="badge" style="background: #fef2f2; color: #dc2626;">Escalated</span>
                    @else
                        <span class="badge" style="background: #f3f4f6; color: #6b7280;">{{ ucfirst($notice->status) }}</span>
                    @endif
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="bi bi-envelope-check" style="font-size: 2.5rem; color: #e5e7eb;"></i>
                    <p style="color: #9ca3af; font-size: 0.85rem; margin: 12px 0 0;">No recent notices</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Recent Clients -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Recent Clients
        </div>
        <a href="{{ route('clients.index') }}" style="color: var(--primary); font-size: 0.78rem; font-weight: 600; text-decoration: none;">View All <i class="bi bi-chevron-right" style="font-size: 0.65rem;"></i></a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentClients as $client)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 34px; height: 34px; background: rgba(48,58,80,0.06); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; color: var(--primary);">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
                            <span style="font-weight: 600;">{{ $client->name }}</span>
                        </div>
                    </td>
                    <td style="color: #6b7280;">{{ $client->email }}</td>
                    <td><span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">{{ $client->status }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary" style="font-size: 0.78rem;">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-5" style="color: #9ca3af;">
                        No clients yet. <a href="{{ route('clients.create') }}" style="color: var(--primary); font-weight: 600;">Add your first client</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
