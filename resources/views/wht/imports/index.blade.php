@extends('layouts.app')
@section('title', 'Import Payments')
@section('page-title', 'Import Payments')

@section('content')
@include('wht.partials.agent-switch', ['company' => $company])

@if(!$analysis)
<div class="card" style="max-width: 780px;">
    <div class="card-body">
        <p style="color: var(--n-400); font-size: 0.85rem;">
            Upload a client's payment sheet. Columns are matched by name, so most sheets work as they
            arrive — anything missing is worked out from the payee's defaults.
        </p>

        <div class="alert alert-info" style="font-size: 0.85rem;">
            <i class="bi bi-calculator me-1"></i>
            <strong>Tax is always recalculated here</strong> from the rate matrix for each payment's tax
            period. If the sheet carries its own tax figures they are only compared, never trusted — any
            disagreement is reported so you catch the client's errors rather than importing them.
        </div>

        <form method="POST" action="{{ route('wht.imports.preview') }}" enctype="multipart/form-data">
            @csrf
            <label class="form-label">What kind of sheet is this?</label>
            <select name="kind" class="form-select mb-3">
                <option value="purchases">Vendor / supplier payments — tax from the section rate matrix</option>
                <option value="salaries">Employee salaries — tax from the year's salary slabs</option>
            </select>

            <label class="form-label">Sheet</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
            <div class="form-text">Excel or CSV, up to 8 MB.</div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Upload &amp; preview</button>
                <a href="{{ route('wht.imports.template') }}" class="btn btn-outline-primary">
                    <i class="bi bi-download me-1"></i> Template
                </a>
            </div>
        </form>

        <hr class="my-4">

        <div style="font-size: 0.83rem;">
            <div class="fw-semibold mb-2">Columns it recognises</div>
            <table class="table table-sm mb-0">
                <tr><td class="text-muted" style="width: 34%;">Payee name</td><td>name, payee, vendor, supplier, party</td></tr>
                <tr><td class="text-muted">CNIC / NTN</td><td>cnic, ntn, cnic/ntn, tax number</td></tr>
                <tr><td class="text-muted">Payment date</td><td>date, payment date, paid on</td></tr>
                <tr><td class="text-muted">Amount</td><td>amount, gross, payment, value, invoice amount</td></tr>
                <tr><td class="text-muted">Section <span class="badge bg-light text-dark border">optional</span></td><td>section, payment section, code — else the payee's default</td></tr>
                <tr><td class="text-muted">Tax period <span class="badge bg-light text-dark border">optional</span></td><td>period, month — else taken from the payment date</td></tr>
                <tr><td class="text-muted">Gross or net <span class="badge bg-light text-dark border">optional</span></td><td>basis, type — else the payee's usual mode</td></tr>
                <tr><td class="text-muted">Tax <span class="badge bg-light text-dark border">optional</span></td><td>tax, tax deducted, wht — used only as a cross-check</td></tr>
            </table>
        </div>
    </div>
</div>

@else
@php $s = $analysis['summary']; @endphp

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Ready to import</div>
        <div class="fw-bold text-success" style="font-size: 1.5rem;">{{ $s['ok'] + $s['warning'] }}</div>
        <div class="text-muted" style="font-size: 0.78rem;">of {{ $s['total'] }} rows</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">With notes</div>
        <div class="fw-bold text-warning" style="font-size: 1.5rem;">{{ $s['warning'] }}</div>
        <div class="text-muted" style="font-size: 0.78rem;">check before importing</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100 @if($s['blocked']) border-danger @endif"><div class="card-body">
        <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Blocked</div>
        <div class="fw-bold @if($s['blocked']) text-danger @endif" style="font-size: 1.5rem;">{{ $s['blocked'] }}</div>
        <div class="text-muted" style="font-size: 0.78rem;">will not import</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Tax calculated</div>
        <div class="fw-bold text-primary" style="font-size: 1.5rem;">{{ number_format($s['tax'], 0) }}</div>
        <div class="text-muted" style="font-size: 0.78rem;">on {{ number_format($s['gross'], 0) }} gross</div>
    </div></div></div>
</div>

@if($s['unknown']->isNotEmpty())
<div class="alert alert-danger">
    <strong><i class="bi bi-person-x me-1"></i> Payees not in this agent's list</strong>
    <p class="mb-2 mt-1" style="font-size: 0.85rem;">
        Add these on the <a href="{{ route('wht.parties.index') }}" target="_blank">Vendors &amp; Employees</a> page —
        with the right category and ATL status, since both change the rate — then upload the sheet again.
    </p>
    @foreach($s['unknown'] as $u)
        <span class="badge bg-danger bg-opacity-10 text-danger">{{ $u }}</span>
    @endforeach
