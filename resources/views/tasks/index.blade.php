@extends('layouts.app')
@section('title', 'Tasks')
@section('page-title', 'Tasks')

@section('styles')
<style>
    .kanban { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 16px; min-height: calc(100vh - 180px); }
    .kanban-col { flex: 1; min-width: 280px; max-width: 340px; display: flex; flex-direction: column; }
    .kanban-header {
        padding: 12px 16px; border-radius: 12px 12px 0 0;
        font-weight: 700; font-size: 0.82rem; text-transform: uppercase;
        letter-spacing: 0.5px; display: flex; justify-content: space-between; align-items: center;
    }
    .kanban-header .count {
        width: 24px; height: 24px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.72rem; font-weight: 800;
    }
    .kanban-body {
        flex: 1; padding: 8px; border-radius: 0 0 12px 12px;
        background: rgba(0,0,0,0.02); overflow-y: auto;
        display: flex; flex-direction: column; gap: 8px;
    }
    .kanban-card {
        background: #fff; border-radius: 10px; padding: 14px 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        cursor: pointer; transition: all 0.2s;
        border-left: 3px solid transparent;
        text-decoration: none; color: inherit; display: block;
    }
    .kanban-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: inherit; }
    .kanban-card.priority-high { border-left-color: #ef4444; }
    .kanban-card.priority-medium { border-left-color: #f59e0b; }
    .kanban-card.priority-low { border-left-color: #d1d5db; }
    .kanban-card .task-title { font-weight: 600; font-size: 0.85rem; color: var(--primary); margin-bottom: 6px; line-height: 1.3; }
    .kanban-card .task-meta { font-size: 0.72rem; color: #9ca3af; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .kanban-card .task-assignees { display: flex; gap: -4px; margin-top: 8px; }
    .kanban-card .task-avatar {
        width: 24px; height: 24px; border-radius: 6px;
        background: rgba(48,58,80,0.08); display: flex; align-items: center; justify-content: center;
        font-size: 0.55rem; font-weight: 700; color: var(--primary); margin-right: 4px;
    }
    .kanban-empty { text-align: center; padding: 32px 16px; color: #d1d5db; font-size: 0.82rem; }
    .kanban-empty i { font-size: 1.5rem; display: block; margin-bottom: 8px; }

    .col-overdue .kanban-header { background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); color: #991b1b; }
    .col-overdue .kanban-header .count { background: #fee2e2; color: #dc2626; }
    .col-pending .kanban-header { background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%); color: #854d0e; }
    .col-pending .kanban-header .count { background: #fef9c3; color: #a16207; }
    .col-progress .kanban-header { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1e40af; }
    .col-progress .kanban-header .count { background: #dbeafe; color: #2563eb; }
    .col-done .kanban-header { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: #065f46; }
    .col-done .kanban-header .count { background: #d1fae5; color: #059669; }
</style>
@endsection

@section('content')
<!-- Top bar -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2">
        <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
            <option value="">All Clients</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
            @endforeach
        </select>
        <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 160px;">
            <option value="">All Assignees</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
            @endforeach
        </select>
        @if(request()->hasAny(['client_id', 'assigned_to']))
            <a href="{{ route('tasks.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-x-lg"></i></a>
        @endif
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('scheduled-tasks.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-clock-history me-1"></i>Scheduled
        </a>
        <a href="{{ route('tasks.create') }}" class="btn btn-accent btn-sm">
            <i class="bi bi-plus-lg me-1"></i>New Task
        </a>
    </div>
</div>

<!-- Kanban Board -->
<div class="kanban">
    <!-- Overdue -->
    @if($overdue->count())
    <div class="kanban-col col-overdue">
        <div class="kanban-header">
            <span><i class="bi bi-exclamation-triangle me-1"></i>Overdue</span>
            <div class="count">{{ $overdue->count() }}</div>
        </div>
        <div class="kanban-body">
            @foreach($overdue as $task)
            @include('tasks._kanban_card', ['task' => $task])
            @endforeach
        </div>
    </div>
    @endif

    <!-- Pending -->
    <div class="kanban-col col-pending">
        <div class="kanban-header">
            <span><i class="bi bi-circle me-1"></i>Pending</span>
            <div class="count">{{ $pending->count() }}</div>
        </div>
        <div class="kanban-body">
            @forelse($pending as $task)
            @include('tasks._kanban_card', ['task' => $task])
            @empty
            <div class="kanban-empty"><i class="bi bi-inbox"></i>No pending tasks</div>
            @endforelse
        </div>
    </div>

    <!-- In Progress -->
    <div class="kanban-col col-progress">
        <div class="kanban-header">
            <span><i class="bi bi-arrow-right-circle me-1"></i>In Progress</span>
            <div class="count">{{ $inProgress->count() }}</div>
        </div>
        <div class="kanban-body">
            @forelse($inProgress as $task)
            @include('tasks._kanban_card', ['task' => $task])
            @empty
            <div class="kanban-empty"><i class="bi bi-arrow-right-circle"></i>No tasks in progress</div>
            @endforelse
        </div>
    </div>

    <!-- Completed -->
    <div class="kanban-col col-done">
        <div class="kanban-header">
            <span><i class="bi bi-check-circle me-1"></i>Completed</span>
            <div class="count">{{ $completed->count() }}</div>
        </div>
        <div class="kanban-body">
            @forelse($completed as $task)
            @include('tasks._kanban_card', ['task' => $task])
            @empty
            <div class="kanban-empty"><i class="bi bi-check-circle"></i>No completed tasks</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
