@php
// pageNo is null for sections that should not be page-numbered (e.g. INDEX)
$sections = [
    ['title' => 'INDEX',              'view' => 'processes.documents.index-page',         'fileMeta' => null,                     'pageNo' => null],
    ['title' => 'APPEAL MEMO',        'view' => 'processes.documents.appeal-memo',        'fileMeta' => null,                     'pageNo' => 1],
    ['title' => 'STAY APPLICATION',   'view' => 'processes.documents.stay-application',   'fileMeta' => null,                     'pageNo' => 2],
    ['title' => 'GROUNDS OF APPEAL',  'view' => 'processes.documents.grounds-of-appeal',  'fileMeta' => null,                     'pageNo' => 3],
    ['title' => 'ORDER IN APPEAL',    'view' => null,                                      'fileMeta' => 'order_in_appeal_file',    'pageNo' => 4],
    ['title' => 'ORDER IN ORIGINAL',  'view' => null,                                      'fileMeta' => 'order_in_original_file',  'pageNo' => 5],
    ['title' => 'RECOVERY NOTICE',    'view' => null,                                      'fileMeta' => 'recovery_notice_file',    'pageNo' => 6],
    ['title' => 'INTIMATION LETTER',  'view' => 'processes.documents.intimation',         'fileMeta' => null,                     'pageNo' => 7],
    ['title' => 'POWER OF ATTORNEY',  'view' => 'processes.documents.power-of-attorney',  'fileMeta' => null,                     'pageNo' => 8],
    ['title' => 'AFFIDAVIT',          'view' => 'processes.documents.affidavit',          'fileMeta' => null,                     'pageNo' => 9],
];
@endphp

<style>
.cp-section { position: relative; page-break-before: always; }
.cp-section:first-of-type { page-break-before: auto; }
.cp-page-no {
    position: absolute;
    top: 0;
    right: 0;
    font-size: 11pt;
    font-weight: bold;
    color: #000;
    z-index: 10;
}
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

@foreach($sections as $section)
<div class="cp-section">
    @if($section['pageNo'])
    <div class="cp-page-no">{{ $section['pageNo'] }}</div>
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
