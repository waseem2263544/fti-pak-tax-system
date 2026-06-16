@php
    $g = fn($k, $d = '') => $settings[$k] ?? $d;
    $money = fn($v) => 'PKR ' . number_format((float) $v, 2);
    $vendor = $purchaseInvoice->contact->name ?? $purchaseInvoice->vendor_name ?? 'Vendor';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; }
    .company-name { font-size: 16pt; font-weight: bold; color: #303a50; }
    .muted { color: #6b7280; font-size: 8.5pt; line-height: 1.5; }
    .doc-title { font-size: 20pt; font-weight: bold; color: #303a50; letter-spacing: 1px; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 2px 0; font-size: 9pt; }
    .meta .label { color: #6b7280; padding-right: 10px; }
    .billbox { background: #f8f9fb; border-radius: 6px; padding: 10px 12px; }
    .items th { background: #303a50; color: #fff; padding: 7px 8px; font-size: 8.5pt; text-align: left; }
    .items th.r, .items td.r { text-align: right; }
    .items td { padding: 7px 8px; border-bottom: 1px solid #eef0f3; font-size: 9pt; }
    .totals td { padding: 4px 8px; font-size: 9.5pt; }
    .totals .label { text-align: right; color: #6b7280; }
    .totals .grand { font-size: 12pt; font-weight: bold; color: #303a50; border-top: 2px solid #303a50; }
    .footer { margin-top: 22px; border-top: 1px solid #eef0f3; padding-top: 10px; color: #6b7280; font-size: 8.5pt; }
</style>
</head>
<body>
<table>
    <tr>
        <td style="width: 60%;">
            <div class="company-name">{{ $g('company_name', 'Company') }}</div>
            <div class="muted">{{ $g('company_address') }}@if($g('company_ntn'))<br>NTN: {{ $g('company_ntn') }}@endif</div>
        </td>
        <td style="width: 40%; text-align: right;"><div class="doc-title">PURCHASE BILL</div></td>
    </tr>
</table>
<hr style="border: none; border-top: 2px solid #303a50; margin: 12px 0 14px;">
<table>
    <tr>
        <td style="width: 55%; vertical-align: top;">
            <div class="muted" style="font-weight: bold; text-transform: uppercase; margin-bottom: 4px;">Vendor</div>
            <div class="billbox"><strong>{{ $vendor }}</strong>
                @if($purchaseInvoice->contact?->phone)<br><span class="muted">{{ $purchaseInvoice->contact->phone }}</span>@endif
                @if($purchaseInvoice->contact?->ntn)<br><span class="muted">NTN: {{ $purchaseInvoice->contact->ntn }}</span>@endif
            </div>
        </td>
        <td style="width: 45%; vertical-align: top;">
            <table class="meta">
                <tr><td class="label">Bill #</td><td><strong>{{ $purchaseInvoice->bill_number }}</strong></td></tr>
                @if($purchaseInvoice->vendor_invoice_no)<tr><td class="label">Vendor Invoice #</td><td>{{ $purchaseInvoice->vendor_invoice_no }}</td></tr>@endif
                <tr><td class="label">Date</td><td>{{ optional($purchaseInvoice->date)->format('d M Y') }}</td></tr>
                <tr><td class="label">Due Date</td><td>{{ optional($purchaseInvoice->due_date)->format('d M Y') }}</td></tr>
            </table>
        </td>
    </tr>
</table>
<table class="items" style="margin-top: 16px;">
    <thead><tr><th style="width: 4%;">#</th><th>Description</th><th class="r" style="width: 10%;">Qty</th><th class="r" style="width: 15%;">Unit Price</th><th class="r" style="width: 12%;">Tax</th><th class="r" style="width: 17%;">Amount</th></tr></thead>
    <tbody>
        @foreach($purchaseInvoice->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->description }}</td>
            <td class="r">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
            <td class="r">{{ number_format($item->unit_price, 2) }}</td>
            <td class="r">{{ $item->tax_rate ? $item->tax_rate . '%' : '—' }}</td>
            <td class="r">{{ number_format($item->amount + $item->tax_amount, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
<table style="margin-top: 12px;">
    <tr>
        <td style="width: 58%;"></td>
        <td style="width: 42%;">
            <table class="totals">
                <tr><td class="label">Subtotal</td><td class="r" style="text-align:right;">{{ $money($purchaseInvoice->subtotal) }}</td></tr>
                @if($purchaseInvoice->tax_amount > 0)<tr><td class="label">Input Tax</td><td class="r" style="text-align:right;">{{ $money($purchaseInvoice->tax_amount) }}</td></tr>@endif
                <tr><td class="label grand">Total</td><td class="r grand" style="text-align:right;">{{ $money($purchaseInvoice->total) }}</td></tr>
                @if($purchaseInvoice->amount_paid > 0)
                <tr><td class="label">Paid</td><td class="r" style="text-align:right;">{{ $money($purchaseInvoice->amount_paid) }}</td></tr>
                <tr><td class="label" style="font-weight:bold;">Balance Due</td><td class="r" style="text-align:right; font-weight:bold;">{{ $money($purchaseInvoice->balance_due) }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>
@if($purchaseInvoice->notes)<div style="margin-top: 16px;"><strong class="muted" style="text-transform:uppercase;">Notes</strong><div style="font-size: 9pt;">{{ $purchaseInvoice->notes }}</div></div>@endif
<div class="footer">{{ $g('company_name', '') }} — generated {{ now()->format('d M Y') }}</div>
</body>
</html>
