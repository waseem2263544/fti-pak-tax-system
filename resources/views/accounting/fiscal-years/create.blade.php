@extends('layouts.app')
@section('title', 'New Fiscal Year')
@section('page-title', 'Fiscal Years')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('accounting.fiscal-years.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Fiscal Years
    </a>
</div>

<form action="{{ route('accounting.fiscal-years.store') }}" method="POST">
    @csrf

    <div class="card mb-4" style="max-width: 600px;">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            New Fiscal Year
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="e.g. FY 2025-26" required>
                    @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" name="start_date" value="{{ old('start_date') }}" required>
                    @error('start_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" name="end_date" value="{{ old('end_date') }}" required>
                    @error('end_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger mb-4" style="max-width: 600px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i> Create Fiscal Year</button>
        <a href="{{ route('accounting.fiscal-years.index') }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection
