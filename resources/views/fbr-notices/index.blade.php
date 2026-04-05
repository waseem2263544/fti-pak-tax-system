@extends('layouts.app')
@section('title', 'FBR Notices')
@section('page-title', 'FBR Notices')

@section('styles')
<style>
    .notice-row { cursor: pointer; transition: all 0.15s; }
    .notice-row:hover { background: #fafbfc !important; }
    .notice-row.unread td:first-child { border-left: 3px solid var(--accent); }
    .notice-row.unread .notice-subject { font-weight: 700; }
    .notice-expanded { background: #fafbfc !important; }
    .notice-detail { display: none; background: #f8f9fb; }
    .notice-detail.show { display: table-row; }
    .notice-body { padding: 20px 24px; font-size: 0.85rem; color: #4b5563; line-height: 1.7; max-height: 300px; overflow-y: auto; }
    .tab-btn { padding: 10px 20px; font-size: 0.85rem; font-weight: 600; border: none; background: none; color: #9ca3af; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; }
    .tab-btn:hover { color: var(--primary); }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--accent); }
    .tab-count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; margin-left: 6px; padding: 0 6px; }
</style>
@endsection

@section('content')
<!-- Tabs -->
<div class="card mb-4">
    <div class="d-flex align-items-center justify-content-between" style="padding: 0 20px; border-bottom: 1px solid #f0f2f5;">
        <div class="d-flex">
            <a href="{{ route('fbr-notices.index', array_merge(request()->except('filter'), ['filter' => 'pending'])) }}" class="tab-btn {{ $filter == 'pending' ? 'active' : '' }}">
                <i class="bi bi-inbox me-1"></i> Needs Attention
                <span class="tab-count" style="background: {{ $pendingCount > 0 ? '#fef2f2' : '#f3f4f6' }}; color: {{ $pendingCount > 0 ? '#dc2626' : '#9ca3af' }};">{{ $pendingCount }}</span>
            </a>
            <a href="{{ route('fbr-notices.index', array_merge(request()->except('filter'), ['filter' => 'actioned'])) }}" class="tab-btn {{ $filter == 'actioned' ? 'active' : '' }}">
                <i class="bi bi-check-circle me-1"></i> Actioned
                <span class="tab-count" style="background: #d1fae5; color: #065f46;">{{ $actionedCount }}</span>
            </a>
            <a href="{{ route('fbr-notices.index', array_merge(request()->except('filter'), ['filter' => 'all'])) }}" class="tab-btn {{ $filter == 'all' ? 'active' : '' }}">
                <i class="bi bi-list me-1"></i> All
            </a>
        </div>
        <a href="/fetch-fbr-now.php" class="btn btn-accent btn-sm"><i class="bi bi-cloud-download me-1"></i>Fetch Now</a>
    </div>
    <!-- Filters row -->
    <div style="padding: 12px 20px;">
        <form method="GET" action="{{ route('fbr-notices.index') }}">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="tax_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Tax Years</option>
                        @foreach($taxYears as $year)
                            <option value="{{ $year }}" {{ request('tax_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="section" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section }}" {{ request('section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    @if(request()->hasAny(['tax_year', 'section']))
                        <a href="{{ route('fbr-notices.index', ['filter' => $filter]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-x-lg me-1"></i>Clear Filters</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Notices Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Notice</th>
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
                <tr class="notice-row {{ $notice->status == 'new' ? 'unread' : '' }}"
                    id="row-{{ $notice->id }}" onclick="toggleNotice({{ $notice->id }})">
                    <td>
                        <div class="notice-subject" style="font-size: 0.88rem; color: var(--primary);">{{ Str::limit($notice->subject, 55) }}</div>
                    </td>
                    <td><span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">{{ $notice->notice_section ?? 'General' }}</span></td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $notice->tax_year ?? '-' }}</td>
                    <td>
                        @if($notice->client)
                            <a href="{{ route('clients.show', $notice->client) }}" style="color: var(--primary); font-weight: 500; text-decoration: none;" onclick="event.stopPropagation();">{{ Str::limit($notice->client->name, 25) }}</a>
                        @else
                            <span style="color: #d1d5db;">Unassigned</span>
                        @endif
                    </td>
                    <td>
                        @if($notice->status == 'new')
                            <span class="badge" style="background: var(--accent); color: var(--primary); font-weight: 700;">Unread</span>
                        @elseif($notice->status == 'reviewed')
                            <span class="badge" style="background: #fef3c7; color: #92400e;">Read</span>
                        @else
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Actioned</span>
                        @endif
                    </td>
                    <td style="font-size: 0.82rem; color: #6b7280;">{{ $notice->email_received_at instanceof \Carbon\Carbon ? $notice->email_received_at->format('M d') : $notice->email_received_at }}</td>
                    <td class="text-end" onclick="event.stopPropagation();">
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleNotice({{ $notice->id }})" title="Read"><i class="bi bi-book"></i></button>
                            @if(in_array($notice->status, ['new', 'reviewed']))
                            <button class="btn btn-sm btn-accent" onclick="addToProceedings({{ $notice->id }}, '{{ addslashes($notice->subject) }}', {{ $notice->client_id ?? 'null' }}, '{{ $notice->tax_year }}')" title="Add to Proceedings"><i class="bi bi-bank2"></i></button>
                            <form method="POST" action="{{ route('fbr-notices.dismiss', $notice) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary" title="Dismiss - no action needed" onclick="return confirm('Dismiss this notice?')"><i class="bi bi-x-lg"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                <!-- Expandable detail -->
                <tr class="notice-detail" id="detail-{{ $notice->id }}">
                    <td colspan="7" style="padding: 0;">
                        <div class="notice-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 style="font-weight: 700; color: var(--primary); margin: 0;">{{ $notice->subject }}</h6>
                                    <div style="font-size: 0.78rem; color: #9ca3af; margin-top: 4px;">
                                        From: {{ $notice->sender_email }} &middot;
                                        {{ $notice->email_received_at instanceof \Carbon\Carbon ? $notice->email_received_at->format('M d, Y H:i') : $notice->email_received_at }}
                                    </div>
                                </div>
                                @if(in_array($notice->status, ['new', 'reviewed']))
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-accent" onclick="addToProceedings({{ $notice->id }}, '{{ addslashes($notice->subject) }}', {{ $notice->client_id ?? 'null' }}, '{{ $notice->tax_year }}')">
                                        <i class="bi bi-bank2 me-1"></i>Add to Proceedings
                                    </button>
                                    <form method="POST" action="{{ route('fbr-notices.dismiss', $notice) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Dismiss?')"><i class="bi bi-x-lg me-1"></i>Dismiss</button>
                                    </form>
                                </div>
                                @endif
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
                        @if($filter == 'pending')
                            <i class="bi bi-check-circle" style="font-size: 2.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                            All caught up! No notices need attention.
                        @elseif($filter == 'actioned')
                            <i class="bi bi-archive" style="font-size: 2.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                            No actioned notices yet.
                        @else
                            <i class="bi bi-envelope" style="font-size: 2.5rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                            No notices found.
                        @endif
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid #f0f2f5; padding: 20px 24px;">
                <h5 style="font-weight: 700; color: var(--primary); margin: 0; font-size: 1rem;"><i class="bi bi-bank2 me-2" style="color: var(--accent);"></i>Add to Proceedings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('proceedings.store') }}">
                @csrf
                <input type="hidden" name="from_fbr" value="1">
                <input type="hidden" name="fbr_notice_id" id="proc-notice-id">
                <input type="hidden" name="description" id="proc-description">
                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="proc-title" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Client</label>
                            <select name="client_id" id="proc-client" class="form-select" required>
                                <option value="">Select Client</option>
                                @foreach(\App\Models\Client::orderBy('name')->get() as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stage</label>
                            <select name="stage" class="form-select" required>
                                <option value="department" selected>Department</option>
                                <option value="commissioner_appeals">Commissioner Appeals</option>
                                <option value="tribunal">Tribunal</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Assigned To</label>
                            <select name="assigned_to" id="proc-assigned" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach(\App\Models\User::orderBy('name')->get() as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tax Year</label>
                            <input type="text" name="tax_year" id="proc-taxyear" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" id="proc-section" class="form-control" placeholder="e.g. 122(9)">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" selected>Pending</option>
                                <option value="adjourned">Adjourned</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Hearing Date</label>
                            <input type="date" name="hearing_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
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

    document.querySelectorAll('.notice-detail.show').forEach(function(el) {
        if (el.id !== 'detail-' + id) {
            el.classList.remove('show');
            el.previousElementSibling.classList.remove('notice-expanded');
        }
    });

    detail.classList.toggle('show');
    row.classList.toggle('notice-expanded');

    // Mark as read via AJAX
    if (row.classList.contains('unread')) {
        row.classList.remove('unread');
        fetch('/fbr-notices/' + id + '/mark-read', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'}
        });
    }
}

function addToProceedings(noticeId, subject, clientId, taxYear) {
    event.stopPropagation();
    document.getElementById('proc-notice-id').value = noticeId;
    document.getElementById('proc-title').value = subject;
    document.getElementById('proc-description').value = 'From FBR Notice: ' + subject;
    document.getElementById('proc-taxyear').value = taxYear || '';

    if (clientId) {
        document.getElementById('proc-client').value = clientId;
    }

    var sectionMatch = subject.match(/(\d+\([^\)]+\))/);
    if (sectionMatch) {
        document.getElementById('proc-section').value = sectionMatch[1];
    }

    var modal = new bootstrap.Modal(document.getElementById('proceedingsModal'));
    modal.show();
}
</script>
@endsection
