@php
    $g = fn($k, $d = '') => $settings[$k] ?? $d;
    $isReceipt = $voucher->type === 'receipt';
    $title = $isReceipt ? 'RECEIPT VOUCHER' : 'PAYMENT VOUCHER';
    $partyLabel = $isReceipt ? 'Received From' : 'Paid To';
    $accent = $isReceipt ? '#10b981' : '#7c3aed';
    $money = fn($v) => 'PKR ' . number_format((float) $v, 2);
    $methods = ['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'online' => 'Online'];
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1f2937; }
    .company-name { font-size: 16pt; font-weight: bold; color: #303a50; }
    .muted { color: #6b7280; font-size: 8.5pt; line-height: 1.5; }
    .doc-title { font-size: 18pt; font-weight: bold; letter-spacing: 1px; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 3px 0; font-size: 9.5pt; }
    .meta .label { color: #6b7280; padding-right: 10px; width: 38%; }
    .amountbox { border: 2px solid; border-radius: 8px; padding: 10px 16px; text-align: center; }
    .items th { background: #303a50; color: #fff; padding: 6px 8px; font-size: 8.5pt; text-align: left; }
    .items td { padding: 6px 8px; border-bottom: 1px solid #eef0f3; font-size: 9pt; }
    .items .r, .items td.r, .items th.r { text-align: right; }
    .sign { margin-top: 48px; }
    .sign td { width: 50%; padding-top: 6px; border-top: 1px solid #9ca3af; font-size: 8.5pt; color: #6b7280; text-align: center; }
    .footer { margin-top: 22px; border-top: 1px solid #eef0f3; padding-top: 10px; color: #9ca3af; font-size: 8pt; text-align: center; }
</style>
</head>
<body>

<table>
    <tr>
        <td style="width: 60%; vertical-align: top;">
            <div class="company-name">{{ $g('company_name', 'Company') }}</div>
            <div class="muted">{{ $g('company_address') }}@if($g('company_phone'))<br>{{ $g('company_phone') }}@endif</div>
        </td>
        <td style="width: 40%; text-align: right; vertical-align: top;">
            <div class="doc-title" style="color: {{ $accent }};">{{ $title }}</div>
            <div class="muted" style="font-size: 10pt; margin-top: 4px;"><strong>{{ $voucher->voucher_number }}</strong></div>
        </td>
    </tr>
</table>

<hr style="border: none; border-top: 2px solid {{ $accent }}; margin: 12px 0 16px;">

<table>
    <tr>
        <td style="width: 58%; vertical-align: top;">
            <table class="meta">
                <tr><td class="label">{{ $partyLabel }}</td><td><strong>{{ $voucher->party_name }}</strong></td></tr>
                <tr><td class="label">Date</td><td>{{ optional($voucher->date)->format('d M Y') }}</td></tr>
                <tr><td class="label">{{ $isReceipt ? 'Deposited To' : 'Paid From' }}</td><td>{{ $voucher->paymentAccount->name ?? '—' }}</td></tr>
                <tr><td class="label">Method</td><td>{{ $methods[$voucher->payment_method] ?? ucfirst($voucher->payment_method) }}@if($voucher->cheque_number) · Cheque #{{ $voucher->cheque_number }}@endif</td></tr>
                @if($voucher->reference)<tr><td class="label">Reference</td><td>{{ $voucher->reference }}</td></tr>@endif
            </table>
        </td>
        <td style="width: 42%; vertical-align: top; padding-left: 16px;">
            <div class="amountbox" style="border-color: {{ $accent }};">
                <div class="muted" style="text-transform: uppercase;">Amount</div>
                <div style="font-size: 18pt; font-weight: bold; color: {{ $accent }};">{{ $money($voucher->amount) }}</div>
            </div>
        </td>
    </tr>
</table>

@if($voucher->items && $voucher->items->count())
<table class="items" style="margin-top: 18px;">
    <thead><tr><th style="width: 45%;">Account</th><th>Description</th><th class="r" style="width: 20%;">Amount</th></tr></thead>
    <tbody>
        @foreach($voucher->items as $item)
        <tr>
            <td>{{ $item->account->name ?? '—' }}</td>
            <td>{{ $item->description }}</td>
            <td class="r">{{ $money($item->amount) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($voucher->narration)
<div style="margin-top: 16px;"><strong class="muted" style="text-transform:uppercase;">Narration</strong><div style="font-size: 9pt; margin-top: 3px;">{{ $voucher->narration }}</div></div>
@endif

<table class="sign">
    <tr>
        <td>{{ $isReceipt ? 'Received By' : 'Authorised By' }}</td>
        <td>{{ $isReceipt ? "Depositor's Signature" : "Receiver's Signature" }}</td>
    </tr>
</table>

<div class="footer">{{ $g('company_name', '') }} — generated {{ now()->format('d M Y') }}</div>

</body>
</html>
