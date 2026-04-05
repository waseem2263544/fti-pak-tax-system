@extends('layouts.app')
@section('title', 'Task: ' . $task->title)
@section('page-title', 'Task Details')

@section('content')
<div class="card mb-4">
    <div class="card-body" style="padding: 24px;">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h4 style="font-weight: 700; color: var(--primary); margin: 0;">{{ $task->title }}</h4>
                <div style="font-size: 0.82rem; color: #9ca3af; margin-top: 4px;">
                    Created by {{ $task->createdBy->name ?? 'Unknown' }} · {{ $task->created_at->format('M d, Y H:i') }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
                <form action="{{ route('tasks.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
                <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Status</div>
                @if($task->status == 'pending') <span class="badge" style="background: #fef3c7; color: #92400e;">Pending</span>
                @elseif($task->status == 'in_progress') <span class="badge" style="background: #dbeafe; color: #1e40af;">In Progress</span>
                @elseif($task->status == 'completed') <span class="badge" style="background: #d1fae5; color: #065f46;">Completed</span>
                @else <span class="badge" style="background: #fef2f2; color: #dc2626;">Overdue</span>
                @endif
            </div>
            <div class="col-md-3 mb-2">
                <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Priority</div>
                @if($task->priority == 0) <span style="color: #6b7280;">Low</span>
                @elseif($task->priority == 1) <span style="color: #d97706; font-weight: 600;">Medium</span>
                @else <span style="color: #dc2626; font-weight: 600;">High</span>
                @endif
            </div>
            <div class="col-md-3 mb-2">
                <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Due Date</div>
                <span>{{ $task->due_date ? $task->due_date->format('M d, Y') : 'Not set' }}</span>
            </div>
            <div class="col-md-3 mb-2">
                <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Client</div>
                @if($task->client)
                    <a href="{{ route('clients.show', $task->client) }}" style="color: var(--primary); font-weight: 500; text-decoration: none;">{{ $task->client->name }}</a>
                @else None @endif
            </div>
        </div>

        @if($task->description)
        <div class="mb-3">
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Description</div>
            <p style="margin: 4px 0 0; color: #4b5563;">{{ $task->description }}</p>
        </div>
        @endif

        <div>
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px;">Assigned To</div>
            @forelse($task->assignedUsers as $u)
                <span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">{{ $u->name }}</span>
            @empty
                <span style="color: #d1d5db;">No one assigned</span>
            @endforelse
        </div>
    </div>
</div>

<!-- Comments / Activity -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-chat-dots" style="color: var(--accent);"></i>
            <span style="font-weight: 700;">Comments ({{ $task->comments->count() }})</span>
        </div>
    </div>
    <div class="card-body" style="padding: 20px;">
        <!-- Add Comment -->
        <form method="POST" action="{{ route('comments.store') }}" class="mb-4">
            @csrf
            <input type="hidden" name="commentable_type" value="task">
            <input type="hidden" name="commentable_id" value="{{ $task->id }}">
            <div class="d-flex gap-3">
                <div style="width: 36px; height: 36px; background: var(--accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.7rem; color: var(--primary); flex-shrink: 0;">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                <div style="flex: 1;">
                    <textarea name="body" class="form-control" rows="2" placeholder="Add a comment or update..." required style="resize: none;"></textarea>
                    <button type="submit" class="btn btn-accent btn-sm mt-2"><i class="bi bi-send me-1"></i>Post</button>
                </div>
            </div>
        </form>

        <!-- Comments List -->
        @forelse($task->comments as $comment)
        <div class="d-flex gap-3 mb-3 pb-3" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
            <div style="width: 36px; height: 36px; background: rgba(48,58,80,0.06); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.7rem; color: var(--primary); flex-shrink: 0;">{{ strtoupper(substr($comment->user->name, 0, 2)) }}</div>
            <div style="flex: 1;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span style="font-weight: 600; font-size: 0.85rem; color: var(--primary);">{{ $comment->user->name }}</span>
                        <span style="font-size: 0.75rem; color: #9ca3af; margin-left: 8px;">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    @if($comment->user_id === auth()->id())
                    <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="d-inline" onsubmit="return confirm('Delete comment?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm" style="color: #d1d5db; padding: 2px 6px;" title="Delete"><i class="bi bi-trash" style="font-size: 0.75rem;"></i></button>
                    </form>
                    @endif
                </div>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #4b5563; line-height: 1.6;">{{ $comment->body }}</p>
            </div>
        </div>
        @empty
        <div class="text-center py-3" style="color: #d1d5db; font-size: 0.85rem;">No comments yet. Be the first to add one.</div>
        @endforelse
    </div>
</div>
@endsection
