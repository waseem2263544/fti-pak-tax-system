@extends('layouts.app')

@section('page-title', 'FBR Notices')

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <h4>FBR Notice Management</h4>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select form-select-sm" onchange="filterNotices()">
                    <option value="">All Status</option>
                    <option value="new">New</option>
                    <option value="reviewed">Reviewed</option>
                    <option value="resolved">Resolved</option>
                    <option value="escalated">Escalated</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Year</label>
                <select class="form-select form-select-sm" onchange="filterNotices()">
                    <option value="">All Years</option>
                    @foreach($taxYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Section</label>
                <select class="form-select form-select-sm" onchange="filterNotices()">
                    <option value="">All Sections</option>
                    @foreach($sections as $section)
                    <option value="{{ $section }}">{{ $section }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-sm btn-outline-secondary w-100" onclick="location.reload()">Reset Filters</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Tax Year</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notices as $notice)
                    <tr @if($notice->is_escalated) class="escalated" @endif>
                        <td>
                            <strong>{{ Str::limit($notice->subject, 40) }}</strong>
                            @if($notice->is_escalated)
                            <br><span class="badge bg-danger">Escalated</span>
                            @endif
                        </td>
                        <td>{{ $notice->notice_section ?? 'General' }}</td>
                        <td>{{ $notice->tax_year ?? '-' }}</td>
                        <td>{{ $notice->client?->name ?? 'Unassigned' }}</td>
                        <td><span class="badge bg-info">{{ $notice->status }}</span></td>
                        <td>{{ $notice->email_received_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('fbr-notices.show', $notice) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No notices found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $notices->links() }}
    </div>
</div>

<script>
function filterNotices() {
    // Build query string from filter values
    const status = document.querySelector('select').value;
    // Implement filtering logic
}
</script>
@endsection
