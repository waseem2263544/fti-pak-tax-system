<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterNumber extends Model
{
    protected $fillable = ['date', 'reference', 'sequence_no', 'year', 'client_id', 'description'];

    protected $casts = ['date' => 'date'];

    public function client() { return $this->belongsTo(Client::class); }

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
