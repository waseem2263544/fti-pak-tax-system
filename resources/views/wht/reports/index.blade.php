@extends('layouts.app')
@section('title', 'WHT Reports')
@section('page-title', 'WHT Reports')

@section('content')
@include('wht.partials.agent-switch', ['company' => $company])

<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Report</label>
                    <select class="form-select" name="type">
                        <option value="purchases" {{ $type == 'purchases' ? 'selected' : '' }}>Payments</option>
                        <option value="salaries" {{ $type == 'salaries' ? 'selected' : '' }}>Salaries</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="month" class="form-control" name="from" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="month" class="form-control" name="to" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Party</label>
                    <select class="form-select" name="party_id">
                        <option value="">All</option>
                        @foreach($parties as $p)
                            <option value="{{ $p->id }}" {{ request('party_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section</label>
                    <select class="form-select" name="section">
                        <option value="">All</option>
                        @foreach($sections->unique('section') as $s)
                            <option value="{{ $s->section }}" {{ request('section') == $s->section ? 'selected' : '' }}>{{ $s->section }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" name="generate" value="1" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-play-fill"></i> Run
                    </button>
                    <a href="{{ route('wht.reports.export', request()->query()) }}" class="btn btn-outline-primary" title="Export CSV">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@if(request()->has('generate'))
    @if($rows->isEmpty())
        <div class="card"><div class="card-body text-center py-5 text-muted">No records in this range.</div></div>
    @else
    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                @if($type === 'salaries')
                <thead><tr>
                    <th>Month</th><th>Employee</th><th>CNIC/NTN</th>
                    <th class="text-end">Taxable</th><th class="text-end">Exempt</th><th class="text-end">Total</th>
                    <th class="text-end">Tax</th><th class="text-end">Net</th><th>CPR</th>
                </tr></thead>
                <tbody>
                    @foreach($rows as $r)
                    <tr>
                        <td>{{ $r->salary_month?->format('M Y') }}</td>
                        <td>{{ $r->employee?->name }}</td>
                        <td>{{ $r->employee?->cnic_ntn }}</td>
                        <td class="text-end">{{ number_format($r->taxable_salary, 0) }}</td>
                        <td class="text-end">{{ number_format($r->exempt_amount, 0) }}</td>
                        <td class="text-end">{{ number_format($r->total_salary, 0) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($r->tax_deducted, 0) }}</td>
                        <td class="text-end">{{ number_format($r->final_net_payment, 0) }}</td>
                        <td>{{ $r->cpr_no ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot><tr class="fw-bold">
                    <td colspan="5" class="text-end">Total</td>
                    <td class="text-end">{{ number_format($rows->sum('total_salary'), 0) }}</td>
                    <td class="text-end">{{ number_format($rows->sum('tax_deducted'), 0) }}</td>
                    <td class="text-end">{{ number_format($rows->sum('final_net_payment'), 0) }}</td>
                    <td></td>
                </tr></tfoot>
                @else
                <thead><tr>
                    <th>Period</th><th>Vendor</th><th>CNIC/NTN</th><th>ATL</th><th>Section</th>
                    <th class="text-end">Gross</th><th class="text-end">Rate</th><th class="text-end">Tax</th>
                    <th class="text-end">Net</th><th>CPR</th>
                </tr></thead>
                <tbody>
                    @foreach($rows as $r)
                    <tr>
                        <td>{{ $r->period_month?->format('M Y') }}</td>
                        <td>{{ $r->party?->name }}</td>
                        <td>{{ $r->party?->cnic_ntn }}</td>
                        <td>{{ $r->party?->atl_status === 'non-filer' ? 'Non-filer' : 'Filer' }}</td>
                        <td>{{ $r->section }}</td>
                        <td class="text-end">{{ number_format($r->gross_amount, 0) }}</td>
                        <td class="text-end">{{ rtrim(rtrim(number_format($r->tax_rate, 2), '0'), '.') }}%</td>
                        <td class="text-end fw-semibold">{{ number_format($r->tax_withheld, 0) }}</td>
                        <td class="text-end">{{ number_format($r->net_payment, 0) }}</td>
                        <td>{{ $r->cpr_no ?: '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot><tr class="fw-bold">
                    <td colspan="5" class="text-end">Total</td>
                    <td class="text-end">{{ number_format($rows->sum('gross_amount'), 0) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($rows->sum('tax_withheld'), 0) }}</td>
                    <td class="text-end">{{ number_format($rows->sum('net_payment'), 0) }}</td>
                    <td></td>
                </tr></tfoot>
                @endif
            </table>
        </div>
    </div>
    @endif
@else
    <div class="card"><div class="card-body text-center py-5 text-muted">
        Choose a range and press <strong>Run</strong>.
    </div></div>
@endif
@endsection
