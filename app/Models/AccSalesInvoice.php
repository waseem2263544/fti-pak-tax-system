<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccSalesInvoice extends Model
{
    protected $table = 'acc_sales_invoices';
    protected $fillable = ['invoice_number', 'client_id', 'date', 'due_date', 'reference', 'subtotal', 'tax_amount', 'discount_amount', 'total', 'amount_paid', 'balance_due', 'status', 'notes', 'terms', 'journal_entry_id', 'created_by'];
    protected $casts = ['date' => 'date', 'due_date' => 'date'];

    public function client() { return $this->belongsTo(Client::class); }
    public function items() { return $this->hasMany(AccSalesInvoiceItem::class, 'sales_invoice_id'); }
    public function journalEntry() { return $this->belongsTo(AccJournalEntry::class, 'journal_entry_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function receipts() { return $this->hasMany(AccVoucher::class, 'invoice_id')->where('invoice_type', 'sales'); }

    public static function nextNumber()
    {
        $prefix = AccAccount::setting('invoice_prefix') ?? 'INV';
        $max = self::max(\DB::raw("CAST(SUBSTRING(invoice_number, " . (strlen($prefix) + 2) . ") AS UNSIGNED)")) ?? 0;
        return $prefix . '-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }

    public function recalculate()
    {
        $this->subtotal = $this->items()->sum('amount');
        $this->tax_amount = $this->items()->sum('tax_amount');
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->balance_due = $this->total - $this->amount_paid;
        $this->save();
    }
}
