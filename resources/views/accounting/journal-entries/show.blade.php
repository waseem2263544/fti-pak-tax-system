@extends('layouts.app')
@section('title', 'Journal Entry ' . $entry->entry_number)
@section('page-title', 'Journal Entries')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('accounting.journal-entries.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Journal Entries
    </a>
</div>

<!-- Header -->
<div class="card mb-4">
    <div class="card-body" style="padding: 28px;">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <h4 style="margin: 0; font-weight: 800; color: var(--primary);">{{ $entry->entry_number }}</h4>
                    @if($entry->status === 'posted')
                        <span class="badge" style="background: #d1fae5; color: #065f46; font-size: 0.78rem;">Posted</span>
                    @elseif($entry->status === 'draft')
                        <span class="badge" style="background: #fef3c7; color: #92400e; font-size: 0.78rem;">Draft</span>
                    @else
                        <span class="badge" style="background: #fef2f2; color: #dc2626; font-size: 0.78rem;">Reversed</span>
                    @endif
                </div>
                <div style="font-size: 0.85rem; color: #6b7280;">
                    <i class="bi bi-calendar3 me-1"></i> {{ $entry->entry_date->format('F d, Y') }}
                    @if($entry->reference)
                        <span class="ms-3"><i class="bi bi-hash me-1"></i> {{ $entry->reference }}</span>
                    @endif
                </div>
                <div style="font-size: 0.9rem; color: var(--primary); margin-top: 8px;">{{ $entry->narration }}</div>
            </div>
            <div class="d-flex gap-2">
                @if($entry->status === 'draft')
                    <form action="{{ route('accounting.journal-entries.post', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('Post this journal entry? This cannot be undone.')">
                        @csrf
                        <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-check-circle me-1"></i> Post</button>
                    </form>
                    <a href="{{ route('accounting.journal-entries.edit', $entry) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>
                    <form action="{{ route('accounting.journal-entries.destroy', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this draft entry?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm" style="border: 1.5px solid #fecaca; color: #dc2626; border-radius: 10px; font-weight: 500; font-size: 0.82rem;"><i class="bi bi-trash me-1"></i> Delete</button>
                    </form>
                @elseif($entry->status === 'posted')
                    <form action="{{ route('accounting.journal-entries.reverse', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this journal entry? A new reversing entry will be created.')">
                        @csrf
                        <button type="submit" class="btn btn-sm" style="border: 1.5px solid #fecaca; color: #dc2626; border-radius: 10px; font-weight: 500; font-size: 0.82rem;"><i class="bi bi-arrow-counterclockwise me-1"></i> Reverse</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Lines Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th style="width: 15%;">Account Code</th>
                    <th style="width: 30%;">Account Name</th>
                    <th style="width: 25%;">Description</th>
                    <th style="width: 15%;" class="text-end">Debit</th>
                    <th style="width: 15%;" class="text-end">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->lines as $line)
                <tr>
                    <td>
                        <a href="{{ route('accounting.accounts.show', $line->account) }}" style="font-family: monospace; font-size: 0.82rem; color: var(--primary); font-weight: 600; text-decoration: none;">
                            {{ $line->account->code }}
                        </a>
                    </td>
                    <td style="font-size: 0.85rem; font-weight: 500;">{{ $line->account->name }}</td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $line->description ?: '-' }}</td>
                    <td class="text-end" style="font-size: 0.85rem; {{ $line->debit > 0 ? 'font-weight: 600;' : 'color: #d1d5db;' }}">
                        {{ $line->debit > 0 ? 'PKR ' . number_format($line->debit, 2) : '-' }}
                    </td>
                    <td class="text-end" style="font-size: 0.85rem; {{ $line->credit > 0 ? 'font-weight: 600;' : 'color: #d1d5db;' }}">
                        {{ $line->credit > 0 ? 'PKR ' . number_format($line->credit, 2) : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: linear-gradient(180deg, #fafbfc 0%, #f6f7f9 100%); border-top: 2px solid #e5e7eb;">
                    <td colspan="3" style="font-weight: 700; color: var(--primary); font-size: 0.88rem; padding: 16px 20px;">Total</td>
                    <td class="text-end" style="font-weight: 800; font-size: 0.95rem; color: var(--primary); padding: 16px 20px;">
                        PKR {{ number_format($entry->lines->sum('debit'), 2) }}
                    </td>
                    <td class="text-end" style="font-weight: 800; font-size: 0.95rem; color: var(--primary); padding: 16px 20px;">
                        PKR {{ number_format($entry->lines->sum('credit'), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if($entry->source_type && $entry->source_type !== 'manual')
<div class="card mt-3">
    <div class="card-body" style="padding: 16px 20px;">
        <div style="font-size: 0.82rem; color: #6b7280;">
            <i class="bi bi-link-45deg me-1"></i>
            Auto-generated from:
            @if($entry->source_type === 'sales_invoice')
                <a href="{{ route('accounting.sales-invoices.show', $entry->source_id) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">Sales Invoice #{{ $entry->source_id }}</a>
            @elseif($entry->source_type === 'purchase_invoice')
                <a href="{{ route('accounting.purchase-invoices.show', $entry->source_id) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">Purchase Invoice #{{ $entry->source_id }}</a>
            @elseif($entry->source_type === 'receipt')
                <a href="{{ route('accounting.receipt-vouchers.show', $entry->source_id) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">Receipt Voucher #{{ $entry->source_id }}</a>
            @elseif($entry->source_type === 'payment')
                <a href="{{ route('accounting.payment-vouchers.show', $entry->source_id) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">Payment Voucher #{{ $entry->source_id }}</a>
            @endif
        </div>
    </div>
</div>
@endif
@endsection
