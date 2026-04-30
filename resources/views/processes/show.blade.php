@extends('layouts.app')
@section('title', $process->title)
@section('page-title', 'Process Details')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 style="font-weight: 700; color: var(--primary); margin: 0; flex: 1;">{{ $process->title }}</h4>
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
                'bench' => 'Bench',
                'tax_year' => 'Tax Year / Tax Period',
                'appellant_name' => 'Client Name',
                'ntn_cnic' => 'Registration No. (NTN/CNIC)',
                'appellant_address' => 'Client Address',
                'appellant_phone' => 'Client Phone',
                'appellant_email' => 'Client Email',
                'cira_order_no' => 'CIR(A) Order No.',
                'cira_order_date' => 'CIR(A) Order Date',
                'assessment_order_no' => 'Assessment Order No.',
                'assessment_order_date' => 'Assessment Order Date',
                'respondent_1' => 'Respondent 1 (Assessing Officer)',
                'respondent_2' => 'Respondent 2 (Commissioner)',
                'recovery_notice_no' => 'Recovery Notice No.',
                'recovery_notice_date' => 'Recovery Notice Date',
                'reference_no' => 'Intimation Ref No.',
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
            <div style="margin: 8px 0 0; color: #4b5563; line-height: 1.7; background: #fafbfc; padding: 16px; border-radius: 8px; border: 1px solid #f0f2f5;">{!! $meta['grounds'] !!}</div>
        </div>
        @endif

        @if(!empty($meta['prayer']))
        <div class="mb-3">
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Prayer / Relief Sought</div>
            <div style="margin: 8px 0 0; color: #4b5563; line-height: 1.7; background: #fafbfc; padding: 16px; border-radius: 8px; border: 1px solid #f0f2f5;">{!! $meta['prayer'] !!}</div>
        </div>
        @endif

        @if(!empty($meta['stay_reasons']))
        <div class="mb-3">
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Brief Facts of the Case</div>
            <div style="margin: 8px 0 0; color: #4b5563; line-height: 1.7; background: #fafbfc; padding: 16px; border-radius: 8px; border: 1px solid #f0f2f5;">{!! $meta['stay_reasons'] !!}</div>
        </div>
        @endif
    </div>
</div>
@endif

@if($process->template === 'st-tribunal-stay')
<!-- Combined Package -->
<div class="card mt-4">
    <div class="card-body" style="padding: 18px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
        <div>
            <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;"><i class="bi bi-collection me-1"></i>Combined Package</div>
            <div style="font-size: 0.82rem; color: #6b7280;">All 10 documents in sequence with page numbers, ready to print as one PDF.</div>
        </div>
        <a href="{{ route('processes.document.combined', $process) }}" target="_blank" class="btn btn-accent"><i class="bi bi-eye me-1"></i>Open Combined Package</a>
    </div>
</div>

<!-- Attached Documents -->
<div class="card mt-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-paperclip" style="color: var(--accent);"></i>
        <span style="font-weight: 700;">Attached Files</span>
    </div>
    <div class="card-body" style="padding: 20px;">
        <div class="row g-3">
            @foreach([
                'order_in_appeal_file' => 'Order in Appeal',
                'order_in_original_file' => 'Order in Original',
                'recovery_notice_file' => 'Recovery Notice',
            ] as $field => $label)
            <div class="col-md-4">
                <div class="card" style="padding: 14px; border: 1.5px solid #e8eaed;">
                    <div style="font-weight: 600; color: var(--primary); font-size: 0.88rem; margin-bottom: 6px;">{{ $label }}</div>
                    @if(!empty($meta[$field]))
                        <a href="{{ asset($meta[$field]) }}" target="_blank" style="font-size: 0.78rem; color: #2A8AB8;"><i class="bi bi-file-earmark me-1"></i>View / Download</a>
                    @else
                        <span style="font-size: 0.78rem; color: #9ca3af;">Not attached yet</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@if($process->template && (str_contains($process->template, 'appeal') || str_contains($process->template, 'stay')))
