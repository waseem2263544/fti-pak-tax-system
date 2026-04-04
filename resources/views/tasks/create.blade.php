@extends('layouts.app')
@section('title', 'Create Task')
@section('page-title', 'Create Task')

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('tasks.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select">
                        <option value="">-- None --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" selected>Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
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
                <div class="col-md-4 mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Assign To</label>
                <div class="row">
                    @foreach($users as $user)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="assigned_users[]" value="{{ $user->id }}" class="form-check-input" id="user{{ $user->id }}">
                            <label class="form-check-label" for="user{{ $user->id }}">{{ $user->name }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Task</button>
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
