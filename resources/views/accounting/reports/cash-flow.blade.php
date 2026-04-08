@extends('layouts.app')
@section('title', 'Cash Flow Report')
@section('page-title', 'Cash Flow Report')

@section('content')
<!-- Date Filter -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" class="d-flex gap-3 align-items-end">
            <div>
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $fromDate }}">
            </div>
            <div>
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $toDate }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Apply</button>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(48,58,80,0.06);"><i class="bi bi-wallet2" style="color: var(--primary);"></i></div>
                <div>
                    <div class="stat-value" style="font-size: 1.3rem;">PKR {{ number_format($totalOpening, 2) }}</div>
                    <div class="stat-label">Opening Balance</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(16,185,129,0.08);"><i class="bi bi-arrow-down-circle" style="color: #10b981;"></i></div>
                <div>
                    <div class="stat-value" style="font-size: 1.3rem; color: #10b981;">PKR {{ number_format($totalReceipts, 2) }}</div>
                    <div class="stat-label">Total Receipts</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(239,68,68,0.07);"><i class="bi bi-arrow-up-circle" style="color: #ef4444;"></i></div>
                <div>
                    <div class="stat-value" style="font-size: 1.3rem; color: #ef4444;">PKR {{ number_format($totalPayments, 2) }}</div>
                    <div class="stat-label">Total Payments</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: var(--accent-glow);"><i class="bi bi-cash-stack" style="color: #8b9a00;"></i></div>
                <div>
                    <div class="stat-value" style="font-size: 1.3rem;">PKR {{ number_format($totalClosing, 2) }}</div>
                    <div class="stat-label">Closing Balance</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cash Flow by Account -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
        Cash & Bank Account Summary
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Opening Balance</th>
                    <th class="text-end" style="color: #10b981;">Receipts (In)</th>
                    <th class="text-end" style="color: #ef4444;">Payments (Out)</th>
                    <th class="text-end">Closing Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cashFlowData as $row)
                <tr>
                    <td>
                        <div style="font-weight: 600; color: var(--primary);">{{ $row['account']->name }}</div>
                        <div style="font-size: 0.72rem; color: #9ca3af;">{{ $row['account']->code }}</div>
                    </td>
                    <td class="text-end" style="font-size: 0.88rem;">PKR {{ number_format($row['opening'], 2) }}</td>
                    <td class="text-end" style="font-size: 0.88rem; color: #10b981; font-weight: 600;">PKR {{ number_format($row['receipts'], 2) }}</td>
                    <td class="text-end" style="font-size: 0.88rem; color: #ef4444; font-weight: 600;">PKR {{ number_format($row['payments'], 2) }}</td>
                    <td class="text-end" style="font-size: 0.88rem; font-weight: 700; color: var(--primary);">PKR {{ number_format($row['closing'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: var(--primary); color: #fff;">
                    <td style="font-weight: 700; border: none;">Total</td>
                    <td class="text-end" style="font-weight: 700; border: none;">PKR {{ number_format($totalOpening, 2) }}</td>
                    <td class="text-end" style="font-weight: 700; border: none;">PKR {{ number_format($totalReceipts, 2) }}</td>
                    <td class="text-end" style="font-weight: 700; border: none;">PKR {{ number_format($totalPayments, 2) }}</td>
                    <td class="text-end" style="font-weight: 700; border: none;">PKR {{ number_format($totalClosing, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Receipts & Payments by Method -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <div style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></div>
                Receipts by Method
            </div>
            <div class="card-body p-0">
                @forelse($receiptsBySource as $method => $amount)
                <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f5f6f8;">
                    <div class="d-flex align-items-center gap-2">
                        @if($method == 'cash') <i class="bi bi-cash" style="color: #10b981;"></i>
                        @elseif($method == 'bank_transfer') <i class="bi bi-bank" style="color: #2563eb;"></i>
                        @elseif($method == 'cheque') <i class="bi bi-file-text" style="color: #8b5cf6;"></i>
                        @else <i class="bi bi-globe" style="color: #f59e0b;"></i>
                        @endif
                        <span style="font-weight: 500; text-transform: capitalize;">{{ str_replace('_', ' ', $method) }}</span>
                    </div>
                    <span style="font-weight: 700; color: #10b981;">PKR {{ number_format($amount, 2) }}</span>
                </div>
                @empty
                <div class="text-center py-4" style="color: #d1d5db;">No receipts in this period</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <div style="width: 8px; height: 8px; background: #ef4444; border-radius: 50%;"></div>
                Payments by Method
            </div>
            <div class="card-body p-0">
                @forelse($paymentsBySource as $method => $amount)
                <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f5f6f8;">
                    <div class="d-flex align-items-center gap-2">
                        @if($method == 'cash') <i class="bi bi-cash" style="color: #ef4444;"></i>
                        @elseif($method == 'bank_transfer') <i class="bi bi-bank" style="color: #2563eb;"></i>
                        @elseif($method == 'cheque') <i class="bi bi-file-text" style="color: #8b5cf6;"></i>
                        @else <i class="bi bi-globe" style="color: #f59e0b;"></i>
                        @endif
                        <span style="font-weight: 500; text-transform: capitalize;">{{ str_replace('_', ' ', $method) }}</span>
                    </div>
                    <span style="font-weight: 700; color: #ef4444;">PKR {{ number_format($amount, 2) }}</span>
                </div>
                @empty
                <div class="text-center py-4" style="color: #d1d5db;">No payments in this period</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Net Cash Flow -->
<div class="card mt-4" style="border-left: 4px solid {{ ($totalReceipts - $totalPayments) >= 0 ? 'var(--accent)' : '#ef4444' }};">
    <div class="card-body" style="padding: 20px 24px;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Net Cash Flow for Period</div>
                <div style="font-size: 0.82rem; color: #6b7280;">{{ $fromDate }} to {{ $toDate }}</div>
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: {{ ($totalReceipts - $totalPayments) >= 0 ? 'var(--primary)' : '#ef4444' }};">
                PKR {{ number_format($totalReceipts - $totalPayments, 2) }}
            </div>
        </div>
    </div>
</div>
@endsection
