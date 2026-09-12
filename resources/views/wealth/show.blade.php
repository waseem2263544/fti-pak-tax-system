@extends('layouts.app')
@section('title', $client->name . ' — Wealth Statement')
@section('page-title', $client->name)

@section('styles')
<style>
    /* ════════════════════════════════════════════════════════════════
       A working paper: ruled rows, one grid, figures in a clean column.
       ════════════════════════════════════════════════════════════════ */

    /* ── Sheets (income working, salary summary, Annex-F) ── */
    .ws-sheet { background: var(--surface); border: 1px solid var(--border);
                border-radius: var(--radius-lg); margin-bottom: 16px; }
    .ws-head  { padding: 13px 20px; border-bottom: 1px solid var(--border); display: flex;
                justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; }
    .ws-head h2 { font-size: 0.94rem; font-weight: 700; margin: 0; letter-spacing: -0.01em; }
    .ws-row   { display: grid; grid-template-columns: minmax(0, 1fr) 150px 150px 90px; gap: 14px;
                align-items: center; padding: 8px 20px; border-bottom: 1px solid var(--n-100);
                font-size: 0.84rem; }
    .ws-row:last-child { border-bottom: none; }
    .ws-group { padding: 8px 20px; background: var(--surface-sunk); border-bottom: 1px solid var(--border);
                font-size: 0.76rem; font-weight: 600; color: var(--text-soft);
                display: flex; justify-content: space-between; gap: 10px; }
    .ws-sub   { font-size: 0.74rem; color: var(--text-muted); }
    .ws-money { text-align: right; font-variant-numeric: tabular-nums; }
    .num      { font-variant-numeric: tabular-nums; }

    details.ws-add > summary { cursor: pointer; padding: 11px 20px; font-size: 0.82rem;
                               font-weight: 600; color: var(--accent-dark); list-style: none; }
    details.ws-add > summary::-webkit-details-marker { display: none; }
    details.ws-add > summary::before { content: '+ '; }
    details.ws-add[open] > summary::before { content: '− '; }
    details.ws-add > div { padding: 0 20px 18px; border-top: 1px solid var(--n-100); }

    /* ── The statement ───────────────────────────────────────────────
       One grid for every row: serial, particulars, this year, last year. */
    .st-doc { background: var(--surface); border: 1px solid var(--border);
              border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 16px; }

    .st-title { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
                padding: 16px 20px; border-bottom: 1px solid var(--border); flex-wrap: wrap; }
    .st-title h2 { font-size: 1rem; font-weight: 700; margin: 0; letter-spacing: -0.01em; }
    .st-title p  { margin: 3px 0 0; font-size: 0.78rem; color: var(--text-muted); }
    .st-title-sub { border-top: 8px solid var(--n-100); }
    .st-actions { display: flex; gap: 8px; align-items: center; }

    .st-cols, .st-r { display: grid; grid-template-columns: 46px minmax(0, 1fr) 158px 138px;
                      gap: 14px; align-items: center; padding: 7px 20px; }
    .st-cols { background: var(--surface-sunk); border-bottom: 1px solid var(--border);
               font-size: 0.7rem; font-weight: 600; color: var(--text-muted);
               text-transform: uppercase; letter-spacing: 0.5px; }
    .st-r    { border-bottom: 1px solid var(--n-100); font-size: 0.84rem; }

    .st-sr   { font-size: 0.75rem; color: var(--text-faint); font-variant-numeric: tabular-nums; }
    .st-rn   { text-align: right; padding-right: 4px; font-style: italic; }
    /* A boxed input's text sits inside a border and padding, so plain figures
       get the same offset and the whole column lines up on one edge. */
    .st-num  { text-align: right; font-variant-numeric: tabular-nums; padding-right: 9px; }
    .st-cell-in { padding-right: 0; }
    .st-prior { color: var(--text-muted); }

    .st-head  { background: var(--surface-sunk); font-weight: 600; }
    .st-sub   { font-weight: 600; }
    .st-total { font-weight: 700; border-top: 1px solid var(--border-strong); }
    .st-final { font-weight: 700; border-top: 1px solid var(--border-strong); }
    .st-final.ok  { background: var(--ok-tint);     color: var(--ok-ink); }
    .st-final.bad { background: var(--danger-tint); color: var(--danger-ink); }

    .st-l     { background: var(--surface); }
    .st-l .st-sr { color: transparent; }
    .st-desc  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; min-width: 0; }
    .st-name  { font-weight: 500; }
    .st-attrs { font-size: 0.73rem; color: var(--text-muted); }
    .st-from  { display: block; font-size: 0.73rem; color: var(--text-muted); font-weight: 400; }
    .st-tag   { font-size: 0.62rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;
                background: var(--n-100); color: var(--text-soft); border-radius: 3px; padding: 1px 5px; }
    .st-tag-b { background: var(--info-tint); color: var(--info-ink); }

    /* Row tools stay out of the way until the row is under the cursor. */
    .st-tools { display: inline-flex; gap: 2px; opacity: 0; transition: opacity 0.12s; margin-left: auto; }
    .st-r:hover .st-tools, .st-tools:focus-within { opacity: 1; }
    .st-tool  { border: none; background: transparent; color: var(--text-muted); cursor: pointer;
                padding: 2px 5px; border-radius: var(--radius-sm); font-size: 0.8rem; line-height: 1; }
    .st-tool:hover { background: var(--n-100); color: var(--text); }
    .st-tool-x:hover { background: var(--danger-tint); color: var(--danger-ink); }

    /* One input style, right-aligned, so the figures form a clean column. */
    .st-in { width: 100%; text-align: right; font-variant-numeric: tabular-nums;
             border: 1px solid var(--border-strong); border-radius: var(--radius-sm);
             padding: 4px 8px; font-size: 0.83rem; background: var(--surface); color: var(--text);
             font-family: inherit; outline: none; }
    .st-in:hover { border-color: var(--n-300); }
    .st-in:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
    .st-in.dirty { border-color: var(--accent); }
    .st-derived  { font-weight: 600; border-bottom: 1px dotted var(--text-faint); cursor: help; }

    .st-status { font-size: 0.76rem; color: var(--text-muted); min-width: 92px; text-align: right; }
    .st-status.saving { color: var(--text-soft); }
    .st-status.saved  { color: var(--ok-ink); }
    .st-status.failed { color: var(--danger-ink); font-weight: 600; }

    /* ── The working behind a line ── */
    .st-mv-box { display: none; background: var(--n-25); border-bottom: 1px solid var(--n-100);
                 padding: 0 20px 14px 60px; }
    .st-mv-box.open { display: block; }
    .st-mv-t { font-size: 0.68rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px;
               color: var(--text-muted); padding: 11px 0 6px; }
    .st-mv   { display: grid; grid-template-columns: minmax(0, 1fr) 158px 34px; gap: 14px;
               align-items: center; padding: 5px 0; font-size: 0.82rem;
               border-bottom: 1px solid var(--n-100); }
    .st-mv-edge { font-weight: 600; color: var(--text-soft); }
    .st-in-amt { color: var(--ok-ink); }
    .st-out    { color: var(--danger-ink); }
    .st-mv-add { display: grid; grid-template-columns: 108px minmax(0, 1fr) 140px 130px 68px;
                 gap: 8px; align-items: center; padding-top: 11px; }

    .st-notes { padding: 14px 20px; }

    /* The three ways an asset leaves the statement. */
    .rm-choice { display: flex; flex-direction: column; gap: 8px; margin-bottom: 4px; }
    .rm-choice label { display: flex; gap: 10px; align-items: flex-start; cursor: pointer;
                       border: 1px solid var(--border); border-radius: var(--radius);
                       padding: 10px 12px; transition: border-color 0.12s, background 0.12s; }
    .rm-choice label:hover { background: var(--n-25); }
    .rm-choice label:has(input:checked) { border-color: var(--accent); background: var(--accent-glow); }
    .rm-choice input { margin-top: 3px; }
    .rm-choice strong { display: block; font-size: 0.86rem; font-weight: 600; }
    .rm-choice em { display: block; font-style: normal; font-size: 0.76rem; color: var(--text-muted); margin-top: 2px; }
    .rm-fields { margin-top: 14px; }

    /* Annex-F rides the same rows, two columns wide. */
    .st-cols-2, .st-r-2 { grid-template-columns: minmax(0, 1fr) 158px 34px; }

    @media (max-width: 900px) {
        .st-cols, .st-r { grid-template-columns: 34px minmax(0, 1fr) 110px 96px; gap: 8px; padding: 7px 12px; }
        .st-mv-add { grid-template-columns: 1fr 1fr; }
        .st-mv-box { padding-left: 20px; }
    }
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
    // Values come back from the database as '2450000.00'; an input should show
    // the rupees and nothing else.
    $int = fn($v) => ($v === null || $v === '') ? '' : (string) (int) round((float) $v);
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
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-recon" type="button">Reconciliation</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-expenses" type="button">Personal Expenses</button></li>
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
                <p>{{ $client->name }} · as at 30 June {{ $taxYear }} · figures in PKR, at cost.
                   Each figure is the running total of that line's additions and disposals.</p>
            </div>
            <div class="st-actions">
                <button type="button" class="btn btn-sm btn-accent" data-bs-toggle="modal" data-bs-target="#addLineModal">
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

        <div class="st-body">

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
        </div>
    </div>
