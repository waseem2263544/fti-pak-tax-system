<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccContact extends Model
{
    protected $table = 'acc_contacts';
    protected $fillable = ['name', 'type', 'email', 'phone', 'address', 'ntn', 'strn', 'opening_balance', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function purchaseInvoices() { return $this->hasMany(AccPurchaseInvoice::class, 'contact_id'); }
    public function payments() { return $this->hasMany(AccVoucher::class, 'contact_id')->where('type', 'payment'); }
}
