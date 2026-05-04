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

$clientPhone = $meta['appellant_phone'] ?? $process->client->contact_no ?? '';
$clientEmail = $meta['appellant_email'] ?? $process->client->email ?? '';
$contactLine = trim(($clientPhone ? $clientPhone : '') . ($clientPhone && $clientEmail ? ', ' : '') . ($clientEmail ?: ''));
if ($contactLine === '') $contactLine = '';

$irOfficeAssessment = $meta['ir_office_assessment'] ?? '';
$irOfficeLocation = $meta['ir_office_location'] ?? '';
$communicationDate = $meta['communication_date'] ?? $ciraOrderDate;
$commissionerAppeals = $meta['commissioner_appeals'] ?? '';
$verifierName = $meta['verifier_name'] ?? '';
$verifierDesignation = $meta['verifier_designation'] ?? '';
$typeOfAppeal = $meta['type_of_appeal'] ?? 'sales_tax';
$isSalesTax = $typeOfAppeal === 'sales_tax';

// Determine individual vs company from registration number digits
$ntnDigits = preg_replace('/\D/', '', $ntn);
$isIndividual = strlen($ntnDigits) === 13;

// Derive verification day / month / year from filing date
$filingDate = $meta['filing_date'] ?? null;
$verificationDay = '';
$verificationMonth = '';
$verificationYear = date('Y');
if ($filingDate) {
    try {
        $dt = \Carbon\Carbon::parse($filingDate);
        $verificationDay = $dt->format('jS');
        $verificationMonth = $dt->format('F');
        $verificationYear = $dt->format('Y');
    } catch (\Exception $e) {
        // ignore parse errors
    }
}

// Format communication date as readable
$communicationDateDisplay = '';
if ($communicationDate && $communicationDate !== '_______________') {
    try {
        $communicationDateDisplay = \Carbon\Carbon::parse($communicationDate)->format('d-M-Y');
    } catch (\Exception $e) {
        $communicationDateDisplay = $communicationDate;
    }
}

// Reusable inline styles
$rowLabel = 'border: none; padding: 3pt 8pt 3pt 0; vertical-align: top; font-size: 10pt;';
$rowValue = 'border: none; border-bottom: 1px solid #000; padding: 3pt 8pt; vertical-align: bottom; font-weight: bold; font-size: 10pt;';
$rowBlank = 'border: none; padding: 3pt 8pt; vertical-align: top;';
$boxOpen  = '<span style="display:inline-block; width: 10pt; height: 10pt; border: 1px solid #000; vertical-align: middle; margin-right: 5pt;"></span>';
$boxCheck = '<span style="display:inline-block; width: 10pt; height: 10pt; border: 1px solid #000; vertical-align: middle; margin-right: 5pt; text-align: center; line-height: 10pt; font-size: 10pt;">&#10003;</span>';
$cell = 'border: 1px solid #000; padding: 2pt 6pt; background: #fff; font-size: 10pt;';
$labelCell = $cell . ' text-align: center; vertical-align: middle; font-weight: bold;';
@endphp

<div style="font-size: 10pt;">

<div style="margin-bottom: 4pt;">
    <p style="margin: 0; text-align: center; font-size: 12pt;"><b>FORM &ldquo;B&rdquo;</b></p>
    <p style="margin: 2pt 0; text-align: center; font-size: 10pt;"><b>[see rule 7]</b></p>
    <p style="margin: 2pt 0 0; text-align: center; line-height: 1.3; font-size: 10pt;"><b><u>FORM OF APPEAL TO THE APPELLATE TRIBUNAL INLAND REVENUE UNDER SECTION 46 OF THE SALES TAX ACT, 1990 OR SECTION 34 OF THE FEDERAL EXCISE ACT, 2005</u></b></p>
</div>

<p style="margin-top: 10pt; text-align: center; font-size: 11pt;">BEFORE THE APPELLATE TRIBUNAL INLAND REVENUE&nbsp;<b><u>{{ strtoupper($bench) }}</u></b></p>

<table style="width: 100%; border-collapse: collapse; margin-top: 8pt;">
    <tr>
        <td style="{{ $rowLabel }} width: 38%;">Appeal/Application No.</td>
        <td style="{{ $rowValue }}">&nbsp;</td>
    </tr>
</table>

