<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Models\WhtSalarySlab;
use App\Services\Wht\WhtCalculator;
use Illuminate\Http\Request;

/**
 * Global salary tax slabs, one set per tax year. Statutory, so not scoped to a
 * withholding agent.
 */
class WhtSalarySlabController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->user()?->hasRole('admin'), 403, 'Only administrators can manage salary slabs.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $years = WhtSalarySlab::query()
            ->select('tax_year')
            ->distinct()
            ->orderByDesc('tax_year')
            ->pluck('tax_year');

        $taxYear = (int) ($request->tax_year ?: $years->first() ?: WhtCalculator::taxYear(now()));

        $slabs = WhtSalarySlab::forYear($taxYear)->get();

        return view('wht.slabs.index', compact('slabs', 'years', 'taxYear'));
    }

    public function store(Request $request)
    {
        WhtSalarySlab::create($this->validated($request));

        return back()->with('success', 'Slab added.');
    }

    public function update(Request $request, WhtSalarySlab $slab)
    {
        $slab->update($this->validated($request));

        return back()->with('success', 'Slab updated.');
    }

    public function destroy(WhtSalarySlab $slab)
    {
        $slab->delete();

        return back()->with('success', 'Slab deleted.');
    }

    /**
     * Copy a whole year's slabs to another year — most years only tweak a couple
     * of numbers, so this beats re-keying seven rows.
     */
    public function copyYear(Request $request)
    {
        $validated = $request->validate([
            'from_year' => 'required|integer|min:2000|max:2100',
            'to_year'   => 'required|integer|min:2000|max:2100|different:from_year',
        ]);

        if (WhtSalarySlab::where('tax_year', $validated['to_year'])->exists()) {
            return back()->with('error', "Tax year {$validated['to_year']} already has slabs. Delete them first.");
        }

        $source = WhtSalarySlab::forYear($validated['from_year'])->get();

        if ($source->isEmpty()) {
            return back()->with('error', "Tax year {$validated['from_year']} has no slabs to copy.");
        }

        foreach ($source as $slab) {
            WhtSalarySlab::create([
                'tax_year'   => $validated['to_year'],
                'min_salary' => $slab->min_salary,
                'max_salary' => $slab->max_salary,
                'fixed_tax'  => $slab->fixed_tax,
                'tax_rate'   => $slab->tax_rate,
            ]);
        }

        return redirect()->route('wht.slabs.index', ['tax_year' => $validated['to_year']])
            ->with('success', "Copied {$source->count()} slabs to tax year {$validated['to_year']}.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tax_year'   => 'required|integer|min:2000|max:2100',
            'min_salary' => 'required|numeric|min:0',
            'max_salary' => 'nullable|numeric|gt:min_salary',
            'fixed_tax'  => 'required|numeric|min:0',
            'tax_rate'   => 'required|numeric|min:0|max:100',
        ]);
    }
}
