@extends('layouts.app')
@section('title', $client->name . ' — Wealth Statement')
@section('page-title', $client->name)

@section('styles')
<style>
    /* A working paper, not a dashboard: ruled rows, figures to the right,
       totals underlined the way the form itself does it. */
    .ws-sheet { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); }
    .ws-sheet + .ws-sheet { margin-top: 18px; }
    .ws-head { padding: 12px 18px; border-bottom: 1px solid var(--border); display: flex;
               justify-content: space-between; align-items: center; gap: 12px; }
    .ws-head h2 { font-size: 0.92rem; font-weight: 700; margin: 0; }
    .ws-code { font-family: ui-monospace, Menlo, monospace; font-size: 0.7rem; color: var(--text-faint);
               border: 1px solid var(--border); border-radius: 4px; padding: 1px 5px; }
    .ws-row { display: grid; grid-template-columns: 1fr 150px 130px 78px; gap: 10px; align-items: center;
              padding: 7px 18px; border-bottom: 1px solid var(--n-100); font-size: 0.83rem; }
    .ws-row:last-child { border-bottom: none; }
    .ws-group { padding: 7px 18px; background: var(--surface-sunk); border-bottom: 1px solid var(--border);
                font-size: 0.78rem; font-weight: 600; color: var(--text-soft);
                display: flex; justify-content: space-between; gap: 10px; }
    .ws-total { display: grid; grid-template-columns: 1fr 150px 130px 78px; gap: 10px;
                padding: 10px 18px; border-top: 2px solid var(--border-strong); font-weight: 700; }
    .ws-sub { font-size: 0.73rem; color: var(--text-muted); }
    .ws-money { text-align: right; font-variant-numeric: tabular-nums; }
    details.ws-add > summary { cursor: pointer; padding: 10px 18px; font-size: 0.82rem;
                               font-weight: 600; color: var(--accent-dark); list-style: none; }
    details.ws-add > summary::-webkit-details-marker { display: none; }
    details.ws-add > summary::before { content: '+ '; }
    details.ws-add[open] > summary::before { content: '− '; }
    details.ws-add > div { padding: 0 18px 16px; border-top: 1px solid var(--n-100); }
</style>
@endsection

@section('content')
@php
    use App\Support\FbrSchema;
    $assetHeads = FbrSchema::assetHeads();
    $liabHeads  = FbrSchema::liabilityHeads();
    $net        = $totals['current']['net'];
    $opening    = (float) $recon->opening_wealth;
    $increase   = $net - $opening;
    $inflows    = (float) $declared['taxable'] + (float) $declared['exempt'] + (float) $declared['final']
                + (float) $recon->adjustments + (float) $recon->foreign_remittance + (float) $recon->inheritance
                + (float) $recon->gift_received + (float) $recon->gain_disposal + (float) $recon->other_sources;
    $outflows   = (float) $recon->gift_given + (float) $recon->loss_disposal + (float) $recon->other_outflows;
    $unreconciled = $inflows - $expenseTotal - $outflows - $increase;
    $n = fn($v) => number_format((float) $v, 0);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="GET" action="{{ route('wealth.show', $client) }}" class="d-flex align-items-center gap-2">
        <label class="form-label mb-0" for="yearPick" style="font-size: 0.8rem;">Tax year</label>
        <select name="year" id="yearPick" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            @foreach($years as $y)<option value="{{ $y }}" @selected($y == $taxYear)>{{ $y }}</option>@endforeach
        </select>
        <span class="text-muted" style="font-size: 0.78rem;">as at 30 June {{ $taxYear }}</span>
    </form>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge {{ abs($unreconciled) < 1 ? 'bg-success' : 'bg-danger' }}">
            Unreconciled (703000): {{ $n($unreconciled) }}
        </span>
        <a href="{{ route('wealth.comparative', $client) }}" class="btn btn-sm btn-outline-primary">Comparative</a>
        <a href="{{ route('wealth.index') }}" class="btn btn-sm btn-outline-primary">Back</a>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#t-income" type="button">Income working</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-assets" type="button">Assets &amp; liabilities</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-expenses" type="button">Annex-F expenses</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-recon" type="button">Reconciliation</button></li>
