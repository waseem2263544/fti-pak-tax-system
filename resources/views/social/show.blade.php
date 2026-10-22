@extends('layouts.app')
@section('title', ($post->title ?: 'Post') . ' - Social Studio')
@section('page-title', 'Social Studio')

@php
    $checks = collect($post->fact_checks ?? []);
    $unverified = $checks->where('status', 'unverified')->count();
    $statusColor = \App\Models\SocialPost::STATUS_COLORS[$post->status] ?? 'secondary';
@endphp

@section('content')
@include('social._nav')

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-{{ $statusColor }}">{{ \App\Models\SocialPost::STATUSES[$post->status] ?? $post->status }}</span>
            <span class="badge bg-light text-dark border"><i class="bi {{ \App\Models\SocialPost::FORMAT_ICONS[$post->format] }} me-1"></i>{{ \App\Models\SocialPost::FORMATS[$post->format] }}
                @if($post->isVideo() && $post->slides) · {{ round($post->duration(), 1) }} s @endif</span>
            @if($post->revision > 1)<span class="badge bg-light text-dark border">version {{ $post->revision }}</span>@endif
            @if($post->planned_for)<span class="text-muted small">for {{ $post->planned_for->format('D j M') }}</span>@endif
        </div>
        <h5 class="mb-0 mt-2">{{ $post->title ?: $post->topic }}</h5>
        @if($post->title)<div class="text-muted small mt-1">{{ $post->topic }}</div>@endif
    </div>
    <div class="d-flex gap-1">
        @if($prev)<a href="{{ route('social.show', $prev) }}" class="btn btn-outline-primary btn-sm" title="Previous post to review"><i class="bi bi-chevron-left"></i></a>@endif
        @if($next)<a href="{{ route('social.show', $next) }}" class="btn btn-outline-primary btn-sm" title="Next post to review">Next to review <i class="bi bi-chevron-right"></i></a>@endif
    </div>
</div>

@if($post->status === 'generating')
    <div class="card"><div class="card-body text-center py-5">
        <div class="spinner-border text-primary"></div>
        <h6 class="mt-3 mb-1">{{ $post->feedback ? 'Claude is applying your changes…' : 'Claude is writing this post…' }}</h6>
        <p class="text-muted small mb-0" id="gen-note">It checks current FBR facts on the web first. This usually takes 1-2 minutes; you can leave this page.</p>
        @if($post->feedback)<div class="mt-3 mx-auto text-start small p-3 rounded" style="max-width:520px;background:var(--n-50)"><b>Your notes:</b> {{ $post->feedback }}</div>@endif
    </div></div>
@elseif($post->status === 'failed')
    <div class="alert alert-danger">
        <b>Claude could not write this post.</b>
        <div class="small mt-1" style="white-space:pre-wrap">{{ $post->error }}</div>
        <form method="POST" action="{{ route('social.regenerate', $post) }}" class="mt-2">@csrf
            <button class="btn btn-sm btn-danger"><i class="bi bi-arrow-repeat me-1"></i> Try again</button>
        </form>
    </div>
@endif

