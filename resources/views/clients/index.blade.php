@extends('layouts.app')
@section('title', 'Client Credentials')
@section('page-title', 'Client Credentials')

@section('content')
<!-- Search & Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('clients.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <div style="position: relative;">
                        <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--n-400);"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, or FBR username..." value="{{ request('search') }}" style="padding-left: 40px;">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Types</option>
                        <option value="Individual" {{ request('status') == 'Individual' ? 'selected' : '' }}>Individual</option>
                        <option value="AOP" {{ request('status') == 'AOP' ? 'selected' : '' }}>AOP</option>
                        <option value="Company" {{ request('status') == 'Company' ? 'selected' : '' }}>Company</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="service" class="form-select">
                        <option value="">All Services</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ request('service') == $service->id ? 'selected' : '' }}>{{ $service->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> Search</button>
                    @if(request()->hasAny(['search', 'status', 'service']))
                        <a href="{{ route('clients.index') }}" class="btn btn-outline-primary" title="Clear"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div style="font-size: 0.85rem; color: var(--n-500);">
        Showing <strong>{{ $clients->total() }}</strong> client{{ $clients->total() !== 1 ? 's' : '' }}
        @if(request('search')) for "<strong>{{ request('search') }}</strong>" @endif
        @if(request('status')) &middot; Type: <strong>{{ request('status') }}</strong> @endif
    </div>
    <a href="{{ route('clients.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1"></i> Add Client</a>
</div>

<!-- Clients Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>FBR</th>
                    <th>Services</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 36px; height: 36px; background: var(--n-100); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.7rem; color: var(--primary); flex-shrink: 0;">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
                            <div>
                                <a href="{{ route('clients.show', $client) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.88rem;">{{ $client->name }}</a>
                                @if($client->email)<div style="font-size: 0.75rem; color: var(--n-400);">{{ $client->email }}</div>@endif
                            </div>
                        </div>
                    </td>
                    <td style="font-size: 0.85rem; color: var(--n-500);">{{ $client->contact_no ?: '-' }}</td>
                    <td>
                        @if($client->status == 'Individual')
                            <span class="badge" style="background: var(--n-100); color: var(--primary);">Individual</span>
                        @elseif($client->status == 'AOP')
                            <span class="badge" style="background: var(--accent-glow); color: var(--accent-dark);">AOP</span>
                        @else
                            <span class="badge" style="background: var(--info-tint); color: var(--info-ink);">Company</span>
                        @endif
                    </td>
                    <td>
                        @if($client->fbr_username)
                            <span style="font-family: monospace; font-size: 0.78rem; color: var(--n-500);">{{ $client->fbr_username }}</span>
                            {{-- Shown only once the extension has announced itself, so it is
                                 never a button that quietly does nothing. --}}
                            <span class="ext-only" hidden>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-1 portal-go"
                                        data-client="{{ $client->id }}" data-portal="fbr"
                                        title="Open IRIS signed in as {{ $client->name }}">
                                    <i class="bi bi-box-arrow-up-right"></i> IRIS
                                </button>
                            </span>
                        @else
                            <span style="color: var(--n-300);">-</span>
                        @endif
                        @if($client->kpra_username)
                            <span class="ext-only" hidden>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-1 portal-go"
                                        data-client="{{ $client->id }}" data-portal="kpra"
                                        title="Open KPRA signed in as {{ $client->name }}">KPRA</button>
                            </span>
                        @endif
                    </td>
                    <td>
                        @php $svcCount = $client->active_services_count; @endphp
                        @if($svcCount > 0)
                            <span class="badge" style="background: var(--ok-tint); color: var(--ok-ink);">{{ $svcCount }} active</span>
                        @else
                            <span style="color: var(--n-300); font-size: 0.82rem;">None</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('clients.destroy', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this client?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color: var(--n-400);">
                        @if(request()->hasAny(['search', 'status', 'service']))
                            <i class="bi bi-search" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                            No clients match your search. <a href="{{ route('clients.index') }}" style="color: var(--primary); font-weight: 600;">Clear filters</a>
                        @else
                            <i class="bi bi-people" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                            No clients yet. <a href="{{ route('clients.create') }}" style="color: var(--primary); font-weight: 600;">Add your first client</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $clients->links() }}</div>
@endsection

@section('scripts')
<script>
/*
 * "Open IRIS signed in" hands the job to the browser extension: the page has no
 * business holding a client's password, and a cross-site form post would not
 * survive the portal's own session handling anyway.
 *
 * The extension marks the document when it loads, so the buttons appear only
 * where they will work.
 */
(function () {
    var waiting = {};
    var seq = 0;

    function extensionReady() {
        return document.documentElement.getAttribute('data-fairtax-extension') === 'ready';
    }

    function reveal() {
        if (!extensionReady()) { return; }
        document.querySelectorAll('.ext-only').forEach(function (el) { el.hidden = false; });
    }

    // The content script may land after this page's own script.
    reveal();
    new MutationObserver(reveal).observe(document.documentElement, {
        attributes: true, attributeFilter: ['data-fairtax-extension'],
    });

    window.addEventListener('message', function (e) {
        if (e.source !== window) { return; }
        var d = e.data;
        if (!d || d.source !== 'fairtax-extension' || d.action !== 'openPortalResult') { return; }

        var btn = waiting[d.requestId];
        if (!btn) { return; }
        delete waiting[d.requestId];

        btn.disabled = false;
        btn.innerHTML = btn.dataset.label;

        var r = d.result || {};
        if (r.ok) { return; }

        alert(
            r.error === 'signed-out'      ? 'The extension is signed out. Open it from the toolbar and sign in again.'
          : r.error === 'no-credentials'  ? 'No password is stored for this client on that portal.'
          : r.error === 'lookup-failed'   ? 'Could not read the credentials. Check you are still signed in to the app.'
                                          : 'The extension could not open the portal.'
        );
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.portal-go');
        if (!btn) { return; }

        if (!extensionReady()) {
            alert('The FairTax extension is not installed in this browser. Get it from Administration → Chrome Extension.');
            return;
        }

        var id = 'p' + (++seq);
        waiting[id] = btn;
        btn.dataset.label = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = 'Opening…';

        window.postMessage({
            source: 'fairtax-app',
            action: 'openPortal',
            requestId: id,
            clientId: btn.dataset.client,
            portal: btn.dataset.portal,
        }, '*');

        // If the extension never answers, give the button back.
        setTimeout(function () {
            if (!waiting[id]) { return; }
            delete waiting[id];
            btn.disabled = false;
            btn.innerHTML = btn.dataset.label;
        }, 8000);
    });
})();
</script>
@endsection
