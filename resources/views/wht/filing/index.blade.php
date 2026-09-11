@extends('layouts.app')
@section('title', 'WHT Filing')
@section('page-title', 'Filing')

@section('content')
@include('wht.partials.agent-switch', ['company' => $company])

@php
    $t = $recon['totals'];
    $x = $recon['exceptions'];
    $fullyDeposited = abs($t['gap']) < 0.01 && $t['tax'] > 0;
@endphp

<div class="card mb-3">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" class="d-flex align-items-end gap-2 flex-wrap">
            <div>
                <label class="form-label mb-1">Tax Year</label>
                <select name="tax_year" class="form-select">
                    @foreach($taxYears as $y)
                        <option value="{{ $y }}" {{ ($period->taxYear ?? 0) == $y ? 'selected' : '' }}>TY{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1">Quarter</label>
                <select name="quarter" class="form-select">
                    @foreach([1 => 'Q1 · Jul–Sep', 2 => 'Q2 · Oct–Dec', 3 => 'Q3 · Jan–Mar', 4 => 'Q4 · Apr–Jun'] as $q => $lbl)
                        <option value="{{ $q }}" {{ ($period->quarter ?? 0) == $q ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Show</button>

            <div class="vr mx-2"></div>

            <a href="{{ route('wht.reports.statement-filing', $period->query()) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> FBR Statement (.xlsm)
            </a>
            <a href="{{ route('wht.reports.statement-excel', $period->query()) }}" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-excel me-1"></i> Working Copy
            </a>

            <div class="ms-auto" style="font-size: 0.8rem;">
                <span class="text-muted">Statements are filed quarterly; deposits are made monthly.</span>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Tax Withheld</div>
        <div class="fw-bold" style="font-size: 1.45rem;">{{ number_format($t['tax'], 0) }}</div>
        <div class="text-muted" style="font-size: 0.78rem;">{{ $t['entries'] }} entries · {{ $period->label }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Deposited</div>
        <div class="fw-bold text-success" style="font-size: 1.45rem;">{{ number_format($t['deposited'], 0) }}</div>
        <div class="text-muted" style="font-size: 0.78rem;">CPR recorded</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100 @if(abs($t['gap']) >= 0.01) border-warning @endif"><div class="card-body">
        <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Gap</div>
        <div class="fw-bold @if(abs($t['gap']) >= 0.01) text-warning @else text-success @endif" style="font-size: 1.45rem;">
            {{ number_format($t['gap'], 0) }}
        </div>
        <div class="text-muted" style="font-size: 0.78rem;">
            {{ $fullyDeposited ? 'fully reconciled' : 'not yet deposited' }}
        </div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Gross Amount</div>
        <div class="fw-bold" style="font-size: 1.45rem;">{{ number_format($t['gross'], 0) }}</div>
        <div class="text-muted" style="font-size: 0.78rem;">payments &amp; salaries</div>
    </div></div></div>
</div>

@if($x['no_psid'] || $x['psid_no_cpr'] || $x['uncoded']->isNotEmpty())
<div class="alert alert-warning" style="font-size: 0.87rem;">
    <strong><i class="bi bi-exclamation-triangle me-1"></i> Holding the gap open</strong>
    <ul class="mb-0 mt-2">
        @if($x['no_psid'])
            <li>{{ $x['no_psid'] }} {{ $x['no_psid'] === 1 ? 'entry has' : 'entries have' }} no PSID —
                {{ number_format($x['no_psid_tax'], 0) }} not yet on a challan.
                <a href="{{ route('wht.deposit.index') }}">Raise one</a>.</li>
        @endif
        @if($x['psid_no_cpr'])
            <li>{{ $x['psid_no_cpr'] }} {{ $x['psid_no_cpr'] === 1 ? 'entry has' : 'entries have' }} a PSID but no CPR —
                {{ number_format($x['psid_no_cpr_tax'], 0) }} raised but not paid, or paid without the CPR recorded.</li>
        @endif
        @if($x['uncoded']->isNotEmpty())
            <li>No FBR code for: {{ $x['uncoded']->implode(', ') }} — these rows cannot go on a statement.
                <a href="{{ route('wht.sections.index') }}">Add the code</a>.</li>
        @endif
    </ul>
</div>
@endif

<div class="card mb-3">
    <div class="card-header"><strong>By FBR code — {{ $period->label }}</strong></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Section</th>
                    <th>Nature of Payment</th>
                    <th class="text-center">Payees</th>
                    <th class="text-end">Gross Amount</th>
                    <th class="text-end">Tax Withheld</th>
                    <th class="text-end">Deposited</th>
                    <th class="text-end">Gap</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recon['rows'] as $r)
                <tr @if(abs($r['gap']) >= 0.01) class="table-warning" @endif>
                    <td><code>{{ $r['code'] ?: '—' }}</code></td>
                    <td class="fw-semibold">{{ $r['section'] }}</td>
                    <td>{{ $r['nature'] ?: '—' }}</td>
                    <td class="text-center">{{ $r['payees'] }}</td>
                    <td class="text-end">{{ number_format($r['gross'], 0) }}</td>
                    <td class="text-end fw-semibold">{{ number_format($r['tax'], 0) }}</td>
                    <td class="text-end text-success">{{ number_format($r['deposited'], 0) }}</td>
                    <td class="text-end @if(abs($r['gap']) >= 0.01) fw-semibold text-warning @else text-muted @endif">
                        {{ number_format($r['gap'], 0) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-5">Nothing recorded for {{ $period->label }}.</td></tr>
                @endforelse
            </tbody>
            @if($recon['rows']->isNotEmpty())
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">Total</td>
                    <td class="text-end">{{ number_format($t['gross'], 0) }}</td>
                    <td class="text-end">{{ number_format($t['tax'], 0) }}</td>
                    <td class="text-end text-success">{{ number_format($t['deposited'], 0) }}</td>
                    <td class="text-end @if(abs($t['gap']) >= 0.01) text-warning @endif">{{ number_format($t['gap'], 0) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><strong>Month by month</strong>
                <span class="text-muted" style="font-size: 0.8rem;">deposits are monthly</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr>
                        <th>Month</th>
                        <th class="text-center">Entries</th>
                        <th class="text-end">Withheld</th>
                        <th class="text-end">Deposited</th>
                        <th class="text-end">Gap</th>
                    </tr></thead>
                    <tbody>
                        @foreach($recon['monthly'] as $m)
                        <tr @if($m['entries'] === 0) class="text-muted" @endif>
                            <td>{{ $m['label'] }}</td>
                            <td class="text-center">{{ $m['entries'] }}</td>
                            <td class="text-end">{{ number_format($m['withheld'], 0) }}</td>
                            <td class="text-end text-success">{{ number_format($m['deposited'], 0) }}</td>
                            <td class="text-end @if(abs($m['gap']) >= 0.01) fw-semibold text-warning @endif">
                                {{ number_format($m['gap'], 0) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><strong>CPRs in this quarter</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>CPR No.</th><th class="text-end">Tax Deposited</th><th class="text-end">Schedule</th></tr></thead>
                    <tbody>
                        @forelse($recon['challans'] as $c)
                        <tr>
                            <td><span class="badge bg-success bg-opacity-10 text-success">{{ $c['cpr_no'] }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format($c['tax'], 0) }}</td>
                            <td class="text-end">
                                <a href="{{ route('wht.challans.pdf', ['cpr' => $c['cpr_no']]) }}" target="_blank"
                                   class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-pdf"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No CPRs recorded in this quarter.</td></tr>
                        @endforelse
                    </tbody>
                    @if($recon['challans']->isNotEmpty())
                    <tfoot><tr class="fw-bold">
                        <td class="text-end">Total</td>
                        <td class="text-end">{{ number_format($recon['challans']->sum('tax'), 0) }}</td>
                        <td></td>
                    </tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
