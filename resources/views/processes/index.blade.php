@extends('layouts.app')
@section('title', 'Processes')
@section('page-title', 'Processes')

@section('styles')
<style>
    .process-card {
        border-radius: 16px; padding: 28px; cursor: pointer;
        transition: all 0.3s cubic-bezier(.4,0,.2,1);
        border: 2px solid transparent; position: relative; overflow: hidden;
    }
    .process-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
    .process-card::after {
        content: ''; position: absolute; top: 0; right: 0;
        width: 120px; height: 120px;
        background: radial-gradient(circle at top right, rgba(215,223,39,0.08) 0%, transparent 70%);
    }
    .process-card .icon-box {
        width: 56px; height: 56px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 16px;
    }
    .process-card h5 { font-weight: 700; color: var(--primary); margin: 0 0 6px; font-size: 1rem; }
    .process-card p { font-size: 0.82rem; color: #6b7280; margin: 0; line-height: 1.5; }
    .process-card .arrow { position: absolute; bottom: 20px; right: 20px; color: #d1d5db; font-size: 1.2rem; transition: all 0.2s; }
    .process-card:hover .arrow { color: var(--accent); transform: translateX(4px); }

    .sub-option {
        padding: 16px 20px; border-radius: 12px; cursor: pointer;
        transition: all 0.2s; border: 1.5px solid #e8eaed; background: #fff;
        display: flex; align-items: center; gap: 12px;
    }
    .sub-option:hover { border-color: var(--accent); background: var(--accent-glow); }
    .sub-option .sub-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .sub-option h6 { font-weight: 600; color: var(--primary); margin: 0; font-size: 0.88rem; }
    .sub-option small { color: #9ca3af; font-size: 0.75rem; }

    .breadcrumb-nav { display: flex; align-items: center; gap: 8px; margin-bottom: 24px; font-size: 0.85rem; }
    .breadcrumb-nav a { color: #9ca3af; text-decoration: none; }
    .breadcrumb-nav a:hover { color: var(--primary); }
    .breadcrumb-nav .sep { color: #d1d5db; }
    .breadcrumb-nav .current { color: var(--primary); font-weight: 600; }
</style>
@endsection

@section('content')
@php $step = request('step', 'home'); @endphp

@if($step === 'home')
<!-- Process Templates -->
<div class="mb-4">
    <p style="color: #6b7280; font-size: 0.88rem;">Select a process to automate document generation and workflows.</p>
</div>

<div class="row g-4">
    <!-- Filing of Appeal -->
    <div class="col-md-4">
        <div class="card process-card" onclick="location.href='{{ route('processes.index', ['step' => 'appeal']) }}'">
            <div class="icon-box" style="background: rgba(139,92,246,0.08);">
                <i class="bi bi-bank" style="color: #7c3aed;"></i>
            </div>
            <h5>Filing of Appeal</h5>
            <p>Generate appeal documents for Commissioner Appeals and Appellate Tribunal for Income Tax and Sales Tax/FED cases.</p>
            <i class="bi bi-chevron-right arrow"></i>
        </div>
    </div>

    <!-- More process templates (coming soon) -->
    <div class="col-md-4">
        <div class="card process-card" style="opacity: 0.5; cursor: default;" onclick="">
            <div class="icon-box" style="background: rgba(59,130,246,0.08);">
                <i class="bi bi-file-earmark-text" style="color: #3b82f6;"></i>
            </div>
            <h5>Reply to Notice</h5>
            <p>Generate reply documents for FBR notices under various sections of the Income Tax Ordinance.</p>
            <span class="badge" style="background: #f3f4f6; color: #9ca3af; position: absolute; top: 16px; right: 16px;">Coming Soon</span>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card process-card" style="opacity: 0.5; cursor: default;" onclick="">
            <div class="icon-box" style="background: rgba(245,158,11,0.08);">
                <i class="bi bi-building" style="color: #f59e0b;"></i>
            </div>
            <h5>Company Registration</h5>
            <p>SECP company incorporation and registration documents with automatic form filling.</p>
            <span class="badge" style="background: #f3f4f6; color: #9ca3af; position: absolute; top: 16px; right: 16px;">Coming Soon</span>
        </div>
    </div>
</div>

@elseif($step === 'appeal')
<!-- Filing of Appeal - Choose Tax Type -->
<div class="breadcrumb-nav">
    <a href="{{ route('processes.index') }}"><i class="bi bi-house me-1"></i>Processes</a>
    <span class="sep"><i class="bi bi-chevron-right"></i></span>
    <span class="current">Filing of Appeal</span>
</div>

<div class="mb-4">
    <h4 style="font-weight: 700; color: var(--primary);">Filing of Appeal</h4>
    <p style="color: #6b7280; font-size: 0.88rem;">Select the type of appeal to file.</p>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card process-card" onclick="location.href='{{ route('processes.index', ['step' => 'appeal-income-tax']) }}'">
            <div class="icon-box" style="background: rgba(48,58,80,0.06);">
                <i class="bi bi-cash-stack" style="color: var(--primary);"></i>
            </div>
            <h5>Income Tax Appeal</h5>
            <p>File appeals under the Income Tax Ordinance, 2001 at Commissioner Appeals or Appellate Tribunal Inland Revenue.</p>
            <i class="bi bi-chevron-right arrow"></i>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card process-card" onclick="location.href='{{ route('processes.index', ['step' => 'appeal-sales-tax']) }}'">
            <div class="icon-box" style="background: rgba(16,185,129,0.08);">
                <i class="bi bi-receipt" style="color: #10b981;"></i>
            </div>
            <h5>Sales Tax / FED Appeal</h5>
            <p>File appeals under the Sales Tax Act, 1990 or Federal Excise Duty at Commissioner Appeals or Appellate Tribunal.</p>
            <i class="bi bi-chevron-right arrow"></i>
        </div>
    </div>
</div>

@elseif($step === 'appeal-income-tax')
<!-- Income Tax Appeal - Choose Level -->
<div class="breadcrumb-nav">
    <a href="{{ route('processes.index') }}"><i class="bi bi-house me-1"></i>Processes</a>
    <span class="sep"><i class="bi bi-chevron-right"></i></span>
    <a href="{{ route('processes.index', ['step' => 'appeal']) }}">Filing of Appeal</a>
    <span class="sep"><i class="bi bi-chevron-right"></i></span>
    <span class="current">Income Tax Appeal</span>
</div>

<div class="mb-4">
    <h4 style="font-weight: 700; color: var(--primary);">Income Tax Appeal</h4>
    <p style="color: #6b7280; font-size: 0.88rem;">Select the appellate level.</p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <a href="{{ route('processes.create', ['template' => 'it-commissioner-appeal']) }}" class="sub-option text-decoration-none">
            <div class="sub-icon" style="background: rgba(59,130,246,0.08);"><i class="bi bi-building" style="color: #3b82f6;"></i></div>
            <div>
                <h6>Commissioner Inland Revenue (Appeals)</h6>
                <small>First appeal under Section 127 of ITO 2001</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('processes.create', ['template' => 'it-tribunal-appeal']) }}" class="sub-option text-decoration-none">
            <div class="sub-icon" style="background: rgba(139,92,246,0.08);"><i class="bi bi-bank2" style="color: #7c3aed;"></i></div>
            <div>
                <h6>Appellate Tribunal Inland Revenue (ATIR)</h6>
                <small>Second appeal under Section 131 of ITO 2001</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('processes.create', ['template' => 'it-tribunal-stay']) }}" class="sub-option text-decoration-none" style="border-color: var(--accent);">
            <div class="sub-icon" style="background: var(--accent-glow);"><i class="bi bi-shield-check" style="color: #8b9a00;"></i></div>
            <div>
                <h6>Stay Application to ATIR</h6>
                <small>Stay of demand pending appeal at Tribunal</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('processes.create', ['template' => 'it-commissioner-stay']) }}" class="sub-option text-decoration-none">
            <div class="sub-icon" style="background: rgba(245,158,11,0.08);"><i class="bi bi-pause-circle" style="color: #f59e0b;"></i></div>
            <div>
                <h6>Stay Application to CIR(A)</h6>
                <small>Stay of demand pending appeal at Commissioner level</small>
            </div>
        </a>
    </div>
