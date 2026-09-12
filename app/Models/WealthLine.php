<?php

namespace App\Models;

use App\Support\FbrSchema;
use Illuminate\Database\Eloquent\Model;

/**
 * One asset or liability belonging to a client.
 *
 * A line lasts across tax years. Nothing is typed onto the statement itself:
 * its figure for a year is the running total of everything recorded against it
 * up to that year, so a year with no movement carries the position forward,
 * which is what a wealth statement does anyway.
 */
class WealthLine extends Model
{
    protected $fillable = [
        'client_id', 'kind', 'code', 'balancing', 'section',
        'description', 'details', 'sort_order', 'disposed_in', 'notes',
    ];

    protected $casts = ['details' => 'array', 'balancing' => 'boolean'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function values()
    {
        return $this->hasMany(WealthValue::class);
    }

    public function movements()
    {
        return $this->hasMany(WealthMovement::class)->orderBy('tax_year')->orderBy('occurred_on')->orderBy('id');
    }

    public function movementsFor(int $taxYear)
    {
        return $this->movements->where('tax_year', $taxYear);
    }

    public static function headLabel(string $code): string
    {
        return FbrSchema::headLabel($code);
    }

    /** The head's own attributes, as IRIS asks for them. */
    public function detail(string $key, $default = null)
    {
        return $this->details[$key] ?? $default;
    }

    /** A one-line summary of the attributes, for the statement's description column. */
    public function attributeSummary(): string
    {
        $head = FbrSchema::head((string) $this->code);

        if (!$head || empty($this->details)) {
            return '';
        }

        $parts = [];

        foreach ($head['fields'] as $field) {
            $v = $this->details[$field['key']] ?? null;

            if ($v === null || $v === '') {
                continue;
            }

            $parts[] = $field['key'] === 'form' ? $v : "{$field['label']}: {$v}";
        }

        return implode(' · ', array_slice($parts, 0, 5));
    }

    /**
     * The figure for a year: everything recorded up to and including it.
     *
     * The balancing line is the exception - it is solved against the
     * reconciliation rather than accumulated.
     */
    public function amountFor(int $taxYear): ?string
    {
        if ($this->balancing) {
            return $this->values->firstWhere('tax_year', $taxYear)?->amount;
        }

        if ($this->relationLoaded('movements')) {
            $upto = $this->movements->where('tax_year', '<=', $taxYear);

            if ($upto->isNotEmpty()) {
                return (string) round($upto->sum(fn($m) => $m->signedAmount()), 2);
            }
        }

        // Lines entered before the statement became movement-driven.
        return $this->values->firstWhere('tax_year', $taxYear)?->amount;
    }

    /** Where the line stood at the start of the year. */
    public function openingFor(int $taxYear): float
    {
        return (float) ($this->amountFor($taxYear - 1) ?? 0);
    }
}
