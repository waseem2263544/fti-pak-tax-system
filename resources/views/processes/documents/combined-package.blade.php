@php
$sections = [
    1 => ['title' => 'INDEX',              'view' => 'processes.documents.index-page',         'fileMeta' => null],
    2 => ['title' => 'APPEAL MEMO',        'view' => 'processes.documents.appeal-memo',        'fileMeta' => null],
    3 => ['title' => 'STAY APPLICATION',   'view' => 'processes.documents.stay-application',   'fileMeta' => null],
    4 => ['title' => 'GROUNDS OF APPEAL',  'view' => 'processes.documents.grounds-of-appeal',  'fileMeta' => null],
    5 => ['title' => 'ORDER IN APPEAL',    'view' => null, 'fileMeta' => 'order_in_appeal_file'],
    6 => ['title' => 'ORDER IN ORIGINAL',  'view' => null, 'fileMeta' => 'order_in_original_file'],
    7 => ['title' => 'RECOVERY NOTICE',    'view' => null, 'fileMeta' => 'recovery_notice_file'],
    8 => ['title' => 'INTIMATION LETTER',  'view' => 'processes.documents.intimation',         'fileMeta' => null],
    9 => ['title' => 'POWER OF ATTORNEY',  'view' => 'processes.documents.power-of-attorney',  'fileMeta' => null],
    10 => ['title' => 'AFFIDAVIT',         'view' => 'processes.documents.affidavit',          'fileMeta' => null],
];
@endphp

<style>
.cp-page-header {
    text-align: center;
    font-size: 9pt;
    color: #888;
    border-bottom: 0.5pt solid #ccc;
    padding-bottom: 4pt;
    margin-bottom: 16pt;
    letter-spacing: 1pt;
    text-transform: uppercase;
}
.cp-section { page-break-before: always; }
.cp-section:first-of-type { page-break-before: auto; }
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

@foreach($sections as $pageNo => $section)
<div class="cp-section">
    <p class="cp-page-header">Page {{ $pageNo }} of {{ count($sections) }} &nbsp;&middot;&nbsp; {{ $section['title'] }}</p>

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
                <p style="font-size: 9pt; margin-top: 18pt;"><i>Print this attached file separately and insert it as page {{ $pageNo }} of the bound package.</i></p>
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
