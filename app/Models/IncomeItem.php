<?php

namespace App\Models;

use App\Support\FbrSchema;
use Illuminate\Database\Eloquent\Model;

/**
 * One line of an income working: a disposal, a let property, an employer.
 *
 * The head's own fields live in `details`, because a capital gain and a salary
 * share almost nothing. `amount` is the figure that reaches the return; it is
 * added up from those fields, which is the only arithmetic done here. No tax
 * is computed anywhere.
 */
class IncomeItem extends Model
{
    protected $fillable = [
        'client_id', 'tax_year', 'head', 'description', 'wealth_line_id',
        'details', 'amount', 'treatment', 'tax_deducted', 'sort_order',
    ];

    protected $casts = [
        'details'  => 'array',
        'tax_year' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /** The asset this line relates to: the property let, the plot sold. */
    public function wealthLine()
    {
        return $this->belongsTo(WealthLine::class);
    }

    public function detail(string $key, $default = null)
    {
        return $this->details[$key] ?? $default;
    }

    /**
     * Work the line's figure out from its own fields.
     *
     * Every head names which of its fields add and which subtract, so a
     * property's net income and a disposal's gain come out of the same rule.
     */
    public function computeAmount(): float
    {
        $head = FbrSchema::incomeHeads()[$this->head] ?? null;

        if (!$head) {
            return (float) $this->amount;
        }

        $sum = fn(array $keys) => array_sum(array_map(
            fn($k) => (float) ($this->details[$k] ?? 0),
            $keys
        ));

        return $sum($head['computed'] ?? []) - $sum($head['less'] ?? []);
    }
}
