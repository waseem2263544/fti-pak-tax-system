<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\IncomeWorking;
use App\Models\WealthLine;
use App\Models\WealthReconciliation;
use App\Models\WealthValue;
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

        $lines = WealthLine::with('values')
            ->where('client_id', $client->id)
            ->orderBy('section')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Every year this client has any figure for, so the comparative view
        // can offer them and the year picker is never empty.
        $years = $lines->pluck('values')->flatten()->pluck('tax_year')
            ->merge([$taxYear, self::currentTaxYear()])
            ->unique()->sortDesc()->values();

        $income = IncomeWorking::firstOrNew(['client_id' => $client->id, 'tax_year' => $taxYear]);
        $recon  = WealthReconciliation::firstOrNew(['client_id' => $client->id, 'tax_year' => $taxYear]);

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
            'client', 'taxYear', 'years', 'lines', 'income', 'recon', 'totals'
        ));
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
            'kind'        => 'required|in:asset,liability',
            'section'     => 'required|string|max:40',
            'description' => 'required|string|max:400',
            'tax_year'    => 'required|integer|min:2000|max:2100',
            'amount'      => 'nullable|numeric',
        ]);

        $line = WealthLine::create([
            'client_id'   => $client->id,
            'kind'        => $validated['kind'],
            'section'     => $validated['section'],
            'description' => $validated['description'],
            'sort_order'  => (int) WealthLine::where('client_id', $client->id)
                                ->where('section', $validated['section'])->max('sort_order') + 1,
        ]);

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
            'section'     => 'required|string|max:40',
            'kind'        => 'required|in:asset,liability',
            'notes'       => 'nullable|string|max:500',
        ]);

        $line->update($validated);

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

        return back()->with('success', 'Reconciliation saved.');
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
