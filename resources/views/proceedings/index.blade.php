@extends('layouts.app')
@section('title', 'Pending Proceedings')
@section('page-title', 'Pending Proceedings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Track cases across Department, Commissioner Appeals, and Tribunal stages.</p>
    <a href="{{ route('proceedings.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1"></i> New Proceeding</a>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-0" style="border-bottom: 2px solid #e8eaed;">
    <li class="nav-item">
        <a class="nav-link {{ $tab == 'department' ? 'active' : '' }}" href="{{ route('proceedings.index', ['tab' => 'department']) }}"
           style="{{ $tab == 'department' ? 'color: var(--primary); font-weight: 700; border-bottom: 3px solid var(--accent);' : 'color: #9ca3af;' }} font-size: 0.9rem; padding: 12px 24px;">
            <i class="bi bi-building me-1"></i> Department
            <span class="badge" style="background: {{ $tab == 'department' ? 'var(--accent)' : '#e5e7eb' }}; color: {{ $tab == 'department' ? 'var(--primary)' : '#6b7280' }}; margin-left: 6px;">{{ $department->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab == 'commissioner_appeals' ? 'active' : '' }}" href="{{ route('proceedings.index', ['tab' => 'commissioner_appeals']) }}"
           style="{{ $tab == 'commissioner_appeals' ? 'color: var(--primary); font-weight: 700; border-bottom: 3px solid var(--accent);' : 'color: #9ca3af;' }} font-size: 0.9rem; padding: 12px 24px;">
            <i class="bi bi-bank me-1"></i> Commissioner Appeals
            <span class="badge" style="background: {{ $tab == 'commissioner_appeals' ? 'var(--accent)' : '#e5e7eb' }}; color: {{ $tab == 'commissioner_appeals' ? 'var(--primary)' : '#6b7280' }}; margin-left: 6px;">{{ $commissioner->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab == 'tribunal' ? 'active' : '' }}" href="{{ route('proceedings.index', ['tab' => 'tribunal']) }}"
           style="{{ $tab == 'tribunal' ? 'color: var(--primary); font-weight: 700; border-bottom: 3px solid var(--accent);' : 'color: #9ca3af;' }} font-size: 0.9rem; padding: 12px 24px;">
            <i class="bi bi-bank2 me-1"></i> Tribunal
            <span class="badge" style="background: {{ $tab == 'tribunal' ? 'var(--accent)' : '#e5e7eb' }}; color: {{ $tab == 'tribunal' ? 'var(--primary)' : '#6b7280' }}; margin-left: 6px;">{{ $tribunal->count() }}</span>
        </a>
    </li>
</ul>

<!-- Tab Content -->
<div class="card" style="border-top-left-radius: 0; border-top-right-radius: 0;">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Case</th>
                    <th>Client</th>
                    <th>Case No.</th>
                    <th>Tax Year</th>
                    <th>Section</th>
                    <th>Hearing Date</th>
                    <th>Status</th>
                    <th>Assigned</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                    $items = ${$tab == 'commissioner_appeals' ? 'commissioner' : $tab};
                @endphp
                @forelse($items as $proceeding)
                <tr>
                    <td>
                        <a href="{{ route('proceedings.show', $proceeding) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">{{ $proceeding->title }}</a>
                    </td>
                    <td>{{ $proceeding->client->name }}</td>
                    <td style="font-family: monospace; font-size: 0.82rem;">{{ $proceeding->case_number ?? '-' }}</td>
                    <td>{{ $proceeding->tax_year ?? '-' }}</td>
                    <td>{{ $proceeding->section ?? '-' }}</td>
                    <td>
                        @if($proceeding->hearing_date)
                            <span style="{{ $proceeding->hearing_date->isPast() ? 'color: #ef4444;' : '' }}">
                                {{ $proceeding->hearing_date->format('M d, Y') }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($proceeding->status == 'pending')
                            <span class="badge" style="background: #fef3c7; color: #92400e;">Pending</span>
                        @elseif($proceeding->status == 'adjourned')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Adjourned</span>
                        @elseif($proceeding->status == 'decided')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Decided</span>
                        @else
                            <span class="badge" style="background: #fef2f2; color: #dc2626;">Appealed</span>
                        @endif
                    </td>
                    <td>{{ $proceeding->assignedTo->name ?? '-' }}</td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('proceedings.edit', $proceeding) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('proceedings.destroy', $proceeding) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this proceeding?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-5" style="color: #9ca3af;">No proceedings at this stage.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
