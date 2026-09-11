<?php

namespace App\Services\Wht;

use Illuminate\Support\Carbon;

/**
 * Tax-year quarters.
 *
 * Pakistan's tax year runs July to June and is named for the year it ends in,
 * so TY2026 Q1 is Jul–Sep 2025 and Q4 is Apr–Jun 2026. Withholding statements
 * are filed quarterly while deposits are made monthly, which is why a quarter
 * has to be a first-class period here rather than three separate months.
 */
class WhtPeriod
{
    /** Month in which each quarter starts, relative to the tax year. */
    private const QUARTER_START = [1 => 7, 2 => 10, 3 => 1, 4 => 4];

    public readonly Carbon $from;
    public readonly Carbon $to;

    private function __construct(
        Carbon $from,
        Carbon $to,
        public readonly string $label,
        public readonly string $type,
        public readonly ?int $taxYear = null,
        public readonly ?int $quarter = null,
    ) {
        $this->from = $from->copy()->startOfMonth();
        $this->to = $to->copy()->startOfMonth();
    }

    public static function quarter(int $taxYear, int $quarter): self
    {
        $quarter = max(1, min(4, $quarter));
        $month = self::QUARTER_START[$quarter];

        // Q1 and Q2 fall in the calendar year before the tax year's name.
        $year = $quarter <= 2 ? $taxYear - 1 : $taxYear;

        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->addMonths(2);

        return new self(
            $from, $to,
            sprintf('Q%d TY%d (%s – %s)', $quarter, $taxYear, $from->format('M Y'), $to->format('M Y')),
            'quarter', $taxYear, $quarter
        );
    }

    public static function month(string $month): self
    {
        $from = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

        return new self($from, $from, $from->format('F Y'), 'month');
    }

    /** Which tax-year quarter a given month falls in. */
    public static function forMonth(Carbon $month): self
    {
        $m = (int) $month->format('n');
        $y = (int) $month->format('Y');

        $taxYear = $m >= 7 ? $y + 1 : $y;
        $quarter = match (true) {
            $m >= 7 && $m <= 9   => 1,
            $m >= 10 && $m <= 12 => 2,
            $m >= 1 && $m <= 3   => 3,
            default              => 4,
        };

        return self::quarter($taxYear, $quarter);
    }

    /** Resolve whatever the request asked for, defaulting to the current quarter. */
    public static function fromRequest($request): self
    {
        if ($request->filled('month')) {
            return self::month($request->get('month'));
        }

        if ($request->filled('tax_year') && $request->filled('quarter')) {
            return self::quarter((int) $request->get('tax_year'), (int) $request->get('quarter'));
        }

        return self::forMonth(now()->startOfMonth());
    }

    /** Every month in the period, oldest first. */
    public function months(): array
    {
        $months = [];

        for ($m = $this->from->copy(); $m->lessThanOrEqualTo($this->to); $m->addMonth()) {
            $months[] = $m->copy();
        }

        return $months;
    }

    public function isQuarter(): bool
    {
        return $this->type === 'quarter';
    }

    public function slug(): string
    {
        return $this->isQuarter()
            ? sprintf('TY%d-Q%d', $this->taxYear, $this->quarter)
            : $this->from->format('Y-m');
    }

    /** Query params that reproduce this period. */
    public function query(): array
    {
        return $this->isQuarter()
            ? ['tax_year' => $this->taxYear, 'quarter' => $this->quarter]
            : ['month' => $this->from->format('Y-m')];
    }
}
