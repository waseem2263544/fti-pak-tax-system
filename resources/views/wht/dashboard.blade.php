@extends('layouts.app')
@section('title', 'WHT Dashboard')
@section('page-title', 'Withholding Tax')

@section('content')
@include('wht.partials.agent-bar')

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Tax on Payments</div>
            <div class="fw-bold" style="font-size: 1.5rem;">{{ number_format($stats['purchase_tax'], 0) }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">{{ $stats['purchase_count'] }} entries · TY{{ $stats['tax_year'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Tax on Salaries</div>
            <div class="fw-bold" style="font-size: 1.5rem;">{{ number_format($stats['salary_tax'], 0) }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">{{ $stats['salary_count'] }} entries · TY{{ $stats['tax_year'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 @if($stats['undeposited_tax'] > 0) border-warning @endif"><div class="card-body">
            <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Not Yet Deposited</div>
            <div class="fw-bold @if($stats['undeposited_tax'] > 0) text-warning @endif" style="font-size: 1.5rem;">
                {{ number_format($stats['undeposited_tax'], 0) }}
            </div>
            <div class="text-muted" style="font-size: 0.78rem;">No CPR recorded</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted" style="font-size: 0.75rem; text-transform: uppercase;">Active Parties</div>
            <div class="fw-bold" style="font-size: 1.5rem;">{{ $stats['parties'] }}</div>
            <div class="text-muted" style="font-size: 0.78rem;">Vendors &amp; employees</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Monthly Withholding — TY{{ $stats['tax_year'] }}</strong>
                <a href="{{ route('wht.reports.statement') }}" class="btn btn-outline-primary btn-sm">Statement</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead><tr>
                        <th>Month</th>
                        <th class="text-end">Payments</th>
                        <th class="text-end">Salaries</th>
                        <th class="text-end">Total</th>
                    </tr></thead>
                    <tbody>
                        @foreach($monthly as $key => $m)
                            @php $total = $m['purchase'] + $m['salary']; @endphp
                            <tr @if($total == 0) class="text-muted" @endif>
                                <td>{{ $m['label'] }}</td>
                                <td class="text-end">{{ number_format($m['purchase'], 0) }}</td>
                                <td class="text-end">{{ number_format($m['salary'], 0) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($total, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Recent Payments</strong>
                <a href="{{ route('wht.purchases.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead><tr><th>Vendor</th><th>Period</th><th class="text-end">Tax</th></tr></thead>
                    <tbody>
                        @forelse($recent as $r)
                        <tr>
                            <td>{{ $r->party?->name ?? '—' }}</td>
                            <td>{{ $r->period_month?->format('M Y') }}</td>
                            <td class="text-end">{{ number_format($r->tax_withheld, 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Nothing recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
