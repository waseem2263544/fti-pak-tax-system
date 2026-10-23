@php
    $partners = old('partners', $firm->partners ?: [[], []]);
    $witnesses = old('witnesses', $firm->witnesses ?: [[], []]);
    $mp = old('managing_partner', $firm->managing_partner);
    $relations = \App\Models\FirmRegistration::RELATIONS;
    $v = fn ($arr, $k) => $arr[$k] ?? '';
@endphp

@if($errors->any())
<div class="alert alert-danger">
    <strong>Please fix the following:</strong>
    <ul class="mb-0 mt-1">@foreach(collect($errors->all())->unique() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- Firm --}}
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-building me-1"></i> <strong>Firm</strong></div>
    <div class="card-body" style="padding: 24px;">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Firm name <span class="text-danger">*</span></label>
                <input type="text" name="firm_name" class="form-control" value="{{ old('firm_name', $firm->firm_name) }}" required placeholder="e.g. Unique Minerals">
            </div>
            <div class="col-md-6">
                <label class="form-label">Client (optional)</label>
                <select name="client_id" class="form-select">
                    <option value="">—</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" @selected(old('client_id', $firm->client_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Principal place of business / head office <span class="text-danger">*</span></label>
                <input type="text" name="principal_place" class="form-control" value="{{ old('principal_place', $firm->principal_place) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Other places of business</label>
                <input type="text" name="other_places" class="form-control" value="{{ old('other_places', $firm->other_places) }}" placeholder="NIL">
            </div>
            <div class="col-12">
                <label class="form-label">Nature of business <span class="text-danger">*</span></label>
                <textarea name="business_description" rows="3" class="form-control" required placeholder="Trading, import, export and supply of ...">{{ old('business_description', $firm->business_description) }}</textarea>
                <div class="form-text">Completes the deed's sentence "That, the parties shall carry on the business of …".</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Deed date <span class="text-danger">*</span></label>
                <input type="date" name="deed_date" class="form-control" value="{{ old('deed_date', $firm->deed_date?->format('Y-m-d')) }}" required>
                <div class="form-text">Also used as the date each partner joined.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Business commenced <span class="text-danger">*</span></label>
                <input type="date" name="commencement_date" class="form-control" value="{{ old('commencement_date', $firm->commencement_date?->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Duration <span class="text-danger">*</span></label>
                <input type="text" name="duration" class="form-control" value="{{ old('duration', $firm->duration) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stamp-paper top gap (cm)</label>
                <input type="number" step="0.05" name="stamp_top_cm" class="form-control" value="{{ old('stamp_top_cm', $firm->stamp_top_cm) }}" required>
                <div class="form-text">Blank space for the e-stamp header. Standard 11.25.</div>
            </div>
        </div>
    </div>
</div>

{{-- Partners --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-1"></i> <strong>Partners</strong></span>
        <span id="share-totals" style="font-size: 0.82rem;"></span>
    </div>
    <div class="card-body" style="padding: 24px;">
        <div id="partners">
            @foreach($partners as $i => $p)
                @include('firm-registrations._person', ['kind' => 'partners', 'i' => $i, 'p' => $p, 'removable' => true])
            @endforeach
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addPartner()"><i class="bi bi-plus-lg me-1"></i> Add partner</button>
    </div>
</div>

{{-- Managing partner --}}
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-person-gear me-1"></i> <strong>Managing partner</strong></div>
    <div class="card-body" style="padding: 24px;">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Managing partner</label>
                <select name="managing_partner" id="managing_partner" class="form-select" data-selected="{{ $mp }}">
                    <option value="">None — no managing partner clause</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Powers of the managing partner (one per line)</label>
                <textarea name="managing_powers" rows="5" class="form-control">{{ old('managing_powers', $firm->managing_powers ?: \App\Models\FirmRegistration::DEFAULT_MANAGING_POWERS) }}</textarea>
                <div class="form-text">The power to admit new partners and the silent-partner line are added automatically.</div>
            </div>
        </div>
    </div>
</div>

{{-- Witnesses --}}
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-person-check me-1"></i> <strong>Witnesses</strong></div>
    <div class="card-body" style="padding: 24px;">
        @foreach([0, 1] as $i)
            @include('firm-registrations._person', ['kind' => 'witnesses', 'i' => $i, 'p' => $witnesses[$i] ?? [], 'removable' => false])
        @endforeach
    </div>
</div>

<div class="card mb-4">
    <div class="card-body" style="padding: 24px;">
        <label class="form-label">Internal notes</label>
        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $firm->notes) }}</textarea>
    </div>
</div>

<template id="partner-template">
    @include('firm-registrations._person', ['kind' => 'partners', 'i' => '__I__', 'p' => [], 'removable' => true])
</template>

@section('scripts')
<script>
(function () {
    const box = document.getElementById('partners');
    let next = {{ count($partners) }};

    window.addPartner = function () {
        const html = document.getElementById('partner-template').innerHTML.replaceAll('__I__', next++);
        box.insertAdjacentHTML('beforeend', html);
        refresh();
    };
    window.removePartner = function (btn) {
        if (box.querySelectorAll('.person-row').length <= 2) { alert('A partnership needs at least two partners.'); return; }
        btn.closest('.person-row').remove();
        refresh();
    };

    function refresh() {
        // number the partner cards and rebuild the managing-partner list in the same order
        const rows = [...box.querySelectorAll('.person-row')];
        const sel = document.getElementById('managing_partner');
        const keep = sel.value !== '' ? sel.value : sel.dataset.selected;
        sel.querySelectorAll('option:not(:first-child)').forEach(o => o.remove());
        rows.forEach((row, n) => {
            row.querySelector('.person-no').textContent = 'Partner No. ' + (n + 1);
            const name = row.querySelector('[name$="[name]"]').value || '(unnamed)';
            sel.add(new Option('Partner No. ' + (n + 1) + ' — ' + name, n));
        });
        if (keep !== undefined && keep !== '' && Number(keep) < rows.length) sel.value = keep;
        delete sel.dataset.selected;

        // share totals must reach 100
        const sum = k => rows.reduce((t, r) => t + (parseFloat(r.querySelector('[name$="[' + k + ']"]').value) || 0), 0);
        const c = sum('capital_share'), p = sum('profit_share');
        const tag = (l, v) => `<span style="color:${Math.abs(v - 100) < 0.01 ? 'var(--ok-ink, #047857)' : '#b91c1c'}; font-weight:600;">${l}: ${v}%</span>`;
        document.getElementById('share-totals').innerHTML = tag('Capital', c) + ' &nbsp; ' + tag('Profit & loss', p);
    }

    // Form posts partners in DOM order, so re-index names before submit
    box.closest('form').addEventListener('submit', () => {
        [...box.querySelectorAll('.person-row')].forEach((row, n) => {
            row.querySelectorAll('[name^="partners["]').forEach(el => {
                el.name = el.name.replace(/^partners\[[^\]]*\]/, 'partners[' + n + ']');
            });
        });
    });
    box.addEventListener('input', refresh);
    refresh();
})();
</script>
@endsection
