@extends('layouts.app')
@section('title', 'Edit Process')
@section('page-title', 'Edit Process')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <form method="POST" action="{{ route('processes.update', $process) }}">
            @csrf @method('PUT')
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
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $process->description) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Stage</label>
                    <select name="stage" class="form-select" required>
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
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $process->notes) }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">Update Process</button>
                <a href="{{ route('processes.show', $process) }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
