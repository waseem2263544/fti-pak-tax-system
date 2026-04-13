@extends('layouts.app')
@section('title', $process->title)
@section('page-title', 'Process Details')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h4 style="font-weight: 700; color: var(--primary); margin: 0;">{{ $process->title }}</h4>
                <p class="mt-1 mb-0" style="color: #9ca3af; font-size: 0.85rem;">{{ $process->client->name }} &middot; {{ $process->service->display_name }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('processes.edit', $process) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
                <form action="{{ route('processes.destroy', $process) }}" method="POST" onsubmit="return confirm('Delete this process?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>

        <!-- Stage Progress -->
        <div class="d-flex gap-2 mb-4">
            @foreach(['intake' => 'Intake', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed'] as $key => $label)
            <div style="flex: 1; padding: 12px; border-radius: 10px; text-align: center; font-size: 0.8rem; font-weight: 600;
                background: {{ $process->stage == $key ? 'var(--accent)' : '#f3f4f6' }};
                color: {{ $process->stage == $key ? 'var(--primary)' : '#9ca3af' }};">
                {{ $label }}
            </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-md-3 mb-3"><strong style="font-size: 0.78rem; color: #9ca3af;">ASSIGNED TO</strong><br>{{ $process->assignedTo->name ?? 'Unassigned' }}</div>
            <div class="col-md-3 mb-3"><strong style="font-size: 0.78rem; color: #9ca3af;">START DATE</strong><br>{{ $process->start_date?->format('M d, Y') ?? '-' }}</div>
            <div class="col-md-3 mb-3"><strong style="font-size: 0.78rem; color: #9ca3af;">DUE DATE</strong><br>{{ $process->due_date?->format('M d, Y') ?? '-' }}</div>
            <div class="col-md-3 mb-3"><strong style="font-size: 0.78rem; color: #9ca3af;">COMPLETED</strong><br>{{ $process->completed_date?->format('M d, Y') ?? '-' }}</div>
        </div>

        @if($process->description)
        <div class="mb-3"><strong style="font-size: 0.78rem; color: #9ca3af;">DESCRIPTION</strong><p class="mt-1">{{ $process->description }}</p></div>
        @endif
    </div>
</div>

@php $meta = $process->metadata ?? []; @endphp
@if(!empty($meta))
<!-- Case Details -->
<div class="card mt-4">
    <div class="card-header d-flex align-items-center gap-2">
        <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
        Case Details
    </div>
    <div class="card-body" style="padding: 24px;">
        <div class="row">
            @foreach([
                'appellant_name' => 'Appellant Name',
                'ntn_cnic' => 'NTN / CNIC',
                'appellant_address' => 'Address',
                'tax_year' => 'Tax Year',
                'section' => 'Section',
                'assessment_order_no' => 'Assessment Order No.',
                'order_date' => 'Order Date',
                'cira_order_no' => 'CIR(A) Order No.',
                'cira_order_date' => 'CIR(A) Order Date',
                'cira_appeal_no' => 'Appeal No. at CIR(A)',
                'respondent_name' => 'Respondent',
                'respondent_address' => 'Respondent Address',
                'demand_amount' => 'Demand Amount',
                'amount_paid' => 'Amount Paid',
                'balance_demand' => 'Balance Demand',
            ] as $key => $label)
                @if(!empty($meta[$key]))
                <div class="col-md-3 mb-3">
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">{{ $label }}</div>
                    <div style="color: var(--primary); font-weight: 500;">
                        @if(in_array($key, ['demand_amount', 'amount_paid', 'balance_demand']))
                            PKR {{ number_format($meta[$key], 2) }}
                        @else
                            {{ $meta[$key] }}
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
        </div>

        @if(!empty($meta['grounds']))
        <div class="mb-3">
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Grounds of Appeal</div>
            <p style="margin: 4px 0 0; color: #4b5563; white-space: pre-line;">{{ $meta['grounds'] }}</p>
        </div>
        @endif

        @if(!empty($meta['prayer']))
        <div class="mb-3">
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Prayer / Relief Sought</div>
            <p style="margin: 4px 0 0; color: #4b5563; white-space: pre-line;">{{ $meta['prayer'] }}</p>
        </div>
        @endif

        @if(!empty($meta['stay_reasons']))
        <div class="mb-3">
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Reasons for Stay</div>
            <p style="margin: 4px 0 0; color: #4b5563; white-space: pre-line;">{{ $meta['stay_reasons'] }}</p>
        </div>
        @endif
    </div>
</div>
@endif

<a href="{{ route('processes.index') }}" class="btn btn-outline-primary mt-3"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection
