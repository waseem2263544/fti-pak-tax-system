<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhtCompany;
use App\Models\WhtSection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Read-only WHT data for external tooling (the wht-psid Claude skill).
 *
 * Authenticated with a single shared token in WHT_API_TOKEN rather than a user
 * session, because the caller is a script. The token gates read access to
 * withholding data, so treat it like a password: keep it out of git, and rotate
 * it if it leaks. Nothing here writes.
 */
class WhtPsidApiController extends Controller
{
    private function authorizeToken(Request $request): bool
    {
        $expected = (string) config('services.wht_api.token', '');

        // An unset token must not mean "open to everyone".
        if ($expected === '') {
            return false;
        }

        $given = (string) ($request->header('X-Wht-Token') ?? $request->get('token', ''));

        return $given !== '' && hash_equals($expected, $given);
    }

    /** GET /api/wht/agents — list withholding agents so the skill can resolve a name. */
    public function agents(Request $request)
    {
        if (!$this->authorizeToken($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(
            WhtCompany::orderBy('name')->get(['id', 'name', 'ntn_cnic', 'is_active'])
        );
    }

    /**
     * GET /api/wht/psid?agent=<id|name>&month=YYYY-MM
     *
     * Rows are selected by TAX PERIOD, not payment date, so a liability
     * deposited late still belongs to its own month.
     */
    public function psid(Request $request)
    {
        if (!$this->authorizeToken($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'agent' => 'required|string',
            'month' => 'required|date_format:Y-m',
        ]);

        $agent = ctype_digit($validated['agent'])
            ? WhtCompany::find($validated['agent'])
            : WhtCompany::where('name', 'like', '%' . $validated['agent'] . '%')->first();

        if (!$agent) {
            return response()->json(['error' => 'Withholding agent not found'], 404);
        }

        $month = Carbon::createFromFormat('Y-m-d', $validated['month'] . '-01')->startOfMonth();

        $sections = WhtSection::all();
        $codeFor = fn(?string $s) => $sections->firstWhere('section', $s);

        $out = [];

        $purchases = $agent->purchases()->with('party')
            ->whereDate('period_month', $month)
            ->orderBy('payment_date')->get();

        foreach ($purchases->groupBy('section') as $section => $items) {
            $meta = $codeFor($section);

            $out[] = [
                'section'        => $section ?: 'Unclassified',
                'code'           => $meta?->code ?? '',
                'payment_nature' => $meta?->payment_nature ?? '',
                'kind'           => 'purchase',
                'rows'           => $items->map(fn($p) => [
                    'payee_name'     => $p->party?->name,
                    'payee_cnic_ntn' => $p->party?->cnic_ntn,
                    'payee_category' => $p->party?->category,
                    'atl_status'     => $p->party?->atl_status === 'non-filer' ? 'Non-filer' : 'Filer',
                    'payment_date'   => $p->payment_date?->toDateString(),
                    'period_month'   => $p->period_month?->format('Y-m'),
                    'gross_amount'   => (float) $p->gross_amount,
                    'tax_rate'       => (float) $p->tax_rate,
                    'tax_withheld'   => (float) $p->tax_withheld,
                    'net_payment'    => (float) $p->net_payment,
                    'psid_no'        => $p->psid_no,
                    'cpr_no'         => $p->cpr_no,
                    'remarks'        => $p->remarks,
                ])->values(),
            ];
        }

        $salaries = $agent->salaries()->with('employee')
            ->whereDate('salary_month', $month)
            ->orderBy('payment_date')->get();

        foreach ($salaries->groupBy('section') as $section => $items) {
            $meta = $codeFor($section ?: '149');

            $out[] = [
                'section'        => $section ?: '149',
                'code'           => $meta?->code ?? '',
                'payment_nature' => $meta?->payment_nature ?? 'Salary',
                'kind'           => 'salary',
                'rows'           => $items->map(fn($s) => [
                    'payee_name'     => $s->employee?->name,
                    'payee_cnic_ntn' => $s->employee?->cnic_ntn,
                    'payment_date'   => $s->payment_date?->toDateString(),
                    'period_month'   => $s->salary_month?->format('Y-m'),
                    'taxable_salary' => (float) $s->taxable_salary,
                    'exempt_amount'  => (float) $s->exempt_amount,
                    'gross_amount'   => (float) $s->total_salary,
                    'tax_rate'       => null,
                    'tax_withheld'   => (float) $s->tax_deducted,
                    'net_payment'    => (float) $s->final_net_payment,
                    'psid_no'        => $s->psid_no,
                    'cpr_no'         => $s->cpr_no,
                ])->values(),
            ];
        }

        usort($out, fn($a, $b) => strcmp($a['section'], $b['section']));

        return response()->json([
            'agent' => [
                'id'       => $agent->id,
                'name'     => $agent->name,
                'ntn_cnic' => $agent->ntn_cnic,
                'address'  => $agent->address,
            ],
            'period'   => $month->format('Y-m'),
            'sections' => $out,
            'totals'   => [
                'rows'  => collect($out)->sum(fn($s) => count($s['rows'])),
                'gross' => collect($out)->sum(fn($s) => collect($s['rows'])->sum('gross_amount')),
                'tax'   => collect($out)->sum(fn($s) => collect($s['rows'])->sum('tax_withheld')),
            ],
        ]);
    }
}
