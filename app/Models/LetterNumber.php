<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterNumber extends Model
{
    protected $fillable = ['date', 'raw_date', 'reference', 'sequence_no', 'year', 'client_id', 'client_name', 'description', 'source'];

    protected $casts = ['date' => 'date'];

    public function client() { return $this->belongsTo(Client::class); }

    /** Who the letter was for; the register often names someone who is not a client. */
    public function getPartyNameAttribute(): string
    {
        return $this->client?->name ?? ($this->client_name ?: '—');
    }

    public static function nextSequence()
    {
        $year = now()->year;
        $last = self::where('year', $year)->max('sequence_no') ?? 0;
        return $last + 1;
    }

    public static function generateReference()
    {
        $year = now()->year;
        $seq = self::nextSequence();
        return 'FTI/' . str_pad($seq, 3, '0', STR_PAD_LEFT) . '/' . $year;
    }
}
