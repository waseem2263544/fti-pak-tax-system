<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtParty;
use App\Models\WhtSection;
use Illuminate\Http\Request;

/**
 * Vendors and employees for the active withholding agent.
 */
class WhtPartyController extends Controller
{
    use ResolvesWhtCompany;

    public function index(Request $request)
    {
        $company = $this->currentCompany();

        $query = $company->parties();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('cnic_ntn', 'like', "%{$search}%"));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('atl_status')) {
            $query->where('atl_status', $request->atl_status);
        }

        // Inactive parties are hidden unless asked for.
        if (!$request->boolean('show_inactive')) {
            $query->where('is_active', true);
        }

        $parties = $query->orderBy('name')->paginate(50)->withQueryString();
        $sections = WhtSection::active()->orderBy('code')->get();

        return view('wht.parties.index', compact('company', 'parties', 'sections'));
    }

    public function store(Request $request)
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('create', $company);

        $data = $this->validated($request);
        $data['wht_company_id'] = $company->id;

        WhtParty::create($data);

        return back()->with('success', 'Party added.');
    }

    public function update(Request $request, WhtParty $party)
    {
        $company = $this->currentCompany();
        abort_unless($party->wht_company_id === $company->id, 404);
        $this->authorizeAbility('edit', $company);

        $party->update($this->validated($request));

        return back()->with('success', 'Party updated.');
    }

    public function destroy(WhtParty $party)
    {
        $company = $this->currentCompany();
        abort_unless($party->wht_company_id === $company->id, 404);
        $this->authorizeAbility('delete', $company);

        if ($party->purchases()->exists() || $party->salaries()->exists()) {
            return back()->with('error', 'Cannot delete a party with recorded transactions. Mark it inactive instead.');
        }

        $party->delete();

        return back()->with('success', 'Party deleted.');
    }

    /**
     * CSV import: name, cnic_ntn, type, category, address, atl_status, default_section
     */
    public function import(Request $request)
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('create', $company);

        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $added = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim($row[0] ?? '');

            if ($name === '') {
                continue;
            }

            $cnic = trim($row[1] ?? '');

            // Same party twice in one company is a duplicate, not an update.
            if ($cnic && $company->parties()->where('cnic_ntn', $cnic)->exists()) {
                $skipped++;
                continue;
            }

            WhtParty::create([
                'wht_company_id'  => $company->id,
                'name'            => $name,
                'cnic_ntn'        => $cnic ?: null,
                'type'            => $this->normaliseType($row[2] ?? 'vendor'),
                'category'        => $this->normaliseCategory($row[3] ?? 'individual'),
                'address'         => trim($row[4] ?? '') ?: null,
                'atl_status'      => $this->normaliseAtl($row[5] ?? 'filer'),
                'default_section' => trim($row[6] ?? '') ?: null,
            ]);
            $added++;
        }

        fclose($handle);

        return back()->with('success', "Imported {$added} parties." . ($skipped ? " Skipped {$skipped} duplicates." : ''));
    }

    private function normaliseType(string $value): string
    {
        return match (strtolower(trim($value))) {
            'employee' => 'employee',
            'both'     => 'both',
            default    => 'vendor',
        };
    }

    private function normaliseCategory(string $value): string
    {
        return match (strtolower(trim($value))) {
            'company' => 'company',
            'aop'     => 'aop',
            default   => 'individual',
        };
    }

    /**
     * The old portal wrote 'Active'/'Inactive' into this column, meaning
     * on/off the Active Taxpayers List.
     */
    private function normaliseAtl(string $value): string
    {
        return in_array(strtolower(trim($value)), ['non-filer', 'nonfiler', 'inactive', 'no'], true)
            ? 'non-filer'
            : 'filer';
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:150',
            'cnic_ntn'              => 'nullable|string|max:20',
            'type'                  => 'required|in:vendor,employee,both',
            'category'              => 'required|in:company,individual,aop',
            'atl_status'            => 'required|in:filer,non-filer',
            'is_active'             => 'boolean',
            'city'                  => 'nullable|string|max:100',
            'address'               => 'nullable|string',
            'default_section'       => 'nullable|string|max:50',
            'default_goods_type'    => 'nullable|string|max:255',
            'default_calc_mode'     => 'required|in:gross,net',
            'default_salary_amount' => 'nullable|numeric|min:0',
            'exempt_rate'           => 'nullable|numeric|min:0|max:100',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
