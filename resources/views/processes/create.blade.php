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
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Appellant Name <span class="text-danger">*</span></label>
                    <input type="text" name="appellant_name" class="form-control" value="{{ old('appellant_name') }}" required placeholder="Full name of the appellant">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">NTN / CNIC</label>
                    <input type="text" name="ntn_cnic" class="form-control" value="{{ old('ntn_cnic') }}" placeholder="NTN or CNIC number">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="appellant_address" class="form-control" value="{{ old('appellant_address') }}" placeholder="Appellant's address">
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tax Year</label>
                    <input type="text" name="tax_year" class="form-control" value="{{ old('tax_year') }}" placeholder="e.g. 2025-2026">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control" value="{{ old('section') }}" placeholder="e.g. 122(5A)">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Assessment Order No.</label>
                    <input type="text" name="assessment_order_no" class="form-control" value="{{ old('assessment_order_no') }}" placeholder="Order number">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Order Date</label>
                    <input type="date" name="order_date" class="form-control" value="{{ old('order_date') }}">
                </div>
            </div>

            @if($isTribunal)
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">CIR(A) Order No.</label>
                    <input type="text" name="cira_order_no" class="form-control" value="{{ old('cira_order_no') }}" placeholder="CIR Appeals order number">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">CIR(A) Order Date</label>
                    <input type="date" name="cira_order_date" class="form-control" value="{{ old('cira_order_date') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Appeal No. at CIR(A)</label>
                    <input type="text" name="cira_appeal_no" class="form-control" value="{{ old('cira_appeal_no') }}" placeholder="Previous appeal number">
                </div>
            </div>
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Respondent Name</label>
                    <input type="text" name="respondent_name" class="form-control" value="{{ old('respondent_name') }}" placeholder="e.g. Commissioner Inland Revenue">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Respondent Designation & Address</label>
                    <input type="text" name="respondent_address" class="form-control" value="{{ old('respondent_address') }}" placeholder="Office address">
                </div>
            </div>

            @if($isStay)
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
                    <input type="number" name="balance_demand" class="form-control" value="{{ old('balance_demand') }}" step="0.01" placeholder="Auto-calculated" readonly style="background: #f8f9fb;">
                </div>
            </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Grounds of Appeal</label>
                <textarea name="grounds" class="form-control" rows="4" placeholder="List the grounds of appeal...">{{ old('grounds') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Prayer / Relief Sought</label>
                <textarea name="prayer" class="form-control" rows="3" placeholder="State the relief sought from the appellate authority...">{{ old('prayer') }}</textarea>
            </div>

            @if($isStay)
            <div class="mb-3">
                <label class="form-label">Reasons for Stay</label>
                <textarea name="stay_reasons" class="form-control" rows="3" placeholder="Why should the demand be stayed pending appeal...">{{ old('stay_reasons') }}</textarea>
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
</script>
@endsection