</div>

{{-- ════════ RECONCILIATION ════════ --}}
<div class="tab-pane fade" id="t-recon">
    <div class="st-doc">
        <header class="st-title">
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
                <div class="st-num st-cell-in"><input type="number" step="1" name="opening_wealth" class="st-in"
                                           value="{{ $int($recon->opening_wealth) }}" aria-label="Net assets previous year"></div>
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
                    <div class="st-num st-cell-in"><input type="number" step="1" name="{{ $f }}" class="st-in"
                                               value="{{ $int($recon->{$f}) }}" aria-label="{{ FbrSchema::INFLOWS[$code] }}"></div>
                    <div class="st-num st-prior"></div>
                </div>
            @endforeach

            <div class="st-r st-head">
                <div class="st-sr">24</div>
                <div>Personal expenses<span class="st-from">from the personal expenses tab@if($salaryDeductions > 0), including {{ $n($salaryDeductions) }} stopped at source@endif</span></div>
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
                    <div class="st-num st-cell-in"><input type="number" step="1" name="{{ $f }}" class="st-in"
                                               value="{{ $int($recon->{$f}) }}" aria-label="{{ FbrSchema::OUTFLOWS[$code] }}"></div>
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
                    <h2>Personal Expenses</h2>
                    <p>Annexure-F · feeds Sr. 24 of the reconciliation</p>
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
                                value="{{ $int($expenses[$code] ?? null) }}" aria-label="{{ $label }}" @if(!$shown) disabled @endif></div>
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
            <div class="st-r st-r-2" style="background: var(--warn-tint);">
                <div>{{ FbrSchema::EXPENSE_CONTRA_LABEL }}</div>
                <div><input type="number" step="1" name="expenses[{{ FbrSchema::EXPENSE_CONTRA }}]"
                            class="st-in"
                            value="{{ $int($expenses[FbrSchema::EXPENSE_CONTRA] ?? null) }}"
                            aria-label="{{ FbrSchema::EXPENSE_CONTRA_LABEL }}"></div>
                <div></div>
            </div>
            @if($salaryDeductions > 0)
                <div class="st-r st-r-2 st-head"><div>From the salary working</div><div></div><div></div></div>
                @foreach($salary as $w)
                    @foreach($w->deductions() as $c)
                        @continue($c->amount() == 0)
                        <div class="st-r st-r-2" >
                            <div>{{ $c->label }} <span class="st-attrs">— {{ $w->employer }}</span></div>
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


