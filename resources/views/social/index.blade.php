@extends('layouts.app')
@section('title', 'Social Studio')
@section('page-title', 'Social Studio')

@section('content')
@include('social._nav')

@if(!$hasKey)
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-key"></i>
    <div>No Claude API key yet, so posts cannot be written.
        @if(Auth::user()->hasRole('admin'))<a href="{{ route('social.settings') }}">Add it in Settings</a>.@else Ask an administrator to add it.@endif
    </div>
</div>
@endif

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="d-flex flex-wrap gap-1">
        <a href="{{ route('social.index', array_filter(['format' => $format])) }}"
           class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-primary' }}">Active</a>
        @foreach(\App\Models\SocialPost::STATUSES as $key => $label)
            @php $n = $counts[$key] ?? 0; @endphp
            @if($n || $status === $key)
            <a href="{{ route('social.index', array_filter(['status' => $key, 'format' => $format])) }}"
               class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-primary' }}">
                {{ $label }} <span class="badge bg-{{ \App\Models\SocialPost::STATUS_COLORS[$key] }} ms-1">{{ $n }}</span>
            </a>
            @endif
        @endforeach
    </div>
    <div class="d-flex gap-2 align-items-center">
        <select class="form-select form-select-sm" style="width:auto" onchange="location=this.value">
            <option value="{{ route('social.index', array_filter(['status' => $status])) }}">All formats</option>
            @foreach(\App\Models\SocialPost::FORMATS as $k => $label)
                <option value="{{ route('social.index', array_filter(['status' => $status, 'format' => $k])) }}" @selected($format === $k)>{{ $label }}</option>
            @endforeach
        </select>
        <a href="{{ route('social.create') }}" class="btn btn-accent btn-sm text-nowrap"><i class="bi bi-stars me-1"></i> New post</a>
    </div>
</div>

@if($posts->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-phone" style="font-size: 2.2rem; color: var(--text-faint);"></i>
            <h6 class="mt-3 mb-1">Nothing here yet</h6>
            <p class="text-muted mb-3" style="font-size:.88rem">Ask Claude for a post, or fill the idea bank and let the daily run draft them.</p>
            <a href="{{ route('social.create') }}" class="btn btn-accent btn-sm">New post</a>
            <a href="{{ route('social.ideas') }}" class="btn btn-outline-primary btn-sm">Idea bank ({{ $ideasCount }})</a>
        </div>
    </div>
@else
<div class="sx-grid">
    @foreach($posts as $post)
    <a href="{{ route('social.show', $post) }}" class="sx-card">
        <div class="sx-thumb" data-post='@json($post->status !== 'generating' && $post->slides && !$post->assets ? $post->renderPayload() : null)'>
            @if(!empty($post->assets))
                <img src="{{ route('social.asset', [$post, 0]) }}" alt="" loading="lazy">
            @elseif($post->status === 'generating')
                <div class="sx-thumb-state"><div class="spinner-border spinner-border-sm"></div><span>Claude is writing…</span></div>
            @elseif($post->status === 'failed')
                <div class="sx-thumb-state text-danger"><i class="bi bi-exclamation-triangle"></i><span>Failed</span></div>
            @endif
            <span class="sx-format"><i class="bi {{ \App\Models\SocialPost::FORMAT_ICONS[$post->format] }}"></i>
                {{ $post->format === 'carousel' && $post->slides ? count($post->slides) : '' }}
                {{ $post->isVideo() && $post->slides ? round($post->duration()) . 's' : '' }}</span>
        </div>
        <div class="sx-meta">
            <span class="badge bg-{{ \App\Models\SocialPost::STATUS_COLORS[$post->status] ?? 'secondary' }}">{{ \App\Models\SocialPost::STATUSES[$post->status] ?? $post->status }}</span>
            @if($post->status === 'approved' && $post->render_status && $post->render_status !== 'done')
                <span class="badge bg-light text-dark border">{{ $post->render_status === 'failed' ? 'render failed' : 'rendering' }}</span>
            @endif
            <div class="sx-title">{{ $post->title ?: $post->topic }}</div>
            <div class="sx-date">{{ $post->planned_for ? 'For ' . $post->planned_for->format('j M') : $post->created_at->format('j M, g:i a') }}</div>
        </div>
    </a>
    @endforeach
</div>
<div class="mt-3">{{ $posts->links() }}</div>
@endif
@endsection

@section('styles')
<style>
    .sx-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 18px; }
    .sx-card { display: block; text-decoration: none; color: inherit; background: var(--surface); border: 1px solid var(--border);
        border-radius: 14px; overflow: hidden; transition: border-color .15s, box-shadow .15s; }
    .sx-card:hover { border-color: var(--border-strong); box-shadow: 0 6px 20px rgba(20,24,31,.08); }
    .sx-thumb { position: relative; aspect-ratio: 9/16; background: #0B2744; overflow: hidden; }
    .sx-thumb > img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sx-thumb .sx-frame { border-radius: 0; box-shadow: none; }
    .sx-thumb-state { position: absolute; inset: 0; display: flex; flex-direction: column; gap: 8px; align-items: center; justify-content: center;
        color: rgba(255,255,255,.75); font-size: .8rem; }
    .sx-format { position: absolute; top: 8px; right: 8px; z-index: 2; background: rgba(6,27,49,.72); color: #fff; font-size: .7rem;
        padding: 2px 8px; border-radius: 999px; display: inline-flex; gap: 4px; align-items: center; }
    .sx-meta { padding: 10px 12px 12px; }
    .sx-title { font-weight: 600; font-size: .84rem; line-height: 1.3; margin-top: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .sx-date { font-size: .72rem; color: var(--text-muted); margin-top: 4px; }
</style>
@endsection

@section('scripts')
@include('social._renderer')
<script>
    // Live thumbnails for posts that have no exported image yet, drawn when scrolled into view.
    const io = new IntersectionObserver(entries => entries.forEach(e => {
        if (!e.isIntersecting) return;
        io.unobserve(e.target);
        const post = JSON.parse(e.target.dataset.post);
        const box = document.createElement('div');
        e.target.prepend(box);
        socialMount(box, post, { mode: 'static', only: 0 });
    }), { rootMargin: '200px' });
    document.querySelectorAll('.sx-thumb').forEach(t => { if (t.dataset.post && t.dataset.post !== 'null') io.observe(t); });

    // Posts waiting for Claude: start them from here (one at a time, the server
    // lock stops doubles), then refresh. Cron finishes any that get cut off.
    const waiting = @json($posts->where('status', 'generating')->pluck('id')->values());
    if (waiting.length) {
        const token = '{{ csrf_token() }}';
        (async () => {
            for (const id of waiting) {
                try {
                    await fetch('{{ url('social/posts') }}/' + id + '/generate', { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
                } catch (e) { /* cut off; cron will finish it */ }
            }
            location.href = @json(route('social.index'));
        })();
    }
</script>
@endsection
