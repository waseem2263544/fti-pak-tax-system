@extends('layouts.app')
@section('title', 'Edit Task')
@section('page-title', 'Edit Task')

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('tasks.update', $task) }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $task->title) }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select">
                        <option value="">-- None --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $task->client_id) == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $task->description) }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" {{ $task->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ $task->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $task->status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="overdue" {{ $task->status == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" required>
                        <option value="0" {{ $task->priority == 0 ? 'selected' : '' }}>Low</option>
                        <option value="1" {{ $task->priority == 1 ? 'selected' : '' }}>Medium</option>
                        <option value="2" {{ $task->priority == 2 ? 'selected' : '' }}>High</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Assign To</label>
                <div class="row">
                    @foreach($users as $user)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="assigned_users[]" value="{{ $user->id }}" class="form-check-input" id="user{{ $user->id }}"
                                {{ $task->assignedUsers->contains($user->id) ? 'checked' : '' }}>
                            <label class="form-check-label" for="user{{ $user->id }}">{{ $user->name }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Task</button>
                <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
