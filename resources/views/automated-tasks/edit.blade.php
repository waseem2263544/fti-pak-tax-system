@extends('layouts.app')
@section('title', 'Edit Automation')
@section('page-title', 'Edit Automation')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <form method="POST" action="{{ route('automated-tasks.update', $automatedTask) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Automation Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $automatedTask->name) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Trigger Type</label>
                    <select name="trigger_type" class="form-select" required>
                        <option value="deadline_based" {{ $automatedTask->trigger_type == 'deadline_based' ? 'selected' : '' }}>Deadline Based</option>
                        <option value="date_based" {{ $automatedTask->trigger_type == 'date_based' ? 'selected' : '' }}>Date Based</option>
                        <option value="recurring" {{ $automatedTask->trigger_type == 'recurring' ? 'selected' : '' }}>Recurring</option>
                        <option value="event_based" {{ $automatedTask->trigger_type == 'event_based' ? 'selected' : '' }}>Event Based</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Trigger Value</label>
                    <input type="text" name="trigger_value" class="form-control" value="{{ old('trigger_value', $automatedTask->trigger_value) }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $automatedTask->description) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Related Service</label>
                    <select name="service_id" class="form-select">
                        <option value="">All Services</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ $automatedTask->service_id == $service->id ? 'selected' : '' }}>{{ $service->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Task Title Template</label>
                    <input type="text" name="task_template" class="form-control" value="{{ old('task_template', $automatedTask->task_template) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" required>
                        <option value="0" {{ $automatedTask->priority == 0 ? 'selected' : '' }}>Low</option>
                        <option value="1" {{ $automatedTask->priority == 1 ? 'selected' : '' }}>Medium</option>
                        <option value="2" {{ $automatedTask->priority == 2 ? 'selected' : '' }}>High</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Assign To Roles</label>
                <div class="d-flex gap-4">
                    @foreach($roles as $role)
                    <div class="form-check">
                        <input type="checkbox" name="assign_to_roles[]" value="{{ $role->id }}" class="form-check-input" id="role{{ $role->id }}"
                            {{ in_array($role->id, $automatedTask->assign_to_roles ?? []) ? 'checked' : '' }}>
                        <label class="form-check-label" for="role{{ $role->id }}">{{ $role->display_name ?? $role->name }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="mb-4">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" class="form-check-input" id="isActive" {{ $automatedTask->is_active ? 'checked' : '' }} style="width: 3em; height: 1.5em;">
                    <label class="form-check-label" for="isActive" style="font-weight: 600;">Active</label>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">Update Automation</button>
                <a href="{{ route('automated-tasks.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
