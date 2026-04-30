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
$isStTribunalStay = ($process->template ?? '') === 'st-tribunal-stay';
$ntnDigits = preg_replace('/\D/', '', $ntn);
$idType = strlen($ntnDigits) === 13 ? 'CNIC' : 'NTN';
@endphp

@if($isStTribunalStay)
<div style="max-width: 280pt;">
    <p style="margin: 0;">{{ $respondent2 }},<br>
    Regional Tax Office,<br>
    Peshawar</p>
</div>
@else
<p>{{ $respondent2 }},<br>
Regional Tax Office,<br>
Peshawar</p>
@endif

<p class="right">Dated: {{ date('d-M-Y') }}<br>Ref: {{ $referenceNo }}</p>

@if($isStTribunalStay)
<p><b>SUBJECT:</b> <b><u>INTIMATION FOR FILING OF STAY APPLICATION IN THE CASE OF {{ strtoupper($clientName) }}, {{ $idType }} {{ $ntn }}, FOR THE ASSESSMENT ORDER NO. {{ strtoupper($assessmentOrderNo) }}.</u></b></p>
@else
<p><b>Subject: INTIMATION FOR FILING OF STAY APPLICATION IN THE CASE OF {{ strtoupper($clientName) }} NTN/CNIC NO. {{ $ntn }}{!! $taxYearText !!}</b></p>
@endif

<p>Respected Sir,</p>

<p>Please refer to the above.</p>

<p>It is submitted that the appellant above is going to file application for Stay before the Honorable Appellate Tribunal Inland Revenue {{ $bench }} against the orders passed by the {{ $respondent1 }} vide Order No. {{ $assessmentOrderNo }} dated {{ $assessmentOrderDate }} as well as the {{ $respondent2 }} vide Order in Appeal No. {{ $ciraOrderNo }} dated {{ $ciraOrderDate }}, attached herewith:</p>

<p class="indent" style="margin-top: 12pt;">
    1. Grounds of Appeal<br>
    2. Demand Notice<br>
    3. Order U/s 129(1) (Order to Confirm/Modify/Remand-Back/Annul Appeal Application)<br>
    4. Order U/s 122(1) (Order to amend Self or Best Judgment or Provisional assessment)
</p>

<p style="margin-top: 24pt;">Thanks</p>

<div class="signature">
    <p>Yours' sincerely,</p>
    <p style="margin-top: 36pt;"><b>Waseem Ur Rehman</b><br>
    (Director - Fair Tax (Pvt) Ltd)</p>
</div>