<table style="margin-top: 6pt; border-collapse: collapse; width: 100%;">
    <tr>
        <td style="{{ $labelCell }} width: 22%;">Type of Appeal</td>
        <td style="{{ $cell }} width: 39%;">{!! $isSalesTax ? $boxCheck : $boxOpen !!}@if($isSalesTax)<b>1. Sales Tax</b>@else 1. Sales Tax @endif</td>
        <td style="{{ $cell }}">{!! $isSalesTax ? $boxOpen : $boxCheck !!}@if(!$isSalesTax)<b>2. Federal Excise</b>@else 2. Federal Excise @endif</td>
    </tr>
</table>

<table style="margin-top: 4pt; border-collapse: collapse; width: 100%;">
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

<table style="width: 100%; border-collapse: collapse; margin-top: 10pt;">
    <tr>
        <td style="{{ $rowLabel }} width: 38%;">Name and Address of Appellant/Applicant</td>
        <td style="{{ $rowValue }}">{{ strtoupper($clientName) }}, {{ strtoupper($address) }}</td>
    </tr>
    <tr>
        <td style="{{ $rowLabel }}">Cell, Phone/Fax No. and Email Address</td>
        <td style="{{ $rowValue }}">{{ $contactLine }}</td>
    </tr>
</table>

<table style="width: 100%; border-collapse: collapse; margin-top: 6pt;">
    <tr>
        <td style="{{ $rowLabel }} width: 38%;">Name and Address of Advocate/Representative</td>
        <td style="{{ $rowValue }}">WASEEM UR REHMAN, FAIR TAX (PVT) LTD, TF-121, DEANS TRADE CENTRE, PESHAWAR CANTT, PESHAWAR.</td>
    </tr>
    <tr>
        <td style="{{ $rowLabel }}">Cell, Phone/Fax No. and Email Address</td>
        <td style="{{ $rowValue }}">0314-9444795, fairtaxint@gmail.com</td>
    </tr>
</table>

<table style="width: 100%; border-collapse: collapse; margin-top: 6pt;">
    <tr>
        <td rowspan="2" style="{{ $rowLabel }} width: 38%;">Name &amp; Address of Respondent(s)</td>
        <td style="border: none; padding: 3pt 4pt 3pt 8pt; vertical-align: bottom; font-weight: bold; width: 4%; font-size: 10pt;">1.</td>
        <td style="{{ $rowValue }}">{{ strtoupper($respondent1) }}</td>
    </tr>
    <tr>
        <td style="border: none; padding: 3pt 4pt 3pt 8pt; vertical-align: bottom; font-weight: bold; font-size: 10pt;">2.</td>
        <td style="{{ $rowValue }}">{{ strtoupper($respondent2) }}</td>
    </tr>
</table>

<table style="width: 100%; border-collapse: collapse; margin-top: 10pt;">
    <tr>
        <td style="{{ $rowLabel }} width: 38%;">Inland Revenue Office in which assessment was made</td>
        <td style="{{ $rowValue }}">{{ strtoupper($irOfficeAssessment) }}</td>
    </tr>
    <tr>
        <td style="{{ $rowLabel }} width: 38%;">and one which it is located</td>
        <td style="{{ $rowValue }}">{{ strtoupper($irOfficeLocation) }}</td>
    </tr>
    <tr>
        <td style="{{ $rowLabel }} width: 38%;">Tax year/ Tax period to which the appeal relates.</td>
        <td style="{{ $rowValue }}">{{ $taxYear }}</td>
    </tr>
    <tr>
        <td style="{{ $rowLabel }} width: 38%;">Section of the Ordinance/Act under which Commissioner passed the order</td>
        <td style="{{ $rowValue }}">{{ $section !== '_______________' ? $section : '' }}</td>
    </tr>
    <tr>
        <td style="{{ $rowLabel }} width: 38%;">Commissioner (Appeals) passing the appellate order</td>
        <td style="{{ $rowValue }}">{{ strtoupper($commissionerAppeals) }}</td>
    </tr>
    <tr>
        <td style="{{ $rowLabel }} width: 38%;">Date of communication of the order appeal against</td>
        <td style="{{ $rowValue }}">{{ $communicationDateDisplay }}</td>
    </tr>
</table>

<p style="text-align: center; margin: 10pt 0 6pt; font-size: 11pt;"><b><u>VERIFICATION</u></b></p>

