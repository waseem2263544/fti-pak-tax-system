@extends('layouts.app')
@section('title', 'Journal Entries')
@section('page-title', 'Journal Entries')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">View and manage all journal entries.</p>
    </div>
    <a href="{{ route('accounting.journal-entries.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> New Journal Entry</a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.journal-entries.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="from" value="{{ request('from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="to" value="{{ request('to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                        <option value="reversed" {{ request('status') == 'reversed' ? 'selected' : '' }}>Reversed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                </div>
                @if(request()->hasAny(['from', 'to', 'status']))
                <div class="col-md-1">
                    <a href="{{ route('accounting.journal-entries.index') }}" class="btn btn-outline-primary w-100" title="Clear"><i class="bi bi-x-lg"></i></a>
                </div>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Journal Entries Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>JE #</th>
                    <th>Date</th>
                    <th>Narration</th>
                    <th>Source</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries ?? [] as $entry)
                <tr>
                    <td>
                        <a href="{{ route('accounting.journal-entries.show', $entry) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.88rem;">
                            {{ $entry->entry_number }}
                        </a>
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $entry->entry_date->format('M d, Y') }}</td>
                    <td style="font-size: 0.85rem;">{{ Str::limit($entry->narration, 50) }}</td>
                    <td>
                        @if($entry->source_type === 'manual')
                            <span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">Manual</span>
                        @elseif($entry->source_type === 'sales_invoice')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Sales Invoice</span>
                        @elseif($entry->source_type === 'purchase_invoice')
                            <span class="badge" style="background: #fef3c7; color: #92400e;">Purchase Invoice</span>
                        @elseif($entry->source_type === 'receipt')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Receipt</span>
                        @elseif($entry->source_type === 'payment')
                            <span class="badge" style="background: #fce7f3; color: #9d174d;">Payment</span>
                        @else
                            <span class="badge" style="background: #f3f4f6; color: #6b7280;">{{ ucfirst($entry->source_type ?? 'Manual') }}</span>
                        @endif
                    </td>
                    <td class="text-end" style="font-weight: 600; font-size: 0.85rem;">PKR {{ number_format($entry->lines->sum('debit'), 2) }}</td>
                    <td>
                        @if($entry->status === 'posted')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Posted</span>
                        @elseif($entry->status === 'draft')
                            <span class="badge" style="background: #fef3c7; color: #92400e;">Draft</span>
                        @else
                            <span class="badge" style="background: #fef2f2; color: #dc2626;">Reversed</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('accounting.journal-entries.show', $entry) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            @if($entry->status === 'draft')
                            <a href="{{ route('accounting.journal-entries.edit', $entry) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-journal-text" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No journal entries found. <a href="{{ route('accounting.journal-entries.create') }}" style="color: var(--primary); font-weight: 600;">Create one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($entries ?? collect(), 'links'))
<div class="mt-3">{{ $entries->links() }}</div>
@endif
@endsection