<!-- Generate Documents -->
<div class="card mt-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-file-earmark-word" style="color: var(--accent);"></i>
        <span style="font-weight: 700;">Generate Documents</span>
    </div>
    <div class="card-body" style="padding: 20px;">
        <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 16px;">Click to preview and download auto-generated documents with all case details filled in.</p>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="{{ route('processes.document.preview', [$process, 'appeal-memo']) }}" target="_blank" class="card text-decoration-none" style="padding: 16px; transition: all 0.2s; border: 1.5px solid #e8eaed;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(220,38,38,0.08); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-file-earmark-ruled" style="color: #dc2626; font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--primary); font-size: 0.88rem;">Appeal Memo</div>
                            <div style="font-size: 0.72rem; color: #9ca3af;">Memorandum of appeal (Form B)</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('processes.document.preview', [$process, 'stay-application']) }}" target="_blank" class="card text-decoration-none" style="padding: 16px; transition: all 0.2s; border: 1.5px solid #e8eaed;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(139,92,246,0.08); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-file-earmark-text" style="color: #7c3aed; font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--primary); font-size: 0.88rem;">Stay Application</div>
                            <div style="font-size: 0.72rem; color: #9ca3af;">Application for interim relief</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('processes.document.preview', [$process, 'grounds-of-appeal']) }}" target="_blank" class="card text-decoration-none" style="padding: 16px; transition: all 0.2s; border: 1.5px solid #e8eaed;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(245,158,11,0.08); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-list-ol" style="color: #f59e0b; font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--primary); font-size: 0.88rem;">Grounds of Appeal</div>
                            <div style="font-size: 0.72rem; color: #9ca3af;">Legal grounds and prayer</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('processes.document.preview', [$process, 'intimation']) }}" target="_blank" class="card text-decoration-none" style="padding: 16px; transition: all 0.2s; border: 1.5px solid #e8eaed;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(59,130,246,0.08); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-envelope" style="color: #3b82f6; font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--primary); font-size: 0.88rem;">Intimation Letter</div>
                            <div style="font-size: 0.72rem; color: #9ca3af;">To Commissioner regarding filing</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('processes.document.preview', [$process, 'affidavit']) }}" target="_blank" class="card text-decoration-none" style="padding: 16px; transition: all 0.2s; border: 1.5px solid #e8eaed;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16,185,129,0.08); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-patch-check" style="color: #10b981; font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--primary); font-size: 0.88rem;">Affidavit</div>
                            <div style="font-size: 0.72rem; color: #9ca3af;">Sworn statement of truth</div>
                        </div>
                    </div>
                </a>
            </div>
            @if($process->template === 'st-tribunal-stay')
            <div class="col-md-4">
                <a href="{{ route('processes.document.preview', [$process, 'power-of-attorney']) }}" target="_blank" class="card text-decoration-none" style="padding: 16px; transition: all 0.2s; border: 1.5px solid #e8eaed;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99,102,241,0.08); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-key" style="color: #6366f1; font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--primary); font-size: 0.88rem;">Power of Attorney</div>
                            <div style="font-size: 0.72rem; color: #9ca3af;">Print on Rs. 200 stamp paper</div>
                        </div>
                    </div>
                </a>
            </div>
            @endif
            <div class="col-md-4">
                <a href="{{ route('processes.document.preview', [$process, 'index']) }}" target="_blank" class="card text-decoration-none" style="padding: 16px; transition: all 0.2s; border: 1.5px solid #e8eaed;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(48,58,80,0.06); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-list-columns-reverse" style="color: var(--primary); font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--primary); font-size: 0.88rem;">Index</div>
                            <div style="font-size: 0.72rem; color: #9ca3af;">Print 4× and tick the copy type</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endif

<a href="{{ route('processes.index') }}" class="btn btn-outline-primary mt-3"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection
