<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtParty;
use App\Models\WhtPurchase;
use App\Models\WhtSection;
use App\Services\Wht\WhtCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WhtPurchaseController extends Controller
{
    use ResolvesWhtCompany;

    public function __construct(private WhtCalculator $calculator)
    {
    }

    public function index(Request $request)
    {
        $company = $this->currentCompany();

        $query = $company->purchases()->with('party');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('psid_no', 'like', "%{$search}%")
                ->orWhere('cpr_no', 'like', "%{$search}%")
                ->orWhere('remarks', 'like', "%{$search}%")
                ->orWhereHas('party', fn($p) => $p->where('name', 'like', "%{$search}%")));
        }

        if ($request->filled('party_id')) {
            $query->where('party_id', $request->party_id);
        }

        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        if ($request->filled('from')) {
            $query->whereDate('period_month', '>=', $request->from . '-01');
        }

        if ($request->filled('to')) {
            $query->whereDate('period_month', '<=', Carbon::parse($request->to . '-01')->endOfMonth());
        }

        if ($request->filled('deposited')) {
            $request->deposited === 'yes'
                ? $query->whereNotNull('cpr_no')->where('cpr_no', '!=', '')
                : $query->where(fn($q) => $q->whereNull('cpr_no')->orWhere('cpr_no', ''));
        }

        $totals = (clone $query)->selectRaw('SUM(gross_amount) g, SUM(tax_withheld) t, SUM(net_payment) n')->first();

        $purchases = $query->orderByDesc('period_month')->orderByDesc('id')->paginate(50)->withQueryString();
        $parties = $company->vendors()->active()->orderBy('name')->get();
        $sections = WhtSection::active()->for('purchase')->orderBy('code')->get();

        return view('wht.purchases.index', compact('company', 'purchases', 'parties', 'sections', 'totals'));
    }

    public function create()
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('create', $company);

        return view('wht.purchases.form', [
            'company'  => $company,
            'purchase' => new WhtPurchase(['period_month' => now()->startOfMonth(), 'payment_date' => now()]),
            'parties'  => $this->selectableParties($company),
            'sections' => WhtSection::active()->for('purchase')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('create', $company);

        $purchase = new WhtPurchase(['wht_company_id' => $company->id, 'created_by' => auth()->id()]);
        $this->fill($purchase, $request, $company);
        $purchase->save();

        return redirect()->route('wht.purchases.index')
            ->with('success', 'Payment recorded.');
    }

    public function edit(WhtPurchase $purchase)
    {
        $company = $this->currentCompany();
        abort_unless($purchase->wht_company_id === $company->id, 404);
        $this->authorizeAbility('edit', $company);

        return view('wht.purchases.form', [
            'company'  => $company,
            'purchase' => $purchase,
            'parties'  => $this->selectableParties($company),
            'sections' => WhtSection::active()->for('purchase')->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, WhtPurchase $purchase)
    {
        $company = $this->currentCompany();
        abort_unless($purchase->wht_company_id === $company->id, 404);
        $this->authorizeAbility('edit', $company);

        $this->fill($purchase, $request, $company);
        $purchase->save();

        return redirect()->route('wht.purchases.index')
            ->with('success', 'Payment updated.');
    }

    public function destroy(WhtPurchase $purchase)
    {
        $company = $this->currentCompany();
        abort_unless($purchase->wht_company_id === $company->id, 404);
        $this->authorizeAbility('delete', $company);

        $purchase->delete();

        return back()->with('success', 'Payment deleted.');
    }

    /**
     * Live preview for the entry form. The same calculator the save path uses,
     * so what the form shows is what gets stored.
     */
    public function preview(Request $request)
    {
        $company = $this->currentCompany();

        $validated = $request->validate([
            'party_id'     => 'nullable|integer',
            'section'      => 'nullable|string|max:50',
            'goods_type'   => 'nullable|string|max:255',
            'period_month' => 'required|date_format:Y-m',
            'amount'       => 'nullable|numeric',
            'calc_mode'    => 'nullable|in:gross,net',
            'manual_rate'  => 'nullable|numeric',
        ]);

        $party = $validated['party_id']
            ? $company->parties()->find($validated['party_id'])
            : null;

        $result = $this->calculator->purchase([
            'party'        => $party,
            'section'      => $validated['section'] ?? null,
            'goods_type'   => $validated['goods_type'] ?? null,
            'period_month' => $validated['period_month'] . '-01',
            'amount'       => $validated['amount'] ?? 0,
            'calc_mode'    => $validated['calc_mode'] ?? 'gross',
            'manual_rate'  => $validated['manual_rate'] ?? null,
        ]);

        return response()->json($result);
    }

    /**
     * Vendors for the entry dropdown.
     *
     * Deliberately NOT filtered by ATL status — the old portal only listed
     * filers, which made every non-filer rate in the matrix unreachable.
     */
    private function selectableParties($company)
    {
        return $company->vendors()->active()->orderBy('name')->get();
    }

    private function fill(WhtPurchase $purchase, Request $request, $company): void
    {
        $validated = $request->validate([
            'party_id'     => 'required|integer',
            'period_month' => 'required|date_format:Y-m',
            'payment_date' => 'required|date',
            'section'      => 'nullable|string|max:50',
            'goods_type'   => 'nullable|string|max:255',
            'calc_mode'    => 'required|in:gross,net',
            'amount'       => 'required|numeric|min:0',
            'manual_rate'  => 'nullable|numeric|min:0|max:100',
            'override'     => 'nullable|boolean',
            'cpr_no'       => 'nullable|string|max:50',
            'cpr_date'     => 'nullable|date',
            'psid_no'      => 'nullable|string|max:50',
            'remarks'      => 'nullable|string',
        ]);

        $party = $company->parties()->findOrFail($validated['party_id']);

        // Recompute server-side; the posted totals are never trusted.
        $result = $this->calculator->purchase([
            'party'        => $party,
            'section'      => $validated['section'] ?? null,
            'goods_type'   => $validated['goods_type'] ?? null,
            'period_month' => $validated['period_month'] . '-01',
            'amount'       => $validated['amount'],
            'calc_mode'    => $validated['calc_mode'],
            'manual_rate'  => $request->boolean('override') ? ($validated['manual_rate'] ?? null) : null,
        ]);

        $purchase->fill([
            'party_id'     => $party->id,
            'period_month' => Carbon::createFromFormat('Y-m-d', $validated['period_month'] . '-01')->startOfMonth(),
            'payment_date' => $validated['payment_date'],
            'section'      => $validated['section'] ?? null,
            'goods_type'   => $validated['goods_type'] ?? null,
            'calc_mode'    => $validated['calc_mode'],
            'cpr_no'       => $validated['cpr_no'] ?? null,
            'cpr_date'     => $validated['cpr_date'] ?? null,
            'psid_no'      => $validated['psid_no'] ?? null,
            'remarks'      => $validated['remarks'] ?? null,
        ] + $result);
    }
}
