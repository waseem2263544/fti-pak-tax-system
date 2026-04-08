@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page-title', 'Sales Invoices')

@section('styles')
<style>
    @media print {
        .sidebar, .top-nav, .no-print { display: none !important; }
        .main-wrapper { margin-left: 0 !important; }
        .main-content { padding: 0 !important; }
        body { background: #fff !important; }
        .card { box-shadow: none !important; border: none !important; }
        .invoice-card { break-inside: avoid; }
    }
</style>
@endsection

@section('content')
<!-- Action Bar -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="{{ route('accounting.sales-invoices.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Sales Invoices
    </a>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer me-1"></i> Print</button>
        @if($invoice->status === 'draft')
            <form action="{{ route('accounting.sales-invoices.send', $invoice) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i> Mark Sent</button>
            </form>
            <a href="{{ route('accounting.sales-invoices.edit', $invoice) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>
        @endif
        @if(in_array($invoice->status, ['sent', 'partial', 'overdue']))
            <a href="{{ route('accounting.receipt-vouchers.create') }}?invoice_id={{ $invoice->id }}" class="btn btn-accent btn-sm"><i class="bi bi-cash-coin me-1"></i> Record Receipt</a>
        @endif
    </div>
</div>

<!-- Invoice Document -->
<div class="card invoice-card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-body" style="padding: 48px;">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start mb-5">
            <div>
                <h3 style="font-weight: 800; color: var(--primary); margin: 0;">INVOICE</h3>
                <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary); margin-top: 4px;">{{ $invoice->invoice_number }}</div>
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
                <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Bill To</div>
                <div style="font-weight: 700; font-size: 1rem; color: var(--primary);">{{ $invoice->client->name ?? '-' }}</div>
                @if($invoice->client->email ?? null)
                    <div style="font-size: 0.85rem; color: #6b7280;">{{ $invoice->client->email }}</div>
                @endif
                @if($invoice->client->contact_no ?? null)
                    <div style="font-size: 0.85rem; color: #6b7280;">{{ $invoice->client->contact_no }}</div>
                @endif
            </div>
            <div class="col-md-6 text-end">
                <div class="row g-2">
                    <div class="col-6 text-start">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Invoice Date</div>
                        <div style="font-size: 0.9rem; font-weight: 600; color: var(--primary);">{{ $invoice->invoice_date->format('F d, Y') }}</div>
                    </div>
                    <div class="col-6 text-start">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Due Date</div>
                        <div style="font-size: 0.9rem; font-weight: 600; {{ $invoice->due_date < now() && $invoice->status !== 'paid' ? 'color: #ef4444;' : 'color: var(--primary);' }}">{{ $invoice->due_date->format('F d, Y') }}</div>
                    </div>
                    <div class="col-6 text-start mt-2">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Status</div>
                        @if($invoice->status === 'paid')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Paid</span>
                        @elseif($invoice->status === 'partial')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Partially Paid</span>
                        @elseif($invoice->status === 'sent')
                            <span class="badge" style="background: var(--accent-glow); color: #5c6300;">Sent</span>
                        @elseif($invoice->status === 'draft')
                            <span class="badge" style="background: #f3f4f6; color: #6b7280;">Draft</span>
                        @elseif($invoice->status === 'overdue')
                            <span class="badge" style="background: #fef2f2; color: #dc2626;">Overdue</span>
                        @endif
                    </div>
                    @if($invoice->reference)
                    <div class="col-6 text-start mt-2">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 4px;">Reference</div>
                        <div style="font-size: 0.85rem; color: var(--primary);">{{ $invoice->reference }}</div>
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
                    @foreach($invoice->items ?? [] as $i => $item)
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
                    <span style="font-weight: 600;">PKR {{ number_format($invoice->subtotal ?? $invoice->items->sum(function($i) { return $i->quantity * $i->unit_price; }), 2) }}</span>
                </div>
                @if(($invoice->tax_amount ?? 0) > 0)
                <div class="d-flex justify-content-between py-2" style="border-bottom: 1px solid #f0f2f5;">
                    <span style="font-size: 0.85rem; color: #6b7280;">Tax</span>
                    <span style="font-weight: 600;">PKR {{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                @endif
                @if(($invoice->discount ?? 0) > 0)
                <div class="d-flex justify-content-between py-2" style="border-bottom: 1px solid #f0f2f5;">
                    <span style="font-size: 0.85rem; color: #6b7280;">Discount</span>
                    <span style="font-weight: 600; color: #ef4444;">- PKR {{ number_format($invoice->discount, 2) }}</span>
                </div>
                @endif
                <div class="d-flex justify-content-between py-3" style="border-top: 2px solid var(--primary);">
                    <span style="font-weight: 700; font-size: 1rem; color: var(--primary);">Total</span>
                    <span style="font-weight: 800; font-size: 1.15rem; color: var(--primary);">PKR {{ number_format($invoice->total, 2) }}</span>
                </div>
                @if($invoice->paid_amount > 0)
                <div class="d-flex justify-content-between py-2" style="border-bottom: 1px solid #f0f2f5;">
                    <span style="font-size: 0.85rem; color: #10b981;">Paid</span>
                    <span style="font-weight: 600; color: #10b981;">- PKR {{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between py-3" style="background: rgba(245,158,11,0.06); padding: 12px; border-radius: 8px;">
                    <span style="font-weight: 700; font-size: 1rem; color: #92400e;">Balance Due</span>
                    <span style="font-weight: 800; font-size: 1.15rem; color: #92400e;">PKR {{ number_format($invoice->total - $invoice->paid_amount, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="mt-5 pt-4" style="border-top: 1px solid #f0f2f5;">
            <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Notes</div>
            <p style="font-size: 0.85rem; color: #6b7280; margin: 0; white-space: pre-line;">{{ $invoice->notes }}</p>
        </div>
        @endif

        @if($invoice->terms)
        <div class="mt-3">
            <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Terms &amp; Conditions</div>
            <p style="font-size: 0.85rem; color: #6b7280; margin: 0; white-space: pre-line;">{{ $invoice->terms }}</p>
        </div>
        @endif
    </div>
</div>

<!-- Payment History -->
@if(($invoice->payments ?? collect())->count() > 0)
<div class="card mt-4 no-print" style="max-width: 900px; margin: 20px auto 0;">
    <div class="card-header d-flex align-items-center gap-2">
        <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
        Payment History
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $payment)
                <tr>
                    <td>
                        <a href="{{ route('accounting.receipt-vouchers.show', $payment) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.85rem;">
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
