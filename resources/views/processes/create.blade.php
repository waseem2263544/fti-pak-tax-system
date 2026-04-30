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
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $templateTitle) }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Client <span class="text-danger">*</span></label>
                    <select name="client_id" class="form-select" required>
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Service</label>
                    <select name="service_id" class="form-select" required>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>{{ $service->display_name }}</option>
                        @endforeach
                    </select>
                </div>
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
                    <label class="form-label">Tax Year <span class="text-danger">*</span></label>
                    <input type="text" name="tax_year" class="form-control" value="{{ old('tax_year') }}" required placeholder="e.g. 2025-2026">
                </div>
            </div>

            <!-- Client Details -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                    <input type="text" name="appellant_name" class="form-control" value="{{ old('appellant_name') }}" required placeholder="Full name as per NTN">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Client Registration No. (NTN/CNIC)</label>
                    <input type="text" name="ntn_cnic" class="form-control" value="{{ old('ntn_cnic') }}" placeholder="NTN or CNIC number">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Client Address</label>
                    <input type="text" name="appellant_address" class="form-control" value="{{ old('appellant_address') }}" placeholder="Registered address">
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

            <!-- Intimation Reference -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Intimation No.</label>
                    <input type="text" name="intimation_no" class="form-control" value="{{ old('intimation_no') }}" placeholder="Intimation number">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Reference No.</label>
                    <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no') }}" placeholder="Reference number">
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

            <!-- Grounds of Appeal (rich text paste) -->
            <div class="mb-3">
                <label class="form-label">Grounds of Appeal</label>
                <div id="grounds-editor" contenteditable="true" class="form-control" style="min-height: 150px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7;">{!! old('grounds') !!}</div>
                <input type="hidden" name="grounds" id="grounds-hidden">
                <small class="text-muted">You can paste formatted text here (from Word, etc.). Formatting will be preserved.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Prayer / Relief Sought</label>
                <div id="prayer-editor" contenteditable="true" class="form-control" style="min-height: 80px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7;">{!! old('prayer') !!}</div>
                <input type="hidden" name="prayer" id="prayer-hidden">
            </div>

            @if($isStay)
            <div class="mb-3">
                <label class="form-label">Reasons for Stay</label>
                <div id="stay-editor" contenteditable="true" class="form-control" style="min-height: 80px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; line-height: 1.7;">{!! old('stay_reasons') !!}</div>
                <input type="hidden" name="stay_reasons" id="stay-hidden">
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Process Settings -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Process Settings
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Stage</label>
                    <select name="stage" class="form-select">
                        <option value="intake" selected>Intake</option>
                        <option value="in_progress">In Progress</option>
                        <option value="review">Review</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Assigned To</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date', date('Y-m-d')) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description / Notes</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Create Process</button>
        <a href="{{ route('processes.index') }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
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

// Sync contenteditable editors to hidden inputs on submit
document.getElementById('invoice-form')?.addEventListener('submit', syncEditors);
document.querySelector('form')?.addEventListener('submit', syncEditors);

function syncEditors() {
    var groundsEditor = document.getElementById('grounds-editor');
    var prayerEditor = document.getElementById('prayer-editor');
    var stayEditor = document.getElementById('stay-editor');

    if (groundsEditor) document.getElementById('grounds-hidden').value = groundsEditor.innerHTML;
    if (prayerEditor) document.getElementById('prayer-hidden').value = prayerEditor.innerHTML;
    if (stayEditor) document.getElementById('stay-hidden').value = stayEditor.innerHTML;
}
</script>
@endsection
