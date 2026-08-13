<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Gate for the WHT module's global settings — tax rates, salary slabs and FBR
 * sections. These are statutory and shared by every withholding agent, so only
 * administrators may change them.
 *
 * This exists as a middleware class because App\Http\Controllers\Controller does
 * not extend Laravel's base controller, so $this->middleware() is unavailable,
 * and Laravel 10 route groups cannot take a closure as middleware.
 */
class EnsureWhtAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(
            $request->user()?->hasRole('admin'),
            403,
            'Only administrators can manage withholding tax settings.'
        );

        return $next($request);
    }
}
