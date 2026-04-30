@php
$clientName = $meta['appellant_name'] ?? $process->client->name ?? '_______________';
$taxYear = trim($meta['tax_year'] ?? '');
$section = $meta['section'] ?? '122(1)/129';
$grounds = $meta['grounds'] ?? '';
$taxYearLine = $taxYear !== '' ? '<br>FOR THE TAX YEAR ' . e($taxYear) : '';
$assessmentOrderNo = $meta['assessment_order_no'] ?? '_______________';
$isStTribunalStay = ($process->template ?? '') === 'st-tribunal-stay';
@endphp

<h1>{{ strtoupper($clientName) }}</h1>

@if($isStTribunalStay)
<h2>GROUNDS OF APPEAL<br>AGAINST ASSESSMENT ORDER NO. {{ $assessmentOrderNo }}</h2>

@if(!empty($meta['stay_reasons']))
<div style="margin-top: 28pt;">
<h3 style="text-decoration: underline;">BRIEF FACTS OF THE CASE</h3>
<div class="rich-content" style="margin-top: 8pt; text-align: justify; line-height: 1.5;">
{!! $meta['stay_reasons'] !!}
</div>
</div>
@endif

<div style="margin-top: 28pt;">
<h3 style="text-decoration: underline;">GROUNDS OF APPEAL</h3>
<div class="rich-content" style="margin-top: 8pt; text-align: justify; line-height: 1.5;">
{!! $grounds !!}
</div>
</div>

@if(!empty($meta['prayer']))
<div style="margin-top: 28pt;">
<h3 style="text-decoration: underline;">PRAYER</h3>
<div class="rich-content" style="margin-top: 8pt; text-align: justify; line-height: 1.5;">
{!! $meta['prayer'] !!}
</div>
</div>
@endif
@else
<h2>GROUNDS OF APPEAL{!! $taxYearLine !!}<br>AGAINST THE ORDER U/S {{ $section }}</h2>

<div style="margin-top: 24pt; text-align: justify; line-height: 1.8;">
{!! $grounds !!}
</div>

@if(!empty($meta['prayer']))
<div style="margin-top: 24pt;">
<h3>PRAYER</h3>
<div style="text-align: justify; line-height: 1.8;">
{!! $meta['prayer'] !!}
</div>
</div>
@endif
@endif

<div class="signature right" style="margin-top: 48pt;">
    <p><b>Appellant</b></p>
    <p><b>{{ strtoupper($clientName) }}</b></p>
    <p style="margin-top: 12pt;">Through</p>
    <p><b>Waseem Ur Rehman</b><br>
    (Director - Fair Tax (Pvt) Ltd)</p>
</div>
