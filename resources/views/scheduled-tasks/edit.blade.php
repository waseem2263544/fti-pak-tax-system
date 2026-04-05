@extends('layouts.app')
@section('title', 'Edit Scheduled Task')
@section('page-title', 'Edit Scheduled Task')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <form method="POST" action="{{ route('scheduled-tasks.update', $automatedTask) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Rule Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $automatedTask->name) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Frequency</label>
                    <select name="trigger_type" class="form-select" required>
                        <option value="monthly" {{ $automatedTask->trigger_type == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ $automatedTask->trigger_type == 'quarterly' ? 'selected' : '' }}>Quarterly (Jan, Apr, Jul, Oct)</option>
                        <option value="yearly" {{ $automatedTask->trigger_type == 'yearly' ? 'selected' : '' }}>Yearly</option>
                        <option value="weekly" {{ $automatedTask->trigger_type == 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="daily" {{ $automatedTask->trigger_type == 'daily' ? 'selected' : '' }}>Daily</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Trigger Value</label>
                    <input type="text" name="trigger_value" class="form-control" value="{{ old('trigger_value', $automatedTask->trigger_value) }}" placeholder="Day or date">
                </div>
            </div>
            @if($automatedTask->trigger_type == 'monthly')
            <div class="mb-3">
                <label class="form-label">Run in specific months only <small class="text-muted">(optional)</small></label>
                <div class="d-flex flex-wrap gap-2">
                    @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $i => $month)
                    <div class="form-check" style="min-width: 80px;">
                        <input type="checkbox" name="run_months[]" value="{{ $i + 1 }}" class="form-check-input" id="month{{ $i + 1 }}"
                            {{ in_array($i + 1, $automatedTask->run_months ?? []) ? 'checked' : '' }}>
                        <label class="form-check-label" for="month{{ $i + 1 }}" style="font-size: 0.85rem;">{{ $month }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="mb-3">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" value="{{ old('description', $automatedTask->description) }}">
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Service</label>
                    <select name="service_id" class="form-select" required>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ $automatedTask->service_id == $service->id ? 'selected' : '' }}>{{ $service->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Assign Tasks To</label>
                    <select name="assign_to_user" class="form-select" required>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $automatedTask->assign_to_user == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="0" {{ $automatedTask->priority == 0 ? 'selected' : '' }}>Low</option>
                        <option value="1" {{ $automatedTask->priority == 1 ? 'selected' : '' }}>Medium</option>
                        <option value="2" {{ $automatedTask->priority == 2 ? 'selected' : '' }}>High</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Due Date (days from creation)</label>
                    <div class="input-group">
                        <input type="number" name="due_in_days" class="form-control" value="{{ old('due_in_days', $automatedTask->due_in_days ?? 15) }}" min="1" max="365">
                        <span class="input-group-text" style="font-size: 0.82rem;">days after task is created</span>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Task Title Template</label>
                <input type="text" name="task_template" class="form-control" value="{{ old('task_template', $automatedTask->task_template) }}" required>
                <small class="text-muted">Use <code>{client_name}</code> and <code>{service}</code> as placeholders.</small>
            </div>
            <div class="mb-4">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" class="form-check-input" {{ $automatedTask->is_active ? 'checked' : '' }} style="width: 3em; height: 1.5em;">
                    <label class="form-check-label" style="font-weight: 600;">Active</label>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">Update Rule</button>
                <a href="{{ route('scheduled-tasks.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
