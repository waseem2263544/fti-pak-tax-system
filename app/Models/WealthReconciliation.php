<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Reconciliation of net assets for one client and year.
 *
 * The app only adds these figures up and reports whether the sources cover
 * what the increase in wealth requires. It does not derive any of them.
 */
class WealthReconciliation extends Model
{
    protected $fillable = [
        'client_id', 'tax_year', 'opening_wealth',
        'personal_expenses', 'gifts_family', 'other_outflows',
        'income_normal', 'income_exempt', 'income_ftr',
        'foreign_remittance', 'gift_received', 'other_sources', 'notes',
    ];

    protected $casts = ['tax_year' => 'integer'];

    public const OUTFLOWS = [
        'personal_expenses' => 'Personal and household expenses',
        'gifts_family'      => 'Gifts / family arrangement',
        'other_outflows'    => 'Other outflows',
    ];

    public const SOURCES = [
        'income_normal'      => 'Income declared, subject to normal tax',
        'income_exempt'      => 'Income declared, exempt from tax',
        'income_ftr'         => 'Income attributable to receipts under final tax',
        'foreign_remittance' => 'Foreign remittance',
        'gift_received'      => 'Gifts received',
        'other_sources'      => 'Other inflows',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getTotalOutflowsAttribute(): float
    {
        return array_sum(array_map(fn($k) => (float) $this->{$k}, array_keys(self::OUTFLOWS)));
    }

    public function getTotalSourcesAttribute(): float
    {
        return array_sum(array_map(fn($k) => (float) $this->{$k}, array_keys(self::SOURCES)));
    }
}
