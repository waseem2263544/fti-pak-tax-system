@extends('layouts.app')
@section('title', 'WHT Transactions')
@section('page-title', 'Transactions')

@section('content')
@include('wht.partials.agent-switch', ['company' => $company])

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="btn-group">
        <a href="{{ route('wht.transactions.index', ['kind' => 'purchases']) }}"
           class="btn btn-{{ $isSalary ? 'outline-primary' : 'primary' }}">
            <i class="bi bi-file-earmark-text me-1"></i> Vendor Payments
        </a>
        <a href="{{ route('wht.transactions.index', ['kind' => 'salaries']) }}"
           class="btn btn-{{ $isSalary ? 'primary' : 'outline-primary' }}">
            <i class="bi bi-cash-stack me-1"></i> Salaries
        </a>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('wht.imports.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import from sheet
        </a>
        <a href="{{ $isSalary ? route('wht.salaries.create') : route('wht.purchases.create') }}" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> Add {{ $isSalary ? 'Salary' : 'Payment' }}
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body" style="padding: 14px 20px;">
        <form method="GET">
            <input type="hidden" name="kind" value="{{ $kind }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                           placeholder="{{ $isSalary ? 'Employee' : 'Vendor' }}, PSID, CPR…">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">{{ $isSalary ? 'Employee' : 'Vendor' }}</label>
                    <select class="form-select" name="party_id">
                        <option value="">All</option>
                        @foreach($parties as $p)
                            <option value="{{ $p->id }}" {{ request('party_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                @unless($isSalary)
                <div class="col-md-2">
                    <label class="form-label mb-1">Section</label>
                    <select class="form-select" name="section">
                        <option value="">All</option>
                        @foreach($sections->unique('section') as $s)
                            <option value="{{ $s->section }}" {{ request('section') == $s->section ? 'selected' : '' }}>{{ $s->section }}</option>
                        @endforeach
                    </select>
                </div>
                @endunless
                <div class="col-md-1">
                    <label class="form-label mb-1">From</label>
                    <input type="month" class="form-control" name="from" value="{{ request('from') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label mb-1">To</label>
                    <input type="month" class="form-control" name="to" value="{{ request('to') }}">
                </div>
                <div class="col-md-{{ $isSalary ? 2 : 1 }}">
                    <label class="form-label mb-1">Deposited</label>
                    <select class="form-select" name="deposited">
                        <option value="">Any</option>
                        <option value="yes" {{ request('deposited') == 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ request('deposited') == 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i></button>
                    <a href="{{ route('wht.transactions.index', ['kind' => $kind]) }}" class="btn btn-outline-primary"><i class="bi bi-x-lg"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('wht.transactions.delete-bulk') }}" id="bulkForm"
      onsubmit="return confirmBulkDelete()">
@csrf
<input type="hidden" name="kind" value="{{ $kind }}">

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 34px;"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                    <th>{{ $isSalary ? 'Month' : 'Period' }}</th>
                    <th>{{ $isSalary ? 'Employee' : 'Vendor' }}</th>
                    @unless($isSalary)<th>Section</th>@endunless
                    <th class="text-end">{{ $isSalary ? 'Taxable' : 'Gross' }}</th>
                    @if($isSalary)<th class="text-end">Exempt</th>@endif
                    <th class="text-end">{{ $isSalary ? 'Total' : 'Rate' }}</th>
                    <th class="text-end">Tax</th>
                    <th class="text-end">Net</th>
                    <th>Challan</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                @php $party = $isSalary ? $r->employee : $r->party; @endphp
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input row-check" name="ids[]" value="{{ $r->id }}"
                               data-tax="{{ $isSalary ? $r->tax_deducted : $r->tax_withheld }}"
                               data-cpr="{{ filled($r->cpr_no) ? 1 : 0 }}">
                    </td>
                    <td>{{ ($isSalary ? $r->salary_month : $r->period_month)?->format('M Y') }}</td>
                    <td>
                        {{ $party?->name ?? '—' }}
                        <div class="text-muted" style="font-size: 0.72rem;">
                            {{ $party?->cnic_ntn }}
                            @if($party?->atl_status === 'non-filer')
                                <span class="badge bg-warning text-dark" style="font-size: 0.58rem;">Non-filer</span>
                            @endif
                        </div>
                    </td>
                    @unless($isSalary)<td>{{ $r->section ?: '—' }}</td>@endunless
                    <td class="text-end">{{ number_format($isSalary ? $r->taxable_salary : $r->gross_amount, 0) }}</td>
                    @if($isSalary)<td class="text-end">{{ number_format($r->exempt_amount, 0) }}</td>@endif
                    <td class="text-end">
                        @if($isSalary)
                            {{ number_format($r->total_salary, 0) }}
                        @else
                            {{ rtrim(rtrim(number_format($r->tax_rate, 2), '0'), '.') }}%
                            @if($r->rate_source === 'manual')
                                <i class="bi bi-pencil-fill text-muted" style="font-size: 0.6rem;" title="Manually overridden"></i>
                            @endif
                        @endif
                    </td>
                    <td class="text-end fw-semibold">{{ number_format($isSalary ? $r->tax_deducted : $r->tax_withheld, 0) }}</td>
                    <td class="text-end">{{ number_format($isSalary ? $r->final_net_payment : $r->net_payment, 0) }}</td>
                    <td>
                        @if($r->cpr_no)
                            <span class="badge bg-success bg-opacity-10 text-success">{{ $r->cpr_no }}</span>
                        @elseif($r->psid_no)
                            <span class="badge bg-info bg-opacity-10 text-info">{{ $r->psid_no }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('wht.reports.certificate', ['type' => $isSalary ? 'salary' : 'purchase', 'id' => $r->id]) }}"
                           target="_blank" class="btn btn-sm btn-outline-primary" title="Certificate"><i class="bi bi-file-earmark-pdf"></i></a>
                        <a href="{{ $isSalary ? route('wht.salaries.edit', $r) : route('wht.purchases.edit', $r) }}"
                           class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        {{-- No per-row delete form here: it would nest inside the bulk form above,
                             which browsers resolve by discarding the inner <form> while keeping its
                             hidden _method=DELETE — so the trash icon submitted the bulk form as a
                             DELETE and got a 405. Tick the row and use Delete selected instead. --}}
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" class="text-center text-muted py-5">
                    Nothing matches these filters.
                </td></tr>
                @endforelse
            </tbody>
            @if($rows->isNotEmpty())
            <tfoot>
                <tr class="fw-semibold">
                    <td colspan="{{ $isSalary ? 3 : 4 }}" class="text-end">Totals (filtered)</td>
                    <td class="text-end">{{ number_format($totals->g, 0) }}</td>
                    @if($isSalary)<td></td><td></td>@else<td></td>@endif
                    <td class="text-end">{{ number_format($totals->t, 0) }}</td>
                    <td class="text-end">{{ number_format($totals->n, 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@if($rows->isNotEmpty())
<div class="card mt-3" id="bulkBar" style="display: none; position: sticky; bottom: 12px;">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="fw-bold"><span id="selCount">0</span> selected</span>
            <span class="text-muted ms-2">·</span>
            <span class="ms-2">Tax <span class="fw-bold" id="selTax">0</span></span>
            <span id="cprWarn" class="badge bg-warning text-dark ms-2 d-none"></span>
        </div>
        <button class="btn btn-outline-danger">
            <i class="bi bi-trash me-1"></i> Delete selected
        </button>
    </div>
</div>
@endif
</form>

<div class="mt-3">{{ $rows->links() }}</div>
@endsection

@section('scripts')
<script>
(function () {
    const checks = () => Array.from(document.querySelectorAll('.row-check'));

    function refresh() {
        const sel = checks().filter(c => c.checked);
        const tax = sel.reduce((t, c) => t + parseFloat(c.dataset.tax || 0), 0);
        const cpr = sel.filter(c => c.dataset.cpr === '1').length;

        document.getElementById('selCount').textContent = sel.length;
        document.getElementById('selTax').textContent = tax.toLocaleString(undefined, {maximumFractionDigits: 0});
        document.getElementById('bulkBar').style.display = sel.length ? '' : 'none';

        // Deleting an entry that has been deposited is the risky case, so say so.
        const warn = document.getElementById('cprWarn');
        warn.classList.toggle('d-none', cpr === 0);
        warn.textContent = cpr ? cpr + ' already deposited (has a CPR)' : '';

        const all = document.getElementById('checkAll');
        all.checked = sel.length > 0 && sel.length === checks().length;
        all.indeterminate = sel.length > 0 && sel.length < checks().length;
    }

    document.getElementById('checkAll').addEventListener('change', e => {
        checks().forEach(c => c.checked = e.target.checked);
        refresh();
    });

    document.addEventListener('change', e => {
        if (e.target.classList.contains('row-check')) refresh();
    });

    window.confirmBulkDelete = function () {
        const sel = checks().filter(c => c.checked);
        const cpr = sel.filter(c => c.dataset.cpr === '1').length;
        let msg = 'Delete ' + sel.length + ' entries? This cannot be undone.';
        if (cpr) msg += '\n\n' + cpr + ' of them record tax already deposited with FBR. '
                     + 'If the period has been filed, your records will no longer match the return.';
        return confirm(msg);
    };

    refresh();
})();
</script>
