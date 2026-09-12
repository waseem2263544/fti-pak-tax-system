<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One employer's salary for a tax year.
 *
 * Worked either as a figure for the whole year or month by month, at the
 * preparer's choice. Which side of that switch is in force decides where each
 * component's amount is read from, and nothing else changes.
 */
class SalaryWorking extends Model
{
    protected $fillable = ['client_id', 'tax_year', 'employer', 'basis', 'notes', 'sort_order'];

    protected $casts = ['tax_year' => 'integer'];

    /** The tax year runs July to June, so the columns do too. */
    public const MONTHS = [
        7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function components()
    {
        return $this->hasMany(SalaryComponent::class)->orderBy('sort_order')->orderBy('id');
    }

    public function income()
    {
        return $this->components->where('side', 'income');
    }

    public function deductions()
    {
        return $this->components->where('side', 'deduction');
    }

    /** Gross pay: everything on the income side, taxable and exempt alike. */
    public function grossIncome(): float
    {
        return $this->income()->sum(fn($c) => $c->amount());
    }

    public function incomeBy(string $treatment): float
    {
        return $this->income()->where('treatment', $treatment)->sum(fn($c) => $c->amount());
    }

    public function totalDeductions(): float
    {
        return $this->deductions()->sum(fn($c) => $c->amount());
    }

    /** Tax withheld by the employer - the head every working carries. */
    public function taxDeducted(): float
    {
        return $this->deductions()->where('is_tax', true)->sum(fn($c) => $c->amount());
    }

    /** What actually reached the taxpayer, and so what funded the year. */
    public function netPay(): float
    {
        return $this->grossIncome() - $this->totalDeductions();
    }

    /**
     * The two heads every salary working starts with.
     *
     * They are locked because the rest of the return reads them: basic pay is
     * the floor of the income side, and tax deducted feeds both the tax
     * figures and personal expenses.
     */
    public function seedDefaults(): void
    {
        $this->components()->create([
            'side' => 'income', 'label' => 'Basic salary',
            'treatment' => 'taxable', 'locked' => true, 'sort_order' => 0,
        ]);

        $this->components()->create([
            'side' => 'deduction', 'label' => 'Income tax deducted',
            'is_tax' => true, 'locked' => true, 'sort_order' => 0,
        ]);
    }
}
