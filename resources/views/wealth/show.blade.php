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

    /* The statement itself: Sr. | description | code | this year | last year. */
    .st-head, .st-row { display: grid; grid-template-columns: 42px 1fr 12px 150px 150px;
                        gap: 10px; align-items: center; padding: 6px 18px; font-size: 0.83rem; }
    .st-head { background: var(--surface-sunk); border-bottom: 1px solid var(--border);
               font-size: 0.72rem; font-weight: 600; color: var(--text-muted); }
    .st-row { border-bottom: 1px solid var(--n-100); }
    .st-headrow { font-weight: 600; background: var(--surface-sunk); }
    .st-count { background: var(--n-100); color: var(--text-soft); border-radius: 10px;
                padding: 0 6px; font-size: 0.68rem; margin-left: 6px; font-weight: 600; }
    .st-detail { background: var(--n-25); }
    .st-line { font-weight: 400; padding-left: 30px; }
    .st-rn { text-align: right; color: var(--text-faint); font-size: 0.75rem; }
    .st-sum { font-weight: 600; background: var(--surface); }
    .st-strong { border-top: 1px solid var(--border-strong); }
    .st-py { color: var(--text-muted); position: relative; }
    .st-acts { position: absolute; right: 0; top: 50%; transform: translateY(-50%);
               display: none; gap: 4px; background: var(--n-25); padding-left: 6px; }
    .st-line:hover .st-acts { display: flex; }
    .st-save { padding: 12px 18px; border-bottom: 1px solid var(--border); }

    /* The working behind a line: opening, what moved, closing. */
    .st-moves { display: none; background: var(--surface); border-left: 2px solid var(--accent);
                margin: 0 18px 8px 48px; border-radius: 0 var(--radius) var(--radius) 0; }
    .st-moves.open { display: block; }
    .st-mv-head { padding: 7px 14px; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
                  letter-spacing: 0.6px; color: var(--text-muted); border-bottom: 1px solid var(--n-100); }
    .st-mv { display: grid; grid-template-columns: 1fr 40px 140px 46px; gap: 8px; align-items: center;
             padding: 6px 14px; font-size: 0.82rem; border-bottom: 1px solid var(--n-100); }
    .st-mv form { margin: 0; }
    .st-mv-op, .st-mv-cl { color: var(--text-soft); font-weight: 600; background: var(--n-25); }
    .st-mv-add { padding: 10px 14px; }
    /* A disclosure styled as a button: opens on click or Enter, and needs no
       script to do it. */
    details.ws-open > summary { list-style: none; padding: 14px 18px; }
    details.ws-open > summary::-webkit-details-marker { display: none; }
    details.ws-open > summary .btn { pointer-events: none; }
    details.ws-open > div { padding: 0 18px 16px; }

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
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-assets" type="button">Wealth statement</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-expenses" type="button">Annex-F expenses</button></li>
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
                                    <option value="{{ $tk }}" @selected($tk === ($head['default_treatment'] ?? 'taxable'))>{{ $t['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="td_{{ $key }}">Tax deducted</label>
                            <input type="number" step="1" name="tax_deducted" id="td_{{ $key }}" class="form-control form-control-sm num">
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
        <div class="ws-head"><h2>Tax</h2></div>
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
                            <input type="number" step="1" name="{{ $f }}" id="tx_{{ $f }}"
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

{{-- ════════ WEALTH STATEMENT ════════
     One document: assets, liabilities, then the reconciliation, each line
     against this year and last. --}}
<div class="tab-pane fade" id="t-assets">
    <div class="st-doc">
        <header class="st-title">
            <div>
                <h2>Wealth Statement</h2>
                <p>{{ $client->name }} · as at 30 June {{ $taxYear }} · figures in PKR, at cost</p>
            </div>
            <div class="st-actions">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLineModal">
                    <i class="bi bi-plus-lg me-1"></i> Add a line
                </button>
                <span class="st-status" data-status></span>
            </div>
        </header>

        <div class="st-cols">
            <div>Sr.</div>
            <div>Particulars</div>
            <div class="st-num">{{ $taxYear }}</div>
            <div class="st-num st-prior">{{ $taxYear - 1 }}</div>
        </div>

        <form method="POST" action="{{ route('wealth.values.save', $client) }}" id="stmtForm" data-autosave>
            @csrf
            <input type="hidden" name="tax_year" value="{{ $taxYear }}">

            @php
                $renderLines = function ($group) use ($client, $taxYear, $n) { return $group; };
            @endphp

            @foreach($assetHeads as $code => $head)
                @continue($code === '7016')
                @php
                    $group = $lines->where('code', (string) $code);
                    $cy = $group->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));
                    $py = $group->sum(fn($l) => (float) ($l->amountFor($taxYear - 1) ?? 0));
                @endphp
                @continue($group->isEmpty())
                <div class="st-r st-head">
                    <div class="st-sr">{{ $head['sr'] }}</div>
                    <div>{{ $head['label'] }}</div>
                    <div class="st-num">{{ $n($cy) }}</div>
                    <div class="st-num st-prior">{{ $py ? $n($py) : '—' }}</div>
                </div>
                @include('wealth.partials._lines', ['group' => $group])
            @endforeach

            @php
                $inside   = $lines->where('kind', 'asset')->where('code', '!=', '7016');
                $insideCy = $inside->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));
                $insidePy = $inside->sum(fn($l) => (float) ($l->amountFor($taxYear - 1) ?? 0));
                $fgn      = $lines->where('code', '7016');
                $fgnCy    = $fgn->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));
                $fgnPy    = $fgn->sum(fn($l) => (float) ($l->amountFor($taxYear - 1) ?? 0));
            @endphp

            <div class="st-r st-sub">
                <div class="st-sr">15</div><div>Total assets inside Pakistan</div>
                <div class="st-num">{{ $n($insideCy) }}</div>
                <div class="st-num st-prior">{{ $n($insidePy) }}</div>
            </div>

            @if($fgn->isNotEmpty())
                <div class="st-r st-head">
                    <div class="st-sr">16</div><div>Assets held outside Pakistan</div>
                    <div class="st-num">{{ $n($fgnCy) }}</div>
                    <div class="st-num st-prior">{{ $fgnPy ? $n($fgnPy) : '—' }}</div>
                </div>
                @include('wealth.partials._lines', ['group' => $fgn])
            @endif

            <div class="st-r st-total">
                <div class="st-sr">17</div><div>Total assets</div>
                <div class="st-num">{{ $n($totals['current']['assets']) }}</div>
                <div class="st-num st-prior">{{ $n($totals['prior']['assets']) }}</div>
            </div>

            @foreach($liabHeads as $code => $head)
                @php
                    $group = $lines->where('code', (string) $code);
                    $cy = $group->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));
                    $py = $group->sum(fn($l) => (float) ($l->amountFor($taxYear - 1) ?? 0));
                @endphp
                @continue($group->isEmpty())
                <div class="st-r st-head">
                    <div class="st-sr">{{ $head['sr'] }}</div><div>{{ $head['label'] }}</div>
                    <div class="st-num">{{ $n($cy) }}</div>
                    <div class="st-num st-prior">{{ $py ? $n($py) : '—' }}</div>
                </div>
                @include('wealth.partials._lines', ['group' => $group])
            @endforeach

            <div class="st-r st-total">
                <div class="st-sr">19</div><div>Total liabilities</div>
                <div class="st-num">{{ $n($totals['current']['liabilities']) }}</div>
                <div class="st-num st-prior">{{ $n($totals['prior']['liabilities']) }}</div>
            </div>
        </form>

        {{-- ── Reconciliation ── --}}
        <header class="st-title st-title-sub">
            <div>
                <h2>Reconciliation of net assets</h2>
                <p>Sources of the year's movement in wealth</p>
            </div>
            <div class="st-actions"><span class="st-status" data-status></span></div>
        </header>

        <form method="POST" action="{{ route('wealth.reconciliation.save', $client) }}" id="reconForm" data-autosave>
            @csrf
            <input type="hidden" name="tax_year" value="{{ $taxYear }}">

            <div class="st-r st-total">
                <div class="st-sr">20</div><div>Net assets, current year</div>
                <div class="st-num">{{ $n($net) }}</div><div class="st-num st-prior"></div>
            </div>
            <div class="st-r">
                <div class="st-sr">21</div><div>Net assets, previous year</div>
                <div class="st-num"><input type="number" step="1" name="opening_wealth" class="st-in"
                                           value="{{ $recon->opening_wealth }}" aria-label="Net assets previous year"></div>
                <div class="st-num st-prior">{{ $n($totals['prior']['net']) }}</div>
            </div>
            <div class="st-r st-sub">
                <div class="st-sr">22</div><div>Increase / decrease in assets</div>
                <div class="st-num">{{ $n($increase) }}</div><div class="st-num st-prior"></div>
            </div>

            <div class="st-r st-head">
                <div class="st-sr">23</div><div>Inflows</div>
                <div class="st-num">{{ $n($inflows) }}</div><div class="st-num st-prior"></div>
            </div>
            @foreach(['taxable' => ['i', '7031'], 'exempt' => ['ii', '7032'], 'final' => ['iii', '7033']] as $t => [$rn, $code])
                <div class="st-r st-l">
                    <div class="st-sr st-rn">{{ $rn }}</div>
                    <div>{{ FbrSchema::INFLOWS[$code] }}<span class="st-from">from the income and salary workings</span></div>
                    <div class="st-num">{{ $n($declared[$t]) }}</div><div class="st-num st-prior"></div>
                </div>
            @endforeach
            @foreach(['adjustments' => ['iv', '7034'], 'foreign_remittance' => ['v', '7035'], 'inheritance' => ['vi', '7036'],
                      'gift_received' => ['vii', '7037'], 'gain_disposal' => ['viii', '7038'], 'other_sources' => ['ix', '7048']] as $f => [$rn, $code])
                <div class="st-r st-l">
                    <div class="st-sr st-rn">{{ $rn }}</div>
                    <div>{{ FbrSchema::INFLOWS[$code] }}</div>
                    <div class="st-num"><input type="number" step="1" name="{{ $f }}" class="st-in"
                                               value="{{ $recon->{$f} ?: '' }}" aria-label="{{ FbrSchema::INFLOWS[$code] }}"></div>
                    <div class="st-num st-prior"></div>
                </div>
            @endforeach

            <div class="st-r st-head">
                <div class="st-sr">24</div>
                <div>Personal expenses<span class="st-from">from Annex-F@if($salaryDeductions > 0), including {{ $n($salaryDeductions) }} stopped at source@endif</span></div>
                <div class="st-num">{{ $n($expenseTotal) }}</div><div class="st-num st-prior"></div>
            </div>

            <div class="st-r st-head">
                <div class="st-sr">25</div><div>Outflows</div>
                <div class="st-num">{{ $n($outflows) }}</div><div class="st-num st-prior"></div>
            </div>
            @foreach(['gift_given' => ['i', '7091'], 'loss_disposal' => ['ii', '7092'], 'other_outflows' => ['iii', '7098']] as $f => [$rn, $code])
                <div class="st-r st-l">
                    <div class="st-sr st-rn">{{ $rn }}</div>
                    <div>{{ FbrSchema::OUTFLOWS[$code] }}</div>
                    <div class="st-num"><input type="number" step="1" name="{{ $f }}" class="st-in"
                                               value="{{ $recon->{$f} ?: '' }}" aria-label="{{ FbrSchema::OUTFLOWS[$code] }}"></div>
                    <div class="st-num st-prior"></div>
                </div>
            @endforeach

            <div class="st-r st-final {{ abs($unreconciled) < 1 ? 'ok' : 'bad' }}">
                <div class="st-sr">26</div>
                <div>Unreconciled amount<span class="st-from">inflows less personal expenses, outflows and the increase in assets — must be nil</span></div>
                <div class="st-num">{{ $n($unreconciled) }}</div><div class="st-num st-prior"></div>
            </div>

            <div class="st-notes">
                <label class="form-label" for="rcNotes">Notes</label>
                <textarea name="notes" id="rcNotes" class="form-control form-control-sm" rows="2">{{ $recon->notes }}</textarea>
            </div>
        </form>
    </div>
