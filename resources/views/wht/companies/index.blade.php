@extends('layouts.app')
@section('title', 'Withholding Agents')
@section('page-title', 'Withholding Agents')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p style="color: var(--n-400); font-size: 0.85rem; margin: 0;">Choose the company whose withholding you want to work on.</p>
    @if(Auth::user()->hasRole('admin'))
        <a href="{{ route('wht.companies.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> New Agent</a>
    @endif
</div>

@if($companies->isEmpty())
    <div class="card"><div class="card-body text-center" style="padding: 48px 20px;">
        <i class="bi bi-building" style="font-size: 2rem; color: var(--n-300);"></i>
        <p class="mt-3 mb-0 text-muted">No withholding agents are available to you yet.</p>
    </div></div>
@else
<div class="row g-3">
    @foreach($companies as $c)
    <div class="col-md-6 col-xl-4">
        <div class="card h-100 @if(session('wht_company_id') == $c->id) border-primary @endif">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0">{{ $c->name }}</h5>
                    @if(session('wht_company_id') == $c->id)
                        <span class="badge bg-primary">Active</span>
                    @endif
                </div>
                <div class="text-muted mb-2" style="font-size: 0.85rem;">
                    NTN / CNIC: {{ $c->ntn_cnic ?: '—' }}
                </div>
                <div class="mb-3" style="font-size: 0.85rem;">
                    Office Reference:
                    @if($c->office_reference)
                        <span class="text-muted">{{ $c->office_reference }}</span>
                    @else
                        <span class="badge bg-warning text-dark">not set</span>
                    @endif
                </div>
                <div class="d-flex gap-3 mb-3" style="font-size: 0.8rem; color: var(--n-500);">
                    <span><i class="bi bi-people me-1"></i>{{ $c->parties_count }} parties</span>
                    <span><i class="bi bi-file-earmark-text me-1"></i>{{ $c->purchases_count }} payments</span>
                    <span><i class="bi bi-cash-stack me-1"></i>{{ $c->salaries_count }} salaries</span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('wht.companies.select', $c) }}" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Open
                    </a>
                    @if(Auth::user()->hasRole('admin'))
                        <a href="{{ route('wht.companies.edit', $c) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
