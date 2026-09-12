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

{{-- ════════ WEALTH STATEMENT — laid out as IRIS presents it ════════
     One numbered statement, Sr. 1 to 26, each row carrying its IRIS code, with
     last year beside this year. Heads expand to the lines behind them. --}}
<div class="tab-pane fade" id="t-assets">
    <div class="ws-sheet">
        <div class="ws-head">
            <div>
                <h2>Wealth Statement</h2>
                <div class="ws-sub">As at 30 June · figures in PKR · assets declared at cost</div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="ws-sub">Only heads with something declared are shown</span>
                <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#addLineModal">
                    <i class="bi bi-plus-lg me-1"></i> Add a line
                </button>
            </div>
        </div>

        <div class="st-head">
            <div>Sr.</div><div>Description</div><div></div>
            <div class="ws-money">{{ $taxYear }}</div>
            <div class="ws-money">{{ $taxYear - 1 }}</div>
        </div>

        {{-- ── Assets, Sr. 1–14 ── --}}
        <form method="POST" action="{{ route('wealth.values.save', $client) }}" id="stmtForm">
            @csrf
            <input type="hidden" name="tax_year" value="{{ $taxYear }}">

            @foreach($assetHeads as $code => $head)
                @continue($code === '7016')
                @php
                    $group = $lines->where('code', (string) $code);
                    $cy = $group->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));
                    $py = $group->sum(fn($l) => (float) ($l->amountFor($taxYear - 1) ?? 0));
                @endphp
                @continue($group->isEmpty())
                <div class="st-row st-headrow">
                    <div>{{ $head['sr'] }}</div>
                    <div>
                        {{ $head['label'] }}
                        <span class="st-count">{{ $group->count() }}</span>
                    </div>
                    <div></div>
                    <div class="ws-money">{{ $cy ? $n($cy) : '' }}</div>
                    <div class="ws-money st-py">{{ $py ? $n($py) : '' }}</div>
                </div>
                <div class="st-detail" id="det-{{ $code }}">
                    @foreach($group as $line)
                        @php $moves = $line->movementsFor($taxYear); $worked = $moves->isNotEmpty(); @endphp
                        <div class="st-row st-line">
                            <div></div>
                            <div>
                                {{ $line->description }}
                                @if($line->balancing)<span class="badge bg-info" style="font-size: 0.6rem;">balancing figure</span>@endif
                                @if($worked)<span class="badge bg-secondary" style="font-size: 0.6rem;">worked</span>@endif
                                @if($line->attributeSummary())<div class="ws-sub">{{ $line->attributeSummary() }}</div>@endif
                            </div>
                            <div></div>
                            <div>
                                @if($line->balancing || $worked)
                                    <div class="ws-money" style="font-weight: 600;">{{ $n($line->amountFor($taxYear)) }}</div>
                                @else
                                    <input type="number" step="0.01" class="form-control form-control-sm num"
                                           name="amounts[{{ $line->id }}]" value="{{ $line->amountFor($taxYear) }}"
                                           aria-label="{{ $line->description }}">
                                @endif
                            </div>
                            <div class="ws-money st-py">
                                {{ $line->amountFor($taxYear - 1) === null ? '—' : $n($line->amountFor($taxYear - 1)) }}
                                <div class="st-acts">
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Additions and disposals"
                                            onclick="toggleMoves({{ $line->id }})"><i class="bi bi-list-ul"></i></button>
                                    @unless($line->balancing)
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Make this the balancing figure"
                                                onclick="setBalancing({{ $line->id }})"><i class="bi bi-calculator"></i></button>
                                    @endunless
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Remove"
                                            onclick="removeLine({{ $line->id }}, @json($line->description))"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>

                        {{-- The working behind the figure: what was added or taken out,
                             when and why. This is what has to stand up if the year is queried. --}}
                        <div class="st-moves @if($worked) open @endif" id="mv-{{ $line->id }}">
                            <div class="st-mv-head">Movements in {{ $taxYear }}</div>
                            <div class="st-mv st-mv-op">
                                <div>Opening — as at 30 June {{ $taxYear - 1 }}</div><div></div>
                                <div class="ws-money">{{ $n($line->values->firstWhere('tax_year', $taxYear - 1)?->amount ?? 0) }}</div><div></div>
                            </div>
                            @foreach($moves as $m)
                                <div class="st-mv">
                                    <div>
                                        {{ $m->note ?: \App\Models\WealthMovement::KINDS[$m->kind] }}
                                        <div class="ws-sub">{{ \App\Models\WealthMovement::KINDS[$m->kind] }}@if($m->occurred_on) · {{ $m->occurred_on->format('d M Y') }}@endif</div>
                                    </div>
                                    <div></div>
                                    <div class="ws-money" style="color: {{ $m->kind === 'disposal' ? 'var(--danger-ink)' : 'var(--ok-ink)' }};">
                                        {{ $m->kind === 'disposal' ? '(' . $n($m->amount) . ')' : $n($m->amount) }}
                                    </div>
                                    <div class="text-end">
                                        <form method="POST" action="{{ route('wealth.movements.destroy', [$client, $line, $m]) }}"
                                              onsubmit="return confirm('Remove this movement?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                            <div class="st-mv st-mv-cl">
                                <div>Closing — as at 30 June {{ $taxYear }}</div><div></div>
                                <div class="ws-money">{{ $n($line->amountFor($taxYear)) }}</div><div></div>
                            </div>
                            <form method="POST" action="{{ route('wealth.movements.store', [$client, $line]) }}" class="st-mv-add row g-2 align-items-end">
                                @csrf
                                <input type="hidden" name="tax_year" value="{{ $taxYear }}">
                                <div class="col-md-2">
                                    <label class="form-label" for="mk{{ $line->id }}">Kind</label>
                                    <select name="kind" id="mk{{ $line->id }}" class="form-select form-select-sm">
                                        @foreach(\App\Models\WealthMovement::KINDS as $k => $label)
                                            <option value="{{ $k }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="mn{{ $line->id }}">What happened</label>
                                    <input type="text" name="note" id="mn{{ $line->id }}" class="form-control form-control-sm"
                                           placeholder="e.g. Construction of ground floor">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="md{{ $line->id }}">Date</label>
                                    <input type="date" name="occurred_on" id="md{{ $line->id }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ma{{ $line->id }}">Amount</label>
                                    <input type="number" step="0.01" name="amount" id="ma{{ $line->id }}" class="form-control form-control-sm num" required>
                                </div>
                                <div class="col-md-1"><button class="btn btn-accent btn-sm w-100">Add</button></div>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endforeach

            @php
                $inside = $lines->where('kind', 'asset')->where('code', '!=', '7016');
                $insideCy = $inside->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));
                $insidePy = $inside->sum(fn($l) => (float) ($l->amountFor($taxYear - 1) ?? 0));
                $fgn = $lines->where('code', '7016');
                $fgnCy = $fgn->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));
                $fgnPy = $fgn->sum(fn($l) => (float) ($l->amountFor($taxYear - 1) ?? 0));
            @endphp

            <div class="st-row st-sum">
                <div>15</div><div>Total Assets inside Pakistan</div><div></div>
                <div class="ws-money">{{ $n($insideCy) }}</div><div class="ws-money st-py">{{ $n($insidePy) }}</div>
            </div>

            @if($fgn->isNotEmpty())
            <div class="st-row st-headrow">
                <div>16</div>
                <div>Assets held outside Pakistan <span class="st-count">{{ $fgn->count() }}</span></div>
                <div></div>
                <div class="ws-money">{{ $fgnCy ? $n($fgnCy) : '' }}</div>
                <div class="ws-money st-py">{{ $fgnPy ? $n($fgnPy) : '' }}</div>
            </div>
            <div class="st-detail" id="det-7016">
                @foreach($fgn as $line)
                    <div class="st-row st-line">
                        <div></div>
                        <div>{{ $line->description }}
                            @if($line->attributeSummary())<div class="ws-sub">{{ $line->attributeSummary() }}</div>@endif</div>
                        <div></div>
                        <div><input type="number" step="0.01" class="form-control form-control-sm num"
                                    name="amounts[{{ $line->id }}]" value="{{ $line->amountFor($taxYear) }}"
                                    aria-label="{{ $line->description }}"></div>
                        <div class="ws-money st-py">{{ $line->amountFor($taxYear - 1) === null ? '—' : $n($line->amountFor($taxYear - 1)) }}
                            <div class="st-acts">
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="removeLine({{ $line->id }}, @json($line->description))"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            <div class="st-row st-sum st-strong">
                <div>17</div><div>Total Assets</div><div></div>
                <div class="ws-money">{{ $n($totals['current']['assets']) }}</div>
                <div class="ws-money st-py">{{ $n($totals['prior']['assets']) }}</div>
            </div>

            {{-- ── Liabilities, Sr. 18–19 ── --}}
            @foreach($liabHeads as $code => $head)
                @php
                    $group = $lines->where('code', (string) $code);
                    $cy = $group->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));
                    $py = $group->sum(fn($l) => (float) ($l->amountFor($taxYear - 1) ?? 0));
                @endphp
                @continue($group->isEmpty())
                <div class="st-row st-headrow">
                    <div>{{ $head['sr'] }}</div>
                    <div>{{ $head['label'] }} <span class="st-count">{{ $group->count() }}</span></div>
                    <div></div>
                    <div class="ws-money">{{ $cy ? $n($cy) : '' }}</div>
                    <div class="ws-money st-py">{{ $py ? $n($py) : '' }}</div>
                </div>
                <div class="st-detail" id="det-{{ $code }}">
                    @foreach($group as $line)
                        <div class="st-row st-line">
                            <div></div>
                            <div>{{ $line->description }}
                                @if($line->attributeSummary())<div class="ws-sub">{{ $line->attributeSummary() }}</div>@endif</div>
                            <div></div>
                            <div><input type="number" step="0.01" class="form-control form-control-sm num"
                                        name="amounts[{{ $line->id }}]" value="{{ $line->amountFor($taxYear) }}"
                                        aria-label="{{ $line->description }}"></div>
                            <div class="ws-money st-py">{{ $line->amountFor($taxYear - 1) === null ? '—' : $n($line->amountFor($taxYear - 1)) }}
                                <div class="st-acts">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="removeLine({{ $line->id }}, @json($line->description))"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="st-row st-sum st-strong">
                <div>19</div><div>Total Liabilities</div><div></div>
                <div class="ws-money">{{ $n($totals['current']['liabilities']) }}</div>
                <div class="ws-money st-py">{{ $n($totals['prior']['liabilities']) }}</div>
            </div>

            <div class="st-save"><button class="btn btn-sm btn-primary">Save figures</button></div>
        </form>

        {{-- ── Reconciliation, Sr. 20–26 ── --}}
        <form method="POST" action="{{ route('wealth.reconciliation.save', $client) }}">
            @csrf
            <input type="hidden" name="tax_year" value="{{ $taxYear }}">

            <div class="st-row st-sum st-strong">
                <div>20</div><div>Net Assets Current Year</div><div></div>
                <div class="ws-money">{{ $n($net) }}</div><div class="ws-money st-py"></div>
            </div>
            <div class="st-row">
                <div>21</div><div>Net Assets Previous Year</div><div></div>
                <div><input type="number" step="0.01" name="opening_wealth" class="form-control form-control-sm num"
                            value="{{ $recon->opening_wealth }}" aria-label="Net assets previous year"></div>
                <div class="ws-money st-py">{{ $n($totals['prior']['net']) }}</div>
            </div>
            <div class="st-row st-sum">
                <div>22</div><div>Increase / Decrease in Assets</div><div></div>
                <div class="ws-money">{{ $n($increase) }}</div><div class="ws-money st-py"></div>
            </div>

            <div class="st-row st-sum st-strong">
                <div>23</div><div>Inflows</div><div></div>
                <div class="ws-money">{{ $n($inflows) }}</div><div class="ws-money st-py"></div>
            </div>
            @foreach(['taxable' => ['i', '7031'], 'exempt' => ['ii', '7032'], 'final' => ['iii', '7033']] as $t => [$rn, $code])
                <div class="st-row st-line">
                    <div class="st-rn">{{ $rn }}</div>
                    <div>{{ FbrSchema::INFLOWS[$code] }}
                        <div class="ws-sub">From the income and salary workings</div></div>
                    <div></div>
                    <div class="ws-money">{{ $n($declared[$t]) }}</div><div class="ws-money st-py"></div>
                </div>
            @endforeach
            @foreach(['adjustments' => ['iv', '7034'], 'foreign_remittance' => ['v', '7035'], 'inheritance' => ['vi', '7036'],
                      'gift_received' => ['vii', '7037'], 'gain_disposal' => ['viii', '7038'], 'other_sources' => ['ix', '7048']] as $f => [$rn, $code])
                <div class="st-row st-line">
                    <div class="st-rn">{{ $rn }}</div>
                    <div>{{ FbrSchema::INFLOWS[$code] }}</div>
                    <div></div>
                    <div><input type="number" step="0.01" name="{{ $f }}" class="form-control form-control-sm num"
                                value="{{ $recon->{$f} ?: '' }}" aria-label="{{ FbrSchema::INFLOWS[$code] }}"></div>
                    <div class="ws-money st-py"></div>
                </div>
            @endforeach

            <div class="st-row st-sum st-strong">
                <div>24</div><div>Personal Expenses
                    <div class="ws-sub">From Annex-F@if($salaryDeductions > 0), including {{ $n($salaryDeductions) }} stopped at source@endif</div></div>
                <div></div>
                <div class="ws-money">{{ $n($expenseTotal) }}</div><div class="ws-money st-py"></div>
            </div>

            <div class="st-row st-sum st-strong">
                <div>25</div><div>Outflows</div><div></div>
                <div class="ws-money">{{ $n($outflows) }}</div><div class="ws-money st-py"></div>
            </div>
            @foreach(['gift_given' => ['i', '7091'], 'loss_disposal' => ['ii', '7092'], 'other_outflows' => ['iii', '7098']] as $f => [$rn, $code])
                <div class="st-row st-line">
                    <div class="st-rn">{{ $rn }}</div>
                    <div>{{ FbrSchema::OUTFLOWS[$code] }}</div>
                    <div></div>
                    <div><input type="number" step="0.01" name="{{ $f }}" class="form-control form-control-sm num"
                                value="{{ $recon->{$f} ?: '' }}" aria-label="{{ FbrSchema::OUTFLOWS[$code] }}"></div>
                    <div class="ws-money st-py"></div>
                </div>
            @endforeach

            <div class="st-row st-sum st-strong" style="background: {{ abs($unreconciled) < 1 ? 'var(--ok-tint)' : 'var(--danger-tint)' }};">
                <div>26</div>
                <div>Unreconciled Amount
                    <div class="ws-sub">7049 − 7089 − 7099 − 703003. Must be nil.</div></div>
                <div></div>
                <div class="ws-money">{{ $n($unreconciled) }}</div><div class="ws-money st-py"></div>
            </div>

            <div class="st-save d-flex gap-2 align-items-center">
                <button class="btn btn-sm btn-primary">Save reconciliation</button>
                <textarea name="notes" class="form-control form-control-sm" rows="1" placeholder="Notes" style="max-width: 420px;">{{ $recon->notes }}</textarea>
            </div>
        </form>
    </div>


{{-- Add a line. In a dialog so the statement below stays readable. --}}
<div class="modal fade" id="addLineModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('wealth.lines.store', $client) }}" id="addLineForm">
            @csrf
            <input type="hidden" name="tax_year" value="{{ $taxYear }}">
            <div class="modal-header">
                <h5 class="modal-title">Add a line to the statement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-5">
                        <label class="form-label" for="lineHead">Head</label>
                        <select name="code" id="lineHead" class="form-select form-select-sm" required onchange="showHeadFields(this.value)">
                            <optgroup label="Assets">
                                @foreach($assetHeads as $code => $h)<option value="{{ $code }}">{{ $h['sr'] }}. {{ $h['label'] }}</option>@endforeach
                            </optgroup>
                            <optgroup label="Liabilities">
                                @foreach($liabHeads as $code => $h)<option value="{{ $code }}">{{ $h['sr'] }}. {{ $h['label'] }}</option>@endforeach
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
                <p class="ws-sub mt-3 mb-0">Assets are declared at cost, including stamp duty, registration and transfer fees.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-accent">Add to statement</button>
            </div>
        </form>
    </div>
</div>

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
