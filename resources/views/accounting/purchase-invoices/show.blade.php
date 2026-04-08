@extends('layouts.app')
@section('title', 'Bill ' . $purchaseInvoice->bill_number)
@section('page-title', 'Purchase Invoices')

@section('styles')
<style>
    @media print {
        .sidebar, .top-nav, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .card { box-shadow: none !important; border: none !important; }
        .bill-card { break-inside: avoid; }
    }
</style>
@endsection

@section('content')
<!-- Action Bar -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="{{ route('accounting.purchase-invoices.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Purchase Invoices
    </a>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer me-1"></i> Print</button>
        @if($purchaseInvoice->status === 'draft')
            <a href="{{ route('accounting.purchase-invoices.edit', $purchaseInvoice) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>
        @endif
        @if(in_array($purchaseInvoice->status, ['received', 'partial', 'overdue']))
            <a href="{{ route('accounting.payment-vouchers.create') }}?bill_id={{ $purchaseInvoice->id }}" class="btn btn-accent btn-sm"><i class="bi bi-cash-coin me-1"></i> Record Payment</a>
        @endif
    </div>
</div>

<!-- Bill Document -->
<div class="card bill-card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-body" style="padding: 48px;">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start mb-5">
            <div>
                <h3 style="font-weight: 800; color: var(--primary); margin: 0;">PURCHASE BILL</h3>
                <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary); margin-top: 4px;">{{ $purchaseInvoice->bill_number }}</div>
            </div>
            <div class="text-end">
                <h5 style="font-weight: 800; color: var(--primary); margin: 0;">FairTax International</h5>
                <div style="font-size: 0.82rem; color: #6b7280; line-height: 1.8;">
                    Tax & Business Consultants<br>
                    Islamabad, Pakistan
                </div>
            </div>
        </div>

        <!-- Meta Row -->
        <div class="row mb-5">
            <div class="col-md-6">
                <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Vendor</div>
                <div style="font-weight: 700; font-size: 1rem; color: var(--primary);">{{ $purchaseInvoice->contact->name ?? $purchaseInvoice->vendor_name ?? '-' }}</div>
                @if($purchaseInvoice->contact->email ?? null)
                    <div style="font-size: 0.85rem; color: #6b7280;">{{ $purchaseInvoice->contact->email }}</div>
                @endif
                @if($purchaseInvoice->contact->phone ?? null)
                    <div style="font-size: 0.85rem; color: #6b7280;">{{ $purchaseInvoice->contact->phone }}</div>
                @endif
            </div>
            <div class="col-md-6 text-end">
                <div class="row g-2">
                    <div class="col-6 text-start">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Bill Date</div>
                        <div style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">{{ $purchaseInvoice->bill_date->format('F d, Y') }}</div>
                    </div>
                    <div class="col-6 text-start">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Due Date</div>
                        <div style="font-size: 0.9rem; font-weight: 600; {{ $purchaseInvoice->due_date < now() && $purchaseInvoice->status !== 'paid' ? 'color: #ef4444;' : 'color: var(--primary);' }}">{{ $purchaseInvoice->due_date->format('F d, Y') }}</div>
                    </div>
                    <div class="col-6 text-start mt-2">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Status</div>
                        @if($purchaseInvoice->status === 'paid')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Paid</span>
                        @elseif($purchaseInvoice->status === 'partial')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Partially Paid</span>
                        @elseif($purchaseInvoice->status === 'received')
                            <span class="badge" style="background: var(--accent-glow); color: #5c6300;">Received</span>
                        @elseif($purchaseInvoice->status === 'draft')
                            <span class="badge" style="background: #f3f4f6; color: #6b7280;">Draft</span>
                        @elseif($purchaseInvoice->status === 'overdue')
                            <span class="badge" style="background: #fef2f2; color: #dc2626;">Overdue</span>
                        @endif
                    </div>
                    @if($purchaseInvoice->vendor_invoice_number)
                    <div class="col-6 text-start mt-2">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Vendor Invoice #</div>
                        <div style="font-size: 0.85rem; color: var(--primary);">{{ $purchaseInvoice->vendor_invoice_number }}</div>
                    </div>
                    @endif
                    @if($purchaseInvoice->reference)
                    <div class="col-6 text-start mt-2">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Reference</div>
                        <div style="font-size: 0.85rem; color: var(--primary);">{{ $purchaseInvoice->reference }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="table-responsive" style="margin: 0 -8px;">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 40%;">Description</th>
                        <th class="text-end" style="width: 12%;">Qty</th>
                        <th class="text-end" style="width: 15%;">Unit Price</th>
                        <th class="text-end" style="width: 12%;">Tax</th>
                        <th class="text-end" style="width: 16%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseInvoice->items ?? [] as $i => $item)
                    <tr>
                        <td style="font-size: 0.82rem; color: #9ca3af;">{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight: 600; font-size: 0.88rem; color: var(--primary);">{{ $item->account->name ?? '-' }}</div>
                            @if($item->description)
                                <div style="font-size: 0.78rem; color: #9ca3af;">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td class="text-end" style="font-size: 0.85rem;">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-end" style="font-size: 0.85rem;">PKR {{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end" style="font-size: 0.85rem;">{{ $item->tax_rate > 0 ? number_format($item->tax_rate, 1) . '%' : '-' }}</td>
                        <td class="text-end" style="font-weight: 600; font-size: 0.85rem;">PKR {{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="d-flex justify-content-end mt-4">
            <div style="width: 320px;">
                <div class="d-flex justify-content-between py-2" style="border-bottom: 1px solid #f0f2f5;">
                    <span style="font-size: 0.85rem; color: #6b7280;">Subtotal</span>
                    <span style="font-weight: 600;">PKR {{ number_format($purchaseInvoice->subtotal ?? $purchaseInvoice->items->sum(function($i) { return $i->quantity * $i->unit_price; }), 2) }}</span>
                </div>
                @if(($purchaseInvoice->tax_amount ?? 0) > 0)
                <div class="d-flex justify-content-between py-2" style="border-bottom: 1px solid #f0f2f5;">
                    <span style="font-size: 0.85rem; color: #6b7280;">Tax</span>
                    <span style="font-weight: 600;">PKR {{ number_format($purchaseInvoice->tax_amount, 2) }}</span>
                </div>
                @endif
                @if(($purchaseInvoice->discount ?? 0) > 0)
                <div class="d-flex justify-content-between py-2" style="border-bottom: 1px solid #f0f2f5;">
                    <span style="font-size: 0.85rem; color: #6b7280;">Discount</span>
                    <span style="font-weight: 600; color: #ef4444;">- PKR {{ number_format($purchaseInvoice->discount, 2) }}</span>
                </div>
                @endif
                <div class="d-flex justify-content-between py-3" style="border-top: 2px solid var(--primary);">
                    <span style="font-weight: 700; font-size: 1rem; color: var(--primary);">Total</span>
                    <span style="font-weight: 800; font-size: 1.15rem; color: var(--primary);">PKR {{ number_format($purchaseInvoice->total, 2) }}</span>
                </div>
                @if($purchaseInvoice->paid_amount > 0)
                <div class="d-flex justify-content-between py-2" style="border-bottom: 1px solid #f0f2f5;">
                    <span style="font-size: 0.85rem; color: #10b981;">Paid</span>
                    <span style="font-weight: 600; color: #10b981;">- PKR {{ number_format($purchaseInvoice->paid_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between py-3" style="background: rgba(245,158,11,0.06); padding: 12px; border-radius: 8px;">
                    <span style="font-weight: 700; font-size: 1rem; color: #92400e;">Balance Due</span>
                    <span style="font-weight: 800; font-size: 1.15rem; color: #92400e;">PKR {{ number_format($purchaseInvoice->total - $purchaseInvoice->paid_amount, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if($purchaseInvoice->notes)
        <div class="mt-5 pt-4" style="border-top: 1px solid #f0f2f5;">
            <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Notes</div>
            <p style="font-size: 0.85rem; color: #6b7280; margin: 0; white-space: pre-line;">{{ $purchaseInvoice->notes }}</p>
        </div>
        @endif

        @if($purchaseInvoice->terms)
        <div class="mt-3">
            <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Terms &amp; Conditions</div>
            <p style="font-size: 0.85rem; color: #6b7280; margin: 0; white-space: pre-line;">{{ $purchaseInvoice->terms }}</p>
        </div>
        @endif
    </div>
</div>

<!-- Payment History -->
@if(($purchaseInvoice->payments ?? collect())->count() > 0)
<div class="card mt-4 no-print" style="max-width: 900px; margin: 20px auto 0;">
    <div class="card-header d-flex align-items-center gap-2">
        <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
        Payment History
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Voucher #</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseInvoice->payments as $payment)
                <tr>
                    <td>
                        <a href="{{ route('accounting.payment-vouchers.show', $payment) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.85rem;">
                            {{ $payment->voucher_number }}
                        </a>
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $payment->payment_date->format('M d, Y') }}</td>
                    <td>
                        <span class="badge" style="background: #f3f4f6; color: #6b7280;">{{ ucfirst($payment->payment_method) }}</span>
                    </td>
                    <td class="text-end" style="font-weight: 600; font-size: 0.85rem; color: #10b981;">PKR {{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
