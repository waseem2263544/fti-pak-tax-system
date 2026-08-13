<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhtParty extends Model
{
    protected $table = 'wht_parties';
    protected $fillable = [
        'wht_company_id', 'name', 'cnic_ntn', 'type', 'category', 'atl_status', 'is_active',
        'city', 'address', 'default_section', 'default_goods_type', 'default_calc_mode',
        'default_salary_amount', 'exempt_rate', 'legacy_id',
    ];
    protected $casts = [
        'is_active'             => 'boolean',
        'default_salary_amount' => 'decimal:2',
        'exempt_rate'           => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(WhtCompany::class, 'wht_company_id'); }
    public function purchases() { return $this->hasMany(WhtPurchase::class, 'party_id'); }
    public function salaries() { return $this->hasMany(WhtSalary::class, 'employee_id'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeVendors($q) { return $q->whereIn('type', ['vendor', 'both']); }
    public function scopeEmployees($q) { return $q->whereIn('type', ['employee', 'both']); }

    public function getIsFilerAttribute(): bool
    {
        return $this->atl_status === 'filer';
    }
}
