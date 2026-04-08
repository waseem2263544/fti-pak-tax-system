@extends('layouts.app')
@section('title', $client->name)
@section('page-title', 'Client Details')

@section('styles')
<style>
    .cred-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 16px; border-bottom: 1px solid #f5f6f8;
    }
    .cred-row:last-child { border-bottom: none; }
    .cred-label {
        font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 2px;
    }
    .cred-value {
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        font-size: 0.88rem; color: var(--primary); font-weight: 500;
    }
    .cred-actions { display: flex; gap: 4px; flex-shrink: 0; margin-left: 12px; }
    .cred-btn {
        width: 30px; height: 30px; border-radius: 8px; border: 1px solid #e8eaed;
        background: #fff; display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: all 0.15s; color: #9ca3af; font-size: 0.82rem;
    }
    .cred-btn:hover { background: #f8f9fb; color: var(--primary); border-color: #d1d5db; }
    .cred-btn.copied { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
    .info-item { margin-bottom: 16px; }
    .info-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px; }
    .info-value { font-size: 0.88rem; color: var(--primary); }
    .section-card .card-header {
        display: flex; align-items: center; gap: 8px;
        padding: 14px 20px; font-size: 0.85rem;
    }
    .section-card .card-header i { font-size: 1rem; }
</style>
@endsection

@section('content')
<!-- Header -->
<div class="card mb-4">
    <div class="card-body" style="padding: 24px;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 56px; height: 56px; background: var(--accent); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; color: var(--primary);">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
                <div>
                    <h4 style="font-weight: 800; color: var(--primary); margin: 0; font-size: 1.3rem;">{{ $client->name }}</h4>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge" style="background: {{ $client->status == 'Company' ? '#dbeafe' : ($client->status == 'AOP' ? 'var(--accent-glow)' : 'rgba(48,58,80,0.06)') }}; color: {{ $client->status == 'Company' ? '#1e40af' : ($client->status == 'AOP' ? '#5c6300' : 'var(--primary)') }};">{{ $client->status }}</span>
                        <span style="color: #d1d5db;">&middot;</span>
                        <span style="font-size: 0.82rem; color: #6b7280;">{{ $client->activeServices->count() }} active service{{ $client->activeServices->count() !== 1 ? 's' : '' }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('clients.edit', $client) }}" class="btn btn-accent btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                <a href="{{ route('clients.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>
    </div>
</div>

<!-- Contact & Notes -->
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card section-card h-100">
            <div class="card-header">
                <i class="bi bi-person-vcard" style="color: var(--accent);"></i>
                <span style="font-weight: 700;">Contact Information</span>
            </div>
            <div class="card-body" style="padding: 20px;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">
                                @if($client->email)
                                    <a href="mailto:{{ $client->email }}" style="color: var(--primary); text-decoration: none;">{{ $client->email }}</a>
                                @else <span style="color: #d1d5db;">Not set</span> @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value">{{ $client->contact_no ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <div class="info-label">Type</div>
                            <div class="info-value">{{ $client->status }}</div>
                        </div>
                    </div>
                </div>
                @if($client->folder_link)
                <div class="info-item mb-0">
                    <div class="info-label">Document Folder</div>
                    <a href="{{ $client->sharePointUrl }}" target="_blank" style="color: var(--primary); font-weight: 500; text-decoration: none; font-size: 0.85rem;">
                        <i class="bi bi-folder2-open me-1"></i>Open in SharePoint
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card section-card h-100">
            <div class="card-header">
                <i class="bi bi-sticky" style="color: var(--accent);"></i>
                <span style="font-weight: 700;">Notes</span>
            </div>
            <div class="card-body" style="padding: 20px;">
                @if($client->notes)
                    <p style="font-size: 0.85rem; color: #4b5563; margin: 0; line-height: 1.6;">{{ $client->notes }}</p>
                @else
                    <p style="color: #d1d5db; font-size: 0.85rem; margin: 0;">No notes added</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Credentials Row -->
