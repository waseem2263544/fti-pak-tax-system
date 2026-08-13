<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtSalary;
use App\Models\WhtSection;
use App\Services\Wht\WhtCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WhtSalaryController extends Controller
{
    use ResolvesWhtCompany;

    public function __construct(private WhtCalculator $calculator)
    {
    }

    public function index(Request $request)
    {
        $company = $this->currentCompany();

        $query = $company->salaries()->with('employee');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('psid_no', 'like', "%{$search}%")
                ->orWhere('cpr_no', 'like', "%{$search}%")
                ->orWhereHas('employee', fn($p) => $p->where('name', 'like', "%{$search}%")));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('from')) {
            $query->whereDate('salary_month', '>=', $request->from . '-01');
        }

        if ($request->filled('to')) {
            $query->whereDate('salary_month', '<=', Carbon::parse($request->to . '-01')->endOfMonth());
        }

        if ($request->filled('deposited')) {
            $request->deposited === 'yes'
                ? $query->whereNotNull('cpr_no')->where('cpr_no', '!=', '')
                : $query->where(fn($q) => $q->whereNull('cpr_no')->orWhere('cpr_no', ''));
        }

        $totals = (clone $query)->selectRaw('SUM(total_salary) g, SUM(tax_deducted) t, SUM(final_net_payment) n')->first();

        $salaries = $query->orderByDesc('salary_month')->orderByDesc('id')->paginate(50)->withQueryString();
        $employees = $company->employees()->active()->orderBy('name')->get();

        return view('wht.salaries.index', compact('company', 'salaries', 'employees', 'totals'));
    }

    public function create()
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('create', $company);

        return view('wht.salaries.form', [
            'company'   => $company,
            'salary'    => new WhtSalary(['salary_month' => now()->startOfMonth(), 'payment_date' => now()]),
            'employees' => $company->employees()->active()->orderBy('name')->get(),
            'sections'  => WhtSection::active()->for('salary')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('create', $company);

        $salary = new WhtSalary(['wht_company_id' => $company->id, 'created_by' => auth()->id()]);
        $this->fill($salary, $request, $company);
        $salary->save();

        return redirect()->route('wht.salaries.index')
            ->with('success', 'Salary recorded.');
    }

    public function edit(WhtSalary $salary)
    {
        $company = $this->currentCompany();
        abort_unless($salary->wht_company_id === $company->id, 404);
        $this->authorizeAbility('edit', $company);

        return view('wht.salaries.form', [
            'company'   => $company,
            'salary'    => $salary,
            'employees' => $company->employees()->active()->orderBy('name')->get(),
            'sections'  => WhtSection::active()->for('salary')->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, WhtSalary $salary)
    {
        $company = $this->currentCompany();
        abort_unless($salary->wht_company_id === $company->id, 404);
        $this->authorizeAbility('edit', $company);

        $this->fill($salary, $request, $company);
        $salary->save();

        return redirect()->route('wht.salaries.index')
            ->with('success', 'Salary updated.');
    }

    public function destroy(WhtSalary $salary)
    {
        $company = $this->currentCompany();
        abort_unless($salary->wht_company_id === $company->id, 404);
        $this->authorizeAbility('delete', $company);

        $salary->delete();

        return back()->with('success', 'Salary deleted.');
    }

    public function preview(Request $request)
    {
        $company = $this->currentCompany();

        $validated = $request->validate([
            'employee_id'  => 'nullable|integer',
            'salary_month' => 'required|date_format:Y-m',
            'amount'       => 'nullable|numeric',
            'calc_mode'    => 'nullable|in:gross,net',
            'exempt_rate'  => 'nullable|numeric|min:0|max:100',
        ]);

        $employee = $validated['employee_id']
            ? $company->parties()->find($validated['employee_id'])
            : null;

        $result = $this->calculator->salary([
            'employee'     => $employee,
            'salary_month' => $validated['salary_month'] . '-01',
            'amount'       => $validated['amount'] ?? 0,
            'calc_mode'    => $validated['calc_mode'] ?? 'gross',
            'exempt_rate'  => $validated['exempt_rate'] ?? null,
        ]);

        return response()->json($result);
    }

    private function fill(WhtSalary $salary, Request $request, $company): void
    {
        $validated = $request->validate([
            'employee_id'  => 'required|integer',
            'salary_month' => 'required|date_format:Y-m',
            'payment_date' => 'required|date',
            'section'      => 'nullable|string|max:50',
            'calc_mode'    => 'required|in:gross,net',
            'amount'       => 'required|numeric|min:0',
            'exempt_rate'  => 'nullable|numeric|min:0|max:100',
            'cpr_no'       => 'nullable|string|max:50',
            'cpr_date'     => 'nullable|date',
            'psid_no'      => 'nullable|string|max:50',
            'challan_date' => 'nullable|date',
        ]);

        $employee = $company->parties()->findOrFail($validated['employee_id']);

        $result = $this->calculator->salary([
            'employee'     => $employee,
            'salary_month' => $validated['salary_month'] . '-01',
            'amount'       => $validated['amount'],
            'calc_mode'    => $validated['calc_mode'],
            'exempt_rate'  => $validated['exempt_rate'] ?? null,
        ]);

        $salary->fill([
            'employee_id'  => $employee->id,
            'salary_month' => Carbon::createFromFormat('Y-m-d', $validated['salary_month'] . '-01')->startOfMonth(),
            'payment_date' => $validated['payment_date'],
            'section'      => $validated['section'] ?? null,
            'calc_mode'    => $validated['calc_mode'],
            'input_amount' => $validated['amount'],
            'cpr_no'       => $validated['cpr_no'] ?? null,
            'cpr_date'     => $validated['cpr_date'] ?? null,
            'psid_no'      => $validated['psid_no'] ?? null,
            'challan_date' => $validated['challan_date'] ?? null,
        ] + $result);
    }
}
