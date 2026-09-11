@extends('layouts.app')
@section('title', 'Prepare PSID')
@section('page-title', 'Prepare PSID')

@section('content')
@include('wht.partials.agent-bar')

<form method="POST" id="psidForm">
@csrf
<input type="hidden" name="kind" value="{{ $kind }}">

<div class="card mb-3">
    <div class="card-body" style="padding: 14px 20px;">
        <div class="d-flex align-items-end gap-3 flex-wrap">
            <div class="btn-group" role="group">
                <a href="{{ route('wht.psid.index', ['kind' => 'purchases', 'month' => $month, 'show' => $show]) }}"
                   class="btn btn-{{ $kind === 'purchases' ? 'primary' : 'outline-primary' }}">Vendor Payments</a>
                <a href="{{ route('wht.psid.index', ['kind' => 'salaries', 'month' => $month, 'show' => $show]) }}"
                   class="btn btn-{{ $kind === 'salaries' ? 'primary' : 'outline-primary' }}">Salaries</a>
            </div>

            <div>
                <label class="form-label mb-1">Tax Period</label>
                <input type="month" form="filterForm" name="month" class="form-control" value="{{ $month }}">
            </div>

            <div>
                <label class="form-label mb-1">Show</label>
                <select form="filterForm" name="show" class="form-select">
                    <option value="unassigned" {{ $show === 'unassigned' ? 'selected' : '' }}>Not yet on a PSID</option>
                    <option value="all" {{ $show === 'all' ? 'selected' : '' }}>All entries</option>
                </select>
            </div>

            <button form="filterForm" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Apply</button>

            <div class="ms-auto text-end">
                @if($outstanding > 0)
                    <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size: 0.8rem;">
                        {{ $outstanding }} entr{{ $outstanding === 1 ? 'y' : 'ies' }} in this period still without a PSID
                    </span>
                @else
                    <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 0.8rem;">
                        Everything in this period is on a PSID
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="table-responsive" style="max-height: 520px;">
        <table class="table table-sm align-middle mb-0">
            <thead style="position: sticky; top: 0; background: #fff; z-index: 2;">
                <tr>
                    <th style="width: 36px;"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                    <th>Period</th>
                    <th>Payee</th>
                    <th>Section</th>
                    <th>Payment Date</th>
                    <th class="text-end">{{ $kind === 'salaries' ? 'Total Salary' : 'Gross' }}</th>
                    <th class="text-end">Tax</th>
                    <th>PSID</th>
                    <th>CPR</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                @php $assigned = filled($r['psid_no']); @endphp
                <tr class="{{ $assigned ? 'table-light text-muted' : '' }}">
                    <td>
                        <input type="checkbox" class="form-check-input row-check" name="ids[]" value="{{ $r['id'] }}"
                               data-tax="{{ $r['tax_withheld'] }}" data-assigned="{{ $assigned ? 1 : 0 }}"
                               {{ $assigned ? '' : 'checked' }}>
                    </td>
                    <td>{{ $r['period'] }}</td>
                    <td>
                        {{ $r['payee_name'] ?? '—' }}
                        <div class="text-muted" style="font-size: 0.72rem;">
                            {{ $r['payee_cnic_ntn'] }}
                            @if($r['atl_status'] === 'non-filer')
                                <span class="badge bg-warning text-dark" style="font-size: 0.58rem;">Non-filer</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $r['section'] ?: '—' }}</td>
                    <td>{{ $r['payment_date'] }}</td>
                    <td class="text-end">{{ number_format($r['gross_amount'], 0) }}</td>
                    <td class="text-end fw-semibold">{{ number_format($r['tax_withheld'], 0) }}</td>
                    <td>
                        @if($r['psid_no'])
                            <span class="badge bg-info bg-opacity-10 text-info">{{ $r['psid_no'] }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($r['cpr_no'])
                            <span class="badge bg-success bg-opacity-10 text-success">{{ $r['cpr_no'] }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-5">
                    No {{ $kind === 'salaries' ? 'salary' : 'vendor' }} entries match this filter.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($rows->isNotEmpty())
<div class="card" style="position: sticky; bottom: 12px;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <span class="fw-bold" style="font-size: 1.1rem;"><span id="selCount">0</span> selected</span>
                <span class="text-muted ms-2">·</span>
                <span class="ms-2">Tax <span class="fw-bold text-primary" id="selTax">0</span></span>
                <span id="reassignWarn" class="badge bg-warning text-dark ms-2 d-none"></span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectBy('unassigned')">Select unassigned</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectBy('all')">Select all</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectBy('none')">Clear</button>
            </div>
        </div>

        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">1. Upload file</label>
                <button class="btn btn-success w-100" formaction="{{ route('wht.psid.download') }}">
                    <i class="bi bi-file-earmark-excel me-1"></i> Generate for IRIS
                </button>
            </div>

            <div class="col-md-4">
                <label class="form-label">2. PSID returned by IRIS</label>
                <div class="input-group">
                    <input type="text" name="psid_no" class="form-control" placeholder="PSID number">
                    <button class="btn btn-primary" formaction="{{ route('wht.psid.assign-psid') }}">Assign</button>
                </div>
            </div>

            <div class="col-md-5">
                <label class="form-label">3. CPR once paid</label>
                <div class="input-group">
                    <input type="text" name="cpr_no" class="form-control" placeholder="CPR number">
                    <input type="date" name="cpr_date" class="form-control" style="max-width: 150px;">
                    <button class="btn btn-primary" formaction="{{ route('wht.psid.assign-cpr') }}">Record</button>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-sm btn-outline-danger" formaction="{{ route('wht.psid.clear') }}"
                    name="what" value="cpr" onclick="return confirm('Clear the CPR on the selected entries?')">Clear CPR</button>
            <button class="btn btn-sm btn-outline-danger" formaction="{{ route('wht.psid.clear') }}"
                    name="what" value="psid" onclick="return confirm('Clear the PSID and CPR on the selected entries?')">Clear PSID &amp; CPR</button>
        </div>
    </div>
</div>
@endif
</form>

<form method="GET" id="filterForm">
    <input type="hidden" name="kind" value="{{ $kind }}">
</form>

@if(Auth::user()->hasRole('admin'))
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;"
         onclick="document.getElementById('layoutBox').classList.toggle('d-none')">
        <strong>Upload file column layout</strong>
        <span class="badge bg-{{ $layoutIsCustom ? 'warning' : 'success' }} bg-opacity-10 text-{{ $layoutIsCustom ? 'warning' : 'success' }}">
            {{ $layoutIsCustom ? 'Customised' : 'Matches FBR ePayments Import Template' }}
        </span>
    </div>
    <div class="card-body d-none" id="layoutBox">
        <p class="text-muted" style="font-size: 0.85rem;">
            These columns match FBR's <strong>ePayments Import Template</strong> exactly — ten columns, header on
            row 1, data from row 2, sheet named Sheet1. IRIS reads the grid literally, so do not add a title block
            or a totals row. If FBR revises the template, paste its header row in here; changes apply immediately.
        </p>
        <form method="POST" action="{{ route('wht.psid.layout') }}">
            @csrf
            <textarea name="layout" class="form-control font-monospace" rows="16" style="font-size: 0.78rem;">{{ $layoutJson }}</textarea>
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary">Save layout</button>
                @if($layoutIsCustom)
                <button type="submit" formaction="{{ route('wht.psid.layout-reset') }}" class="btn btn-outline-primary"
                        onclick="return confirm('Reset to the FBR default layout?')">Reset to default</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
(function () {
    const checks = () => Array.from(document.querySelectorAll('.row-check'));

    function refresh() {
        const sel = checks().filter(c => c.checked);
        const tax = sel.reduce((t, c) => t + parseFloat(c.dataset.tax || 0), 0);

        document.getElementById('selCount').textContent = sel.length;
        document.getElementById('selTax').textContent =
            tax.toLocaleString(undefined, {maximumFractionDigits: 0});

        // Selecting something already on a challan is the duplication risk, so say so.
        const already = sel.filter(c => c.dataset.assigned === '1').length;
        const warn = document.getElementById('reassignWarn');
        warn.classList.toggle('d-none', already === 0);
        warn.textContent = already
            ? already + ' already on a PSID — assigning again will reassign ' + (already === 1 ? 'it' : 'them')
            : '';

        const all = document.getElementById('checkAll');
        all.checked = sel.length > 0 && sel.length === checks().length;
        all.indeterminate = sel.length > 0 && sel.length < checks().length;
    }

    window.selectBy = function (mode) {
        checks().forEach(c => {
            c.checked = mode === 'all' || (mode === 'unassigned' && c.dataset.assigned === '0');
        });
        refresh();
    };

    document.getElementById('checkAll').addEventListener('change', e => {
        checks().forEach(c => c.checked = e.target.checked);
        refresh();
    });

    document.addEventListener('change', e => {
        if (e.target.classList.contains('row-check')) refresh();
    });

    refresh();
})();
</script>
@endsection
