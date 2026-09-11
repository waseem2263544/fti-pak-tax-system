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

    /**
     * Agents a user may open. Admins see every active one; everyone else sees
     * what their wht_company_user grant allows.
     */
    public static function accessibleTo($user)
    {
        if (!$user) {
            return static::whereRaw('1 = 0')->get();
        }

        $query = static::query()->where('is_active', true)->orderBy('name');

        if (!$user->hasRole('admin')) {
            $query->whereHas('users', fn($q) => $q
                ->where('users.id', $user->id)
                ->where('wht_company_user.can_view', true));
        }

        return $query->get();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'wht_company_user', 'wht_company_id', 'user_id')
            ->withPivot(['can_view', 'can_create', 'can_edit', 'can_delete']);
    }
}
