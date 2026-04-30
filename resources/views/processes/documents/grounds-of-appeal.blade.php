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
    <p style="font-size: 16pt; font-weight: bold; letter-spacing: 1pt; margin: 0;">{{ strtoupper($clientName) }}</p>
    <p style="font-size: 11pt; margin: 12pt 0 0; letter-spacing: 2pt;">GROUNDS OF APPEAL</p>
    <p style="font-size: 11pt; margin: 4pt 0 0; font-style: italic;">Against Assessment Order No. {{ $assessmentOrderNo }}</p>
    <hr style="border: none; border-top: 0.75pt solid #000; width: 50%; margin: 20pt auto 0;">
</div>

@php
$sections = [
    ['title' => 'Brief Facts of the Case', 'content' => $meta['stay_reasons'] ?? ''],
    ['title' => 'Grounds of Appeal',       'content' => $grounds],
    ['title' => 'Prayer',                  'content' => $meta['prayer'] ?? ''],
];
@endphp

@foreach($sections as $sec)
    @if(!empty(trim(strip_tags($sec['content']))))
    <div style="margin-top: 26pt;">
        <p style="font-size: 12pt; font-weight: bold; letter-spacing: 1.5pt; text-transform: uppercase; margin: 0 0 10pt; padding-bottom: 4pt; border-bottom: 0.5pt solid #000;">{{ $sec['title'] }}</p>
        <div class="rich-content" style="text-align: justify; font-size: 12pt; line-height: 1.5;">
            {!! $sec['content'] !!}
        </div>
    </div>
    @endif
@endforeach

{{-- ── Signature block ─────────────────────────────────────────── --}}
<div style="margin-top: 60pt; padding-top: 10pt; max-width: 260pt; margin-left: auto; border-top: 0.5pt solid #000; text-align: right;">
    <p style="margin: 0; font-weight: bold;">Appellant</p>
    <p style="margin: 4pt 0 0; font-weight: bold; letter-spacing: 0.5pt;">{{ strtoupper($clientName) }}</p>
    <p style="margin: 16pt 0 4pt; font-style: italic;">Through</p>
    <p style="margin: 0; font-weight: bold;">Waseem Ur Rehman</p>
    <p style="margin: 0; font-style: italic; font-size: 11pt;">Director — Fair Tax (Pvt) Ltd</p>
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
