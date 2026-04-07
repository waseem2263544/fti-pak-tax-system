<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccJournalEntryLine extends Model
{
    protected $table = 'acc_journal_entry_lines';
    protected $fillable = ['journal_entry_id', 'account_id', 'debit', 'credit', 'description'];

    public function journalEntry() { return $this->belongsTo(AccJournalEntry::class, 'journal_entry_id'); }
    public function account() { return $this->belongsTo(AccAccount::class, 'account_id'); }
}
