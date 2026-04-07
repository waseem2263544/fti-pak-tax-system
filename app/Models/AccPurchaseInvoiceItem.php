<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccPurchaseInvoiceItem extends Model
{
    protected $table = 'acc_purchase_invoice_items';
    protected $fillable = ['purchase_invoice_id', 'account_id', 'description', 'quantity', 'unit_price', 'tax_rate', 'tax_amount', 'amount'];

    public function invoice() { return $this->belongsTo(AccPurchaseInvoice::class, 'purchase_invoice_id'); }
    public function account() { return $this->belongsTo(AccAccount::class, 'account_id'); }
}
