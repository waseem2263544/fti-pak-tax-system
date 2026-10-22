@extends('layouts.app')
@section('title', 'Ideas - Social Studio')
@section('page-title', 'Social Studio')

@section('content')
@include('social._nav')

<div class="row g-4">
    <div class="col-lg-8">
        <div class="d-flex gap-1 mb-3">
            @foreach(['new' => 'Waiting', 'used' => 'Used', 'dismissed' => 'Dismissed'] as $k => $label)
                <a href="{{ route('social.ideas', ['status' => $k]) }}" class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-outline-primary' }}">
                    {{ $label }} <span class="badge bg-secondary ms-1">{{ $counts[$k] ?? 0 }}</span></a>
            @endforeach
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <tbody>
                    @forelse($ideas as $idea)
                        <tr>
                            <td>
                                <div class="fw-semibold" style="font-size:.88rem">{{ $idea->topic }}</div>
                                @if($idea->angle)<div class="text-muted" style="font-size:.8rem">{{ $idea->angle }}</div>@endif
                                <div class="mt-1">
                                    @if($idea->format)<span class="badge bg-light text-dark border">{{ $idea->format }}</span>@endif
                                    @if($idea->audience)<span class="badge bg-light text-dark border">{{ $idea->audience }}</span>@endif
                                    @if($idea->source === 'ai')<span class="badge bg-light text-primary border"><i class="bi bi-stars"></i> Claude</span>@endif
                                </div>
                            </td>
                            <td class="text-end text-nowrap" style="width:1%">
                                @if($idea->status === 'new')
                                    <a href="{{ route('social.create', ['idea' => $idea->id]) }}" class="btn btn-sm btn-accent">Make post</a>
                                @elseif($idea->post)
                                    <a href="{{ route('social.show', $idea->post) }}" class="btn btn-sm btn-outline-primary">Open post</a>
                                @endif
                                @if($idea->status !== 'used')
                                <form method="POST" action="{{ route('social.ideas.dismiss', $idea) }}" class="d-inline">@csrf
                                    <button class="btn btn-sm btn-outline-secondary" title="{{ $idea->status === 'dismissed' ? 'Restore' : 'Dismiss' }}">
                                        <i class="bi {{ $idea->status === 'dismissed' ? 'bi-arrow-counterclockwise' : 'bi-x-lg' }}"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-muted text-center py-4">No ideas here.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $ideas->links() }}</div>
    </div>

    <div class="col-lg-4">
        <form method="POST" action="{{ route('social.ideas.suggest') }}" class="card mb-3" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='<span class=&quot;spinner-border spinner-border-sm me-1&quot;></span> Researching… (about a minute)'">
            @csrf
            <div class="card-body">
                <h6><i class="bi bi-stars me-1"></i> Ask Claude for ideas</h6>
                <p class="text-muted small">Claude looks up what is due in the Pakistani tax calendar and suggests topics, skipping ones already used.</p>
                <input name="focus" class="form-control form-control-sm mb-2" placeholder="Focus (optional) - e.g. sales tax, salaried people">
                <div class="d-flex gap-2">
                    <select name="count" class="form-select form-select-sm" style="width:auto">
                        @foreach([5, 10, 15, 20] as $n)<option value="{{ $n }}" @selected($n === 10)>{{ $n }} ideas</option>@endforeach
                    </select>
                    <button class="btn btn-sm btn-accent flex-fill">Suggest</button>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('social.ideas.store') }}" class="card">
            @csrf
            <div class="card-body">
                <h6>Add an idea</h6>
                <textarea name="topic" rows="2" class="form-control form-control-sm mb-2" placeholder="Topic" required></textarea>
                <input name="angle" class="form-control form-control-sm mb-2" placeholder="Angle or hook (optional)">
                <input name="audience" class="form-control form-control-sm mb-2" placeholder="Audience (optional)">
                <select name="format" class="form-select form-select-sm mb-2">
                    <option value="">Any format</option>
                    @foreach(\App\Models\SocialPost::FORMATS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                </select>
                <button class="btn btn-sm btn-primary w-100">Add</button>
            </div>
        </form>
    </div>
</div>
@endsection
