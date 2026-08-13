<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhtPurchase extends Model
{
    protected $table = 'wht_purchases';
    protected $fillable = [
        'wht_company_id', 'party_id', 'period_month', 'payment_date', 'section', 'goods_type',
        'calc_mode', 'gross_amount', 'tax_rate', 'tax_rate_id', 'rate_source', 'tax_withheld',
        'net_payment', 'cpr_no', 'cpr_date', 'psid_no', 'remarks', 'created_by', 'legacy_id',
    ];
    protected $casts = [
        'period_month' => 'date',
        'payment_date' => 'date',
        'cpr_date'     => 'date',
        'gross_amount' => 'decimal:2',
        'tax_rate'     => 'decimal:3',
        'tax_withheld' => 'decimal:2',
        'net_payment'  => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(WhtCompany::class, 'wht_company_id'); }
    public function party() { return $this->belongsTo(WhtParty::class, 'party_id'); }
    public function appliedRate() { return $this->belongsTo(WhtTaxRate::class, 'tax_rate_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeForPeriod($q, $from, $to)
    {
        return $q->whereBetween('period_month', [
            WhtTaxRate::monthStart($from),
            WhtTaxRate::monthStart($to),
        ]);
    }

    public function getIsDepositedAttribute(): bool
    {
        return filled($this->cpr_no);
    }
}
