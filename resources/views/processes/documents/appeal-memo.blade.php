@php
$bench = $meta['bench'] ?? 'Peshawar Bench, Peshawar';
$clientName = $meta['appellant_name'] ?? $process->client->name ?? '_______________';
$ntn = $meta['ntn_cnic'] ?? '_______________';
$address = $meta['appellant_address'] ?? '_______________';
$taxYear = trim($meta['tax_year'] ?? '');
$section = $meta['section'] ?? '_______________';
$respondent1 = $meta['respondent_2'] ?? 'Commissioner Inland Revenue (Appeals)';
$respondent2 = $meta['respondent_1'] ?? 'Deputy Commissioner Inland Revenue';
$ciraOrderDate = $meta['cira_order_date'] ?? '_______________';

$clientPhone = $process->client->contact_no ?? '';
$clientEmail = $process->client->email ?? '';
$contactLine = trim(($clientPhone ? $clientPhone : '') . ($clientPhone && $clientEmail ? ', ' : '') . ($clientEmail ?: ''));
if ($contactLine === '') $contactLine = '_______________';

$irOfficeAssessment = $meta['ir_office_assessment'] ?? '_______________';
$irOfficeLocation = $meta['ir_office_location'] ?? '_______________';
$communicationDate = $meta['communication_date'] ?? $ciraOrderDate;
$verifierName = $meta['verifier_name'] ?? '_______________';
$verifierDesignation = $meta['verifier_designation'] ?? '_______________';
$verificationDay = $meta['verification_day'] ?? '_______';
$verificationMonth = $meta['verification_month'] ?? '_______________';
$verificationYear = $meta['verification_year'] ?? date('Y');
$typeOfAppeal = $meta['type_of_appeal'] ?? 'sales_tax';
$isSalesTax = $typeOfAppeal === 'sales_tax';
@endphp

<div style="margin-bottom: 6pt;">
    <p style="margin: 0; text-align: center;"><b>FORM &ldquo;B&rdquo;</b></p>
    <p style="margin: 0; text-align: center;"><b>[see rule 7]</b></p>
    <p style="margin: 0; text-align: center;"><b><u>FORM OF APPEAL TO THE APPELLATE TRIBUNAL INLAND REVENUE UNDER SECTION 46 OF THE SALES TAX ACT, 1990 OR SECTION 34 OF THE FEDERAL EXCISE ACT, 2005</u></b></p>
</div>

<p style="margin-top: 14pt;">BEFORE THE APPELLATE TRIBUNAL INLAND REVENUE&nbsp;<b><u>{{ strtoupper($bench) }}</u></b></p>

<p>Appeal/Application No.<span style="display:inline-block; border-bottom: 1px solid #000; min-width: 360px;">&nbsp;</span></p>

@php
$boxOpen  = '<span style="display:inline-block; width: 10pt; height: 10pt; border: 1px solid #000; vertical-align: middle; margin-right: 4pt;"></span>';
$boxCheck = '<span style="display:inline-block; width: 10pt; height: 10pt; border: 1px solid #000; vertical-align: middle; margin-right: 4pt; text-align: center; line-height: 10pt; font-size: 10pt;">&#10003;</span>';
@endphp

@php
$cell = 'border: 1px solid #000; padding: 2pt 6pt; background: #fff; font-size: 11pt;';
$labelCell = $cell . ' text-align: center; vertical-align: middle; font-weight: bold;';
@endphp

<table style="margin-top: 4pt; border-collapse: collapse; width: 100%;">
    <tr>
        <td style="{{ $labelCell }} width: 22%;">Type of Appeal</td>
        <td style="{{ $cell }} width: 39%;">{!! $isSalesTax ? $boxCheck : $boxOpen !!}@if($isSalesTax)<b>1. Sales Tax</b>@else 1. Sales Tax @endif</td>
        <td style="{{ $cell }}">{!! $isSalesTax ? $boxOpen : $boxCheck !!}@if(!$isSalesTax)<b>2. Federal Excise</b>@else 2. Federal Excise @endif</td>
    </tr>
</table>

<table style="margin-top: 2pt; border-collapse: collapse; width: 100%;">
    <tr>
        <td rowspan="2" style="{{ $labelCell }} width: 12%;">Relates to:</td>
        <td style="{{ $cell }} width: 22%;">{!! $boxOpen !!}1. Main Appeal</td>
        <td style="{{ $cell }} width: 22%;">{!! $boxCheck !!}<b>2. Stay Application</b></td>
        <td style="{{ $cell }} width: 22%;">{!! $boxOpen !!}3. Early Hearing</td>
        <td style="{{ $cell }}">{!! $boxOpen !!}4. Condonation of delay</td>
    </tr>
    <tr>
        <td style="{{ $cell }}">{!! $boxOpen !!}5. Rectification</td>
        <td style="{{ $cell }}">{!! $boxOpen !!}6. Recalling</td>
        <td colspan="2" style="{{ $cell }}">{!! $boxOpen !!}7. Others</td>
    </tr>
</table>