@if($post->slides && $post->status !== 'generating')
<div class="row g-4">
    {{-- ============ preview ============ --}}
    <div class="col-xl-7">
        <div class="card">
            <div class="card-body">
                @if($post->isVideo())
                    @if($post->hasFiles())
                    <ul class="nav nav-pills nav-sm mb-3" style="font-size:.82rem">
                        <li class="nav-item"><a class="nav-link active py-1" href="#" data-tab="mp4">Rendered video</a></li>
                        <li class="nav-item"><a class="nav-link py-1" href="#" data-tab="live">Live preview</a></li>
                    </ul>
                    <div data-pane="mp4" class="sx-player">
                        <video src="{{ route('social.video', $post) }}?r={{ $post->revision }}" controls playsinline preload="metadata" style="width:100%;aspect-ratio:9/16;border-radius:14px;background:#000"></video>
                    </div>
                    @endif
                    <div data-pane="live" class="sx-player" @if($post->hasFiles()) style="display:none" @endif>
                        <div id="video-box"></div>
                        <div class="sx-controls">
                            <button type="button" class="btn btn-primary btn-sm" id="play"><i class="bi bi-play-fill"></i></button>
                            <input type="range" id="scrub" min="0" max="1000" value="0" class="form-range">
                            <span id="clock" class="text-muted small font-monospace">0.0s</span>
                        </div>
                        <div class="text-muted small mt-2">Scenes: @foreach($post->slides as $i => $s)<a href="#" class="sx-jump" data-scene="{{ $i }}">{{ $i + 1 }}</a>@endforeach</div>
                    </div>
                @else
                    <div class="sx-slides {{ count($post->slides) === 1 ? 'one' : '' }}">
                        @foreach($post->slides as $i => $slide)
                        <div class="sx-slide">
                            <div class="sx-slide-box" data-index="{{ $i }}"></div>
                            <div class="d-flex justify-content-between align-items-center mt-1 small text-muted">
                                <span>{{ $i + 1 }} · {{ $slide['layout'] }}</span>
                                @if($post->hasFiles() && isset($post->assets[$i]))
                                    <a href="{{ route('social.download', [$post, 'slide' => $i]) }}"><i class="bi bi-download"></i> PNG</a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ============ editor ============ --}}
        @if($post->status !== 'posted')
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#editor">
                <span><i class="bi bi-pencil-square me-1"></i> Edit text and layout yourself</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div id="editor" class="collapse">
                <div class="card-body">
                    <p class="text-muted small">Wrap a word in <code>**double stars**</code> to colour it. Changes to an approved post send it back for approval.</p>
                    <div id="slide-forms"></div>
                    @if($post->format !== 'image')
                    <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-slide"><i class="bi bi-plus"></i> Add {{ $post->isVideo() ? 'scene' : 'slide' }}</button>
                    @endif
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Caption</label>
                        <textarea id="ed-caption" class="form-control" rows="3" dir="auto">{{ $post->caption }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Hashtags <span class="text-muted fw-normal">(spaces between)</span></label>
                        <input id="ed-tags" class="form-control" dir="auto" value="{{ implode(' ', $post->hashtags ?? []) }}">
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="save-edits"><i class="bi bi-check2 me-1"></i> Save changes</button>
                    <span class="small text-muted ms-2" id="save-note"></span>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ============ decision panel ============ --}}
    <div class="col-xl-5">
        @if(in_array($post->status, ['review', 'changes']))
        <div class="card mb-3 sx-decide">
            <div class="card-body">
                <h6 class="mb-2">Your decision</h6>
                @if($unverified)
                    <div class="alert alert-warning py-2 small mb-3"><i class="bi bi-exclamation-triangle me-1"></i>
                        {{ $unverified }} {{ Str::plural('claim', $unverified) }} could not be verified. Check the list below before approving.</div>
                @endif
                <form method="POST" action="{{ route('social.approve', $post) }}" class="mb-2">@csrf
                    <button class="btn btn-success w-100"><i class="bi bi-check-circle me-1"></i> Approve {{ $post->isVideo() ? 'and render video' : '' }}</button>
                </form>
                <form method="POST" action="{{ route('social.changes', $post) }}" class="mb-2">@csrf
                    <textarea name="feedback" rows="3" class="form-control form-control-sm mb-2" required dir="auto"
                        placeholder="Tell Claude what to change - e.g. 'Hook is too long', 'Use the 2026 deadline', 'Make slide 3 a list'…"></textarea>
                    <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-chat-left-text me-1"></i> Send back with changes</button>
                </form>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('social.regenerate', $post) }}" class="flex-fill" onsubmit="return confirm('Throw this version away and write it again from the topic?')">@csrf
                        <button class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-arrow-repeat me-1"></i> Start over</button>
                    </form>
                    <form method="POST" action="{{ route('social.reject', $post) }}" class="flex-fill">@csrf
                        <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-x-circle me-1"></i> Reject</button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        @if(in_array($post->status, ['approved', 'posted']))
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-2"><i class="bi bi-check-circle-fill text-success me-1"></i> Approved
                    <span class="text-muted fw-normal small">by {{ $post->approver?->name ?? '—' }}, {{ $post->approved_at?->format('j M, g:i a') }}</span></h6>
                <div id="files-state">
                @if($post->hasFiles())
                    <a href="{{ route('social.download', $post) }}" class="btn btn-accent w-100 mb-2"><i class="bi bi-download me-1"></i>
                        Download {{ $post->isVideo() ? 'MP4' : (count($post->assets ?? []) > 1 ? 'all slides (ZIP)' : 'PNG') }}</a>
                @elseif($post->render_status === 'failed')
                    <div class="alert alert-danger small py-2 mb-2"><b>Rendering failed.</b> {{ Str::limit($post->render_error, 300) }}</div>
                @elseif($post->isVideo())
                    <div class="alert alert-info small py-2 mb-2"><span class="spinner-border spinner-border-sm me-1"></span>
                        Waiting for the render worker. A 30-second video takes about 2-3 minutes once it starts.
                        @if(!\App\Services\Social\SocialBrand::get('worker_seen_at'))<br><b>No render worker has connected yet</b> - see Settings &gt; Render worker.@endif
                    </div>
                @else
                    <div class="alert alert-info small py-2 mb-2"><span class="spinner-border spinner-border-sm me-1"></span> <span id="export-note">Preparing the image files…</span></div>
                @endif
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('social.posted', $post) }}" class="flex-fill">@csrf
                        <button class="btn btn-sm w-100 {{ $post->status === 'posted' ? 'btn-outline-secondary' : 'btn-outline-primary' }}">
                            <i class="bi bi-tiktok me-1"></i> {{ $post->status === 'posted' ? 'Mark as not posted' : 'Mark as posted on TikTok' }}</button>
                    </form>
                    @if($post->status === 'approved' && ($post->hasFiles() || $post->render_status === 'failed'))
                    <form method="POST" action="{{ route('social.rerender', $post) }}">@csrf
                        <button class="btn btn-sm btn-outline-secondary" title="Render the files again"><i class="bi bi-arrow-clockwise"></i></button>
                    </form>
                    @endif
                </div>
                @if($post->posted_at)<div class="small text-muted mt-2">Posted {{ $post->posted_at->format('j M Y, g:i a') }}</div>@endif
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Caption</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="copy-caption"><i class="bi bi-clipboard me-1"></i> Copy</button>
                </div>
                <div class="sx-caption" id="caption-text" data-copy="{{ $post->captionWithTags() }}">
                    <div dir="rtl">{{ $post->caption }}</div>
                    @if($post->hashtags)<div dir="ltr" class="sx-tags">{{ collect($post->hashtags)->map(fn($t) => '#' . $t)->implode(' ') }}</div>@endif
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-2">Fact check <span class="text-muted fw-normal small">- every claim on the slides and caption</span></h6>
                @forelse($checks as $c)
                    <div class="sx-check sx-check-{{ $c['status'] }}">
                        <i class="bi {{ ['verified' => 'bi-patch-check-fill', 'brand_fact' => 'bi-building-check', 'unverified' => 'bi-exclamation-triangle-fill'][$c['status']] ?? 'bi-question-circle' }}"></i>
                        <div>
                            <div>{{ $c['claim'] }}</div>
                            <div class="small text-muted">
                                {{ ['verified' => 'Verified', 'brand_fact' => 'From brand facts', 'unverified' => 'Not verified'][$c['status']] ?? $c['status'] }}
                                @if(!empty($c['source']) && Str::startsWith($c['source'], 'http'))
                                    · <a href="{{ $c['source'] }}" target="_blank" rel="noopener">{{ parse_url($c['source'], PHP_URL_HOST) }}</a>
                                @elseif(!empty($c['source']) && $c['source'] !== 'brand facts') · {{ $c['source'] }} @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No factual claims listed.</p>
                @endforelse
            </div>
        </div>

        <div class="small text-muted">
            Created {{ $post->created_at->format('j M Y, g:i a') }}@if($post->creator) by {{ $post->creator->name }}@endif
            @if($post->ai_model) · {{ $post->ai_model }}@endif
            @if($post->ai_usage) · {{ number_format(($post->ai_usage['input_tokens'] ?? 0) + ($post->ai_usage['output_tokens'] ?? 0)) }} tokens, {{ $post->ai_usage['web_searches'] ?? 0 }} searches @endif
            <form method="POST" action="{{ route('social.destroy', $post) }}" class="d-inline" onsubmit="return confirm('Delete this post and its files?')">
                @csrf @method('DELETE')
                <button class="btn btn-link btn-sm text-danger p-0 ms-2 align-baseline">Delete</button>
            </form>
        </div>
    </div>
