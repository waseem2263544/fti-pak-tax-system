@php
$bench = $meta['bench'] ?? 'Peshawar Bench Peshawar';
$clientName = $meta['appellant_name'] ?? $process->client->name ?? '_______________';
$ntn = $meta['ntn_cnic'] ?? '_______________';
$address = $meta['appellant_address'] ?? '_______________';
$respondent1 = $meta['respondent_1'] ?? 'The Commissioner Inland Revenue';
$respondent2 = $meta['respondent_2'] ?? 'The Commissioner Inland Revenue (Appeals)';
$year = date('Y');
$isStTribunalStay = ($process->template ?? '') === 'st-tribunal-stay';
$ntnDigits = preg_replace('/\D/', '', $ntn);
$idType = strlen($ntnDigits) === 13 ? 'CNIC' : 'NTN';
$isIndividual = $idType === 'CNIC';
$verifierName = $meta['verifier_name'] ?? '';
$verifierDesignation = $meta['verifier_designation'] ?? '';
@endphp

@if($isStTribunalStay)
{{-- Reserve top space for stamp-paper printed header --}}
<div style="height: 3.2in;"></div>

@if(!($inCombinedPdf ?? false))
{{-- Stamp-paper reminder watermark (screen-only preview, hidden in print + omitted from combined PDF) --}}
<style>
.stamp-paper-watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    font-size: 64pt;
    color: rgba(220, 38, 38, 0.18);
    font-weight: bold;
    letter-spacing: 4pt;
    pointer-events: none;
    z-index: 9999;
    white-space: nowrap;
    user-select: none;
    text-transform: uppercase;
}
@media print {
    .stamp-paper-watermark { display: none !important; }
}
</style>
<div class="stamp-paper-watermark">Print on Rs. 200 Stamp Paper</div>
@endif
@endif

<h1>BEFORE THE APPELLATE TRIBUNAL,<br>INLAND REVENUE, {{ strtoupper($bench) }}</h1>

@if($isStTribunalStay)
<p><b>Appellant:</b> {{ $clientName }}, {{ $idType }} {{ $ntn }}, {{ $address }}</p>
@else
<p class="center"><b>In RE: CM No; ____________________/{{ $year }}</b></p>
<p class="center"><b>In ITA No: ____________________/{{ $year }}</b></p>

<p><b>Appellant:</b> {{ $clientName }},<br>
NTN/CNIC No. {{ $ntn }}</p>
@endif

<p><b>Respondents:</b></p>
<p class="indent">1. {{ strtoupper($respondent2) }}</p>
<p class="indent">2. {{ strtoupper($respondent1) }}</p>

<h2 style="margin-top: 36pt;">AFFIDAVIT</h2>

@if($isStTribunalStay && !$isIndividual)
{{-- Company / AOP affidavit: verifier swears on behalf of the company --}}
<p>I, <b>{{ $verifierName ?: '_______________' }}</b>, being the <b>{{ $verifierDesignation ?: '_______________' }}</b> of <b>{{ $clientName }}</b>, having NTN <b>{{ $ntn }}</b>, do hereby solemnly affirm that contents of this Application are true and correct to the best of my knowledge and belief, and nothing has been concealed intentionally from this Honorable Tribunal.</p>
@else
{{-- Individual affidavit (default) --}}
<p>I, <b>{{ $clientName }}</b> {{ $idType }}# <b>{{ $ntn }}</b>, do hereby solemnly affirm that contents of this Application are true and correct to the best of my knowledge and belief, and nothing has been concealed intentionally from this Honorable Tribunal.</p>
@endif

@if($isStTribunalStay && !$isIndividual)
{{-- Company / AOP signature: verifier signs on behalf of the company --}}
<div class="signature right" style="margin-top: 36pt;">
    <p>________________________<br>
    <b>Signature</b></p>
    <p style="margin-top: 12pt;"><b>{{ strtoupper($verifierName ?: '_______________') }}</b></p>
    <p style="margin: 0;">{{ $verifierDesignation ?: '_______________' }}</p>
    <p style="margin: 0;"><b>{{ strtoupper($clientName) }}</b></p>
</div>
@else
<div class="signature right" style="margin-top: 36pt;">
    <p>________________________<br>
    <b>Signature</b></p>
    <p style="margin-top: 12pt;"><b>{{ strtoupper($clientName) }}</b></p>
</div>
@endif
