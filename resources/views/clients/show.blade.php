@extends('layouts.app')
@section('title', $client->name)
@section('page-title', 'Client Details')

@section('content')
<!-- Header -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div class="d-flex align-items-center gap-3">
        <div style="width: 52px; height: 52px; background: rgba(48,58,80,0.06); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; color: var(--primary);">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
        <div>
            <h4 style="font-weight: 700; color: var(--primary); margin: 0;">{{ $client->name }}</h4>
            <span class="badge mt-1" style="background: {{ $client->status == 'Company' ? '#dbeafe' : ($client->status == 'AOP' ? 'var(--accent-glow)' : 'rgba(48,58,80,0.06)') }}; color: {{ $client->status == 'Company' ? '#1e40af' : ($client->status == 'AOP' ? '#5c6300' : 'var(--primary)') }};">{{ $client->status }}</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Delete this client?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
        </form>
        <a href="{{ route('clients.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Contact Info -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-person-lines-fill me-2" style="color: var(--accent);"></i>Contact Information</div>
            <div class="card-body">
                <div class="mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Email</strong><br>{{ $client->email ?: '-' }}</div>
                <div class="mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Phone</strong><br>{{ $client->contact_no ?: '-' }}</div>
                @if($client->folder_link)
                <div class="mb-0"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Folder</strong><br><a href="{{ $client->folder_link }}" target="_blank" style="color: var(--primary); font-weight: 500;"><i class="bi bi-folder2-open me-1"></i>Open Documents</a></div>
                @endif
                @if($client->notes)
                <div class="mt-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Notes</strong><br><span style="font-size: 0.85rem;">{{ $client->notes }}</span></div>
                @endif
            </div>
        </div>
    </div>

    <!-- FBR Credentials -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-shield-lock me-2" style="color: var(--accent);"></i>FBR Credentials</div>
            <div class="card-body">
                <div class="mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Username</strong><br><span style="font-family: monospace;">{{ $client->fbr_username ?: '-' }}</span></div>
                <div class="mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Password</strong><br>
                    @if($client->fbr_password)
                    <span class="password-field" id="fbr-pass" style="font-family: monospace;">••••••••</span>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="togglePass('fbr-pass', '{{ addslashes($client->fbr_password) }}')" style="font-size: 0.7rem; padding: 2px 8px;"><i class="bi bi-eye"></i></button>
                    @else - @endif
                </div>
                <div class="mb-0"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">IT Pin Code</strong><br><span style="font-family: monospace;">{{ $client->it_pin_code ?: '-' }}</span></div>
            </div>
        </div>
    </div>

    <!-- KPRA & SECP -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-key me-2" style="color: var(--accent);"></i>KPRA / SECP Credentials</div>
            <div class="card-body">
                <div class="mb-2"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">KPRA Username</strong><br><span style="font-family: monospace;">{{ $client->kpra_username ?: '-' }}</span></div>
                <div class="mb-2"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">KPRA Password</strong><br>
                    @if($client->kpra_password)
                    <span id="kpra-pass" style="font-family: monospace;">••••••••</span>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="togglePass('kpra-pass', '{{ addslashes($client->kpra_password) }}')" style="font-size: 0.7rem; padding: 2px 8px;"><i class="bi bi-eye"></i></button>
                    @else - @endif
                </div>
                <div class="mb-2"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">KPRA Pin</strong><br><span style="font-family: monospace;">{{ $client->kpra_pin ?: '-' }}</span></div>
                <hr style="border-color: #f0f2f5;">
                <div class="mb-2"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">SECP Password</strong><br>
                    @if($client->secp_password)
                    <span id="secp-pass" style="font-family: monospace;">••••••••</span>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="togglePass('secp-pass', '{{ addslashes($client->secp_password) }}')" style="font-size: 0.7rem; padding: 2px 8px;"><i class="bi bi-eye"></i></button>
                    @else - @endif
                </div>
                <div class="mb-0"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">SECP Pin</strong><br><span style="font-family: monospace;">{{ $client->secp_pin ?: '-' }}</span></div>
            </div>
        </div>
    </div>
</div>

