@extends('layouts.app')
@section('title', 'Income Tax Returns')
@section('page-title', 'Income Tax Return Filing')

@section('content')
@php
    $colors = [
        'not_yet_contacted'   => ['#6b7280', '#f3f4f6', '#9ca3af'],
        'documents_requested' => ['#1e40af', '#dbeafe', '#3b82f6'],
        'working'             => ['#92400e', '#fef3c7', '#f59e0b'],
        'sent_for_review'     => ['#6d28d9', '#ede9fe', '#8b5cf6'],
        'filed'               => ['#065f46', '#d1fae5', '#10b981'],
        'not_required'        => ['#475569', '#e2e8f0', '#64748b'],
    ];
@endphp

<style>
    .itr-select { border: none; border-radius: 20px; padding: 5px 28px 5px 12px; font-size: 0.8rem; font-weight: 600; cursor: pointer; -webkit-appearance: none; appearance: none; background-repeat: no-repeat; background-position: right 8px center; background-size: 12px; }
    @foreach($colors as $key => $c)
    .st-{{ $key }} { color: {{ $c[0] }}; background-color: {{ $c[1] }}; }
    @endforeach
    .itr-remarks { border: 1px solid transparent; border-radius: 6px; padding: 5px 8px; width: 100%; font-size: 0.82rem; background: #f8f9fb; transition: border-color .15s; }
    .itr-remarks:focus { border-color: var(--accent, #8b9a00); background: #fff; outline: none; }
    .saved-tick { color: #10b981; opacity: 0; transition: opacity .2s; font-size: 0.9rem; }
    .saved-tick.show { opacity: 1; }
    .dist-seg { height: 100%; float: left; }
    .stat-mini .num { font-size: 1.5rem; font-weight: 700; color: var(--primary); line-height: 1; }
    .stat-mini .pct { font-size: 0.85rem; font-weight: 600; }
    .stat-mini .lbl { font-size: 0.72rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .4px; margin-top: 4px; }
    /* toolbar polish */
    .itr-toolbar .input-group-text { border-right: 0; padding-right: 4px; }
    .itr-toolbar .input-group .form-control { border-left: 0; padding-left: 4px; box-shadow: none; }
    .itr-toolbar .input-group:focus-within { border-radius: 6px; box-shadow: 0 0 0 .2rem rgba(48,58,80,.08); }
    #itrFilterForm .ts-wrapper { margin: 0; }
    #itrFilterForm .ts-control { min-height: 31px; padding: 2px 8px; border-radius: 6px; font-size: 0.85rem; border-color: #dee2e6; box-shadow: none; }
    #itrFilterForm .ts-control .item { background: #eef0f3; color: var(--primary); border-radius: 12px; font-size: 0.78rem; padding: 1px 8px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div style="color:#6b7280; font-size:0.88rem;">Clients whose active service is <strong>Income Tax Return</strong>. New such clients appear here automatically as <em>Not yet contacted</em>.</div>
    <div style="font-size:0.85rem; color:#6b7280;">Filed: <strong id="filedPctText" style="color:#10b981;">{{ $filedPct }}%</strong> of {{ $total }}</div>
</div>

<!-- Distribution bar -->
<div style="height:12px; border-radius:6px; overflow:hidden; background:#eef0f3; margin-bottom:18px;">
    <div style="height:100%; width:100%;">
        @foreach($statuses as $key => $label)
        <div class="dist-seg" id="seg-{{ $key }}" style="width: {{ $counts[$key]['pct'] }}%; background: {{ $colors[$key][2] }};" title="{{ $label }}: {{ $counts[$key]['count'] }}"></div>
        @endforeach
    </div>
</div>

<!-- Dashboard cards -->
<div class="row g-3 mb-4">
    <div class="col">
        <a href="{{ route('income-tax-returns.index', array_filter(['exclude' => request('exclude'), 'mine' => request()->boolean('mine') ? 1 : null])) }}" class="card stat-card text-decoration-none {{ !request('status') ? 'border-2' : '' }}" style="{{ !request('status') ? 'border:2px solid var(--accent);' : '' }}">
            <div class="stat-mini">
                <div class="num" id="count-total">{{ $total }}</div>
                <div class="lbl">Total Clients</div>
            </div>
        </a>
    </div>
    @foreach($statuses as $key => $label)
    <div class="col">
        <a href="{{ route('income-tax-returns.index', array_filter(['status' => $key, 'exclude' => request('exclude'), 'mine' => request()->boolean('mine') ? 1 : null])) }}" class="card stat-card text-decoration-none" style="{{ request('status') === $key ? 'border:2px solid '.$colors[$key][2].';' : '' }}">
            <div class="stat-mini">
                <div class="d-flex align-items-baseline gap-2">
                    <span class="num" id="count-{{ $key }}">{{ $counts[$key]['count'] }}</span>
                    <span class="pct" id="pct-{{ $key }}" style="color:{{ $colors[$key][2] }};">{{ $counts[$key]['pct'] }}%</span>
                </div>
                <div class="lbl" style="color:{{ $colors[$key][0] }};">{{ $label }}</div>
            </div>
        </a>
    </div>
    @endforeach
</div>

<!-- Search -->
@php
    $mineOn = request()->boolean('mine');
    $excludeSel = array_map('intval', (array) request('exclude', []));
    $anyFilter = request('q') || request('status') || $mineOn || !empty($excludeSel);
@endphp
<div class="card mb-3 itr-toolbar">
    <div class="card-body d-flex align-items-center gap-2 flex-wrap" style="padding:12px 16px;">
        {{-- Quick-filter chips --}}
        @unless($showSkipped)
        <a href="{{ route('income-tax-returns.index', array_filter(['status' => request('status'), 'q' => request('q'), 'exclude' => request('exclude'), 'mine' => $mineOn ? null : 1])) }}"
           class="btn btn-sm rounded-pill {{ $mineOn ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-person-check me-1"></i>Assigned to me @if($mineCount)<span class="badge rounded-pill {{ $mineOn ? 'bg-white text-primary' : 'bg-light text-dark' }} ms-1">{{ $mineCount }}</span>@endif
        </a>
        @endunless
        <a href="{{ $showSkipped ? route('income-tax-returns.index') : route('income-tax-returns.index', ['skipped' => 1]) }}"
           class="btn btn-sm rounded-pill {{ $showSkipped ? 'btn-secondary' : 'btn-outline-secondary' }}">
            <i class="bi bi-eye-slash me-1"></i>{{ $showSkipped ? 'Back to active' : 'Skipped' }} @if($skippedCount && !$showSkipped)<span class="badge rounded-pill bg-light text-dark ms-1">{{ $skippedCount }}</span>@endif
        </a>

        {{-- Search + exclude, right-aligned --}}
        <form method="GET" id="itrFilterForm" class="d-flex align-items-center gap-2 flex-wrap ms-auto">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            @if($mineOn && !$showSkipped)<input type="hidden" name="mine" value="1">@endif
            @if($showSkipped)<input type="hidden" name="skipped" value="1">@endif

            <div class="d-flex align-items-center gap-1" title="Hide clients assigned to these people">
                <i class="bi bi-funnel text-muted"></i>
                <select name="exclude[]" multiple class="tom-exclude" placeholder="Exclude assignee…" style="min-width:210px;">
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ in_array($u->id, $excludeSel, true) ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="input-group input-group-sm" style="width:230px;">
                <button type="submit" class="input-group-text bg-white text-muted" style="cursor:pointer;"><i class="bi bi-search"></i></button>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search client…">
            </div>

            @if($anyFilter)
            <a href="{{ route('income-tax-returns.index', $showSkipped ? ['skipped' => 1] : []) }}" class="btn btn-sm btn-link text-muted text-decoration-none px-1"><i class="bi bi-x-lg me-1"></i>Clear</a>
            @endif
        </form>
    </div>
</div>
@if($showSkipped)
<div class="alert alert-secondary py-2" style="font-size:0.85rem;"><i class="bi bi-eye-slash me-1"></i>Showing <strong>skipped</strong> clients — these are hidden from the main list. Use the restore button to bring one back.</div>
@endif

<!-- Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Client</th>
                    <th style="width:160px;">Contact</th>
                    <th style="width:150px;">Folder</th>
                    <th style="width:190px;">Status</th>
                    <th style="width:170px;">Assigned To</th>
                    <th>Remarks</th>
                    <th style="width:150px;">Last Updated</th>
                    <th style="width:44px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $i => $client)
                <tr data-client="{{ $client->id }}">
                    <td style="color:#9ca3af;">{{ $i + 1 }}</td>
                    <td>
                        <a href="{{ route('clients.show', $client) }}" style="font-weight:600; color:var(--primary); text-decoration:none;">{{ $client->name }}</a>
                    </td>
                    <td>
                        @if($client->tracker_contact)
                        <a href="tel:{{ $client->tracker_contact }}" class="text-decoration-none" style="color:var(--primary); font-weight:500;"><i class="bi bi-telephone me-1"></i>{{ $client->tracker_contact }}</a>
                        <button type="button" class="contact-edit btn btn-sm btn-link p-0 ms-1 text-muted" data-client="{{ $client->id }}" data-value="{{ $client->tracker_contact }}" title="Change number"><i class="bi bi-pencil"></i></button>
                        @else
                        <button type="button" class="contact-add btn btn-sm btn-outline-secondary" data-client="{{ $client->id }}" data-value="" data-suggest="{{ $client->contact_no }}"><i class="bi bi-plus-lg me-1"></i>Add number</button>
                        @endif
                    </td>
                    <td>
                        @php $tf = $client->tracker_folder; $tfUrl = $tf ? (preg_match('~^https?://~i', $tf) ? $tf : 'https://' . $tf) : ''; @endphp
                        @if($tf)
                        <a href="{{ $tfUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" title="Open SharePoint folder"><i class="bi bi-folder2-open"></i></a>
                        <button type="button" class="folder-edit btn btn-sm btn-link p-0 ms-1 text-muted" data-client="{{ $client->id }}" data-link="{{ $tf }}" title="Change link"><i class="bi bi-pencil"></i></button>
                        @else
                        <button type="button" class="folder-add btn btn-sm btn-outline-secondary" data-client="{{ $client->id }}" data-link="" data-suggest="{{ $client->folder_link }}"><i class="bi bi-plus-lg me-1"></i>Add link</button>
                        @endif
                    </td>
                    <td>
                        <select class="itr-select st-{{ $client->tracker_status }}" data-client="{{ $client->id }}">
                            @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ $client->tracker_status === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select class="itr-assign form-select form-select-sm" data-client="{{ $client->id }}">
                            <option value="">— Unassigned —</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (string) $client->tracker_assigned === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" class="itr-remarks" data-client="{{ $client->id }}" value="{{ $client->tracker_remarks }}" placeholder="Add remarks…">
                            <i class="bi bi-check-circle-fill saved-tick" data-client="{{ $client->id }}"></i>
                        </div>
                    </td>
                    <td style="font-size:0.8rem; color:#6b7280;" data-updated="{{ $client->id }}">{{ $client->tracker_updated ? $client->tracker_updated->format('d M Y H:i') : '—' }}</td>
                    <td class="text-end">
                        @if($showSkipped)
                        <button type="button" class="itr-skip btn btn-sm btn-link text-muted p-0" data-client="{{ $client->id }}" data-skip="0" title="Restore to active list"><i class="bi bi-arrow-counterclockwise"></i></button>
                        @else
                        <button type="button" class="itr-skip btn btn-sm btn-link text-muted p-0" data-client="{{ $client->id }}" data-skip="1" title="Skip / hide this client"><i class="bi bi-eye-slash"></i></button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-5" style="color:#9ca3af;">
                    <i class="bi bi-inbox" style="font-size:2rem; opacity:0.3; display:block; margin-bottom:8px;"></i>
                    No clients have Income Tax Return as an active service.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const CSRF = '{{ csrf_token() }}';
    const URL_TPL = '{{ url('income-tax-returns') }}';
    const STATUS_KEYS = @json(array_keys($statuses));
    const FILTERED = {{ (request('status') || request('q') || request('mine') || request('skipped') || request('exclude')) ? 'true' : 'false' }};

    // Exclude-assignee multi-select (auto-submits the filter form on change)
    if (window.TomSelect) {
        document.querySelectorAll('.tom-exclude').forEach(function (el) {
            var ts = new TomSelect(el, { plugins: ['remove_button'], placeholder: 'Exclude assignee…' });
            ts.on('change', function () { document.getElementById('itrFilterForm').submit(); });
        });
    }

    function flashRow(clientId, ok) {
        var row = document.querySelector('tr[data-client="' + clientId + '"]');
        if (!row) return;
        row.style.transition = 'background-color .2s';
        row.style.backgroundColor = ok ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.14)';
        setTimeout(function () { row.style.backgroundColor = ''; }, ok ? 700 : 2500);
    }

    function post(clientId, payload, onOk) {
        fetch(URL_TPL + '/' + clientId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (d) {
            if (d && d.ok) {
                var cell = document.querySelector('[data-updated="' + clientId + '"]');
                if (cell && d.updated_at) cell.textContent = d.updated_at;
                flashRow(clientId, true);
                if (onOk) onOk(d);
            } else {
                flashRow(clientId, false);
            }
        }).catch(function () {
            flashRow(clientId, false);
            console.error('Save failed for client ' + clientId + '. If only "Assigned To" fails, re-run /migrate-it-returns.php (assigned_to column).');
        });
    }

    // Status change
    document.querySelectorAll('.itr-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            STATUS_KEYS.forEach(k => sel.classList.remove('st-' + k));
            sel.classList.add('st-' + sel.value);
            post(sel.dataset.client, { status: sel.value }, function () {
                if (FILTERED) { location.reload(); } else { recalcDashboard(); }
            });
        });
    });

    // Assignee change
    document.querySelectorAll('.itr-assign').forEach(function (sel) {
        sel.addEventListener('change', function () {
            post(sel.dataset.client, { assigned_to: sel.value }, function () { if (FILTERED) location.reload(); });
        });
    });

    // Contact number + Folder link: edit via prompt (no inline input fields)
    function promptSave(btn, field, label) {
        var current = btn.dataset.value || btn.dataset.link || '';
        var val = prompt(label + ':', current || btn.dataset.suggest || '');
        if (val === null) return;               // cancelled
        var payload = {}; payload[field] = val.trim();
        post(btn.dataset.client, payload, function () { location.reload(); });
    }
    document.querySelectorAll('.contact-add, .contact-edit').forEach(function (b) {
        b.addEventListener('click', function () { promptSave(b, 'contact_number', 'Contact number'); });
    });
    document.querySelectorAll('.folder-add, .folder-edit').forEach(function (b) {
        b.addEventListener('click', function () { promptSave(b, 'folder_link', 'SharePoint folder link'); });
    });

    // Skip / restore a client
    document.querySelectorAll('.itr-skip').forEach(function (b) {
        b.addEventListener('click', function () {
            post(b.dataset.client, { skipped: b.dataset.skip }, function () { location.reload(); });
        });
    });

    // Remarks save on blur (only if changed)
    document.querySelectorAll('.itr-remarks').forEach(function (inp) {
        var original = inp.value;
        inp.addEventListener('blur', function () {
            if (inp.value === original) return;
            original = inp.value;
            post(inp.dataset.client, { remarks: inp.value }, function () {
                var tick = document.querySelector('.saved-tick[data-client="' + inp.dataset.client + '"]');
                if (tick) { tick.classList.add('show'); setTimeout(() => tick.classList.remove('show'), 1500); }
            });
        });
    });

    // Recompute dashboard cards + distribution bar from current selects
    function recalcDashboard() {
        var selects = document.querySelectorAll('.itr-select');
        var total = selects.length;
        var counts = {}; STATUS_KEYS.forEach(k => counts[k] = 0);
        selects.forEach(s => { if (counts[s.value] !== undefined) counts[s.value]++; });
        STATUS_KEYS.forEach(function (k) {
            var pct = total ? Math.round(counts[k] / total * 100) : 0;
            var cEl = document.getElementById('count-' + k); if (cEl) cEl.textContent = counts[k];
            var pEl = document.getElementById('pct-' + k); if (pEl) pEl.textContent = pct + '%';
            var seg = document.getElementById('seg-' + k); if (seg) seg.style.width = pct + '%';
        });
        var filed = total ? Math.round(counts['filed'] / total * 100) : 0;
        var f = document.getElementById('filedPctText'); if (f) f.textContent = filed + '%';
    }
})();
</script>
@endsection
