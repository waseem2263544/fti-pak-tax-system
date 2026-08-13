<?php

namespace App\Services\Wht;

use App\Models\WhtParty;
use App\Models\WhtSalarySlab;
use App\Models\WhtSetting;
use App\Models\WhtTaxRate;
use Illuminate\Support\Carbon;

/**
 * Withholding tax maths.
 *
 * The old portal did all of this in browser JavaScript and stored whatever the
 * form posted, so a crafted request could save any tax figure. Everything here
 * runs server-side and is the single source of truth — the controllers, the CSV
 * import and the live-preview endpoint all call into it.
 */
class WhtCalculator
{
    /**
     * Pakistani tax year for a date: July 2025 – June 2026 is tax year 2026.
     */
    public static function taxYear($date): int
    {
        $d = Carbon::parse($date);

        return $d->month >= 7 ? $d->year + 1 : $d->year;
    }

    /**
     * Default exempt (medical) allowance rate, as a percentage of taxable salary.
     */
    public static function defaultExemptRate(): float
    {
        return (float) WhtSetting::get('exempt_allowance_rate', 10);
    }

    // ── PURCHASES ────────────────────────────────────────────────────────────

    /**
     * Compute a purchase/service/contract payment.
     *
     * The rate is resolved from the global matrix using the PERIOD MONTH, not
     * the payment date. A manual rate may be supplied to override the matrix
     * (recorded as rate_source = 'manual').
     *
     * @param  array{party?:WhtParty|null, section?:string|null, goods_type?:string|null,
     *               period_month:mixed, amount:float|string, calc_mode?:string,
     *               manual_rate?:float|string|null}  $input
     * @return array{tax_rate:float, tax_rate_id:int|null, rate_source:string,
     *               gross_amount:float, tax_withheld:float, net_payment:float}
     */
    public function purchase(array $input): array
    {
        $party    = $input['party'] ?? null;
        $mode     = ($input['calc_mode'] ?? 'gross') === 'net' ? 'net' : 'gross';
        $amount   = (float) ($input['amount'] ?? 0);
        $manual   = $input['manual_rate'] ?? null;

        $rateRow = WhtTaxRate::resolve(
            $input['section']    ?? null,
            $party?->category,
            $party?->atl_status,
            $input['period_month'],
            $input['goods_type'] ?? null,
        );

        // An explicit rate always wins, but we still note whether it happens to
        // match the matrix so the UI can show "Auto" vs "Manual" honestly.
        if ($manual !== null && $manual !== '') {
            $rate       = (float) $manual;
            $matched    = $rateRow && abs((float) $rateRow->rate - $rate) < 0.0005;
            $rateSource = $matched ? 'matrix' : 'manual';
            $rateId     = $matched ? $rateRow->id : null;
        } elseif ($rateRow) {
            $rate       = (float) $rateRow->rate;
            $rateSource = 'matrix';
            $rateId     = $rateRow->id;
        } else {
            $rate       = 0.0;
            $rateSource = 'manual';
            $rateId     = null;
        }

        if ($mode === 'net') {
            // Gross up from the net payment. A rate of 100% or more has no
            // finite gross, so fall back to treating the input as gross.
            if ($rate >= 100) {
                $gross = $amount;
            } else {
                $gross = $amount / (1 - ($rate / 100));
            }
            $tax = $gross - $amount;
            $net = $amount;
        } else {
            $gross = $amount;
            $tax   = $gross * ($rate / 100);
            $net   = $gross - $tax;
        }

        return [
            'tax_rate'     => round($rate, 3),
            'tax_rate_id'  => $rateId,
            'rate_source'  => $rateSource,
            'gross_amount' => round($gross, 2),
            'tax_withheld' => round($tax, 2),
            'net_payment'  => round($net, 2),
        ];
    }

    // ── SALARIES ─────────────────────────────────────────────────────────────

    /**
     * Annual salary tax for an annual taxable amount under a year's slabs.
     */
    public function annualSalaryTax(float $annualTaxable, int $taxYear): float
    {
        if ($annualTaxable <= 0) {
            return 0.0;
        }

        $slab = WhtSalarySlab::forYear($taxYear)
            ->where('min_salary', '<', $annualTaxable)
            ->where(fn($q) => $q->whereNull('max_salary')->orWhere('max_salary', '>=', $annualTaxable))
            ->orderByDesc('min_salary')
            ->first();

        if (!$slab) {
            return 0.0;
        }

        $exceeding = $annualTaxable - (float) $slab->min_salary;

        return (float) $slab->fixed_tax + ($exceeding * ((float) $slab->tax_rate / 100));
    }

    /**
     * Monthly tax on a monthly taxable (basic) salary.
     */
    public function monthlySalaryTax(float $monthlyTaxable, int $taxYear): float
    {
        return $this->annualSalaryTax($monthlyTaxable * 12, $taxYear) / 12;
    }

    /**
     * Compute a salary payment.
     *
     * Gross mode: `amount` is the total salary (taxable + exempt allowance).
     * Net mode:   `amount` is the take-home the employee should receive, and the
     *             taxable salary is solved for by bisection.
     *
     * The exempt (medical) allowance rate was hardcoded at 10% in three places
     * in the old portal; it is now a setting with a per-employee override.
     *
     * @param  array{employee?:WhtParty|null, salary_month:mixed, amount:float|string,
     *               calc_mode?:string, exempt_rate?:float|string|null}  $input
     * @return array{tax_year:int, exempt_rate:float, taxable_salary:float,
     *               exempt_amount:float, total_salary:float, tax_deducted:float,
     *               final_net_payment:float}
     */
    public function salary(array $input): array
    {
        $employee = $input['employee'] ?? null;
        $mode     = ($input['calc_mode'] ?? 'gross') === 'net' ? 'net' : 'gross';
        $amount   = (float) ($input['amount'] ?? 0);
        $taxYear  = static::taxYear($input['salary_month']);

        $exemptRate = $input['exempt_rate']
            ?? $employee?->exempt_rate
            ?? static::defaultExemptRate();
        $exemptRate = (float) $exemptRate;
        $factor     = 1 + ($exemptRate / 100);

        if ($mode === 'net') {
            $taxable = $this->solveTaxableFromNet($amount, $taxYear, $factor);
        } else {
            $taxable = $factor > 0 ? $amount / $factor : 0.0;
        }

        $tax    = $this->monthlySalaryTax($taxable, $taxYear);
        $exempt = $taxable * ($exemptRate / 100);
        $total  = $taxable + $exempt;
        $net    = $total - $tax;

        return [
            'tax_year'          => $taxYear,
            'exempt_rate'       => round($exemptRate, 2),
            'taxable_salary'    => round($taxable, 2),
            'exempt_amount'     => round($exempt, 2),
            'total_salary'      => round($total, 2),
            'tax_deducted'      => round($tax, 2),
            'final_net_payment' => round($net, 2),
        ];
    }

    /**
     * Invert the salary calculation: find the taxable salary whose net payment
     * equals $targetNet. Tax is a step function of income, so bisection is used
     * rather than an algebraic solve.
     */
    private function solveTaxableFromNet(float $targetNet, int $taxYear, float $factor): float
    {
        if ($targetNet <= 0) {
            return 0.0;
        }

        $low  = 0.0;
        $high = max($targetNet * 2, 1.0);

        for ($i = 0; $i < 60; $i++) {
            $mid = ($low + $high) / 2;
            $net = ($mid * $factor) - $this->monthlySalaryTax($mid, $taxYear);

            if (abs($net - $targetNet) < 0.005) {
                return $mid;
            }

            if ($net < $targetNet) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return ($low + $high) / 2;
    }
}
