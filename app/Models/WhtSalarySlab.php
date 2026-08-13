<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Global salary tax slab for a tax year. Not scoped to a company.
 */
class WhtSalarySlab extends Model
{
    protected $table = 'wht_salary_slabs';
    protected $fillable = ['tax_year', 'min_salary', 'max_salary', 'fixed_tax', 'tax_rate'];
    protected $casts = [
        'tax_year'   => 'integer',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'fixed_tax'  => 'decimal:2',
        'tax_rate'   => 'decimal:3',
    ];

    public function scopeForYear($q, int $taxYear)
    {
        return $q->where('tax_year', $taxYear)->orderBy('min_salary');
    }
}
