@extends('layouts.app')
@section('title', 'Reconcile ' . $account->name)
@section('page-title', 'Reconcile · ' . $account->name)

@section('content')
@php $money = fn($v) => 'PKR ' . number_format((float) $v, 2); @endphp

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<a href="{{ route('accounting.reconciliation.index') }}" style="color:#9ca3af; text-decoration:none; font-size:0.85rem;"><i class="bi bi-arrow-left me-1"></i>All accounts</a>

<form method="GET" action="{{ route('accounting.reconciliation.show', $account) }}" class="card my-3">
    <div class="card-body row g-2 align-items-end" style="padding:16px;">
        <div class="col-md-3">
            <label class="form-label">Up to date</label>
            <input type="date" name="as_of_date" class="form-control" value="{{ $asOf }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Statement closing balance</label>
            <input type="number" step="0.01" name="statement_balance" class="form-control" value="{{ $statementBalance }}" placeholder="From your bank statement">
        </div>
        <div class="col-md-2"><button class="btn btn-accent w-100"><i class="bi bi-arrow-repeat me-1"></i>Load</button></div>
    </div>
</form>

<form method="POST" action="{{ route('accounting.reconciliation.save', $account) }}">
    @csrf
    <input type="hidden" name="as_of_date" value="{{ $asOf }}">
    <input type="hidden" name="statement_balance" value="{{ $statementBalance }}">

    <!-- Summary -->
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card stat-card"><div><div class="stat-label">Opening</div><div class="stat-value" style="font-size:1.1rem;">{{ $money($openingBalance) }}</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card"><div><div class="stat-label">Cleared Balance</div><div class="stat-value" style="font-size:1.1rem;" id="clearedBalance">{{ $money($clearedBalance) }}</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card"><div><div class="stat-label">Statement</div><div class="stat-value" style="font-size:1.1rem;">{{ $statementBalance !== null ? $money($statementBalance) : '—' }}</div></div></div></div>
        <div class="col-md-3"><div class="card stat-card" id="diffCard"><div><div class="stat-label">Difference</div><div class="stat-value" style="font-size:1.1rem;" id="diffValue">{{ $difference !== null ? $money($difference) : '—' }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check me-1"></i>Transactions up to {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}</span>
            <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-save me-1"></i>Save Reconciliation</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th style="width:40px;">✓</th><th>Date</th><th>Entry</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                <tbody>
                    @forelse($lines as $line)
                    <tr>
                        <td><input type="checkbox" class="form-check-input clr" name="cleared_lines[]" value="{{ $line->id }}" data-amt="{{ (float)$line->debit - (float)$line->credit }}" {{ $line->cleared ? 'checked' : '' }}></td>
                        <td style="font-size:0.82rem; color:#6b7280; white-space:nowrap;">{{ optional($line->journalEntry->date)->format('d M Y') }}</td>
                        <td style="font-size:0.82rem;"><a href="{{ route('accounting.journal-entries.show', $line->journal_entry_id) }}">{{ $line->journalEntry->entry_number }}</a></td>
                        <td style="font-size:0.82rem;">{{ $line->description ?: $line->journalEntry->narration }}</td>
                        <td class="text-end" style="font-size:0.85rem;">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                        <td class="text-end" style="font-size:0.85rem;">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4" style="color:#9ca3af;">No posted transactions for this account.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
(function () {
    var opening = {{ $openingBalance }};
    var statement = {{ $statementBalance !== null ? $statementBalance : 'null' }};
    function fmt(v) { return 'PKR ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function recalc() {
        var cleared = opening;
        document.querySelectorAll('.clr:checked').forEach(function (c) { cleared += parseFloat(c.dataset.amt) || 0; });
        document.getElementById('clearedBalance').textContent = fmt(cleared);
        if (statement !== null) {
            var diff = statement - cleared;
            document.getElementById('diffValue').textContent = fmt(diff);
            var card = document.getElementById('diffCard');
            card.style.background = Math.abs(diff) < 0.005 ? 'rgba(16,185,129,0.10)' : 'rgba(239,68,68,0.07)';
        }
    }
    document.querySelectorAll('.clr').forEach(function (c) { c.addEventListener('change', recalc); });
    recalc();
})();
</script>
@endsection
