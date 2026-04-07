<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccPurchaseInvoice extends Model
{
    protected $table = 'acc_purchase_invoices';
    protected $fillable = ['bill_number', 'contact_id', 'vendor_name', 'vendor_invoice_no', 'date', 'due_date', 'subtotal', 'tax_amount', 'total', 'amount_paid', 'balance_due', 'status', 'notes', 'journal_entry_id', 'created_by'];
    protected $casts = ['date' => 'date', 'due_date' => 'date'];

    public function contact() { return $this->belongsTo(AccContact::class, 'contact_id'); }
    public function items() { return $this->hasMany(AccPurchaseInvoiceItem::class, 'purchase_invoice_id'); }
    public function journalEntry() { return $this->belongsTo(AccJournalEntry::class, 'journal_entry_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function payments() { return $this->hasMany(AccVoucher::class, 'invoice_id')->where('invoice_type', 'purchase'); }

    public static function nextNumber()
    {
        $prefix = AccAccount::setting('bill_prefix') ?? 'BILL';
        $max = self::max(\DB::raw("CAST(SUBSTRING(bill_number, " . (strlen($prefix) + 2) . ") AS UNSIGNED)")) ?? 0;
        return $prefix . '-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }

    public function recalculate()
    {
        $this->subtotal = $this->items()->sum('amount');
        $this->tax_amount = $this->items()->sum('tax_amount');
        $this->total = $this->subtotal + $this->tax_amount;
        $this->balance_due = $this->total - $this->amount_paid;
        $this->save();
    }
}