</div>
@endif

<template id="slide-form-tpl">
    <div class="sx-ed border rounded p-3 mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
            <b class="sx-ed-n"></b>
            <select class="form-select form-select-sm" data-f="layout" style="width:auto">
                @foreach(\App\Models\SocialPost::LAYOUTS as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach
            </select>
            <select class="form-select form-select-sm" data-f="room" style="width:auto">
                <option value="">auto colour</option><option value="navy">navy</option><option value="paper">light</option><option value="blue">blue</option>
            </select>
            @if($post->isVideo())
            <div class="input-group input-group-sm" style="width:120px"><input type="number" step="0.1" min="1.5" max="8" class="form-control" data-f="duration"><span class="input-group-text">s</span></div>
            @endif
            <div class="ms-auto btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" data-act="up" title="Move up"><i class="bi bi-arrow-up"></i></button>
                <button type="button" class="btn btn-outline-secondary" data-act="down" title="Move down"><i class="bi bi-arrow-down"></i></button>
                <button type="button" class="btn btn-outline-danger" data-act="del" title="Remove"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-4"><input class="form-control form-control-sm" dir="auto" data-f="kicker" placeholder="Kicker (small label)"></div>
            <div class="col-md-8"><input class="form-control form-control-sm" dir="auto" data-f="heading" placeholder="Heading"></div>
            <div class="col-12"><textarea class="form-control form-control-sm" dir="auto" rows="2" data-f="body" placeholder="Body text"></textarea></div>
            <div class="col-12"><textarea class="form-control form-control-sm" dir="auto" rows="3" data-f="points" placeholder="Points - one per line (list, steps, warning)"></textarea></div>
            <div class="col-md-4"><input class="form-control form-control-sm" data-f="stat_value" placeholder="Big number (stat)"></div>
            <div class="col-md-5"><input class="form-control form-control-sm" dir="auto" data-f="stat_label" placeholder="Number label"></div>
            <div class="col-md-3"><input class="form-control form-control-sm" data-f="ghost" placeholder="Ghost word"></div>
        </div>
    </div>
</template>
@endsection

@section('styles')
<style>
    .sx-slides { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 16px; }
    .sx-slides.one { grid-template-columns: minmax(0, 340px); }
    .sx-player { max-width: 380px; }
    .sx-controls { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
    .sx-controls .form-range { flex: 1; }
    .sx-jump { display: inline-block; min-width: 22px; text-align: center; margin-left: 2px; padding: 0 4px; border-radius: 6px; background: var(--n-100); text-decoration: none; }
    .sx-caption > div[dir=rtl] { white-space: pre-wrap; }
    .sx-caption { font-size: 1rem; line-height: 1.9; background: var(--n-25); border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px;
        font-family: 'FT Geist', 'FT Nastaliq', sans-serif; }
    .sx-tags { margin-top: 8px; color: #1982C3; font-size: .9rem; unicode-bidi: plaintext; text-align: left; }
    .sx-check { display: flex; gap: 10px; padding: 8px 0; border-top: 1px solid var(--border); font-size: .84rem; }
    .sx-check:first-of-type { border-top: 0; }
    .sx-check > i { margin-top: 2px; }
    .sx-check-verified > i { color: #1AAF63; }
    .sx-check-brand_fact > i { color: #1982C3; }
    .sx-check-unverified > i { color: #d39200; }
    .sx-check-unverified { background: #fff8e1; margin: 0 -10px; padding: 8px 10px; border-radius: 8px; }
    .sx-decide { border-color: rgba(25,130,195,.35); }
</style>
@endsection

@section('scripts')
@include('social._renderer')
<script>
(function () {
    const post = @json($post->renderPayload());
    const status = @json($post->status);
    const csrf = '{{ csrf_token() }}';
    const headers = { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' };

    // ---------- waiting for Claude ----------
    if (status === 'generating') {
        fetch(@json(route('social.generate', $post)), { method: 'POST', headers })
            .catch(() => null)
            .finally(() => {
                const poll = () => fetch(@json(route('social.status', $post)), { headers }).then(r => r.json())
                    .then(s => s.status === 'generating' ? setTimeout(poll, 5000) : location.reload())
                    .catch(() => setTimeout(poll, 8000));
                poll();
            });
        return;
    }
    if (!post.slides || !post.slides.length) return;

    // ---------- previews ----------
    const instances = [];
    if (post.format === 'video') {
        const r = socialMount(document.getElementById('video-box'), post, { mode: 'video' });
        const play = document.getElementById('play'), scrub = document.getElementById('scrub'), clock = document.getElementById('clock');
        let t = Math.min(1.6, r.duration), playing = false, last = 0; // open on the hook, fully in
        const draw = () => { r.seek(t); scrub.value = Math.round(t / r.duration * 1000); clock.textContent = t.toFixed(1) + 's / ' + r.duration.toFixed(1) + 's'; };
        const loop = now => {
            if (!playing) return;
            t += (now - last) / 1000; last = now;
            if (t >= r.duration) { t = r.duration; playing = false; play.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>'; }
            draw();
            if (playing) requestAnimationFrame(loop);
        };
        play.onclick = () => {
            if (playing) { playing = false; play.innerHTML = '<i class="bi bi-play-fill"></i>'; return; }
            if (t >= r.duration || t === Math.min(1.6, r.duration)) t = 0;
            playing = true; last = performance.now(); play.innerHTML = '<i class="bi bi-pause-fill"></i>';
            requestAnimationFrame(loop);
        };
        scrub.oninput = () => { playing = false; play.innerHTML = '<i class="bi bi-play-fill"></i>'; t = scrub.value / 1000 * r.duration; draw(); };
        document.querySelectorAll('.sx-jump').forEach(a => a.onclick = e => {
            e.preventDefault(); playing = false; t = r.scenes[+a.dataset.scene]._start + 0.01; draw();
        });
        r.ready.then(draw);
        document.querySelectorAll('[data-tab]').forEach(a => a.onclick = e => {
            e.preventDefault();
            document.querySelectorAll('[data-tab]').forEach(x => x.classList.toggle('active', x === a));
            document.querySelectorAll('[data-pane]').forEach(p => p.style.display = p.dataset.pane === a.dataset.tab ? '' : 'none');
        });
    } else {
        document.querySelectorAll('.sx-slide-box').forEach(box => {
            instances.push(socialMount(box, post, { mode: 'static', only: +box.dataset.index }));
        });
    }

    // ---------- export after approval (images and carousels) ----------
    @if($post->status === 'approved' && !$post->isVideo() && !$post->hasFiles() && $post->render_status !== 'failed')
    (async () => {
        const note = document.getElementById('export-note');
        try {
            for (let i = 0; i < instances.length; i++) {
                note.textContent = 'Preparing the image files… ' + (i + 1) + ' of ' + instances.length;
                await instances[i].ready;
                instances[i].show(0);
                const png = await SocialRenderer.toPng(instances[i].stage);
                const res = await fetch(@json(route('social.assets.store', $post)), {
                    method: 'POST', headers, body: JSON.stringify({ index: i, total: instances.length, png })
                });
                if (!res.ok) throw new Error('Upload failed (' + res.status + ')');
            }
            location.reload();
        } catch (e) {
            note.textContent = 'This browser could not prepare the files (' + e.message + '). The render worker will do it within a few minutes.';
        }
    })();
    @endif

    @if($post->status === 'approved' && $post->isVideo() && !$post->hasFiles() && $post->render_status !== 'failed')
    const pollRender = () => fetch(@json(route('social.status', $post)), { headers }).then(r => r.json())
        .then(s => (s.has_files || s.render_status === 'failed') ? location.reload() : setTimeout(pollRender, 10000))
        .catch(() => setTimeout(pollRender, 15000));
    setTimeout(pollRender, 10000);
    @endif

    // ---------- caption copy ----------
    const copyBtn = document.getElementById('copy-caption');
    copyBtn && (copyBtn.onclick = () => {
        navigator.clipboard.writeText(document.getElementById('caption-text').dataset.copy).then(() => {
            copyBtn.innerHTML = '<i class="bi bi-check2 me-1"></i> Copied';
            setTimeout(() => copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i> Copy', 1800);
        });
    });

    // ---------- editor ----------
    const wrap = document.getElementById('slide-forms');
    if (!wrap) return;
    const tpl = document.getElementById('slide-form-tpl');
    const maxSlides = { image: 1, carousel: 8, video: 8 }[post.format];
    const label = post.format === 'video' ? 'Scene' : 'Slide';

    function addForm(s) {
        const node = tpl.content.firstElementChild.cloneNode(true);
        const set = (f, v) => { const el = node.querySelector('[data-f="' + f + '"]'); if (el) el.value = v == null ? '' : v; };
        set('layout', s.layout || 'point'); set('room', s.room || ''); set('kicker', s.kicker); set('heading', s.heading);
        set('body', s.body); set('points', (s.points || []).join('\n')); set('ghost', s.ghost);
        set('stat_value', s.stat && s.stat.value); set('stat_label', s.stat && s.stat.label); set('duration', s.duration || 3);
        node.querySelector('[data-act="up"]').onclick = () => { node.previousElementSibling && wrap.insertBefore(node, node.previousElementSibling); renumber(); };
        node.querySelector('[data-act="down"]').onclick = () => { node.nextElementSibling && wrap.insertBefore(node.nextElementSibling, node); renumber(); };
        node.querySelector('[data-act="del"]').onclick = () => { if (wrap.children.length > 1) { node.remove(); renumber(); } };
        wrap.appendChild(node);
        renumber();
    }
    function renumber() {
        [...wrap.children].forEach((n, i) => n.querySelector('.sx-ed-n').textContent = label + ' ' + (i + 1));
        const add = document.getElementById('add-slide');
        if (add) add.disabled = wrap.children.length >= maxSlides;
    }
    function collect() {
        return [...wrap.children].map(n => {
            const g = f => { const el = n.querySelector('[data-f="' + f + '"]'); return el ? el.value.trim() : ''; };
            const s = { layout: g('layout') };
            ['room', 'kicker', 'heading', 'body', 'ghost'].forEach(f => { if (g(f)) s[f] = g(f); });
            const pts = g('points').split('\n').map(x => x.trim()).filter(Boolean);
            if (pts.length) s.points = pts;
            if (g('stat_value')) s.stat = { value: g('stat_value'), label: g('stat_label') };
            if (post.format === 'video') s.duration = parseFloat(g('duration')) || 3;
            return s;
        });
    }
    post.slides.forEach(addForm);
    const add = document.getElementById('add-slide');
    add && (add.onclick = () => addForm({ layout: 'point', duration: 3 }));

    document.getElementById('save-edits').onclick = async () => {
        const note = document.getElementById('save-note');
        note.textContent = 'Saving…';
        const res = await fetch(@json(route('social.update', $post)), {
            method: 'PUT', headers,
            body: JSON.stringify({ slides: collect(), caption: document.getElementById('ed-caption').value, hashtags: document.getElementById('ed-tags').value })
        });
        if (res.ok) location.reload();
        else note.textContent = 'Could not save (' + res.status + ').';
    };
})();
</script>
@endsection
