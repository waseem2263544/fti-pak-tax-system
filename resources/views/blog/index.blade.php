@extends('layouts.app')
@section('title', 'Blog')
@section('page-title', 'Blog')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <p class="text-muted mb-0" style="font-size: .85rem;">
            Posts on fairtaxint.com. Publishing asks the site to refresh that page straight away.
        </p>
    </div>
    <a href="{{ route('blog.create') }}" class="btn btn-accent btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New post
    </a>
</div>

<form method="GET" class="d-flex flex-wrap gap-2 mb-3">
    <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm"
           placeholder="Search title, slug or excerpt…" style="max-width: 320px;">
    <select name="region" class="form-select form-select-sm" style="width: auto;">
        <option value="">Both sites</option>
        @foreach(\App\Models\Post::REGIONS as $k => $label)
            <option value="{{ $k }}" @selected($region === $k)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="status" class="form-select form-select-sm" style="width: auto;">
        <option value="">Any status</option>
        <option value="draft" @selected(request('status') === 'draft')>Draft ({{ $counts['draft'] }})</option>
        <option value="published" @selected(request('status') === 'published')>Published ({{ $counts['published'] }})</option>
    </select>
    <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    @if($search !== '' || $region || request('status'))
        <a href="{{ route('blog.index') }}" class="btn btn-outline-primary btn-sm">Clear</a>
    @endif
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th style="width: 90px;">Site</th>
                    <th style="width: 130px;">Category</th>
                    <th style="width: 80px;" title="SEO score from the focus keyphrase analysis">SEO</th>
                    <th style="width: 110px;">Published</th>
                    <th style="width: 110px;">Status</th>
                    <th style="width: 90px;"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($posts as $post)
                <tr>
                    <td>
                        <a href="{{ route('blog.edit', $post) }}" style="font-weight: 600; text-decoration: none;">{{ $post->title }}</a>
                        @if($post->featured)<span class="badge bg-warning text-dark ms-1" style="font-size: .6rem;">featured</span>@endif
                        <div class="text-muted" style="font-size: .74rem; font-family: ui-monospace, Menlo, monospace;">
                            /{{ $post->region }}/blog/{{ $post->slug }}
                        </div>
                    </td>
                    <td><span class="badge bg-secondary">{{ \App\Models\Post::REGIONS[$post->region] ?? $post->region }}</span></td>
                    <td style="font-size: .82rem;">{{ $post->category ?: '—' }}</td>
                    <td style="font-size: .82rem;" class="text-nowrap">
                        @php
                            $band = $post->seo_score === null ? 'off'
                                : ($post->seo_score >= 70 ? 'good' : ($post->seo_score >= 40 ? 'ok' : 'bad'));
                            $colour = ['good' => '#2e9e5b', 'ok' => '#e0a000', 'bad' => '#c0392b', 'off' => 'var(--n-300)'][$band];
                        @endphp
                        <span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:{{ $colour }};"></span>
                        {{ $post->seo_score === null ? '—' : $post->seo_score }}
                    </td>
                    <td style="font-size: .82rem;">{{ $post->published_on?->format('d M Y') ?: '—' }}</td>
                    <td>
                        @if($post->status === 'published')
                            <span class="badge bg-success">Published</span>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        @if($post->status === 'published')
                            <a href="https://fairtaxint.com/{{ $post->region }}/blog/{{ $post->slug }}" target="_blank"
                               class="btn btn-sm btn-outline-primary" title="View on the site"><i class="bi bi-box-arrow-up-right"></i></a>
                        @endif
                        <a href="{{ route('blog.edit', $post) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-5 text-muted">
                    @if($search !== '') Nothing matches “{{ $search }}”. @else No posts yet. @endif
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $posts->links() }}</div>
@endsection
