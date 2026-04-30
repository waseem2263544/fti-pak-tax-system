@php
$bench = $meta['bench'] ?? 'Peshawar Bench Peshawar';
$clientName = $meta['appellant_name'] ?? $process->client->name ?? '_______________';
$ntn = $meta['ntn_cnic'] ?? '_______________';
$address = $meta['appellant_address'] ?? '_______________';
$taxYear = $meta['tax_year'] ?? '________';
$section = $meta['section'] ?? '122(1)';
$assessmentOrderNo = $meta['assessment_order_no'] ?? '_______________';
$assessmentOrderDate = $meta['assessment_order_date'] ?? '_______________';
$ciraOrderNo = $meta['cira_order_no'] ?? '_______________';
$ciraOrderDate = $meta['cira_order_date'] ?? '_______________';
$respondent1 = $meta['respondent_1'] ?? 'The Commissioner Inland Revenue';
$respondent2 = $meta['respondent_2'] ?? 'The Commissioner Inland Revenue (Appeals)';
$recoveryNoticeNo = $meta['recovery_notice_no'] ?? '_______________';
$recoveryNoticeDate = $meta['recovery_notice_date'] ?? '_______________';
$year = date('Y');
@endphp

<h1>BEFORE THE APPELLATE TRIBUNAL INLAND<br>REVENUE {{ strtoupper($bench) }}</h1>

<p class="center"><b>In Income Tax Appeal No. ___________________/{{ $year }}</b></p>

<p><b>Appellant:</b> {{ strtoupper($clientName) }},<br>
CNIC/NTN No. {{ $ntn }}</p>

<p><b>Application of interim relief</b> for the Order U/s {{ $section }} passed by the {{ $respondent1 }} vide order No. {{ $assessmentOrderNo }} dated {{ $assessmentOrderDate }} as well as Order U/s 129(1) Passed by the {{ $respondent2 }} vide Order No. {{ $ciraOrderNo }} dated {{ $ciraOrderDate }}.</p>

<p><b>Respected Sir,</b></p>

<p>The Applicant humbly submits as under:</p>

<p class="indent">1. That the Applicant has filed an appeal before your Honour and yet the date of hearing has not been fixed.</p>

<p class="indent">2. That facts and grounds mentioned in the grounds of appeal may kindly be considered as an integral part of this application.</p>

<p class="indent">3. That Applicant has an excellent prima facie case in its favor and there is genuine hope of its success.</p>

<p class="indent">4. That the department is pressing hard for payment of the disputed amount. (Copy of recovery notice No. {{ $recoveryNoticeNo }} dated {{ $recoveryNoticeDate }} is enclosed).</p>

<p class="indent">5. That the balance of convenience is also in favor of the Applicant.</p>

<p class="indent">6. That the impugned order passed by the {{ $respondent1 }} is totally illegal and against the facts of the case.</p>

<p style="margin-top: 18pt;">It is therefore humbly prayed that on acceptance of this application, impugned order may be further suspended till final decision of main appeal, bank accounts may be de-attached and proceedings initiated against our client may be stopped till final decision of main appeal.</p>

<p>Any other relief deemed appropriate in the circumstances may be granted.</p>

<div class="signature right">
    <p><b>{{ strtoupper($clientName) }}</b></p>
    <p>Through</p>
    <p><b>Waseem Ur Rehman</b><br>
    Fair Tax (Pvt) Ltd<br>
    Partner<br>
    TF – 121, Deans Trade Centre<br>
    Peshawar Cantt, Peshawar.</p>
</div>
