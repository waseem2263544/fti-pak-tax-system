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
$pronounPossessive = $isIndividual ? 'my' : 'our';
$pronounObject = $isIndividual ? 'me' : 'us';
@endphp

@if($isStTribunalStay)
{{-- Reserve top space for stamp-paper printed header --}}
<div style="height: 3in;"></div>

{{-- Stamp-paper reminder watermark (screen only, hidden when printing) --}}
<style>
.stamp-paper-watermark {
    position: fixed;
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
@media print { .stamp-paper-watermark { display: none !important; } }
</style>
<div class="stamp-paper-watermark">Print on Rs. 200 Stamp Paper</div>
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

<h2 style="margin-top: 36pt;">POWER OF ATTORNEY</h2>

@if($isStTribunalStay && !$isIndividual)
{{-- Company / AOP: verifier executes the Power of Attorney on behalf of the company --}}
<p>I, <b>{{ $verifierName ?: '_______________' }}</b>, being the <b>{{ $verifierDesignation ?: '_______________' }}</b> of <b>{{ $clientName }}</b>, having NTN <b>{{ $ntn }}</b>, duly authorized to execute this Power of Attorney, do hereby nominate, constitute, and appoint <b>Mr. Waseem Ur Rehman</b>, Director of <b>Fair Tax (Pvt) Ltd</b>, having office at TF-121, 3rd Floor, Deans Trade Center, Islamia Road, Peshawar Cantt., as our Authorized Representative to act for and on behalf of the company in the above-titled matter before the Honorable Appellate Tribunal Inland Revenue, {{ $bench }}.</p>
@else
{{-- Individual --}}
<p>I, <b>{{ $clientName }}</b>, {{ $idType }}# <b>{{ $ntn }}</b>, resident of <b>{{ $address }}</b>, do hereby nominate, constitute, and appoint <b>Mr. Waseem Ur Rehman</b>, Director of <b>Fair Tax (Pvt) Ltd</b>, having office at TF-121, 3rd Floor, Deans Trade Center, Islamia Road, Peshawar Cantt., as my Authorized Representative to act for and on my behalf in the above-titled matter before the Honorable Appellate Tribunal Inland Revenue, {{ $bench }}.</p>
@endif

<p style="margin-top: 12pt;">The said Authorized Representative is hereby fully empowered to:</p>

<ol style="margin-left: 24pt; line-height: 1.7;">
    <li>Appear, plead, and represent in the captioned proceedings before the Honorable Tribunal;</li>
    <li>File applications, replies, rejoinders, written submissions, statements, and other pleadings;</li>
    <li>Receive notices, orders, and any communication issued in connection with the case;</li>
    <li>Inspect records, sign documents, and obtain certified copies of orders and proceedings;</li>
    <li>Compromise, withdraw, or refer the matter to any competent forum where lawful;</li>
    <li>Engage further counsel or substitute representation as may be required.</li>
</ol>

<p style="margin-top: 12pt;">All acts and deeds lawfully performed by the said Authorized Representative in pursuance of this Power of Attorney shall be deemed to have been done with {{ $pronounPossessive }} full knowledge and consent and shall be binding upon {{ $pronounObject }}.</p>

@if($isStTribunalStay && !$isIndividual)
{{-- Company signature block --}}
<div class="signature right" style="margin-top: 72pt;">
    <p>________________________<br>
    <b>Signature of Executant</b></p>
    <p style="margin-top: 12pt;"><b>{{ strtoupper($verifierName ?: '_______________') }}</b></p>
    <p style="margin: 0;">{{ $verifierDesignation ?: '_______________' }}</p>
    <p style="margin: 0;"><b>{{ strtoupper($clientName) }}</b></p>
</div>
@else
<div class="signature right" style="margin-top: 72pt;">
    <p>________________________<br>
    <b>Signature of Executant</b></p>
    <p style="margin-top: 12pt;"><b>{{ strtoupper($clientName) }}</b></p>
</div>
@endif

<div style="margin-top: 36pt;">
    <p style="margin: 0;"><b>Accepted by:</b></p>
    <p style="margin-top: 24pt;">________________________<br>
    <b>Mr. Waseem Ur Rehman</b><br>
    Director - Fair Tax (Pvt) Ltd</p>
</div>
