@extends('layouts.app')
@section('title', 'Sales Tax Report')
@section('page-title', 'Sales Tax Report')

@section('content')
<style>
    @media print { .sidebar, .top-nav, .no-print { display: none !important; } body { background: #fff; } .main-content { margin: 0 !important; } }
    .tx-table th { background: #303a50; color: #fff; padding: 7px 9px; font-size: 0.78rem; }
    .tx-table td { padding: 6px 9px; border-bottom: 1px solid #eef0f3; font-size: 0.82rem; }
    .tx-table .r, .tx-table td.r, .tx-table th.r { text-align: right; }
</style>
@php $money = fn($v) => 'PKR ' . number_format((float) $v, 2); @endphp

<div class="card mb-4 no-print">
    <div class="card-body" style="padding: 18px;">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" value="{{ $fromDate }}"></div>
            <div class="col-md-4"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" value="{{ $toDate }}"></div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-accent"><i class="bi bi-search me-1"></i>Generate</button>
                <button type="button" onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer"></i></button>
                <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-primary"><i class="bi bi-filetype-csv"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div><div class="stat-label">Taxable Sales</div><div class="stat-value" style="font-size:1.2rem;">{{ $money($taxableSales) }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div><div class="stat-label">Output Tax (Sales)</div><div class="stat-value" style="font-size:1.2rem; color:#10b981;">{{ $money($outputTax) }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div><div class="stat-label">Input Tax (Purchases)</div><div class="stat-value" style="font-size:1.2rem; color:#3b82f6;">{{ $money($inputTax) }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat-card" style="background: {{ $netTax >= 0 ? 'rgba(239,68,68,0.06)' : 'rgba(16,185,129,0.06)' }};"><div><div class="stat-label">{{ $netTax >= 0 ? 'Net Tax Payable' : 'Net Tax Refundable' }}</div><div class="stat-value" style="font-size:1.2rem; color: {{ $netTax >= 0 ? '#dc2626' : '#10b981' }};">{{ $money(abs($netTax)) }}</div></div></div></div>
</div>

<div class="text-center mb-3" style="font-size: 0.82rem; color:#6b7280;">Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>

<!-- Output Tax detail -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-receipt me-1"></i>Output Tax — Sales</div>
    <div class="table-responsive">
        <table class="tx-table" style="width:100%; border-collapse:collapse;">
            <thead><tr><th>Date</th><th>Invoice</th><th>Client</th><th class="r">Taxable</th><th class="r">Tax</th><th class="r">Total</th></tr></thead>
            <tbody>
                @forelse($sales as $inv)
                <tr>
                    <td>{{ optional($inv->date)->format('d M Y') }}</td>
                    <td>{{ $inv->invoice_number }}</td>
                    <td>{{ $inv->client->name ?? '—' }}</td>
                    <td class="r">{{ number_format($inv->items->sum('amount'), 2) }}</td>
                    <td class="r">{{ number_format($inv->items->sum('tax_amount'), 2) }}</td>
                    <td class="r">{{ number_format($inv->total, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3" style="color:#9ca3af;">No sales in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr style="background:#f8f9fb; font-weight:700;"><td colspan="3">Total</td><td class="r">{{ number_format($taxableSales, 2) }}</td><td class="r">{{ number_format($outputTax, 2) }}</td><td class="r"></td></tr></tfoot>
        </table>
    </div>
</div>

<!-- Input Tax detail -->
<div class="card">
    <div class="card-header"><i class="bi bi-cart-check me-1"></i>Input Tax — Purchases</div>
    <div class="table-responsive">
        <table class="tx-table" style="width:100%; border-collapse:collapse;">
            <thead><tr><th>Date</th><th>Bill</th><th>Vendor</th><th class="r">Taxable</th><th class="r">Tax</th><th class="r">Total</th></tr></thead>
            <tbody>
                @forelse($purchases as $bill)
                <tr>
                    <td>{{ optional($bill->date)->format('d M Y') }}</td>
                    <td>{{ $bill->bill_number }}</td>
                    <td>{{ $bill->contact->name ?? '—' }}</td>
                    <td class="r">{{ number_format($bill->items->sum('amount'), 2) }}</td>
                    <td class="r">{{ number_format($bill->items->sum('tax_amount'), 2) }}</td>
                    <td class="r">{{ number_format($bill->total, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3" style="color:#9ca3af;">No purchases in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr style="background:#f8f9fb; font-weight:700;"><td colspan="3">Total</td><td class="r">{{ number_format($taxablePurchases, 2) }}</td><td class="r">{{ number_format($inputTax, 2) }}</td><td class="r"></td></tr></tfoot>
        </table>
    </div>
</div>
@endsection
