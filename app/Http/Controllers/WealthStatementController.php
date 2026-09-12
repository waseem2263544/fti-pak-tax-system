<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\IncomeWorking;
use App\Models\WealthLine;
use App\Models\WealthReconciliation;
use App\Models\IncomeItem;
use App\Models\SalaryWorking;
use App\Models\WealthExpense;
use App\Models\WealthValue;
use App\Support\FbrSchema;
use Illuminate\Http\Request;

/**
 * Wealth statement and income tax working papers, per client and tax year.
 *
 * The app records and adds up what the preparer enters. It never computes tax:
 * tax chargeable is taken from IRIS by hand.
 */
class WealthStatementController extends Controller
{
    /** Pakistani tax years run July to June and are named for the year they end. */
    public static function currentTaxYear(): int
    {
        $now = now();

        return $now->month >= 7 ? $now->year + 1 : $now->year;
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));

        $clients = Client::query()
            ->when($search !== '', fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('wealthLines')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('wealth.index', compact('clients', 'search'));
    }

    public function show(Request $request, Client $client)
    {
        $taxYear = (int) $request->get('year', self::currentTaxYear());

        // Solve the balancing line before anything is read, so the statement and
        // the reconciliation on screen agree.
        $this->rebalance($client, $taxYear);

        $lines = WealthLine::with('values')
            ->where('client_id', $client->id)
            ->orderBy('code')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Every year this client has any figure for, so the comparative view
        // can offer them and the year picker is never empty.
        $years = $lines->pluck('values')->flatten()->pluck('tax_year')
            ->merge([$taxYear, self::currentTaxYear()])
            ->unique()->sortDesc()->values();

        $income = IncomeWorking::firstOrNew(['client_id' => $client->id, 'tax_year' => $taxYear]);

        // One source for every reconciliation figure, shared with rebalance().
        $f = $this->figures($client, $taxYear);

        $recon            = $f['recon'];
        $expenses         = $f['expenses'];
        $declared         = $f['declared'];
        $expenseTotal     = $f['expenseTotal'];
        $salary           = $f['salary'];
        $salaryDeductions = $f['salaryDeductions'];
        $salaryTax        = $salary->sum(fn($w) => $w->taxDeducted());
        $inflows          = $f['inflows'];
        $outflows         = $f['outflows'];

        $items = IncomeItem::with('wealthLine')
            ->where('client_id', $client->id)->where('tax_year', $taxYear)
            ->orderBy('head')->orderBy('sort_order')->orderBy('id')
            ->get()->groupBy('head');

        $totals = [
            'current' => $this->netWealth($lines, $taxYear),
            'prior'   => $this->netWealth($lines, $taxYear - 1),
        ];

        // Opening wealth defaults to last year's closing figure, but stays
        // editable: the first year on the system has no prior year to read.
        if ($recon->opening_wealth === null) {
            $recon->opening_wealth = $totals['prior']['net'];
        }

        return view('wealth.show', compact(
            'client', 'taxYear', 'years', 'lines', 'income', 'recon', 'totals',
            'items', 'expenses', 'declared', 'expenseTotal',
            'salary', 'salaryDeductions', 'salaryTax', 'inflows', 'outflows'
        ));
    }

    /**
     * Everything the reconciliation needs for a year, in one place.
     *
     * show() renders from this and rebalance() solves from it, so the figure
     * on screen and the figure written to the balancing line can never come
     * from two different sums.
     */
    private function figures(Client $client, int $taxYear): array
    {
        $items = IncomeItem::where('client_id', $client->id)->where('tax_year', $taxYear)->get();

        $salary = SalaryWorking::with('components.months')
            ->where('client_id', $client->id)->where('tax_year', $taxYear)
            ->orderBy('sort_order')->get();

        $declared = ['taxable' => 0.0, 'exempt' => 0.0, 'final' => 0.0];
        foreach ($items as $item) {
            $declared[$item->treatment] = ($declared[$item->treatment] ?? 0) + (float) $item->amount;
        }
        $declared['taxable'] += $salary->sum(fn($w) => $w->incomeBy('taxable'));
        $declared['exempt']  += $salary->sum(fn($w) => $w->incomeBy('exempt'));

        $expenses = WealthExpense::where('client_id', $client->id)
            ->where('tax_year', $taxYear)->pluck('amount', 'code');

        $salaryDeductions = $salary->sum(fn($w) => $w->totalDeductions());

        $expenseTotal = $expenses->except([FbrSchema::EXPENSE_CONTRA])->sum()
                      - (float) ($expenses[FbrSchema::EXPENSE_CONTRA] ?? 0)
                      + $salaryDeductions;

        $recon = WealthReconciliation::firstOrNew(['client_id' => $client->id, 'tax_year' => $taxYear]);

        $inflows = $declared['taxable'] + $declared['exempt'] + $declared['final']
                 + (float) $recon->adjustments + (float) $recon->foreign_remittance
                 + (float) $recon->inheritance + (float) $recon->gift_received
                 + (float) $recon->gain_disposal + (float) $recon->other_sources;

        $outflows = (float) $recon->gift_given + (float) $recon->loss_disposal + (float) $recon->other_outflows;

        return compact('items', 'salary', 'declared', 'expenses', 'expenseTotal',
                       'salaryDeductions', 'recon', 'inflows', 'outflows');
    }

    /**
     * Solve the balancing line so the year reconciles to nil.
     *
     * From 703000 = 7049 - 7089 - 7099 - 703003, with 703003 = net assets this
     * year less last:
     *
     *     cash = opening + inflows - expenses - outflows - other assets + liabilities
     *
     * Everything else on the statement is evidenced; the notes and coins are
     * whatever is left, which is what a preparer plugs by hand anyway.
     */
    private function rebalance(Client $client, int $taxYear): void
    {
        $balancing = WealthLine::where('client_id', $client->id)->where('balancing', true)->first();

        if (!$balancing) {
            return;
        }

        $f = $this->figures($client, $taxYear);

        // Nothing to solve against until an opening position exists.
        if ($f['recon']->opening_wealth === null) {
            return;
        }

        $lines = WealthLine::with('values')->where('client_id', $client->id)->get();

        $otherAssets = $lines->where('kind', 'asset')->where('id', '!=', $balancing->id)
            ->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));

        $liabilities = $lines->where('kind', 'liability')
            ->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));

        $cash = (float) $f['recon']->opening_wealth
              + $f['inflows'] - $f['expenseTotal'] - $f['outflows']
              - $otherAssets + $liabilities;

        WealthValue::updateOrCreate(
            ['wealth_line_id' => $balancing->id, 'tax_year' => $taxYear],
            ['amount' => round($cash, 2)]
        );
    }

    /** Assets, liabilities and net wealth for one year. */
    private function netWealth($lines, int $taxYear): array
    {
        $sum = fn(string $kind) => $lines->where('kind', $kind)
            ->sum(fn($l) => (float) ($l->amountFor($taxYear) ?? 0));

        $assets = $sum('asset');
        $liabilities = $sum('liability');

        return [
            'assets'      => $assets,
            'liabilities' => $liabilities,
            'net'         => $assets - $liabilities,
        ];
    }

    public function comparative(Request $request, Client $client)
    {
        $lines = WealthLine::with('values')
            ->where('client_id', $client->id)
            ->orderBy('section')->orderBy('sort_order')->orderBy('id')
            ->get();

        $years = $lines->pluck('values')->flatten()->pluck('tax_year')
            ->unique()->sortDesc()->take(6)->sort()->values();

        if ($years->isEmpty()) {
            $years = collect([self::currentTaxYear()]);
        }

        $totals = [];
        foreach ($years as $y) {
            $totals[$y] = $this->netWealth($lines, (int) $y);
        }

        return view('wealth.comparative', compact('client', 'lines', 'years', 'totals'));
    }

    public function storeLine(Request $request, Client $client)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:8',
            'description' => 'required|string|max:400',
            'tax_year'    => 'required|integer|min:2000|max:2100',
            'amount'      => 'nullable|numeric',
            'details'     => 'array',
        ]);

        $code = $validated['code'];
        $head = FbrSchema::head($code) ?? abort(404, 'Unknown head.');
        $kind = array_key_exists($code, FbrSchema::liabilityHeads()) ? 'liability' : 'asset';

        $line = WealthLine::create([
            'client_id'   => $client->id,
            'kind'        => $kind,
            'code'        => $code,
            'section'     => $code,
            'description' => $validated['description'],
            'details'     => $this->cleanDetails($head['fields'], $validated['details'] ?? []),
            'sort_order'  => (int) WealthLine::where('client_id', $client->id)
                                ->where('code', $code)->max('sort_order') + 1,
        ]);

        // Cash in hand is what a preparer plugs, so the first one becomes the
        // balancing line unless another already is.
        if ($code === '7012'
            && !WealthLine::where('client_id', $client->id)->where('balancing', true)->exists()) {
            $line->update(['balancing' => true]);
        }

        if ($validated['amount'] !== null && $validated['amount'] !== '') {
            WealthValue::create([
                'wealth_line_id' => $line->id,
                'tax_year'       => $validated['tax_year'],
                'amount'         => $validated['amount'],
            ]);
        }

        return back()->with('success', 'Added to the wealth statement.');
    }

    public function updateLine(Request $request, Client $client, WealthLine $line)
    {
        abort_unless($line->client_id === $client->id, 404);

        $validated = $request->validate([
            'description' => 'required|string|max:400',
            'code'        => 'required|string|max:8',
            'notes'       => 'nullable|string|max:500',
            'details'     => 'array',
        ]);

        $head = FbrSchema::head($validated['code']) ?? abort(404, 'Unknown head.');

        $line->update([
            'description' => $validated['description'],
            'code'        => $validated['code'],
            'section'     => $validated['code'],
            'kind'        => array_key_exists($validated['code'], FbrSchema::liabilityHeads()) ? 'liability' : 'asset',
            'notes'       => $validated['notes'] ?? null,
            'details'     => $this->cleanDetails($head['fields'], $validated['details'] ?? []),
        ]);

        return back()->with('success', 'Line updated.');
    }

    public function destroyLine(Client $client, WealthLine $line)
    {
        abort_unless($line->client_id === $client->id, 404);

        $line->delete();

        return back()->with('success', 'Line removed, along with its figures for every year.');
    }

    /** Save the whole year's column in one go. */
    public function saveValues(Request $request, Client $client)
    {
        $validated = $request->validate([
            'tax_year'   => 'required|integer|min:2000|max:2100',
            'amounts'    => 'array',
            'amounts.*'  => 'nullable|numeric',
        ]);

        $year = (int) $validated['tax_year'];
        $ownLines = WealthLine::where('client_id', $client->id)->pluck('id')->flip();

        foreach ($validated['amounts'] ?? [] as $lineId => $amount) {
            if (!$ownLines->has((int) $lineId)) {
                continue;
            }

            // A cleared box means "not declared this year", which is different
            // from a declared zero only in that it leaves no row behind.
            if ($amount === null || $amount === '') {
                WealthValue::where('wealth_line_id', $lineId)->where('tax_year', $year)->delete();
                continue;
            }

            WealthValue::updateOrCreate(
                ['wealth_line_id' => $lineId, 'tax_year' => $year],
                ['amount' => $amount]
            );
        }

        $this->rebalance($client, $year);

        return back()->with('success', "Figures saved for tax year {$year}.");
    }

    public function saveIncome(Request $request, Client $client)
    {
        $fields = array_merge(
            array_keys(IncomeWorking::HEADS),
            ['exempt_income', 'ftr_income', 'deductible_allowances',
             'tax_chargeable', 'tax_reductions_credits', 'tax_deducted', 'tax_paid']
        );

        $rules = ['tax_year' => 'required|integer|min:2000|max:2100', 'notes' => 'nullable|string'];
        foreach ($fields as $f) {
            $rules[$f] = 'nullable|numeric';
        }

        $validated = $request->validate($rules);

        $data = ['notes' => $validated['notes'] ?? null];
        foreach ($fields as $f) {
            $data[$f] = $validated[$f] ?? 0;
        }

        IncomeWorking::updateOrCreate(
            ['client_id' => $client->id, 'tax_year' => (int) $validated['tax_year']],
            $data
        );

        return back()->with('success', 'Income working saved.');
    }

    public function saveReconciliation(Request $request, Client $client)
    {
        $fields = array_merge(
            array_keys(WealthReconciliation::OUTFLOWS),
            array_keys(WealthReconciliation::SOURCES),
            ['opening_wealth']
        );

        $rules = ['tax_year' => 'required|integer|min:2000|max:2100', 'notes' => 'nullable|string'];
        foreach ($fields as $f) {
            $rules[$f] = 'nullable|numeric';
        }

        $validated = $request->validate($rules);

        $data = ['notes' => $validated['notes'] ?? null];
        foreach ($fields as $f) {
            $data[$f] = $validated[$f] ?? 0;
        }

        WealthReconciliation::updateOrCreate(
            ['client_id' => $client->id, 'tax_year' => (int) $validated['tax_year']],
            $data
        );

        $this->rebalance($client, (int) $validated['tax_year']);

        return back()->with('success', 'Reconciliation saved.');
    }

    public function storeIncomeItem(Request $request, Client $client)
    {
        $head = (string) $request->get('head');
        $schema = FbrSchema::incomeHeads()[$head] ?? abort(404, 'Unknown income head.');

        $validated = $request->validate([
            'head'           => 'required|string|max:30',
            'tax_year'       => 'required|integer|min:2000|max:2100',
            'description'    => 'nullable|string|max:400',
            'wealth_line_id' => 'nullable|integer',
            'treatment'      => 'required|in:taxable,exempt,final',
            'tax_deducted'   => 'nullable|numeric',
            'details'        => 'array',
        ]);

        // A linked asset has to belong to this client.
        $lineId = $validated['wealth_line_id'] ?? null;
        if ($lineId && !WealthLine::where('id', $lineId)->where('client_id', $client->id)->exists()) {
            $lineId = null;
        }

        $item = new IncomeItem([
            'client_id'      => $client->id,
            'tax_year'       => (int) $validated['tax_year'],
            'head'           => $head,
            'description'    => $validated['description'] ?? null,
            'wealth_line_id' => $lineId,
            'details'        => $this->cleanDetails($schema['fields'], $validated['details'] ?? []),
            'treatment'      => $validated['treatment'],
            'tax_deducted'   => $validated['tax_deducted'] ?? 0,
            'sort_order'     => (int) IncomeItem::where('client_id', $client->id)
                                    ->where('tax_year', $validated['tax_year'])
                                    ->where('head', $head)->max('sort_order') + 1,
        ]);
        $item->amount = $item->computeAmount();
        $item->save();

        return back()->with('success', $schema['label'] . ' line added.');
    }

    public function updateIncomeItem(Request $request, Client $client, IncomeItem $item)
    {
        abort_unless($item->client_id === $client->id, 404);

        $schema = FbrSchema::incomeHeads()[$item->head] ?? abort(404);

        $validated = $request->validate([
            'description'  => 'nullable|string|max:400',
            'treatment'    => 'required|in:taxable,exempt,final',
            'tax_deducted' => 'nullable|numeric',
            'details'      => 'array',
        ]);

        $item->fill([
            'description'  => $validated['description'] ?? null,
            'treatment'    => $validated['treatment'],
            'tax_deducted' => $validated['tax_deducted'] ?? 0,
            'details'      => $this->cleanDetails($schema['fields'], $validated['details'] ?? []),
        ]);
        $item->amount = $item->computeAmount();
        $item->save();

        return back()->with('success', 'Line updated.');
    }

    public function destroyIncomeItem(Client $client, IncomeItem $item)
    {
        abort_unless($item->client_id === $client->id, 404);

        $item->delete();

        return back()->with('success', 'Line removed.');
    }

    /** Keep only the keys the head actually defines, and drop empty ones. */
    private function cleanDetails(array $fields, array $input): array
    {
        $allowed = array_column($fields, 'key');
        $out = [];

        foreach ($allowed as $key) {
            $v = $input[$key] ?? null;
            if ($v !== null && $v !== '') {
                $out[$key] = is_numeric($v) ? (float) $v : $v;
            }
        }

        return $out;
    }

    public function saveExpenses(Request $request, Client $client)
    {
        $validated = $request->validate([
            'tax_year'   => 'required|integer|min:2000|max:2100',
            'expenses'   => 'array',
            'expenses.*' => 'nullable|numeric',
        ]);

        $year = (int) $validated['tax_year'];
        $codes = array_merge(array_keys(FbrSchema::EXPENSES), [FbrSchema::EXPENSE_CONTRA]);

        foreach ($codes as $code) {
            $amount = $validated['expenses'][$code] ?? null;

            if ($amount === null || $amount === '') {
                WealthExpense::where('client_id', $client->id)
                    ->where('tax_year', $year)->where('code', $code)->delete();
                continue;
            }

            WealthExpense::updateOrCreate(
                ['client_id' => $client->id, 'tax_year' => $year, 'code' => $code],
                ['amount' => $amount]
            );
        }

        $this->rebalance($client, $year);

        return back()->with('success', 'Annex-F saved.');
    }

    /** Choose which line carries the balancing figure. Only one can. */
    public function setBalancing(Request $request, Client $client, WealthLine $line)
    {
        abort_unless($line->client_id === $client->id, 404);

        WealthLine::where('client_id', $client->id)->update(['balancing' => false]);
        $line->update(['balancing' => true]);

        $this->rebalance($client, (int) $request->get('tax_year', self::currentTaxYear()));

        return back()->with('success', $line->description . ' now carries the balancing figure.');
    }

    /** Copy a year's figures forward, so next year starts from last year's closing position. */
    public function carryForward(Request $request, Client $client)
    {
        $validated = $request->validate([
            'from_year' => 'required|integer|min:2000|max:2100',
            'to_year'   => 'required|integer|min:2000|max:2100|different:from_year',
        ]);

        $from = (int) $validated['from_year'];
        $to   = (int) $validated['to_year'];

        $lines = WealthLine::with('values')->where('client_id', $client->id)->get();
        $copied = 0;

        foreach ($lines as $line) {
            $amount = $line->amountFor($from);
            if ($amount === null) {
                continue;
            }

            // Never overwrite a figure already entered for the target year.
            $exists = $line->values->contains(fn($v) => $v->tax_year === $to);
            if ($exists) {
                continue;
            }

            WealthValue::create([
                'wealth_line_id' => $line->id,
                'tax_year'       => $to,
                'amount'         => $amount,
            ]);
            $copied++;
        }

        return back()->with('success', "Carried {$copied} lines forward from {$from} to {$to}. Figures already entered for {$to} were left alone.");
    }
}