</div>

@elseif($step === 'appeal-sales-tax')
<!-- Sales Tax/FED Appeal - Choose Level -->
<div class="breadcrumb-nav">
    <a href="{{ route('processes.index') }}"><i class="bi bi-house me-1"></i>Processes</a>
    <span class="sep"><i class="bi bi-chevron-right"></i></span>
    <a href="{{ route('processes.index', ['step' => 'appeal']) }}">Filing of Appeal</a>
    <span class="sep"><i class="bi bi-chevron-right"></i></span>
    <span class="current">Sales Tax / FED Appeal</span>
</div>

<div class="mb-4">
    <h4 style="font-weight: 700; color: var(--primary);">Sales Tax / FED Appeal</h4>
    <p style="color: #6b7280; font-size: 0.88rem;">Select the appellate level.</p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <a href="{{ route('processes.create', ['template' => 'st-commissioner-appeal']) }}" class="sub-option text-decoration-none">
            <div class="sub-icon" style="background: rgba(59,130,246,0.08);"><i class="bi bi-building" style="color: #3b82f6;"></i></div>
            <div>
                <h6>Commissioner Inland Revenue (Appeals)</h6>
                <small>First appeal under Section 45B of Sales Tax Act 1990</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('processes.create', ['template' => 'st-tribunal-appeal']) }}" class="sub-option text-decoration-none">
            <div class="sub-icon" style="background: rgba(139,92,246,0.08);"><i class="bi bi-bank2" style="color: #7c3aed;"></i></div>
            <div>
                <h6>Appellate Tribunal Inland Revenue (ATIR)</h6>
                <small>Second appeal under Section 46 of Sales Tax Act 1990</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('processes.create', ['template' => 'st-tribunal-stay']) }}" class="sub-option text-decoration-none">
            <div class="sub-icon" style="background: var(--accent-glow);"><i class="bi bi-shield-check" style="color: #8b9a00;"></i></div>
            <div>
                <h6>Stay Application to ATIR</h6>
                <small>Stay of demand pending Sales Tax appeal at Tribunal</small>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('processes.create', ['template' => 'st-commissioner-stay']) }}" class="sub-option text-decoration-none">
            <div class="sub-icon" style="background: rgba(245,158,11,0.08);"><i class="bi bi-pause-circle" style="color: #f59e0b;"></i></div>
            <div>
                <h6>Stay Application to CIR(A)</h6>
                <small>Stay of demand pending Sales Tax appeal at Commissioner level</small>
            </div>
        </a>
    </div>
</div>

@endif

<!-- Recent Processes -->
@if($step === 'home' && isset($processes) && $processes->count())
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Recent Processes
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Process</th>
                    <th>Client</th>
                    <th>Stage</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($processes as $process)
                <tr>
                    <td style="font-weight: 600; color: var(--primary);">{{ $process->title }}</td>
                    <td>{{ $process->client->name ?? '-' }}</td>
                    <td>
                        @if($process->stage == 'intake') <span class="badge" style="background: #f3f4f6; color: #6b7280;">Intake</span>
                        @elseif($process->stage == 'in_progress') <span class="badge" style="background: #dbeafe; color: #1e40af;">In Progress</span>
                        @elseif($process->stage == 'review') <span class="badge" style="background: #fef3c7; color: #92400e;">Review</span>
                        @else <span class="badge" style="background: #d1fae5; color: #065f46;">Completed</span>
                        @endif
                    </td>
                    <td style="font-size: 0.82rem; color: #6b7280;">{{ $process->created_at->format('M d, Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('processes.show', $process) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
