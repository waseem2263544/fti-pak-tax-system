<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccSalesInvoiceItem extends Model
{
    protected $table = 'acc_sales_invoice_items';
    protected $fillable = ['sales_invoice_id', 'account_id', 'description', 'quantity', 'unit_price', 'tax_rate', 'tax_amount', 'discount', 'amount'];

    public function invoice() { return $this->belongsTo(AccSalesInvoice::class, 'sales_invoice_id'); }
    public function account() { return $this->belongsTo(AccAccount::class, 'account_id'); }
}
