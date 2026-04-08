@extends('layouts.app')
@section('title', 'General Ledger')
@section('page-title', 'General Ledger')

@section('styles')
<style>
    @media print {
        .sidebar, .top-nav, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .card { box-shadow: none !important; border: none !important; }
        .ledger-card { break-inside: avoid; }
    }
</style>
@endsection

@section('content')
<!-- Filter -->
<div class="card mb-4 no-print">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.reports.general-ledger') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Account</label>
                    <select class="form-select searchable" name="account_id">
                        <option value="">All Accounts</option>
                        @foreach($allAccounts ?? [] as $acc)
                            <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="from_date" value="{{ request('from_date', $fromDate ?? date('Y-01-01')) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="to_date" value="{{ request('to_date', $toDate ?? date('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Generate</button>
                </div>
                <div class="col-md-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-primary w-100"><i class="bi bi-printer me-1"></i> Print</button>
                </div>
                @if(request()->hasAny(['account_id', 'from_date', 'to_date']))
                <div class="col-md-1">
                    <a href="{{ route('accounting.reports.general-ledger') }}" class="btn btn-outline-primary w-100" title="Clear"><i class="bi bi-x-lg"></i></a>
                </div>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Report Header -->
<div class="card mb-4">
    <div class="card-body" style="padding: 32px; text-align: center;">
        <h4 style="font-weight: 800; color: var(--primary); margin: 0;">FairTax International</h4>
        <h5 style="font-weight: 600; color: var(--primary); margin: 8px 0 4px;">General Ledger</h5>
        <div style="font-size: 0.85rem; color: #6b7280;">
            {{ \Carbon\Carbon::parse(request('from_date', $fromDate ?? date('Y-01-01')))->format('F d, Y') }}
            to
            {{ \Carbon\Carbon::parse(request('to_date', $toDate ?? now()))->format('F d, Y') }}
        </div>
    </div>
</div>

<!-- Ledger Data -->
@forelse($ledgerData ?? [] as $ledger)
<div class="card mb-4 ledger-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            <span style="font-weight: 700; color: var(--primary);">{{ $ledger['account']->code ?? '' }} - {{ $ledger['account']->name ?? '' }}</span>
        </div>
        <span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary); font-weight: 600;">
            {{ ucfirst($ledger['account']->type ?? '') }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 12%;">JE #</th>
                    <th style="width: 36%;">Description</th>
                    <th class="text-end" style="width: 14%;">Debit</th>
                    <th class="text-end" style="width: 14%;">Credit</th>
                    <th class="text-end" style="width: 14%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($ledger['opening_balance']))
                <tr style="background: rgba(48,58,80,0.02);">
                    <td colspan="5" style="font-size: 0.82rem; font-weight: 600; color: #6b7280;">Opening Balance</td>
                    <td class="text-end" style="font-weight: 700; font-size: 0.85rem; color: var(--primary);">PKR {{ number_format($ledger['opening_balance'] ?? 0, 2) }}</td>
                </tr>
                @endif

                @forelse($ledger['transactions'] ?? [] as $txn)
                <tr>
                    <td style="font-size: 0.82rem; color: #6b7280;">{{ \Carbon\Carbon::parse($txn->date)->format('M d, Y') }}</td>
                    <td>
                        @if($txn->journal_entry_id ?? null)
                        <a href="{{ route('accounting.journal-entries.show', $txn->journal_entry_id) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.82rem;">
                            {{ $txn->entry_number ?? '-' }}
                        </a>
                        @else
                        <span style="font-size: 0.82rem; color: #9ca3af;">-</span>
                        @endif
                    </td>
                    <td style="font-size: 0.85rem; color: #374151;">{{ $txn->description ?? '-' }}</td>
                    <td class="text-end" style="font-size: 0.85rem; {{ ($txn->debit ?? 0) > 0 ? 'font-weight: 600; color: var(--primary);' : 'color: #d1d5db;' }}">
                        {{ ($txn->debit ?? 0) > 0 ? 'PKR ' . number_format($txn->debit, 2) : '-' }}
                    </td>
                    <td class="text-end" style="font-size: 0.85rem; {{ ($txn->credit ?? 0) > 0 ? 'font-weight: 600; color: var(--primary);' : 'color: #d1d5db;' }}">
                        {{ ($txn->credit ?? 0) > 0 ? 'PKR ' . number_format($txn->credit, 2) : '-' }}
                    </td>
                    <td class="text-end" style="font-size: 0.85rem; font-weight: 600; color: var(--primary);">
                        PKR {{ number_format($txn->running_balance ?? 0, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-3" style="color: #9ca3af; font-size: 0.85rem;">No transactions for this period.</td>
                </tr>
                @endforelse

                @if(isset($ledger['closing_balance']))
                <tr style="background: var(--primary);">
                    <td colspan="5" style="font-weight: 700; color: #fff; font-size: 0.85rem; padding-left: 16px;">Closing Balance</td>
                    <td class="text-end" style="font-weight: 800; color: #fff; font-size: 0.9rem;">PKR {{ number_format($ledger['closing_balance'] ?? 0, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center py-5" style="color: #9ca3af;">
        <i class="bi bi-journal-text" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
        No ledger data found. Select an account and date range, then click Generate.
    </div>
</div>
@endforelse
@endsection
