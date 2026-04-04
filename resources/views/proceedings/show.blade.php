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
<a href="{{ route('proceedings.index', ['tab' => $proceeding->stage]) }}" class="btn btn-outline-primary mt-3"><i class="bi bi-arrow-left me-1"></i>Back</a>
@endsection
