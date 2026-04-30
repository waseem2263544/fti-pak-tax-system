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
$intimationNo = $meta['intimation_no'] ?? '_______________';
$referenceNo = $meta['reference_no'] ?? '_______________';
@endphp

<p>{{ $respondent2 }},<br>
Regional Tax Office,<br>
Peshawar</p>

<p class="right">Dated: {{ date('d-M-Y') }}<br>Ref: {{ $referenceNo }}</p>

<p><b>Subject: INTIMATION FOR FILING OF STAY APPLICATION IN THE CASE OF {{ strtoupper($clientName) }} NTN/CNIC NO. {{ $ntn }}@if($taxYear) FOR THE TAX YEAR {{ $taxYear }}@endif</b></p>

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
    (Partner - FairTax International)</p>
</div>
