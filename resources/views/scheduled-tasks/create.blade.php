@extends('layouts.app')
@section('title', 'Create Scheduled Task')
@section('page-title', 'Create Scheduled Task')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <form method="POST" action="{{ route('scheduled-tasks.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Rule Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g. Monthly Sales Tax Filing">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Frequency</label>
                    <select name="trigger_type" id="triggerType" class="form-select" required onchange="updateTriggerValue()">
                        <option value="monthly" selected>Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="weekly">Weekly</option>
                        <option value="daily">Daily</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3" id="triggerValueGroup">
                    <label class="form-label" id="triggerLabel">Day of Month</label>
                    <div id="triggerMonthly">
                        <select name="trigger_value" class="form-select">
                            @for($i = 1; $i <= 28; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description <small class="text-muted">(optional)</small></label>
                <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="What this rule does...">
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Service</label>
                    <select name="service_id" class="form-select" required>
                        <option value="">Select Service</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->display_name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Tasks will be created for all clients with this active service.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Assign Tasks To</label>
                    <select name="assign_to_user" class="form-select" required>
                        <option value="">Select Employee</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
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
                <label class="form-label">Task Title Template</label>
                <input type="text" name="task_template" class="form-control @error('task_template') is-invalid @enderror" value="{{ old('task_template', 'File {service} of {client_name}') }}" required>
                <small class="text-muted">Use <code>{client_name}</code> for client name and <code>{service}</code> for service name.</small>
                @error('task_template') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked style="width: 3em; height: 1.5em;">
                    <label class="form-check-label" for="isActive" style="font-weight: 600;">Active</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">Create Rule</button>
                <a href="{{ route('scheduled-tasks.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function updateTriggerValue() {
    var type = document.getElementById('triggerType').value;
    var group = document.getElementById('triggerValueGroup');
    var label = document.getElementById('triggerLabel');
    var container = document.getElementById('triggerMonthly');

    if (type === 'daily') {
        group.style.display = 'none';
        return;
    }
    group.style.display = 'block';

    if (type === 'monthly') {
        label.textContent = 'Day of Month';
        var opts = '';
        for (var i = 1; i <= 28; i++) opts += '<option value="'+i+'">'+i+'</option>';
        container.innerHTML = '<select name="trigger_value" class="form-select">'+opts+'</select>';
    } else if (type === 'weekly') {
        label.textContent = 'Day of Week';
        container.innerHTML = '<select name="trigger_value" class="form-select"><option value="1">Monday</option><option value="2">Tuesday</option><option value="3">Wednesday</option><option value="4">Thursday</option><option value="5">Friday</option><option value="6">Saturday</option><option value="0">Sunday</option></select>';
    } else if (type === 'yearly') {
        label.textContent = 'Date (MM-DD)';
        container.innerHTML = '<input type="text" name="trigger_value" class="form-control" placeholder="09-30" pattern="\\d{2}-\\d{2}">';
    }
}
</script>
@endsection
