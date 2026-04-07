<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccJournalEntry extends Model
{
    protected $table = 'acc_journal_entries';
    protected $fillable = ['entry_number', 'date', 'fiscal_year_id', 'reference', 'narration', 'source_type', 'source_id', 'total_amount', 'is_posted', 'is_reversed', 'reversed_by', 'created_by', 'posted_at'];
    protected $casts = ['date' => 'date', 'posted_at' => 'datetime', 'is_posted' => 'boolean', 'is_reversed' => 'boolean'];

    public function lines() { return $this->hasMany(AccJournalEntryLine::class, 'journal_entry_id'); }
    public function fiscalYear() { return $this->belongsTo(AccFiscalYear::class, 'fiscal_year_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function isBalanced()
    {
        $totals = $this->lines()->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        return round($totals->d ?? 0, 2) === round($totals->c ?? 0, 2);
    }

    public function post()
    {
        if (!$this->isBalanced()) return false;
        $this->update(['is_posted' => true, 'posted_at' => now()]);
        return true;
    }

    public static function nextNumber()
    {
        $prefix = AccAccount::setting('journal_prefix') ?? 'JV';
        $max = self::where('entry_number', 'like', $prefix . '-%')
            ->max(\DB::raw("CAST(SUBSTRING(entry_number, " . (strlen($prefix) + 2) . ") AS UNSIGNED)")) ?? 0;
        return $prefix . '-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }
}
