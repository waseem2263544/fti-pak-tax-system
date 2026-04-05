@extends('layouts.app')
@section('title', 'FBR Notices')
@section('page-title', 'FBR Notices')

@section('styles')
<style>
    .notice-row { cursor: pointer; transition: all 0.15s; }
    .notice-row:hover { background: #fafbfc !important; }
    .notice-row.read { background: #fff; }
    .notice-row.unread { background: #f8faff; font-weight: 500; }
    .notice-row.unread td:first-child { border-left: 3px solid var(--accent); }
    .notice-expanded {
        background: #fafbfc !important;
        border-left: 3px solid var(--accent) !important;
    }
    .notice-detail {
        display: none;
        background: #f8f9fb;
        animation: slideDown 0.2s ease;
    }
    .notice-detail.show { display: table-row; }
    @keyframes slideDown { from { opacity: 0; } to { opacity: 1; } }
    .notice-body {
        padding: 20px 24px;
        font-size: 0.85rem; color: #4b5563;
        line-height: 1.7;
        max-height: 300px; overflow-y: auto;
    }
</style>
@endsection

@section('content')
<!-- Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('fbr-notices.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>New</option>
                        <option value="reviewed" {{ request('status') == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="escalated" {{ request('status') == 'escalated' ? 'selected' : '' }}>Escalated</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="tax_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Tax Years</option>
                        @foreach($taxYears as $year)
                            <option value="{{ $year }}" {{ request('tax_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="section" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section }}" {{ request('section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    @if(request()->hasAny(['status', 'tax_year', 'section']))
                        <a href="{{ route('fbr-notices.index') }}" class="btn btn-outline-primary btn-sm flex-grow-1"><i class="bi bi-x-lg me-1"></i>Clear</a>
                    @endif
                    <a href="/fetch-fbr-now.php" class="btn btn-accent btn-sm flex-grow-1" title="Fetch latest from email"><i class="bi bi-cloud-download me-1"></i>Fetch Now</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div style="font-size: 0.85rem; color: #6b7280;">
        <strong>{{ $notices->total() }}</strong> notice{{ $notices->total() !== 1 ? 's' : '' }}
        @if(request('status')) &middot; {{ ucfirst(request('status')) }} @endif
    </div>
</div>

<!-- Notices Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0" id="notices-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Section</th>
                    <th>Tax Year</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($notices as $notice)
                <tr class="notice-row {{ $notice->status == 'new' ? 'unread' : 'read' }} {{ $notice->is_escalated ? 'escalated' : '' }}"
                    id="row-{{ $notice->id }}" onclick="toggleNotice({{ $notice->id }})">
                    <td>
                        <div style="font-size: 0.88rem; color: var(--primary);">{{ Str::limit($notice->subject, 50) }}</div>
                        <div style="font-size: 0.75rem; color: #9ca3af;">From: {{ $notice->sender_email }}</div>
                    </td>
                    <td>
                        <span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">{{ $notice->notice_section ?? 'General' }}</span>
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $notice->tax_year ?? '-' }}</td>
                    <td>
                        @if($notice->client)
                            <a href="{{ route('clients.show', $notice->client) }}" style="color: var(--primary); font-weight: 500; text-decoration: none;" onclick="event.stopPropagation();">{{ $notice->client->name }}</a>
                        @else
                            <span style="color: #d1d5db;">Unassigned</span>
                        @endif
                    </td>
                    <td>
                        @if($notice->status == 'new')
                            <span class="badge" style="background: var(--accent); color: var(--primary); font-weight: 700;">New</span>
                        @elseif($notice->status == 'reviewed')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Reviewed</span>
                        @elseif($notice->status == 'resolved')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Resolved</span>
                        @else
                            <span class="badge" style="background: #fef2f2; color: #dc2626;">Escalated</span>
                        @endif
                    </td>
                    <td style="font-size: 0.82rem; color: #6b7280;">{{ $notice->email_received_at instanceof \Carbon\Carbon ? $notice->email_received_at->format('M d, Y') : $notice->email_received_at }}</td>
                    <td class="text-end" onclick="event.stopPropagation();">
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleNotice({{ $notice->id }})" title="Read">
                                <i class="bi bi-book"></i>
                            </button>
                            <button class="btn btn-sm btn-accent" onclick="addToProceedings({{ $notice->id }}, '{{ addslashes($notice->subject) }}', {{ $notice->client_id ?? 'null' }})" title="Add to Proceedings">
                                <i class="bi bi-bank2"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <!-- Expandable detail row -->
                <tr class="notice-detail" id="detail-{{ $notice->id }}">
                    <td colspan="7" style="padding: 0;">
                        <div class="notice-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 style="font-weight: 700; color: var(--primary); margin: 0;">{{ $notice->subject }}</h6>
                                    <div style="font-size: 0.78rem; color: #9ca3af; margin-top: 4px;">
                                        From: {{ $notice->sender_email }} &middot;
                                        Received: {{ $notice->email_received_at instanceof \Carbon\Carbon ? $notice->email_received_at->format('M d, Y H:i') : $notice->email_received_at }}
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    @if($notice->status == 'new')
                                    <form method="POST" action="{{ route('fbr-notices.updateStatus', $notice) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="reviewed">
                                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check me-1"></i>Mark Reviewed</button>
                                    </form>
                                    @endif
                                    @if($notice->status != 'resolved')
                                    <form method="POST" action="{{ route('fbr-notices.updateStatus', $notice) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="resolved">
                                        <button class="btn btn-sm btn-outline-primary" style="color: #065f46; border-color: #a7f3d0;"><i class="bi bi-check-circle me-1"></i>Resolve</button>
                                    </form>
                                    @endif
                                    <button class="btn btn-sm btn-accent" onclick="addToProceedings({{ $notice->id }}, '{{ addslashes($notice->subject) }}', {{ $notice->client_id ?? 'null' }})">
                                        <i class="bi bi-bank2 me-1"></i>Add to Proceedings
                                    </button>
                                </div>
                            </div>
                            <div style="background: #fff; border-radius: 8px; padding: 16px; border: 1px solid #e8eaed;">
                                {!! nl2br(e($notice->body ?: 'No preview available.')) !!}
                            </div>
                            @if(!$notice->client)
                            <div class="mt-3">
                                <form method="POST" action="{{ route('fbr-notices.assignClient', $notice) }}" class="d-flex gap-2 align-items-center">
                                    @csrf
                                    <span style="font-size: 0.82rem; font-weight: 600; color: var(--primary);">Assign to client:</span>
                                    <select name="client_id" class="form-select form-select-sm" style="max-width: 250px;" required>
                                        <option value="">Select client...</option>
                                        @foreach(\App\Models\Client::orderBy('name')->get() as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-primary">Assign</button>
                                </form>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-envelope-check" style="font-size: 2.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No notices found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $notices->withQueryString()->links() }}</div>

<!-- Add to Proceedings Modal -->
<div class="modal fade" id="proceedingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid #f0f2f5; padding: 20px 24px;">
                <h5 style="font-weight: 700; color: var(--primary); margin: 0; font-size: 1rem;"><i class="bi bi-bank2 me-2" style="color: var(--accent);"></i>Add to Proceedings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('proceedings.store') }}">
                @csrf
                <div class="modal-body" style="padding: 24px;">
                    <input type="hidden" name="description" id="proc-description">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="proc-title" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client</label>
                            <select name="client_id" id="proc-client" class="form-select" required>
                                <option value="">Select Client</option>
                                @foreach(\App\Models\Client::orderBy('name')->get() as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stage</label>
                            <select name="stage" class="form-select" required>
                                <option value="department" selected>Department</option>
                                <option value="commissioner_appeals">Commissioner Appeals</option>
                                <option value="tribunal">Tribunal</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tax Year</label>
                            <input type="text" name="tax_year" class="form-control" value="{{ now()->year - 1 }}-{{ str_pad(now()->year - 2000, 2, '0', STR_PAD_LEFT) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" id="proc-section" class="form-control" placeholder="e.g. 122(9)">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" selected>Pending</option>
                                <option value="adjourned">Adjourned</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hearing Date</label>
                        <input type="date" name="hearing_date" class="form-control">
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f2f5; padding: 16px 24px;">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-bank2 me-1"></i>Add Proceeding</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleNotice(id) {
    var detail = document.getElementById('detail-' + id);
    var row = document.getElementById('row-' + id);

    // Close all other open details
    document.querySelectorAll('.notice-detail.show').forEach(function(el) {
        if (el.id !== 'detail-' + id) {
            el.classList.remove('show');
            el.previousElementSibling.classList.remove('notice-expanded');
        }
    });

    detail.classList.toggle('show');
    row.classList.toggle('notice-expanded');

    // Mark as read visually
    row.classList.remove('unread');
    row.classList.add('read');
}

function addToProceedings(noticeId, subject, clientId) {
    event.stopPropagation();
    document.getElementById('proc-title').value = subject;
    document.getElementById('proc-description').value = 'From FBR Notice #' + noticeId + ': ' + subject;
    if (clientId) {
        document.getElementById('proc-client').value = clientId;
    }

    // Try to extract section from subject
    var sectionMatch = subject.match(/(\d+\([^\)]+\))/);
    if (sectionMatch) {
        document.getElementById('proc-section').value = sectionMatch[1];
    }

    var modal = new bootstrap.Modal(document.getElementById('proceedingsModal'));
    modal.show();
}
</script>
@endsection