<!-- Active Services -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-briefcase me-2" style="color: var(--accent);"></i>Active Services ({{ $client->activeServices->count() }})</span>
    </div>
    <div class="card-body p-0">
        @forelse($client->activeServices as $service)
        <div class="d-flex justify-content-between align-items-center px-3 py-3" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
            <div>
                <div style="font-weight: 600; font-size: 0.88rem; color: var(--primary);">{{ $service->display_name }}</div>
                <div style="font-size: 0.75rem; color: #9ca3af;">{{ $service->description }}</div>
            </div>
            <div>
                @if($service->pivot->next_deadline)
                    @php $days = now()->diffInDays($service->pivot->next_deadline, false); @endphp
                    <span style="font-size: 0.82rem; color: #6b7280;">Due: {{ \Carbon\Carbon::parse($service->pivot->next_deadline)->format('M d, Y') }}</span>
                    @if($days < 0)
                        <span class="badge ms-1" style="background: #fef2f2; color: #dc2626;">{{ abs($days) }}d overdue</span>
                    @elseif($days <= 7)
                        <span class="badge ms-1" style="background: #fef3c7; color: #92400e;">{{ $days }}d left</span>
                    @else
                        <span class="badge ms-1" style="background: #d1fae5; color: #065f46;">On track</span>
                    @endif
                @else
                    <span style="font-size: 0.82rem; color: #d1d5db;">No deadline set</span>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-4" style="color: #9ca3af;">No active services</div>
        @endforelse
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Shareholders -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-people me-2" style="color: var(--accent);"></i>Shareholders ({{ $client->shareholders->count() }})</div>
            <div class="card-body p-0">
                @forelse($client->shareholders as $sh)
                <div class="d-flex justify-content-between align-items-center px-3 py-2" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
                    <a href="{{ route('clients.show', $sh) }}" style="color: var(--primary); font-weight: 500; text-decoration: none;">{{ $sh->name }}</a>
                    @if($sh->pivot->share_percentage)<span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">{{ $sh->pivot->share_percentage }}%</span>@endif
                </div>
                @empty
                <div class="text-center py-4" style="color: #9ca3af;">No shareholders</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Tasks -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-check2-square me-2" style="color: var(--accent);"></i>Tasks ({{ $client->tasks->count() }})</div>
            <div class="card-body p-0">
                @forelse($client->tasks->take(5) as $task)
                <div class="d-flex justify-content-between align-items-center px-3 py-2" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
                    <a href="{{ route('tasks.show', $task) }}" style="color: var(--primary); font-weight: 500; text-decoration: none;">{{ $task->title }}</a>
                    @if($task->status == 'pending')
                        <span class="badge" style="background: #fef3c7; color: #92400e;">Pending</span>
                    @elseif($task->status == 'in_progress')
                        <span class="badge" style="background: #dbeafe; color: #1e40af;">In Progress</span>
                    @elseif($task->status == 'completed')
                        <span class="badge" style="background: #d1fae5; color: #065f46;">Completed</span>
                    @else
                        <span class="badge" style="background: #fef2f2; color: #dc2626;">Overdue</span>
                    @endif
                </div>
                @empty
                <div class="text-center py-4" style="color: #9ca3af;">No tasks</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- FBR Notices -->
<div class="card">
    <div class="card-header"><i class="bi bi-envelope-paper me-2" style="color: var(--accent);"></i>FBR Notices ({{ $client->fbrNotices->count() }})</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Subject</th><th>Section</th><th>Tax Year</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($client->fbrNotices->take(5) as $notice)
                <tr>
                    <td style="font-weight: 500;">{{ Str::limit($notice->subject, 40) }}</td>
                    <td>{{ $notice->notice_section ?? '-' }}</td>
                    <td>{{ $notice->tax_year ?? '-' }}</td>
                    <td>
                        @if($notice->is_escalated) <span class="badge" style="background: #fef2f2; color: #dc2626;">Escalated</span>
                        @elseif($notice->status == 'new') <span class="badge" style="background: var(--accent); color: var(--primary);">New</span>
                        @else <span class="badge" style="background: #f3f4f6; color: #6b7280;">{{ ucfirst($notice->status) }}</span>
                        @endif
                    </td>
                    <td style="color: #6b7280;">{{ $notice->email_received_at instanceof \Carbon\Carbon ? $notice->email_received_at->format('M d, Y') : $notice->email_received_at }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4" style="color: #9ca3af;">No notices</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
function togglePass(id, value) {
    var el = document.getElementById(id);
    if (el.textContent === '••••••••') {
        el.textContent = value;
    } else {
        el.textContent = '••••••••';
    }
}
</script>
@endsection
