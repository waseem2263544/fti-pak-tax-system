@extends('layouts.app')
@section('title', $salary->exists ? 'Edit Salary' : 'Record Salary')
@section('page-title', $salary->exists ? 'Edit Salary' : 'Record Salary')

@section('content')
@include('wht.partials.agent-switch', ['company' => $company])

@php
    $amount = $salary->exists ? $salary->input_amount : old('amount');
@endphp

<form method="POST" action="{{ $salary->exists ? route('wht.salaries.update', $salary) : route('wht.salaries.store') }}" id="salaryForm">
    @csrf
    @if($salary->exists) @method('PUT') @endif

    <div class="card mb-4">
        <div class="card-header"><strong>Salary Details</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" id="employeeId" class="form-select" required>
                        <option value="">Select employee…</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}"
                                    data-section="{{ $e->default_section }}"
                                    data-mode="{{ $e->default_calc_mode }}"
                                    data-salary="{{ $e->default_salary_amount }}"
                                    data-exempt="{{ $e->exempt_rate }}"
                                    {{ old('employee_id', $salary->employee_id) == $e->id ? 'selected' : '' }}>
                                {{ $e->name }}@if($e->cnic_ntn) ({{ $e->cnic_ntn }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Salary Month <span class="text-danger">*</span></label>
                    <input type="month" name="salary_month" id="salaryMonth" class="form-control"
                           value="{{ old('salary_month', $salary->salary_month?->format('Y-m') ?? now()->format('Y-m')) }}" required>
                    <div class="form-text">Sets the tax year.</div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control"
                           value="{{ old('payment_date', $salary->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Section</label>
                    <select name="section" class="form-select">
                        <option value="">Select section…</option>
                        @foreach($sections as $s)
                            <option value="{{ $s->section }}" {{ old('section', $salary->section) == $s->section ? 'selected' : '' }}>
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
            <strong>Calculation <span class="text-muted fw-normal" style="font-size: 0.8rem;">Tax Year <span id="taxYear">—</span></span></strong>
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" id="calcModeToggle"
                       {{ old('calc_mode', $salary->calc_mode) === 'net' ? 'checked' : '' }}>
                <label class="form-check-label" for="calcModeToggle">Work back from take-home pay</label>
            </div>
        </div>
        <div class="card-body">
            <input type="hidden" name="calc_mode" id="calcMode" value="{{ old('calc_mode', $salary->calc_mode ?? 'gross') }}">

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" id="amountLabel">Total Salary <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount" id="amount" class="form-control" value="{{ $amount }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Exempt Allowance (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="exempt_rate" id="exemptRate" class="form-control"
                           value="{{ old('exempt_rate', $salary->exists ? $salary->exempt_rate : null) }}"
                           placeholder="{{ \App\Services\Wht\WhtCalculator::defaultExemptRate() }}">
                    <div class="form-text">Blank uses the default.</div>
                </div>

                <div class="col-md-2">
                    <label class="form-label text-muted">Taxable Salary</label>
                    <input type="text" id="outTaxable" class="form-control" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted">Exempt Amount</label>
                    <input type="text" id="outExempt" class="form-control" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted">Total Salary</label>
                    <input type="text" id="outTotal" class="form-control" readonly>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-3 offset-md-6">
                    <label class="form-label">Tax Deducted</label>
                    <input type="text" id="outTax" class="form-control fw-semibold" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Final Net Payment</label>
                    <input type="text" id="outNet" class="form-control fw-semibold" readonly>
                </div>
            </div>

            <div id="slabWarning" class="alert alert-warning mt-3 mb-0 d-none" style="font-size: 0.85rem;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                No salary slabs are defined for tax year <span id="warnYear"></span>, so no tax will be computed.
                Add them under <a href="{{ route('wht.slabs.index') }}" target="_blank">Salary Slabs</a>.
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Challan</strong> <span class="text-muted" style="font-size: 0.8rem;">optional</span></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">PSID No.</label>
                    <input type="text" name="psid_no" class="form-control" value="{{ old('psid_no', $salary->psid_no) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CPR No.</label>
                    <input type="text" name="cpr_no" class="form-control" value="{{ old('cpr_no', $salary->cpr_no) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CPR Date</label>
                    <input type="date" name="cpr_date" class="form-control" value="{{ old('cpr_date', $salary->cpr_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Challan Date</label>
                    <input type="date" name="challan_date" class="form-control" value="{{ old('challan_date', $salary->challan_date?->format('Y-m-d')) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">{{ $salary->exists ? 'Update' : 'Record' }} Salary</button>
        <a href="{{ route('wht.salaries.index') }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
(function () {
    const employeeId = document.getElementById('employeeId');
    const salaryMonth = document.getElementById('salaryMonth');
    const amount = document.getElementById('amount');
    const exemptRate = document.getElementById('exemptRate');
    const calcMode = document.getElementById('calcMode');
    const calcToggle = document.getElementById('calcModeToggle');
    const amountLabel = document.getElementById('amountLabel');
    const taxYear = document.getElementById('taxYear');
    const slabWarning = document.getElementById('slabWarning');
    const warnYear = document.getElementById('warnYear');
    const out = {
        taxable: document.getElementById('outTaxable'),
        exempt: document.getElementById('outExempt'),
        total: document.getElementById('outTotal'),
        tax: document.getElementById('outTax'),
        net: document.getElementById('outNet'),
    };

    const money = n => Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    let pending = null;

    async function refresh() {
        if (!salaryMonth.value) return;

        const body = new FormData();
        body.append('_token', document.querySelector('input[name="_token"]').value);
        body.append('employee_id', employeeId.value);
        body.append('salary_month', salaryMonth.value);
        body.append('amount', amount.value || 0);
        body.append('calc_mode', calcMode.value);
        if (exemptRate.value !== '') body.append('exempt_rate', exemptRate.value);

        if (pending) pending.abort();
        pending = new AbortController();

        let data;
        try {
            const res = await fetch(@json(route('wht.salaries.preview')), {
                method: 'POST', body, signal: pending.signal,
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
            });
            if (!res.ok) return;
            data = await res.json();
        } catch (e) {
            return;
        }

        out.taxable.value = money(data.taxable_salary);
        out.exempt.value = money(data.exempt_amount);
        out.total.value = money(data.total_salary);
        out.tax.value = money(data.tax_deducted);
        out.net.value = money(data.final_net_payment);
        taxYear.textContent = data.tax_year;

        // Zero tax on a non-zero salary almost always means the slabs are missing.
        const suspicious = Number(data.taxable_salary) > 0 && Number(data.tax_deducted) === 0;
        warnYear.textContent = data.tax_year;
        slabWarning.classList.toggle('d-none', !suspicious);
    }

    function setMode(isNet) {
        calcMode.value = isNet ? 'net' : 'gross';
        amountLabel.innerHTML = (isNet ? 'Net Payment (take-home)' : 'Total Salary') + ' <span class="text-danger">*</span>';
        refresh();
    }

    employeeId.addEventListener('change', () => {
        const opt = employeeId.selectedOptions[0];
        if (opt) {
            if (opt.dataset.exempt) exemptRate.value = opt.dataset.exempt;
            if (opt.dataset.salary && !amount.value) amount.value = opt.dataset.salary;
            if (opt.dataset.mode) {
                calcToggle.checked = opt.dataset.mode === 'net';
                setMode(calcToggle.checked);
                return;
            }
        }
        refresh();
    });

    calcToggle.addEventListener('change', () => setMode(calcToggle.checked));
    [salaryMonth, amount, exemptRate].forEach(el => {
        el.addEventListener('input', refresh);
        el.addEventListener('change', refresh);
    });

    refresh();
})();
</script>
@endsection
