<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One asset or liability belonging to a client.
 *
 * A line lasts across tax years; its figure for each year lives in
 * wealth_values. That is what makes the comparative statement possible without
 * matching rows by their description.
 */
class WealthLine extends Model
{
    protected $fillable = ['client_id', 'kind', 'code', 'section', 'description', 'details', 'sort_order', 'disposed_in', 'notes'];

    protected $casts = ['details' => 'array'];

    /** Kept for rows created before the move onto FBR codes. */
    public const LEGACY_SECTION = 'legacy';

    public static function headLabel(string $code): string
    {
        return \App\Support\FbrSchema::headLabel($code);
    }

    /** The head's own attributes, as IRIS asks for them. */
    public function detail(string $key, $default = null)
    {
        return $this->details[$key] ?? $default;
    }

    /** A one-line summary of the attributes, for the statement's description column. */
    public function attributeSummary(): string
    {
        $head = \App\Support\FbrSchema::head((string) $this->code);

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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function values()
    {
        return $this->hasMany(WealthValue::class);
    }

    /** The figure declared for this line in a given year, or null if none. */
    public function amountFor(int $taxYear): ?string
    {
        return $this->values->firstWhere('tax_year', $taxYear)?->amount;
    }
}
