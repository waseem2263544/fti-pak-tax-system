@extends('layouts.app')
@section('title', 'Task: ' . $task->title)
@section('page-title', 'Task Details')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <h4>{{ $task->title }}</h4>
            <div>
                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
                <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                </form>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <strong>Status:</strong><br>
                @if($task->status == 'pending')
                    <span class="badge bg-warning">Pending</span>
                @elseif($task->status == 'in_progress')
                    <span class="badge bg-info">In Progress</span>
                @elseif($task->status == 'completed')
                    <span class="badge bg-success">Completed</span>
                @else
                    <span class="badge bg-danger">Overdue</span>
                @endif
            </div>
            <div class="col-md-3">
                <strong>Priority:</strong><br>
                @if($task->priority == 0) Low @elseif($task->priority == 1) Medium @else <span class="text-danger">High</span> @endif
            </div>
            <div class="col-md-3">
                <strong>Due Date:</strong><br>
                {{ $task->due_date ? $task->due_date->format('M d, Y') : 'Not set' }}
            </div>
            <div class="col-md-3">
                <strong>Client:</strong><br>
                {{ $task->client->name ?? 'None' }}
            </div>
        </div>

        <div class="mb-3">
            <strong>Description:</strong>
            <p class="mt-1">{{ $task->description ?: 'No description' }}</p>
        </div>

        <div class="mb-3">
            <strong>Created By:</strong> {{ $task->createdBy->name ?? 'Unknown' }}
        </div>

        <div class="mb-3">
            <strong>Assigned To:</strong>
            @forelse($task->assignedUsers as $u)
                <span class="badge bg-secondary">{{ $u->name }}</span>
            @empty
                <span class="text-muted">No one assigned</span>
            @endforelse
        </div>

        <div class="text-muted small">
            Created: {{ $task->created_at->format('M d, Y H:i') }}
            | Updated: {{ $task->updated_at->format('M d, Y H:i') }}
        </div>
    </div>
</div>

<a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary mt-3"><i class="bi bi-arrow-left"></i> Back to Tasks</a>
@endsection
