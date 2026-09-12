@extends('layouts.app')
@section('title', $client->name . ' — Wealth Statement')
@section('page-title', $client->name)

@section('content')
@php
    $money = fn($v) => $v === null ? '' : number_format((float) $v, 0);
    $net   = $totals['current']['net'];
    $required = ($net - (float) $recon->opening_wealth) + $recon->total_outflows;
    $difference = $recon->total_sources - $required;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="GET" action="{{ route('wealth.show', $client) }}" class="d-flex align-items-center gap-2">
        <label class="form-label mb-0" for="yearPick" style="font-size: 0.8rem;">Tax year</label>
        <select name="year" id="yearPick" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            @foreach($years as $y)
                <option value="{{ $y }}" @selected($y == $taxYear)>{{ $y }}</option>
            @endforeach
        </select>
        <span class="text-muted" style="font-size: 0.78rem;">year ended 30 June {{ $taxYear }}</span>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('wealth.comparative', $client) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-table me-1"></i> Comparative
        </a>
        <a href="{{ route('wealth.index') }}" class="btn btn-sm btn-outline-primary">Back</a>
    </div>
</div>

{{-- Where the year stands, at a glance. --}}
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card">
        <div class="stat-value num">{{ number_format($totals['current']['assets'], 0) }}</div>
        <div class="stat-label">Total assets</div>
    </div></div>
    <div class="col-md-3"><div class="stat-card">
        <div class="stat-value num">{{ number_format($totals['current']['liabilities'], 0) }}</div>
        <div class="stat-label">Liabilities</div>
    </div></div>
    <div class="col-md-3"><div class="stat-card">
        <div class="stat-value num">{{ number_format($net, 0) }}</div>
        <div class="stat-label">Net wealth, 30 June {{ $taxYear }}</div>
    </div></div>
    <div class="col-md-3"><div class="stat-card">
        <div class="stat-value num" style="color: {{ abs($difference) < 1 ? 'var(--ok-ink)' : 'var(--danger-ink)' }};">
            {{ number_format($difference, 0) }}
        </div>
        <div class="stat-label">Unreconciled {{ abs($difference) < 1 ? '— balanced' : '— must reach nil' }}</div>
    </div></div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-wealth" type="button">Assets &amp; liabilities</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-recon" type="button">Reconciliation</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-income" type="button">Income tax working</button></li>
</ul>

<div class="tab-content">

