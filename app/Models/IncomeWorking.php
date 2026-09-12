<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The income side of a year's working paper.
 *
 * Every figure is entered by the preparer. Nothing here is computed from tax
 * rates - tax_chargeable in particular is typed in from IRIS, so the app never
 * puts a number on a return that nobody checked.
 */
class IncomeWorking extends Model
{
    protected $fillable = [
        'client_id', 'tax_year',
        'salary', 'property', 'business', 'capital_gain', 'other_sources',
        'foreign_sources', 'agriculture', 'exempt_income', 'ftr_income',
        'deductible_allowances', 'tax_chargeable', 'tax_reductions_credits',
        'tax_deducted', 'tax_paid', 'notes',
    ];

    protected $casts = ['tax_year' => 'integer'];

    /** Heads of income, in the order IRIS lists them. */
    public const HEADS = [
        'salary'          => 'Salary',
        'property'        => 'Property',
        'business'        => 'Business',
        'capital_gain'    => 'Capital gains',
        'other_sources'   => 'Other sources',
        'foreign_sources' => 'Foreign sources',
        'agriculture'     => 'Agriculture',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /** Income taxable at normal rates, before deductible allowances. */
    public function getTotalIncomeAttribute(): float
    {
        return array_sum(array_map(fn($h) => (float) $this->{$h}, array_keys(self::HEADS)));
    }

    public function getTaxableIncomeAttribute(): float
    {
        return $this->total_income - (float) $this->deductible_allowances;
    }

    /** What is left to pay once credits and tax already paid are taken off. */
    public function getTaxPayableAttribute(): float
    {
        return (float) $this->tax_chargeable
             - (float) $this->tax_reductions_credits
             - (float) $this->tax_deducted
             - (float) $this->tax_paid;
    }
}
