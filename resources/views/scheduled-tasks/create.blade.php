@extends('layouts.app')
@section('title', 'Create Automation')
@section('page-title', 'Create Automation')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <form method="POST" action="{{ route('scheduled-tasks.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Automation Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g. Monthly Sales Tax Reminder">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Trigger Type</label>
                    <select name="trigger_type" class="form-select" required>
                        <option value="deadline_based">Deadline Based</option>
                        <option value="date_based">Date Based</option>
                        <option value="recurring">Recurring</option>
                        <option value="event_based">Event Based</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Trigger Value</label>
                    <input type="text" name="trigger_value" class="form-control" value="{{ old('trigger_value') }}" placeholder="e.g. 7 days before, monthly">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="What does this automation do?">{{ old('description') }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Related Service</label>
                    <select name="service_id" class="form-select">
                        <option value="">All Services</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Task Title Template</label>
                    <input type="text" name="task_template" class="form-control" value="{{ old('task_template') }}" required placeholder="e.g. File Sales Tax Return for {client}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" required>
                        <option value="0">Low</option>
                        <option value="1" selected>Medium</option>
                        <option value="2">High</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Assign To Roles</label>
                <div class="d-flex gap-4">
                    @foreach($roles as $role)
                    <div class="form-check">
                        <input type="checkbox" name="assign_to_roles[]" value="{{ $role->id }}" class="form-check-input" id="role{{ $role->id }}">
                        <label class="form-check-label" for="role{{ $role->id }}">{{ $role->display_name ?? $role->name }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="mb-4">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked style="width: 3em; height: 1.5em;">
                    <label class="form-check-label" for="isActive" style="font-weight: 600;">Active</label>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">Create Automation</button>
                <a href="{{ route('scheduled-tasks.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
