<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Global withholding rate matrix, versioned by tax period month.
 *
 * Rates are statutory, so they are not scoped to a company. A rate applies to a
 * transaction whose PERIOD MONTH falls inside [effective_from, effective_to] —
 * deliberately not its payment date, so a June liability deposited in August
 * still uses June's rate.
 */
class WhtTaxRate extends Model
{
    protected $table = 'wht_tax_rates';
    protected $fillable = [
        'section', 'goods_type', 'category', 'atl_status', 'rate',
        'effective_from', 'effective_to', 'notes',
    ];
    protected $casts = [
        'rate'           => 'decimal:3',
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    /**
     * Transactions that were saved against this rule.
     */
    public function purchases()
    {
        return $this->hasMany(WhtPurchase::class, 'tax_rate_id');
    }

    /**
     * Normalise any date to the first day of its month.
     */
    public static function monthStart($date): Carbon
    {
        return Carbon::parse($date)->startOfMonth();
    }

    /**
     * Rates in force during the given period month.
     */
    public function scopeInForce($q, $periodMonth)
    {
        $month = static::monthStart($periodMonth)->toDateString();

        return $q->whereDate('effective_from', '<=', $month)
                 ->where(fn($w) => $w->whereNull('effective_to')->orWhereDate('effective_to', '>=', $month));
    }

    /**
     * Find the rate for one (section, category, ATL status) combination in a
     * given period month. Falls back to the blank goods_type row when no
     * goods-type-specific rule exists. If windows overlap, the one that started
     * most recently wins.
     */
    public static function resolve(
        ?string $section,
        ?string $category,
        ?string $atlStatus,
        $periodMonth,
        ?string $goodsType = null
    ): ?self {
        if (!$section || !$category || !$atlStatus) {
            return null;
        }

        $base = static::query()
            ->where('section', $section)
            ->where('category', $category)
            ->where('atl_status', $atlStatus)
            ->inForce($periodMonth)
            ->orderByDesc('effective_from');

        if ($goodsType) {
            $match = (clone $base)->where('goods_type', $goodsType)->first();
            if ($match) {
                return $match;
            }
        }

        return $base->where('goods_type', '')->first();
    }
}
