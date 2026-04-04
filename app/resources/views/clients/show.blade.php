@extends('layouts.app')

@section('page-title', 'Client: ' . $client->name)

@section('content')
<div class="row mb-3">
    <div class="col-md-6">
        <h4>{{ $client->name }}</h4>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('clients.edit', $client) }}" class="btn btn-secondary">Edit</a>
        <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row">
    <!-- Client Info -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">Contact Information</h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Name:</strong> {{ $client->name }}</p>
                <p class="mb-2"><strong>Email:</strong> {{ $client->email }}</p>
                <p class="mb-2"><strong>Contact No:</strong> {{ $client->contact_no }}</p>
                <p class="mb-2"><strong>Status:</strong> <span class="badge bg-info">{{ $client->status }}</span></p>
                @if($client->folder_link)
                <p class="mb-0"><strong>Folder Link:</strong> <a href="{{ $client->folder_link }}" target="_blank">View Documents</a></p>
                @endif
            </div>
        </div>
    </div>

    <!-- Credentials -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">Credentials (Encrypted)</h6>
            </div>
            <div class="card-body">
                @if($client->fbr_username)
                <p class="mb-2"><strong>FBR User:</strong> {{ $client->fbr_username }}</p>
                @endif
                @if($client->kpra_username)
                <p class="mb-2"><strong>KPRA User:</strong> {{ $client->kpra_username }}</p>
                @endif
                @if($client->secp_pin)
                <p class="mb-0"><strong>SECP PIN:</strong> ••••••</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Active Services -->
<div class="card mb-3">
    <div class="card-header bg-light">
        <h6 class="mb-0">Active Services ({{ $client->activeServices()->count() }})</h6>
    </div>
    <div class="card-body">
        @forelse($client->activeServices as $service)
        <div class="row mb-2 pb-2 border-bottom">
            <div class="col-md-4">
                <strong>{{ $service->display_name }}</strong>
            </div>
            <div class="col-md-4">
                @if($service->pivot->next_deadline)
                <small class="text-muted">Next Deadline:</small><br>
                <strong>{{ $service->pivot->next_deadline->format('Y-m-d') }}</strong>
                @else
                <small class="text-muted">No deadline set</small>
                @endif
            </div>
            <div class="col-md-4">
                @if($service->pivot->next_deadline)
                @php
                    $daysRemaining = now()->diffInDays($service->pivot->next_deadline, false);
                @endphp
                @if($daysRemaining < 0)
                    <span class="badge bg-danger">{{ abs($daysRemaining) }} days overdue</span>
                @elseif($daysRemaining <= 7)
                    <span class="badge bg-warning">{{ $daysRemaining }} days left</span>
                @else
                    <span class="badge bg-success">On track</span>
                @endif
                @endif
            </div>
        </div>
        @empty
        <p class="text-muted">No active services</p>
        @endforelse
    </div>
</div>

<div class="row">
    <!-- Shareholders -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">Shareholders ({{ $client->shareholders()->count() }})</h6>
            </div>
            <div class="card-body">
                @forelse($client->shareholders as $shareholder)
                <div class="mb-2">
                    <p class="mb-0"><strong>{{ $shareholder->name }}</strong></p>
                    <small class="text-muted">{{ $shareholder->pivot->share_percentage }}%</small>
                </div>
                @empty
                <p class="text-muted">No shareholders</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Tasks -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">Related Tasks ({{ $client->tasks()->count() }})</h6>
            </div>
            <div class="card-body">
                @forelse($client->tasks()->take(5)->get() as $task)
                <div class="mb-2">
                    <p class="mb-0"><strong>{{ $task->title }}</strong></p>
                    <small class="text-muted">{{ $task->status }}</small>
                </div>
                @empty
                <p class="text-muted">No tasks</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- FBR Notices -->
<div class="card">
    <div class="card-header bg-light">
        <h6 class="mb-0">FBR Notices ({{ $client->fbrNotices()->count() }})</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Tax Year</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($client->fbrNotices()->take(5)->get() as $notice)
                    <tr>
                        <td>{{ Str::limit($notice->subject, 30) }}</td>
                        <td>{{ $notice->notice_section }}</td>
                        <td>{{ $notice->tax_year }}</td>
                        <td><span class="badge @if($notice->is_escalated) bg-danger @else bg-warning @endif">{{ $notice->status }}</span></td>
                        <td>{{ $notice->email_received_at->format('Y-m-d') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No notices</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
