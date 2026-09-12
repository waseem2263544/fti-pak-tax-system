<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single head within a salary working - basic pay, a house rent allowance,
 * tax deducted, a provident fund contribution.
 *
 * Income heads carry a treatment, because an exempt allowance still has to be
 * declared and still funds the year, but lands on a different reconciliation
 * code. Deduction heads do not: a deduction is money that left, whatever its
 * tax character.
 */
class SalaryComponent extends Model
{
    protected $fillable = [
        'salary_working_id', 'side', 'label', 'treatment',
        'is_tax', 'locked', 'annual_amount', 'sort_order',
    ];

    protected $casts = ['is_tax' => 'boolean', 'locked' => 'boolean'];

    public function working()
    {
        return $this->belongsTo(SalaryWorking::class, 'salary_working_id');
    }

    public function months()
    {
        return $this->hasMany(SalaryMonth::class);
    }

    /**
     * The figure for the year.
     *
     * Read from whichever basis the working is on, so switching the basis
     * never quietly rewrites what was entered on the other one.
     */
    public function amount(): float
    {
        if (($this->working->basis ?? 'annual') === 'monthly') {
            return (float) $this->months->sum('amount');
        }

        return (float) $this->annual_amount;
    }

    public function monthAmount(int $month): ?string
    {
        return $this->months->firstWhere('month', $month)?->amount;
    }
}
