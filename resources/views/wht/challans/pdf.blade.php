@php
    $purchaseTax = $purchases->sum('tax_withheld');
    $salaryTax   = $salaries->sum('tax_deducted');
    $totalTax    = $purchaseTax + $salaryTax;
    $totalGross  = $purchases->sum('gross_amount') + $salaries->sum('total_salary');
    $entries     = $purchases->count() + $salaries->count();
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 9.5pt; color: #111; }
    .head { text-align: center; border-bottom: 2px solid #111; padding-bottom: 7px; margin-bottom: 14px; }
    .head h1 { font-size: 14pt; margin: 0 0 3px; }
    .head .meta { font-size: 9pt; color: #444; }
    .title { text-align: center; font-size: 12pt; font-weight: bold; letter-spacing: .04em; margin: 14px 0 4px; }
    .subtitle { text-align: center; font-size: 9pt; color: #555; margin-bottom: 14px; }
    .refs { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 9.5pt; }
    .refs td { border: 1px solid #bbb; padding: 5px 8px; }
    .refs .label { background: #f2f2f2; font-weight: bold; width: 15%; }
    table.detail { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.detail th, table.detail td { border: 1px solid #999; padding: 4px 6px; font-size: 8.5pt; }
    table.detail th { background: #eef2f6; text-align: left; }
    table.detail tfoot td { background: #f7f7f7; font-weight: bold; }
    .num { text-align: right; }
    .section-label { font-size: 10pt; font-weight: bold; margin: 12px 0 5px; padding-bottom: 2px; border-bottom: 1px solid #ccc; }
    .grand { width: 45%; margin-left: auto; border-collapse: collapse; margin-top: 6px; }
    .grand td { border: 1px solid #999; padding: 6px 8px; font-size: 10pt; }
    .grand .label { background: #eef2f6; font-weight: bold; }
    .foot { margin-top: 20px; font-size: 8pt; color: #666; text-align: center; font-style: italic; }
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

<div class="title">SCHEDULE OF WITHHOLDING TAX DEPOSITED</div>
<div class="subtitle">Entries covered by {{ $reference }} {{ $reference === 'CPR' ? $cpr : $psid }}</div>

<table class="refs">
    <tr>
        <td class="label">PSID No.</td>
        <td>{{ $psid ?: '—' }}</td>
        <td class="label">CPR No.</td>
        <td>{{ $cpr ?: '—' }}</td>
        <td class="label">Deposited</td>
        <td>{{ $paidOn?->format('d M Y') ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Entries</td>
        <td>{{ $entries }}</td>
        <td class="label">Total Tax</td>
        <td><strong>PKR {{ number_format($totalTax, 2) }}</strong></td>
        <td class="label">Printed</td>
        <td>{{ now()->format('d M Y') }}</td>
    </tr>
</table>

@if($purchases->isNotEmpty())
<div class="section-label">Vendor, Supplier &amp; Contractor Payments — {{ $purchases->count() }} entries</div>
<table class="detail">
    <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th>Payee</th>
            <th>CNIC / NTN</th>
            <th>ATL</th>
            <th>Section</th>
            <th>Period</th>
            <th>Payment Date</th>
            <th class="num">Gross Amount</th>
            <th class="num">Rate</th>
            <th class="num">Tax Withheld</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchases as $i => $p)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $p->party?->name ?? '—' }}</td>
            <td>{{ $p->party?->cnic_ntn ?: '—' }}</td>
            <td>{{ $p->party?->atl_status === 'non-filer' ? 'Non-filer' : 'Filer' }}</td>
            <td>{{ $p->section ?: '—' }}</td>
            <td>{{ $p->period_month?->format('M Y') }}</td>
            <td>{{ $p->payment_date?->format('d M Y') }}</td>
            <td class="num">{{ number_format($p->gross_amount, 2) }}</td>
            <td class="num">{{ rtrim(rtrim(number_format($p->tax_rate, 3), '0'), '.') }}%</td>
            <td class="num">{{ number_format($p->tax_withheld, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7" class="num">Sub-total</td>
            <td class="num">{{ number_format($purchases->sum('gross_amount'), 2) }}</td>
            <td></td>
            <td class="num">{{ number_format($purchaseTax, 2) }}</td>
        </tr>
    </tfoot>
</table>
@endif

@if($salaries->isNotEmpty())
<div class="section-label">Salaries — Section 149 — {{ $salaries->count() }} entries</div>
<table class="detail">
    <thead>
        <tr>
            <th style="width: 4%;">#</th>
            <th>Employee</th>
            <th>CNIC / NTN</th>
            <th>Section</th>
            <th>Salary Month</th>
            <th>Payment Date</th>
            <th class="num">Taxable</th>
            <th class="num">Exempt</th>
            <th class="num">Total Salary</th>
            <th class="num">Tax Deducted</th>
        </tr>
    </thead>
    <tbody>
        @foreach($salaries as $i => $s)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $s->employee?->name ?? '—' }}</td>
            <td>{{ $s->employee?->cnic_ntn ?: '—' }}</td>
            <td>{{ $s->section ?: '149' }}</td>
            <td>{{ $s->salary_month?->format('M Y') }}</td>
            <td>{{ $s->payment_date?->format('d M Y') }}</td>
            <td class="num">{{ number_format($s->taxable_salary, 2) }}</td>
            <td class="num">{{ number_format($s->exempt_amount, 2) }}</td>
            <td class="num">{{ number_format($s->total_salary, 2) }}</td>
            <td class="num">{{ number_format($s->tax_deducted, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="8" class="num">Sub-total</td>
            <td class="num">{{ number_format($salaries->sum('total_salary'), 2) }}</td>
            <td class="num">{{ number_format($salaryTax, 2) }}</td>
        </tr>
    </tfoot>
</table>
@endif

<table class="grand">
    <tr>
        <td class="label">Total Amount Paid</td>
        <td class="num">PKR {{ number_format($totalGross, 2) }}</td>
    </tr>
    <tr>
        <td class="label">Total Tax Deposited</td>
        <td class="num"><strong>PKR {{ number_format($totalTax, 2) }}</strong></td>
    </tr>
</table>

<div class="foot">
    Computer generated schedule · {{ $company->name }} · {{ $reference }} {{ $reference === 'CPR' ? $cpr : $psid }}
</div>

</body>
</html>
