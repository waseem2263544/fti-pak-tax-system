@extends('layouts.app')
@section('title', $proceeding->title)
@section('page-title', 'Proceeding Details')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h4 style="font-weight: 700; color: var(--primary); margin: 0;">{{ $proceeding->title }}</h4>
                <p class="mt-1 mb-0" style="color: #9ca3af; font-size: 0.85rem;">{{ $proceeding->client->name }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('proceedings.edit', $proceeding) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
                <form action="{{ route('proceedings.destroy', $proceeding) }}" method="POST" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-2 mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Stage</strong><br>
                @if($proceeding->stage == 'department') <span class="badge" style="background: #dbeafe; color: #1e40af;">Department</span>
                @elseif($proceeding->stage == 'commissioner_appeals') <span class="badge" style="background: #fef3c7; color: #92400e;">Commissioner Appeals</span>
                @else <span class="badge" style="background: #fce7f3; color: #9d174d;">Tribunal</span>
                @endif
            </div>
            <div class="col-md-2 mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Status</strong><br>
                @if($proceeding->status == 'pending') <span class="badge" style="background: #fef3c7; color: #92400e;">Pending</span>
                @elseif($proceeding->status == 'adjourned') <span class="badge" style="background: #dbeafe; color: #1e40af;">Adjourned</span>
                @elseif($proceeding->status == 'decided') <span class="badge" style="background: #d1fae5; color: #065f46;">Decided</span>
                @else <span class="badge" style="background: #fef2f2; color: #dc2626;">Appealed</span>
                @endif
            </div>
            <div class="col-md-2 mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Case No.</strong><br>{{ $proceeding->case_number ?? '-' }}</div>
            <div class="col-md-2 mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Tax Year</strong><br>{{ $proceeding->tax_year ?? '-' }}</div>
            <div class="col-md-2 mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Section</strong><br>{{ $proceeding->section ?? '-' }}</div>
            <div class="col-md-2 mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Assigned</strong><br>{{ $proceeding->assignedTo->name ?? '-' }}</div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Hearing Date</strong><br>{{ $proceeding->hearing_date?->format('M d, Y') ?? '-' }}</div>
            <div class="col-md-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Order Date</strong><br>{{ $proceeding->order_date?->format('M d, Y') ?? '-' }}</div>
        </div>
        @if($proceeding->description)
        <div class="mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Description</strong><p class="mt-1">{{ $proceeding->description }}</p></div>
        @endif
        @if($proceeding->outcome)
        <div class="mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Outcome</strong><p class="mt-1">{{ $proceeding->outcome }}</p></div>
        @endif
        @if($proceeding->notes)
        <div class="mb-3"><strong style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase;">Notes</strong><p class="mt-1">{{ $proceeding->notes }}</p></div>
        @endif
    </div>
</div>
<!-- Comments -->
<div class="card mt-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-chat-dots" style="color: var(--accent);"></i>
        <span style="font-weight: 700;">Comments ({{ $proceeding->comments->count() }})</span>
    </div>
    <div class="card-body" style="padding: 20px;">
        <form method="POST" action="{{ route('comments.store') }}" class="mb-4">
            @csrf
            <input type="hidden" name="commentable_type" value="proceeding">
            <input type="hidden" name="commentable_id" value="{{ $proceeding->id }}">
            <div class="d-flex gap-3">
                <div style="width: 36px; height: 36px; background: var(--accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.7rem; color: var(--primary); flex-shrink: 0;">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                <div style="flex: 1;">
                    <textarea name="body" class="form-control" rows="2" placeholder="Add a comment or update..." required style="resize: none;"></textarea>
                    <button type="submit" class="btn btn-accent btn-sm mt-2"><i class="bi bi-send me-1"></i>Post</button>
                </div>
            </div>
        </form>
        @forelse($proceeding->comments as $comment)
        <div class="d-flex gap-3 mb-3 pb-3" style="{{ !$loop->last ? 'border-bottom: 1px solid #f5f6f8;' : '' }}">
            <div style="width: 36px; height: 36px; background: rgba(48,58,80,0.06); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.7rem; color: var(--primary); flex-shrink: 0;">{{ strtoupper(substr($comment->user->name, 0, 2)) }}</div>
            <div style="flex: 1;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span style="font-weight: 600; font-size: 0.85rem; color: var(--primary);">{{ $comment->user->name }}</span>
                        <span style="font-size: 0.75rem; color: #9ca3af; margin-left: 8px;">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    @if($comment->user_id === auth()->id())
                    <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm" style="color: #d1d5db;"><i class="bi bi-trash" style="font-size: 0.75rem;"></i></button></form>
                    @endif
                </div>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #4b5563; line-height: 1.6;">{!! $comment->rendered_body !!}</p>
            </div>
        </div>
        @empty
        <div class="text-center py-3" style="color: #d1d5db; font-size: 0.85rem;">No comments yet.</div>
        @endforelse
    </div>
</div>

<a href="{{ route('proceedings.index', ['tab' => $proceeding->stage]) }}" class="btn btn-outline-primary mt-3"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection
