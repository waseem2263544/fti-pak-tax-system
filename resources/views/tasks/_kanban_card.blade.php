<a href="{{ route('tasks.show', $task) }}" class="kanban-card priority-{{ $task->priority == 2 ? 'high' : ($task->priority == 1 ? 'medium' : 'low') }}">
    <div class="task-title">{{ Str::limit($task->title, 60) }}</div>
    <div class="task-meta">
        @if($task->client)
            <span><i class="bi bi-person me-1"></i>{{ Str::limit($task->client->name, 20) }}</span>
        @endif
        @if($task->due_date)
            @php $days = now()->startOfDay()->diffInDays($task->due_date, false); @endphp
            <span style="{{ $days < 0 ? 'color: #dc2626; font-weight: 600;' : ($days <= 3 ? 'color: #d97706;' : '') }}">
                <i class="bi bi-calendar me-1"></i>{{ $task->due_date->format('M d') }}
                @if($days < 0) ({{ abs($days) }}d late) @elseif($days == 0) (Today) @elseif($days <= 3) ({{ $days }}d) @endif
            </span>
        @endif
        @if($task->priority == 2)
            <span style="color: #dc2626; font-weight: 600;"><i class="bi bi-flag-fill me-1"></i>High</span>
        @endif
    </div>
    @if($task->assignedUsers->count())
    <div class="task-assignees">
        @foreach($task->assignedUsers->take(3) as $u)
        <div class="task-avatar" title="{{ $u->name }}">{{ strtoupper(substr($u->name, 0, 2)) }}</div>
        @endforeach
        @if($task->assignedUsers->count() > 3)
        <div class="task-avatar" style="background: var(--accent-glow); color: #5c6300;">+{{ $task->assignedUsers->count() - 3 }}</div>
        @endif
    </div>
    @endif
</a>
