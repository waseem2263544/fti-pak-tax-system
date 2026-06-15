@php
$bench = $meta['bench'] ?? 'Peshawar Bench Peshawar';
$clientName = $meta['appellant_name'] ?? $process->client->name ?? '_______________';
$ntn = $meta['ntn_cnic'] ?? '_______________';
$taxYear = trim($meta['tax_year'] ?? '');
$respondent1 = $meta['respondent_1'] ?? 'The Commissioner Inland Revenue';
$respondent2 = $meta['respondent_2'] ?? 'The Commissioner Inland Revenue (Appeals)';
$assessmentOrderNo = $meta['assessment_order_no'] ?? '_______________';
$assessmentOrderDate = $meta['assessment_order_date'] ?? '_______________';
$ciraOrderNo = $meta['cira_order_no'] ?? '_______________';
$ciraOrderDate = $meta['cira_order_date'] ?? '_______________';
$referenceNo = $meta['reference_no'] ?? '_______________';
$taxYearText = $taxYear !== '' ? ' FOR THE TAX YEAR ' . e($taxYear) : '';
$isStTribunalStay = in_array(($process->template ?? ''), ['st-tribunal-stay', 'st-tribunal-stay-extension'], true);
$isItTribunalAppeal = ($process->template ?? '') === 'it-tribunal-appeal';

// Intimation letter date. For it-tribunal-appeal anchor to a STORED date (filing date, else the
// process creation date) so it doesn't drift to "today" each time the document is re-opened.
if ($isItTribunalAppeal) {
    try {
        $intimationDt = !empty($meta['filing_date']) ? \Carbon\Carbon::parse($meta['filing_date']) : ($process->created_at ?? \Carbon\Carbon::now());
    } catch (\Exception $e) {
        $intimationDt = $process->created_at ?? \Carbon\Carbon::now();
    }
    $intimationDate = $intimationDt->format('d-M-Y');
} else {
    $intimationDate = date('d-M-Y');
}
$ntnDigits = preg_replace('/\D/', '', $ntn);
$idType = strlen($ntnDigits) === 13 ? 'CNIC' : 'NTN';
$recoveryNoticeNo = $meta['recovery_notice_no'] ?? '_______________';
$recoveryNoticeDateRaw = $meta['recovery_notice_date'] ?? '';
$recoveryNoticeDate = '_______________';
if ($isStTribunalStay && $recoveryNoticeDateRaw) {
    try { $recoveryNoticeDate = \Carbon\Carbon::parse($recoveryNoticeDateRaw)->format('j F Y'); }
    catch (\Exception $e) { $recoveryNoticeDate = $recoveryNoticeDateRaw; }
} elseif ($recoveryNoticeDateRaw) {
    $recoveryNoticeDate = $recoveryNoticeDateRaw;
}
@endphp

@if($isStTribunalStay)
@if(!($inCombinedPdf ?? false))
@include('processes.documents._letterhead-header')
@endif

<div style="margin: 64pt 0 10pt; width: 40%;">
    <p style="margin: 0; font-size: 11pt; line-height: 1.5;">{{ $respondent2 }}</p>
</div>
@elseif($isItTribunalAppeal)
{{-- space below the letterhead header, then a narrow addressee block (~35% wide) that wraps by word --}}
<div style="height: 48pt;"></div>
<div style="width: 35%; margin: 0 0 10pt;">
    <p style="margin: 0; line-height: 1.6;">{{ $respondent2 }}</p>
</div>
@else
<p>{{ $respondent2 }}</p>
@endif

<p class="right">Dated: {{ $intimationDate }}<br>Ref: {{ $referenceNo }}</p>

