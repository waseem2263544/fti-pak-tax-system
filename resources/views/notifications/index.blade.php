@extends('layouts.app')
@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5>All Notifications</h5>
    <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
        @csrf
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check-all"></i> Mark All Read</button>
    </form>
</div>

@forelse($notifications as $notification)
<div class="card mb-2 {{ !$notification->is_read ? 'border-start border-primary border-3' : '' }}">
    <div class="card-body py-2 px-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <strong>{{ $notification->title }}</strong>
                @if($notification->priority == 'high')
                    <span class="badge bg-danger ms-1">High</span>
                @elseif($notification->priority == 'medium')
                    <span class="badge bg-warning ms-1">Medium</span>
                @endif
                <p class="mb-0 text-muted small">{{ $notification->message }}</p>
            </div>
            <div class="text-end">
                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                @if(!$notification->is_read)
                <br><a href="#" onclick="markRead({{ $notification->id }})" class="small">Mark read</a>
                @endif
            </div>
        </div>
    </div>
</div>
@empty
<div class="text-center text-muted py-5">No notifications</div>
@endforelse

<div class="mt-3">{{ $notifications->links() }}</div>
@endsection

@section('scripts')
<script>
function markRead(id) {
    fetch('/notifications/' + id + '/read', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'}
    }).then(() => location.reload());
}
</script>
@endsection
