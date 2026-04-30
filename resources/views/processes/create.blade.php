@extends('layouts.app')
@section('title', 'New Process')
@section('page-title', 'Processes')

@php
$template = request('template', '');
$templateNames = [
    'it-commissioner-appeal' => 'Income Tax Appeal - Commissioner Appeals',
    'it-tribunal-appeal' => 'Income Tax Appeal - ATIR',
    'it-tribunal-stay' => 'Income Tax Stay Application - ATIR',
    'it-commissioner-stay' => 'Income Tax Stay Application - CIR(A)',
    'st-commissioner-appeal' => 'Sales Tax/FED Appeal - Commissioner Appeals',
    'st-tribunal-appeal' => 'Sales Tax/FED Appeal - ATIR',
    'st-tribunal-stay' => 'Sales Tax/FED Stay Application - ATIR',
    'st-commissioner-stay' => 'Sales Tax/FED Stay Application - CIR(A)',
];
$templateTitle = $templateNames[$template] ?? 'New Process';
$isAppeal = str_contains($template, 'appeal') || str_contains($template, 'stay');
$isIncomeTax = str_starts_with($template, 'it-');
$isTribunal = str_contains($template, 'tribunal');
$isStay = str_contains($template, 'stay');
@endphp

@section('content')
<!-- Breadcrumb -->
<div class="d-flex align-items-center gap-2 mb-4" style="font-size: 0.85rem;">
    <a href="{{ route('processes.index') }}" style="color: #9ca3af; text-decoration: none;"><i class="bi bi-house me-1"></i>Processes</a>
    @if($isAppeal)
    <span style="color: #d1d5db;"><i class="bi bi-chevron-right"></i></span>
    <a href="{{ route('processes.index', ['step' => 'appeal']) }}" style="color: #9ca3af; text-decoration: none;">Filing of Appeal</a>
    <span style="color: #d1d5db;"><i class="bi bi-chevron-right"></i></span>
    <a href="{{ route('processes.index', ['step' => $isIncomeTax ? 'appeal-income-tax' : 'appeal-sales-tax']) }}" style="color: #9ca3af; text-decoration: none;">{{ $isIncomeTax ? 'Income Tax' : 'Sales Tax/FED' }}</a>
    @endif
    <span style="color: #d1d5db;"><i class="bi bi-chevron-right"></i></span>
    <span style="color: var(--primary); font-weight: 600;">{{ $templateTitle }}</span>
</div>

