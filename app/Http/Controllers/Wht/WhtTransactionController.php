<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtSection;
use App\Models\WhtPsidRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Transactions — vendor payments and salaries on one page.
 *
 * They were two menu entries and two screens, but they are the same thing with
 * different columns: money paid, tax withheld, a period it belongs to. One page
 * with a kind switch halves the navigation and puts adding, importing and
 * certificates in the same place as the list.
 */
class WhtTransactionController extends Controller
{
    use ResolvesWhtCompany;

    public function index(Request $request)
    {
        $company = $this->currentCompany();

        $kind = $request->get('kind') === 'salaries' ? 'salaries' : 'purchases';
        $isSalary = $kind === 'salaries';

        $query = $isSalary
            ? $company->salaries()->with('employee')
            : $company->purchases()->with('party');

        $periodColumn = $isSalary ? 'salary_month' : 'period_month';
        $partyColumn = $isSalary ? 'employee_id' : 'party_id';
        $relation = $isSalary ? 'employee' : 'party';

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('psid_no', 'like', "%{$search}%")
                ->orWhere('cpr_no', 'like', "%{$search}%")
                ->orWhereHas($relation, fn($p) => $p->where('name', 'like', "%{$search}%")));
        }

        if ($request->filled('party_id')) {
            $query->where($partyColumn, $request->party_id);
        }

        if (!$isSalary && $request->filled('section')) {
            $query->where('section', $request->section);
        }

        if ($request->filled('from')) {
            $query->whereDate($periodColumn, '>=', $request->from . '-01');
        }

        if ($request->filled('to')) {
            $query->whereDate($periodColumn, '<=', Carbon::parse($request->to . '-01')->endOfMonth());
        }

        if ($request->filled('deposited')) {
            $request->deposited === 'yes'
                ? $query->whereNotNull('cpr_no')->where('cpr_no', '!=', '')
                : $query->where(fn($q) => $q->whereNull('cpr_no')->orWhere('cpr_no', ''));
        }

        $totals = (clone $query)
            ->selectRaw($isSalary
                ? 'SUM(total_salary) g, SUM(tax_deducted) t, SUM(final_net_payment) n'
                : 'SUM(gross_amount) g, SUM(tax_withheld) t, SUM(net_payment) n')
            ->first();

        $rows = $query->orderByDesc($periodColumn)->orderByDesc('id')
            ->paginate(50)->withQueryString();

        $parties = $isSalary
            ? $company->employees()->active()->orderBy('name')->get()
            : $company->vendors()->active()->orderBy('name')->get();

        $sections = WhtSection::active()->for($isSalary ? 'salary' : 'purchase')->orderBy('code')->get();

        // Open requests hold their entries, so they have to be visible and
        // cancellable from here - otherwise a failed attempt locks entries with
        // nothing on screen explaining why.
        $openRequests = WhtPsidRequest::where('wht_company_id', $company->id)
            ->where('kind', $kind)
            ->where('status', 'open')
            ->latest()
            ->get();

        return view('wht.transactions.index', compact(
            'company', 'kind', 'isSalary', 'rows', 'totals', 'parties', 'sections', 'openRequests'
        ));
    }

    /**
     * Delete a selected set of entries.
     *
     * Entries carrying a CPR record tax already deposited with FBR, so the count
     * of those is reported back rather than deleted quietly — if a quarter has
     * been filed, removing them puts your records out of step with the return.
     */
    public function destroyBulk(Request $request)
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('delete', $company);

        $validated = $request->validate([
            'kind'  => 'required|in:purchases,salaries',
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $isSalary = $validated['kind'] === 'salaries';
        $query = ($isSalary ? $company->salaries() : $company->purchases())
            ->whereIn('id', $validated['ids']);

        $taxColumn = $isSalary ? 'tax_deducted' : 'tax_withheld';

        $deposited = (clone $query)->whereNotNull('cpr_no')->where('cpr_no', '!=', '')->count();
        $depositedTax = (clone $query)->whereNotNull('cpr_no')->where('cpr_no', '!=', '')->sum($taxColumn);
        $tax = (clone $query)->sum($taxColumn);

        $count = 0;
        DB::transaction(function () use ($query, &$count) {
            $count = $query->delete();
        });

        $note = $deposited
            ? " {$deposited} of them carried a CPR, so " . number_format((float) $depositedTax, 0)
              . ' of already-deposited tax is no longer recorded.'
            : '';

        return back()->with($deposited ? 'error' : 'success',
            "Deleted {$count} entries totalling " . number_format((float) $tax, 0) . " in tax.{$note}");
    }
}
