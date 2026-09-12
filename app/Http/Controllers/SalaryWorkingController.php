<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SalaryComponent;
use App\Models\SalaryMonth;
use App\Models\SalaryWorking;
use Illuminate\Http\Request;

/**
 * The salary working: employers, their income and deduction heads, and the
 * figures for each - annually or month by month.
 *
 * Nothing here computes tax. Tax deducted is a head the preparer fills in from
 * the salary certificate.
 */
class SalaryWorkingController extends Controller
{
    public function index(Request $request, Client $client)
    {
        $taxYear = (int) $request->get('year', WealthStatementController::currentTaxYear());

        $workings = SalaryWorking::with('components.months')
            ->where('client_id', $client->id)
            ->where('tax_year', $taxYear)
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return view('wealth.salary', compact('client', 'taxYear', 'workings'));
    }

    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'tax_year' => 'required|integer|min:2000|max:2100',
            'employer' => 'required|string|max:255',
            'basis'    => 'required|in:annual,monthly',
        ]);

        $working = SalaryWorking::create([
            'client_id'  => $client->id,
            'tax_year'   => (int) $validated['tax_year'],
            'employer'   => $validated['employer'],
            'basis'      => $validated['basis'],
            'sort_order' => (int) SalaryWorking::where('client_id', $client->id)
                                ->where('tax_year', $validated['tax_year'])->max('sort_order') + 1,
        ]);

        $working->seedDefaults();

        return back()->with('success', 'Salary working added for ' . $working->employer . '.');
    }

    public function update(Request $request, Client $client, SalaryWorking $working)
    {
        abort_unless($working->client_id === $client->id, 404);

        $validated = $request->validate([
            'employer' => 'required|string|max:255',
            'basis'    => 'required|in:annual,monthly',
            'notes'    => 'nullable|string',
        ]);

        $working->update($validated);

        return back()->with('success', 'Salary working updated.');
    }

    public function destroy(Client $client, SalaryWorking $working)
    {
        abort_unless($working->client_id === $client->id, 404);

        $working->delete();

        return back()->with('success', 'Salary working removed.');
    }

    public function addComponent(Request $request, Client $client, SalaryWorking $working)
    {
        abort_unless($working->client_id === $client->id, 404);

        $validated = $request->validate([
            'side'      => 'required|in:income,deduction',
            'label'     => 'required|string|max:200',
            'treatment' => 'nullable|in:taxable,exempt',
        ]);

        $working->components()->create([
            'side'       => $validated['side'],
            'label'      => $validated['label'],
            // Only income is split by treatment; a deduction is money gone.
            'treatment'  => $validated['side'] === 'income' ? ($validated['treatment'] ?? 'taxable') : null,
            'sort_order' => (int) $working->components()->where('side', $validated['side'])->max('sort_order') + 1,
        ]);

        return back()->with('success', 'Head added.');
    }

    public function destroyComponent(Client $client, SalaryWorking $working, SalaryComponent $component)
    {
        abort_unless($working->client_id === $client->id && $component->salary_working_id === $working->id, 404);

        if ($component->locked) {
            return back()->with('error', 'Basic salary and tax deducted are part of every salary working and cannot be removed.');
        }

        $component->delete();

        return back()->with('success', 'Head removed.');
    }

    /** Save the whole working in one go: labels, treatments and every figure. */
    public function save(Request $request, Client $client, SalaryWorking $working)
    {
        abort_unless($working->client_id === $client->id, 404);

        $validated = $request->validate([
            'basis'           => 'required|in:annual,monthly',
            'labels'          => 'array',
            'labels.*'        => 'nullable|string|max:200',
            'treatments'      => 'array',
            'treatments.*'    => 'nullable|in:taxable,exempt',
            'annual'          => 'array',
            'annual.*'        => 'nullable|numeric',
            'months'          => 'array',
        ]);

        $working->update(['basis' => $validated['basis']]);

        $components = $working->components()->get()->keyBy('id');

        foreach ($components as $id => $component) {
            $changes = [];

            // A locked head keeps its name; the rest can be renamed in place.
            $label = $validated['labels'][$id] ?? null;
            if (!$component->locked && $label !== null && trim($label) !== '') {
                $changes['label'] = trim($label);
            }

            if ($component->side === 'income') {
                $t = $validated['treatments'][$id] ?? null;
                if ($t !== null) {
                    $changes['treatment'] = $t;
                }
            }

            $annual = $validated['annual'][$id] ?? null;
            $changes['annual_amount'] = ($annual === null || $annual === '') ? 0 : $annual;

            $component->update($changes);

            foreach ($validated['months'][$id] ?? [] as $month => $amount) {
                $month = (int) $month;
                if ($month < 1 || $month > 12) {
                    continue;
                }

                if ($amount === null || $amount === '') {
                    SalaryMonth::where('salary_component_id', $component->id)->where('month', $month)->delete();
                    continue;
                }

                SalaryMonth::updateOrCreate(
                    ['salary_component_id' => $component->id, 'month' => $month],
                    ['amount' => $amount]
                );
            }
        }

        return back()->with('success', 'Salary working saved.');
    }
}
