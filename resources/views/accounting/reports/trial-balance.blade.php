@extends('layouts.app')
@section('title', 'Trial Balance')
@section('page-title', 'Trial Balance')

@section('styles')
<style>
    @media print {
        .sidebar, .top-nav, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .card { box-shadow: none !important; border: none !important; }
    }
    .group-header td {
        background: rgba(48,58,80,0.03) !important;
        font-weight: 700 !important;
        font-size: 0.82rem !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--primary) !important;
        padding: 10px 16px !important;
    }
    .subtotal-row td {
        background: rgba(48,58,80,0.02) !important;
        font-weight: 700 !important;
        border-top: 1px solid rgba(48,58,80,0.1) !important;
    }
    .grand-total-row td {
        background: var(--primary) !important;
        color: #fff !important;
        font-weight: 800 !important;
        font-size: 0.92rem !important;
    }
</style>
@endsection

@section('content')
<!-- Filter -->
<div class="card mb-4 no-print">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.reports.trial-balance') }}">
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
        <h5 style="font-weight: 600; color: var(--primary); margin: 8px 0 4px;">Trial Balance</h5>
        <div style="font-size: 0.85rem; color: #6b7280;">As of {{ \Carbon\Carbon::parse($asOfDate ?? now())->format('F d, Y') }}</div>
    </div>
</div>

<!-- Trial Balance Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th style="width: 12%;">Code</th>
                    <th style="width: 48%;">Account Name</th>
                    <th class="text-end" style="width: 20%;">Debit</th>
                    <th class="text-end" style="width: 20%;">Credit</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandDebit = 0;
                    $grandCredit = 0;
                    $types = [
                        'asset' => 'Assets',
                        'liability' => 'Liabilities',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expenses',
                    ];
                @endphp

                @foreach($types as $typeKey => $typeLabel)
                    @php
                        $typeAccounts = collect($accounts ?? [])->filter(function($acc) use ($typeKey) {
                            return ($acc->type ?? '') === $typeKey;
                        });
                        $typeDebit = 0;
                        $typeCredit = 0;
                    @endphp

                    @if($typeAccounts->count() > 0)
                    <tr class="group-header">
                        <td colspan="4">{{ $typeLabel }}</td>
                    </tr>

                    @foreach($typeAccounts as $account)
                        @php
                            $balance = $account->balance ?? 0;
                            $debit = $balance > 0 ? $balance : 0;
                            $credit = $balance < 0 ? abs($balance) : 0;
                            $typeDebit += $debit;
                            $typeCredit += $credit;
                            $grandDebit += $debit;
                            $grandCredit += $credit;
                        @endphp
                        @if($debit != 0 || $credit != 0)
                        <tr>
                            <td style="font-size: 0.82rem; color: #6b7280; padding-left: 24px;">{{ $account->code }}</td>
                            <td style="font-size: 0.85rem; color: var(--primary); padding-left: 24px;">{{ $account->name }}</td>
                            <td class="text-end" style="font-size: 0.85rem; font-weight: {{ $debit > 0 ? '600' : '400' }}; color: {{ $debit > 0 ? 'var(--primary)' : '#d1d5db' }};">
                                {{ $debit > 0 ? 'PKR ' . number_format($debit, 2) : '-' }}
                            </td>
                            <td class="text-end" style="font-size: 0.85rem; font-weight: {{ $credit > 0 ? '600' : '400' }}; color: {{ $credit > 0 ? 'var(--primary)' : '#d1d5db' }};">
                                {{ $credit > 0 ? 'PKR ' . number_format($credit, 2) : '-' }}
                            </td>
                        </tr>
                        @endif
                    @endforeach

                    <tr class="subtotal-row">
                        <td colspan="2" style="font-size: 0.82rem; padding-left: 24px;">Total {{ $typeLabel }}</td>
                        <td class="text-end" style="font-size: 0.85rem;">PKR {{ number_format($typeDebit, 2) }}</td>
                        <td class="text-end" style="font-size: 0.85rem;">PKR {{ number_format($typeCredit, 2) }}</td>
                    </tr>
                    @endif
                @endforeach

                <!-- Grand Total -->
                <tr class="grand-total-row">
                    <td colspan="2" style="padding-left: 16px;">Grand Total</td>
                    <td class="text-end">PKR {{ number_format($grandDebit, 2) }}</td>
                    <td class="text-end">PKR {{ number_format($grandCredit, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@if(abs($grandDebit - $grandCredit) > 0.01)
<div class="alert alert-danger mt-3" style="max-width: 600px;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Trial balance does not balance!</strong> Difference: PKR {{ number_format(abs($grandDebit - $grandCredit), 2) }}
</div>
@endif
@endsection
