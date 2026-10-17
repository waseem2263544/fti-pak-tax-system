@extends('layouts.app')
@section('title', $post->exists ? 'Edit post' : 'New post')
@section('page-title', $post->exists ? $post->title : 'New post')

@section('styles')
<style>
    .bl-editor { border: 1px solid var(--border-strong); border-radius: var(--radius);
                 min-height: 460px; padding: 16px 18px; background: var(--surface);
                 font-size: .92rem; line-height: 1.7; overflow-y: auto; max-height: 70vh; }
    .bl-editor:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
    .bl-editor h2 { font-size: 1.15rem; font-weight: 700; margin: 1.3em 0 .5em; }
    .bl-editor h3 { font-size: 1rem; font-weight: 600; margin: 1.2em 0 .4em; }
    .bl-editor p  { margin: 0 0 .9em; }
    .bl-editor ul, .bl-editor ol { margin: 0 0 .9em 1.2em; }
    .bl-editor li { margin-bottom: .3em; }
    .bl-editor table { width: 100%; border-collapse: collapse; margin: 0 0 1em; }
    .bl-editor th, .bl-editor td { border: 1px solid var(--border); padding: 6px 9px; font-size: .86rem; }
    .bl-editor a { color: var(--accent-dark); text-decoration: underline; text-underline-offset: 2px; }
    .bl-editor figure { margin: 1.2em 0; }
    .bl-editor figure img { max-width: 100%; height: auto; border-radius: var(--radius);
                            display: block; border: 1px solid var(--border); }
    .bl-editor figcaption { font-size: .8rem; color: var(--text-muted); margin-top: 6px; }
    .bl-editor figure.bl-picked img { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
    .bl-editor .bl-busy { opacity: .55; font-size: .82rem; font-style: italic; }
    .bl-bar { display: flex; flex-wrap: wrap; gap: 4px; padding: 8px; border: 1px solid var(--border);
              border-bottom: 0; border-radius: var(--radius) var(--radius) 0 0; background: var(--surface-sunk); }
    .bl-bar button { border: 1px solid transparent; background: transparent; border-radius: var(--radius-sm);
                     padding: 4px 9px; font-size: .78rem; cursor: pointer; color: var(--text-soft); }
    .bl-bar button:hover { background: var(--n-100); color: var(--text); }
    .bl-bar .dropdown-menu { font-size: .82rem; }
    .bl-side label { font-size: .78rem; }
    .bl-html { font: 12px/1.6 ui-monospace, Menlo, monospace; }

    /* The image panel, shown only while an image in the body is selected. */
    .bl-imgpanel { border: 1px solid var(--accent); border-top: 0;
                   border-radius: 0 0 var(--radius) var(--radius); padding: 12px 14px;
                   background: var(--surface-sunk); }

    /* Search appearance and the two analyses. */
    .bl-snippet { border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 16px;
                  background: #fff; max-width: 600px; }
    .bl-snippet .s-url   { font-size: .75rem; color: #202124; }
    .bl-snippet .s-site  { font-size: .72rem; color: #5f6368; }
    .bl-snippet .s-title { font-size: 1.1rem; color: #1a0dab; line-height: 1.3; margin: 3px 0 2px;
                           font-family: Arial, sans-serif; }
    .bl-snippet .s-desc  { font-size: .82rem; color: #4d5156; line-height: 1.45; font-family: Arial, sans-serif; }
    .bl-meter { height: 3px; border-radius: 2px; background: var(--n-200); overflow: hidden; margin-top: 5px; }
    .bl-meter span { display: block; height: 100%; background: var(--ok, #2e9e5b); transition: width .15s; }
    .bl-meter.over span { background: var(--danger, #c0392b); }
    .bl-meter.under span { background: #e0a000; }

    .bl-checks { list-style: none; margin: 0; padding: 0; }
    .bl-checks li { display: flex; gap: 9px; align-items: flex-start; padding: 6px 0;
                    border-bottom: 1px solid var(--border); font-size: .83rem; line-height: 1.5; }
    .bl-checks li:last-child { border-bottom: 0; }
    .bl-dot { width: 10px; height: 10px; border-radius: 50%; flex: 0 0 10px; margin-top: .35em; }
    .bl-dot.good { background: #2e9e5b; }
    .bl-dot.ok   { background: #e0a000; }
    .bl-dot.bad  { background: #c0392b; }
    .bl-dot.off  { background: var(--n-300); }
    .bl-score { display: inline-flex; align-items: center; gap: 7px; font-size: .8rem; font-weight: 600; }
    .bl-score .bl-dot { width: 13px; height: 13px; flex-basis: 13px; margin: 0; }
</style>
@endsection

@section('content')
<form method="POST" action="{{ $post->exists ? route('blog.update', $post) : route('blog.store') }}" id="postForm">
    @csrf
    @if($post->exists) @method('PUT') @endif

    <input type="hidden" name="seo_score" id="seoScoreField" value="{{ $post->seo_score }}">
    <input type="hidden" name="readability_score" id="readScoreField" value="{{ $post->readability_score }}">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('blog.index') }}" class="btn btn-sm btn-outline-primary">← All posts</a>
        <div class="d-flex gap-2 align-items-center">
            <span class="bl-score" id="scoreChip" title="SEO score">
                <span class="bl-dot off" id="scoreChipDot"></span><span id="scoreChipText">SEO —</span>
            </span>
            @if($post->exists && $post->status === 'published')
                <a href="https://fairtaxint.com/{{ $post->region }}/blog/{{ $post->slug }}" target="_blank"
                   class="btn btn-sm btn-outline-primary">View live</a>
            @endif
            <button name="status" value="draft" class="btn btn-sm btn-outline-primary">
                {{ $post->status === 'published' ? 'Save without publishing' : 'Save as draft' }}
            </button>
            @if($askPassword ?? true)
                <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#publishModal">
                    {{ $post->status === 'published' ? 'Save & refresh site' : 'Publish' }}
                </button>
            @else
                <button name="status" value="published" class="btn btn-sm btn-accent">
                    {{ $post->status === 'published' ? 'Save & refresh site' : 'Publish' }}
                </button>
            @endif
        </div>
    </div>

    @if($post->exists && $post->liveRevision && $post->differsFromLive())
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
            <div style="font-size: .85rem;">
                This draft has changes the website does not have. Readers still see the version published
                {{ $post->liveRevision->created_at?->diffForHumans() }}.
            </div>
            <div class="d-flex gap-2">
                <a href="https://fairtaxint.com/{{ $post->region }}/blog/{{ $post->liveRevision->slug }}"
                   target="_blank" class="btn btn-sm btn-outline-primary">See the live version</a>
                <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="if(confirm('Replace this draft with the version that is live on the website? Your unpublished changes are lost.')) document.getElementById('revertForm').submit();">
                    Reset to the live version
                </button>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" required
                               value="{{ old('title', $post->title) }}" style="font-size: 1rem; font-weight: 600;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="excerpt">Excerpt</label>
                        <textarea name="excerpt" id="excerpt" class="form-control" rows="2"
                                  maxlength="1000">{{ old('excerpt', $post->excerpt) }}</textarea>
                        <div class="form-text">Shown on the blog index, and used as the meta description unless you write one below.</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Body</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="toggleHtml">Edit HTML</button>
                </div>
                <div class="card-body">
                    <div class="bl-bar" id="bar">
                        <button type="button" data-cmd="formatBlock" data-arg="h2">H2</button>
                        <button type="button" data-cmd="formatBlock" data-arg="h3">H3</button>
                        <button type="button" data-cmd="formatBlock" data-arg="p">Text</button>
                        <button type="button" data-cmd="bold"><strong>B</strong></button>
                        <button type="button" data-cmd="italic"><em>I</em></button>
                        <button type="button" data-cmd="insertUnorderedList">List</button>
                        <button type="button" data-cmd="insertOrderedList">1. List</button>
                        <button type="button" id="mkLink">Link</button>
                        <button type="button" id="rmLink">Unlink</button>
                        <span class="dropdown">
                            <button type="button" class="dropdown-toggle" data-bs-toggle="dropdown">Image</button>
                            <ul class="dropdown-menu">
                                <li><button type="button" class="dropdown-item" id="imgUpload">Upload from this computer…</button></li>
                                <li><button type="button" class="dropdown-item" id="imgUrl">Use a web address or site path…</button></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button type="button" class="dropdown-item" id="imgFolder">Choose the folder…</button></li>
                            </ul>
                        </span>
                        <button type="button" id="mkTable">Table</button>
                        <button type="button" data-cmd="removeFormat">Clear</button>
                    </div>
                    <div class="bl-editor" id="editor" contenteditable="true">{!! old('content', $post->content) !!}</div>

                    <div class="bl-imgpanel d-none" id="imgPanel">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label" for="imgAlt">Alt text</label>
                                <input type="text" id="imgAlt" class="form-control form-control-sm"
                                       placeholder="What the image shows">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="imgCap">Caption</label>
                                <input type="text" id="imgCap" class="form-control form-control-sm"
                                       placeholder="Shown under the image">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="imgSize">Width</label>
                                <select id="imgSize" class="form-select form-select-sm">
                                    <option value="">Full</option>
                                    <option value="66%">Two thirds</option>
                                    <option value="50%">Half</option>
                                    <option value="320px">Small</option>
                                </select>
                            </div>
                            <div class="col-md-1 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-danger" id="imgRemove">Remove</button>
                            </div>
                            <div class="col-12">
                                <div class="form-text">
                                    Alt text describes the image for a reader who cannot see it, and is what Google
                                    reads. Say what is in it — not "screenshot".
                                </div>
                            </div>
                        </div>
                    </div>

                    <textarea name="content" id="content" class="form-control bl-html d-none" rows="24">{{ old('content', $post->content) }}</textarea>
                    <div class="form-text mt-2">
                        You can also drag an image straight onto the text, or paste one from a screenshot.
                        They are filed under <code id="folderLabel"></code> —
                        <a href="#" id="folderChange">change</a>.
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong>Search appearance</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="focus_keyphrase">Focus keyphrase</label>
                        <input type="text" name="focus_keyphrase" id="focus_keyphrase" class="form-control form-control-sm"
                               value="{{ old('focus_keyphrase', $post->focus_keyphrase) }}"
                               placeholder="e.g. PTCL tax certificate">
                        <div class="form-text">The search you want this post to win. Everything below is measured against it.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="related_keyphrases">Also ranks for</label>
                        <input type="text" name="related_keyphrases" id="related_keyphrases" class="form-control form-control-sm"
                               value="{{ old('related_keyphrases', $post->related_keyphrases) }}"
                               placeholder="separated by commas">
                    </div>

                    <div class="mb-3">
                        <div class="form-label">How it looks on Google</div>
                        <div class="bl-snippet">
                            <div class="s-site">Fair Tax International</div>
                            <div class="s-url" id="snipUrl">fairtaxint.com › pk › blog</div>
                            <div class="s-title" id="snipTitle">—</div>
                            <div class="s-desc" id="snipDesc">—</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="meta_title">SEO title</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control form-control-sm"
                               value="{{ old('meta_title', $post->meta_title) }}" placeholder="left blank, the post title is used">
                        <div class="bl-meter" id="titleMeter"><span style="width:0"></span></div>
                        <div class="form-text" id="titleHint"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="meta_description">Meta description</label>
                        <textarea name="meta_description" id="meta_description" class="form-control form-control-sm"
                                  rows="2" maxlength="400"
                                  placeholder="left blank, the excerpt is used">{{ old('meta_description', $post->meta_description) }}</textarea>
                        <div class="bl-meter" id="descMeter"><span style="width:0"></span></div>
                        <div class="form-text" id="descHint"></div>
                    </div>

                    <details>
                        <summary style="font-size:.82rem; cursor:pointer; color:var(--text-soft);">Advanced</summary>
                        <div class="row g-2 mt-1">
                            <div class="col-md-8">
                                <label class="form-label" for="canonical_url">Canonical URL</label>
                                <input type="text" name="canonical_url" id="canonical_url" class="form-control form-control-sm bl-html"
                                       value="{{ old('canonical_url', $post->canonical_url) }}"
                                       placeholder="only if this page repeats another one">
                            </div>
                            <div class="col-md-4 d-flex align-items-end pb-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="noindex" value="1" id="noindex"
                                           @checked(old('noindex', $post->noindex))>
                                    <label class="form-check-label" for="noindex">Keep out of search results</label>
                                </div>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>SEO analysis</strong>
                    <span class="bl-score"><span class="bl-dot off" id="seoDot"></span><span id="seoScoreText">—</span></span>
                </div>
                <div class="card-body"><ul class="bl-checks" id="seoChecks"></ul></div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Readability</strong>
                    <span class="bl-score"><span class="bl-dot off" id="readDot"></span><span id="readScoreText">—</span></span>
                </div>
                <div class="card-body"><ul class="bl-checks" id="readChecks"></ul></div>
            </div>
        </div>

        <div class="col-lg-4 bl-side">
            <div class="card mb-3">
                <div class="card-header"><strong>Publishing</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="region">Site</label>
                        <select name="region" id="region" class="form-select form-select-sm">
                            @foreach(\App\Models\Post::REGIONS as $k => $label)
                                <option value="{{ $k }}" @selected(old('region', $post->region) === $k)>
                                    {{ $label }} — /{{ $k }}/blog
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control form-control-sm bl-html"
                               value="{{ old('slug', $post->slug) }}" placeholder="made from the title">
                        <div class="form-text">
                            Changing this on a live post breaks its existing links. Add a redirect on the site if you do.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="category">Category</label>
                        <input type="text" name="category" id="category" class="form-control form-control-sm"
                               value="{{ old('category', $post->category) }}" list="catList">
                        <datalist id="catList">
                            @foreach(\App\Models\Post::distinct()->pluck('category')->filter() as $c)
                                <option value="{{ $c }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label" for="published_on">Published</label>
                            <input type="date" name="published_on" id="published_on" class="form-control form-control-sm"
                                   value="{{ old('published_on', $post->published_on?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="reviewed_on">Last reviewed</label>
                            <input type="date" name="reviewed_on" id="reviewed_on" class="form-control form-control-sm"
                                   value="{{ old('reviewed_on', $post->reviewed_on?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <div class="form-text">
                                Set "last reviewed" only when the content genuinely changed. On tax advice it is a
                                trust signal, and the site shows it.
                            </div>
                        </div>
                    </div>
                    <div class="mb-2 mt-3">
                        <label class="form-label" for="read_time">Reading time</label>
                        <input type="text" name="read_time" id="read_time" class="form-control form-control-sm"
                               value="{{ old('read_time', $post->read_time) }}" placeholder="worked out from the body">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="featured" value="1" id="featured"
                               @checked(old('featured', $post->featured))>
                        <label class="form-check-label" for="featured">Feature on the blog index</label>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Hero image</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="heroUpload">Upload</button>
                </div>
                <div class="card-body">
                    <label class="form-label" for="hero_image">Image</label>
                    <input type="text" name="hero_image" id="hero_image" class="form-control form-control-sm mb-1 bl-html"
                           value="{{ old('hero_image', $post->hero_image) }}" placeholder="/blog/something.jpg">
                    <div class="form-text mb-2">
                        Upload above, or give a path in the website's own public folder.
                    </div>

                    <label class="form-label" for="hero_image_alt">Alt text</label>
                    <input type="text" name="hero_image_alt" id="hero_image_alt" class="form-control form-control-sm"
                           value="{{ old('hero_image_alt', $post->hero_image_alt) }}"
                           placeholder="What the image shows">
                    <div class="form-text">
                        Describes the image for a reader who cannot see it, and is what Google reads. It counts
                        in the SEO analysis, since the hero heads the post.
                    </div>
                    <div id="heroPreview" class="mt-2"></div>
                </div>
            </div>

            @if($post->exists)
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div style="font-size: .78rem; color: var(--text-muted);">
                            @if($post->author) Created by {{ $post->author->name }}<br>@endif
                            Updated {{ $post->updated_at?->diffForHumans() }}
                        </div>
                        <div class="d-flex gap-2">
                            @if($post->status === 'published')
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="if(confirm('Take this post off the website? It stays here and can go back up.')) document.getElementById('unpublishForm').submit();">
                                    Take down
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="if(confirm('Delete this post? If it is live, the page disappears from the site.')) document.getElementById('delForm').submit();">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</form>

<input type="file" id="filePicker" accept="image/*" multiple class="d-none">

@if($askPassword ?? true)
<div class="modal fade" id="publishModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Publish to fairtaxint.com</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p style="font-size: .84rem;" class="text-muted">
                    This puts the post on the public website. Enter your own password to confirm.
                </p>
                <input type="password" name="publish_password" id="publishPassword" form="postForm"
                       class="form-control form-control-sm @error('publish_password') is-invalid @enderror"
                       autocomplete="current-password" placeholder="Your password">
                @error('publish_password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="status" value="published" form="postForm" class="btn btn-sm btn-accent">
                    Publish
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($post->exists)
    <form method="POST" id="delForm" action="{{ route('blog.destroy', $post) }}" class="d-none">
        @csrf @method('DELETE')
    </form>
    <form method="POST" id="unpublishForm" action="{{ route('blog.unpublish', $post) }}" class="d-none">
        @csrf
    </form>
    <form method="POST" id="revertForm" action="{{ route('blog.revert', $post) }}" class="d-none">
        @csrf
    </form>
@endif
@endsection

@section('scripts')
<script src="{{ asset('js/blog-seo.js') }}?v={{ @filemtime(public_path('js/blog-seo.js')) }}"></script>
<script>
/*
 * A plain rich-text editor over contenteditable, with the SEO analysis
 * alongside it.
 *
 * The site renders the body as raw HTML, so what matters is that the markup
 * stays simple - h2, h3, p, lists, tables, links, figures. Pasted text is
 * stripped for that reason: a paste from Word carries a kilobyte of styling
 * that the site's own stylesheet then has to fight. A pasted *image* is the
 * exception - that gets uploaded.
 */
(function () {
    const editor = document.getElementById('editor');
    const field  = document.getElementById('content');
    const form   = document.getElementById('postForm');
    const picker = document.getElementById('filePicker');
    const uploadUrl = @json(route('blog.image'));
    const token = document.querySelector('input[name="_token"]').value;

    /* ---------------- selection ----------------
     * A prompt() or a file dialog takes the focus, and with it the caret.
     * Every command that opens one saves the range first and puts it back
     * afterwards, otherwise the text comes back unlinked and the image lands
     * at the top of the post.
     */
    let saved = null;

    function remember() {
        const sel = window.getSelection();
        if (sel && sel.rangeCount && editor.contains(sel.anchorNode)) {
            saved = sel.getRangeAt(0).cloneRange();
        }
    }

    function restore() {
        editor.focus();
        if (!saved) { return; }
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(saved);
    }

    document.addEventListener('selectionchange', () => {
        if (document.activeElement === editor) { remember(); }
    });

    /* ---------------- toolbar ---------------- */

    document.getElementById('bar').addEventListener('mousedown', remember);

    document.getElementById('bar').addEventListener('click', e => {
        const b = e.target.closest('button[data-cmd]');
        if (!b) { return; }
        restore();
        document.execCommand(b.dataset.cmd, false, b.dataset.arg || null);
        schedule();
    });

    document.getElementById('mkLink').addEventListener('click', () => {
        const sel = window.getSelection();
        const selected = saved ? saved.toString() : (sel ? sel.toString() : '');
        const url = prompt('Link to where?\n\nA full https:// address, or a path like /pk/contact for this site.');
        if (!url) { return; }

        restore();

        if (selected.trim()) {
            document.execCommand('createLink', false, url);
        } else {
            // Nothing was selected, so there is no text to turn into a link.
            // Insert one rather than silently doing nothing.
            const label = prompt('What should the link say?', url) || url;
            document.execCommand('insertHTML', false,
                '<a href="' + escapeAttr(url) + '">' + escapeHtml(label) + '</a>');
        }

        // Links to anywhere but this site open in a new tab, so a reader is
        // not sent away from the post.
        editor.querySelectorAll('a[href]').forEach(a => {
            const href = a.getAttribute('href') || '';
            if (/^https?:\/\//i.test(href) && !/fairtaxint\.com/i.test(href)) {
                a.setAttribute('target', '_blank');
                a.setAttribute('rel', 'noopener');
            } else {
                a.removeAttribute('target');
                a.removeAttribute('rel');
            }
        });

        schedule();
    });

    document.getElementById('rmLink').addEventListener('click', () => {
        restore();
        document.execCommand('unlink');
        schedule();
    });

    document.getElementById('mkTable').addEventListener('click', () => {
        const cols = parseInt(prompt('How many columns?', '3'), 10);
        const rows = parseInt(prompt('How many rows, not counting the header?', '3'), 10);
        if (!cols || !rows) { return; }

        const head = '<tr>' + '<th>Heading</th>'.repeat(cols) + '</tr>';
        const body = ('<tr>' + '<td> </td>'.repeat(cols) + '</tr>').repeat(rows);
        restore();
        document.execCommand('insertHTML', false,
            '<table><thead>' + head + '</thead><tbody>' + body + '</tbody></table><p> </p>');
        schedule();
    });

    /* ---------------- images ----------------
     * Uploaded files are served from this app, and the figure markup matches
     * what the site's posts already use: figure, img with width and height so
     * the page does not jump as it loads, and an optional caption.
     */

    let pickerTarget = 'body';

    /*
     * Where the files go.
     *
     * The post's own slug by default, so a post's screenshots stay together
     * and are still findable by name in a year. It follows the slug until
     * the writer picks something else.
     */
    const folderLabel = document.getElementById('folderLabel');
    let folder = @json(old('slug', $post->slug) ?: '');
    let folderTouched = false;

    function showFolder() {
        folderLabel.textContent = '/uploads/blog/' + (folder || new Date().toISOString().slice(0, 7).replace('-', '/'));
    }

    function chooseFolder() {
        const picked = prompt('Which folder should images go in?\n\nLowercase letters, numbers, hyphens and slashes — for example ptcl or 2026/sales-tax.', folder);
        if (picked === null) { return; }
        folder = picked.trim().toLowerCase().replace(/[^a-z0-9\-\/]+/g, '-').replace(/^\/+|\/+$/g, '');
        folderTouched = true;
        showFolder();
    }

    document.getElementById('imgFolder').addEventListener('click', chooseFolder);
    document.getElementById('folderChange').addEventListener('click', e => { e.preventDefault(); chooseFolder(); });

    document.getElementById('imgUpload').addEventListener('click', () => {
        pickerTarget = 'body';
        remember();
        picker.multiple = true;
        picker.click();
    });

    document.getElementById('heroUpload').addEventListener('click', () => {
        pickerTarget = 'hero';
        picker.multiple = false;
        picker.click();
    });

    picker.addEventListener('change', () => {
        const files = Array.from(picker.files || []);
        picker.value = '';
        if (!files.length) { return; }

        if (pickerTarget === 'hero') {
            upload(files[0]).then(image => {
                document.getElementById('hero_image').value = image.url;
                drawHero();
                schedule();
            }).catch(report);

            return;
        }

        placeAll(files);
    });

    document.getElementById('imgUrl').addEventListener('click', () => {
        const url = prompt('Address of the image.\n\nA full https:// address, or a path like /blog/ptcl/form.jpg for an image already on the website.');
        if (!url) { return; }
        const alt = prompt('Alt text — what does the image show?', '') || '';
        restore();
        insertFigure({ url: url.trim(), width: null, height: null }, alt);
        schedule();
    });

    /** Upload each file in turn, so three screenshots land in the order chosen. */
    function placeAll(files) {
        const note = document.createElement('p');
        note.className = 'bl-busy';
        note.textContent = 'Uploading ' + files.length + ' image' + (files.length === 1 ? '' : 's') + '…';
        restore();
        insertNode(note);

        files.reduce((chain, file) => chain.then(() => {
            return upload(file).then(image => {
                const figure = buildFigure(image, '');
                note.parentNode.insertBefore(figure, note);
                pick(figure);
            });
        }), Promise.resolve())
            .catch(report)
            .finally(() => {
                const tail = document.createElement('p');
                tail.innerHTML = '<br>';
                note.parentNode.replaceChild(tail, note);
                schedule();
            });
    }

    function upload(file) {
        const data = new FormData();
        data.append('file', file);
        data.append('folder', folder);
        data.append('_token', token);

        return fetch(uploadUrl, { method: 'POST', body: data, headers: { 'Accept': 'application/json' } })
            .then(r => r.json().then(body => {
                if (!r.ok) {
                    throw new Error(body.message || (body.errors && body.errors.file && body.errors.file[0]) || 'Upload failed.');
                }

                return body;
            }));
    }

    function buildFigure(image, alt) {
        const figure = document.createElement('figure');
        const img = document.createElement('img');
        img.src = image.url;
        img.alt = alt || '';
        img.setAttribute('loading', 'lazy');
        if (image.width)  { img.setAttribute('width', image.width); }
        if (image.height) { img.setAttribute('height', image.height); }
        figure.appendChild(img);

        return figure;
    }

    function insertFigure(image, alt) {
        const figure = buildFigure(image, alt);
        insertNode(figure);
        pick(figure);
    }

    /**
     * Put a block between two blocks, never inside one.
     *
     * A figure dropped inside a paragraph produces <p>text<figure>..</figure></p>,
     * which no browser will keep - it closes the paragraph on re-parse and the
     * saved HTML stops matching what was on screen.
     */
    function insertNode(node) {
        const sel = window.getSelection();
        let range = saved;

        if (!range || !editor.contains(range.commonAncestorContainer)) {
            range = document.createRange();
            range.selectNodeContents(editor);
            range.collapse(false);
        }

        range.collapse(false);

        const block = outerBlock(range.startContainer);
        if (block) {
            editor.insertBefore(node, block.nextSibling);
        } else {
            range.insertNode(node);
        }

        const after = document.createRange();
        after.setStartAfter(node);
        after.collapse(true);
        sel.removeAllRanges();
        sel.addRange(after);
        saved = after.cloneRange();
    }

    /** The caret's own block, as a direct child of the editor. */
    function outerBlock(node) {
        let current = node && node.nodeType === 3 ? node.parentNode : node;

        while (current && current.parentNode && current.parentNode !== editor) {
            current = current.parentNode;
        }

        return current && current.parentNode === editor ? current : null;
    }

    function report(error) {
        alert(error.message || 'The image could not be uploaded.');
    }

    // Dropping a file onto the text puts it where it was dropped.
    editor.addEventListener('dragover', e => {
        if (e.dataTransfer && Array.from(e.dataTransfer.types || []).includes('Files')) { e.preventDefault(); }
    });

    editor.addEventListener('drop', e => {
        const files = Array.from((e.dataTransfer && e.dataTransfer.files) || [])
            .filter(f => f.type.startsWith('image/'));
        if (!files.length) { return; }

        e.preventDefault();

        const at = document.caretRangeFromPoint
            ? document.caretRangeFromPoint(e.clientX, e.clientY)
            : null;
        if (at) { saved = at; }

        placeAll(files);
    });

    /* ---------------- the image panel ---------------- */

    const panel = document.getElementById('imgPanel');
    const altBox = document.getElementById('imgAlt');
    const capBox = document.getElementById('imgCap');
    const sizeBox = document.getElementById('imgSize');
    let picked = null;

    function pick(figure) {
        editor.querySelectorAll('figure.bl-picked').forEach(f => f.classList.remove('bl-picked'));
        picked = figure;

        if (!figure) {
            panel.classList.add('d-none');

            return;
        }

        figure.classList.add('bl-picked');
        panel.classList.remove('d-none');

        const img = figure.querySelector('img');
        const caption = figure.querySelector('figcaption');
        altBox.value = img ? (img.getAttribute('alt') || '') : '';
        capBox.value = caption ? caption.textContent : '';
        sizeBox.value = figure.style.maxWidth || '';
    }

    editor.addEventListener('click', e => {
        const img = e.target.closest('img');
        if (!img) { pick(null); return; }

        let figure = img.closest('figure');
        if (!figure) {
            // An image pasted in as bare markup: wrap it so it can carry a caption.
            figure = document.createElement('figure');
            img.parentNode.insertBefore(figure, img);
            figure.appendChild(img);
        }

        pick(figure);
    });

    altBox.addEventListener('input', () => {
        if (!picked) { return; }
        const img = picked.querySelector('img');
        if (img) { img.setAttribute('alt', altBox.value); }
        schedule();
    });

    capBox.addEventListener('input', () => {
        if (!picked) { return; }
        let caption = picked.querySelector('figcaption');

        if (!capBox.value.trim()) {
            if (caption) { caption.remove(); }
        } else {
            if (!caption) {
                caption = document.createElement('figcaption');
                picked.appendChild(caption);
            }
            caption.textContent = capBox.value;
        }

        schedule();
    });

    sizeBox.addEventListener('change', () => {
        if (!picked) { return; }
        if (sizeBox.value) { picked.style.maxWidth = sizeBox.value; }
        else { picked.style.removeProperty('max-width'); }
        schedule();
    });

    document.getElementById('imgRemove').addEventListener('click', () => {
        if (!picked) { return; }
        picked.remove();
        pick(null);
        schedule();
    });

    /* ---------------- pasting ---------------- */

    editor.addEventListener('paste', e => {
        const clipboard = e.clipboardData || window.clipboardData;
        const images = Array.from((clipboard && clipboard.files) || []).filter(f => f.type.startsWith('image/'));

        if (images.length) {
            e.preventDefault();
            remember();
            placeAll(images);

            return;
        }

        e.preventDefault();
        document.execCommand('insertText', false, clipboard.getData('text/plain'));
        schedule();
    });

    /* ---------------- HTML view ---------------- */

    let showingHtml = false;
    document.getElementById('toggleHtml').addEventListener('click', function () {
        if (showingHtml) {
            editor.innerHTML = field.value;
        } else {
            field.value = editor.innerHTML;
        }
        showingHtml = !showingHtml;
        editor.classList.toggle('d-none', showingHtml);
        field.classList.toggle('d-none', !showingHtml);
        document.getElementById('bar').style.display = showingHtml ? 'none' : '';
        panel.classList.add('d-none');
        this.textContent = showingHtml ? 'Back to editor' : 'Edit HTML';
        schedule();
    });

    form.addEventListener('submit', () => {
        if (!showingHtml) { field.value = bodyHtml(); }
    });

    /** The body as it will be saved, without the editor's own selection marker. */
    function bodyHtml() {
        const copy = editor.cloneNode(true);
        copy.querySelectorAll('figure.bl-picked').forEach(f => f.classList.remove('bl-picked'));
        copy.querySelectorAll('.bl-busy').forEach(n => n.remove());
        copy.querySelectorAll('[class=""]').forEach(n => n.removeAttribute('class'));

        return copy.innerHTML;
    }

    /* ---------------- slug ---------------- */

    const title = document.getElementById('title');
    const slug = document.getElementById('slug');
    let slugTouched = slug.value !== '';

    slug.addEventListener('input', () => {
        slugTouched = true;
        if (!folderTouched) { folder = slug.value; showFolder(); }
        schedule();
    });
    title.addEventListener('input', () => {
        if (!slugTouched) {
            slug.value = title.value.toLowerCase().normalize('NFKD')
                .replace(/[^\w\s-]/g, '').trim().replace(/[\s_]+/g, '-').replace(/-+/g, '-').slice(0, 80);
            if (!folderTouched) { folder = slug.value; showFolder(); }
        }
        schedule();
    });

    /* ---------------- the analysis ---------------- */

    const usedKeyphrases = @json($usedKeyphrases ?? []);
    const watch = ['excerpt', 'focus_keyphrase', 'related_keyphrases', 'meta_title', 'meta_description',
                   'region', 'hero_image', 'hero_image_alt', 'read_time'];
    watch.forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.addEventListener('input', schedule); el.addEventListener('change', schedule); }
    });
    editor.addEventListener('input', schedule);
    field.addEventListener('input', schedule);

    let timer = null;
    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(run, 400);
    }

    function run() {
        const html = showingHtml ? field.value : bodyHtml();
        const result = window.BlogSeo.analyse({
            title: title.value,
            metaTitle: document.getElementById('meta_title').value.trim() || title.value,
            metaDescription: document.getElementById('meta_description').value.trim()
                || document.getElementById('excerpt').value.trim(),
            excerpt: document.getElementById('excerpt').value,
            slug: slug.value,
            html: html,
            keyphrase: document.getElementById('focus_keyphrase').value,
            synonyms: document.getElementById('related_keyphrases').value.split(',').map(s => s.trim()).filter(Boolean),
            usedKeyphrases: usedKeyphrases,
            heroImage: document.getElementById('hero_image').value.trim(),
            heroAlt: document.getElementById('hero_image_alt').value.trim()
        });

        paint('seoChecks', 'seoDot', 'seoScoreText', result.seo);
        paint('readChecks', 'readDot', 'readScoreText', result.readability);

        document.getElementById('seoScoreField').value = result.seo.score === null ? '' : result.seo.score;
        document.getElementById('readScoreField').value = result.readability.score === null ? '' : result.readability.score;

        const chipBand = window.BlogSeo.band(result.seo.score);
        document.getElementById('scoreChipDot').className = 'bl-dot ' + chipBand;
        document.getElementById('scoreChipText').textContent =
            'SEO ' + (result.seo.score === null ? '—' : result.seo.score) + ' · ' + result.words + ' words';

        const readTime = document.getElementById('read_time');
        if (!readTime.value.trim()) { readTime.placeholder = result.readMinutes + ' min read'; }

        snippet(result);
    }

    function paint(listId, dotId, textId, part) {
        const list = document.getElementById(listId);
        list.innerHTML = '';

        part.checks.forEach(check => {
            const li = document.createElement('li');
            const dot = document.createElement('span');
            dot.className = 'bl-dot ' + check.status;
            const text = document.createElement('span');
            text.textContent = check.text;
            li.appendChild(dot);
            li.appendChild(text);
            list.appendChild(li);
        });

        const band = window.BlogSeo.band(part.score);
        document.getElementById(dotId).className = 'bl-dot ' + band;
        document.getElementById(textId).textContent = part.score === null
            ? 'Needs a keyphrase'
            : part.score + ' / 100 · ' + ({ good: 'Good', ok: 'Needs work', bad: 'Poor' }[band] || '');
    }

    function snippet(result) {
        const metaTitle = document.getElementById('meta_title').value.trim() || title.value || 'Untitled';
        const description = document.getElementById('meta_description').value.trim()
            || document.getElementById('excerpt').value.trim();
        const region = document.getElementById('region').value;

        document.getElementById('snipUrl').textContent =
            'fairtaxint.com › ' + region + ' › blog › ' + (slug.value || 'slug');
        document.getElementById('snipTitle').textContent = clip(metaTitle, 65);
        document.getElementById('snipDesc').textContent = description
            ? clip(description, 158)
            : 'Google will pick a sentence from the page.';

        meter('titleMeter', 'titleHint', result.titlePixels, 400, 600, 'px of about 600 pixels');
        meter('descMeter', 'descHint', description.length, 120, 156, 'of 156 characters');
    }

    function meter(meterId, hintId, value, low, high, unit) {
        const bar = document.getElementById(meterId);
        const fill = bar.querySelector('span');
        fill.style.width = Math.min(100, Math.round(value / high * 100)) + '%';
        bar.classList.toggle('over', value > high);
        bar.classList.toggle('under', value > 0 && value < low);
        document.getElementById(hintId).textContent = value + ' ' + unit +
            (value > high ? ' — will be cut off' : value === 0 ? '' : value < low ? ' — room for more' : '');
    }

    function clip(text, max) {
        return text.length > max ? text.slice(0, max - 1).trim() + '…' : text;
    }

    function drawHero() {
        const url = document.getElementById('hero_image').value.trim();
        document.getElementById('heroPreview').innerHTML = url
            ? '<img src="' + escapeAttr(url) + '" alt="" style="max-width:100%;border-radius:var(--radius-sm);border:1px solid var(--border)">'
            : '';
    }

    document.getElementById('hero_image').addEventListener('input', drawHero);

    function escapeHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function escapeAttr(s) {
        return escapeHtml(s).replace(/"/g, '&quot;');
    }

    showFolder();
    drawHero();
    run();

    @error('publish_password')
        const failed = document.getElementById('publishModal');
        if (failed) {
            new bootstrap.Modal(failed).show();
            document.getElementById('publishPassword').focus();
        }
    @enderror
})();
</script>
@endsection
