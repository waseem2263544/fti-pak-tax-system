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
    protected $fillable = ['client_id', 'kind', 'section', 'description', 'sort_order', 'disposed_in', 'notes'];

    /**
     * Sections, in the order a wealth statement presents them.
     *
     * Labels follow the firm's own working papers; the grouping follows what
     * IRIS asks for, so a line can be read straight onto the return.
     */
    public const SECTIONS = [
        'asset' => [
            'agricultural_property'   => 'Agricultural property',
            'immovable_property'      => 'Commercial, industrial, residential property (non-business)',
            'foreign_immovable'       => 'Foreign immoveable property',
            'business_capital'        => 'Business capital',
            'foreign_business_capital'=> 'Foreign business capital',
            'financial_assets'        => 'Financial assets & investments (non-business)',
            'bank_accounts'           => 'Cash at bank',
            'cash_in_hand'            => 'Cash in hand',
            'motor_vehicles'          => 'Motor vehicles',
            'household_effects'       => 'Household effects',
            'assets_in_others_name'   => "Assets in the name of others",
            'receivables'             => 'Receivables / debtors',
            'other_assets'            => 'Any other assets',
        ],
        'liability' => [
            'creditors'               => 'Creditors / payables (borrowing, loans, credit)',
            'other_liabilities'       => 'Any other liabilities',
        ],
    ];

    public static function sectionLabel(string $section): string
    {
        return self::SECTIONS['asset'][$section]
            ?? self::SECTIONS['liability'][$section]
            ?? $section;
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
