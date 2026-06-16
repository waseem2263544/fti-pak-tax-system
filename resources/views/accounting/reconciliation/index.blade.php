@extends('layouts.app')
@section('title', 'Bank Reconciliation')
@section('page-title', 'Bank Reconciliation')

@section('content')
@php $money = fn($v) => 'PKR ' . number_format((float) $v, 2); @endphp
<div class="mb-3" style="color:#6b7280; font-size:0.85rem;">Select a cash or bank account to reconcile against its statement.</div>

<div class="row g-3">
    @forelse($accounts as $a)
    <div class="col-md-4">
        <a href="{{ route('accounting.reconciliation.show', $a) }}" class="card text-decoration-none" style="padding:18px; border:1.5px solid #e8eaed;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div style="font-weight:700; color:var(--primary);">{{ $a->name }}</div>
                    <div style="font-size:0.78rem; color:#9ca3af;">{{ $a->code }}</div>
                </div>
                <i class="bi bi-bank2" style="font-size:1.4rem; color:#8b5cf6;"></i>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-end">
                <div><div style="font-size:0.72rem; color:#9ca3af;">BOOK BALANCE</div><div style="font-weight:700;">{{ $money($a->computed_balance) }}</div></div>
                @if($a->uncleared_count > 0)<span class="badge" style="background:#fef3c7; color:#92400e;">{{ $a->uncleared_count }} uncleared</span>@else<span class="badge" style="background:#d1fae5; color:#065f46;">All cleared</span>@endif
            </div>
        </a>
    </div>
    @empty
    <div class="col-12"><div class="card"><div class="card-body text-center py-5" style="color:#9ca3af;">No bank or cash accounts found.</div></div></div>
    @endforelse
</div>
@endsection
