<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccFiscalYear extends Model
{
    protected $table = 'acc_fiscal_years';
    protected $fillable = ['name', 'start_date', 'end_date', 'is_closed', 'is_active'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'is_closed' => 'boolean', 'is_active' => 'boolean'];

    public function journalEntries() { return $this->hasMany(AccJournalEntry::class, 'fiscal_year_id'); }

    public static function getForDate($date)
    {
        return self::where('start_date', '<=', $date)->where('end_date', '>=', $date)->first();
    }

    public static function active()
    {
        return self::where('is_active', true)->first();
    }
}
