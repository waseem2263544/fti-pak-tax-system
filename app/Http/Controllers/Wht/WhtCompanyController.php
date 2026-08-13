<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\User;
use App\Models\WhtCompany;
use Illuminate\Http\Request;

/**
 * Withholding agents, and the picker that sets which one you are working on.
 */
class WhtCompanyController extends Controller
{
    use ResolvesWhtCompany;

    public function index()
    {
        $companies = $this->accessibleCompanies()->loadCount(['purchases', 'salaries', 'parties']);

        return view('wht.companies.index', compact('companies'));
    }

    /**
     * Set the active withholding agent for this session.
     */
    public function select(WhtCompany $company)
    {
        abort_unless($this->canAccess($company), 403, 'You do not have access to this withholding agent.');

        session(['wht_company_id' => $company->id]);

        return redirect()->route('wht.dashboard');
    }

    public function create()
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        return view('wht.companies.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $company = WhtCompany::create($this->validated($request));

        session(['wht_company_id' => $company->id]);

        return redirect()->route('wht.dashboard')
            ->with('success', 'Withholding agent created.');
    }

    public function edit(WhtCompany $company)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $users = User::orderBy('name')->get();
        $company->load('users');

        return view('wht.companies.edit', compact('company', 'users'));
    }

    public function update(Request $request, WhtCompany $company)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $company->update($this->validated($request));

        return redirect()->route('wht.companies.index')
            ->with('success', 'Withholding agent updated.');
    }

    public function destroy(WhtCompany $company)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        if ($company->purchases()->exists() || $company->salaries()->exists()) {
            return back()->with('error', 'Cannot delete a withholding agent that has recorded transactions. Deactivate it instead.');
        }

        if (session('wht_company_id') == $company->id) {
            session()->forget('wht_company_id');
        }

        $company->delete();

        return redirect()->route('wht.companies.index')
            ->with('success', 'Withholding agent deleted.');
    }

    /**
     * Replace this company's per-user access grants.
     */
    public function updateAccess(Request $request, WhtCompany $company)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'grants'                => 'array',
            'grants.*.can_view'     => 'boolean',
            'grants.*.can_create'   => 'boolean',
            'grants.*.can_edit'     => 'boolean',
            'grants.*.can_delete'   => 'boolean',
        ]);

        $sync = [];

        foreach ($validated['grants'] ?? [] as $userId => $abilities) {
            // A user with no view access has no grant at all.
            if (empty($abilities['can_view'])) {
                continue;
            }

            $sync[$userId] = [
                'can_view'   => true,
                'can_create' => (bool) ($abilities['can_create'] ?? false),
                'can_edit'   => (bool) ($abilities['can_edit'] ?? false),
                'can_delete' => (bool) ($abilities['can_delete'] ?? false),
            ];
        }

        $company->users()->sync($sync);

        return back()->with('success', 'Access updated.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'      => 'required|string|max:150',
            'ntn_cnic'  => 'nullable|string|max:20',
            'address'   => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
