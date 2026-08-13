<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Models\WhtSection;
use App\Models\WhtTaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The global withholding rate matrix.
 *
 * Rates are statutory and therefore not scoped to a withholding agent — one
 * matrix serves every company in the module. Each row is valid for a range of
 * tax period months, so a Finance Act change in July does not rewrite what
 * earlier months were computed at.
 */
class WhtTaxRateController extends Controller
{
    // Admin-only; enforced on the route group in routes/web.php.

    public function index(Request $request)
    {
        $query = WhtTaxRate::query();

        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('atl_status')) {
            $query->where('atl_status', $request->atl_status);
        }

        // "In force during" — the month the user is asking about.
        if ($request->filled('month')) {
            $query->inForce($request->month . '-01');
        }

        $rates = $query->orderBy('section')
            ->orderBy('category')
            ->orderBy('atl_status')
            ->orderByDesc('effective_from')
            ->paginate(50)
            ->withQueryString();

        $sections = WhtSection::active()->for('purchase')->orderBy('section')->get()->unique('section');

        return view('wht.rates.index', compact('rates', 'sections'));
    }

    public function create()
    {
        $sections = WhtSection::active()->for('purchase')->orderBy('section')->get()->unique('section');

        return view('wht.rates.create', compact('sections'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        WhtTaxRate::create($validated);

        return redirect()->route('wht.rates.index')
            ->with('success', 'Rate added.');
    }

    public function edit(WhtTaxRate $rate)
    {
        $sections = WhtSection::active()->for('purchase')->orderBy('section')->get()->unique('section');

        return view('wht.rates.edit', compact('rate', 'sections'));
    }

    public function update(Request $request, WhtTaxRate $rate)
    {
        $rate->update($this->validated($request, $rate));

        return redirect()->route('wht.rates.index')
            ->with('success', 'Rate updated.');
    }

    public function destroy(WhtTaxRate $rate)
    {
        // Transactions snapshot their rate, so deleting a rule never changes a
        // recorded figure — but it does break the audit link back to the rule.
        $inUse = $rate->purchases()->exists();

        $rate->delete();

        return redirect()->route('wht.rates.index')
            ->with('success', $inUse
                ? 'Rate deleted. Existing transactions keep the rate they were saved with.'
                : 'Rate deleted.');
    }

    /**
     * Close off the current row and open a new one from a given month — the
     * normal way to record a Finance Act change without losing history.
     */
    public function supersede(Request $request, WhtTaxRate $rate)
    {
        $validated = $request->validate([
            'rate'  => 'required|numeric|min:0|max:100',
            'month' => 'required|date_format:Y-m',
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $validated['month'] . '-01')->startOfMonth();

        if ($from->lessThanOrEqualTo($rate->effective_from)) {
            return back()->with('error', 'The new rate must start after the month the current rate began.');
        }

        $rate->update(['effective_to' => $from->copy()->subMonth()->startOfMonth()]);

        WhtTaxRate::create([
            'section'        => $rate->section,
            'goods_type'     => $rate->goods_type,
            'category'       => $rate->category,
            'atl_status'     => $rate->atl_status,
            'rate'           => $validated['rate'],
            'effective_from' => $from,
            'effective_to'   => null,
            'notes'          => 'Superseded rate ' . $rate->rate . '%',
        ]);

        return redirect()->route('wht.rates.index')
            ->with('success', "New rate effective from {$from->format('M Y')}.");
    }

    private function validated(Request $request, ?WhtTaxRate $rate = null): array
    {
        $data = $request->validate([
            'section'        => 'required|string|max:50',
            'goods_type'     => 'nullable|string|max:255',
            'category'       => 'required|in:company,individual,aop',
            'atl_status'     => 'required|in:filer,non-filer',
            'rate'           => 'required|numeric|min:0|max:100',
            'effective_from' => 'required|date_format:Y-m',
            'effective_to'   => 'nullable|date_format:Y-m',
            'notes'          => 'nullable|string|max:255',
        ]);

        $data['goods_type']     = $data['goods_type'] ?? '';
        $data['effective_from'] = Carbon::createFromFormat('Y-m-d', $data['effective_from'] . '-01')->startOfMonth();
        $data['effective_to']   = $data['effective_to']
            ? Carbon::createFromFormat('Y-m-d', $data['effective_to'] . '-01')->startOfMonth()
            : null;

        if ($data['effective_to'] && $data['effective_to']->lessThan($data['effective_from'])) {
            abort(422, 'The end month cannot be before the start month.');
        }

        return $data;
    }
}
