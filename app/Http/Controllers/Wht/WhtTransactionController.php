<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtSection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        return view('wht.transactions.index', compact(
            'company', 'kind', 'isSalary', 'rows', 'totals', 'parties', 'sections'
        ));
    }
}
