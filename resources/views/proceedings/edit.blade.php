@extends('layouts.app')
@section('title', 'Edit Proceeding')
@section('page-title', 'Edit Proceeding')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <form method="POST" action="{{ route('proceedings.update', $proceeding) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $proceeding->title) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select" required>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ $proceeding->client_id == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Stage</label>
                    <select name="stage" class="form-select" required>
                        <option value="department" {{ $proceeding->stage == 'department' ? 'selected' : '' }}>Department</option>
                        <option value="commissioner_appeals" {{ $proceeding->stage == 'commissioner_appeals' ? 'selected' : '' }}>Commissioner Appeals</option>
                        <option value="tribunal" {{ $proceeding->stage == 'tribunal' ? 'selected' : '' }}>Tribunal</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Case Number</label>
                    <input type="text" name="case_number" class="form-control" value="{{ old('case_number', $proceeding->case_number) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tax Year</label>
                    <input type="text" name="tax_year" class="form-control" value="{{ old('tax_year', $proceeding->tax_year) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control" value="{{ old('section', $proceeding->section) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" {{ $proceeding->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="adjourned" {{ $proceeding->status == 'adjourned' ? 'selected' : '' }}>Adjourned</option>
                        <option value="decided" {{ $proceeding->status == 'decided' ? 'selected' : '' }}>Decided</option>
                        <option value="appealed" {{ $proceeding->status == 'appealed' ? 'selected' : '' }}>Appealed</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Hearing Date</label>
                    <input type="date" name="hearing_date" class="form-control" value="{{ $proceeding->hearing_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Order Date</label>
                    <input type="date" name="order_date" class="form-control" value="{{ $proceeding->order_date?->format('Y-m-d') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Assigned To</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $proceeding->assigned_to == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $proceeding->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Outcome</label>
                <textarea name="outcome" class="form-control" rows="2">{{ old('outcome', $proceeding->outcome) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $proceeding->notes) }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">Update Proceeding</button>
                <a href="{{ route('proceedings.show', $proceeding) }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
