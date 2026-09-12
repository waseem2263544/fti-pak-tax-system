@extends('layouts.app')
@section('title', $client->name . ' — Salary working')
@section('page-title', $client->name)

@section('styles')
<style>
    .sw-sheet { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); margin-bottom: 18px; }
    .sw-head { padding: 12px 18px; border-bottom: 1px solid var(--border); display: flex;
               justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
    .sw-head h2 { font-size: 0.94rem; font-weight: 700; margin: 0; }
    .sw-band { padding: 7px 18px; background: var(--surface-sunk); border-bottom: 1px solid var(--border);
               font-size: 0.78rem; font-weight: 600; color: var(--text-soft); }
    .sw-sub { font-size: 0.73rem; color: var(--text-muted); }
    .sw-money { text-align: right; font-variant-numeric: tabular-nums; }
    .sw-table { width: 100%; border-collapse: collapse; }
    .sw-table th { font-size: 0.7rem; font-weight: 600; color: var(--text-muted); padding: 7px 8px;
                   text-align: right; white-space: nowrap; }
    .sw-table th:first-child, .sw-table td:first-child { text-align: left; padding-left: 18px; min-width: 210px; }
    .sw-table td { padding: 4px 8px; border-bottom: 1px solid var(--n-100); font-size: 0.82rem; }
    .sw-table input[type=number] { text-align: right; }
    .sw-table .tot td { border-top: 2px solid var(--border-strong); border-bottom: none; font-weight: 700; padding-top: 9px; }
    .sw-mini { width: 86px; }
    details.sw-open > summary { list-style: none; padding: 14px 18px; cursor: pointer; }
    details.sw-open > summary::-webkit-details-marker { display: none; }
    details.sw-open > summary .btn { pointer-events: none; }
    details.sw-open > div { padding: 0 18px 16px; }
</style>
@endsection

@section('content')
@php $n = fn($v) => number_format((float) $v, 0); @endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="GET" class="d-flex align-items-center gap-2">
        <label class="form-label mb-0" for="yr" style="font-size: 0.8rem;">Tax year</label>
        <select name="year" id="yr" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            @for($y = \App\Http\Controllers\WealthStatementController::currentTaxYear() + 1; $y >= 2015; $y--)
                <option value="{{ $y }}" @selected($y == $taxYear)>{{ $y }}</option>
            @endfor
        </select>
        <span class="sw-sub">year ended 30 June {{ $taxYear }}</span>
    </form>
    <a href="{{ route('wealth.show', ['client' => $client, 'year' => $taxYear]) }}" class="btn btn-sm btn-outline-primary">
        Back to the working paper
    </a>
</div>

@foreach($workings as $w)
    @php
        $inc = $w->income(); $ded = $w->deductions();
        $months = \App\Models\SalaryWorking::MONTHS;
    @endphp
    <form method="POST" action="{{ route('wealth.salary.save', [$client, $w]) }}">
        @csrf
        <div class="sw-sheet">
            <div class="sw-head">
                <div>
                    <h2>{{ $w->employer }}</h2>
                    <div class="sw-sub">Gross {{ $n($w->grossIncome()) }} · deductions {{ $n($w->totalDeductions()) }} · net {{ $n($w->netPay()) }}</div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Basis">
                        <input type="radio" class="btn-check" name="basis" value="annual" id="b_a_{{ $w->id }}"
                               @checked($w->basis === 'annual') onchange="this.form.submit()">
                        <label class="btn btn-outline-primary" for="b_a_{{ $w->id }}">Annual</label>
                        <input type="radio" class="btn-check" name="basis" value="monthly" id="b_m_{{ $w->id }}"
                               @checked($w->basis === 'monthly') onchange="this.form.submit()">
                        <label class="btn btn-outline-primary" for="b_m_{{ $w->id }}">Monthly</label>
                    </div>
                    <button class="btn btn-sm btn-primary">Save</button>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="sw-table">
                    <thead>
                        <tr>
                            <th>Head</th>
                            @if($w->basis === 'monthly')
                                @foreach($months as $m => $label)<th>{{ $label }}</th>@endforeach
                            @endif
                            <th style="width: 130px;">{{ $w->basis === 'monthly' ? 'Year' : 'Amount' }}</th>
                            <th style="width: 120px;">Treatment</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>

                    {{-- ── INCOME ── --}}
                    <tbody>
                        <tr><td colspan="{{ $w->basis === 'monthly' ? 16 : 4 }}" class="sw-band">Income</td></tr>
                        @foreach($inc as $c)
                            <tr>
                                <td>
                                    @if($c->locked)
                                        <strong>{{ $c->label }}</strong>
                                        <div class="sw-sub">always present</div>
                                    @else
                                        <input type="text" name="labels[{{ $c->id }}]" value="{{ $c->label }}"
                                               class="form-control form-control-sm" aria-label="Head name">
                                    @endif
                                </td>
                                @if($w->basis === 'monthly')
                                    @foreach($months as $m => $label)
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sw-mini"
                                                   name="months[{{ $c->id }}][{{ $m }}]" value="{{ $c->monthAmount($m) }}"
                                                   aria-label="{{ $c->label }} {{ $label }}"></td>
                                    @endforeach
                                    <td class="sw-money">{{ $n($c->amount()) }}</td>
                                @else
                                    <td><input type="number" step="0.01" class="form-control form-control-sm"
                                               name="annual[{{ $c->id }}]" value="{{ $c->annual_amount ?: '' }}"
                                               aria-label="{{ $c->label }}"></td>
                                @endif
                                <td>
                                    <select name="treatments[{{ $c->id }}]" class="form-select form-select-sm" aria-label="Treatment">
                                        <option value="taxable" @selected($c->treatment === 'taxable')>Taxable</option>
                                        <option value="exempt"  @selected($c->treatment === 'exempt')>Exempt</option>
                                    </select>
                                </td>
                                <td class="text-end">
                                    @unless($c->locked)
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="dropComponent({{ $w->id }}, {{ $c->id }})"><i class="bi bi-trash"></i></button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                        <tr class="tot">
                            <td>Gross salary</td>
                            @if($w->basis === 'monthly')<td colspan="12"></td>@endif
                            <td class="sw-money">{{ $n($w->grossIncome()) }}</td>
                            <td class="sw-sub">
                                taxable {{ $n($w->incomeBy('taxable')) }}<br>exempt {{ $n($w->incomeBy('exempt')) }}
                            </td>
                            <td></td>
                        </tr>
                    </tbody>

                    {{-- ── DEDUCTIONS ── --}}
                    <tbody>
                        <tr><td colspan="{{ $w->basis === 'monthly' ? 16 : 4 }}" class="sw-band">Deductions</td></tr>
                        @foreach($ded as $c)
                            <tr>
                                <td>
                                    @if($c->locked)
                                        <strong>{{ $c->label }}</strong>
                                        <div class="sw-sub">always present · also shown in personal expenses</div>
                                    @else
                                        <input type="text" name="labels[{{ $c->id }}]" value="{{ $c->label }}"
                                               class="form-control form-control-sm" aria-label="Head name">
                                    @endif
                                </td>
                                @if($w->basis === 'monthly')
                                    @foreach($months as $m => $label)
                                        <td><input type="number" step="0.01" class="form-control form-control-sm sw-mini"
                                                   name="months[{{ $c->id }}][{{ $m }}]" value="{{ $c->monthAmount($m) }}"
                                                   aria-label="{{ $c->label }} {{ $label }}"></td>
                                    @endforeach
                                    <td class="sw-money">{{ $n($c->amount()) }}</td>
                                @else
                                    <td><input type="number" step="0.01" class="form-control form-control-sm"
                                               name="annual[{{ $c->id }}]" value="{{ $c->annual_amount ?: '' }}"
                                               aria-label="{{ $c->label }}"></td>
                                @endif
                                <td class="sw-sub">{{ $c->is_tax ? 'tax withheld' : '—' }}</td>
                                <td class="text-end">
                                    @unless($c->locked)
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="dropComponent({{ $w->id }}, {{ $c->id }})"><i class="bi bi-trash"></i></button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                        <tr class="tot">
                            <td>Total deductions</td>
                            @if($w->basis === 'monthly')<td colspan="12"></td>@endif
                            <td class="sw-money">{{ $n($w->totalDeductions()) }}</td>
                            <td class="sw-sub">of which tax {{ $n($w->taxDeducted()) }}</td>
                            <td></td>
                        </tr>
                        <tr class="tot" style="border-top: none;">
                            <td>Net pay</td>
                            @if($w->basis === 'monthly')<td colspan="12"></td>@endif
                            <td class="sw-money">{{ $n($w->netPay()) }}</td>
                            <td></td><td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="padding: 12px 18px; border-top: 1px solid var(--border);" class="d-flex flex-wrap gap-3 align-items-end">
                <div>
                    <label class="form-label" for="ns_{{ $w->id }}">Add a head</label>
                    <div class="d-flex gap-2">
                        <select form="addc{{ $w->id }}" name="side" id="ns_{{ $w->id }}" class="form-select form-select-sm" style="width: auto;">
                            <option value="income">Income</option>
                            <option value="deduction">Deduction</option>
                        </select>
                        <input form="addc{{ $w->id }}" type="text" name="label" class="form-control form-control-sm"
                               placeholder="e.g. House rent allowance, EOBI, provident fund" required style="min-width: 280px;"
                               aria-label="New head name">
                        <select form="addc{{ $w->id }}" name="treatment" class="form-select form-select-sm" style="width: auto;"
                                aria-label="Treatment">
                            <option value="taxable">Taxable</option>
                            <option value="exempt">Exempt</option>
                        </select>
                        <button form="addc{{ $w->id }}" class="btn btn-accent btn-sm">Add</button>
                    </div>
                    <div class="sw-sub mt-1">Treatment applies to income heads only.</div>
                </div>
                <div class="ms-auto">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="dropWorking({{ $w->id }}, @json($w->employer))">
                        <i class="bi bi-trash me-1"></i> Remove employer
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Kept outside the save form: nested forms are not allowed. --}}
    <form method="POST" id="addc{{ $w->id }}" action="{{ route('wealth.salary.components.store', [$client, $w]) }}" class="d-none">@csrf</form>
