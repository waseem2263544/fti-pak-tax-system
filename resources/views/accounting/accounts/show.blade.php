@extends('layouts.app')
@section('title', $account->name . ' - Ledger')
@section('page-title', 'Account Ledger')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('accounting.accounts.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Chart of Accounts
    </a>
</div>

<!-- Account Header -->
<div class="card mb-4">
    <div class="card-body" style="padding: 28px;">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span style="font-family: monospace; font-size: 0.82rem; background: rgba(48,58,80,0.06); padding: 4px 10px; border-radius: 6px; font-weight: 600; color: var(--primary);">{{ $account->code }}</span>
                    <h4 style="margin: 0; font-weight: 800; color: var(--primary);">{{ $account->name }}</h4>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @php
                        $typeColors = [
                            'asset' => ['bg' => 'rgba(59,130,246,0.08)', 'color' => '#1e40af'],
                            'liability' => ['bg' => 'rgba(239,68,68,0.07)', 'color' => '#dc2626'],
                            'equity' => ['bg' => 'rgba(139,92,246,0.08)', 'color' => '#7c3aed'],
                            'revenue' => ['bg' => 'rgba(16,185,129,0.08)', 'color' => '#065f46'],
                            'expense' => ['bg' => 'rgba(245,158,11,0.08)', 'color' => '#92400e'],
                        ];
                        $tc = $typeColors[$account->type] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280'];
                    @endphp
                    <span class="badge" style="background: {{ $tc['bg'] }}; color: {{ $tc['color'] }};">{{ ucfirst($account->type) }}</span>
                    @if($account->sub_type)
                        <span class="badge" style="background: #f3f4f6; color: #6b7280;">{{ $account->sub_type }}</span>
                    @endif
                </div>
                @if($account->description)
                    <p style="color: #6b7280; font-size: 0.85rem; margin: 12px 0 0;">{{ $account->description }}</p>
                @endif
            </div>
            <div class="text-end">
                <div style="font-size: 0.72rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px;">Current Balance</div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--primary);">PKR {{ number_format(abs($account->balance), 2) }}</div>
                <div style="font-size: 0.78rem; color: #6b7280;">{{ $account->balance >= 0 ? 'Debit' : 'Credit' }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.accounts.show', $account) }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="from" value="{{ request('from', now()->startOfMonth()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="to" value="{{ request('to', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                </div>
                @if(request()->hasAny(['from', 'to']))
                <div class="col-md-1">
                    <a href="{{ route('accounting.accounts.show', $account) }}" class="btn btn-outline-primary w-100" title="Clear"><i class="bi bi-x-lg"></i></a>
                </div>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Ledger Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>JE #</th>
                    <th>Description</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @php $runningBalance = $openingBalance ?? 0; @endphp
                @if(isset($openingBalance))
                <tr style="background: #fafbfc;">
                    <td colspan="3" style="font-weight: 600; color: #6b7280; font-size: 0.85rem;">Opening Balance</td>
                    <td class="text-end" style="font-size: 0.85rem;">-</td>
                    <td class="text-end" style="font-size: 0.85rem;">-</td>
                    <td class="text-end" style="font-weight: 700; font-size: 0.85rem;">PKR {{ number_format(abs($runningBalance), 2) }} {{ $runningBalance >= 0 ? 'Dr' : 'Cr' }}</td>
                </tr>
                @endif
                @forelse($transactions ?? [] as $txn)
                @php
                    $runningBalance += $txn->debit - $txn->credit;
                @endphp
                <tr>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $txn->journalEntry->entry_date->format('M d, Y') }}</td>
                    <td>
                        <a href="{{ route('accounting.journal-entries.show', $txn->journalEntry) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.85rem;">
                            {{ $txn->journalEntry->entry_number }}
                        </a>
                    </td>
                    <td style="font-size: 0.85rem;">{{ $txn->description ?: $txn->journalEntry->narration }}</td>
                    <td class="text-end" style="font-size: 0.85rem;">{{ $txn->debit > 0 ? 'PKR ' . number_format($txn->debit, 2) : '-' }}</td>
                    <td class="text-end" style="font-size: 0.85rem;">{{ $txn->credit > 0 ? 'PKR ' . number_format($txn->credit, 2) : '-' }}</td>
                    <td class="text-end" style="font-weight: 600; font-size: 0.85rem;">
                        PKR {{ number_format(abs($runningBalance), 2) }} {{ $runningBalance >= 0 ? 'Dr' : 'Cr' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-journal-text" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No transactions found for this period.
                    </td>
                </tr>
                @endforelse
                @if(($transactions ?? collect())->count() > 0)
                <tr style="background: linear-gradient(180deg, #fafbfc 0%, #f6f7f9 100%); border-top: 2px solid #e5e7eb;">
                    <td colspan="3" style="font-weight: 700; color: var(--primary); font-size: 0.85rem;">Closing Balance</td>
                    <td class="text-end" style="font-weight: 700; font-size: 0.85rem;">PKR {{ number_format(($transactions ?? collect())->sum('debit'), 2) }}</td>
                    <td class="text-end" style="font-weight: 700; font-size: 0.85rem;">PKR {{ number_format(($transactions ?? collect())->sum('credit'), 2) }}</td>
                    <td class="text-end" style="font-weight: 800; font-size: 0.9rem; color: var(--primary);">
                        PKR {{ number_format(abs($runningBalance), 2) }} {{ $runningBalance >= 0 ? 'Dr' : 'Cr' }}
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($transactions ?? collect(), 'links'))
<div class="mt-3">{{ $transactions->links() }}</div>
@endif
@endsection
