@extends('layouts.app')
@section('title', 'Settings - Social Studio')
@section('page-title', 'Social Studio')

@section('content')
@include('social._nav')

<form method="POST" action="{{ route('social.settings.update') }}" enctype="multipart/form-data">
@csrf
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-key me-1"></i> Claude</h6>
                <label class="form-label small fw-semibold">API key
                    @if($hasKey)<span class="badge bg-success ms-1">saved</span>@else<span class="badge bg-warning text-dark ms-1">not set</span>@endif
                </label>
                <input type="password" name="claude_api_key" class="form-control mb-1" autocomplete="off" placeholder="{{ $hasKey ? 'Leave blank to keep the saved key' : 'sk-ant-…' }}">
                <div class="form-text mb-3">From console.anthropic.com &gt; API keys. Stored encrypted.
                    @if($hasKey)<label class="ms-2"><input type="checkbox" name="remove_api_key" value="1"> remove saved key</label>@endif</div>
                <div class="row g-2">
                    <div class="col-7">
                        <label class="form-label small fw-semibold">Model</label>
                        <input name="claude_model" class="form-control" value="{{ $model }}">
                        <div class="form-text">claude-sonnet-5-5 is the sensible default; claude-opus-5-5 writes better Urdu at about twice the cost.</div>
                    </div>
                    <div class="col-5">
                        <label class="form-label small fw-semibold">Fact research</label>
                        <div class="form-check form-switch mt-1">
                            <input type="hidden" name="web_search" value="0">
                            <input class="form-check-input" type="checkbox" name="web_search" value="1" id="ws" @checked($webSearch)>
                            <label class="form-check-label small" for="ws">Search the web before writing</label>
                        </div>
                        <div class="form-text">About $0.01 per search; up to 4 per post.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-palette me-1"></i> Brand</h6>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Brand name</label><input name="brand_name" class="form-control" value="{{ $brand['name'] }}" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">TikTok handle</label><input name="handle" class="form-control" value="{{ $brand['handle'] }}" placeholder="@fairtaxint"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Website</label><input name="website" class="form-control" value="{{ $brand['website'] }}" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">WhatsApp number</label><input name="whatsapp" class="form-control" value="{{ $brand['whatsapp'] }}" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Default call to action</label><input name="cta" class="form-control" dir="auto" value="{{ $brand['cta'] }}"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">End-card line</label><input name="tagline" class="form-control" value="{{ $brand['tagline'] }}"></div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Urdu typeface</label>
                        <div>
                            <label class="me-3"><input type="radio" name="urdu_font" value="nastaliq" @checked($brand['urduFont'] !== 'naskh')> Nastaliq <span class="text-muted small">(traditional, what Urdu readers expect)</span></label>
                            <label><input type="radio" name="urdu_font" value="naskh" @checked($brand['urduFont'] === 'naskh')> Naskh <span class="text-muted small">(compact, fits more text)</span></label>
                        </div>
                    </div>
                </div>

                <label class="form-label small fw-semibold mt-3">Colours</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($brand['colors'] as $k => $hex)
                    <label class="text-center small" style="width:64px">
                        <input type="color" name="colors[{{ $k }}]" value="{{ $hex }}" class="form-control form-control-color w-100">
                        {{ $k }}
                    </label>
                    @endforeach
                </div>

                <label class="form-label small fw-semibold mt-3">Logos <span class="text-muted fw-normal">(PNG with transparent background)</span></label>
                <div class="row g-2 align-items-end">
                    @foreach(['dark' => ['logo_dark', 'On navy', 'logoOnDark', '#0B2744'], 'blue' => ['logo_blue', 'On blue', 'logoOnBlue', '#1982C3'], 'light' => ['logo_light', 'On light', 'logoOnLight', '#F3F8FD']] as $bg => [$field, $label, $kitKey, $swatch])
                    <div class="col-4">
                        <div class="rounded p-2 mb-1 text-center" style="background: {{ $swatch }}"><img src="{{ $brand[$kitKey] }}" style="max-width:100%;height:34px;object-fit:contain"></div>
                        <div class="small">{{ $label }} @if($customLogos[$bg])<span class="badge bg-light text-dark border">custom</span>@endif</div>
                        <input type="file" name="{{ $field }}" accept="image/png" class="form-control form-control-sm">
                    </div>
                    @endforeach
                </div>
                <label class="small mt-2"><input type="checkbox" name="reset_logos" value="1"> Go back to the standard logos</label>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-1"><i class="bi bi-chat-quote me-1"></i> What Claude knows</h6>
                <p class="text-muted small">Claude may state the brand facts without a source; everything else it must verify. Keep prices, phone numbers and client counts here up to date.</p>
                <label class="form-label small fw-semibold">Brand facts</label>
                <textarea name="facts" rows="9" class="form-control mb-3" style="font-size:.84rem">{{ $facts }}</textarea>
                <label class="form-label small fw-semibold">Voice and language rules</label>
                <textarea name="voice" rows="7" class="form-control mb-3" style="font-size:.84rem">{{ $voice }}</textarea>
                <label class="form-label small fw-semibold">Default audience</label>
                <input name="audience" class="form-control" value="{{ $audience }}">
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-1"><i class="bi bi-calendar-check me-1"></i> Daily drafts</h6>
                <p class="text-muted small">Each day Claude drafts posts from the idea bank into the review queue (it refills the bank itself when it runs low). Nothing goes out without approval.</p>
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="daily_enabled" value="0">
                    <input class="form-check-input" type="checkbox" name="daily_enabled" value="1" id="de" @checked($daily['enabled'])>
                    <label class="form-check-label" for="de">Draft posts every day</label>
                </div>
                <div class="row g-2">
                    <div class="col-4"><label class="form-label small">From (PKT)</label>
                        <select name="daily_hour" class="form-select form-select-sm">@for($h = 0; $h < 24; $h++)<option value="{{ $h }}" @selected($daily['hour'] === $h)>{{ sprintf('%02d:00', $h) }}</option>@endfor</select></div>
                    <div class="col-3"><label class="form-label small">Posts</label>
                        <input type="number" name="daily_count" min="1" max="10" class="form-control form-control-sm" value="{{ $daily['count'] }}"></div>
                    <div class="col-5"><label class="form-label small">Formats (rotated)</label><div>
                        @foreach(\App\Models\SocialPost::FORMATS as $k => $l)
                            <label class="me-2 small"><input type="checkbox" name="daily_formats[]" value="{{ $k }}" @checked(in_array($k, $daily['formats']))> {{ $l }}</label>
                        @endforeach
                    </div></div>
                </div>
                <div class="form-text">Runs from the hourly cron job (<code>php artisan social:run</code>). @if($daily['last'])Last run: {{ $daily['last'] }}.@endif</div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-1"><i class="bi bi-music-note-beamed me-1"></i> Video music <span class="text-muted fw-normal small">(optional)</span></h6>
                <p class="text-muted small">Laid under every rendered video, faded out at the end. Leave empty to add a TikTok sound when you upload instead - trending sounds usually reach more people.</p>
                @if($music)<div class="small mb-2"><i class="bi bi-check-circle text-success"></i> A track is saved. <label class="ms-2"><input type="checkbox" name="remove_music" value="1"> remove</label></div>@endif
                <input type="file" name="music" accept=".mp3,.m4a,.aac,.wav" class="form-control form-control-sm">
                <div class="form-text">Only use music you have the rights to.</div>
            </div>
        </div>
    </div>
