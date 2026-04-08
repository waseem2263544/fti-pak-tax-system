@extends('layouts.app')
@section('title', 'Accounting Dashboard')
@section('page-title', 'Accounting Overview')

@section('content')
<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(16,185,129,0.08);">
                    <i class="bi bi-graph-up-arrow" style="color: #10b981;"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.35rem;">PKR {{ number_format($totalRevenue ?? 0, 0) }}</div>
                    <div class="stat-label">Revenue (YTD)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(239,68,68,0.07);">
                    <i class="bi bi-graph-down-arrow" style="color: #ef4444;"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.35rem;">PKR {{ number_format($totalExpenses ?? 0, 0) }}</div>
                    <div class="stat-label">Expenses (YTD)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: var(--accent-glow);">
                    <i class="bi bi-trophy" style="color: var(--accent-dark);"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.35rem; {{ ($netProfit ?? 0) < 0 ? 'color: #ef4444;' : '' }}">PKR {{ number_format($netProfit ?? 0, 0) }}</div>
                    <div class="stat-label">Net Profit</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(59,130,246,0.08);">
                    <i class="bi bi-arrow-down-left-circle" style="color: #3b82f6;"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.35rem;">PKR {{ number_format($accountsReceivable ?? 0, 0) }}</div>
                    <div class="stat-label">Receivable</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(245,158,11,0.08);">
                    <i class="bi bi-arrow-up-right-circle" style="color: #f59e0b;"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.35rem;">PKR {{ number_format($accountsPayable ?? 0, 0) }}</div>
                    <div class="stat-label">Payable</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(139,92,246,0.08);">
                    <i class="bi bi-wallet2" style="color: #8b5cf6;"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.35rem;">PKR {{ number_format($cashBalance ?? 0, 0) }}</div>
                    <div class="stat-label">Cash Balance</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-body" style="padding: 20px;">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            <span style="font-weight: 600; font-size: 0.9rem; color: var(--primary);">Quick Actions</span>
        </div>
        <div class="row g-2">
            <div class="col-md-3 col-6">
                <a href="{{ route('accounting.sales-invoices.create') }}" class="btn btn-accent w-100 d-flex align-items-center justify-content-center gap-2" style="padding: 12px;">
                    <i class="bi bi-receipt"></i> New Invoice
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="{{ route('accounting.receipt-vouchers.create') }}" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" style="padding: 12px;">
                    <i class="bi bi-cash-coin"></i> New Receipt
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="{{ route('accounting.payment-vouchers.create') }}" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" style="padding: 12px;">
                    <i class="bi bi-cash-stack"></i> New Payment
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="{{ route('accounting.journal-entries.create') }}" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2" style="padding: 12px;">
                    <i class="bi bi-journal-bookmark"></i> Journal Entry
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Recent Journal Entries
        </div>
        <a href="{{ route('accounting.journal-entries.index') }}" style="color: var(--primary); font-size: 0.78rem; font-weight: 600; text-decoration: none;">View All <i class="bi bi-chevron-right" style="font-size: 0.65rem;"></i></a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>JE #</th>
                    <th>Date</th>
                    <th>Narration</th>
                    <th>Source</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentEntries ?? [] as $entry)
                <tr>
                    <td>
                        <a href="{{ route('accounting.journal-entries.show', $entry) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                            {{ $entry->entry_number }}
                        </a>
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $entry->entry_date->format('M d, Y') }}</td>
                    <td style="font-size: 0.85rem;">{{ Str::limit($entry->narration, 45) }}</td>
                    <td>
                        @if($entry->source_type === 'manual')
                            <span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">Manual</span>
                        @elseif($entry->source_type === 'sales_invoice')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Sales Invoice</span>
                        @elseif($entry->source_type === 'purchase_invoice')
                            <span class="badge" style="background: #fef3c7; color: #92400e;">Purchase Invoice</span>
                        @elseif($entry->source_type === 'receipt')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Receipt</span>
                        @elseif($entry->source_type === 'payment')
                            <span class="badge" style="background: #fce7f3; color: #9d174d;">Payment</span>
                        @else
                            <span class="badge" style="background: #f3f4f6; color: #6b7280;">{{ ucfirst($entry->source_type ?? 'Manual') }}</span>
                        @endif
                    </td>
                    <td class="text-end" style="font-weight: 600; font-size: 0.85rem;">PKR {{ number_format($entry->lines->sum('debit'), 2) }}</td>
                    <td>
                        @if($entry->status === 'posted')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Posted</span>
                        @elseif($entry->status === 'draft')
                            <span class="badge" style="background: #fef3c7; color: #92400e;">Draft</span>
                        @else
                            <span class="badge" style="background: #fef2f2; color: #dc2626;">Reversed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-journal-text" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No journal entries yet. <a href="{{ route('accounting.journal-entries.create') }}" style="color: var(--primary); font-weight: 600;">Create your first entry</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