@endforeach

{{-- Open by default only when there is nothing yet, so a new year starts ready
     to type into rather than behind a click. --}}
<details class="sw-sheet sw-open" @if($workings->isEmpty()) open @endif>
    <summary>
        <span class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1"></i> Add an employer</span>
    </summary>
    <div>
        <form method="POST" action="{{ route('wealth.salary.store', $client) }}" class="row g-2 align-items-end">
            @csrf
            <input type="hidden" name="tax_year" value="{{ $taxYear }}">
            <div class="col-md-6">
                <label class="form-label" for="emp">Employer</label>
                <input type="text" name="employer" id="emp" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="bs">Worked</label>
                <select name="basis" id="bs" class="form-select form-select-sm">
                    <option value="annual">Annually — one figure for the year</option>
                    <option value="monthly">Monthly — a figure per month</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-accent btn-sm w-100">Add employer</button>
            </div>
        </form>
        <p class="sw-sub mt-2 mb-0">
            Every salary working starts with two heads that cannot be removed: <strong>Basic salary</strong> on the
            income side and <strong>Income tax deducted</strong> on the deduction side. Add allowances, perquisites,
            provident fund and the rest as they apply. You can switch between annual and monthly at any time &mdash;
            figures entered on one basis are kept.
        </p>
    </div>
</details>

<form method="POST" id="dropForm" class="d-none">@csrf @method('DELETE')</form>
@endsection

@section('scripts')
<script>
function dropComponent(workingId, componentId) {
    if (!confirm('Remove this head and its figures?')) return;
    var f = document.getElementById('dropForm');
    f.action = '{{ url('wealth/' . $client->id . '/salary') }}/' + workingId + '/components/' + componentId;
    f.submit();
}
function dropWorking(workingId, employer) {
    if (!confirm('Remove the salary working for ' + employer + '?\n\nEvery head and figure under it goes too.')) return;
    var f = document.getElementById('dropForm');
    f.action = '{{ url('wealth/' . $client->id . '/salary') }}/' + workingId;
    f.submit();
}
</script>
@endsection
