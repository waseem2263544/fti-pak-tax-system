<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccVoucher extends Model
{
    protected $table = 'acc_vouchers';
    protected $fillable = ['voucher_number', 'type', 'date', 'client_id', 'contact_id', 'party_name', 'payment_account_id', 'amount', 'payment_method', 'cheque_number', 'reference', 'narration', 'status', 'invoice_id', 'invoice_type', 'journal_entry_id', 'created_by'];
    protected $casts = ['date' => 'date'];

    public function client() { return $this->belongsTo(Client::class); }
    public function contact() { return $this->belongsTo(AccContact::class, 'contact_id'); }
    public function paymentAccount() { return $this->belongsTo(AccAccount::class, 'payment_account_id'); }
    public function items() { return $this->hasMany(AccVoucherItem::class, 'voucher_id'); }
    public function journalEntry() { return $this->belongsTo(AccJournalEntry::class, 'journal_entry_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function scopePayments($q) { return $q->where('type', 'payment'); }
    public function scopeReceipts($q) { return $q->where('type', 'receipt'); }

    public static function nextPaymentNumber()
    {
        $prefix = AccAccount::setting('payment_prefix') ?? 'PV';
        $max = self::where('type', 'payment')->max(\DB::raw("CAST(SUBSTRING(voucher_number, " . (strlen($prefix) + 2) . ") AS UNSIGNED)")) ?? 0;
        return $prefix . '-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }

    public static function nextReceiptNumber()
    {
        $prefix = AccAccount::setting('receipt_prefix') ?? 'RV';
        $max = self::where('type', 'receipt')->max(\DB::raw("CAST(SUBSTRING(voucher_number, " . (strlen($prefix) + 2) . ") AS UNSIGNED)")) ?? 0;
        return $prefix . '-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getPartyNameAttribute($value)
    {
        if ($value) return $value;
        if ($this->type === 'receipt' && $this->client) return $this->client->name;
        if ($this->type === 'payment' && $this->contact) return $this->contact->name;
        return 'Unknown';
    }
}
