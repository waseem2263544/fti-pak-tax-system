@extends('layouts.app')
@section('title', 'Edit Process')
@section('page-title', 'Edit Process')

@php
$template = $process->template ?? '';
$meta = $process->metadata ?? [];
$isAppeal = str_contains($template, 'appeal') || str_contains($template, 'stay');
$isTribunal = str_contains($template, 'tribunal');
$isStay = str_contains($template, 'stay');
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
@endphp

@section('content')
<form method="POST" action="{{ route('processes.update', $process) }}">
    @csrf @method('PUT')

    <!-- Header -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                @if($isStay) <i class="bi bi-shield-check" style="color: #8b9a00;"></i>
                @elseif($isTribunal) <i class="bi bi-bank2" style="color: #7c3aed;"></i>
                @elseif($isAppeal) <i class="bi bi-building" style="color: #3b82f6;"></i>
                @else <i class="bi bi-arrow-repeat" style="color: var(--accent);"></i>
                @endif
                <span style="font-weight: 700;">{{ $templateNames[$template] ?? 'Edit Process' }}</span>
            </div>
            @if($template)
            <span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">{{ $template }}</span>
            @endif
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $process->title) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Client</label>
                <select name="client_id" class="form-select" required>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ $process->client_id == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
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
            <div class="row">
                @if(str_starts_with($template, 'st-'))
                <div class="col-md-4 mb-3">
                    <label class="form-label">Type of Appeal</label>
                    <select name="type_of_appeal" class="form-select">
                        <option value="sales_tax" {{ ($meta['type_of_appeal'] ?? 'sales_tax') === 'sales_tax' ? 'selected' : '' }}>Sales Tax</option>
                        <option value="federal_excise" {{ ($meta['type_of_appeal'] ?? '') === 'federal_excise' ? 'selected' : '' }}>Federal Excise Duty</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                @else
                <div class="col-md-6 mb-3">
                @endif
                    <label class="form-label">Bench</label>
                    <input type="text" name="bench" class="form-control" value="{{ old('bench', $meta['bench'] ?? '') }}" placeholder="Peshawar Bench, Peshawar">
                </div>
                @if(str_starts_with($template, 'st-'))
                <div class="col-md-4 mb-3">
                @else
                <div class="col-md-6 mb-3">
                @endif
                    <label class="form-label">Tax Year / Tax Period</label>
                    <input type="text" name="tax_year" class="form-control" value="{{ old('tax_year', $meta['tax_year'] ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client Registration No. (NTN/CNIC)</label>
                    <input type="text" name="ntn_cnic" class="form-control" value="{{ old('ntn_cnic', $meta['ntn_cnic'] ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client Address</label>
                    <input type="text" name="appellant_address" class="form-control" value="{{ old('appellant_address', $meta['appellant_address'] ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client Phone</label>
                    <input type="text" name="appellant_phone" class="form-control" value="{{ old('appellant_phone', $meta['appellant_phone'] ?? $process->client->contact_no ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client Email</label>
                    <input type="text" name="appellant_email" class="form-control" value="{{ old('appellant_email', $meta['appellant_email'] ?? $process->client->email ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">CIR(A) Order No.</label>
                    <input type="text" name="cira_order_no" class="form-control" value="{{ old('cira_order_no', $meta['cira_order_no'] ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">CIR(A) Order Date</label>
                    <input type="date" name="cira_order_date" class="form-control" value="{{ old('cira_order_date', $meta['cira_order_date'] ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Assessment Order No.</label>
                    <input type="text" name="assessment_order_no" class="form-control" value="{{ old('assessment_order_no', $meta['assessment_order_no'] ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Assessment Order Date</label>
                    <input type="date" name="assessment_order_date" class="form-control" value="{{ old('assessment_order_date', $meta['assessment_order_date'] ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Respondent 1 (Assessing Officer)</label>
                    <input type="text" name="respondent_1" class="form-control" value="{{ old('respondent_1', $meta['respondent_1'] ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Respondent 2 (Commissioner)</label>
                    <input type="text" name="respondent_2" class="form-control" value="{{ old('respondent_2', $meta['respondent_2'] ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Recovery Notice No.</label>
                    <input type="text" name="recovery_notice_no" class="form-control" value="{{ old('recovery_notice_no', $meta['recovery_notice_no'] ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Recovery Notice Date</label>
                    <input type="date" name="recovery_notice_date" class="form-control" value="{{ old('recovery_notice_date', $meta['recovery_notice_date'] ?? '') }}">
                </div>
            </div>

            @if(str_starts_with($template, 'st-'))
            <!-- Appeal Memo (Form B) Details -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Section of Ordinance/Act</label>
                    <input type="text" name="section" class="form-control" value="{{ old('section', $meta['section'] ?? '') }}" placeholder="e.g. 11E, 122(1)">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Communication of Order</label>
                    <input type="date" name="communication_date" class="form-control" value="{{ old('communication_date', $meta['communication_date'] ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Filing of Appeal</label>
                    <input type="date" name="filing_date" class="form-control" value="{{ old('filing_date', $meta['filing_date'] ?? '') }}">
                    <small class="text-muted">Used as verification date on the memo</small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">IR Office (where assessment was made)</label>
                    <input type="text" name="ir_office_assessment" class="form-control" value="{{ old('ir_office_assessment', $meta['ir_office_assessment'] ?? '') }}" placeholder="e.g. Corporate Zone, RTO Peshawar">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">IR Office Location</label>
                    <input type="text" name="ir_office_location" class="form-control" value="{{ old('ir_office_location', $meta['ir_office_location'] ?? '') }}" placeholder="e.g. Regional Tax Office, Jamrud Road, Peshawar">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Verifier Name <small class="text-muted">(for companies/AOPs)</small></label>
                    <input type="text" name="verifier_name" class="form-control" value="{{ old('verifier_name', $meta['verifier_name'] ?? '') }}" placeholder="e.g. Zakir Khan">
                    <small class="text-muted">For individuals (13-digit CNIC), appellant name + CNIC are used automatically.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Verifier Designation <small class="text-muted">(for companies/AOPs)</small></label>
                    <input type="text" name="verifier_designation" class="form-control" value="{{ old('verifier_designation', $meta['verifier_designation'] ?? '') }}" placeholder="e.g. Director, Partner">
                </div>
            </div>
            @endif
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Intimation Ref No.</label>
                    <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no', $meta['reference_no'] ?? '') }}">
                </div>
            </div>

            @if($isStay)
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Demand Amount (PKR)</label>
                    <input type="number" name="demand_amount" class="form-control" value="{{ old('demand_amount', $meta['demand_amount'] ?? '') }}" step="0.01" oninput="calcBalance()">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Amount Already Paid (PKR)</label>
                    <input type="number" name="amount_paid" class="form-control" value="{{ old('amount_paid', $meta['amount_paid'] ?? '0') }}" step="0.01" oninput="calcBalance()">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Balance Demand (PKR)</label>
                    <input type="number" name="balance_demand" class="form-control" value="{{ old('balance_demand', $meta['balance_demand'] ?? '') }}" readonly style="background: #f8f9fb;">
                </div>
            </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Grounds of Appeal</label>
                <div id="grounds-editor" contenteditable="true" class="form-control" style="min-height: 150px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7;">{!! old('grounds', $meta['grounds'] ?? '') !!}</div>
                <input type="hidden" name="grounds" id="grounds-hidden">
                <small class="text-muted">You can paste formatted text here. Formatting will be preserved.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Prayer / Relief Sought</label>
                <div id="prayer-editor" contenteditable="true" class="form-control" style="min-height: 80px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7;">{!! old('prayer', $meta['prayer'] ?? '') !!}</div>
                <input type="hidden" name="prayer" id="prayer-hidden">
            </div>

            @if($isStay)
            <div class="mb-3">
                <label class="form-label">Reasons for Stay</label>
                <div id="stay-editor" contenteditable="true" class="form-control" style="min-height: 80px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7;">{!! old('stay_reasons', $meta['stay_reasons'] ?? '') !!}</div>
                <input type="hidden" name="stay_reasons" id="stay-hidden">
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-accent">Update Process</button>
        <a href="{{ route('processes.show', $process) }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
@if($isStay)
function calcBalance() {
    var d = parseFloat(document.querySelector('input[name="demand_amount"]').value) || 0;
    var p = parseFloat(document.querySelector('input[name="amount_paid"]').value) || 0;
    document.querySelector('input[name="balance_demand"]').value = (d - p).toFixed(2);
}
@endif

// Sync contenteditable editors to hidden inputs on submit
document.querySelector('form').addEventListener('submit', function() {
    var g = document.getElementById('grounds-editor');
    var p = document.getElementById('prayer-editor');
    var s = document.getElementById('stay-editor');
    if (g) document.getElementById('grounds-hidden').value = g.innerHTML;
    if (p) document.getElementById('prayer-hidden').value = p.innerHTML;
    if (s) document.getElementById('stay-hidden').value = s.innerHTML;
});
</script>
@endsection