<div class="row g-3 mb-4">
    <!-- FBR -->
    <div class="col-md-4">
        <div class="card section-card h-100">
            <div class="card-header" style="background: rgba(48,58,80,0.02);">
                <i class="bi bi-shield-lock-fill" style="color: #2563eb;"></i>
                <span style="font-weight: 700;">FBR Credentials</span>
            </div>
            <div class="p-0">
                <div class="cred-row">
                    <div>
                        <div class="cred-label"><i class="bi bi-person-fill me-1"></i>Username</div>
                        <div class="cred-value" id="fbr-user">{{ $client->fbr_username ?: '-' }}</div>
                    </div>
                    @if($client->fbr_username)
                    <div class="cred-actions">
                        <button class="cred-btn" onclick="copyText('fbr-user', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                    </div>
                    @endif
                </div>
                <div class="cred-row">
                    <div>
                        <div class="cred-label"><i class="bi bi-key-fill me-1"></i>Password</div>
                        <div class="cred-value" id="fbr-pass" data-value="{{ $client->fbr_password }}">{{ $client->fbr_password ? '••••••••' : '-' }}</div>
                    </div>
                    @if($client->fbr_password)
                    <div class="cred-actions">
                        <button class="cred-btn" onclick="togglePass('fbr-pass')" title="Show/Hide"><i class="bi bi-eye"></i></button>
                        <button class="cred-btn" onclick="copyValue('fbr-pass', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                    </div>
                    @endif
                </div>
                <div class="cred-row">
                    <div>
                        <div class="cred-label"><i class="bi bi-hash me-1"></i>IT Pin Code</div>
                        <div class="cred-value" id="fbr-pin">{{ $client->it_pin_code ?: '-' }}</div>
                    </div>
                    @if($client->it_pin_code)
                    <div class="cred-actions">
                        <button class="cred-btn" onclick="copyText('fbr-pin', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- KPRA -->
    <div class="col-md-4">
        <div class="card section-card h-100">
            <div class="card-header" style="background: rgba(48,58,80,0.02);">
                <i class="bi bi-building-fill-lock" style="color: #059669;"></i>
                <span style="font-weight: 700;">KPRA Credentials</span>
            </div>
            <div class="p-0">
                <div class="cred-row">
                    <div>
                        <div class="cred-label"><i class="bi bi-person-fill me-1"></i>Username</div>
                        <div class="cred-value" id="kpra-user">{{ $client->kpra_username ?: '-' }}</div>
                    </div>
                    @if($client->kpra_username)
                    <div class="cred-actions">
                        <button class="cred-btn" onclick="copyText('kpra-user', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                    </div>
                    @endif
                </div>
                <div class="cred-row">
                    <div>
                        <div class="cred-label"><i class="bi bi-key-fill me-1"></i>Password</div>
                        <div class="cred-value" id="kpra-pass" data-value="{{ $client->kpra_password }}">{{ $client->kpra_password ? '••••••••' : '-' }}</div>
                    </div>
                    @if($client->kpra_password)
                    <div class="cred-actions">
                        <button class="cred-btn" onclick="togglePass('kpra-pass')" title="Show/Hide"><i class="bi bi-eye"></i></button>
                        <button class="cred-btn" onclick="copyValue('kpra-pass', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                    </div>
                    @endif
                </div>
                <div class="cred-row">
                    <div>
                        <div class="cred-label"><i class="bi bi-hash me-1"></i>Pin</div>
                        <div class="cred-value" id="kpra-pin">{{ $client->kpra_pin ?: '-' }}</div>
                    </div>
                    @if($client->kpra_pin)
                    <div class="cred-actions">
                        <button class="cred-btn" onclick="copyText('kpra-pin', this)" title="Copy"><i class="bi bi-clipboard"></i></button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- SECP -->
    <div class="col-md-4">
        <div class="card section-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center" style="background: rgba(48,58,80,0.02);">
                <div><i class="bi bi-safe" style="color: #d97706;"></i> <span style="font-weight: 700;">SECP Directors</span></div>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addDirectorModal" style="font-size: 0.72rem; padding: 2px 10px;"><i class="bi bi-plus"></i> Add</button>
            </div>
            <div class="p-0">
                @forelse($client->secpDirectors as $i => $director)
                <div style="padding: 12px 16px; {{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div style="font-weight: 700; font-size: 0.88rem; color: var(--primary);">{{ $director->director_name }}</div>
                            <div style="font-size: 0.75rem; color: #9ca3af; font-family: monospace;">{{ $director->cnic ?: 'No CNIC' }}</div>
                        </div>
                        <form method="POST" action="{{ route('clients.delete-director', $director) }}" onsubmit="return confirm('Remove this director?')">
                            @csrf @method('DELETE')
                            <button class="cred-btn" title="Remove"><i class="bi bi-x-lg" style="font-size: 0.65rem;"></i></button>
                        </form>
                    </div>
                    <div class="d-flex gap-2">
                        <div style="flex: 1;">
                            <div class="cred-label" style="font-size: 0.65rem;"><i class="bi bi-key-fill me-1"></i>Password</div>
                            <div class="d-flex align-items-center gap-1">
                                <span class="cred-value" id="secp-pass-{{ $director->id }}" data-value="{{ $director->secp_password }}" style="font-size: 0.8rem;">{{ $director->secp_password ? '••••••••' : '-' }}</span>
                                @if($director->secp_password)
                                <button class="cred-btn" onclick="togglePass('secp-pass-{{ $director->id }}')" title="Show"><i class="bi bi-eye" style="font-size: 0.7rem;"></i></button>
                                <button class="cred-btn" onclick="copyValue('secp-pass-{{ $director->id }}', this)" title="Copy"><i class="bi bi-clipboard" style="font-size: 0.7rem;"></i></button>
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="cred-label" style="font-size: 0.65rem;"><i class="bi bi-hash me-1"></i>PIN</div>
                            <div class="d-flex align-items-center gap-1">
                                <span class="cred-value" id="secp-pin-{{ $director->id }}" style="font-size: 0.8rem;">{{ $director->secp_pin ?: '-' }}</span>
                                @if($director->secp_pin)
                                <button class="cred-btn" onclick="copyText('secp-pin-{{ $director->id }}', this)" title="Copy"><i class="bi bi-clipboard" style="font-size: 0.7rem;"></i></button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4" style="color: #d1d5db; font-size: 0.82rem;">No directors added</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Add Director Modal -->
