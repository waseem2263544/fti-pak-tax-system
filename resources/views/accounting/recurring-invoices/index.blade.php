@extends('layouts.app')
@section('title', 'Recurring Invoices')
@section('page-title', 'Recurring Invoices')

@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <div style="color:#6b7280; font-size:0.85rem;">Templates for retainer / repeat billing. Generate creates a real invoice and advances the schedule.</div>
    <div class="d-flex gap-2">
        @if($dueCount > 0)
        <form method="POST" action="{{ route('accounting.recurring-invoices.generate-due') }}" onsubmit="return confirm('Generate invoices for all {{ $dueCount }} due template(s)?')">
            @csrf
            <button class="btn btn-primary btn-sm"><i class="bi bi-lightning-charge me-1"></i>Generate {{ $dueCount }} due</button>
        </form>
        @endif
        <a href="{{ route('accounting.recurring-invoices.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1"></i>New Template</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Client</th><th>Frequency</th><th>Next Date</th><th>Reference</th><th class="text-end">Lines</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($templates as $t)
                <tr>
                    <td style="font-weight:600;">{{ $t->client->name ?? '—' }}</td>
                    <td>{{ ucfirst($t->frequency) }}</td>
                    <td>
                        {{ optional($t->next_date)->format('d M Y') }}
                        @if($t->isDue())<span class="badge" style="background:#fef3c7; color:#92400e; margin-left:4px;">Due</span>@endif
                    </td>
                    <td style="font-size:0.82rem; color:#6b7280;">{{ $t->reference ?: '—' }}</td>
                    <td class="text-end">{{ is_array($t->items) ? count($t->items) : 0 }}</td>
                    <td>@if($t->is_active)<span class="badge" style="background:#d1fae5; color:#065f46;">Active</span>@else<span class="badge" style="background:#f3f4f6; color:#6b7280;">Paused</span>@endif</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('accounting.recurring-invoices.generate', $t) }}" class="d-inline" onsubmit="return confirm('Generate an invoice now?')">
                            @csrf
                            <button class="btn btn-outline-primary btn-sm" title="Generate now"><i class="bi bi-lightning-charge"></i></button>
                        </form>
                        <a href="{{ route('accounting.recurring-invoices.edit', $t) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('accounting.recurring-invoices.destroy', $t) }}" class="d-inline" onsubmit="return confirm('Delete this template?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-5" style="color:#9ca3af;"><i class="bi bi-arrow-repeat" style="font-size:2rem; opacity:0.3; display:block; margin-bottom:8px;"></i>No recurring templates yet. <a href="{{ route('accounting.recurring-invoices.create') }}">Create one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
