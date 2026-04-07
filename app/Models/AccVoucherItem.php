<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccVoucherItem extends Model
{
    protected $table = 'acc_voucher_items';
    protected $fillable = ['voucher_id', 'account_id', 'description', 'amount'];

    public function voucher() { return $this->belongsTo(AccVoucher::class, 'voucher_id'); }
    public function account() { return $this->belongsTo(AccAccount::class, 'account_id'); }
}