<form method="POST" action="{{ route('processes.store') }}">
    @csrf
    <input type="hidden" name="template" value="{{ $template }}">

    <!-- Header -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            @if($isStay)
                <i class="bi bi-shield-check" style="color: #8b9a00;"></i>
            @elseif($isTribunal)
                <i class="bi bi-bank2" style="color: #7c3aed;"></i>
            @else
                <i class="bi bi-building" style="color: #3b82f6;"></i>
            @endif
            <span style="font-weight: 700;">{{ $templateTitle }}</span>
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $templateTitle) }}" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Client <span class="text-danger">*</span></label>
                <select name="client_id" class="form-select" required>
                    <option value="">Select Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if($isAppeal)
    <!-- Case Details -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Case Details
        </div>
        <div class="card-body" style="padding: 24px;">
            <!-- Bench & Tax Year -->
            <div class="row">
                @if(str_starts_with($template, 'st-'))
                <div class="col-md-4 mb-3">
                    <label class="form-label">Type of Appeal <span class="text-danger">*</span></label>
                    <select name="type_of_appeal" class="form-select" required>
                        <option value="sales_tax" {{ old('type_of_appeal') === 'sales_tax' ? 'selected' : '' }}>Sales Tax</option>
                        <option value="federal_excise" {{ old('type_of_appeal') === 'federal_excise' ? 'selected' : '' }}>Federal Excise Duty</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                @else
                <div class="col-md-6 mb-3">
                @endif
                    <label class="form-label">Bench <span class="text-danger">*</span></label>
                    <input type="text" name="bench" class="form-control" value="{{ old('bench') }}" required placeholder="Peshawar Bench, Peshawar">
                </div>
                @if(str_starts_with($template, 'st-'))
                <div class="col-md-4 mb-3">
                @else
                <div class="col-md-6 mb-3">
                @endif
                    <label class="form-label">Tax Year / Tax Period <span class="text-danger">*</span></label>
                    <input type="text" name="tax_year" class="form-control" value="{{ old('tax_year') }}" required placeholder="e.g. 2025-2026">
                </div>
            </div>

            <!-- Client Details -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client Registration No. (NTN/CNIC)</label>
                    <input type="text" name="ntn_cnic" class="form-control" value="{{ old('ntn_cnic') }}" placeholder="NTN or CNIC number">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client Address</label>
                    <input type="text" name="appellant_address" class="form-control" value="{{ old('appellant_address') }}" placeholder="Registered address">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client Phone</label>
                    <input type="text" name="appellant_phone" class="form-control" value="{{ old('appellant_phone') }}" placeholder="e.g. 0314-9444785">
                    <small class="text-muted">Leave blank to use the client's saved phone</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client Email</label>
                    <input type="text" name="appellant_email" class="form-control" value="{{ old('appellant_email') }}" placeholder="e.g. info@example.com">
                    <small class="text-muted">Leave blank to use the client's saved email</small>
                </div>
            </div>

            <!-- CIR(A) Order -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">CIR(A) Order No.</label>
                    <input type="text" name="cira_order_no" class="form-control" value="{{ old('cira_order_no') }}" placeholder="Commissioner Appeals order number">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">CIR(A) Order Date</label>
                    <input type="date" name="cira_order_date" class="form-control" value="{{ old('cira_order_date') }}">
                </div>
            </div>

            <!-- Assessment Order -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Assessment Order No.</label>
                    <input type="text" name="assessment_order_no" class="form-control" value="{{ old('assessment_order_no') }}" placeholder="Assessment order number">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Assessment Order Date</label>
                    <input type="date" name="assessment_order_date" class="form-control" value="{{ old('assessment_order_date') }}">
                </div>
            </div>

            <!-- Respondents -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Respondent 1 (Assessing Officer)</label>
                    <input type="text" name="respondent_1" class="form-control" value="{{ old('respondent_1') }}" placeholder="Name & Designation of Assessing Officer">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Respondent 2 (Commissioner)</label>
                    <input type="text" name="respondent_2" class="form-control" value="{{ old('respondent_2') }}" placeholder="Name & Designation of Commissioner">
                </div>
            </div>

            <!-- Recovery Notice -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Recovery Notice No.</label>
                    <input type="text" name="recovery_notice_no" class="form-control" value="{{ old('recovery_notice_no') }}" placeholder="Recovery notice number">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Recovery Notice Date</label>
                    <input type="date" name="recovery_notice_date" class="form-control" value="{{ old('recovery_notice_date') }}">
                </div>
            </div>

            @if(str_starts_with($template, 'st-'))
            <!-- Appeal Memo (Form B) Details -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Section of Ordinance/Act</label>
                    <input type="text" name="section" class="form-control" value="{{ old('section') }}" placeholder="e.g. 11E, 122(1)">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Communication of Order</label>
                    <input type="date" name="communication_date" class="form-control" value="{{ old('communication_date') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Filing of Appeal</label>
                    <input type="date" name="filing_date" class="form-control" value="{{ old('filing_date') }}">
                    <small class="text-muted">Used as verification date on the memo</small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">IR Office (where assessment was made)</label>
                    <input type="text" name="ir_office_assessment" class="form-control" value="{{ old('ir_office_assessment') }}" placeholder="e.g. Corporate Zone, RTO Peshawar">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">IR Office Location</label>
                    <input type="text" name="ir_office_location" class="form-control" value="{{ old('ir_office_location') }}" placeholder="e.g. Regional Tax Office, Jamrud Road, Peshawar">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Verifier Name <small class="text-muted">(for companies/AOPs)</small></label>
                    <input type="text" name="verifier_name" class="form-control" value="{{ old('verifier_name') }}" placeholder="e.g. Zakir Khan">
                    <small class="text-muted">For individuals (13-digit CNIC), appellant name + CNIC are used automatically.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Verifier Designation <small class="text-muted">(for companies/AOPs)</small></label>
                    <input type="text" name="verifier_designation" class="form-control" value="{{ old('verifier_designation') }}" placeholder="e.g. Director, Partner">
                </div>
            </div>
            @endif

            <!-- Intimation Reference -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Intimation Ref No.</label>
                    <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no') }}" placeholder="Reference shown on the intimation letter">
                </div>
            </div>

            @if($isStay)
            <!-- Demand Details -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Demand Amount (PKR)</label>
                    <input type="number" name="demand_amount" class="form-control" value="{{ old('demand_amount') }}" step="0.01" placeholder="Total demand raised">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Amount Already Paid (PKR)</label>
                    <input type="number" name="amount_paid" class="form-control" value="{{ old('amount_paid', '0') }}" step="0.01">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Balance Demand (PKR)</label>
                    <input type="number" name="balance_demand" class="form-control" value="{{ old('balance_demand') }}" step="0.01" readonly style="background: #f8f9fb;">
                </div>
            </div>
            @endif

            @if($isStay)
            <div class="mb-3">
                <label class="form-label">Brief Facts of the Case</label>
                <div id="stay-editor" contenteditable="true" class="form-control" style="min-height: 100px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7;">{!! old('stay_reasons') !!}</div>
                <input type="hidden" name="stay_reasons" id="stay-hidden">
            </div>
            @endif

            <!-- Grounds of Appeal (rich text paste) -->
            <div class="mb-3">
                <label class="form-label">Grounds of Appeal</label>
                <div id="grounds-editor" contenteditable="true" class="form-control" style="min-height: 150px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7;">{!! old('grounds') !!}</div>
                <input type="hidden" name="grounds" id="grounds-hidden">
                <small class="text-muted">You can paste formatted text here (from Word, etc.) or use the toolbar to add bullets/numbers.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Prayer / Relief Sought</label>
                <div id="prayer-editor" contenteditable="true" class="form-control" style="min-height: 80px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7;">{!! old('prayer') !!}</div>
                <input type="hidden" name="prayer" id="prayer-hidden">
            </div>
        </div>
    </div>
    @endif

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Create Process</button>
        <a href="{{ route('processes.index') }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
