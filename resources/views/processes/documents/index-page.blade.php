@php
$bench = $meta['bench'] ?? 'Peshawar Bench Peshawar';
$clientName = $meta['appellant_name'] ?? $process->client->name ?? '_______________';
$ntn = $meta['ntn_cnic'] ?? '_______________';
$ntnDigits = preg_replace('/\D/', '', $ntn);
$idType = strlen($ntnDigits) === 13 ? 'CNIC' : 'NTN';
$taxYear = trim($meta['tax_year'] ?? '');
$respondent1 = $meta['respondent_1'] ?? 'The Commissioner Inland Revenue';
$respondent2 = $meta['respondent_2'] ?? 'The Commissioner Inland Revenue (Appeals)';
$year = date('Y');
@endphp

<table style="border: none; margin-left: auto; margin-right: 0; margin-bottom: 14pt; width: auto;">
    <tr>
        <td style="border: 1px solid #000; padding: 3pt 6pt; text-align: center; font-weight: bold; font-size: 8pt;">MEMBER COPY</td>
        <td style="border: 1px solid #000; padding: 3pt 6pt; text-align: center; font-weight: bold; font-size: 8pt;">ACCOUNTANT COPY</td>
        <td style="border: 1px solid #000; padding: 3pt 6pt; text-align: center; font-weight: bold; font-size: 8pt;">TRIBUNAL COPY</td>
        <td style="border: 1px solid #000; padding: 3pt 6pt; text-align: center; font-weight: bold; font-size: 8pt;">OFFICE COPY</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000; padding: 3pt 6pt; text-align: center; font-size: 8pt;">&nbsp;</td>
        <td style="border: 1px solid #000; padding: 3pt 6pt; text-align: center; font-size: 8pt;">&nbsp;</td>
        <td style="border: 1px solid #000; padding: 3pt 6pt; text-align: center; font-size: 8pt;">&nbsp;</td>
        <td style="border: 1px solid #000; padding: 3pt 6pt; text-align: center; font-size: 8pt;">&nbsp;</td>
    </tr>
</table>

<h1>BEFORE THE APPELLATE TRIBUNAL INLAND<br>REVENUE {{ strtoupper($bench) }}</h1>

<p><b>In RE: CM No.____________________/{{ $year }}</b></p>

<p><b>Appellant:</b> STAY APPLICATION IN THE CASE OF {{ strtoupper($clientName) }} {{ $idType }} NO. {{ $ntn }}@if($taxYear) FOR THE TAX YEAR {{ $taxYear }}@endif</p>

<p><b>Respondents:</b></p>
<p class="indent">1. {{ strtoupper($respondent2) }}</p>
<p class="indent">2. {{ strtoupper($respondent1) }}</p>

<h2 style="margin-top: 24pt;">INDEX</h2>

<table>
    <thead>
        <tr>
            <th style="width: 10%;">S. NO</th>
            <th style="width: 65%;">DESCRIPTION</th>
            <th style="width: 25%;">PAGE NO.</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="center">1</td><td>FORM "B"</td><td></td></tr>
        <tr><td class="center">2</td><td>INDEX OF APPEAL</td><td></td></tr>
        <tr><td class="center">3</td><td>STAY APPLICATION</td><td></td></tr>
        <tr><td class="center">4</td><td>GROUNDS OF APPEAL</td><td></td></tr>
        <tr><td class="center">5</td><td>DEMAND NOTICE</td><td></td></tr>
        <tr><td class="center">6</td><td>ORDER PASSED BY THE COMMISSIONER IR (APPEAL)</td><td></td></tr>
        <tr><td class="center">7</td><td>ORDER PASSED BY THE COMMISSIONER (IR)</td><td></td></tr>
        <tr><td class="center">8</td><td>AFFIDAVIT</td><td></td></tr>
        <tr><td class="center">9</td><td>INTIMATION LETTER</td><td></td></tr>
        <tr><td class="center">10</td><td>POWER OF ATTORNEY</td><td></td></tr>
        <tr><td class="center">11</td><td>STAY ORDER</td><td></td></tr>
    </tbody>
</table>

<div class="signature right" style="margin-top: 36pt;">
    <p><b>WASEEM UR REHMAN</b><br>
    PARTNER<br>
    M/S FAIRTAX INTERNATIONAL<br>
    AUTHORIZED REPRESENTATIVE</p>
</div>