{{-- Add a line. Its value at cost becomes the line's opening movement, so every
     figure on the statement traces back to something that happened. --}}
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
                    <div class="col-md-6">
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
                    <div class="col-md-6">
                        <label class="form-label" for="lineDesc">Description</label>
                        <input type="text" name="description" id="lineDesc" class="form-control form-control-sm" required
                               placeholder="e.g. 1 Kanal Plot 59/E-1, Hayatabad">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="lineAmt">Value at cost</label>
                        <input type="number" step="1" name="amount" id="lineAmt"
                               class="form-control form-control-sm" style="text-align: right;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="lineWhen">Date acquired</label>
                        <input type="date" name="acquired_on" id="lineWhen" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="lineNote">How it was acquired</label>
                        <input type="text" name="acquired_note" id="lineNote" class="form-control form-control-sm"
                               placeholder="e.g. Purchased — sale deed dated 14 May 2025">
                    </div>
                </div>

                @foreach($assetHeads + $liabHeads as $code => $h)
                    <div class="row g-2 head-fields" data-code="{{ $code }}" style="display: none;">
                        @include('wealth.partials._fields', ['fields' => $h['fields'], 'values' => [], 'name' => 'details'])
                    </div>
                @endforeach

                <p class="ws-sub mt-3 mb-0">
                    Assets are declared at cost, including stamp duty, registration and transfer fees. This
                    becomes the line's opening entry; later changes — construction, a further instalment, a
                    part disposal — are recorded against the line as movements.
                </p>
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

