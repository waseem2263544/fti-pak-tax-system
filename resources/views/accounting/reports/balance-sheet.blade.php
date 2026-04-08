@extends('layouts.app')
@section('title', 'Balance Sheet')
@section('page-title', 'Balance Sheet')

@section('styles')
<style>
    @media print {
        .sidebar, .top-nav, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .card { box-shadow: none !important; border: none !important; }
    }
    .bs-section-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--primary);
        padding: 12px 0 8px;
        border-bottom: 2px solid var(--primary);
        margin-bottom: 12px;
    }
    .bs-account-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 0.85rem;
    }
    .bs-account-name {
        color: #374151;
    }
    .bs-account-amount {
        font-weight: 600;
        color: var(--primary);
    }
    .bs-subtotal {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        margin-top: 8px;
        border-top: 1px solid #e5e7eb;
        font-weight: 700;
        color: var(--primary);
        font-size: 0.9rem;
    }
    .bs-grand-total {
        display: flex;
        justify-content: space-between;
        padding: 14px 16px;
        margin-top: 12px;
        background: var(--primary);
        color: #fff;
        font-weight: 800;
        font-size: 1rem;
        border-radius: 8px;
    }
</style>
@endsection

@section('content')
<!-- Filter -->
<div class="card mb-4 no-print">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.reports.balance-sheet') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">As of Date</label>
                    <input type="date" class="form-control" name="as_of_date" value="{{ $asOfDate ?? date('Y-m-d') }}">
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
        <h5 style="font-weight: 600; color: var(--primary); margin: 8px 0 4px;">Balance Sheet</h5>
        <div style="font-size: 0.85rem; color: #6b7280;">As of {{ \Carbon\Carbon::parse($asOfDate ?? now())->format('F d, Y') }}</div>
    </div>
</div>

<!-- Balance Sheet -->
<div class="row g-4">
    <!-- Left: Assets -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-body" style="padding: 24px;">
                <div class="bs-section-title">Assets</div>

                @php $totalAssets = 0; @endphp
                @foreach($assets ?? [] as $account)
                    @php $totalAssets += abs($account->balance ?? 0); @endphp
                    <div class="bs-account-row">
                        <span class="bs-account-name">
                            <span style="color: #9ca3af; font-size: 0.78rem; margin-right: 8px;">{{ $account->code }}</span>
                            {{ $account->name }}
                        </span>
                        <span class="bs-account-amount">PKR {{ number_format(abs($account->balance ?? 0), 2) }}</span>
                    </div>
                @endforeach

                <div class="bs-grand-total">
                    <span>Total Assets</span>
                    <span>PKR {{ number_format($totalAssets, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Liabilities + Equity -->
    <div class="col-md-6">
        <!-- Liabilities -->
        <div class="card mb-4">
            <div class="card-body" style="padding: 24px;">
                <div class="bs-section-title">Liabilities</div>

                @php $totalLiabilities = 0; @endphp
                @foreach($liabilities ?? [] as $account)
                    @php $totalLiabilities += abs($account->balance ?? 0); @endphp
                    <div class="bs-account-row">
                        <span class="bs-account-name">
                            <span style="color: #9ca3af; font-size: 0.78rem; margin-right: 8px;">{{ $account->code }}</span>
                            {{ $account->name }}
                        </span>
                        <span class="bs-account-amount">PKR {{ number_format(abs($account->balance ?? 0), 2) }}</span>
                    </div>
                @endforeach

                <div class="bs-subtotal">
                    <span>Total Liabilities</span>
                    <span>PKR {{ number_format($totalLiabilities, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Equity -->
        <div class="card mb-4">
            <div class="card-body" style="padding: 24px;">
                <div class="bs-section-title">Equity</div>

                @php $totalEquity = 0; @endphp
                @foreach($equity ?? [] as $account)
                    @php $totalEquity += abs($account->balance ?? 0); @endphp
                    <div class="bs-account-row">
                        <span class="bs-account-name">
                            <span style="color: #9ca3af; font-size: 0.78rem; margin-right: 8px;">{{ $account->code }}</span>
                            {{ $account->name }}
                        </span>
                        <span class="bs-account-amount">PKR {{ number_format(abs($account->balance ?? 0), 2) }}</span>
                    </div>
                @endforeach

                <div class="bs-subtotal">
                    <span>Total Equity</span>
                    <span>PKR {{ number_format($totalEquity, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Total L+E -->
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <div class="bs-grand-total" style="border-radius: 12px; margin: 0;">
                    <span>Total Liabilities + Equity</span>
                    <span>PKR {{ number_format($totalLiabilities + $totalEquity, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Verification -->
@php $difference = abs($totalAssets - ($totalLiabilities + $totalEquity)); @endphp
@if($difference > 0.01)
<div class="alert alert-danger mt-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Balance sheet does not balance!</strong> Assets: PKR {{ number_format($totalAssets, 2) }} | Liabilities + Equity: PKR {{ number_format($totalLiabilities + $totalEquity, 2) }} | Difference: PKR {{ number_format($difference, 2) }}
</div>
@else
<div class="alert mt-4" style="background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.15); color: #065f46;">
    <i class="bi bi-check-circle-fill me-2"></i>
    Balance sheet is balanced. Assets = Liabilities + Equity
</div>
@endif
@endsection
