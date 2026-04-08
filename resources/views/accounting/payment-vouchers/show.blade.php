@extends('layouts.app')
@section('title', 'Payment ' . $voucher->voucher_number)
@section('page-title', 'Payment Vouchers')

@section('styles')
<style>
    @media print {
        .sidebar, .top-nav, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .card { box-shadow: none !important; border: none !important; }
    }
</style>
@endsection

@section('content')
<!-- Action Bar -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="{{ route('accounting.payment-vouchers.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Payment Vouchers
    </a>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
</div>

<!-- Voucher Document -->
<div class="card" style="max-width: 750px; margin: 0 auto;">
    <div class="card-body" style="padding: 48px;">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start mb-5">
            <div>
                <h3 style="font-weight: 800; color: var(--primary); margin: 0;">PAYMENT VOUCHER</h3>
                <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary); margin-top: 4px;">{{ $voucher->voucher_number }}</div>
            </div>
            <div class="text-end">
                <h5 style="font-weight: 800; color: var(--primary); margin: 0;">FairTax International</h5>
                <div style="font-size: 0.82rem; color: #6b7280; line-height: 1.8;">
                    Tax & Business Consultants<br>
                    Islamabad, Pakistan
                </div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Paid To</div>
                <div style="font-weight: 700; font-size: 1rem; color: var(--primary);">{{ $voucher->contact->name ?? $voucher->party_name ?? '-' }}</div>
                @if($voucher->contact->email ?? null)
                    <div style="font-size: 0.85rem; color: #6b7280;">{{ $voucher->contact->email }}</div>
                @endif
            </div>
            <div class="col-md-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Date</div>
                        <div style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">{{ $voucher->payment_date->format('F d, Y') }}</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Status</div>
                        @if($voucher->status === 'posted')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Posted</span>
                        @elseif($voucher->status === 'draft')
                            <span class="badge" style="background: #f3f4f6; color: #6b7280;">Draft</span>
                        @elseif($voucher->status === 'cancelled')
                            <span class="badge" style="background: #fef2f2; color: #991b1b;">Cancelled</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Amount Card -->
        <div style="background: rgba(239,68,68,0.04); border: 1px solid rgba(239,68,68,0.15); border-radius: 12px; padding: 28px; text-align: center; margin-bottom: 32px;">
            <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Amount Paid</div>
            <div style="font-weight: 800; font-size: 2.2rem; color: #ef4444;">PKR {{ number_format($voucher->amount, 2) }}</div>
        </div>

        <!-- Payment Details -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Payment Method</div>
                <div style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">
                    {{ ucfirst(str_replace('_', ' ', $voucher->payment_method)) }}
                </div>
            </div>
            @if($voucher->cheque_number)
            <div class="col-md-4">
                <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Cheque Number</div>
                <div style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">{{ $voucher->cheque_number }}</div>
            </div>
            @endif
            <div class="col-md-4">
                <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Paying Account</div>
                <div style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">{{ $voucher->account->name ?? '-' }}</div>
            </div>
        </div>

        @if($voucher->bill)
        <div class="mb-4">
            <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Linked Bill</div>
            <a href="{{ route('accounting.purchase-invoices.show', $voucher->bill) }}" style="font-size: 0.9rem; font-weight: 600; color: var(--primary); text-decoration: none;">
                {{ $voucher->bill->bill_number }} <i class="bi bi-box-arrow-up-right" style="font-size: 0.75rem;"></i>
            </a>
        </div>
        @endif

        @if($voucher->journalEntry)
        <div class="mb-4">
            <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Journal Entry</div>
            <a href="{{ route('accounting.journal-entries.show', $voucher->journalEntry) }}" style="font-size: 0.9rem; font-weight: 600; color: var(--primary); text-decoration: none;">
                {{ $voucher->journalEntry->entry_number }} <i class="bi bi-box-arrow-up-right" style="font-size: 0.75rem;"></i>
            </a>
        </div>
        @endif

        @if($voucher->narration)
        <div class="mt-4 pt-4" style="border-top: 1px solid #f0f2f5;">
            <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Narration</div>
            <p style="font-size: 0.85rem; color: #6b7280; margin: 0; white-space: pre-line;">{{ $voucher->narration }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
