@php
    $isSalary = $type === 'salary';
    $gross = $isSalary ? $record->total_salary : $record->gross_amount;
    $tax   = $isSalary ? $record->tax_deducted : $record->tax_withheld;
    $net   = $isSalary ? $record->final_net_payment : $record->net_payment;
    $period = $isSalary ? $record->salary_month : $record->period_month;
    $certNo = sprintf('%s-%d-%06d', $prefix, $period?->year, $record->id);
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 11pt; color: #111; }
    .head { text-align: center; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 18px; }
    .head h1 { font-size: 15pt; margin: 0 0 4px; }
    .head .meta { font-size: 9.5pt; color: #444; }
    .title { text-align: center; font-size: 13pt; font-weight: bold; letter-spacing: .04em; margin: 18px 0 6px; }
    .subtitle { text-align: center; font-size: 9.5pt; color: #555; margin-bottom: 18px; }
    .refs { width: 100%; font-size: 9.5pt; margin-bottom: 16px; }
    .refs td { padding: 2px 0; }
    .party { border: 1px solid #ccc; padding: 10px 12px; margin-bottom: 16px; }
    .party .label { font-size: 8.5pt; text-transform: uppercase; letter-spacing: .05em; color: #666; }
    table.detail { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.detail th, table.detail td { border: 1px solid #999; padding: 6px 8px; font-size: 10pt; }
    table.detail th { background: #f2f2f2; text-align: left; }
    .num { text-align: right; }
    .statement { font-size: 10.5pt; line-height: 1.6; margin-bottom: 28px; }
    .sign { margin-top: 46px; text-align: right; font-size: 10pt; }
    .sign .line { border-top: 1px solid #111; display: inline-block; width: 200px; padding-top: 4px; }
    .foot { margin-top: 26px; font-size: 8.5pt; color: #666; text-align: center; font-style: italic; }
</style>
</head>
<body>

<div class="head">
    <h1>{{ $company->name }}</h1>
    <div class="meta">
        {{ $company->address }}@if($company->address && $company->ntn_cnic) · @endif
        @if($company->ntn_cnic)NTN: {{ $company->ntn_cnic }}@endif
    </div>
</div>

<div class="title">WITHHOLDING TAX CERTIFICATE</div>
<div class="subtitle">Under the Income Tax Ordinance, 2001</div>

<table class="refs">
    <tr>
        <td><strong>Certificate No:</strong> {{ $certNo }}</td>
        <td style="text-align: right;"><strong>Date of Issue:</strong> {{ now()->format('d M Y') }}</td>
    </tr>
    <tr>
        <td><strong>Tax Period:</strong> {{ $period?->format('F Y') }}</td>
        <td style="text-align: right;"><strong>Tax Year:</strong> {{ \App\Services\Wht\WhtCalculator::taxYear($period) }}</td>
    </tr>
</table>

<div class="party">
    <div class="label">Issued To</div>
    <div style="font-size: 11.5pt; font-weight: bold; margin: 2px 0;">{{ $party?->name }}</div>
    <div style="font-size: 9.5pt;">
        CNIC / NTN: {{ $party?->cnic_ntn ?: '—' }}<br>
        @if($party?->address){{ $party->address }}@endif
    </div>
</div>

<table class="detail">
    <thead>
        <tr>
            <th>Payment Date</th>
            <th>Section</th>
            <th>Nature of Payment</th>
            <th class="num">Gross Amount</th>
            <th class="num">Rate</th>
            <th class="num">Tax Withheld</th>
            <th class="num">Net Paid</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $record->payment_date?->format('d M Y') }}</td>
            <td>{{ $record->section ?: ($isSalary ? '149' : '—') }}</td>
            <td>{{ $isSalary ? 'Salary' : 'Payment for goods / services / contract' }}</td>
            <td class="num">{{ number_format($gross, 2) }}</td>
            <td class="num">
                @if($isSalary)
                    As per slab
                @else
                    {{ rtrim(rtrim(number_format($record->tax_rate, 3), '0'), '.') }}%
                @endif
            </td>
            <td class="num"><strong>{{ number_format($tax, 2) }}</strong></td>
            <td class="num">{{ number_format($net, 2) }}</td>
        </tr>
    </tbody>
</table>

@if($record->psid_no || $record->cpr_no)
<table class="detail">
    <thead><tr><th>PSID No.</th><th>CPR No.</th><th>Deposit Date</th></tr></thead>
    <tbody>
        <tr>
            <td>{{ $record->psid_no ?: '—' }}</td>
            <td>{{ $record->cpr_no ?: '—' }}</td>
            <td>{{ $record->cpr_date?->format('d M Y') ?: '—' }}</td>
        </tr>
    </tbody>
</table>
@endif

<div class="statement">
    This is to certify that a sum of <strong>PKR {{ number_format($tax, 2) }}</strong>
    has been deducted / collected as withholding tax
    @if($record->section) under section {{ $record->section }} @endif
    of the Income Tax Ordinance, 2001 from the payment made to the above named person,
    and has been deposited in the Government Treasury.
</div>

<div class="sign">
    <div class="line">Authorised Signature</div>
    <div style="font-size: 9pt; color: #555; margin-top: 4px;">for {{ $company->name }}</div>
</div>

@if($footer)<div class="foot">{{ $footer }}</div>@endif

</body>
</html>
