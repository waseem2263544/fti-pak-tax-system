@extends('layouts.app')
@section('title', 'Income Statement')
@section('page-title', 'Income Statement')

@section('styles')
<style>
    @media print {
        .sidebar, .top-nav, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .card { box-shadow: none !important; border: none !important; }
    }
    .is-section-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--primary);
        padding: 12px 0 8px;
        border-bottom: 2px solid var(--primary);
        margin-bottom: 8px;
    }
    .is-account-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 0.85rem;
    }
    .is-account-name { color: #374151; }
    .is-account-amount { font-weight: 600; color: var(--primary); }
    .is-subtotal {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        margin-top: 8px;
        border-top: 1px solid #e5e7eb;
        font-weight: 700;
        color: var(--primary);
        font-size: 0.9rem;
    }
</style>
@endsection

@section('content')
<!-- Filter -->
<div class="card mb-4 no-print">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.reports.income-statement') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="from_date" value="{{ $fromDate ?? date('Y-01-01') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="to_date" value="{{ $toDate ?? date('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Generate</button>
                </div>
                <div class="col-md-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-primary w-100"><i class="bi bi-printer me-1"></i> Print</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Report Header -->
<div class="card mb-4">
    <div class="card-body" style="padding: 32px; text-align: center;">
        <h4 style="font-weight: 800; color: var(--primary); margin: 0;">FairTax International</h4>
        <h5 style="font-weight: 600; color: var(--primary); margin: 8px 0 4px;">Income Statement</h5>
        <div style="font-size: 0.85rem; color: #6b7280;">
            {{ \Carbon\Carbon::parse($fromDate ?? date('Y-01-01'))->format('F d, Y') }}
            to
            {{ \Carbon\Carbon::parse($toDate ?? now())->format('F d, Y') }}
        </div>
    </div>
</div>

<!-- Income Statement -->
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-body" style="padding: 32px;">

        <!-- Revenue -->
        <div class="is-section-title">Revenue</div>
        @php $totalRevenue = 0; @endphp
        @foreach($revenues ?? [] as $account)
            @php $totalRevenue += abs($account->balance ?? 0); @endphp
            <div class="is-account-row">
                <span class="is-account-name">
                    <span style="color: #9ca3af; font-size: 0.78rem; margin-right: 8px;">{{ $account->code }}</span>
                    {{ $account->name }}
                </span>
                <span class="is-account-amount">PKR {{ number_format(abs($account->balance ?? 0), 2) }}</span>
            </div>
        @endforeach
        <div class="is-subtotal">
            <span>Total Revenue</span>
            <span>PKR {{ number_format($totalRevenue, 2) }}</span>
        </div>

        <div style="height: 24px;"></div>

        <!-- Expenses -->
        <div class="is-section-title">Expenses</div>
        @php $totalExpenses = 0; @endphp
        @foreach($expenses ?? [] as $account)
            @php $totalExpenses += abs($account->balance ?? 0); @endphp
            <div class="is-account-row">
                <span class="is-account-name">
                    <span style="color: #9ca3af; font-size: 0.78rem; margin-right: 8px;">{{ $account->code }}</span>
                    {{ $account->name }}
                </span>
                <span class="is-account-amount" style="color: #ef4444;">PKR {{ number_format(abs($account->balance ?? 0), 2) }}</span>
            </div>
        @endforeach
        <div class="is-subtotal">
            <span>Total Expenses</span>
            <span style="color: #ef4444;">PKR {{ number_format($totalExpenses, 2) }}</span>
        </div>

        <div style="height: 24px;"></div>

        <!-- Net Profit / Loss -->
        @php $netProfit = $totalRevenue - $totalExpenses; @endphp
        <div style="padding: 18px 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; {{ $netProfit >= 0 ? 'background: rgba(215,223,39,0.12); border: 2px solid var(--accent);' : 'background: rgba(239,68,68,0.06); border: 2px solid rgba(239,68,68,0.3);' }}">
            <span style="font-weight: 800; font-size: 1.1rem; color: var(--primary);">
                {{ $netProfit >= 0 ? 'Net Profit' : 'Net Loss' }}
            </span>
            <span style="font-weight: 800; font-size: 1.4rem; {{ $netProfit >= 0 ? 'color: #065f46;' : 'color: #dc2626;' }}">
                PKR {{ number_format(abs($netProfit), 2) }}
            </span>
        </div>

    </div>
</div>
@endsection