/* Open the working behind a line: opening cost, what moved, closing. */
function toggleMoves(lineId) {
    var el = document.getElementById('mv-' + lineId);
    if (el) { el.classList.toggle('open'); }
}

/*
 * Forms marked data-autosave save themselves when a field is left, and the
 * pane is then re-read from the server so every derived figure - the totals,
 * the balancing line, the unreconciled amount - is the server's answer rather
 * than a guess made in the browser.
 */
(function () {
    var TOKEN = '{{ csrf_token() }}';
    var timers = {};

    function status(form, text, cls) {
        var pane = form.closest('.tab-pane') || document;
        pane.querySelectorAll('[data-status]').forEach(function (el) {
            el.textContent = text;
            el.className = 'st-status ' + (cls || '');
        });
    }

    function refreshPane(paneId) {
        if (!paneId) { return Promise.resolve(); }

        return fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var doc   = new DOMParser().parseFromString(html, 'text/html');
                var fresh = doc.getElementById(paneId);
                var live  = document.getElementById(paneId);
                if (fresh && live) { live.innerHTML = fresh.innerHTML; }
            });
    }

    function save(form) {
        var pane   = form.closest('.tab-pane');
        var paneId = pane ? pane.id : null;
        status(form, 'Saving…', 'saving');

        return fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-CSRF-TOKEN': TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(function (r) {
            if (!r.ok) { throw new Error(r.status); }
            return refreshPane(paneId);
        }).then(function () {
            status(form, 'Saved', 'saved');
        }).catch(function () {
            status(form, 'Not saved', 'failed');
        });
    }

    document.addEventListener('change', function (e) {
        var form = e.target.closest ? e.target.closest('form[data-autosave]') : null;
        if (!form) { return; }
        e.target.classList.add('dirty');
        var key = form.id || form.action;
        clearTimeout(timers[key]);
        timers[key] = setTimeout(function () { save(form); }, 400);
    });

    // These forms are never submitted the ordinary way; Enter saves instead.
    document.addEventListener('submit', function (e) {
        if (!e.target.matches || !e.target.matches('form[data-autosave]')) { return; }
        e.preventDefault();
        save(e.target);
    });
})();

/*
 * Removing an asset is three different events, and each asks for different
 * things. Hidden fields are disabled so a mode's inputs never reach the server
 * when another mode was chosen.
 */
function rmMode(lineId) {
    var dialog = document.getElementById('rm' + lineId);
    if (!dialog) { return; }

    var mode = dialog.querySelector('input[name=mode]:checked');
    mode = mode ? mode.value : 'error';

    var relativeBox = dialog.querySelector('input[name=relative]');
    var toRelative  = relativeBox ? relativeBox.checked : true;

    dialog.querySelectorAll('.rm-fields').forEach(function (box) {
        var on = box.dataset.for.split(' ').indexOf(mode) >= 0;
        box.hidden = !on;
        box.querySelectorAll('input, select').forEach(function (i) {
            if (i.name === 'relative') { return; }
            i.disabled = !on;
        });
    });

    // Fair market value only matters for a gift to someone who is not a relative.
    var fmv = document.getElementById('rmfmv' + lineId);
    if (fmv) {
        var show = (mode === 'gift') && !toRelative;
        fmv.hidden = !show;
        fmv.querySelectorAll('input').forEach(function (i) { i.disabled = !show; });
    }

    var note = document.getElementById('rmnote' + lineId);
    if (note) {
        note.textContent =
            mode === 'error' ? 'The line will simply be removed. Nothing is posted anywhere else.'
          : mode === 'sale'  ? 'The cost leaves the statement as a disposal, and the gain or loss goes to capital gains in the income working.'
          : toRelative       ? 'The cost leaves the statement and appears as a gift given under outflows on the reconciliation.'
                             : 'The cost leaves the statement, and the fair market value less cost goes to capital gains in the income working.';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[id^=rm]').forEach(function (el) {
        var m = el.id.match(/^rm(\d+)$/);
        if (m) { rmMode(m[1]); }
    });
});

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
