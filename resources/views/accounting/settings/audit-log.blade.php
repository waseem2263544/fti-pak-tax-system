@extends('layouts.app')
@section('title', 'Audit Log')
@section('page-title', 'Accounting Audit Log')

@section('content')
@php
    $actionColors = ['created' => ['#065f46', '#d1fae5'], 'updated' => ['#92400e', '#fef3c7'], 'deleted' => ['#dc2626', '#fef2f2']];
@endphp

<div class="card mb-4">
    <div class="card-body" style="padding: 16px;">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">All actions</option>
                    @foreach(['created', 'updated', 'deleted'] as $a)
                        <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Document</label>
                <select name="model" class="form-select">
                    <option value="">All documents</option>
                    @foreach($models as $key => $label)
                        <option value="{{ $key }}" {{ request('model') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-accent w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Document</th><th>Reference</th><th>Changes</th></tr></thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="font-size: 0.8rem; color: #6b7280; white-space: nowrap;">{{ optional($log->created_at)->format('d M Y H:i') }}</td>
                    <td style="font-size: 0.82rem;">{{ $log->user_name ?? '—' }}</td>
                    <td>@php $c = $actionColors[$log->action] ?? ['#6b7280', '#f3f4f6']; @endphp<span class="badge" style="color: {{ $c[0] }}; background: {{ $c[1] }};">{{ ucfirst($log->action) }}</span></td>
                    <td style="font-size: 0.82rem;">{{ $models[$log->model_type] ?? $log->model_type }}</td>
                    <td style="font-size: 0.82rem; font-weight: 600;">{{ $log->label ?? '#' . $log->model_id }}</td>
                    <td style="font-size: 0.78rem; color: #6b7280;">
                        @if(is_array($log->changes))
                            @foreach(array_slice($log->changes, 0, 4, true) as $field => $diff)
                                <div><strong>{{ $field }}</strong>: {{ \Illuminate\Support\Str::limit((string)($diff['from'] ?? ''), 18) }} → {{ \Illuminate\Support\Str::limit((string)($diff['to'] ?? ''), 18) }}</div>
                            @endforeach
                            @if(count($log->changes) > 4)<div>+{{ count($log->changes) - 4 }} more…</div>@endif
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-5" style="color: #9ca3af;"><i class="bi bi-clock-history" style="font-size: 2rem; opacity: 0.3; display:block; margin-bottom:8px;"></i>No audit entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-body">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
