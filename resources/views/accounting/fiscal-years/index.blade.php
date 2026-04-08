@extends('layouts.app')
@section('title', 'Fiscal Years')
@section('page-title', 'Fiscal Years')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Manage fiscal year periods for your chart of accounts.</p>
    </div>
    <a href="{{ route('accounting.fiscal-years.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> New Fiscal Year</a>
</div>

<!-- Fiscal Years Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fiscalYears ?? [] as $fy)
                <tr>
                    <td style="font-weight: 600; font-size: 0.88rem; color: var(--primary);">{{ $fy->name }}</td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ \Carbon\Carbon::parse($fy->start_date)->format('M d, Y') }}</td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ \Carbon\Carbon::parse($fy->end_date)->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            @if($fy->is_active)
                                <span class="badge" style="background: #d1fae5; color: #065f46;">Active</span>
                            @endif
                            @if($fy->is_closed)
                                <span class="badge" style="background: #fef2f2; color: #991b1b;">Closed</span>
                            @endif
                            @if(!$fy->is_active && !$fy->is_closed)
                                <span class="badge" style="background: #f3f4f6; color: #6b7280;">Inactive</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            @if(!$fy->is_closed)
                            <a href="{{ route('accounting.fiscal-years.edit', $fy) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endif
                            @if(!$fy->is_active && !$fy->is_closed)
                            <form action="{{ route('accounting.fiscal-years.destroy', $fy) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-calendar3" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No fiscal years found. <a href="{{ route('accounting.fiscal-years.create') }}" style="color: var(--primary); font-weight: 600;">Create one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
