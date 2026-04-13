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
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $process->title) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select" required>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ $process->client_id == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Service</label>
                    <select name="service_id" class="form-select" required>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ $process->service_id == $service->id ? 'selected' : '' }}>{{ $service->display_name }}</option>
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
                    <label class="form-label">Appellant Name</label>
                    <input type="text" name="appellant_name" class="form-control" value="{{ old('appellant_name', $meta['appellant_name'] ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">NTN / CNIC</label>
                    <input type="text" name="ntn_cnic" class="form-control" value="{{ old('ntn_cnic', $meta['ntn_cnic'] ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="appellant_address" class="form-control" value="{{ old('appellant_address', $meta['appellant_address'] ?? '') }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tax Year</label>
                    <input type="text" name="tax_year" class="form-control" value="{{ old('tax_year', $meta['tax_year'] ?? '') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control" value="{{ old('section', $meta['section'] ?? '') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Assessment Order No.</label>
                    <input type="text" name="assessment_order_no" class="form-control" value="{{ old('assessment_order_no', $meta['assessment_order_no'] ?? '') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Order Date</label>
                    <input type="date" name="order_date" class="form-control" value="{{ old('order_date', $meta['order_date'] ?? '') }}">
                </div>
            </div>

            @if($isTribunal)
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">CIR(A) Order No.</label>
                    <input type="text" name="cira_order_no" class="form-control" value="{{ old('cira_order_no', $meta['cira_order_no'] ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">CIR(A) Order Date</label>
                    <input type="date" name="cira_order_date" class="form-control" value="{{ old('cira_order_date', $meta['cira_order_date'] ?? '') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Appeal No. at CIR(A)</label>
                    <input type="text" name="cira_appeal_no" class="form-control" value="{{ old('cira_appeal_no', $meta['cira_appeal_no'] ?? '') }}">
                </div>
            </div>
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Respondent Name</label>
                    <input type="text" name="respondent_name" class="form-control" value="{{ old('respondent_name', $meta['respondent_name'] ?? '') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Respondent Designation & Address</label>
                    <input type="text" name="respondent_address" class="form-control" value="{{ old('respondent_address', $meta['respondent_address'] ?? '') }}">
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
                <textarea name="grounds" class="form-control" rows="4">{{ old('grounds', $meta['grounds'] ?? '') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Prayer / Relief Sought</label>
                <textarea name="prayer" class="form-control" rows="3">{{ old('prayer', $meta['prayer'] ?? '') }}</textarea>
            </div>

            @if($isStay)
            <div class="mb-3">
                <label class="form-label">Reasons for Stay</label>
                <textarea name="stay_reasons" class="form-control" rows="3">{{ old('stay_reasons', $meta['stay_reasons'] ?? '') }}</textarea>
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
                        <option value="intake" {{ $process->stage == 'intake' ? 'selected' : '' }}>Intake</option>
                        <option value="in_progress" {{ $process->stage == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="review" {{ $process->stage == 'review' ? 'selected' : '' }}>Review</option>
                        <option value="completed" {{ $process->stage == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Assigned To</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $process->assigned_to == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $process->start_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="{{ $process->due_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Completed</label>
                    <input type="date" name="completed_date" class="form-control" value="{{ $process->completed_date?->format('Y-m-d') }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description / Notes</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $process->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-accent">Update Process</button>
        <a href="{{ route('processes.show', $process) }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection

@if($isStay)
@section('scripts')
<script>
function calcBalance() {
    var d = parseFloat(document.querySelector('input[name="demand_amount"]').value) || 0;
    var p = parseFloat(document.querySelector('input[name="amount_paid"]').value) || 0;
    document.querySelector('input[name="balance_demand"]').value = (d - p).toFixed(2);
}
</script>
@endsection
@endif
