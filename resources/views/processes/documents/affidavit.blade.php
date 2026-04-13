@php
$bench = $meta['bench'] ?? 'Peshawar Bench Peshawar';
$clientName = $meta['appellant_name'] ?? $process->client->name ?? '_______________';
$ntn = $meta['ntn_cnic'] ?? '_______________';
$respondent1 = $meta['respondent_1'] ?? 'The Commissioner Inland Revenue';
$respondent2 = $meta['respondent_2'] ?? 'The Commissioner Inland Revenue (Appeals)';
$year = date('Y');
@endphp

<h1>BEFORE THE APPELLATE TRIBUNAL,<br>INLAND REVENUE, {{ strtoupper($bench) }}</h1>

<p class="center"><b>In RE: CM No; ____________________/{{ $year }}</b></p>
<p class="center"><b>In ITA No: ____________________/{{ $year }}</b></p>

<p><b>Appellant:</b> {{ $clientName }},<br>
NTN/CNIC No. {{ $ntn }}</p>

<p><b>Respondents:</b></p>
<p class="indent">1. {{ strtoupper($respondent2) }}</p>
<p class="indent">2. {{ strtoupper($respondent1) }}</p>

<h2 style="margin-top: 36pt;">AFFIDAVIT</h2>

<p>I, <b>{{ $clientName }}</b> CNIC/NTN# <b>{{ $ntn }}</b>, do hereby solemnly affirm that contents of this Application are true and correct to the best of my knowledge and belief, and nothing has been concealed intentionally from this Honorable Tribunal.</p>

<div class="signature right" style="margin-top: 72pt;">
    <p>________________________<br>
    <b>Signature</b></p>
    <p style="margin-top: 12pt;"><b>{{ strtoupper($clientName) }}</b></p>
</div>
