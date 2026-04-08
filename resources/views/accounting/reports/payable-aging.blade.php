@extends('layouts.app')
@section('title', 'Payable Aging')
@section('page-title', 'Payable Aging')

@section('styles')
<style>
    @media print {
        .sidebar, .top-nav, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .card { box-shadow: none !important; border: none !important; }
    }
    .aging-current { background: rgba(16,185,129,0.06); }
    .aging-30 { background: rgba(245,158,11,0.06); }
    .aging-60 { background: rgba(249,115,22,0.06); }
    .aging-90 { background: rgba(239,68,68,0.06); }
    .aging-header-current { background: rgba(16,185,129,0.12) !important; color: #065f46 !important; }
    .aging-header-30 { background: rgba(245,158,11,0.12) !important; color: #92400e !important; }
    .aging-header-60 { background: rgba(249,115,22,0.12) !important; color: #9a3412 !important; }
    .aging-header-90 { background: rgba(239,68,68,0.12) !important; color: #991b1b !important; }
</style>
@endsection

@section('content')
<!-- Filter -->
<div class="card mb-4 no-print">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.reports.payable-aging') }}">
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
        <h5 style="font-weight: 600; color: var(--primary); margin: 8px 0 4px;">Accounts Payable Aging</h5>
        <div style="font-size: 0.85rem; color: #6b7280;">As of {{ \Carbon\Carbon::parse($asOfDate ?? now())->format('F d, Y') }}</div>
    </div>
</div>

<!-- Aging Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th style="width: 28%;">Vendor</th>
                    <th class="text-end aging-header-current" style="width: 14%;">Current (0-30)</th>
                    <th class="text-end aging-header-30" style="width: 14%;">31-60 Days</th>
                    <th class="text-end aging-header-60" style="width: 14%;">61-90 Days</th>
                    <th class="text-end aging-header-90" style="width: 14%;">90+ Days</th>
                    <th class="text-end" style="width: 16%; font-weight: 800;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totCurrent = 0; $tot30 = 0; $tot60 = 0; $tot90 = 0; $totTotal = 0;
                @endphp

                @forelse($aging ?? [] as $row)
                @php
                    $current = $row['current'] ?? 0;
                    $days30 = $row['days_31_60'] ?? 0;
                    $days60 = $row['days_61_90'] ?? 0;
                    $days90 = $row['days_90_plus'] ?? 0;
                    $total = $current + $days30 + $days60 + $days90;
                    $totCurrent += $current;
                    $tot30 += $days30;
                    $tot60 += $days60;
                    $tot90 += $days90;
                    $totTotal += $total;
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 28px; height: 28px; background: rgba(48,58,80,0.06); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.6rem; color: var(--primary);">{{ strtoupper(substr($row['vendor_name'] ?? '', 0, 2)) }}</div>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary);">{{ $row['vendor_name'] ?? '-' }}</span>
                        </div>
                    </td>
                    <td class="text-end aging-current" style="font-size: 0.85rem; font-weight: {{ $current > 0 ? '600' : '400' }}; color: {{ $current > 0 ? '#065f46' : '#d1d5db' }};">
                        {{ $current > 0 ? 'PKR ' . number_format($current, 2) : '-' }}
                    </td>
                    <td class="text-end aging-30" style="font-size: 0.85rem; font-weight: {{ $days30 > 0 ? '600' : '400' }}; color: {{ $days30 > 0 ? '#92400e' : '#d1d5db' }};">
                        {{ $days30 > 0 ? 'PKR ' . number_format($days30, 2) : '-' }}
                    </td>
                    <td class="text-end aging-60" style="font-size: 0.85rem; font-weight: {{ $days60 > 0 ? '600' : '400' }}; color: {{ $days60 > 0 ? '#9a3412' : '#d1d5db' }};">
                        {{ $days60 > 0 ? 'PKR ' . number_format($days60, 2) : '-' }}
                    </td>
                    <td class="text-end aging-90" style="font-size: 0.85rem; font-weight: {{ $days90 > 0 ? '600' : '400' }}; color: {{ $days90 > 0 ? '#991b1b' : '#d1d5db' }};">
                        {{ $days90 > 0 ? 'PKR ' . number_format($days90, 2) : '-' }}
                    </td>
                    <td class="text-end" style="font-weight: 700; font-size: 0.85rem; color: var(--primary);">
                        PKR {{ number_format($total, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-clock-history" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No payable aging data found.
                    </td>
                </tr>
                @endforelse

                @if(count($aging ?? []) > 0)
                <tr style="background: var(--primary);">
                    <td style="font-weight: 800; color: #fff; padding-left: 16px;">Total</td>
                    <td class="text-end" style="font-weight: 700; color: #fff; font-size: 0.85rem;">PKR {{ number_format($totCurrent, 2) }}</td>
                    <td class="text-end" style="font-weight: 700; color: #fff; font-size: 0.85rem;">PKR {{ number_format($tot30, 2) }}</td>
                    <td class="text-end" style="font-weight: 700; color: #fff; font-size: 0.85rem;">PKR {{ number_format($tot60, 2) }}</td>
                    <td class="text-end" style="font-weight: 700; color: #fff; font-size: 0.85rem;">PKR {{ number_format($tot90, 2) }}</td>
                    <td class="text-end" style="font-weight: 800; color: #fff; font-size: 0.92rem;">PKR {{ number_format($totTotal, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
