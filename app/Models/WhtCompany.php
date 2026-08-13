<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhtCompany extends Model
{
    protected $table = 'wht_companies';
    protected $fillable = ['name', 'ntn_cnic', 'address', 'logo_path', 'client_id', 'is_active', 'legacy_id'];
    protected $casts = ['is_active' => 'boolean'];

    public function parties() { return $this->hasMany(WhtParty::class, 'wht_company_id'); }
    public function vendors() { return $this->parties()->whereIn('type', ['vendor', 'both']); }
    public function employees() { return $this->parties()->whereIn('type', ['employee', 'both']); }
    public function purchases() { return $this->hasMany(WhtPurchase::class, 'wht_company_id'); }
    public function salaries() { return $this->hasMany(WhtSalary::class, 'wht_company_id'); }
    public function challans() { return $this->hasMany(WhtChallan::class, 'wht_company_id'); }
    public function client() { return $this->belongsTo(Client::class); }

    public function users()
    {
        return $this->belongsToMany(User::class, 'wht_company_user', 'wht_company_id', 'user_id')
            ->withPivot(['can_view', 'can_create', 'can_edit', 'can_delete']);
    }
}
