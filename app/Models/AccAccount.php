<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccAccount extends Model
{
    use \App\Models\Concerns\LogsAccountingActivity;

    protected $table = 'acc_accounts';
    protected $fillable = ['code', 'name', 'type', 'sub_type', 'parent_id', 'description', 'is_system', 'is_active', 'opening_balance', 'opening_balance_type'];
    protected $casts = ['is_system' => 'boolean', 'is_active' => 'boolean'];

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id')->orderBy('code'); }
    public function journalLines() { return $this->hasMany(AccJournalEntryLine::class, 'account_id'); }

    public function getBalanceAttribute()
    {
        $result = $this->journalLines()
            ->whereHas('journalEntry', fn($q) => $q->where('is_posted', true))
            ->selectRaw('COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')
            ->first();

        $debit = $result->total_debit ?? 0;
        $credit = $result->total_credit ?? 0;

        // Add opening balance
        if ($this->opening_balance_type === 'debit') $debit += $this->opening_balance;
        elseif ($this->opening_balance_type === 'credit') $credit += $this->opening_balance;

        // Assets and Expenses are debit-nature (debit - credit)
        // Liabilities, Equity, Revenue are credit-nature (credit - debit)
        if (in_array($this->type, ['asset', 'expense'])) {
            return $debit - $credit;
        }
        return $credit - $debit;
    }

    public function isDebitNature() { return in_array($this->type, ['asset', 'expense']); }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeOfType($q, $type) { return $q->where('type', $type); }

    public static function setting($key)
    {
        return DB::table('acc_settings')->where('key', $key)->value('value');
    }

    /**
     * Resolve a control/default account id for a logical role. Tries the configured
     * setting first, then falls back to the well-known seeded account code, so posting
     * never silently breaks when a setting was never configured.
     */
    public static function resolveId($role)
    {
        static $map = [
            'accounts_receivable' => ['default_receivable_account', '1200'],
            'accounts_payable'    => ['default_payable_account', '2100'],
            'cash'                => ['default_cash_account', '1110'],
            'bank'                => ['default_bank_account', '1120'],
            'sales'               => ['default_sales_account', '4100'],
            'purchase'            => ['default_purchase_account', '5000'],
            'sales_tax'           => ['default_sales_tax_account', '2300'],
            'purchase_tax'        => ['default_purchase_tax_account', '1220'],
            'sales_discount'      => ['default_sales_discount_account', '4200'],
            'wht_receivable'      => ['default_wht_receivable_account', '1210'],
        ];

        if (!isset($map[$role])) {
            return self::setting($role) ?: self::setting($role . '_id');
        }

        [$settingKey, $fallbackCode] = $map[$role];
        $id = self::setting($settingKey);
        if ($id && self::whereKey($id)->exists()) {
            return $id;
        }
        return self::where('code', $fallbackCode)->value('id');
    }

    public static function getNextCode($type)
    {
        $prefixes = ['asset' => '1', 'liability' => '2', 'equity' => '3', 'revenue' => '4', 'expense' => '5'];
        $prefix = $prefixes[$type] ?? '9';
        $max = self::where('code', 'like', $prefix . '%')->max(DB::raw('CAST(code AS UNSIGNED)')) ?? ($prefix . '000');
        return (string)($max + 10);
    }
}