</div>
<button class="btn btn-accent"><i class="bi bi-check2 me-1"></i> Save settings</button>
</form>

<div class="card mt-4">
    <div class="card-body">
        <h6 class="mb-1"><i class="bi bi-hdd-rack me-1"></i> Render worker (VPS)</h6>
        <p class="text-muted small mb-2">Videos are rendered by a small program on the Linux VPS, because shared hosting cannot run a browser. Setup steps are in <code>render-worker/README.md</code> in the repository.</p>
        <div class="row g-2 small">
            <div class="col-md-6"><b>APP_URL</b><div class="font-monospace bg-light rounded p-2 mt-1">{{ url('/') }}</div></div>
            <div class="col-md-6"><b>RENDER_TOKEN</b>
                <div class="input-group input-group-sm mt-1">
                    <input type="password" class="form-control font-monospace" value="{{ $renderToken }}" readonly id="rt">
                    <button class="btn btn-outline-secondary" type="button" onclick="const i=document.getElementById('rt');i.type=i.type==='password'?'text':'password'"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('rt').value)"><i class="bi bi-clipboard"></i></button>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 mt-2">
            <span class="small">@if($lastWorker)<i class="bi bi-circle-fill text-success" style="font-size:.5rem"></i> Worker last checked in {{ \Carbon\Carbon::parse($lastWorker)->diffForHumans() }}@else<i class="bi bi-circle-fill text-secondary" style="font-size:.5rem"></i> No worker has connected yet @endif</span>
            <form method="POST" action="{{ route('social.settings.rotate-token') }}" onsubmit="return confirm('The worker will stop until you give it the new token. Continue?')">@csrf
                <button class="btn btn-sm btn-outline-danger">New token</button>
            </form>
        </div>
    </div>
</div>
@endsection
