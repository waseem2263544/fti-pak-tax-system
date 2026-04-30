@php
// Cumulative page numbering with multi-page attachment support
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

// pageNo is null for sections that should not be page-numbered (e.g. INDEX)
$sections = [
    ['title' => 'INDEX',              'view' => 'processes.documents.index-page',         'fileMeta' => null,                       'pageNo' => null,             'pages' => 1],
    ['title' => 'APPEAL MEMO',        'view' => 'processes.documents.appeal-memo',        'fileMeta' => null,                       'pageNo' => $pAppealMemo,     'pages' => 1],
    ['title' => 'STAY APPLICATION',   'view' => 'processes.documents.stay-application',   'fileMeta' => null,                       'pageNo' => $pStayApp,        'pages' => 1],
    ['title' => 'GROUNDS OF APPEAL',  'view' => 'processes.documents.grounds-of-appeal',  'fileMeta' => null,                       'pageNo' => $pGrounds,        'pages' => 1],
    ['title' => 'ORDER IN APPEAL',    'view' => null,                                      'fileMeta' => 'order_in_appeal_file',     'pageNo' => $pOrderInAppeal,   'pages' => $orderInAppealPages],
    ['title' => 'ORDER IN ORIGINAL',  'view' => null,                                      'fileMeta' => 'order_in_original_file',   'pageNo' => $pOrderInOriginal, 'pages' => $orderInOriginalPages],
    ['title' => 'RECOVERY NOTICE',    'view' => null,                                      'fileMeta' => 'recovery_notice_file',     'pageNo' => $pRecoveryNotice,  'pages' => $recoveryNoticePages],
    ['title' => 'INTIMATION LETTER',  'view' => 'processes.documents.intimation',         'fileMeta' => null,                       'pageNo' => $pIntimation,     'pages' => 1],
    ['title' => 'POWER OF ATTORNEY',  'view' => 'processes.documents.power-of-attorney',  'fileMeta' => null,                       'pageNo' => $pPOA,            'pages' => 1],
    ['title' => 'AFFIDAVIT',          'view' => 'processes.documents.affidavit',          'fileMeta' => null,                       'pageNo' => $pAffidavit,      'pages' => 1],
];
@endphp

<style>
.cp-section { position: relative; page-break-before: always; padding-top: 6pt; }
.cp-section:first-of-type { page-break-before: auto; }
.cp-page-no {
    position: absolute;
    top: -4pt;
    right: -4pt;
    font-size: 26pt;
    font-weight: 900;
    color: #000;
    line-height: 1;
    z-index: 10;
    letter-spacing: -1pt;
}
/* On-screen separator between sections (hidden when printing - real page-break takes over) */
.cp-separator {
    height: 26pt;
    margin: 18pt 0;
    background: repeating-linear-gradient(
        45deg,
        #d1d5db 0,
        #d1d5db 4pt,
        transparent 4pt,
        transparent 12pt
    );
    border-top: 2pt solid #303a50;
    border-bottom: 2pt solid #303a50;
    text-align: center;
    color: #6b7280;
    font-size: 9pt;
    line-height: 26pt;
    text-transform: uppercase;
    letter-spacing: 2pt;
}
@media print { .cp-separator { display: none; } }
.cp-attachment-img { max-width: 100%; max-height: 9in; display: block; margin: 0 auto; }
.cp-placeholder {
    border: 2pt dashed #aaa;
    padding: 60pt 30pt;
    text-align: center;
    margin: 24pt 0;
    color: #666;
}
.cp-placeholder h2 { margin-top: 0; color: #303a50; }
.cp-placeholder p { text-align: center; }
.cp-placeholder a { color: #2A8AB8; }
@media print {
    .cp-placeholder { border-color: #303a50; color: #000; }
}
</style>

@foreach($sections as $idx => $section)
@if($idx > 0)
<div class="cp-separator">— End of {{ $sections[$idx - 1]['title'] }} &nbsp;·&nbsp; Next: {{ $section['title'] }} —</div>
@endif
<div class="cp-section">
    @if($section['pageNo'])
    <div class="cp-page-no">{{ $section['pages'] > 1 ? $section['pageNo'] . '-' . ($section['pageNo'] + $section['pages'] - 1) : $section['pageNo'] }}</div>
    @endif

    @if($section['view'])
        @include($section['view'])
    @elseif(!empty($meta[$section['fileMeta']]))
        @php
            $filePath = $meta[$section['fileMeta']];
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        @endphp
        @if($isImage)
            <img src="{{ asset($filePath) }}" alt="{{ $section['title'] }}" class="cp-attachment-img">
        @else
            <div class="cp-placeholder">
                <h2>{{ $section['title'] }}</h2>
                <p>An attachment is uploaded for this section.</p>
                <p><a href="{{ asset($filePath) }}" target="_blank">Open / Download attached file</a></p>
                <p style="font-size: 9pt; margin-top: 18pt;"><i>Print this attached file separately and insert it as page {{ $section['pageNo'] }} of the bound package.</i></p>
            </div>
        @endif
    @else
        <div class="cp-placeholder">
            <h2>{{ $section['title'] }}</h2>
            <p>No attachment uploaded yet.</p>
            <p style="font-size: 9pt; margin-top: 12pt;"><i>Upload via the process edit form to include this in the package.</i></p>
        </div>
    @endif
</div>
@endforeach
