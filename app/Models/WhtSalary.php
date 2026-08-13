<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhtSalary extends Model
{
    protected $table = 'wht_salaries';
    protected $fillable = [
        'wht_company_id', 'employee_id', 'salary_month', 'payment_date', 'section', 'calc_mode',
        'input_amount', 'taxable_salary', 'exempt_amount', 'exempt_rate', 'total_salary',
        'tax_deducted', 'final_net_payment', 'tax_year', 'cpr_no', 'cpr_date', 'psid_no',
        'challan_date', 'created_by', 'legacy_id',
    ];
    protected $casts = [
        'salary_month'      => 'date',
        'payment_date'      => 'date',
        'cpr_date'          => 'date',
        'challan_date'      => 'date',
        'input_amount'      => 'decimal:2',
        'taxable_salary'    => 'decimal:2',
        'exempt_amount'     => 'decimal:2',
        'exempt_rate'       => 'decimal:2',
        'total_salary'      => 'decimal:2',
        'tax_deducted'      => 'decimal:2',
        'final_net_payment' => 'decimal:2',
        'tax_year'          => 'integer',
    ];

    public function company() { return $this->belongsTo(WhtCompany::class, 'wht_company_id'); }
    public function employee() { return $this->belongsTo(WhtParty::class, 'employee_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeForPeriod($q, $from, $to)
    {
        return $q->whereBetween('salary_month', [
            WhtTaxRate::monthStart($from),
            WhtTaxRate::monthStart($to),
        ]);
    }

    public function getIsDepositedAttribute(): bool
    {
        return filled($this->cpr_no);
    }
}
