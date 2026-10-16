@extends('layouts.app')
@section('title', 'Website connection')
@section('page-title', 'Website connection')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('blog.index') }}" class="btn btn-sm btn-outline-primary">← All posts</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('blog.settings.save') }}">
            @csrf
            <div class="card mb-3">
                <div class="card-header"><strong>How this app reaches fairtaxint.com</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="revalidate_url">Refresh address</label>
                        <input type="text" name="revalidate_url" id="revalidate_url" class="form-control form-control-sm"
                               value="{{ old('revalidate_url', $revalidateUrl) }}"
                               placeholder="https://fairtaxint.com/api/revalidate">
                        <div class="form-text">
                            Called when you publish, so the website rebuilds that page within seconds instead of
                            waiting up to five minutes.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="revalidate_secret">Shared secret</label>
                        <input type="password" name="revalidate_secret" id="revalidate_secret"
                               class="form-control form-control-sm" autocomplete="new-password"
                               placeholder="{{ $hasSecret ? 'set — leave blank to keep it' : 'not set yet' }}">
                        <div class="form-text">
                            The same value must sit in Vercel as <code>BLOG_REVALIDATE_SECRET</code>. It is stored
                            encrypted here and never shown again. Set it once; it is not asked for when you publish.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="media_base">Image address</label>
                        <input type="text" name="media_base" id="media_base" class="form-control form-control-sm"
                               value="{{ old('media_base', $mediaBase) }}"
                               placeholder="https://fairtaxint.com/blog-media">
                        <div class="form-text">
                            Where uploaded images are linked from. The website passes this path through to the app,
                            so a post's pictures carry your own domain. Left blank, they are linked from this app.
                        </div>
                    </div>

                    <hr>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="require_password" value="1"
                               id="require_password" @checked(old('require_password', $askPassword))>
                        <label class="form-check-label" for="require_password">
                            Ask for my password before publishing
                        </label>
                        <div class="form-text">
                            Publishing puts a page on a public website. Saving a draft never asks.
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size: .78rem;">Saving also tests the connection.</span>
                    <button class="btn btn-sm btn-accent">Save and test</button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><strong>Setting this up once</strong></div>
            <div class="card-body" style="font-size: .84rem; line-height: 1.6;">
                <p>Pick any long random string. Put it in two places:</p>
                <ol class="ps-3">
                    <li class="mb-2">Here, in <strong>Shared secret</strong>.</li>
                    <li>
                        In Vercel — your project → Settings → Environment Variables →
                        <code>BLOG_REVALIDATE_SECRET</code> → then redeploy.
                    </li>
                </ol>
                <p class="mb-0 text-muted">
                    That is the whole of it. Nothing here is asked for again, and publishing never prompts for
                    the secret — only, if you want it, for your own password.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
