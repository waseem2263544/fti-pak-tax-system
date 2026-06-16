@extends('layouts.app')
@section('title', 'Accounting Health Check')
@section('page-title', 'Accounting Health Check')

@section('content')
@php $money = fn($v) => 'PKR ' . number_format((float) $v, 2); @endphp

<div class="mb-4" style="color:#6b7280; font-size:0.85rem;">Automated checks that your books are internally consistent.</div>

<!-- Overall ledger balance -->
<div class="card mb-3">
    <div class="card-body d-flex align-items-center justify-content-between" style="padding: 18px 24px;">
        <div>
            <div style="font-weight:600; color:var(--primary);">
                @if($globalBalanced)<i class="bi bi-check-circle-fill text-success me-1"></i>Ledger is balanced @else<i class="bi bi-x-circle-fill text-danger me-1"></i>Ledger is OUT OF BALANCE @endif
            </div>
            <div class="muted" style="font-size:0.82rem; color:#6b7280;">Total posted debits {{ $money($globalDebit) }} vs credits {{ $money($globalCredit) }} (difference {{ $money($globalDebit - $globalCredit) }})</div>
        </div>
        <span class="badge" style="background: {{ $globalBalanced ? '#d1fae5' : '#fef2f2' }}; color: {{ $globalBalanced ? '#065f46' : '#dc2626' }}; font-size: 0.85rem; padding: 8px 14px;">{{ $globalBalanced ? 'PASS' : 'FAIL' }}</span>
    </div>
</div>

<!-- Unbalanced entries -->
<div class="card mb-3">
    <div class="card-body" style="padding: 18px 24px;">
        <div style="font-weight:600; color:var(--primary); margin-bottom:8px;">
            @if($unbalancedEntries->isEmpty())<i class="bi bi-check-circle-fill text-success me-1"></i>All posted journal entries balance
            @else<i class="bi bi-x-circle-fill text-danger me-1"></i>{{ $unbalancedEntries->count() }} unbalanced journal entr{{ $unbalancedEntries->count() == 1 ? 'y' : 'ies' }}@endif
        </div>
        @if($unbalancedEntries->isNotEmpty())
        <ul style="margin:0; font-size:0.85rem;">
            @foreach($unbalancedEntries as $je)
            <li><a href="{{ route('accounting.journal-entries.show', $je) }}">{{ $je->entry_number }}</a> — {{ optional($je->date)->format('d M Y') }}</li>
            @endforeach
        </ul>
        @endif
    </div>
</div>

<!-- Control accounts -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-diagram-3 me-1"></i>Control Account Configuration</div>
    <div class="card-body" style="padding: 18px 24px;">
        <div class="row g-2">
            @foreach($controlAccounts as $ca)
            <div class="col-md-6 d-flex align-items-center justify-content-between" style="border-bottom:1px solid #f0f2f5; padding:6px 0;">
                <span style="font-size:0.85rem;">{{ $ca['label'] }}</span>
                @if($ca['account'])<span style="font-size:0.82rem; color:#065f46;"><i class="bi bi-check2 me-1"></i>{{ $ca['account'] }}</span>
                @else<span style="font-size:0.82rem; color:#dc2626;"><i class="bi bi-exclamation-triangle me-1"></i>Not configured</span>@endif
            </div>
            @endforeach
        </div>
        <div class="mt-2" style="font-size:0.8rem; color:#9ca3af;">Configure these in <a href="{{ route('accounting.settings.index') }}">Settings</a>.</div>
    </div>
</div>

<!-- Other checks -->
<div class="card">
    <div class="card-body" style="padding: 18px 24px;">
        <div style="padding:6px 0; border-bottom:1px solid #f0f2f5;">
            @if($activeFy)<i class="bi bi-check-circle-fill text-success me-1"></i>Active fiscal year: <strong>{{ $activeFy->name }}</strong>
            @else<i class="bi bi-x-circle-fill text-danger me-1"></i>No active fiscal year set@endif
        </div>
        <div style="padding:6px 0; border-bottom:1px solid #f0f2f5;">
            @if($invoicesNoJe == 0)<i class="bi bi-check-circle-fill text-success me-1"></i>All non-draft sales invoices have a journal entry
            @else<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>{{ $invoicesNoJe }} non-draft sales invoice(s) without a journal entry@endif
        </div>
        <div style="padding:6px 0;">
            @if($billsNoJe == 0)<i class="bi bi-check-circle-fill text-success me-1"></i>All non-draft purchase bills have a journal entry
            @else<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>{{ $billsNoJe }} non-draft purchase bill(s) without a journal entry@endif
        </div>
    </div>
</div>
@endsection
