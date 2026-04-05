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
                <div class="stat-icon" style="background: rgba(139,92,246,0.08);">
                    <i class="bi bi-bank2" style="color: #7c3aed;"></i>
                </div>
                <div>
                    <div class="stat-value">{{ $pendingProceedings }}</div>
                    <div class="stat-label">Pending Proceedings</div>
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
                    <div class="stat-label">FBR Notifications</div>
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
                    Recent FBR Notifications
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

<!-- Upcoming Proceedings -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Upcoming Proceedings
        </div>
        <a href="{{ route('proceedings.index') }}" style="color: var(--primary); font-size: 0.78rem; font-weight: 600; text-decoration: none;">View All <i class="bi bi-chevron-right" style="font-size: 0.65rem;"></i></a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Case</th>
                    <th>Client</th>
                    <th>Stage</th>
                    <th>Hearing Date</th>
                    <th>Assigned</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($upcomingProceedings as $proc)
                <tr>
                    <td>
                        <a href="{{ route('proceedings.show', $proc) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.88rem;">{{ Str::limit($proc->title, 40) }}</a>
                        @if($proc->section)<div style="font-size: 0.72rem; color: #9ca3af;">Section {{ $proc->section }}</div>@endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 28px; height: 28px; background: rgba(48,58,80,0.06); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.6rem; color: var(--primary);">{{ strtoupper(substr($proc->client->name, 0, 2)) }}</div>
                            <span style="font-size: 0.85rem;">{{ Str::limit($proc->client->name, 20) }}</span>
                        </div>
                    </td>
                    <td>
                        @if($proc->stage == 'department')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Department</span>
                        @elseif($proc->stage == 'commissioner_appeals')
                            <span class="badge" style="background: #fef3c7; color: #92400e;">Comm. Appeals</span>
                        @else
                            <span class="badge" style="background: #fce7f3; color: #9d174d;">Tribunal</span>
                        @endif
                    </td>
                    <td>
                        @if($proc->hearing_date)
                            @php $days = now()->startOfDay()->diffInDays($proc->hearing_date, false); @endphp
                            <span style="font-size: 0.85rem; {{ $days < 0 ? 'color: #dc2626; font-weight: 600;' : ($days <= 3 ? 'color: #d97706;' : 'color: #6b7280;') }}">
                                {{ $proc->hearing_date->format('M d, Y') }}
                            </span>
                            @if($days < 0)
                                <span class="badge ms-1" style="background: #fef2f2; color: #dc2626;">Overdue</span>
                            @elseif($days == 0)
                                <span class="badge ms-1" style="background: #fef3c7; color: #92400e;">Today</span>
                            @elseif($days <= 3)
                                <span class="badge ms-1" style="background: #fef3c7; color: #92400e;">{{ $days }}d</span>
                            @endif
                        @else
                            <span style="color: #d1d5db; font-size: 0.85rem;">Not set</span>
                        @endif
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $proc->assignedTo->name ?? '-' }}</td>
                    <td class="text-end">
                        <a href="{{ route('proceedings.edit', $proc) }}" class="btn btn-sm btn-outline-primary" style="font-size: 0.75rem;"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-bank2" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No pending proceedings.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
