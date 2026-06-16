@extends('layouts.app')
@section('title', 'Customer Statement')
@section('page-title', 'Customer Statement of Account')

@push('styles')
@endpush

@section('content')
<style>
    @media print {
        .sidebar, .top-nav, .no-print { display: none !important; }
        body { background: #fff; }
        .main-content { margin: 0 !important; padding: 0 !important; }
    }
    .stmt-table th { background: #303a50; color: #fff; padding: 8px 10px; font-size: 0.8rem; }
    .stmt-table td { padding: 7px 10px; border-bottom: 1px solid #eef0f3; font-size: 0.85rem; }
    .stmt-table tr.r, .stmt-table td.r, .stmt-table th.r { text-align: right; }
</style>

@php $money = fn($v) => 'PKR ' . number_format((float) $v, 2); @endphp

<!-- Filter -->
<div class="card mb-4 no-print">
    <div class="card-body" style="padding: 18px;">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Client</label>
                <select name="client_id" class="form-select tom" required>
                    <option value="">Select client…</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ (string) request('client_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-accent flex-fill"><i class="bi bi-search"></i></button>
                @if($client)<button type="button" onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer"></i></button>@endif
            </div>
        </form>
    </div>
</div>

@if($client)
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <div class="d-flex justify-content-between mb-4">
            <div>
                <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary);">Statement of Account</div>
                <div style="color: #6b7280; font-size: 0.85rem;">{{ $client->name }}</div>
                @if($client->contact_no)<div style="color: #9ca3af; font-size: 0.8rem;">{{ $client->contact_no }}</div>@endif
            </div>
            <div class="text-end" style="font-size: 0.82rem; color: #6b7280;">
                <div>Period: {{ $fromDate ? \Carbon\Carbon::parse($fromDate)->format('d M Y') : 'Beginning' }} – {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
                <div class="mt-2" style="font-size: 1.1rem; font-weight: 700; color: {{ $closingBalance > 0 ? '#dc2626' : 'var(--primary)' }};">Balance Due: {{ $money($closingBalance) }}</div>
            </div>
        </div>

        <table class="stmt-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="width: 13%;">Date</th>
                    <th style="width: 15%;">Type</th>
                    <th>Reference</th>
                    <th class="r" style="width: 16%;">Debit</th>
                    <th class="r" style="width: 16%;">Credit</th>
                    <th class="r" style="width: 18%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background: #f8f9fb;">
                    <td colspan="5"><strong>Opening Balance</strong></td>
                    <td class="r" style="text-align: right;"><strong>{{ $money($openingBalance) }}</strong></td>
                </tr>
                @forelse($rows as $r)
                <tr>
                    <td>{{ $r['date']->format('d M Y') }}</td>
                    <td>
                        @if($r['type'] === 'Invoice')<span class="badge" style="background:#dbeafe;color:#1e40af;">Invoice</span>
                        @else<span class="badge" style="background:#d1fae5;color:#065f46;">Receipt</span>@endif
                    </td>
                    <td>{{ $r['ref'] }}</td>
                    <td class="r" style="text-align: right;">{{ $r['debit'] ? $money($r['debit']) : '—' }}</td>
                    <td class="r" style="text-align: right;">{{ $r['credit'] ? $money($r['credit']) : '—' }}</td>
                    <td class="r" style="text-align: right; font-weight: 600;">{{ $money($r['balance']) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4" style="color:#9ca3af;">No transactions in this period.</td></tr>
                @endforelse
                <tr style="background: #303a50; color: #fff;">
                    <td colspan="3"><strong>Totals</strong></td>
                    <td class="r" style="text-align: right;"><strong>{{ $money($totalDebit) }}</strong></td>
                    <td class="r" style="text-align: right;"><strong>{{ $money($totalCredit) }}</strong></td>
                    <td class="r" style="text-align: right;"><strong>{{ $money($closingBalance) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card"><div class="card-body text-center py-5" style="color:#9ca3af;">
    <i class="bi bi-person-lines-fill" style="font-size: 2.5rem; opacity: 0.3;"></i>
    <div class="mt-2">Select a client to generate their statement of account.</div>
</div></div>
@endif
@endsection

@section('scripts')
<script>
    if (window.TomSelect) { document.querySelectorAll('.tom').forEach(function (el) { new TomSelect(el, { create: false }); }); }
</script>
@endsection