<p style="margin-top: 14pt;">Name and Address of Appellant/Applicant&nbsp;&nbsp;<b><u>{{ strtoupper($clientName) }}, {{ strtoupper($address) }}</u></b></p>

<p>Cell, Phone/Fax No. and Email Address&nbsp;&nbsp;<b><u>{{ $contactLine }}</u></b></p>

<p>Name and Address of Advocate/Representative&nbsp;&nbsp;<b><u>WASEEM UR REHMAN, FAIRTAX INTERNATIONAL</u></b><br>
<b><u>TF-121, DEANS TRADE CENTRE, PESHAWAR CANTT, PESHAWAR.</u></b></p>

<p>Cell, Phone/Fax No. and Email Address&nbsp;&nbsp;<b><u>0314-9444795, fairtaxint@gmail.com</u></b></p>

<p>Name &amp; Address of Respondent(s)&nbsp;&nbsp;1.&nbsp;<b><u>{{ strtoupper($respondent1) }}</u></b><br>
<span style="margin-left: 230pt;">2.&nbsp;<b><u>{{ strtoupper($respondent2) }}</u></b></span></p>

<p>Inland Revenue Office in which assessment was made&nbsp;&nbsp;<b><u>{{ strtoupper($irOfficeAssessment) }}</u></b></p>

<p>and one which it is located&nbsp;&nbsp;<b><u>{{ strtoupper($irOfficeLocation) }}</u></b></p>

<p>Tax year/ Tax period to which the appeal relates.&nbsp;&nbsp;<b><u>{{ $taxYear ?: '_______________' }}</u></b></p>

<p>Section of the Ordinance/Act under which Commissioner passed the order&nbsp;&nbsp;<b><u>{{ $section }}</u></b></p>

<p>Commissioner (Appeals) passing the appellate order&nbsp;&nbsp;<b><u>{{ strtoupper($respondent1) }}</u></b></p>

<p>Date of communication of the order appeal against&nbsp;&nbsp;<b><u>{{ $communicationDate }}</u></b></p>

<p style="text-align: center; margin-top: 14pt;"><b><u>VERIFICATION</u></b></p>

<p>I&nbsp;<b><u>{{ strtoupper($verifierName) }}</u></b>&nbsp;the&nbsp;<b><u>{{ strtoupper($verifierDesignation) }}</u></b>&nbsp;of the company, do hereby declare that which is stated above is true to my information and belief.</p>

<p>Verified today, the&nbsp;<b><u>{{ strtoupper($verificationDay) }}</u></b>&nbsp;day of&nbsp;<b><u>{{ strtoupper($verificationMonth) }} {{ $verificationYear }}</u></b></p>

<p style="text-align: right; margin-top: 24pt;">Signature of Appellant/Applicant&nbsp;<span style="display:inline-block; border-bottom: 1px solid #000; min-width: 180px;">&nbsp;</span></p>

<p style="text-align: right; margin-top: 24pt;">Signature of Authorized Representative&nbsp;<span style="display:inline-block; border-bottom: 1px solid #000; min-width: 180px;">&nbsp;</span></p>

<p style="margin-top: 14pt;"><b><u>Enclosures</u></b></p>

<table class="no-border">
    <tr>
        <td style="width: 50%; vertical-align: top;">
            1. Memorandum of Appeal<br>
            2. Index documents<br>
            3. Power of attorney<br>
            4. Affidavit<br>
            5. Summary of the case
        </td>
        <td style="vertical-align: top;">
            6. Recovery Memo/Seizure Report/Copy of FIR (if any)<br>
            7. Show cause notice<br>
            8. Order-in-original<br>
            9. Order-in-appeal<br>
            10. Any other document(s) relating to this appeal
        </td>
    </tr>
</table>

<p style="text-align: center; font-weight: bold; margin-top: 14pt; border-top: 1px solid #000; padding-top: 8pt;">For Official Use only:</p>

<p>Received in registry against Diary No.&nbsp;<span style="display:inline-block; border-bottom: 1px solid #000; min-width: 180px;">&nbsp;</span>&nbsp;on&nbsp;<span style="display:inline-block; border-bottom: 1px solid #000; min-width: 140px;">&nbsp;</span></p>

<p>Objection(s)&nbsp;&nbsp;1.&nbsp;<span style="display:inline-block; border-bottom: 1px solid #000; min-width: 180px;">&nbsp;</span>&nbsp;&nbsp;&nbsp;&nbsp;2.&nbsp;<span style="display:inline-block; border-bottom: 1px solid #000; min-width: 180px;">&nbsp;</span></p>

<p style="margin-left: 70pt;">3.&nbsp;<span style="display:inline-block; border-bottom: 1px solid #000; min-width: 180px;">&nbsp;</span>&nbsp;&nbsp;&nbsp;&nbsp;4.&nbsp;<span style="display:inline-block; border-bottom: 1px solid #000; min-width: 180px;">&nbsp;</span></p>

<p style="text-align: right; margin-top: 24pt;"><b>Assistant Registrar</b></p>
