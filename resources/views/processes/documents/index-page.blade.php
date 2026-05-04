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
$taxYearText = $taxYear !== '' ? ' FOR THE TAX YEAR ' . e($taxYear) : '';
$isStTribunalStay = ($process->template ?? '') === 'st-tribunal-stay';
$ciraOrderNo = $meta['cira_order_no'] ?? '_______________';
$assessmentOrderNo = $meta['assessment_order_no'] ?? '_______________';

// Cumulative page numbering for st-tribunal-stay package.
// Prefer measured starts/counts from the two-pass renderer; fall back to estimates.
$measuredStarts = $meta['__section_starts'] ?? null;
$measuredPages  = $meta['__section_pages']  ?? null;

$orderInAppealPages   = max(1, (int) ($meta['order_in_appeal_file_pages']   ?? 1));
$orderInOriginalPages = max(1, (int) ($meta['order_in_original_file_pages'] ?? 1));
$recoveryNoticePages  = max(1, (int) ($meta['recovery_notice_file_pages']   ?? 1));

if (is_array($measuredStarts) && is_array($measuredPages)) {
    $pAppealMemo      = $measuredStarts['appeal_memo']       ?? 1;
    $pStayApp         = $measuredStarts['stay_app']          ?? 0;
    $pGrounds         = $measuredStarts['grounds']           ?? 0;
    $pOrderInAppeal   = $measuredStarts['order_in_appeal']   ?? 0;
    $pOrderInOriginal = $measuredStarts['order_in_original'] ?? 0;
    $pRecoveryNotice  = $measuredStarts['recovery_notice']   ?? 0;
    $pIntimation      = $measuredStarts['intimation']        ?? 0;
    $pPOA             = $measuredStarts['poa']               ?? 0;
    $pAffidavit       = $measuredStarts['affidavit']         ?? 0;
    $appealMemoPages  = $measuredPages['appeal_memo']        ?? 1;
    $stayAppPages     = $measuredPages['stay_app']           ?? 1;
    $groundsPages     = $measuredPages['grounds']            ?? 1;
    $intimationPages  = $measuredPages['intimation']         ?? 1;
    $poaPages         = $measuredPages['poa']                ?? 1;
    $affidavitPages   = $measuredPages['affidavit']          ?? 1;
    $orderInAppealPages   = $measuredPages['order_in_appeal']   ?? $orderInAppealPages;
    $orderInOriginalPages = $measuredPages['order_in_original'] ?? $orderInOriginalPages;
    $recoveryNoticePages  = $measuredPages['recovery_notice']   ?? $recoveryNoticePages;
} else {
    // Fallback (HTML preview / estimate before PDF render)
    $appealMemoPages    = 2;
    $stayAppPages       = 1;
    $groundsPages       = max(1, (int) ($meta['grounds_pages_override'] ?? 1));
    $intimationPages    = 1;
    $poaPages           = 1;
    $affidavitPages     = 1;
    $pAppealMemo      = 1;
    $pStayApp         = $pAppealMemo      + $appealMemoPages;
    $pGrounds         = $pStayApp         + $stayAppPages;
    $pOrderInAppeal   = $pGrounds         + $groundsPages;
    $pOrderInOriginal = $pOrderInAppeal   + $orderInAppealPages;
    $pRecoveryNotice  = $pOrderInOriginal + $orderInOriginalPages;
    $pIntimation      = $pRecoveryNotice  + $recoveryNoticePages;
    $pPOA             = $pIntimation      + $intimationPages;
    $pAffidavit       = $pPOA             + $poaPages;
}
@endphp

@if($isStTribunalStay && !($inCombinedPdf ?? false))
@include('processes.documents._letterhead-header')
@elseif(!$isStTribunalStay)
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
@endif

@if($isStTribunalStay)
<div style="font-size: 10pt; line-height: 1.35;">

<h1 style="font-size: 14pt; line-height: 1.25; margin: 0 0 8pt;">BEFORE THE APPELLATE TRIBUNAL INLAND<br>REVENUE {{ strtoupper($bench) }}</h1>

<p style="margin: 4pt 0; font-size: 10pt;"><b>In RE: CM No.____________________/{{ $year }}</b></p>

<p style="margin: 14pt 0 14pt; font-size: 10pt;"><b>SUBJECT:</b> <b><u>STAY APPLICATION IN THE CASE OF {{ strtoupper($clientName) }} {{ $idType }} NO. {{ $ntn }}{!! $taxYearText !!}</u></b></p>

<p style="margin: 4pt 0 1pt; font-size: 10pt;"><b>RESPONDENTS:</b></p>
<p class="indent" style="margin: 0; font-size: 10pt;">1. {{ strtoupper($respondent2) }}</p>
<p class="indent" style="margin: 0; font-size: 10pt;">2. {{ strtoupper($respondent1) }}</p>

