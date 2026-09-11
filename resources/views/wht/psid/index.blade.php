@extends('layouts.app')
@section('title', 'Prepare PSID')
@section('page-title', 'Prepare PSID')

@section('content')
@include('wht.partials.agent-bar')

@php
    $badges = [
        'empty'   => ['secondary', 'Nothing recorded'],
        'pending' => ['warning',   'Not yet uploaded'],
        'partial' => ['warning',   'Partially assigned'],
        'psid'    => ['info',      'PSID assigned — awaiting payment'],
        'paid'    => ['success',   'Paid'],
    ];
@endphp

<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" class="d-flex align-items-end gap-2 flex-wrap">
            <div>
                <label class="form-label mb-1">Tax Period</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Show</button>
            <div class="ms-auto text-muted" style="font-size: 0.82rem; max-width: 520px;">
                Two challans a month: one covering all vendor withholding, one covering salaries.
                Entries are selected by <strong>tax period</strong>, so a liability deposited later still
                belongs to the month it arose in.
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
@foreach($batches as $kind => $b)
    @php [$tone, $label] = $badges[$b['status']]; @endphp
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ $b['label'] }}</strong>
                <span class="badge bg-{{ $tone }} bg-opacity-10 text-{{ $tone }}">{{ $label }}</span>
            </div>

            <div class="card-body">
                @if($b['count'] === 0)
                    <p class="text-muted text-center py-4 mb-0">
                        No {{ $kind === 'salaries' ? 'salary' : 'vendor' }} entries for {{ $monthStart->format('F Y') }}.
                    </p>
                @else
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Payees</div>
                            <div class="fw-bold" style="font-size: 1.2rem;">{{ $b['payees'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Entries</div>
                            <div class="fw-bold" style="font-size: 1.2rem;">{{ $b['count'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase;">Tax to Deposit</div>
                            <div class="fw-bold text-primary" style="font-size: 1.2rem;">{{ number_format($b['tax'], 0) }}</div>
                        </div>
                    </div>

                    @if($b['sections']->isNotEmpty() && $kind !== 'salaries')
                        <div class="mb-3" style="font-size: 0.8rem;">
                            <span class="text-muted">Sections:</span>
                            @foreach($b['sections'] as $s)
                                <span class="badge bg-light text-dark border">{{ $s }}</span>
                            @endforeach
                        </div>
                    @endif

                    <hr class="my-3">

                    {{-- Step 1 --}}
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <span class="badge bg-secondary rounded-circle">1</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1">Download the upload file</div>
                            <a href="{{ route('wht.psid.download', ['kind' => $kind, 'month' => $month]) }}"
                               class="btn btn-success btn-sm">
                                <i class="bi bi-file-earmark-excel me-1"></i> Generate for IRIS
                            </a>
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <span class="badge bg-secondary rounded-circle">2</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1">Enter the PSID that IRIS returned</div>
                            @if($b['psid_no']->isNotEmpty())
                                <div class="mb-2">
                                    @foreach($b['psid_no'] as $p)
                                        <span class="badge bg-info bg-opacity-10 text-info">{{ $p }}</span>
                                    @endforeach
                                    <span class="text-muted ms-1" style="font-size: 0.78rem;">
                                        on {{ $b['with_psid'] }} of {{ $b['count'] }} entries
                                    </span>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('wht.psid.assign-psid', $kind) }}" class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="text" name="psid_no" class="form-control form-control-sm"
                                       placeholder="PSID number" required>
                                <button class="btn btn-primary btn-sm text-nowrap">Assign to all {{ $b['count'] }}</button>
                            </form>
                        </div>
                    </div>

                    {{-- Step 3 --}}
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge bg-secondary rounded-circle">3</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1">Record the CPR once paid</div>
                            @if($b['cpr_no']->isNotEmpty())
                                <div class="mb-2">
                                    @foreach($b['cpr_no'] as $c)
                                        <span class="badge bg-success bg-opacity-10 text-success">{{ $c }}</span>
                                    @endforeach
                                    <span class="text-muted ms-1" style="font-size: 0.78rem;">
                                        on {{ $b['with_cpr'] }} of {{ $b['count'] }} entries
                                    </span>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('wht.psid.assign-cpr', $kind) }}" class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="text" name="cpr_no" class="form-control form-control-sm"
                                       placeholder="CPR number" required>
                                <input type="date" name="cpr_date" class="form-control form-control-sm" style="max-width: 150px;">
                                <button class="btn btn-primary btn-sm text-nowrap">Record</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            @if($b['count'] > 0 && ($b['with_psid'] > 0 || $b['with_cpr'] > 0))
            <div class="card-footer d-flex gap-2" style="font-size: 0.8rem;">
                @if($b['with_cpr'] > 0)
                <form method="POST" action="{{ route('wht.psid.clear', $kind) }}"
                      onsubmit="return confirm('Clear the CPR on all {{ $b['count'] }} entries?')">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="what" value="cpr">
                    <button class="btn btn-outline-danger btn-sm">Clear CPR</button>
                </form>
                @endif
                @if($b['with_psid'] > 0)
                <form method="POST" action="{{ route('wht.psid.clear', $kind) }}"
                      onsubmit="return confirm('Clear the PSID and CPR on all {{ $b['count'] }} entries?')">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="what" value="psid">
                    <button class="btn btn-outline-danger btn-sm">Clear PSID &amp; CPR</button>
                </form>
                @endif
            </div>
            @endif
        </div>
    </div>
@endforeach
</div>

@if(Auth::user()->hasRole('admin'))
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;"
         onclick="document.getElementById('layoutBox').classList.toggle('d-none')">
        <strong>Upload file column layout</strong>
        <span class="badge bg-{{ $layoutIsCustom ? 'success' : 'warning' }} bg-opacity-10 text-{{ $layoutIsCustom ? 'success' : 'warning' }}">
            {{ $layoutIsCustom ? 'Customised' : 'Built-in default — unverified' }}
        </span>
    </div>
    <div class="card-body d-none" id="layoutBox">
        <p class="text-muted" style="font-size: 0.85rem;">
            This is what the generated file's columns look like. The built-in default is a
            <strong>best guess</strong> — if IRIS rejects an upload, open a real FBR template, copy its header
            row exactly, and edit the <code>header</code> values below to match. Changes apply immediately,
            with no deploy.
        </p>
        <form method="POST" action="{{ route('wht.psid.layout') }}">
            @csrf
            <textarea name="layout" class="form-control font-monospace" rows="18"
                      style="font-size: 0.78rem;">{{ $layoutJson }}</textarea>
            <div class="mt-3 d-flex gap-2">
                <button class="btn btn-primary">Save layout</button>
                @if($layoutIsCustom)
                <button type="submit" formaction="{{ route('wht.psid.layout-reset') }}"
                        class="btn btn-outline-primary"
                        onclick="return confirm('Reset to the built-in default layout?')">Reset to default</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endif
@endsection
