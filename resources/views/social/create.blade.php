@extends('layouts.app')
@section('title', 'New post - Social Studio')
@section('page-title', 'Social Studio')

@section('content')
@include('social._nav')

<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('social.store') }}" class="card">
            @csrf
            <input type="hidden" name="idea_id" value="{{ old('idea_id', $idea?->id) }}">
            <div class="card-body">
                <h6 class="mb-3">What should Claude write?</h6>

                <label class="form-label fw-semibold" style="font-size:.85rem">Format <span class="text-muted fw-normal">- pick several to get one of each on the same topic</span></label>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @php $chosen = old('formats', [$idea?->format ?: 'carousel']); @endphp
                    @foreach([
                        'image' => ['bi-image', 'Image', 'One slide'],
                        'carousel' => ['bi-collection', 'Carousel', '4-8 slides to swipe'],
                        'video' => ['bi-camera-reels', 'Video', '20-35 s motion graphic'],
                    ] as $k => [$icon, $label, $hint])
                    <label class="sx-opt">
                        <input type="checkbox" name="formats[]" value="{{ $k }}" @checked(in_array($k, $chosen))>
                        <span><i class="bi {{ $icon }}"></i> <b>{{ $label }}</b><small>{{ $hint }}</small></span>
                    </label>
                    @endforeach
                </div>
                @error('formats')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.85rem">Topic</label>
                    <textarea name="topic" rows="2" class="form-control" required
                        placeholder="e.g. Income tax return deadline for salaried people - what happens if you file late">{{ old('topic', $idea?->topic) }}</textarea>
                    @error('topic')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.85rem">Audience</label>
                    <input name="audience" class="form-control" value="{{ old('audience', $idea?->audience) }}" placeholder="{{ $audience }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.85rem">Extra instructions <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="brief" rows="3" class="form-control"
                        placeholder="Angle, figures to use, things to avoid, an offer to mention…">{{ old('brief', $idea?->angle) }}</textarea>
                </div>
                <div class="mb-3" style="max-width: 220px;">
                    <label class="form-label fw-semibold" style="font-size:.85rem">Planned for <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="date" name="planned_for" class="form-control" value="{{ old('planned_for') }}">
                </div>

                <button class="btn btn-accent" @disabled(!$hasKey)><i class="bi bi-stars me-1"></i> Write it</button>
                <span class="text-muted ms-2" style="font-size:.8rem">Claude checks current FBR facts on the web first; allow 1-2 minutes.</span>
                @if(!$hasKey)<div class="text-danger small mt-2">Add the Claude API key in Settings first.</div>@endif
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">From the idea bank</h6>
                    <a href="{{ route('social.ideas') }}" class="small">Manage</a>
                </div>
                @forelse($ideas as $i)
                    <a href="{{ route('social.create', ['idea' => $i->id]) }}" class="sx-idea @if($idea?->id === $i->id) active @endif">
                        <div class="fw-semibold">{{ $i->topic }}</div>
                        @if($i->angle)<div class="text-muted">{{ $i->angle }}</div>@endif
                        @if($i->format)<span class="badge bg-light text-dark border mt-1">{{ $i->format }}</span>@endif
                    </a>
                @empty
                    <p class="text-muted small mb-0">No ideas waiting. <a href="{{ route('social.ideas') }}">Ask Claude for some</a>.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .sx-opt { cursor: pointer; }
    .sx-opt input { position: absolute; opacity: 0; }
    .sx-opt span { display: flex; flex-direction: column; gap: 2px; min-width: 150px; padding: 10px 14px; border: 1px solid var(--border-strong);
        border-radius: 10px; font-size: .85rem; transition: all .12s; }
    .sx-opt span small { color: var(--text-muted); font-size: .74rem; }
    .sx-opt input:checked + span { border-color: var(--accent, #1982C3); box-shadow: 0 0 0 3px rgba(25,130,195,.15); background: rgba(25,130,195,.04); }
    .sx-opt input:focus-visible + span { outline: 2px solid var(--accent, #1982C3); }
    .sx-idea { display: block; padding: 9px 10px; margin: 0 -10px; border-radius: 8px; text-decoration: none; color: inherit; font-size: .82rem; }
    .sx-idea:hover, .sx-idea.active { background: var(--n-50); }
</style>
@endsection