<table style="margin-top: 8pt; font-size: 9pt;">
    <thead>
        <tr>
            <th style="width: 10%; padding: 3pt 6pt; font-size: 9pt;">S. NO</th>
            <th style="width: 65%; padding: 3pt 6pt; font-size: 9pt;">DESCRIPTION</th>
            <th style="width: 25%; padding: 3pt 6pt; font-size: 9pt;">PAGE NO.</th>
        </tr>
    </thead>
@else
<h1 style="font-size: 13pt; line-height: 1.2; margin: 0 0 6pt;">BEFORE THE APPELLATE TRIBUNAL INLAND<br>REVENUE {{ strtoupper($bench) }}</h1>

<p style="margin: 4pt 0;"><b>In RE: CM No.____________________/{{ $year }}</b></p>

<p style="margin: 4pt 0;"><b>SUBJECT:</b> <b><u>STAY APPLICATION IN THE CASE OF {{ strtoupper($clientName) }} {{ $idType }} NO. {{ $ntn }}{!! $taxYearText !!}</u></b></p>

<p style="margin: 6pt 0 2pt;"><b>RESPONDENTS:</b></p>
<p class="indent" style="margin: 0;">1. {{ strtoupper($respondent2) }}</p>
<p class="indent" style="margin: 0;">2. {{ strtoupper($respondent1) }}</p>

<table style="margin-top: 10pt;">
    <thead>
        <tr>
            <th style="width: 10%;">S. NO</th>
            <th style="width: 65%;">DESCRIPTION</th>
            <th style="width: 25%;">PAGE NO.</th>
        </tr>
    </thead>
@endif
    <tbody>
        @if($isStTribunalStay)
        <tr><td class="center" style="padding: 3pt 6pt;">1</td><td style="padding: 3pt 6pt;">APPEAL MEMO</td><td class="center" style="padding: 3pt 6pt;">{{ $appealMemoPages > 1 ? $pAppealMemo . '-' . ($pAppealMemo + $appealMemoPages - 1) : $pAppealMemo }}</td></tr>
        <tr><td class="center" style="padding: 3pt 6pt;">2</td><td style="padding: 3pt 6pt;">STAY APPLICATION</td><td class="center" style="padding: 3pt 6pt;">{{ $pStayApp }}</td></tr>
        <tr><td class="center" style="padding: 3pt 6pt;">3</td><td style="padding: 3pt 6pt;">GROUNDS OF APPEAL</td><td class="center" style="padding: 3pt 6pt;">{{ $groundsPages > 1 ? $pGrounds . '-' . ($pGrounds + $groundsPages - 1) : $pGrounds }}</td></tr>
        <tr><td class="center" style="padding: 3pt 6pt;">4</td><td style="padding: 3pt 6pt;">ORDER IN APPEAL {{ $ciraOrderNo }}</td><td class="center" style="padding: 3pt 6pt;">{{ $orderInAppealPages > 1 ? $pOrderInAppeal . '-' . ($pOrderInAppeal + $orderInAppealPages - 1) : $pOrderInAppeal }}</td></tr>
        <tr><td class="center" style="padding: 3pt 6pt;">5</td><td style="padding: 3pt 6pt;">ORDER IN ORIGINAL {{ $assessmentOrderNo }}</td><td class="center" style="padding: 3pt 6pt;">{{ $orderInOriginalPages > 1 ? $pOrderInOriginal . '-' . ($pOrderInOriginal + $orderInOriginalPages - 1) : $pOrderInOriginal }}</td></tr>
        <tr><td class="center" style="padding: 3pt 6pt;">6</td><td style="padding: 3pt 6pt;">RECOVERY NOTICE</td><td class="center" style="padding: 3pt 6pt;">{{ $recoveryNoticePages > 1 ? $pRecoveryNotice . '-' . ($pRecoveryNotice + $recoveryNoticePages - 1) : $pRecoveryNotice }}</td></tr>
        <tr><td class="center" style="padding: 3pt 6pt;">7</td><td style="padding: 3pt 6pt;">INTIMATION LETTER</td><td class="center" style="padding: 3pt 6pt;">{{ $pIntimation }}</td></tr>
        <tr><td class="center" style="padding: 3pt 6pt;">8</td><td style="padding: 3pt 6pt;">POWER OF ATTORNEY</td><td class="center" style="padding: 3pt 6pt;">{{ $pPOA }}</td></tr>
        <tr><td class="center" style="padding: 3pt 6pt;">9</td><td style="padding: 3pt 6pt;">AFFIDAVIT</td><td class="center" style="padding: 3pt 6pt;">{{ $pAffidavit }}</td></tr>
        @else
        <tr><td class="center">1</td><td>APPEAL MEMO</td><td></td></tr>
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
        @endif
    </tbody>
</table>

<div class="signature right" style="margin-top: 14pt;">
    <p style="margin: 0; font-size: 10pt;"><b>WASEEM UR REHMAN</b><br>
    DIRECTOR<br>
    FAIR TAX (PVT) LTD<br>
    AUTHORIZED REPRESENTATIVE</p>
</div>

@if($isStTribunalStay)
</div>
@endif

@if($isStTribunalStay && !($inCombinedPdf ?? false))
@include('processes.documents._letterhead-footer')
@endif
