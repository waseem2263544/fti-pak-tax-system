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
        <a href="{{ route('income-tax-returns.index', array_filter(['mine' => request()->boolean('mine') ? 1 : null])) }}" class="card stat-card text-decoration-none {{ !request('status') ? 'border-2' : '' }}" style="{{ !request('status') ? 'border:2px solid var(--accent);' : '' }}">
            <div class="stat-mini">
                <div class="num" id="count-total">{{ $total }}</div>
                <div class="lbl">Total Clients</div>
            </div>
        </a>
    </div>
    @foreach($statuses as $key => $label)
    <div class="col">
        <a href="{{ route('income-tax-returns.index', array_filter(['status' => $key, 'mine' => request()->boolean('mine') ? 1 : null])) }}" class="card stat-card text-decoration-none" style="{{ request('status') === $key ? 'border:2px solid '.$colors[$key][2].';' : '' }}">
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
@php $mineOn = request()->boolean('mine'); @endphp
<div class="card mb-3">
    <div class="card-body d-flex gap-2 align-items-center flex-wrap" style="padding:14px 18px;">
        <a href="{{ route('income-tax-returns.index', array_filter(['status' => request('status'), 'q' => request('q'), 'mine' => $mineOn ? null : 1])) }}" class="btn btn-sm {{ $mineOn ? 'btn-primary' : 'btn-outline-primary' }}">
            <i class="bi bi-person-check me-1"></i>Assigned to me @if($mineCount)<span class="badge bg-light text-dark ms-1">{{ $mineCount }}</span>@endif
        </a>
        <form method="GET" class="d-flex gap-2 align-items-center">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            @if($mineOn)<input type="hidden" name="mine" value="1">@endif
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="max-width:280px;" placeholder="Search client…">
            <button class="btn btn-sm btn-accent"><i class="bi bi-search"></i></button>
        </form>
        @if(request('q') || request('status') || $mineOn)<a href="{{ route('income-tax-returns.index') }}" class="btn btn-sm btn-outline-primary">Clear</a>@endif
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Client</th>
                    <th style="width:160px;">Contact</th>
                    <th style="width:190px;">Status</th>
                    <th style="width:170px;">Assigned To</th>
                    <th>Remarks</th>
                    <th style="width:150px;">Last Updated</th>
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
                        <input type="text" class="itr-contact form-control form-control-sm" data-client="{{ $client->id }}" value="{{ $client->tracker_contact }}" placeholder="Number…" style="max-width:150px;">
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
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-5" style="color:#9ca3af;">
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
    const FILTERED = {{ (request('status') || request('q') || request('mine')) ? 'true' : 'false' }};

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

    // Contact number save on blur (only if changed)
    document.querySelectorAll('.itr-contact').forEach(function (inp) {
        var original = inp.value;
        inp.addEventListener('blur', function () {
            if (inp.value === original) return;
            original = inp.value;
            post(inp.dataset.client, { contact_number: inp.value });
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