<div class="modal fade" id="addDirectorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #f0f2f5; padding: 20px 24px;">
                <h5 style="font-weight: 700; color: var(--primary); margin: 0; font-size: 1rem;"><i class="bi bi-safe me-2" style="color: #d97706;"></i>Add SECP Director</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('clients.add-director', $client) }}">
                @csrf
                <div class="modal-body" style="padding: 24px;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Director Name <span class="text-danger">*</span></label>
                            <input type="text" name="director_name" class="form-control" required placeholder="Full name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CNIC</label>
                            <input type="text" name="cnic" class="form-control" placeholder="00000-0000000-0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SECP Password</label>
                            <input type="text" name="secp_password" class="form-control" placeholder="Password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SECP PIN</label>
                            <input type="text" name="secp_pin" class="form-control" placeholder="PIN">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f2f5; padding: 16px 24px;">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Director</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Active Services -->
<div class="card section-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-briefcase-fill" style="color: var(--accent);"></i>
            <span style="font-weight: 700;">Active Services ({{ $client->activeServices->count() }})</span>
        </div>
    </div>
    <div class="p-0">
        @forelse($client->activeServices as $service)
        <div class="d-flex align-items-center gap-3 px-4 py-3" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
            <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--accent);"></div>
            <div style="font-weight: 600; font-size: 0.88rem; color: var(--primary);">{{ $service->display_name }}</div>
        </div>
        @empty
        <div class="text-center py-4" style="color: #9ca3af;">No active services</div>
        @endforelse
    </div>
</div>

<!-- Shareholders & Tasks -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card section-card h-100">
            <div class="card-header">
                <i class="bi bi-people-fill" style="color: var(--accent);"></i>
                <span style="font-weight: 700;">Shareholders ({{ $client->shareholders->count() }})</span>
            </div>
            <div class="p-0">
                @forelse($client->shareholders as $sh)
                <div class="d-flex justify-content-between align-items-center px-4 py-2" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
                    <a href="{{ route('clients.show', $sh) }}" style="color: var(--primary); font-weight: 500; text-decoration: none; font-size: 0.88rem;">{{ $sh->name }}</a>
                    @if($sh->pivot->share_percentage)<span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">{{ $sh->pivot->share_percentage }}%</span>@endif
                </div>
                @empty
                <div class="text-center py-4" style="color: #9ca3af; font-size: 0.85rem;">No shareholders</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card section-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check2-square" style="color: var(--accent);"></i>
                    <span style="font-weight: 700;">Tasks ({{ $client->tasks->count() }})</span>
                </div>
                <a href="{{ route('tasks.create') }}" style="font-size: 0.75rem; color: var(--primary); font-weight: 600; text-decoration: none;">+ Add</a>
            </div>
            <div class="p-0">
                @forelse($client->tasks->take(5) as $task)
                <div class="d-flex justify-content-between align-items-center px-4 py-2" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
                    <a href="{{ route('tasks.show', $task) }}" style="color: var(--primary); font-weight: 500; text-decoration: none; font-size: 0.88rem;">{{ $task->title }}</a>
                    @if($task->status == 'pending') <span class="badge" style="background: #fef3c7; color: #92400e;">Pending</span>
                    @elseif($task->status == 'in_progress') <span class="badge" style="background: #dbeafe; color: #1e40af;">In Progress</span>
                    @elseif($task->status == 'completed') <span class="badge" style="background: #d1fae5; color: #065f46;">Done</span>
                    @else <span class="badge" style="background: #fef2f2; color: #dc2626;">Overdue</span>
                    @endif
                </div>
                @empty
                <div class="text-center py-4" style="color: #9ca3af; font-size: 0.85rem;">No tasks</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- FBR Notices -->
<div class="card section-card">
    <div class="card-header">
        <i class="bi bi-envelope-paper-fill" style="color: var(--accent);"></i>
        <span style="font-weight: 700;">FBR Notices ({{ $client->fbrNotices->count() }})</span>
    </div>
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
function togglePass(id) {
    var el = document.getElementById(id);
    if (el.textContent === '••••••••') {
        el.textContent = el.dataset.value;
    } else {
        el.textContent = '••••••••';
    }
}

function copyText(id, btn) {
    var text = document.getElementById(id).textContent.trim();
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="bi bi-check"></i>';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
        }, 1500);
    });
}

function copyValue(id, btn) {
    var el = document.getElementById(id);
    var text = el.dataset.value;
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="bi bi-check"></i>';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
        }, 1500);
    });
}
</script>
@endsection
