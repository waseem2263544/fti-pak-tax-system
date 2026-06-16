@php
    $g = fn($k, $d = '') => $settings[$k] ?? $d;
    $company = $g('company_name', 'Company');
    $statusColors = [
        'draft' => ['#6b7280', '#f3f4f6'], 'sent' => ['#1e40af', '#dbeafe'],
        'partial' => ['#92400e', '#fef3c7'], 'paid' => ['#065f46', '#d1fae5'],
        'overdue' => ['#dc2626', '#fef2f2'], 'cancelled' => ['#6b7280', '#f3f4f6'],
    ];
    $sc = $statusColors[$salesInvoice->status] ?? ['#6b7280', '#f3f4f6'];
    $money = fn($v) => 'PKR ' . number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; }
    .accent { color: #303a50; }
    table { width: 100%; border-collapse: collapse; }
    .head-table td { vertical-align: top; }
    .company-name { font-size: 17pt; font-weight: bold; color: #303a50; }
    .muted { color: #6b7280; font-size: 8.5pt; line-height: 1.5; }
    .doc-title { font-size: 22pt; font-weight: bold; color: #303a50; letter-spacing: 1px; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
    .meta td { padding: 2px 0; font-size: 9pt; }
    .meta .label { color: #6b7280; padding-right: 10px; }
    .billto { background: #f8f9fb; border-radius: 6px; padding: 10px 12px; }
    .items th { background: #303a50; color: #fff; padding: 7px 8px; font-size: 8.5pt; text-align: left; }
    .items th.r, .items td.r { text-align: right; }
    .items td { padding: 7px 8px; border-bottom: 1px solid #eef0f3; font-size: 9pt; }
    .totals td { padding: 4px 8px; font-size: 9.5pt; }
    .totals .label { text-align: right; color: #6b7280; }
    .totals .grand { font-size: 12pt; font-weight: bold; color: #303a50; border-top: 2px solid #303a50; }
    .footer { margin-top: 22px; border-top: 1px solid #eef0f3; padding-top: 10px; color: #6b7280; font-size: 8.5pt; }
    .stamp { float: right; border: 2px solid; padding: 4px 12px; border-radius: 6px; font-weight: bold; font-size: 11pt; transform: rotate(-6deg); }
</style>
</head>
<body>

<table class="head-table">
    <tr>
        <td style="width: 60%;">
            <div class="company-name">{{ $company }}</div>
            <div class="muted">
                {{ $g('company_address') }}<br>
                @if($g('company_ntn'))NTN: {{ $g('company_ntn') }} @endif @if($g('company_strn')) &nbsp; STRN: {{ $g('company_strn') }}@endif
                @if($g('company_phone'))<br>Phone: {{ $g('company_phone') }}@endif
                @if($g('company_email')) &nbsp; {{ $g('company_email') }}@endif
            </div>
        </td>
        <td style="width: 40%; text-align: right;">
            <div class="doc-title">INVOICE</div>
            <div style="margin-top: 6px;"><span class="badge" style="color: {{ $sc[0] }}; background: {{ $sc[1] }};">{{ ucfirst($salesInvoice->status) }}</span></div>
        </td>
    </tr>
</table>

<hr style="border: none; border-top: 2px solid #303a50; margin: 12px 0 14px;">

<table>
    <tr>
        <td style="width: 55%; vertical-align: top;">
            <div class="muted" style="font-weight: bold; text-transform: uppercase; margin-bottom: 4px;">Bill To</div>
            <div class="billto">
                <strong>{{ $salesInvoice->client->name ?? 'Client' }}</strong><br>
                <span class="muted">
                    @if($salesInvoice->client?->contact_no){{ $salesInvoice->client->contact_no }}<br>@endif
                    @if($salesInvoice->client?->email){{ $salesInvoice->client->email }}@endif
                </span>
            </div>
        </td>
        <td style="width: 45%; vertical-align: top;">
            <table class="meta">
                <tr><td class="label">Invoice #</td><td><strong>{{ $salesInvoice->invoice_number }}</strong></td></tr>
                <tr><td class="label">Date</td><td>{{ optional($salesInvoice->date)->format('d M Y') }}</td></tr>
                <tr><td class="label">Due Date</td><td>{{ optional($salesInvoice->due_date)->format('d M Y') }}</td></tr>
                @if($salesInvoice->reference)<tr><td class="label">Reference</td><td>{{ $salesInvoice->reference }}</td></tr>@endif
            </table>
        </td>
    </tr>
</table>

<table class="items" style="margin-top: 16px;">
    <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th style="width: 42%;">Description</th>
            <th class="r" style="width: 10%;">Qty</th>
            <th class="r" style="width: 15%;">Unit Price</th>
            <th class="r" style="width: 12%;">Tax</th>
            <th class="r" style="width: 17%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($salesInvoice->items as $i => $item)
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
        <td style="width: 55%; vertical-align: top;">
            @if($salesInvoice->balance_due <= 0 && $salesInvoice->total > 0)
                <span class="stamp" style="color: #10b981;">PAID</span>
            @endif
        </td>
        <td style="width: 45%;">
            <table class="totals">
                <tr><td class="label">Subtotal</td><td class="r" style="text-align:right;">{{ $money($salesInvoice->subtotal) }}</td></tr>
                @if($salesInvoice->tax_amount > 0)<tr><td class="label">Sales Tax</td><td class="r" style="text-align:right;">{{ $money($salesInvoice->tax_amount) }}</td></tr>@endif
                @if($salesInvoice->discount_amount > 0)<tr><td class="label">Discount</td><td class="r" style="text-align:right;">− {{ $money($salesInvoice->discount_amount) }}</td></tr>@endif
                <tr><td class="label grand">Total</td><td class="r grand" style="text-align:right;">{{ $money($salesInvoice->total) }}</td></tr>
                @if($salesInvoice->amount_paid > 0)
                <tr><td class="label">Paid</td><td class="r" style="text-align:right;">{{ $money($salesInvoice->amount_paid) }}</td></tr>
                <tr><td class="label" style="font-weight:bold;">Balance Due</td><td class="r" style="text-align:right; font-weight:bold;">{{ $money($salesInvoice->balance_due) }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>

@if($salesInvoice->notes)
<div style="margin-top: 16px;"><strong class="muted" style="text-transform:uppercase;">Notes</strong><div style="font-size: 9pt; margin-top: 3px;">{{ $salesInvoice->notes }}</div></div>
@endif

<div class="footer">
    @if($salesInvoice->terms){{ $salesInvoice->terms }}<br>@endif
    {{ $g('invoice_footer', 'Thank you for your business!') }}
</div>

</body>
</html>
