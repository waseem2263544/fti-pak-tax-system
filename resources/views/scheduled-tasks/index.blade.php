@extends('layouts.app')
@section('title', 'Scheduled Tasks')
@section('page-title', 'Scheduled Tasks')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Configure rules to auto-create tasks based on deadlines, schedules, or events.</p>
    <a href="{{ route('scheduled-tasks.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1"></i> New Automation</a>
</div>

@forelse($automations as $auto)
<div class="card mb-3">
    <div class="card-body" style="padding: 20px 24px;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
                    background: {{ $auto->is_active ? 'var(--accent-glow)' : 'rgba(156,163,175,0.1)' }};">
                    <i class="bi bi-lightning-charge-fill" style="font-size: 1.2rem; color: {{ $auto->is_active ? '#8b9a00' : '#9ca3af' }};"></i>
                </div>
                <div>
                    <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">{{ $auto->name }}</div>
                    <div style="font-size: 0.8rem; color: #9ca3af;">
                        @if($auto->trigger_type == 'deadline_based') <i class="bi bi-calendar-event me-1"></i>Deadline Based
                        @elseif($auto->trigger_type == 'date_based') <i class="bi bi-calendar-date me-1"></i>Date Based
                        @elseif($auto->trigger_type == 'recurring') <i class="bi bi-arrow-repeat me-1"></i>Recurring
                        @else <i class="bi bi-broadcast me-1"></i>Event Based
                        @endif
                        @if($auto->trigger_value) &middot; {{ $auto->trigger_value }} @endif
                        @if($auto->service) &middot; {{ $auto->service->display_name }} @endif
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" {{ $auto->is_active ? 'checked' : '' }}
                        onchange="toggleAutomation({{ $auto->id }})" style="cursor: pointer; width: 3em; height: 1.5em;">
                </div>
                <span class="badge" style="background: {{ $auto->is_active ? '#d1fae5' : '#f3f4f6' }}; color: {{ $auto->is_active ? '#065f46' : '#9ca3af' }};">
                    {{ $auto->is_active ? 'Active' : 'Paused' }}
                </span>
                <div class="d-flex gap-1">
                    <a href="{{ route('scheduled-tasks.edit', $auto) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('scheduled-tasks.destroy', $auto) }}" method="POST" onsubmit="return confirm('Delete this automation?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        @if($auto->description)
        <p style="margin: 10px 0 0 57px; font-size: 0.82rem; color: #6b7280;">{{ $auto->description }}</p>
        @endif
        <div style="margin: 8px 0 0 57px; font-size: 0.75rem; color: #d1d5db;">
            Creates: "{{ $auto->task_template }}"
            &middot; Priority: {{ ['Low', 'Medium', 'High'][$auto->priority] }}
            @if($auto->last_run_at) &middot; Last run: {{ $auto->last_run_at->diffForHumans() }} @endif
        </div>
    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-lightning-charge" style="font-size: 3rem; color: #e5e7eb;"></i>
        <h5 class="mt-3" style="color: var(--primary); font-weight: 700;">No automations yet</h5>
        <p style="color: #9ca3af; font-size: 0.85rem;">Create your first automation to auto-generate tasks based on deadlines or schedules.</p>
        <a href="{{ route('scheduled-tasks.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> Create Automation</a>
    </div>
</div>
@endforelse

<div class="mt-3">{{ $automations->links() }}</div>
@endsection

@section('scripts')
<script>
function toggleAutomation(id) {
    fetch('/scheduled-tasks/' + id + '/toggle', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'}
    }).then(() => location.reload());
}
</script>
@endsection