@if($isStTribunalStay)
<p><b>SUBJECT:</b> <b><u>INTIMATION FOR FILING OF STAY APPLICATION IN THE CASE OF {{ strtoupper($clientName) }}, {{ $idType }} {{ $ntn }}, FOR THE ASSESSMENT ORDER NO. {{ strtoupper($assessmentOrderNo) }}.</u></b></p>
@elseif($isItTribunalAppeal)
<p><b>SUBJECT:</b> <b><u>INTIMATION FOR FILING OF APPEAL IN THE CASE OF {{ strtoupper($clientName) }}, {{ $idType }} {{ $ntn }}{!! $taxYearText !!}</u></b></p>
@else
<p><b>Subject: INTIMATION FOR FILING OF STAY APPLICATION IN THE CASE OF {{ strtoupper($clientName) }} NTN/CNIC NO. {{ $ntn }}{!! $taxYearText !!}</b></p>
@endif

@if($isStTribunalStay)
<p>Respected Sir/ Madam,</p>

<p>With reference to the above-subject matter, the appellant is going to file a stay application before the Honorable Appellate Tribunal Inland Revenue, {{ $bench }}, against the recovery notice No. {{ $recoveryNoticeNo }}, dated {{ $recoveryNoticeDate }}, as per the grounds of appeal.</p>

<p>Enclosed, please find the following documents;</p>

<p class="indent" style="margin-top: 6pt;">
    1. Stay Application<br>
    2. Form B<br>
    3. Grounds of Appeal<br>
    4. Order in Original<br>
    5. Order in Appeal<br>
    6. Recovery Notice<br>
    7. Power of Attorney<br>
    8. Affidavit
</p>
@elseif($isItTribunalAppeal)
<p>Respected Sir/ Madam,</p>

<p>With reference to the above-subject matter, the appellant is going to file an appeal before the Honorable Appellate Tribunal Inland Revenue, {{ $bench }}, against the order passed by the {{ $respondent1 }}, as per the grounds of appeal.</p>

<p>Enclosed, please find the following documents;</p>

<p class="indent" style="margin-top: 6pt;">
    1. Form A<br>
    2. Grounds of Appeal<br>
    3. Order in Appeal<br>
    4. Original Order<br>
    5. Fee Challan<br>
    6. Power of Attorney<br>
    7. Affidavit
</p>
@else
<p>Respected Sir,</p>

<p>Please refer to the above.</p>

<p>It is submitted that the appellant above is going to file application for Stay before the Honorable Appellate Tribunal Inland Revenue {{ $bench }} against the orders passed by the {{ $respondent1 }} vide Order No. {{ $assessmentOrderNo }} dated {{ $assessmentOrderDate }} as well as the {{ $respondent2 }} vide Order in Appeal No. {{ $ciraOrderNo }} dated {{ $ciraOrderDate }}, attached herewith:</p>

<p class="indent" style="margin-top: 12pt;">
    1. Grounds of Appeal<br>
    2. Demand Notice<br>
    3. Order U/s 129(1) (Order to Confirm/Modify/Remand-Back/Annul Appeal Application)<br>
    4. Order U/s 122(1) (Order to amend Self or Best Judgment or Provisional assessment)
</p>
@endif

@if($isStTribunalStay)
<div style="margin-top: 18pt; font-size: 10pt;">
    <p style="margin: 0;">Yours' sincerely,</p>
    <p style="margin: 24pt 0 0;"><b>Waseem Ur Rehman</b><br>
    (Director - Fair Tax (Pvt) Ltd)</p>
</div>
@elseif($isItTribunalAppeal)
<div style="margin-top: 18pt;">
    <p style="margin: 0;">Yours' sincerely,</p>
    <p style="margin: 24pt 0 0;"><b>Waseem Ur Rehman</b><br>
    (Director - Fair Tax (Pvt) Ltd)</p>
</div>
@else
<p style="margin-top: 24pt;">Thanks</p>

<div class="signature">
    <p>Yours' sincerely,</p>
    <p style="margin-top: 36pt;"><b>Waseem Ur Rehman</b><br>
    (Director - Fair Tax (Pvt) Ltd)</p>
</div>
@endif

@if($isStTribunalStay && !($inCombinedPdf ?? false))
@include('processes.documents._letterhead-footer')
@endif
