@extends('layouts.app')
@section('title', 'Tax News')
@section('page-title', 'Tax News')

@section('content')
<!-- Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <div class="d-flex justify-content-between align-items-center">
            <form method="GET" action="{{ route('news.index') }}" class="d-flex gap-2 align-items-center flex-grow-1">
                <div style="position: relative; flex: 1; max-width: 300px;">
                    <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search news..." style="padding-left: 36px;">
                </div>
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 180px;">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
                <select name="source" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 180px;">
                    <option value="">All Sources</option>
                    @foreach($sources as $src)
                        <option value="{{ $src }}" {{ request('source') == $src ? 'selected' : '' }}>{{ $src }}</option>
                    @endforeach
                </select>
                @if(request()->hasAny(['search', 'category', 'source']))
                    <a href="{{ route('news.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
            <a href="{{ route('news.fetch') }}" class="btn btn-accent btn-sm ms-3"><i class="bi bi-cloud-download me-1"></i>Fetch Now</a>
        </div>
    </div>
</div>

<!-- Pinned Articles -->
@php $pinned = $news->where('is_pinned', true); @endphp
@if($pinned->count())
<div class="mb-4">
    @foreach($pinned as $article)
    <div class="card mb-2" style="border-left: 4px solid var(--accent);">
        <div class="card-body" style="padding: 16px 20px;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-pin-fill" style="color: var(--accent); font-size: 0.85rem;"></i>
                        <span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary); font-size: 0.7rem;">{{ $article->category }}</span>
                        <span style="font-size: 0.72rem; color: #9ca3af;">{{ $article->source }} &middot; {{ $article->published_at->diffForHumans() }}</span>
                    </div>
                    <a href="{{ $article->url }}" target="_blank" style="color: var(--primary); font-weight: 700; font-size: 0.95rem; text-decoration: none; line-height: 1.4;">{{ $article->title }} <i class="bi bi-box-arrow-up-right" style="font-size: 0.7rem;"></i></a>
                    @if($article->summary)
                    <p style="color: #6b7280; font-size: 0.82rem; margin: 6px 0 0; line-height: 1.5;">{{ $article->summary }}</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('news.pin', $article) }}">@csrf
                    <button class="btn btn-sm" style="color: var(--accent);" title="Unpin"><i class="bi bi-pin-fill"></i></button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<!-- News List -->
@forelse($news->where('is_pinned', false) as $article)
<div class="card mb-2">
    <div class="card-body" style="padding: 16px 20px;">
        <div class="d-flex justify-content-between align-items-start">
            <div style="flex: 1;">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge" style="background:
                        {{ $article->category == 'FBR Updates' ? '#dbeafe' :
                           ($article->category == 'Income Tax' ? 'rgba(48,58,80,0.06)' :
                           ($article->category == 'Sales Tax' ? 'var(--accent-glow)' :
                           ($article->category == 'Judgments' ? '#fef3c7' :
                           ($article->category == 'Policy & Budget' ? '#fce7f3' : '#f3f4f6')))) }};
                        color:
                        {{ $article->category == 'FBR Updates' ? '#1e40af' :
                           ($article->category == 'Income Tax' ? 'var(--primary)' :
                           ($article->category == 'Sales Tax' ? '#5c6300' :
                           ($article->category == 'Judgments' ? '#92400e' :
                           ($article->category == 'Policy & Budget' ? '#9d174d' : '#6b7280')))) }};
                        font-size: 0.68rem;">{{ $article->category }}</span>
                    <span style="font-size: 0.72rem; color: #9ca3af;">{{ $article->source }}</span>
                    <span style="font-size: 0.72rem; color: #d1d5db;">&middot;</span>
                    <span style="font-size: 0.72rem; color: #9ca3af;">{{ $article->published_at->diffForHumans() }}</span>
                    @if($article->also_reported_by && count($article->also_reported_by))
                    <span style="font-size: 0.68rem; color: #9ca3af; background: #f3f4f6; padding: 2px 8px; border-radius: 4px;">
                        Also: {{ implode(', ', $article->also_reported_by) }}
                    </span>
                    @endif
                </div>
                <a href="{{ $article->url }}" target="_blank" style="color: var(--primary); font-weight: 600; font-size: 0.9rem; text-decoration: none; line-height: 1.4;">
                    {{ $article->title }} <i class="bi bi-box-arrow-up-right" style="font-size: 0.65rem; color: #9ca3af;"></i>
                </a>
                @if($article->summary)
                <p style="color: #6b7280; font-size: 0.8rem; margin: 4px 0 0; line-height: 1.5;">{{ Str::limit($article->summary, 200) }}</p>
                @endif
            </div>
            <div class="d-flex gap-1 ms-3">
                <form method="POST" action="{{ route('news.pin', $article) }}">@csrf
                    <button class="btn btn-sm btn-outline-primary" title="Pin"><i class="bi bi-pin"></i></button>
                </form>
                <form method="POST" action="{{ route('news.destroy', $article) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-secondary" title="Remove"><i class="bi bi-x-lg"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-newspaper" style="font-size: 2.5rem; color: #e5e7eb;"></i>
        <h5 class="mt-3" style="color: var(--primary);">No tax news yet</h5>
        <p style="color: #9ca3af; font-size: 0.85rem;">Click "Fetch Now" to get the latest tax news from Pakistani sources.</p>
        <a href="{{ route('news.fetch') }}" class="btn btn-accent"><i class="bi bi-cloud-download me-1"></i>Fetch Now</a>
    </div>
</div>
@endforelse

<div class="mt-3">{{ $news->links() }}</div>
@endsection
