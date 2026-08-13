<?php

namespace App\Http\Controllers\Wht\Concerns;

use App\Models\WhtCompany;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the withholding agent the user is currently working on.
 *
 * The old portal kept this in $_SESSION['active_company_id'] and every page
 * re-read it by hand. Same idea, but access is checked against wht_company_user
 * (or the admin role) on every resolution rather than only at switch time.
 */
trait ResolvesWhtCompany
{
    protected function currentCompany(): WhtCompany
    {
        $id = session('wht_company_id');

        $company = $id ? WhtCompany::find($id) : null;

        if (!$company || !$this->canAccess($company)) {
            session()->forget('wht_company_id');

            throw new HttpResponseException(
                redirect()->route('wht.companies.index')
                    ->with('error', 'Select a withholding agent to continue.')
            );
        }

        return $company;
    }

    protected function canAccess(WhtCompany $company): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $company->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('can_view', true)
            ->exists();
    }

    /**
     * Companies the current user may open, for the picker and the header dropdown.
     */
    protected function accessibleCompanies()
    {
        $user = Auth::user();

        $query = WhtCompany::query()->where('is_active', true)->orderBy('name');

        if (!$user?->hasRole('admin')) {
            $query->whereHas('users', fn($q) => $q
                ->where('users.id', $user?->id)
                ->where('wht_company_user.can_view', true));
        }

        return $query->get();
    }

    /**
     * Per-company ability check. Admins get everything; other users are limited
     * by their wht_company_user grants.
     */
    protected function can(string $ability, ?WhtCompany $company = null): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        $company ??= $this->currentCompany();

        $pivot = $company->users()->wherePivot('user_id', $user->id)->first()?->pivot;

        return (bool) ($pivot?->{"can_{$ability}"} ?? false);
    }

    protected function authorizeAbility(string $ability, ?WhtCompany $company = null): void
    {
        abort_unless($this->can($ability, $company), 403, 'You do not have permission to do that.');
    }
}