</div>

{{-- ════════ ANNEX-F ════════ --}}
<div class="tab-pane fade" id="t-expenses">
    <form method="POST" action="{{ route('wealth.expenses.save', $client) }}" data-autosave>
        @csrf
        <input type="hidden" name="tax_year" value="{{ $taxYear }}">
        <div class="st-doc">
            <header class="st-title">
                <div>
                    <h2>Annex-F — personal expenses</h2>
                    <p>Feeds Sr. 24 of the reconciliation</p>
                </div>
                <div class="st-actions"><span class="st-status" data-status></span></div>
            </header>
            <div class="st-cols st-cols-2"><div>Particulars</div><div class="st-num">{{ $taxYear }}</div><div></div></div>
            @foreach(FbrSchema::EXPENSES as $code => $label)
                @php $shown = isset($expenses[$code]); @endphp
                <div class="st-r st-r-2 opt-row" id="exp-{{ $code }}" data-key="{{ $code }}"
                     style="@if(!$shown) display: none;@endif">
                    <div>{{ $label }}</div>
                    <div><input type="number" step="1" name="expenses[{{ $code }}]" class="st-in"
                                value="{{ $expenses[$code] ?? '' }}" aria-label="{{ $label }}" @if(!$shown) disabled @endif></div>
                    <div></div>
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
                <div><input type="number" step="1" name="expenses[{{ FbrSchema::EXPENSE_CONTRA }}]"
                            class="st-in"
                            value="{{ $expenses[FbrSchema::EXPENSE_CONTRA] ?? '' }}"
                            aria-label="{{ FbrSchema::EXPENSE_CONTRA_LABEL }}"></div>
                <div></div>
            </div>
            @if($salaryDeductions > 0)
                <div class="st-r st-r-2 st-head"><span>From the salary working</span></div>
                @foreach($salary as $w)
                    @foreach($w->deductions() as $c)
                        @continue($c->amount() == 0)
                        <div class="st-r st-r-2" >
                            <div>{{ $c->label }} <span class="ws-sub">— {{ $w->employer }}</span></div>
                            <div class="st-num">{{ $n($c->amount()) }}</div>
                            <div></div>
                        </div>
                    @endforeach
                @endforeach
            @endif
            <div class="st-r st-r-2 st-total">
                <div>Personal expenses
                    @if($salaryDeductions > 0)
                        <span class="st-from">Includes {{ $n($salaryDeductions) }} stopped at source, of which {{ $n($salaryTax) }} is tax.</div>
                    @endif
                </div>
                <div class="st-num">{{ $n($expenseTotal) }}</div>
                <div></div>
            </div>
        </div>
    </form>
</div>

</div>

{{-- Movement forms live out here, clear of the statement's save form. --}}
@foreach($lines as $line)
    <form method="POST" id="mvadd{{ $line->id }}" class="d-none"
          action="{{ route('wealth.movements.store', [$client, $line]) }}">
        @csrf
        <input type="hidden" name="tax_year" value="{{ $taxYear }}">
    </form>
    @foreach($line->movementsFor($taxYear) as $m)
        <form method="POST" id="mvdel{{ $m->id }}" class="d-none"
              action="{{ route('wealth.movements.destroy', [$client, $line, $m]) }}"
              onsubmit="return confirm('Remove this movement?')">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endforeach

<form method="POST" id="balancingForm" class="d-none">@csrf<input type="hidden" name="tax_year" value="{{ $taxYear }}"></form>

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

function setBalancing(lineId) {
    if (!confirm('Make this the balancing figure?\n\nIt will be worked out so the reconciliation comes to nil, and can no longer be typed in.')) return;
    var f = document.getElementById('balancingForm');
    f.action = '{{ url('wealth/' . $client->id . '/lines') }}/' + lineId + '/balancing';
    f.submit();
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
