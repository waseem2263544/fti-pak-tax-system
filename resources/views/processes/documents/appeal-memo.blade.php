@php
$bench = $meta['bench'] ?? 'Peshawar Bench Peshawar';
$clientName = $meta['appellant_name'] ?? $process->client->name ?? '_______________';
$ntn = $meta['ntn_cnic'] ?? '_______________';
$ntnDigits = preg_replace('/\D/', '', $ntn);
$idType = strlen($ntnDigits) === 13 ? 'CNIC' : 'NTN';
$address = $meta['appellant_address'] ?? '_______________';
$taxYear = trim($meta['tax_year'] ?? '');
$section = $meta['section'] ?? '122(1)/129';
$assessmentOrderNo = $meta['assessment_order_no'] ?? '_______________';
$assessmentOrderDate = $meta['assessment_order_date'] ?? '_______________';
$ciraOrderNo = $meta['cira_order_no'] ?? '_______________';
$ciraOrderDate = $meta['cira_order_date'] ?? '_______________';
$respondent1 = $meta['respondent_1'] ?? 'The Commissioner Inland Revenue';
$respondent2 = $meta['respondent_2'] ?? 'The Commissioner Inland Revenue (Appeals)';
$demandAmount = $meta['demand_amount'] ?? null;
$amountPaid = $meta['amount_paid'] ?? null;
$balanceDemand = $meta['balance_demand'] ?? null;
$year = date('Y');
@endphp

<h1 style="font-size: 18pt; line-height: 1.3;">BEFORE THE APPELLATE TRIBUNAL INLAND<br>REVENUE {{ strtoupper($bench) }}</h1>

<p class="center"><b>In ITA No. ____________________/{{ $year }}</b></p>

<p><b>SUBJECT:</b> <b><u>APPEAL UNDER SECTION 131 OF THE INCOME TAX ORDINANCE, 2001 IN THE CASE OF {{ strtoupper($clientName) }} {{ $idType }} NO. {{ $ntn }}@if($taxYear) FOR THE TAX YEAR {{ $taxYear }}@endif</u></b></p>

<p><b>APPELLANT:</b> {{ strtoupper($clientName) }},<br>
{{ $idType }} No. {{ $ntn }},<br>
{{ $address }}</p>

<p><b>RESPONDENTS:</b></p>
<p class="indent">1. {{ strtoupper($respondent2) }}</p>
<p class="indent">2. {{ strtoupper($respondent1) }}</p>

<h2 style="margin-top: 24pt;">MEMORANDUM OF APPEAL</h2>

<p><b>Respected Sir,</b></p>

<p>The Appellant most humbly and respectfully begs to submit as under:</p>

<table style="margin-top: 12pt;">
    <tr>
        <td style="width: 8%;" class="center">1.</td>
        <td style="width: 50%;">Section under which the order appealed against has been passed</td>
        <td>{{ $section }}</td>
    </tr>
    <tr>
        <td class="center">2.</td>
        <td>Date of order appealed against</td>
        <td>{{ $ciraOrderDate }}</td>
    </tr>
    <tr>
        <td class="center">3.</td>
        <td>Authority who passed the order</td>
        <td>{{ $respondent2 }}</td>
    </tr>
    <tr>
        <td class="center">4.</td>
        <td>Order No. of CIR(A)</td>
        <td>{{ $ciraOrderNo }}</td>
    </tr>
    <tr>
        <td class="center">5.</td>
        <td>Order No. of Assessing Officer (u/s 122)</td>
        <td>{{ $assessmentOrderNo }} dated {{ $assessmentOrderDate }}</td>
    </tr>
    <tr>
        <td class="center">6.</td>
        <td>Tax Year</td>
        <td>{{ $taxYear ?: '________' }}</td>
    </tr>
    <tr>
        <td class="center">7.</td>
        <td>Demand created by the Assessing Officer (PKR)</td>
        <td>{{ $demandAmount !== null && $demandAmount !== '' ? number_format((float)$demandAmount, 2) : '_______________' }}</td>
    </tr>
    <tr>
        <td class="center">8.</td>
        <td>Amount paid against demand (PKR)</td>
        <td>{{ $amountPaid !== null && $amountPaid !== '' ? number_format((float)$amountPaid, 2) : '_______________' }}</td>
    </tr>
    <tr>
        <td class="center">9.</td>
        <td>Balance demand outstanding (PKR)</td>
        <td>{{ $balanceDemand !== null && $balanceDemand !== '' ? number_format((float)$balanceDemand, 2) : '_______________' }}</td>
    </tr>
    <tr>
        <td class="center">10.</td>
        <td>Address for service of notices on the Appellant</td>
        <td>M/s FairTax International, TF-121, Deans Trade Centre, Peshawar Cantt, Peshawar.</td>
    </tr>
</table>

<p style="margin-top: 18pt;">The Appellant being aggrieved by the order passed by the {{ $respondent2 }} prefers this appeal before this Honorable Tribunal on the grounds set out in the accompanying Grounds of Appeal, which may kindly be read as an integral part of this memorandum.</p>

<p>It is therefore, most humbly and respectfully prayed that this Honorable Tribunal may graciously be pleased to accept this appeal, set aside the impugned order, and grant such other relief as it may deem just and proper in the circumstances of the case.</p>

<div class="signature right" style="margin-top: 36pt;">
    <p><b>Appellant</b></p>
    <p><b>{{ strtoupper($clientName) }}</b></p>
    <p style="margin-top: 12pt;">Through</p>
    <p><b>Waseem Ur Rehman</b><br>
    (Partner - FairTax International)<br>
    Authorized Representative</p>
</div>
