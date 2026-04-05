@extends('layouts.app')
@section('title', 'Scheduled Tasks')
@section('page-title', 'Scheduled Tasks')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Auto-create tasks for clients based on their active services on a schedule.</p>
    <a href="{{ route('scheduled-tasks.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1"></i> New Rule</a>
</div>

@forelse($automations as $auto)
<div class="card mb-3">
    <div class="card-body" style="padding: 20px 24px;">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-start gap-3">
                <div style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                    background: {{ $auto->is_active ? 'var(--accent-glow)' : 'rgba(156,163,175,0.1)' }};">
                    <i class="bi bi-clock-history" style="font-size: 1.2rem; color: {{ $auto->is_active ? '#8b9a00' : '#9ca3af' }};"></i>
                </div>
                <div>
                    <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">{{ $auto->name }}</div>
                    <div style="font-size: 0.8rem; color: #6b7280; margin-top: 2px;">
                        @if($auto->trigger_type == 'monthly')
                            <i class="bi bi-calendar-event me-1"></i>Every month on day <strong>{{ $auto->trigger_value }}</strong>
                        @elseif($auto->trigger_type == 'yearly')
                            <i class="bi bi-calendar-heart me-1"></i>Every year on <strong>{{ $auto->trigger_value }}</strong>
                        @elseif($auto->trigger_type == 'weekly')
                            <i class="bi bi-calendar-week me-1"></i>Every week on <strong>{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$auto->trigger_value ?? 0] }}</strong>
                        @else
                            <i class="bi bi-calendar-day me-1"></i>Every day
                        @endif
                        at <strong>{{ $auto->run_at_time ?? '08:00' }}</strong>
                        &middot; Looks for clients with <strong>{{ $auto->service->display_name ?? 'All' }}</strong>
                        &middot; Assigns to <strong>{{ $auto->assignedUser->name ?? '-' }}</strong>
                    </div>
                    <div style="font-size: 0.78rem; color: #9ca3af; margin-top: 4px;">
                        Task: "{{ $auto->task_template }}"
                        &middot; Due in {{ $auto->due_in_days ?? '?' }} days
                        &middot; Priority: {{ ['Low', 'Medium', 'High'][$auto->priority] }}
                        @if($auto->last_run_at) &middot; Last run: {{ $auto->last_run_at->diffForHumans() }} @endif
                    </div>
                    @if($auto->description)
                    <div style="font-size: 0.8rem; color: #6b7280; margin-top: 4px;">{{ $auto->description }}</div>
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form method="POST" action="{{ route('scheduled-tasks.run', $auto) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary" title="Run now" onclick="return confirm('Run this rule now? It will create tasks for all matching clients.')"><i class="bi bi-play-fill"></i></button>
                </form>
                <div class="form-check form-switch" style="margin: 0;">
                    <input class="form-check-input" type="checkbox" {{ $auto->is_active ? 'checked' : '' }}
                        onchange="toggleRule({{ $auto->id }})" style="cursor: pointer; width: 3em; height: 1.5em;">
                </div>
                <span class="badge" style="background: {{ $auto->is_active ? '#d1fae5' : '#f3f4f6' }}; color: {{ $auto->is_active ? '#065f46' : '#9ca3af' }}; min-width: 55px;">
                    {{ $auto->is_active ? 'Active' : 'Paused' }}
                </span>
                <a href="{{ route('scheduled-tasks.edit', $auto) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <form action="{{ route('scheduled-tasks.destroy', $auto) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this rule?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-clock-history" style="font-size: 3rem; color: #e5e7eb;"></i>
        <h5 class="mt-3" style="color: var(--primary); font-weight: 700;">No scheduled tasks yet</h5>
        <p style="color: #9ca3af; font-size: 0.85rem; max-width: 400px; margin: 0 auto 16px;">
            Create rules to auto-generate tasks. For example: "On the 1st of every month, create Sales Tax filing tasks for all clients with Sales Tax service."
        </p>
        <a href="{{ route('scheduled-tasks.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> Create First Rule</a>
    </div>
</div>
@endforelse

<div class="mt-3">{{ $automations->links() }}</div>
@endsection

@section('scripts')
<script>
function toggleRule(id) {
    fetch('/scheduled-tasks/' + id + '/toggle', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'}
    }).then(() => location.reload());
}
</script>
@endsection
