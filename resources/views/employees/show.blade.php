@extends('layouts.app')
@section('title', 'Employee: ' . $employee->name)
@section('page-title', 'Employee Details')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <h4>{{ $employee->name }}</h4>
            <div>
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <strong>Email:</strong><br>{{ $employee->email }}
            </div>
            <div class="col-md-4">
                <strong>Roles:</strong><br>
                @foreach($employee->roles as $role)
                    <span class="badge bg-primary">{{ $role->display_name ?? $role->name }}</span>
                @endforeach
            </div>
            <div class="col-md-4">
                <strong>Joined:</strong><br>{{ $employee->created_at->format('M d, Y') }}
            </div>
        </div>

        <h5 class="mt-4">Assigned Tasks ({{ $employee->tasks->count() }})</h5>
        @if($employee->tasks->count())
        <table class="table table-sm">
            <thead><tr><th>Title</th><th>Status</th><th>Due Date</th></tr></thead>
            <tbody>
                @foreach($employee->tasks as $task)
                <tr>
                    <td><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                    <td>
                        @if($task->status == 'pending') <span class="badge bg-warning">Pending</span>
                        @elseif($task->status == 'in_progress') <span class="badge bg-info">In Progress</span>
                        @elseif($task->status == 'completed') <span class="badge bg-success">Completed</span>
                        @else <span class="badge bg-danger">Overdue</span>
                        @endif
                    </td>
                    <td>{{ $task->due_date ? $task->due_date->format('M d, Y') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-muted">No tasks assigned</p>
        @endif
    </div>
</div>

<a href="{{ route('employees.index') }}" class="btn btn-outline-secondary mt-3"><i class="bi bi-arrow-left"></i> Back</a>
@endsection