</div>
@endif

@if(!empty($analysis['unmapped']))
<div class="alert alert-warning" style="font-size: 0.85rem;">
    <i class="bi bi-question-circle me-1"></i>
    Columns that were not recognised and will be ignored:
    @foreach($analysis['unmapped'] as $u)<code>{{ $u }}</code>@if(!$loop->last), @endif @endforeach
</div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>{{ $filename ?? 'Preview' }}</strong>
        <span class="badge bg-light text-dark border">{{ ($kind ?? 'purchases') === 'salaries' ? 'Salaries' : 'Vendor payments' }}</span>
        <span class="text-muted" style="font-size: 0.82rem;">{{ $s['total'] }} rows read</span>
    </div>
    <div class="table-responsive" style="max-height: 560px;">
        <table class="table table-sm align-middle mb-0">
            <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
                <tr>
                    <th style="width: 46px;">Row</th>
                    <th>Payee</th>
                    <th>Section</th>
                    <th>Period</th>
                    <th>Date</th>
                    <th class="text-end">{{ ($kind ?? 'purchases') === 'salaries' ? 'Total Salary' : 'Gross' }}</th>
                    <th class="text-end">{{ ($kind ?? 'purchases') === 'salaries' ? 'Tax Yr' : 'Rate' }}</th>
                    <th class="text-end">Tax</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analysis['rows'] as $r)
                @php
                    $tone = match($r['status']) {
                        'blocked' => 'table-danger',
                        'warning' => 'table-warning',
                        default   => '',
                    };
                @endphp
                <tr class="{{ $tone }}">
                    <td class="text-muted">{{ $r['line'] }}</td>
                    <td>
                        {{ $r['party_name'] ?? $r['raw_payee'] }}
                        <div class="text-muted" style="font-size: 0.72rem;">
                            {{ $r['cnic_ntn'] }}
                            @if($r['atl_status'] === 'non-filer')
                                <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">Non-filer</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $r['section'] ?: '—' }}</td>
                    <td>{{ $r['period_month'] ?: '—' }}</td>
                    <td>{{ $r['payment_date'] ?: '—' }}</td>
                    <td class="text-end">{{ $r['gross_amount'] !== null ? number_format($r['gross_amount'], 0) : '—' }}</td>
                    <td class="text-end">
                        @if(($kind ?? 'purchases') === 'salaries')
                            {{ $r['tax_year'] ?? '—' }}
                        @else
                            {{ $r['tax_rate'] !== null ? rtrim(rtrim(number_format($r['tax_rate'], 2), '0'), '.') . '%' : '—' }}
                        @endif
                    </td>
                    <td class="text-end fw-semibold">{{ $r['tax_withheld'] !== null ? number_format($r['tax_withheld'], 0) : '—' }}</td>
                    <td style="font-size: 0.76rem; max-width: 320px;">
                        @foreach($r['problems'] as $p)
                            <div class="text-danger">{{ $p }}</div>
                        @endforeach
                        @foreach($r['warnings'] ?? [] as $w)
                            <div class="text-warning-emphasis">{{ $w }}</div>
                        @endforeach
                        @foreach($r['notes'] as $n)
                            <div class="text-muted">{{ $n }}</div>
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<form method="POST" action="{{ route('wht.imports.commit') }}"
      onsubmit="return confirm('Import these payments? They will be created as new entries.')">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                @if($s['warning'])
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="include_warnings" value="1" id="iw" checked>
                    <label class="form-check-label" for="iw">
                        Include the {{ $s['warning'] }} row{{ $s['warning'] === 1 ? '' : 's' }} with notes
                    </label>
                </div>
                @endif
                @if($s['blocked'])
                <div class="text-danger" style="font-size: 0.83rem;">
                    {{ $s['blocked'] }} blocked row{{ $s['blocked'] === 1 ? '' : 's' }} will be skipped.
                </div>
                @endif
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('wht.imports.index') }}" class="btn btn-outline-primary">Start over</a>
                <button class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Import {{ $s['ok'] + $s['warning'] }} payments
                </button>
            </div>
        </div>
    </div>
</form>
@endif
@endsection
