@extends('layouts.app')
@section('title', $purchase->exists ? 'Edit Payment' : 'Record Payment')
@section('page-title', $purchase->exists ? 'Edit Payment' : 'Record Payment')

@section('content')
@include('wht.partials.agent-bar')

@php
    $amount = $purchase->exists
        ? ($purchase->calc_mode === 'net' ? $purchase->net_payment : $purchase->gross_amount)
        : old('amount');
@endphp

<form method="POST" action="{{ $purchase->exists ? route('wht.purchases.update', $purchase) : route('wht.purchases.store') }}" id="purchaseForm">
    @csrf
    @if($purchase->exists) @method('PUT') @endif

    <div class="card mb-4">
        <div class="card-header"><strong>Payment Details</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Vendor <span class="text-danger">*</span></label>
                    <select name="party_id" id="partyId" class="form-select" required>
                        <option value="">Select vendor…</option>
                        @foreach($parties as $p)
                            <option value="{{ $p->id }}"
                                    data-section="{{ $p->default_section }}"
                                    data-goods="{{ $p->default_goods_type }}"
                                    data-mode="{{ $p->default_calc_mode }}"
                                    {{ old('party_id', $purchase->party_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}@if($p->cnic_ntn) ({{ $p->cnic_ntn }})@endif
                                — {{ ucfirst($p->category) }}, {{ $p->atl_status }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Both filers and non-filers are listed; the rate follows the vendor's ATL status.</div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tax Period <span class="text-danger">*</span></label>
                    <input type="month" name="period_month" id="periodMonth" class="form-control"
                           value="{{ old('period_month', $purchase->period_month?->format('Y-m') ?? now()->format('Y-m')) }}" required>
                    <div class="form-text">Drives which rate applies.</div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control"
                           value="{{ old('payment_date', $purchase->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Section</label>
                    <select name="section" id="section" class="form-select">
                        <option value="">Select section…</option>
                        @foreach($sections as $s)
                            <option value="{{ $s->section }}" {{ old('section', $purchase->section) == $s->section ? 'selected' : '' }}>
                                {{ $s->label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Calculation</strong>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="calcModeToggle" name="calc_mode_toggle"
                       {{ old('calc_mode', $purchase->calc_mode) === 'net' ? 'checked' : '' }}>
                <label class="form-check-label" for="calcModeToggle">Enter net payment instead of gross</label>
            </div>
        </div>
        <div class="card-body">
            <input type="hidden" name="calc_mode" id="calcMode" value="{{ old('calc_mode', $purchase->calc_mode ?? 'gross') }}">

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" id="amountLabel">Gross Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control"
                           value="{{ $amount }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Tax Rate (%)</span>
                        <span id="rateBadge" class="badge bg-secondary" style="font-size: 0.65rem;">—</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="0.001" min="0" max="100" name="manual_rate" id="manualRate"
                               class="form-control" value="{{ old('manual_rate', $purchase->tax_rate) }}" readonly>
                        <button class="btn btn-outline-primary" type="button" id="overrideBtn" title="Override the matrix rate">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                    <input type="hidden" name="override" id="override" value="{{ old('override', $purchase->rate_source === 'manual' ? 1 : 0) }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label text-muted">Gross</label>
                    <input type="text" id="outGross" class="form-control" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Tax Withheld</label>
                    <input type="text" id="outTax" class="form-control fw-semibold" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Net Payment</label>
                    <input type="text" id="outNet" class="form-control" readonly>
                </div>
            </div>

            <div id="rateWarning" class="alert alert-warning mt-3 mb-0 d-none" style="font-size: 0.85rem;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                No rate is defined for this section, category and ATL status in the selected tax period.
                Enter one manually, or add it to the <a href="{{ route('wht.rates.index') }}" target="_blank">rate matrix</a>.
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Challan &amp; Notes</strong> <span class="text-muted" style="font-size: 0.8rem;">optional</span></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">PSID No.</label>
                    <input type="text" name="psid_no" class="form-control" value="{{ old('psid_no', $purchase->psid_no) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CPR No.</label>
                    <input type="text" name="cpr_no" class="form-control" value="{{ old('cpr_no', $purchase->cpr_no) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">CPR Date</label>
                    <input type="date" name="cpr_date" class="form-control" value="{{ old('cpr_date', $purchase->cpr_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" value="{{ old('remarks', $purchase->remarks) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">{{ $purchase->exists ? 'Update' : 'Record' }} Payment</button>
        <a href="{{ route('wht.purchases.index') }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
(function () {
    const form = document.getElementById('purchaseForm');
    const partyId = document.getElementById('partyId');
    const periodMonth = document.getElementById('periodMonth');
    const section = document.getElementById('section');
    const amount = document.getElementById('amount');
    const manualRate = document.getElementById('manualRate');
    const override = document.getElementById('override');
    const overrideBtn = document.getElementById('overrideBtn');
    const calcMode = document.getElementById('calcMode');
    const calcToggle = document.getElementById('calcModeToggle');
    const amountLabel = document.getElementById('amountLabel');
    const rateBadge = document.getElementById('rateBadge');
    const rateWarning = document.getElementById('rateWarning');
    const outGross = document.getElementById('outGross');
    const outTax = document.getElementById('outTax');
    const outNet = document.getElementById('outNet');

    const money = n => Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    let pending = null;

    // Every figure comes back from the server, so the preview and the saved
    // record can never disagree.
    async function refresh() {
        if (!periodMonth.value) return;

        const body = new FormData();
        body.append('_token', document.querySelector('input[name="_token"]').value);
        body.append('party_id', partyId.value);
        body.append('section', section.value);
        body.append('period_month', periodMonth.value);
        body.append('amount', amount.value || 0);
        body.append('calc_mode', calcMode.value);
        if (override.value === '1' && manualRate.value !== '') {
            body.append('manual_rate', manualRate.value);
        }

        if (pending) pending.abort();
        pending = new AbortController();

        let data;
        try {
            const res = await fetch(@json(route('wht.purchases.preview')), {
                method: 'POST', body, signal: pending.signal,
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
            });
            if (!res.ok) return;
            data = await res.json();
        } catch (e) {
            return; // aborted or offline — leave the last good figures on screen
        }

        outGross.value = money(data.gross_amount);
        outTax.value = money(data.tax_withheld);
        outNet.value = money(data.net_payment);

        if (override.value !== '1') {
            manualRate.value = data.tax_rate;
        }

        const matched = data.rate_source === 'matrix';
        rateBadge.textContent = override.value === '1' ? 'Manual' : (matched ? 'From matrix' : 'No rule');
        rateBadge.className = 'badge ' + (override.value === '1' ? 'bg-secondary' : (matched ? 'bg-success' : 'bg-warning text-dark'));

        // Only nag when the matrix genuinely has nothing and nothing was typed.
        rateWarning.classList.toggle('d-none', matched || override.value === '1' || !section.value || !partyId.value);
    }

    function setMode(isNet) {
        calcMode.value = isNet ? 'net' : 'gross';
        amountLabel.innerHTML = (isNet ? 'Net Payment' : 'Gross Amount') + ' <span class="text-danger">*</span>';
        refresh();
    }

    overrideBtn.addEventListener('click', () => {
        const on = override.value !== '1';
        override.value = on ? '1' : '0';
        manualRate.readOnly = !on;
        overrideBtn.classList.toggle('btn-primary', on);
        overrideBtn.classList.toggle('btn-outline-primary', !on);
        if (on) manualRate.focus();
        refresh();
    });

    // Pull the vendor's defaults through on selection.
    partyId.addEventListener('change', () => {
        const opt = partyId.selectedOptions[0];
        if (opt) {
            if (opt.dataset.section && !section.value) section.value = opt.dataset.section;
            if (opt.dataset.mode) {
                calcToggle.checked = opt.dataset.mode === 'net';
                setMode(calcToggle.checked);
                return;
            }
        }
        refresh();
    });

    calcToggle.addEventListener('change', () => setMode(calcToggle.checked));
    [periodMonth, section, amount, manualRate].forEach(el => {
        el.addEventListener('input', refresh);
        el.addEventListener('change', refresh);
    });

    // Restore override state on an edit / validation bounce.
    if (override.value === '1') {
        manualRate.readOnly = false;
        overrideBtn.classList.add('btn-primary');
        overrideBtn.classList.remove('btn-outline-primary');
    }

    refresh();
})();
</script>
@endsection
