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
// Each generated doc assumed to be 1 page; uploaded files use stored page counts.
$orderInAppealPages   = max(1, (int) ($meta['order_in_appeal_file_pages']   ?? 1));
$orderInOriginalPages = max(1, (int) ($meta['order_in_original_file_pages'] ?? 1));
$recoveryNoticePages  = max(1, (int) ($meta['recovery_notice_file_pages']   ?? 1));
$pAppealMemo      = 1;
$pStayApp         = $pAppealMemo + 1;
$pGrounds         = $pStayApp + 1;
$pOrderInAppeal   = $pGrounds + 1;
$pOrderInOriginal = $pOrderInAppeal + $orderInAppealPages;
$pRecoveryNotice  = $pOrderInOriginal + $orderInOriginalPages;
$pIntimation      = $pRecoveryNotice + $recoveryNoticePages;
$pPOA             = $pIntimation + 1;
$pAffidavit       = $pPOA + 1;
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
    <tbody>
        @if($isStTribunalStay)
        <tr><td class="center">1</td><td>APPEAL MEMO</td><td class="center">{{ $pAppealMemo }}</td></tr>
        <tr><td class="center">2</td><td>STAY APPLICATION</td><td class="center">{{ $pStayApp }}</td></tr>
        <tr><td class="center">3</td><td>GROUNDS OF APPEAL</td><td class="center">{{ $pGrounds }}</td></tr>
        <tr><td class="center">4</td><td>ORDER IN APPEAL {{ $ciraOrderNo }}</td><td class="center">{{ $orderInAppealPages > 1 ? $pOrderInAppeal . '-' . ($pOrderInAppeal + $orderInAppealPages - 1) : $pOrderInAppeal }}</td></tr>
        <tr><td class="center">5</td><td>ORDER IN ORIGINAL {{ $assessmentOrderNo }}</td><td class="center">{{ $orderInOriginalPages > 1 ? $pOrderInOriginal . '-' . ($pOrderInOriginal + $orderInOriginalPages - 1) : $pOrderInOriginal }}</td></tr>
        <tr><td class="center">6</td><td>RECOVERY NOTICE</td><td class="center">{{ $recoveryNoticePages > 1 ? $pRecoveryNotice . '-' . ($pRecoveryNotice + $recoveryNoticePages - 1) : $pRecoveryNotice }}</td></tr>
        <tr><td class="center">7</td><td>INTIMATION LETTER</td><td class="center">{{ $pIntimation }}</td></tr>
        <tr><td class="center">8</td><td>POWER OF ATTORNEY</td><td class="center">{{ $pPOA }}</td></tr>
        <tr><td class="center">9</td><td>AFFIDAVIT</td><td class="center">{{ $pAffidavit }}</td></tr>
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

<div class="signature right" style="margin-top: 18pt;">
    <p style="margin: 0;"><b>WASEEM UR REHMAN</b><br>
    DIRECTOR<br>
    FAIR TAX (PVT) LTD<br>
    AUTHORIZED REPRESENTATIVE</p>
</div>

@if($isStTribunalStay && !($inCombinedPdf ?? false))
@include('processes.documents._letterhead-footer')
@endif
