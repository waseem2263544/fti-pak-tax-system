<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhtChallan extends Model
{
    protected $table = 'wht_challans';
    protected $fillable = [
        'wht_company_id', 'psid_no', 'cpr_no', 'psid_file', 'cpr_file', 'paid_on', 'legacy_id',
    ];
    protected $casts = ['paid_on' => 'date'];

    public function company() { return $this->belongsTo(WhtCompany::class, 'wht_company_id'); }

    public function purchases()
    {
        return $this->hasMany(WhtPurchase::class, 'psid_no', 'psid_no')
            ->where('wht_company_id', $this->wht_company_id);
    }

    public function salaries()
    {
        return $this->hasMany(WhtSalary::class, 'psid_no', 'psid_no')
            ->where('wht_company_id', $this->wht_company_id);
    }
}