@if($isIndividual)
<p style="line-height: 1.5; margin: 0; text-align: justify; font-size: 10pt;">I,&nbsp;<span style="border-bottom: 1px solid #000; font-weight: bold;">{{ strtoupper($clientName) }}</span>, CNIC #&nbsp;<span style="border-bottom: 1px solid #000; font-weight: bold;">{{ $ntn }}</span>, do hereby declare that which is stated above is true to my information and belief.</p>
@else
<p style="line-height: 1.5; margin: 0; text-align: justify; font-size: 10pt;">I,&nbsp;<span style="border-bottom: 1px solid #000; font-weight: bold;">{{ strtoupper($verifierName) }}</span>, the&nbsp;<span style="border-bottom: 1px solid #000; font-weight: bold;">{{ strtoupper($verifierDesignation) }}</span>&nbsp;of the company, do hereby declare that which is stated above is true to my information and belief.</p>
@endif

<p style="line-height: 1.5; margin: 4pt 0 0; font-size: 10pt;">Verified today, the&nbsp;<span style="border-bottom: 1px solid #000; font-weight: bold;">{{ strtoupper($verificationDay) }}</span>&nbsp;day of&nbsp;<span style="border-bottom: 1px solid #000; font-weight: bold;">{{ strtoupper($verificationMonth) }} {{ $verificationYear }}</span></p>

<table style="width: 100%; border-collapse: collapse; margin-top: 50pt;">
    <tr>
        <td style="border: none; padding: 0; width: 50%; text-align: center;">
            <div style="border-top: 1px solid #000; padding-top: 4pt; font-size: 10pt;">Signature of Appellant/Applicant</div>
        </td>
        <td style="border: none; padding: 0; width: 50%; text-align: center;">
            <div style="border-top: 1px solid #000; padding-top: 4pt; font-size: 10pt;">Signature of Authorized Representative</div>
        </td>
    </tr>
</table>

</div>

<pagebreak />
<p style="margin: 0 0 6pt;"><b><u>Enclosures</u></b></p>

<table style="width: 100%; border-collapse: collapse;">
    <tr>
        <td style="border: none; padding: 2pt 8pt 2pt 0; vertical-align: top; width: 50%; line-height: 1.8;">
            1. Memorandum of Appeal<br>
            2. Index documents<br>
            3. Power of attorney<br>
            4. Affidavit<br>
            5. Summary of the case
        </td>
        <td style="border: none; padding: 2pt 0; vertical-align: top; line-height: 1.8;">
            6. Recovery Memo/Seizure Report/Copy of FIR (if any)<br>
            7. Show cause notice<br>
            8. Order-in-original<br>
            9. Order-in-appeal<br>
            10. Any other document(s) relating to this appeal
        </td>
    </tr>
</table>

<div style="border-top: 1.5pt solid #000; margin-top: 28pt; padding-top: 12pt;">
    <p style="text-align: center; margin: 0 0 12pt;"><b>For Official Use only:</b></p>

    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="{{ $rowLabel }} width: 32%;">Received in registry against Diary No.</td>
            <td style="{{ $rowValue }} width: 30%;">&nbsp;</td>
            <td style="border: none; padding: 6pt 8pt; vertical-align: bottom; width: 4%;">on</td>
            <td style="{{ $rowValue }}">&nbsp;</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-top: 4pt;">
        <tr>
            <td style="{{ $rowLabel }} width: 14%;">Objection(s)</td>
            <td style="border: none; padding: 6pt 4pt 6pt 8pt; vertical-align: bottom; width: 4%;">1.</td>
            <td style="{{ $rowValue }} width: 36%;">&nbsp;</td>
            <td style="border: none; padding: 6pt 4pt 6pt 8pt; vertical-align: bottom; width: 4%;">2.</td>
            <td style="{{ $rowValue }}">&nbsp;</td>
        </tr>
        <tr>
            <td style="{{ $rowLabel }}">&nbsp;</td>
            <td style="border: none; padding: 6pt 4pt 6pt 8pt; vertical-align: bottom;">3.</td>
            <td style="{{ $rowValue }}">&nbsp;</td>
            <td style="border: none; padding: 6pt 4pt 6pt 8pt; vertical-align: bottom;">4.</td>
            <td style="{{ $rowValue }}">&nbsp;</td>
        </tr>
    </table>

    <p style="text-align: right; margin-top: 36pt;"><b>Assistant Registrar</b></p>
</div>
