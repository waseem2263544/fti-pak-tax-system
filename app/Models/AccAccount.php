<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccAccount extends Model
{
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

    public static function getNextCode($type)
    {
        $prefixes = ['asset' => '1', 'liability' => '2', 'equity' => '3', 'revenue' => '4', 'expense' => '5'];
        $prefix = $prefixes[$type] ?? '9';
        $max = self::where('code', 'like', $prefix . '%')->max(DB::raw('CAST(code AS UNSIGNED)')) ?? ($prefix . '000');
        return (string)($max + 10);
    }
}
