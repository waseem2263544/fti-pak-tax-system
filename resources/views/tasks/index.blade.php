@extends('layouts.app')
@section('title', 'Tasks')
@section('page-title', 'Tasks')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
            </select>
            <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <a href="{{ route('tasks.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus"></i> New Task
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Due Date</th>
                    <th>Assigned To</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr>
                    <td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                    <td>{{ $task->client->name ?? '-' }}</td>
                    <td>
                        @if($task->status == 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($task->status == 'in_progress')
                            <span class="badge bg-info">In Progress</span>
                        @elseif($task->status == 'completed')
                            <span class="badge bg-success">Completed</span>
                        @else
                            <span class="badge bg-danger">Overdue</span>
                        @endif
                    </td>
                    <td>
                        @if($task->priority == 0)
                            <span class="text-muted">Low</span>
                        @elseif($task->priority == 1)
                            <span class="text-warning">Medium</span>
                        @else
                            <span class="text-danger fw-bold">High</span>
                        @endif
                    </td>
                    <td>{{ $task->due_date ? $task->due_date->format('M d, Y') : '-' }}</td>
                    <td>
                        @foreach($task->assignedUsers as $u)
                            <span class="badge bg-secondary">{{ $u->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this task?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No tasks found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $tasks->links() }}</div>
@endsection
