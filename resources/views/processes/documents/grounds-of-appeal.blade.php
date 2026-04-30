@php
$clientName = $meta['appellant_name'] ?? $process->client->name ?? '_______________';
$taxYear = trim($meta['tax_year'] ?? '');
$section = $meta['section'] ?? '122(1)/129';
$grounds = $meta['grounds'] ?? '';
$taxYearLine = $taxYear !== '' ? '<br>FOR THE TAX YEAR ' . e($taxYear) : '';
$assessmentOrderNo = $meta['assessment_order_no'] ?? '_______________';
$isStTribunalStay = ($process->template ?? '') === 'st-tribunal-stay';
@endphp

@if($isStTribunalStay)
{{-- ── Title block ─────────────────────────────────────────────── --}}
<div style="text-align: center; margin: 0 0 30pt;">
    <p style="font-size: 16pt; font-weight: bold; margin: 0;">{{ strtoupper($clientName) }}</p>
    <p style="font-size: 13pt; font-weight: bold; margin: 14pt 0 0;">GROUNDS OF APPEAL</p>
    <p style="font-size: 12pt; margin: 4pt 0 0;">Against Assessment Order No. {{ $assessmentOrderNo }}</p>
</div>

@php
$sections = [
    ['title' => 'BRIEF FACTS OF THE CASE', 'content' => $meta['stay_reasons'] ?? ''],
    ['title' => 'GROUNDS OF APPEAL',       'content' => $grounds],
    ['title' => 'PRAYER',                  'content' => $meta['prayer'] ?? ''],
];
@endphp

@foreach($sections as $sec)
    @if(!empty(trim(strip_tags($sec['content']))))
    <div style="margin-top: 28pt;">
        <p style="font-size: 13pt; font-weight: bold; text-decoration: underline; margin: 0 0 12pt;">{{ $sec['title'] }}</p>
        <div class="rich-content" style="text-align: justify; font-size: 12pt; line-height: 1.5;">
            {!! $sec['content'] !!}
        </div>
    </div>
    @endif
@endforeach

{{-- ── Signature block ─────────────────────────────────────────── --}}
<div style="margin-top: 56pt; text-align: right;">
    <p style="margin: 0; font-weight: bold;">Appellant</p>
    <p style="margin: 4pt 0 0; font-weight: bold;">{{ strtoupper($clientName) }}</p>
    <p style="margin: 14pt 0 4pt;">Through</p>
    <p style="margin: 0; font-weight: bold;">Waseem Ur Rehman</p>
    <p style="margin: 0;">Director - Fair Tax (Pvt) Ltd</p>
</div>
@else
<h1>{{ strtoupper($clientName) }}</h1>

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

<div class="signature right" style="margin-top: 48pt;">
    <p><b>Appellant</b></p>
    <p><b>{{ strtoupper($clientName) }}</b></p>
    <p style="margin-top: 12pt;">Through</p>
    <p><b>Waseem Ur Rehman</b><br>
    (Director - Fair Tax (Pvt) Ltd)</p>
</div>
@endif
