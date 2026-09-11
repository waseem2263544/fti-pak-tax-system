<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtCompany;
use App\Models\WhtParty;
use App\Models\WhtSalarySlab;
use App\Models\WhtSection;
use App\Models\WhtTaxRate;
use App\Services\Wht\WhtCalculator;

/**
 * Setup — one place for everything you configure rather than five menu entries.
 *
 * Each card links to the screen that already manages that data; this is a hub,
 * not a replacement, so nothing about those screens changes.
 */
class WhtSetupController extends Controller
{
    use ResolvesWhtCompany;

    public function index()
    {
        $company = $this->currentCompany();
        $isAdmin = (bool) auth()->user()?->hasRole('admin');

        $taxYear = WhtCalculator::taxYear(now());

        return view('wht.setup.index', [
            'company' => $company,
            'isAdmin' => $isAdmin,
            'counts'  => [
                'agents'    => WhtCompany::accessibleTo(auth()->user())->count(),
                'parties'   => $company->parties()->count(),
                'vendors'   => $company->vendors()->active()->count(),
                'employees' => $company->employees()->active()->count(),
                'rates'     => $isAdmin ? WhtTaxRate::count() : null,
                'sections'  => $isAdmin ? WhtSection::where('is_active', true)->count() : null,
                'slabs'     => $isAdmin ? WhtSalarySlab::where('tax_year', $taxYear)->count() : null,
            ],
            'taxYear' => $taxYear,
            // A party with no CNIC/NTN cannot appear on a PSID or statement.
            'partiesMissingId' => $company->parties()->active()
                ->where(fn($q) => $q->whereNull('cnic_ntn')->orWhere('cnic_ntn', ''))
                ->count(),
        ]);
    }
}