@if($template === 'st-tribunal-stay')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<style>
.ql-editor { min-height: 160px; max-height: 420px; font-family: inherit; line-height: 1.7; font-size: 14px; }
.ql-toolbar.ql-snow { border-top-left-radius: 6px; border-top-right-radius: 6px; }
.ql-container.ql-snow { border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; }
</style>
@else
<style>
.editor-toolbar { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; border: 1px solid #ced4da; border-bottom: none; border-radius: 6px 6px 0 0; padding: 6px 8px; background: #f8f9fb; }
.ed-btn { background: #fff; border: 1px solid #e2e6ea; border-radius: 4px; font-size: 0.85rem; padding: 3px 9px; cursor: pointer; color: #303a50; line-height: 1.2; min-width: 30px; }
.ed-btn:hover { background: #eef0f3; }
.ed-sep { display: inline-block; border-left: 1px solid #d1d5db; height: 18px; margin: 0 4px; }
[contenteditable="true"].form-control { border-top-left-radius: 0; border-top-right-radius: 0; }
[contenteditable="true"] ul { list-style: disc; padding-left: 2em; margin: 0.5em 0; }
[contenteditable="true"] ol { list-style: decimal; padding-left: 2em; margin: 0.5em 0; }
</style>
@endif
<script>
@if($template === 'st-tribunal-stay')
// Replace contenteditable editors with Quill (paste-sanitized, MS-Word-style behavior)
(function() {
    var qToolbar = [
        ['bold', 'italic', 'underline'],
        [{'list': 'ordered'}, {'list': 'bullet'}],
        [{'indent': '-1'}, {'indent': '+1'}],
        ['clean']
    ];
    ['stay', 'grounds', 'prayer'].forEach(function(name) {
        var ed = document.getElementById(name + '-editor');
        var hi = document.getElementById(name + '-hidden');
        if (!ed || !hi) return;
        var initial = ed.innerHTML;
        var box = document.createElement('div');
        box.id = name + '-quill';
        ed.parentNode.replaceChild(box, ed);
        var q = new Quill('#' + name + '-quill', {
            theme: 'snow',
            modules: { toolbar: qToolbar }
        });
        if (initial && initial.trim()) q.clipboard.dangerouslyPasteHTML(initial);
        var sync = function() { hi.value = q.root.innerHTML; };
        q.on('text-change', sync);
        sync();
    });
})();
@else
// Inject formatting toolbar above each rich-text editor (legacy contenteditable path)
(function() {
    var editors = document.querySelectorAll('[contenteditable="true"].form-control');
    if (!editors.length) return;
    var html = '<div class="editor-toolbar">' +
        '<button type="button" class="ed-btn" data-cmd="bold" title="Bold (Ctrl+B)"><b>B</b></button>' +
        '<button type="button" class="ed-btn" data-cmd="italic" title="Italic (Ctrl+I)"><i>I</i></button>' +
        '<button type="button" class="ed-btn" data-cmd="underline" title="Underline (Ctrl+U)"><u>U</u></button>' +
        '<span class="ed-sep"></span>' +
        '<button type="button" class="ed-btn" data-cmd="insertUnorderedList" title="Bullet list">&bull; List</button>' +
        '<button type="button" class="ed-btn" data-cmd="insertOrderedList" title="Numbered list">1. List</button>' +
        '<span class="ed-sep"></span>' +
        '<button type="button" class="ed-btn" data-cmd="outdent" title="Decrease indent">&larr;</button>' +
        '<button type="button" class="ed-btn" data-cmd="indent" title="Increase indent">&rarr;</button>' +
        '<span class="ed-sep"></span>' +
        '<button type="button" class="ed-btn" data-cmd="removeFormat" title="Clear formatting">&times;</button>' +
    '</div>';
    editors.forEach(function(editor) {
        var t = document.createElement('div');
        t.innerHTML = html;
        var bar = t.firstElementChild;
        editor.parentNode.insertBefore(bar, editor);
        bar.querySelectorAll('.ed-btn').forEach(function(btn) {
            btn.addEventListener('mousedown', function(e) {
                e.preventDefault();
                document.execCommand(btn.getAttribute('data-cmd'), false, null);
            });
        });
    });
})();

// Keep hidden inputs in lockstep with their contenteditable editors (legacy path)
function bindEditor(editorId, hiddenId) {
    var ed = document.getElementById(editorId);
    var hi = document.getElementById(hiddenId);
    if (!ed || !hi) return;
    var sync = function() { hi.value = ed.innerHTML; };
    sync();
    ed.addEventListener('input', sync);
    ed.addEventListener('blur', sync);
    ed.addEventListener('keyup', sync);
}
bindEditor('grounds-editor', 'grounds-hidden');
bindEditor('prayer-editor',  'prayer-hidden');
bindEditor('stay-editor',    'stay-hidden');

// Belt-and-braces: also sync on submit
document.querySelector('form')?.addEventListener('submit', function() {
    ['grounds', 'prayer', 'stay'].forEach(function(name) {
        var ed = document.getElementById(name + '-editor');
        var hi = document.getElementById(name + '-hidden');
        if (ed && hi) hi.value = ed.innerHTML;
    });
});
@endif

// Auto-calculate balance demand
var demandInput = document.querySelector('input[name="demand_amount"]');
var paidInput = document.querySelector('input[name="amount_paid"]');
var balanceInput = document.querySelector('input[name="balance_demand"]');
if (demandInput && paidInput && balanceInput) {
    function calcBalance() {
        var demand = parseFloat(demandInput.value) || 0;
        var paid = parseFloat(paidInput.value) || 0;
        balanceInput.value = (demand - paid).toFixed(2);
    }
    demandInput.addEventListener('input', calcBalance);
    paidInput.addEventListener('input', calcBalance);
}
</script>
@endsection
