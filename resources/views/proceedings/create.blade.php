@extends('layouts.app')
@section('title', 'Add Proceeding')
@section('page-title', 'Add Proceeding')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <form method="POST" action="{{ route('proceedings.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required placeholder="e.g. Income Tax Assessment Order">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select" required>
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Stage</label>
                    <select name="stage" class="form-select" required>
                        <option value="department">Department</option>
                        <option value="commissioner_appeals">Commissioner Appeals</option>
                        <option value="tribunal">Tribunal</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Case Number</label>
                    <input type="text" name="case_number" class="form-control" value="{{ old('case_number') }}" placeholder="e.g. ITO/2026/001">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tax Year</label>
                    <input type="text" name="tax_year" class="form-control" value="{{ old('tax_year') }}" placeholder="e.g. 2025-2026">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control" value="{{ old('section') }}" placeholder="e.g. 122(5A)">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" selected>Pending</option>
                        <option value="adjourned">Adjourned</option>
                        <option value="decided">Decided</option>
                        <option value="appealed">Appealed</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Hearing Date</label>
                    <input type="date" name="hearing_date" class="form-control" value="{{ old('hearing_date') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Assigned To</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">Add Proceeding</button>
                <a href="{{ route('proceedings.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
