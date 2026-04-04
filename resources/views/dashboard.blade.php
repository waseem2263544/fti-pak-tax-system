@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Total Clients</h6>
                <h3 class="text-primary">{{ $totalClients }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Active Services</h6>
                <h3 class="text-success">{{ $activeServices }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">Pending Tasks</h6>
                <h3 class="text-warning">{{ $pendingTasks }}</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title text-muted">FBR Notices (New)</h6>
                <h3 class="text-danger">{{ $newFbrNotices }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- My Tasks -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-check-square"></i> My Tasks</h6>
            </div>
            <div class="card-body">
                @forelse($myTasks as $task)
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <p class="mb-0"><strong>{{ $task->title }}</strong></p>
                        <small class="text-muted">{{ $task->client?->name }}</small>
                    </div>
                    <span class="badge bg-info">{{ $task->status }}</span>
                </div>
                @empty
                <p class="text-muted mb-0">No tasks assigned</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent FBR Notices -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-envelope"></i> Recent FBR Notices</h6>
            </div>
            <div class="card-body">
                @forelse($recentNotices as $notice)
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <div>
                        <p class="mb-0"><strong>{{ Str::limit($notice->subject, 30) }}</strong></p>
                        <small class="text-muted">{{ $notice->notice_section }} ({{ $notice->tax_year }})</small>
                    </div>
                    <span class="badge @if($notice->is_escalated) bg-danger @else bg-warning @endif">{{ $notice->status }}</span>
                </div>
                @empty
                <p class="text-muted mb-0">No recent notices</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Recent Clients -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-people"></i> Recent Clients</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Services</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentClients as $client)
                            <tr>
                                <td><strong>{{ $client->name }}</strong></td>
                                <td>{{ $client->email }}</td>
                                <td><span class="badge bg-info">{{ $client->status }}</span></td>
                                <td>{{ $client->activeServices()->count() }} active</td>
                                <td>
                                    <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No clients yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
