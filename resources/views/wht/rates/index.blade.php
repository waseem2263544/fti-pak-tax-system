@extends('layouts.app')
@section('title', 'WHT Tax Rates')
@section('page-title', 'Withholding Tax Rates')

@section('content')
<div class="alert alert-info d-flex align-items-start" style="font-size: 0.85rem;">
    <i class="bi bi-info-circle me-2 mt-1"></i>
    <div>
        These rates are <strong>global</strong> — one matrix serves every withholding agent, because the rates are statutory.
        Each row is valid for a range of <strong>tax period months</strong>. A transaction uses the rate in force for
        <em>its</em> period, so depositing a June liability in August still applies June's rate, and a Finance Act change
        never rewrites earlier months.
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p style="color: var(--n-400); font-size: 0.85rem; margin: 0;">Section × category × ATL status, versioned by month.</p>
    <a href="{{ route('wht.rates.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> Add Rate</a>
</div>

<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Section</label>
                    <select class="form-select" name="section">
                        <option value="">All sections</option>
                        @foreach($sections as $s)
                            <option value="{{ $s->section }}" {{ request('section') == $s->section ? 'selected' : '' }}>
                                {{ $s->section }} — {{ $s->payment_nature }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All</option>
                        @foreach(['company' => 'Company', 'individual' => 'Individual', 'aop' => 'AOP'] as $v => $l)
                            <option value="{{ $v }}" {{ request('category') == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">ATL Status</label>
                    <select class="form-select" name="atl_status">
                        <option value="">All</option>
                        <option value="filer" {{ request('atl_status') == 'filer' ? 'selected' : '' }}>Filer</option>
                        <option value="non-filer" {{ request('atl_status') == 'non-filer' ? 'selected' : '' }}>Non-filer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">In force during</label>
                    <input type="month" class="form-control" name="month" value="{{ request('month') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i></button>
                    <a href="{{ route('wht.rates.index') }}" class="btn btn-outline-primary"><i class="bi bi-x-lg"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Goods / Service Type</th>
                    <th>Category</th>
                    <th>ATL</th>
                    <th class="text-end">Rate</th>
                    <th>Effective</th>
                    <th>Notes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rates as $r)
                @php $current = $r->effective_to === null; @endphp
                <tr>
                    <td class="fw-semibold">{{ $r->section }}</td>
                    <td>{{ $r->goods_type ?: '—' }}</td>
                    <td>{{ $r->category === 'aop' ? 'AOP' : ucfirst($r->category) }}</td>
                    <td>
                        @if($r->atl_status === 'filer')
                            <span class="badge bg-success bg-opacity-10 text-success">Filer</span>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning">Non-filer</span>
                        @endif
                    </td>
                    <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($r->rate, 3), '0'), '.') }}%</td>
                    <td>
                        {{ $r->effective_from->format('M Y') }} →
                        @if($current)
                            <span class="badge bg-primary" style="font-size: 0.65rem;">current</span>
                        @else
                            {{ $r->effective_to->format('M Y') }}
                        @endif
                    </td>
                    <td class="text-muted" style="font-size: 0.8rem;">{{ $r->notes ?: '—' }}</td>
                    <td class="text-end text-nowrap">
                        @if($current)
                        <button class="btn btn-sm btn-outline-primary" title="New rate from a given month"
                                onclick='openSupersede(@json($r))'><i class="bi bi-calendar-plus"></i></button>
                        @endif
                        <a href="{{ route('wht.rates.edit', $r) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('wht.rates.destroy', $r) }}" class="d-inline"
                              onsubmit="return confirm('Delete this rate rule? Recorded transactions keep the rate they were saved with.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-5">
                    No rates defined yet. Add them here, or import them from the old portal.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $rates->links() }}</div>

{{-- Supersede: close the current row and open a new one --}}
<div class="modal fade" id="supersedeModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="supersedeForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">New Rate From a Given Month</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size: 0.85rem;">
                    The current rule is closed off the month before, and a new one starts from the month you pick.
                    Nothing already recorded changes.
                </p>
                <div class="mb-3">
                    <label class="form-label">Rule</label>
                    <input type="text" class="form-control" id="s_desc" readonly>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">New Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.001" min="0" max="100" name="rate" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="month" name="month" class="form-control" value="{{ now()->format('Y-m') }}" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const supersedeModal = new bootstrap.Modal(document.getElementById('supersedeModal'));

function openSupersede(r) {
    const form = document.getElementById('supersedeForm');
    form.action = '{{ url('wht/rates') }}/' + r.id + '/supersede';
    document.getElementById('s_desc').value =
        r.section + ' · ' + r.category + ' · ' + r.atl_status + ' · currently ' + parseFloat(r.rate) + '%';
    supersedeModal.show();
}
</script>
@endsection
