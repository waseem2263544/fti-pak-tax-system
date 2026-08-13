@php
    $totalGross = $purchases->sum('gross_amount') + $salaries->sum('total_salary');
    $totalTax   = $purchases->sum('tax_withheld') + $salaries->sum('tax_deducted');
    $certNo = sprintf('%s-TY%d-%06d', $prefix, $taxYear, $party->id);
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 10.5pt; color: #111; }
    .head { text-align: center; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 18px; }
    .head h1 { font-size: 15pt; margin: 0 0 4px; }
    .head .meta { font-size: 9.5pt; color: #444; }
    .title { text-align: center; font-size: 13pt; font-weight: bold; letter-spacing: .04em; margin: 18px 0 4px; }
    .subtitle { text-align: center; font-size: 9.5pt; color: #555; margin-bottom: 18px; }
    .refs { width: 100%; font-size: 9.5pt; margin-bottom: 14px; }
    .party { border: 1px solid #ccc; padding: 10px 12px; margin-bottom: 16px; }
    .party .label { font-size: 8.5pt; text-transform: uppercase; letter-spacing: .05em; color: #666; }
    table.detail { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.detail th, table.detail td { border: 1px solid #999; padding: 5px 7px; font-size: 9.5pt; }
    table.detail th { background: #f2f2f2; text-align: left; }
    table.detail tfoot td { background: #f8f8f8; font-weight: bold; }
    .num { text-align: right; }
    .section-label { font-size: 10.5pt; font-weight: bold; margin: 14px 0 6px; }
    .statement { font-size: 10.5pt; line-height: 1.6; margin: 18px 0 28px; }
    .sign { margin-top: 40px; text-align: right; font-size: 10pt; }
    .sign .line { border-top: 1px solid #111; display: inline-block; width: 200px; padding-top: 4px; }
    .foot { margin-top: 22px; font-size: 8.5pt; color: #666; text-align: center; font-style: italic; }
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

<div class="title">ANNUAL WITHHOLDING TAX CERTIFICATE</div>
<div class="subtitle">
    Tax Year {{ $taxYear }} (1 July {{ $taxYear - 1 }} — 30 June {{ $taxYear }}) · Income Tax Ordinance, 2001
</div>

<table class="refs">
    <tr>
        <td><strong>Certificate No:</strong> {{ $certNo }}</td>
        <td style="text-align: right;"><strong>Date of Issue:</strong> {{ now()->format('d M Y') }}</td>
    </tr>
</table>

<div class="party">
    <div class="label">Issued To</div>
    <div style="font-size: 11.5pt; font-weight: bold; margin: 2px 0;">{{ $party->name }}</div>
    <div style="font-size: 9.5pt;">
        CNIC / NTN: {{ $party->cnic_ntn ?: '—' }} ·
        Status: {{ $party->atl_status === 'non-filer' ? 'Non-filer' : 'Filer' }}<br>
        @if($party->address){{ $party->address }}@endif
    </div>
</div>

@if($purchases->isNotEmpty())
<div class="section-label">Payments for Goods, Services &amp; Contracts</div>
<table class="detail">
    <thead>
        <tr>
            <th>Period</th><th>Payment Date</th><th>Section</th>
            <th class="num">Gross</th><th class="num">Rate</th>
            <th class="num">Tax Withheld</th><th>CPR No.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchases as $p)
        <tr>
            <td>{{ $p->period_month?->format('M Y') }}</td>
            <td>{{ $p->payment_date?->format('d M Y') }}</td>
            <td>{{ $p->section ?: '—' }}</td>
            <td class="num">{{ number_format($p->gross_amount, 2) }}</td>
            <td class="num">{{ rtrim(rtrim(number_format($p->tax_rate, 3), '0'), '.') }}%</td>
            <td class="num">{{ number_format($p->tax_withheld, 2) }}</td>
            <td>{{ $p->cpr_no ?: '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="num">Sub-total</td>
            <td class="num">{{ number_format($purchases->sum('gross_amount'), 2) }}</td>
            <td></td>
            <td class="num">{{ number_format($purchases->sum('tax_withheld'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endif

@if($salaries->isNotEmpty())
<div class="section-label">Salary — Section 149</div>
<table class="detail">
    <thead>
        <tr>
            <th>Month</th><th>Payment Date</th>
            <th class="num">Taxable</th><th class="num">Exempt</th><th class="num">Total Salary</th>
            <th class="num">Tax Deducted</th><th>CPR No.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($salaries as $s)
        <tr>
            <td>{{ $s->salary_month?->format('M Y') }}</td>
            <td>{{ $s->payment_date?->format('d M Y') }}</td>
            <td class="num">{{ number_format($s->taxable_salary, 2) }}</td>
            <td class="num">{{ number_format($s->exempt_amount, 2) }}</td>
            <td class="num">{{ number_format($s->total_salary, 2) }}</td>
            <td class="num">{{ number_format($s->tax_deducted, 2) }}</td>
            <td>{{ $s->cpr_no ?: '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="num">Sub-total</td>
            <td class="num">{{ number_format($salaries->sum('total_salary'), 2) }}</td>
            <td class="num">{{ number_format($salaries->sum('tax_deducted'), 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endif

<table class="detail">
    <tbody>
        <tr>
            <td style="width: 60%;"><strong>Total Amount Paid (Tax Year {{ $taxYear }})</strong></td>
            <td class="num"><strong>PKR {{ number_format($totalGross, 2) }}</strong></td>
        </tr>
        <tr>
            <td><strong>Total Tax Deducted / Collected</strong></td>
            <td class="num"><strong>PKR {{ number_format($totalTax, 2) }}</strong></td>
        </tr>
    </tbody>
</table>

<div class="statement">
    This is to certify that during tax year {{ $taxYear }} a total sum of
    <strong>PKR {{ number_format($totalTax, 2) }}</strong> has been deducted / collected as withholding tax
    from payments made to the above named person under the Income Tax Ordinance, 2001,
    and has been deposited in the Government Treasury.
</div>

<div class="sign">
    <div class="line">Authorised Signature</div>
    <div style="font-size: 9pt; color: #555; margin-top: 4px;">for {{ $company->name }}</div>
</div>

@if($footer)<div class="foot">{{ $footer }}</div>@endif

</body>
</html>
