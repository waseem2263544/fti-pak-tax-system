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
    .bl-editor a { color: var(--accent-dark); }
    .bl-bar { display: flex; flex-wrap: wrap; gap: 4px; padding: 8px; border: 1px solid var(--border);
              border-bottom: 0; border-radius: var(--radius) var(--radius) 0 0; background: var(--surface-sunk); }
    .bl-bar button { border: 1px solid transparent; background: transparent; border-radius: var(--radius-sm);
                     padding: 4px 9px; font-size: .78rem; cursor: pointer; color: var(--text-soft); }
    .bl-bar button:hover { background: var(--n-100); color: var(--text); }
    .bl-side label { font-size: .78rem; }
    .bl-html { font: 12px/1.6 ui-monospace, Menlo, monospace; }
</style>
@endsection

@section('content')
<form method="POST" action="{{ $post->exists ? route('blog.update', $post) : route('blog.store') }}">
    @csrf
    @if($post->exists) @method('PUT') @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('blog.index') }}" class="btn btn-sm btn-outline-primary">← All posts</a>
        <div class="d-flex gap-2 align-items-center">
            @if($post->exists && $post->status === 'published')
                <a href="https://fairtaxint.com/{{ $post->region }}/blog/{{ $post->slug }}" target="_blank"
                   class="btn btn-sm btn-outline-primary">View live</a>
            @endif
            <button name="status" value="draft" class="btn btn-sm btn-outline-primary">Save as draft</button>
            <button name="status" value="published" class="btn btn-sm btn-accent">
                {{ $post->status === 'published' ? 'Save & refresh site' : 'Publish' }}
            </button>
        </div>
    </div>

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
                        <div class="form-text">Shown on the blog index and used as the meta description.</div>
                    </div>
                </div>
            </div>

            <div class="card">
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
                        <button type="button" id="mkTable">Table</button>
                        <button type="button" data-cmd="removeFormat">Clear</button>
                    </div>
                    <div class="bl-editor" id="editor" contenteditable="true">{!! old('content', $post->content) !!}</div>
                    <textarea name="content" id="content" class="form-control bl-html d-none" rows="24">{{ old('content', $post->content) }}</textarea>
                    <div class="form-text mt-2">
                        The site renders this HTML as written. Headings, lists, tables and links are styled by
                        the site's own stylesheet, so keep the markup plain.
                    </div>
                </div>
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
                <div class="card-header"><strong>Hero image</strong></div>
                <div class="card-body">
                    <input type="text" name="hero_image" class="form-control form-control-sm mb-2 bl-html"
                           value="{{ old('hero_image', $post->hero_image) }}" placeholder="/blog/something.jpg">
                    <input type="text" name="hero_image_alt" class="form-control form-control-sm"
                           value="{{ old('hero_image_alt', $post->hero_image_alt) }}" placeholder="What the image shows">
                    <div class="form-text">
                        A path within the website's public folder. Left blank, the site draws its own card.
                    </div>
                </div>
            </div>

            @if($post->exists)
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div style="font-size: .78rem; color: var(--text-muted);">
                            @if($post->author) Created by {{ $post->author->name }}<br>@endif
                            Updated {{ $post->updated_at?->diffForHumans() }}
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="if(confirm('Delete this post? If it is live, the page disappears from the site.')) document.getElementById('delForm').submit();">
                            Delete
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</form>

@if($post->exists)
    <form method="POST" id="delForm" action="{{ route('blog.destroy', $post) }}" class="d-none">
        @csrf @method('DELETE')
    </form>
@endif
@endsection

@section('scripts')
<script>
/*
 * A plain rich-text editor over contenteditable.
 *
 * The site renders the body as raw HTML, so what matters is that the markup
 * stays simple - h2, h3, p, lists, tables, links. Pasted content is stripped
 * to text for that reason: a paste from Word carries a kilobyte of styling
 * that the site's own stylesheet then has to fight.
 */
(function () {
    const editor = document.getElementById('editor');
    const field  = document.getElementById('content');
    const form   = editor.closest('form');

    document.getElementById('bar').addEventListener('click', e => {
        const b = e.target.closest('button[data-cmd]');
        if (!b) return;
        editor.focus();
        document.execCommand(b.dataset.cmd, false, b.dataset.arg || null);
    });

    document.getElementById('mkLink').addEventListener('click', () => {
        const url = prompt('Link to where?\n\nUse a full https:// address, or a path like /pk/contact for this site.');
        if (!url) return;
        editor.focus();
        document.execCommand('createLink', false, url);
    });

    document.getElementById('mkTable').addEventListener('click', () => {
        const cols = parseInt(prompt('How many columns?', '3'), 10);
        const rows = parseInt(prompt('How many rows, not counting the header?', '3'), 10);
        if (!cols || !rows) return;

        const head = '<tr>' + '<th>Heading</th>'.repeat(cols) + '</tr>';
        const body = ('<tr>' + '<td> </td>'.repeat(cols) + '</tr>').repeat(rows);
        editor.focus();
        document.execCommand('insertHTML', false, '<table><thead>' + head + '</thead><tbody>' + body + '</tbody></table><p> </p>');
    });

    // Paste as text, so Word and Google Docs cannot smuggle styling in.
    editor.addEventListener('paste', e => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, text);
    });

    // The HTML view and the rich view are two windows onto one value.
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
        this.textContent = showingHtml ? 'Back to editor' : 'Edit HTML';
    });

    form.addEventListener('submit', () => {
        if (!showingHtml) { field.value = editor.innerHTML; }
    });

    // A slug follows the title until someone types their own.
    const title = document.getElementById('title');
    const slug = document.getElementById('slug');
    let slugTouched = slug.value !== '';

    slug.addEventListener('input', () => { slugTouched = true; });
    title.addEventListener('input', () => {
        if (slugTouched) return;
        slug.value = title.value.toLowerCase().normalize('NFKD')
            .replace(/[^\w\s-]/g, '').trim().replace(/[\s_]+/g, '-').replace(/-+/g, '-').slice(0, 80);
    });
})();
</script>
@endsection