{{-- ═══ ASSETS & LIABILITIES ═══ --}}
<div class="tab-pane fade show active" id="tab-wealth">
    <form method="POST" action="{{ route('wealth.values.save', $client) }}">
        @csrf
        <input type="hidden" name="tax_year" value="{{ $taxYear }}">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Statement as at 30 June {{ $taxYear }}</span>
                <button class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i> Save figures</button>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 320px;">Description</th>
                            <th class="num" style="width: 170px;">{{ $taxYear }}</th>
                            <th class="num" style="width: 150px;">{{ $taxYear - 1 }}</th>
                            <th style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach(['asset', 'liability'] as $kind)
                        @foreach(\App\Models\WealthLine::SECTIONS[$kind] as $key => $label)
                            @php $group = $lines->where('kind', $kind)->where('section', $key); @endphp
                            @continue($group->isEmpty())
                            <tr>
                                <td colspan="4" style="background: var(--surface-sunk); font-weight: 600; font-size: 0.78rem; color: var(--text-soft);">
                                    {{ $label }}
                                    @if($kind === 'liability')<span class="badge bg-warning ms-1" style="font-size:0.6rem;">liability</span>@endif
                                </td>
                            </tr>
                            @foreach($group as $line)
                                <tr>
                                    <td style="font-size: 0.83rem;">
                                        {{ $line->description }}
                                        @if($line->notes)<div class="text-muted" style="font-size: 0.73rem;">{{ $line->notes }}</div>@endif
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm num"
                                               name="amounts[{{ $line->id }}]"
                                               value="{{ $line->amountFor($taxYear) }}"
                                               aria-label="{{ $line->description }} for {{ $taxYear }}">
                                    </td>
                                    <td class="num text-muted" style="font-size: 0.82rem;">
                                        {{ $money($line->amountFor($taxYear - 1)) ?: '—' }}
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                title="Remove this line and its figures for every year"
                                                onclick="removeLine({{ $line->id }}, @json($line->description))">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach

                    @if($lines->isEmpty())
                        <tr><td colspan="4" class="text-center py-5 text-muted">
                            Nothing on the statement yet. Add the first asset below.
                        </td></tr>
                    @endif
                    </tbody>
                    <tfoot>
                        <tr style="border-top: 2px solid var(--border-strong);">
                            <td style="font-weight: 700;">Net wealth</td>
                            <td class="num" style="font-weight: 700;">{{ number_format($net, 0) }}</td>
                            <td class="num text-muted">{{ number_format($totals['prior']['net'], 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">Add a line</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('wealth.lines.store', $client) }}" class="row g-2 align-items-end">
                        @csrf
                        <input type="hidden" name="tax_year" value="{{ $taxYear }}">
                        <div class="col-md-3">
                            <label class="form-label" for="newSection">Section</label>
                            <select name="section" id="newSection" class="form-select form-select-sm" required
                                    onchange="document.getElementById('newKind').value = this.selectedOptions[0].dataset.kind">
                                @foreach(\App\Models\WealthLine::SECTIONS as $kind => $sections)
                                    <optgroup label="{{ ucfirst($kind) }}s">
                                        @foreach($sections as $key => $label)
                                            <option value="{{ $key }}" data-kind="{{ $kind }}">{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <input type="hidden" name="kind" id="newKind" value="asset">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="newDesc">Description</label>
                            <input type="text" name="description" id="newDesc" class="form-control form-control-sm"
                                   placeholder="e.g. 1 Kanal Plot 59/E-1, Hayatabad" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="newAmount">{{ $taxYear }}</label>
                            <input type="number" step="0.01" name="amount" id="newAmount" class="form-control form-control-sm num">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-accent btn-sm w-100"><i class="bi bi-plus-lg"></i> Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Carry figures forward</div>
                <div class="card-body">
                    <p class="text-muted mb-2" style="font-size: 0.8rem;">
                        Copies every line's figure into another year. Anything already entered for the
                        target year is left alone.
                    </p>
                    <form method="POST" action="{{ route('wealth.carry-forward', $client) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-5">
                            <label class="form-label" for="cfFrom">From</label>
                            <input type="number" name="from_year" id="cfFrom" class="form-control form-control-sm" value="{{ $taxYear - 1 }}" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label" for="cfTo">To</label>
                            <input type="number" name="to_year" id="cfTo" class="form-control form-control-sm" value="{{ $taxYear }}" required>
                        </div>
                        <div class="col-2">
                            <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-arrow-right"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ RECONCILIATION ═══ --}}
<div class="tab-pane fade" id="tab-recon">
    <form method="POST" action="{{ route('wealth.reconciliation.save', $client) }}">
        @csrf
        <input type="hidden" name="tax_year" value="{{ $taxYear }}">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">What has to be explained</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="openingWealth">Net wealth as at 30 June {{ $taxYear - 1 }}</label>
                            <input type="number" step="0.01" name="opening_wealth" id="openingWealth"
                                   class="form-control num" value="{{ $recon->opening_wealth }}">
                            <div class="form-text">Taken from last year's statement; change it if the prior year was filed elsewhere.</div>
                        </div>
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>Net wealth as at 30 June {{ $taxYear }}</td>
                                <td class="num">{{ number_format($net, 0) }}</td>
                            </tr>
                            <tr>
                                <td>Increase in wealth</td>
                                <td class="num" style="font-weight: 600;">{{ number_format($net - (float) $recon->opening_wealth, 0) }}</td>
                            </tr>
                        </table>
                        <hr>
                        <p class="form-label">Add: outflows during the year</p>
                        @foreach(\App\Models\WealthReconciliation::OUTFLOWS as $key => $label)
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-7"><label class="mb-0" for="o_{{ $key }}" style="font-size: 0.83rem;">{{ $label }}</label></div>
                                <div class="col-5">
                                    <input type="number" step="0.01" name="{{ $key }}" id="o_{{ $key }}"
                                           class="form-control form-control-sm num" value="{{ $recon->{$key} ?: '' }}">
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between pt-2" style="border-top: 1px solid var(--border); font-weight: 700;">
                            <span>Total to be explained</span>
                            <span class="num">{{ number_format($required, 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Where it came from</div>
                    <div class="card-body">
                        @foreach(\App\Models\WealthReconciliation::SOURCES as $key => $label)
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-7"><label class="mb-0" for="s_{{ $key }}" style="font-size: 0.83rem;">{{ $label }}</label></div>
                                <div class="col-5">
                                    <input type="number" step="0.01" name="{{ $key }}" id="s_{{ $key }}"
                                           class="form-control form-control-sm num" value="{{ $recon->{$key} ?: '' }}">
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between pt-2" style="border-top: 1px solid var(--border); font-weight: 700;">
                            <span>Total sources</span>
                            <span class="num">{{ number_format($recon->total_sources, 0) }}</span>
                        </div>

                        <div class="alert {{ abs($difference) < 1 ? 'alert-success' : 'alert-warning' }} mt-3 mb-0">
                            @if(abs($difference) < 1)
                                Sources cover the increase in wealth exactly. This reconciles.
                            @else
                                Out by <strong class="num">{{ number_format($difference, 0) }}</strong>.
                                {{ $difference > 0 ? 'Sources exceed what needs explaining.' : 'Sources fall short of what needs explaining.' }}
                                IRIS will not accept the statement until this is nil.
                            @endif
                        </div>

                        <div class="mt-3">
                            <label class="form-label" for="reconNotes">Notes</label>
                            <textarea name="notes" id="reconNotes" class="form-control" rows="2">{{ $recon->notes }}</textarea>
                        </div>
                        <button class="btn btn-primary mt-3"><i class="bi bi-check-lg me-1"></i> Save reconciliation</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ═══ INCOME TAX WORKING ═══ --}}
<div class="tab-pane fade" id="tab-income">
    <form method="POST" action="{{ route('wealth.income.save', $client) }}">
        @csrf
        <input type="hidden" name="tax_year" value="{{ $taxYear }}">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Income declared, tax year {{ $taxYear }}</div>
                    <div class="card-body">
                        @foreach(\App\Models\IncomeWorking::HEADS as $key => $label)
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-7"><label class="mb-0" for="i_{{ $key }}" style="font-size: 0.83rem;">{{ $label }}</label></div>
                                <div class="col-5">
                                    <input type="number" step="0.01" name="{{ $key }}" id="i_{{ $key }}"
                                           class="form-control form-control-sm num" value="{{ $income->{$key} ?: '' }}">
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between pt-2 mb-3" style="border-top: 1px solid var(--border); font-weight: 700;">
                            <span>Total income</span>
                            <span class="num">{{ number_format($income->total_income, 0) }}</span>
                        </div>

                        @foreach(['exempt_income' => 'Income exempt from tax',
                                  'ftr_income' => 'Income under final tax regime',
                                  'deductible_allowances' => 'Deductible allowances'] as $key => $label)
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-7"><label class="mb-0" for="i_{{ $key }}" style="font-size: 0.83rem;">{{ $label }}</label></div>
                                <div class="col-5">
                                    <input type="number" step="0.01" name="{{ $key }}" id="i_{{ $key }}"
                                           class="form-control form-control-sm num" value="{{ $income->{$key} ?: '' }}">
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between pt-2" style="border-top: 1px solid var(--border); font-weight: 700;">
                            <span>Taxable income</span>
                            <span class="num">{{ number_format($income->taxable_income, 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Tax</div>
                    <div class="card-body">
                        <div class="alert alert-info" style="font-size: 0.8rem;">
                            Tax chargeable is entered by hand, from IRIS. Nothing on this page is
                            calculated from tax rates.
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="i_tax_chargeable">Tax chargeable</label>
                            <input type="number" step="0.01" name="tax_chargeable" id="i_tax_chargeable"
                                   class="form-control num" style="font-weight: 700;" value="{{ $income->tax_chargeable ?: '' }}">
                        </div>

                        @foreach(['tax_reductions_credits' => 'Tax reductions and credits',
                                  'tax_deducted' => 'Tax deducted at source',
                                  'tax_paid' => 'Tax paid with return / advance'] as $key => $label)
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-7"><label class="mb-0" for="i_{{ $key }}" style="font-size: 0.83rem;">{{ $label }}</label></div>
                                <div class="col-5">
                                    <input type="number" step="0.01" name="{{ $key }}" id="i_{{ $key }}"
                                           class="form-control form-control-sm num" value="{{ $income->{$key} ?: '' }}">
                                </div>
                            </div>
                        @endforeach

                        <div class="d-flex justify-content-between pt-2" style="border-top: 1px solid var(--border); font-weight: 700;">
                            <span>{{ $income->tax_payable >= 0 ? 'Tax payable' : 'Refundable' }}</span>
                            <span class="num">{{ number_format(abs($income->tax_payable), 0) }}</span>
                        </div>

                        <div class="mt-3">
                            <label class="form-label" for="incomeNotes">Notes</label>
                            <textarea name="notes" id="incomeNotes" class="form-control" rows="2">{{ $income->notes }}</textarea>
                        </div>
                        <button class="btn btn-primary mt-3"><i class="bi bi-check-lg me-1"></i> Save income working</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

</div>

<form method="POST" id="removeLineForm" class="d-none">@csrf @method('DELETE')</form>
@endsection

@section('scripts')
<script>
function removeLine(id, description) {
    if (!confirm('Remove “' + description + '” from the wealth statement?\n\nIts figures for every year are removed too.')) return;
    var f = document.getElementById('removeLineForm');
    f.action = '{{ url('wealth/' . $client->id . '/lines') }}/' + id;
    f.submit();
}
</script>
@endsection
