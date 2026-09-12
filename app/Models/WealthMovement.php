<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Something that happened to an asset during a year: construction on a plot,
 * a further instalment, a part disposal.
 *
 * The statement carries the closing figure; this is the working that explains
 * how it got there, which is what has to stand up if the year is queried.
 */
class WealthMovement extends Model
{
    protected $fillable = ['wealth_line_id', 'tax_year', 'kind', 'occurred_on', 'note', 'amount'];

    protected $casts = ['tax_year' => 'integer', 'occurred_on' => 'date'];

    public const KINDS = [
        'addition' => 'Addition',
        'disposal' => 'Disposal',
    ];

    public function line()
    {
        return $this->belongsTo(WealthLine::class, 'wealth_line_id');
    }

    /** Additions add to cost, disposals take away. */
    public function signedAmount(): float
    {
        return $this->kind === 'disposal' ? -(float) $this->amount : (float) $this->amount;
    }
}