</ul>

<div class="tab-content">

{{-- ════════ INCOME WORKING ════════ --}}
<div class="tab-pane fade show active" id="t-income">
    {{-- Salary has an income side, a deduction side and its own basis, so it
         lives on its own page. What shows here is the result. --}}
    <div class="ws-sheet">
        <div class="ws-head">
            <div>
                <h2>Salary</h2>
                <div class="ws-sub">
                    @if($salary->isEmpty())
                        No employer added for {{ $taxYear }}.
                    @else
                        {{ $salary->count() }} {{ Str::plural('employer', $salary->count()) }} ·
                        gross {{ $n($salary->sum(fn($w) => $w->grossIncome())) }} ·
                        deductions {{ $n($salaryDeductions) }} ·
                        net {{ $n($salary->sum(fn($w) => $w->netPay())) }}
                    @endif
                </div>
            </div>
            <a href="{{ route('wealth.salary.index', ['client' => $client, 'year' => $taxYear]) }}" class="btn btn-sm btn-accent">
                {{ $salary->isEmpty() ? 'Set up salary' : 'Open salary working' }}
            </a>
        </div>
        @foreach($salary as $w)
            <div class="ws-row" style="grid-template-columns: 1fr 150px 130px 78px;">
                <div>
                    <strong>{{ $w->employer }}</strong>
                    <div class="ws-sub">
                        {{ $w->basis === 'monthly' ? 'Worked month by month' : 'Worked annually' }} ·
                        taxable {{ $n($w->incomeBy('taxable')) }} · exempt {{ $n($w->incomeBy('exempt')) }} ·
                        tax withheld {{ $n($w->taxDeducted()) }}
                    </div>
                </div>
                <div class="ws-money">{{ $n($w->grossIncome()) }}</div>
                <div class="ws-sub">gross, to 7031 / 7032</div>
                <div></div>
            </div>
        @endforeach
    </div>

    {{-- Only heads that have something declared are shown. The rest are in the
         picker below, and reveal themselves when chosen - the same way IRIS
         only shows the heads you say apply. --}}
    @foreach(FbrSchema::incomeHeads() as $key => $head)
        @php $rows = $items[$key] ?? collect(); @endphp
        <div class="ws-sheet head-sheet" id="head-{{ $key }}" data-head="{{ $key }}"
             @if($rows->isEmpty()) style="display: none;" @endif>
            <div class="ws-head">
                <div>
                    <h2>{{ $head['label'] }}</h2>
                    @isset($head['hint'])<div class="ws-sub">{{ $head['hint'] }}</div>@endisset
                </div>
                <div class="ws-money" style="font-weight: 700;">{{ $n($rows->sum('amount')) }}</div>
            </div>

            @forelse($rows as $row)
                <div class="ws-row">
                    <div>
                        <strong>{{ $row->description ?: ($row->wealthLine->description ?? ucfirst($head['line'])) }}</strong>
                        @if($row->wealthLine)
                            <div class="ws-sub">Asset: {{ $row->wealthLine->description }}</div>
                        @endif
                        <div class="ws-sub">
                            @foreach($head['fields'] as $f)
                                @php $v = $row->detail($f['key']); @endphp
                                @if($v !== null && $v !== '')
                                    {{ $f['label'] }}: <strong>{{ is_numeric($v) ? $n($v) : $v }}</strong>@if(!$loop->last) · @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="ws-money">{{ $n($row->amount) }}</div>
                    <div class="ws-sub">
                        {{ FbrSchema::TREATMENTS[$row->treatment]['label'] ?? $row->treatment }}
                        <div>code {{ FbrSchema::TREATMENTS[$row->treatment]['code'] ?? '—' }}</div>
                    </div>
                    <div class="text-end">
                        <form method="POST" action="{{ route('wealth.income-items.destroy', [$client, $row]) }}" class="d-inline"
                              onsubmit="return confirm('Remove this line?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="ws-row"><div class="text-muted">Nothing declared under this head.</div><div></div><div></div><div></div></div>
            @endforelse

            <details class="ws-add">
                <summary>Add a {{ $head['line'] }}</summary>
                <div>
                    <form method="POST" action="{{ route('wealth.income-items.store', $client) }}" class="row g-2 align-items-end pt-3">
                        @csrf
                        <input type="hidden" name="head" value="{{ $key }}">
                        <input type="hidden" name="tax_year" value="{{ $taxYear }}">

                        <div class="col-md-6">
                            <label class="form-label" for="d_{{ $key }}">Description</label>
                            <input type="text" name="description" id="d_{{ $key }}" class="form-control form-control-sm"
                                   placeholder="{{ ucfirst($head['line']) }}">
                        </div>

                        @isset($head['links_asset'])
                            <div class="col-md-6">
                                <label class="form-label" for="a_{{ $key }}">Which asset</label>
                                <select name="wealth_line_id" id="a_{{ $key }}" class="form-select form-select-sm">
                                    <option value="">Not linked to a declared asset</option>
                                    @foreach($lines->whereIn('code', $head['links_asset']) as $l)
                                        <option value="{{ $l->id }}">{{ $l->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endisset

                        @include('wealth.partials._fields', [
                            'fields' => $head['fields'],
                            'values' => [],
                            'name'   => 'details',
                        ])

                        <div class="col-md-3">
                            <label class="form-label" for="t_{{ $key }}">Treatment</label>
                            <select name="treatment" id="t_{{ $key }}" class="form-select form-select-sm">
                                @foreach(FbrSchema::TREATMENTS as $tk => $t)
                                    <option value="{{ $tk }}" @selected($tk === ($head['default_treatment'] ?? 'taxable'))>{{ $t['label'] }} ({{ $t['code'] }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="td_{{ $key }}">Tax deducted</label>
                            <input type="number" step="0.01" name="tax_deducted" id="td_{{ $key }}" class="form-control form-control-sm num">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-accent btn-sm w-100">Add</button>
                        </div>
                    </form>
                </div>
            </details>
        </div>
    @endforeach

    <div class="ws-sheet" id="addHeadCard">
        <div class="ws-head"><h2>Add a source of income</h2></div>
        <div style="padding: 14px 18px;" class="d-flex flex-wrap gap-2 align-items-end">
            <div style="min-width: 280px;">
                <label class="form-label" for="addHead">Head</label>
                <select id="addHead" class="form-select form-select-sm">
                    @foreach(FbrSchema::incomeHeads() as $key => $head)
                        <option value="{{ $key }}" @if(!($items[$key] ?? collect())->isEmpty()) hidden @endif>{{ $head['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-accent btn-sm" onclick="revealHead()">Add</button>
            <span class="ws-sub">Only the heads that apply to this client are shown.</span>
        </div>
    </div>

    {{-- Tax is typed in. Nothing above is used to compute it. --}}
    <div class="ws-sheet">
        <div class="ws-head"><h2>Tax</h2><span class="ws-code">entered from IRIS</span></div>
        <form method="POST" action="{{ route('wealth.income.save', $client) }}">
            @csrf
            <input type="hidden" name="tax_year" value="{{ $taxYear }}">
            <div style="padding: 16px 18px;">
                <div class="alert alert-info" style="font-size: 0.8rem;">
                    Tax chargeable is entered by hand from IRIS. Nothing on this page is worked out from tax rates.
                </div>
                <div class="row g-3">
                    @foreach(['tax_chargeable' => 'Tax chargeable', 'tax_reductions_credits' => 'Tax reductions and credits',
                              'tax_deducted' => 'Tax deducted at source', 'tax_paid' => 'Tax paid with return / advance'] as $f => $label)
                        <div class="col-md-3">
                            <label class="form-label" for="tx_{{ $f }}">{{ $label }}</label>
                            <input type="number" step="0.01" name="{{ $f }}" id="tx_{{ $f }}"
                                   class="form-control form-control-sm num" value="{{ $income->{$f} ?: '' }}">
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <label class="form-label" for="inNotes">Notes</label>
                    <textarea name="notes" id="inNotes" class="form-control form-control-sm" rows="2">{{ $income->notes }}</textarea>
                </div>
                <button class="btn btn-primary btn-sm mt-3">Save tax figures</button>
            </div>
        </form>
    </div>
</div>

{{-- ════════ ASSETS & LIABILITIES ════════ --}}
<div class="tab-pane fade" id="t-assets">
    <form method="POST" action="{{ route('wealth.values.save', $client) }}">
        @csrf
        <input type="hidden" name="tax_year" value="{{ $taxYear }}">
        <div class="ws-sheet">
            <div class="ws-head">
                <h2>Wealth statement as at 30 June {{ $taxYear }}</h2>
                <button class="btn btn-sm btn-primary">Save figures</button>
            </div>

            <div class="ws-row" style="font-weight: 600; color: var(--text-muted); font-size: 0.75rem;">
                <div>Description</div><div class="ws-money">{{ $taxYear }}</div>
                <div class="ws-money">{{ $taxYear - 1 }}</div><div></div>
            </div>

            @foreach($assetHeads + $liabHeads as $code => $head)
                @php $group = $lines->where('code', (string) $code); @endphp
                @continue($group->isEmpty())
                <div class="ws-group">
                    <span>{{ $head['sr'] }}. {{ $head['label'] }}</span>
                    <span class="ws-code">{{ $code }}</span>
                </div>
                @foreach($group as $line)
                    <div class="ws-row">
                        <div>
                            {{ $line->description }}
                            @if($line->attributeSummary())<div class="ws-sub">{{ $line->attributeSummary() }}</div>@endif
                        </div>
                        <div><input type="number" step="0.01" class="form-control form-control-sm num"
                                    name="amounts[{{ $line->id }}]" value="{{ $line->amountFor($taxYear) }}"
                                    aria-label="{{ $line->description }}"></div>
                        <div class="ws-money text-muted">{{ $line->amountFor($taxYear - 1) === null ? '—' : $n($line->amountFor($taxYear - 1)) }}</div>
                        <div class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="removeLine({{ $line->id }}, @json($line->description))"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                @endforeach
            @endforeach

            @if($lines->isEmpty())
                <div class="ws-row"><div class="text-muted py-3">Nothing on the statement yet.</div><div></div><div></div><div></div></div>
            @endif

            <div class="ws-total">
                <div>Total assets <span class="ws-code">7019</span></div>
                <div class="ws-money">{{ $n($totals['current']['assets']) }}</div>
                <div class="ws-money text-muted">{{ $n($totals['prior']['assets']) }}</div><div></div>
            </div>
            <div class="ws-total" style="border-top: 1px solid var(--border);">
                <div>Total liabilities <span class="ws-code">7029</span></div>
                <div class="ws-money">{{ $n($totals['current']['liabilities']) }}</div>
                <div class="ws-money text-muted">{{ $n($totals['prior']['liabilities']) }}</div><div></div>
            </div>
            <div class="ws-total" style="border-top: 1px solid var(--border);">
                <div>Net assets <span class="ws-code">703001</span></div>
                <div class="ws-money">{{ $n($net) }}</div>
                <div class="ws-money text-muted">{{ $n($totals['prior']['net']) }}</div><div></div>
            </div>
        </div>
    </form>

    <div class="ws-sheet">
        <div class="ws-head"><h2>Add a line</h2></div>
        <div style="padding: 16px 18px;">
            <form method="POST" action="{{ route('wealth.lines.store', $client) }}" id="addLineForm">
                @csrf
                <input type="hidden" name="tax_year" value="{{ $taxYear }}">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-5">
                        <label class="form-label" for="lineHead">Head</label>
                        <select name="code" id="lineHead" class="form-select form-select-sm" required onchange="showHeadFields(this.value)">
                            <optgroup label="Assets">
                                @foreach($assetHeads as $code => $h)
                                    <option value="{{ $code }}">{{ $h['sr'] }}. {{ $h['label'] }} ({{ $code }})</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Liabilities">
                                @foreach($liabHeads as $code => $h)
                                    <option value="{{ $code }}">{{ $h['sr'] }}. {{ $h['label'] }} ({{ $code }})</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="lineDesc">Description</label>
                        <input type="text" name="description" id="lineDesc" class="form-control form-control-sm" required
                               placeholder="e.g. 1 Kanal Plot 59/E-1, Hayatabad">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="lineAmt">Value at cost</label>
                        <input type="number" step="0.01" name="amount" id="lineAmt" class="form-control form-control-sm num">
                    </div>
                </div>

                @foreach($assetHeads + $liabHeads as $code => $h)
                    <div class="row g-2 head-fields" data-code="{{ $code }}" style="display: none;">
                        @include('wealth.partials._fields', ['fields' => $h['fields'], 'values' => [], 'name' => 'details'])
                    </div>
                @endforeach

                <button class="btn btn-accent btn-sm mt-3">Add to statement</button>
                <span class="ws-sub ms-2">Assets are declared at cost, including stamp duty, registration and transfer fees.</span>
            </form>
        </div>
    </div>

    <div class="ws-sheet">
        <div class="ws-head"><h2>Carry figures forward</h2></div>
        <div style="padding: 16px 18px;">
            <p class="ws-sub mb-2">Copies each line's figure into another year. Anything already entered for the target year is left alone.</p>
            <form method="POST" action="{{ route('wealth.carry-forward', $client) }}" class="row g-2 align-items-end" style="max-width: 460px;">
                @csrf
                <div class="col-5"><label class="form-label" for="cfFrom">From</label>
                    <input type="number" name="from_year" id="cfFrom" class="form-control form-control-sm" value="{{ $taxYear - 1 }}" required></div>
                <div class="col-5"><label class="form-label" for="cfTo">To</label>
                    <input type="number" name="to_year" id="cfTo" class="form-control form-control-sm" value="{{ $taxYear }}" required></div>
                <div class="col-2"><button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-arrow-right"></i></button></div>
            </form>
        </div>
    </div>
</div>

{{-- ════════ ANNEX-F ════════ --}}
<div class="tab-pane fade" id="t-expenses">
    <form method="POST" action="{{ route('wealth.expenses.save', $client) }}">
        @csrf
        <input type="hidden" name="tax_year" value="{{ $taxYear }}">
        <div class="ws-sheet">
            <div class="ws-head">
                <div><h2>Annex-F — personal expenses</h2>
                     <div class="ws-sub">Feeds Sr. 24 of the reconciliation <span class="ws-code">7089</span></div></div>
                <button class="btn btn-sm btn-primary">Save</button>
            </div>
            @foreach(FbrSchema::EXPENSES as $code => $label)
                @php $shown = isset($expenses[$code]); @endphp
                <div class="ws-row opt-row" id="exp-{{ $code }}" data-key="{{ $code }}"
                     style="grid-template-columns: 1fr 180px 90px;@if(!$shown) display: none;@endif">
                    <div>{{ $label }}</div>
                    <div><input type="number" step="0.01" name="expenses[{{ $code }}]" class="form-control form-control-sm num"
                                value="{{ $expenses[$code] ?? '' }}" aria-label="{{ $label }}" @if(!$shown) disabled @endif></div>
                    <div class="ws-code">{{ $code }}</div>
                </div>
            @endforeach
            <div style="padding: 12px 18px; border-bottom: 1px solid var(--n-100);" class="d-flex flex-wrap gap-2 align-items-end">
                <div style="min-width: 300px;">
                    <label class="form-label" for="addExpense">Add an expense head</label>
                    <select id="addExpense" class="form-select form-select-sm">
                        @foreach(FbrSchema::EXPENSES as $code => $label)
                            <option value="exp-{{ $code }}" @if(isset($expenses[$code])) hidden @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="revealRow('addExpense')">Add</button>
            </div>
            <div class="ws-row" style="grid-template-columns: 1fr 180px 90px; background: var(--warn-tint);">
                <div>{{ FbrSchema::EXPENSE_CONTRA_LABEL }}</div>
                <div><input type="number" step="0.01" name="expenses[{{ FbrSchema::EXPENSE_CONTRA }}]"
                            class="form-control form-control-sm num"
                            value="{{ $expenses[FbrSchema::EXPENSE_CONTRA] ?? '' }}"
                            aria-label="{{ FbrSchema::EXPENSE_CONTRA_LABEL }}"></div>
                <div class="ws-code">{{ FbrSchema::EXPENSE_CONTRA }}</div>
            </div>
            @if($salaryDeductions > 0)
                <div class="ws-group"><span>From the salary working</span><span class="ws-code">entered there</span></div>
                @foreach($salary as $w)
                    @foreach($w->deductions() as $c)
                        @continue($c->amount() == 0)
                        <div class="ws-row" style="grid-template-columns: 1fr 180px 90px;">
                            <div>{{ $c->label }} <span class="ws-sub">— {{ $w->employer }}</span></div>
                            <div class="ws-money">{{ $n($c->amount()) }}</div>
                            <div class="ws-code">{{ $c->is_tax ? 'tax' : '' }}</div>
                        </div>
                    @endforeach
                @endforeach
            @endif
            <div class="ws-total" style="grid-template-columns: 1fr 180px 90px;">
                <div>Personal expenses
                    @if($salaryDeductions > 0)
                        <div class="ws-sub">Includes {{ $n($salaryDeductions) }} stopped at source, of which {{ $n($salaryTax) }} is tax.</div>
                    @endif
                </div>
                <div class="ws-money">{{ $n($expenseTotal) }}</div>
                <div class="ws-code">7089</div>
            </div>
        </div>
    </form>
</div>

{{-- ════════ RECONCILIATION ════════ --}}
<div class="tab-pane fade" id="t-recon">
    <form method="POST" action="{{ route('wealth.reconciliation.save', $client) }}">
        @csrf
        <input type="hidden" name="tax_year" value="{{ $taxYear }}">
        <div class="ws-sheet">
            <div class="ws-head"><h2>Reconciliation of net assets</h2><button class="btn btn-sm btn-primary">Save</button></div>

            <div class="ws-row" style="grid-template-columns: 1fr 180px 90px;">
                <div>Net assets, current year</div>
                <div class="ws-money">{{ $n($net) }}</div><div class="ws-code">703001</div>
            </div>
            <div class="ws-row" style="grid-template-columns: 1fr 180px 90px;">
                <div>Net assets, previous year
                    <div class="ws-sub">Taken from last year's statement; change it if the prior year was filed elsewhere.</div></div>
                <div><input type="number" step="0.01" name="opening_wealth" class="form-control form-control-sm num"
                            value="{{ $recon->opening_wealth }}" aria-label="Net assets previous year"></div>
                <div class="ws-code">703002</div>
            </div>
            <div class="ws-row" style="grid-template-columns: 1fr 180px 90px; font-weight: 600;">
                <div>Increase / decrease in assets</div>
                <div class="ws-money">{{ $n($increase) }}</div><div class="ws-code">703003</div>
            </div>

            <div class="ws-group"><span>Sr. 23 — Inflows</span><span class="ws-code">7049</span></div>
            @foreach(['taxable' => '7031', 'exempt' => '7032', 'final' => '7033'] as $t => $code)
                <div class="ws-row" style="grid-template-columns: 1fr 180px 90px;">
                    <div>{{ FbrSchema::INFLOWS[$code] }}
                        <div class="ws-sub">From the income working and the salary working — add lines there, not here.</div></div>
                    <div class="ws-money">{{ $n($declared[$t]) }}</div><div class="ws-code">{{ $code }}</div>
                </div>
            @endforeach
            @foreach(['adjustments' => '7034', 'foreign_remittance' => '7035', 'inheritance' => '7036',
                      'gift_received' => '7037', 'gain_disposal' => '7038', 'other_sources' => '7048'] as $f => $code)
                @php $shown = (float) $recon->{$f} != 0; @endphp
                <div class="ws-row opt-row" id="in-{{ $f }}" style="grid-template-columns: 1fr 180px 90px;@if(!$shown) display: none;@endif">
                    <div>{{ FbrSchema::INFLOWS[$code] }}</div>
                    <div><input type="number" step="0.01" name="{{ $f }}" class="form-control form-control-sm num"
                                value="{{ $recon->{$f} ?: '' }}" aria-label="{{ FbrSchema::INFLOWS[$code] }}"></div>
                    <div class="ws-code">{{ $code }}</div>
                </div>
            @endforeach
            <div style="padding: 10px 18px;" class="d-flex flex-wrap gap-2 align-items-end">
                <select id="addInflow" class="form-select form-select-sm" style="max-width: 420px;">
                    @foreach(['adjustments' => '7034', 'foreign_remittance' => '7035', 'inheritance' => '7036',
                              'gift_received' => '7037', 'gain_disposal' => '7038', 'other_sources' => '7048'] as $f => $code)
                        <option value="in-{{ $f }}" @if((float) $recon->{$f} != 0) hidden @endif>{{ FbrSchema::INFLOWS[$code] }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="revealRow('addInflow')">Add inflow</button>
            </div>
            <div class="ws-total" style="grid-template-columns: 1fr 180px 90px;">
                <div>Total inflows</div><div class="ws-money">{{ $n($inflows) }}</div><div class="ws-code">7049</div>
            </div>

            <div class="ws-group"><span>Sr. 24 — Personal expenses</span><span class="ws-code">7089</span></div>
            <div class="ws-row" style="grid-template-columns: 1fr 180px 90px;">
                <div>From Annex-F</div><div class="ws-money">{{ $n($expenseTotal) }}</div><div class="ws-code">7089</div>
            </div>

            <div class="ws-group"><span>Sr. 25 — Outflows</span><span class="ws-code">7099</span></div>
            @foreach(['gift_given' => '7091', 'loss_disposal' => '7092', 'other_outflows' => '7098'] as $f => $code)
                @php $shown = (float) $recon->{$f} != 0; @endphp
                <div class="ws-row opt-row" id="out-{{ $f }}" style="grid-template-columns: 1fr 180px 90px;@if(!$shown) display: none;@endif">
                    <div>{{ FbrSchema::OUTFLOWS[$code] }}</div>
                    <div><input type="number" step="0.01" name="{{ $f }}" class="form-control form-control-sm num"
                                value="{{ $recon->{$f} ?: '' }}" aria-label="{{ FbrSchema::OUTFLOWS[$code] }}"></div>
                    <div class="ws-code">{{ $code }}</div>
                </div>
            @endforeach
            <div style="padding: 10px 18px;" class="d-flex flex-wrap gap-2 align-items-end">
                <select id="addOutflow" class="form-select form-select-sm" style="max-width: 420px;">
                    @foreach(['gift_given' => '7091', 'loss_disposal' => '7092', 'other_outflows' => '7098'] as $f => $code)
                        <option value="out-{{ $f }}" @if((float) $recon->{$f} != 0) hidden @endif>{{ FbrSchema::OUTFLOWS[$code] }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="revealRow('addOutflow')">Add outflow</button>
            </div>
            <div class="ws-total" style="grid-template-columns: 1fr 180px 90px;">
                <div>Total outflows</div><div class="ws-money">{{ $n($outflows) }}</div><div class="ws-code">7099</div>
            </div>

            <div class="ws-total" style="grid-template-columns: 1fr 180px 90px; background: {{ abs($unreconciled) < 1 ? 'var(--ok-tint)' : 'var(--danger-tint)' }};">
                <div>Unreconciled amount — must be nil</div>
                <div class="ws-money">{{ $n($unreconciled) }}</div><div class="ws-code">703000</div>
            </div>

            <div style="padding: 14px 18px;">
                <p class="ws-sub">703000 = 7049 − 7089 − 7099 − 703003. IRIS will not accept the statement until this is nil.</p>
                <label class="form-label" for="rcNotes">Notes</label>
                <textarea name="notes" id="rcNotes" class="form-control form-control-sm" rows="2">{{ $recon->notes }}</textarea>
            </div>
        </div>
    </form>
</div>

</div>

<form method="POST" id="removeLineForm" class="d-none">@csrf @method('DELETE')</form>
@endsection

@section('scripts')
<script>
/*
 * Reveal a head or a row that was hidden because nothing had been entered
 * against it. Hidden number inputs are disabled so an untouched field is never
 * submitted - otherwise saving would write a zero over a head the preparer
 * never opened.
 */
function revealHead() {
    var sel = document.getElementById('addHead');
    var opt = sel.selectedOptions[0];
    if (!opt) return;

    var sheet = document.getElementById('head-' + sel.value);
    if (!sheet) return;

    sheet.style.display = '';
    opt.hidden = true;

    // Open its add form and put the cursor in the first field.
    var det = sheet.querySelector('details.ws-add');
    if (det) {
        det.open = true;
        var first = det.querySelector('input, select');
        if (first) first.focus();
    }
    sheet.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Move on to the next head still available, so repeated adds are quick.
    var next = Array.prototype.find.call(sel.options, function (o) { return !o.hidden; });
    if (next) { sel.value = next.value; } else { document.getElementById('addHeadCard').style.display = 'none'; }
}

function revealRow(selectId) {
    var sel = document.getElementById(selectId);
    var opt = sel.selectedOptions[0];
    if (!opt) return;

    var row = document.getElementById(sel.value);
    if (!row) return;

    row.style.display = '';
    row.querySelectorAll('input, select').forEach(function (i) { i.disabled = false; });
    opt.hidden = true;

    var input = row.querySelector('input');
    if (input) input.focus();

    var next = Array.prototype.find.call(sel.options, function (o) { return !o.hidden; });
    if (next) { sel.value = next.value; } else { sel.closest('div').style.display = 'none'; }
}

function showHeadFields(code) {
    document.querySelectorAll('.head-fields').forEach(function (el) {
        var on = el.dataset.code === code;
        el.style.display = on ? '' : 'none';
        // Disabled inputs are not submitted, so a hidden head cannot leak its
        // fields into the head actually being saved.
        el.querySelectorAll('input, select').forEach(function (i) { i.disabled = !on; });
    });
}
document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('lineHead');
    if (sel) showHeadFields(sel.value);
});

function removeLine(id, description) {
    if (!confirm('Remove “' + description + '” from the statement?\n\nIts figures for every year go too.')) return;
    var f = document.getElementById('removeLineForm');
    f.action = '{{ url('wealth/' . $client->id . '/lines') }}/' + id;
    f.submit();
}
</script>
@endsection
