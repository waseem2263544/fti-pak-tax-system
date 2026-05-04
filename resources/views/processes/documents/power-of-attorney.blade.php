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
@media print { .stamp-paper-watermark { display: none !important; } }
</style>
<div class="stamp-paper-watermark">Print on Rs. 200 Stamp Paper</div>
@endif
@endif

@if($isStTribunalStay)
<h1 style="font-size: 13pt; margin: 0 0 10pt;">BEFORE THE APPELLATE TRIBUNAL,<br>INLAND REVENUE, {{ strtoupper($bench) }}</h1>

<p style="margin: 8pt 0;"><b>Appellant:</b> {{ $clientName }}, {{ $idType }} {{ $ntn }}, {{ $address }}</p>

<p style="margin: 6pt 0 2pt;"><b>Respondents:</b></p>
<p class="indent" style="margin: 0;">1. {{ strtoupper($respondent2) }}</p>
<p class="indent" style="margin: 0 0 8pt;">2. {{ strtoupper($respondent1) }}</p>

<h2 style="font-size: 13pt; margin: 12pt 0 8pt;">POWER OF ATTORNEY</h2>

@if(!$isIndividual)
<p style="margin: 0 0 8pt; line-height: 1.5;">I, <b>{{ $verifierName ?: '_______________' }}</b>, being the <b>{{ $verifierDesignation ?: '_______________' }}</b> of <b>{{ $clientName }}</b> (NTN <b>{{ $ntn }}</b>), do hereby appoint <b>Mr. Waseem Ur Rehman</b>, Director of <b>Fair Tax (Pvt) Ltd</b> (TF-121, Deans Trade Center, Peshawar), as our Authorized Representative in the above-titled matter before the Honorable Appellate Tribunal Inland Revenue, {{ $bench }}, with full authority to appear, plead, file and receive pleadings, inspect records, sign documents, obtain certified copies, and do all acts necessary in connection with this case. All acts so performed shall be binding upon us.</p>
@else
<p style="margin: 0 0 8pt; line-height: 1.5;">I, <b>{{ $clientName }}</b>, CNIC# <b>{{ $ntn }}</b>, do hereby appoint <b>Mr. Waseem Ur Rehman</b>, Director of <b>Fair Tax (Pvt) Ltd</b> (TF-121, Deans Trade Center, Peshawar), as my Authorized Representative in the above-titled matter before the Honorable Appellate Tribunal Inland Revenue, {{ $bench }}, with full authority to appear, plead, file and receive pleadings, inspect records, sign documents, obtain certified copies, and do all acts necessary in connection with this case. All acts so performed shall be binding upon me.</p>
@endif

{{-- Side-by-side signature block: Authorized Representative (left) | Executant (right) --}}
<table class="no-border" style="width: 100%; border: none; margin-top: 30pt; border-collapse: collapse;">
    <tr>
        <td style="border: none; padding: 0 12pt 0 0; vertical-align: top; width: 50%; text-align: center;">
            <p style="margin: 0;">________________________</p>
            <p style="margin: 4pt 0 0; font-size: 11pt;"><b>Mr. Waseem Ur Rehman</b></p>
            <p style="margin: 0; font-size: 11pt;">Director - Fair Tax (Pvt) Ltd</p>
            <p style="margin: 4pt 0 0; font-size: 10pt;"><b>(Authorized Representative)</b></p>
        </td>
        <td style="border: none; padding: 0 0 0 12pt; vertical-align: top; width: 50%; text-align: center;">
            <p style="margin: 0;">________________________</p>
            @if(!$isIndividual)
            <p style="margin: 4pt 0 0; font-size: 11pt;"><b>{{ strtoupper($verifierName ?: '_______________') }}</b></p>
            <p style="margin: 0; font-size: 11pt;">{{ $verifierDesignation ?: '_______________' }}</p>
            <p style="margin: 0; font-size: 11pt;"><b>{{ strtoupper($clientName) }}</b></p>
            @else
            <p style="margin: 4pt 0 0; font-size: 11pt;"><b>{{ strtoupper($clientName) }}</b></p>
            @endif
            <p style="margin: 4pt 0 0; font-size: 10pt;"><b>(Executant)</b></p>
        </td>
    </tr>
</table>

@else
<h1>BEFORE THE APPELLATE TRIBUNAL,<br>INLAND REVENUE, {{ strtoupper($bench) }}</h1>

<p class="center"><b>In RE: CM No; ____________________/{{ $year }}</b></p>
<p class="center"><b>In ITA No: ____________________/{{ $year }}</b></p>

<p><b>Appellant:</b> {{ $clientName }},<br>
NTN/CNIC No. {{ $ntn }}</p>

<p><b>Respondents:</b></p>
<p class="indent">1. {{ strtoupper($respondent2) }}</p>
<p class="indent">2. {{ strtoupper($respondent1) }}</p>

<h2 style="margin-top: 36pt;">POWER OF ATTORNEY</h2>

<p>I, <b>{{ $clientName }}</b>, {{ $idType }}# <b>{{ $ntn }}</b>, resident of <b>{{ $address }}</b>, do hereby nominate, constitute, and appoint <b>Mr. Waseem Ur Rehman</b>, Director of <b>Fair Tax (Pvt) Ltd</b>, having office at TF-121, 3rd Floor, Deans Trade Center, Islamia Road, Peshawar Cantt., as my Authorized Representative to act for and on my behalf in the above-titled matter before the Honorable Appellate Tribunal Inland Revenue, {{ $bench }}.</p>

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

{{-- Side-by-side signature block: Authorized Representative (left) | Executant (right) --}}
<table class="no-border" style="width: 100%; border: none; margin-top: 48pt; border-collapse: collapse;">
    <tr>
        <td style="border: none; padding: 0 12pt 0 0; vertical-align: top; width: 50%; text-align: center;">
            <p style="margin: 0;">________________________</p>
            <p style="margin: 6pt 0 0;"><b>Mr. Waseem Ur Rehman</b></p>
            <p style="margin: 0;">Director - Fair Tax (Pvt) Ltd</p>
            <p style="margin: 6pt 0 0;"><b>(Authorized Representative)</b></p>
        </td>
        <td style="border: none; padding: 0 0 0 12pt; vertical-align: top; width: 50%; text-align: center;">
            <p style="margin: 0;">________________________</p>
            <p style="margin: 6pt 0 0;"><b>{{ strtoupper($clientName) }}</b></p>
            <p style="margin: 6pt 0 0;"><b>(Executant)</b></p>
        </td>
    </tr>
</table>
@endif
