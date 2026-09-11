@extends('layouts.app')
@section('title', 'WHT Setup')
@section('page-title', 'Setup')

@section('content')
@include('wht.partials.agent-switch', ['company' => $company])

@if($partiesMissingId > 0)
<div class="alert alert-warning d-flex align-items-start" style="font-size: 0.87rem;">
    <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
    <div>
        <strong>{{ $partiesMissingId }}</strong> active {{ $partiesMissingId === 1 ? 'party has' : 'parties have' }}
        no CNIC or NTN. FBR needs one on every line, so their entries will fail validation on a PSID or
        statement upload. <a href="{{ route('wht.parties.index') }}">Fix them</a>.
    </div>
</div>
@endif

<div class="row g-3">
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('wht.parties.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Vendors &amp; Employees</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $counts['parties'] }}</span>
                </div>
                <p class="text-muted mb-0" style="font-size: 0.84rem;">
                    Who you withhold from, for <strong>{{ $company->name }}</strong>.
                    {{ $counts['vendors'] }} vendors, {{ $counts['employees'] }} employees.
                    Category and ATL status set here decide the rate.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('wht.companies.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>Withholding Agents</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $counts['agents'] }}</span>
                </div>
                <p class="text-muted mb-0" style="font-size: 0.84rem;">
                    The companies you file for. Add agents, edit their NTN and address, and control
                    which staff can see each one.
                </p>
            </div>
        </a>
    </div>

    @if($isAdmin)
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('wht.rates.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0"><i class="bi bi-percent me-2"></i>Tax Rates</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $counts['rates'] }}</span>
                </div>
                <p class="text-muted mb-0" style="font-size: 0.84rem;">
                    Section × category × ATL status, versioned by month. Shared by every agent,
                    because the rates are statutory.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('wht.slabs.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Salary Slabs</h5>
                    <span class="badge bg-{{ $counts['slabs'] ? 'primary' : 'warning' }} bg-opacity-10 text-{{ $counts['slabs'] ? 'primary' : 'warning' }}">
                        TY{{ $taxYear }}: {{ $counts['slabs'] }}
                    </span>
                </div>
                <p class="text-muted mb-0" style="font-size: 0.84rem;">
                    Annual salary bands per tax year. Without slabs for a year, salary entries in it
                    compute zero tax.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('wht.sections.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0"><i class="bi bi-list-ol me-2"></i>FBR Sections</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $counts['sections'] }}</span>
                </div>
                <p class="text-muted mb-0" style="font-size: 0.84rem;">
                    Withholding heads and their FBR payment codes. The code is what goes on a PSID
                    and a statement, so every section in use needs one.
                </p>
            </div>
        </a>
    </div>

    <div class="col-md-6 col-xl-4">
        <a href="{{ route('wht.deposit.index') }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Upload File Layout</h5>
                </div>
                <p class="text-muted mb-0" style="font-size: 0.84rem;">
                    The columns of the IRIS PSID file, matching FBR's ePayments template. Edit it at the
                    foot of the Deposit page if FBR revises the format — no deploy needed.
                </p>
            </div>
        </a>
    </div>
    @endif
</div>
@endsection
