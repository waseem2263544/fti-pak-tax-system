@extends('layouts.app')
@section('title', 'Accounting Dashboard')
@section('page-title', 'Accounting Overview')

@section('content')
@php
    $fmt = fn($v) => 'PKR ' . number_format((float) $v, 0);
    $cards = [
        ['label' => 'Revenue (YTD)',  'value' => $stats['revenue_total'] ?? 0, 'icon' => 'bi-graph-up-arrow',      'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.08)'],
        ['label' => 'Expenses (YTD)', 'value' => $stats['expense_total'] ?? 0, 'icon' => 'bi-graph-down-arrow',    'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.07)'],
        ['label' => 'Net Profit',     'value' => $stats['net_income'] ?? 0,    'icon' => 'bi-trophy',             'color' => 'var(--accent-dark)', 'bg' => 'var(--accent-glow)', 'danger' => ($stats['net_income'] ?? 0) < 0],
        ['label' => 'Receivable',     'value' => $stats['ar_total'] ?? 0,      'icon' => 'bi-arrow-down-left-circle','color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.08)'],
        ['label' => 'Payable',        'value' => $stats['ap_total'] ?? 0,      'icon' => 'bi-arrow-up-right-circle', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.08)'],
        ['label' => 'Cash & Bank',    'value' => $stats['cash_total'] ?? 0,    'icon' => 'bi-wallet2',            'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.08)'],
    ];
@endphp
@if($currentFY)
<div class="mb-3" style="font-size: 0.8rem; color: #9ca3af;"><i class="bi bi-calendar-range me-1"></i>Fiscal Year: <strong style="color: var(--primary);">{{ $currentFY->name }}</strong> ({{ $currentFY->start_date->format('d M Y') }} – {{ $currentFY->end_date->format('d M Y') }})</div>
@else
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>No active fiscal year. <a href="{{ route('accounting.fiscal-years.index') }}">Set one up</a> to see period figures.</div>
@endif

<!-- Stats Row -->
<div class="row g-3 mb-4">
    @foreach($cards as $c)
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: {{ $c['bg'] }};">
                    <i class="bi {{ $c['icon'] }}" style="color: {{ $c['color'] }};"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.3rem; {{ !empty($c['danger']) ? 'color: #ef4444;' : '' }}">{{ $fmt($c['value']) }}</div>
                    <div class="stat-label">{{ $c['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
                Revenue vs Expenses
            </div>
            <div class="card-body" style="padding: 18px;">
                <canvas id="revExpChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
                Expense Breakdown
            </div>
            <div class="card-body" style="padding: 18px;">
                @if($expenseBreakdown->isEmpty())
                    <div class="text-center py-5" style="color: #9ca3af;"><i class="bi bi-pie-chart" style="font-size: 2rem; opacity: 0.3;"></i><div class="mt-2" style="font-size: 0.85rem;">No expenses recorded yet</div></div>
                @else
                    <canvas id="expenseChart" height="200"></canvas>
                @endif
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
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ optional($entry->date)->format('M d, Y') }}</td>
                    <td style="font-size: 0.85rem;">{{ Str::limit($entry->narration ?: $entry->reference, 45) }}</td>
                    <td>
                        @php $labels = ['manual' => ['Manual', 'rgba(48,58,80,0.06)', 'var(--primary)'], 'sales_invoice' => ['Sales Invoice', '#dbeafe', '#1e40af'], 'purchase_invoice' => ['Purchase Invoice', '#fef3c7', '#92400e'], 'receipt_voucher' => ['Receipt', '#d1fae5', '#065f46'], 'payment_voucher' => ['Payment', '#fce7f3', '#9d174d'], 'reversal' => ['Reversal', '#fef2f2', '#dc2626']]; $b = $labels[$entry->source_type] ?? [ucfirst(str_replace('_', ' ', $entry->source_type ?? 'Manual')), '#f3f4f6', '#6b7280']; @endphp
                        <span class="badge" style="background: {{ $b[1] }}; color: {{ $b[2] }};">{{ $b[0] }}</span>
                    </td>
                    <td class="text-end" style="font-weight: 600; font-size: 0.85rem;">PKR {{ number_format($entry->total_amount ?: $entry->lines->sum('debit'), 2) }}</td>
                    <td>
                        @if($entry->is_reversed)
                            <span class="badge" style="background: #fef2f2; color: #dc2626;">Reversed</span>
                        @elseif($entry->is_posted)
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Posted</span>
                        @else
                            <span class="badge" style="background: #fef3c7; color: #92400e;">Draft</span>
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

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = '#6b7280';

    var revExp = document.getElementById('revExpChart');
    if (revExp) {
        new Chart(revExp, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    { label: 'Revenue', data: @json($chartRevenue), backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 22 },
                    { label: 'Expenses', data: @json($chartExpense), backgroundColor: '#ef4444', borderRadius: 4, maxBarThickness: 22 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return 'PKR ' + v.toLocaleString(); } } } }
            }
        });
    }

    var exp = document.getElementById('expenseChart');
    if (exp) {
        new Chart(exp, {
            type: 'doughnut',
            data: {
                labels: @json($expenseBreakdown->pluck('name')),
                datasets: [{
                    data: @json($expenseBreakdown->pluck('total')),
                    backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#ec4899', '#14b8a6', '#64748b']
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                cutout: '60%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
            }
        });
    }
})();
</script>
@endsection
