<?php

namespace App\Services\Wht;

use App\Models\WhtCompany;
use App\Models\WhtSection;

/**
 * Reconciles a quarter's withholding against what was actually deposited.
 *
 * Statements are filed quarterly by FBR payment code; deposits happen monthly by
 * challan. This puts the two on the same axis — code by code — so the gap is
 * visible before the statement is filed rather than after FBR queries it.
 *
 * "Deposited" means a CPR has been recorded against the entry. A PSID alone is
 * a challan raised, not money paid.
 */
class WhtReconciler
{
    public function reconcile(WhtCompany $company, WhtPeriod $period): array
    {
        $sections = WhtSection::all();

        $purchases = $company->purchases()->with('party')
            ->whereBetween('period_month', [$period->from, $period->to])->get();

        $salaries = $company->salaries()->with('employee')
            ->whereBetween('salary_month', [$period->from, $period->to])->get();

        $byCode = [];

        foreach ($purchases as $p) {
            $this->add($byCode, $sections, $p->section, (float) $p->gross_amount,
                (float) $p->tax_withheld, filled($p->cpr_no), $p->party?->id,
                $p->period_month?->format('Y-m'));
        }

        foreach ($salaries as $s) {
            $this->add($byCode, $sections, $s->section ?: '149', (float) $s->total_salary,
                (float) $s->tax_deducted, filled($s->cpr_no), $s->employee?->id,
                $s->salary_month?->format('Y-m'));
        }

        // Sort by code so it reads the way the statement does.
        uasort($byCode, fn($a, $b) => strcmp((string) $a['code'], (string) $b['code']));

        $rows = collect(array_values($byCode))->map(function ($r) {
            $r['payees'] = count($r['payee_ids']);
            $r['gap'] = round($r['tax'] - $r['deposited'], 2);
            unset($r['payee_ids']);

            return $r;
        });

        // Month-by-month, since deposits are monthly even when filing is quarterly.
        $monthly = [];

        foreach ($period->months() as $m) {
            $key = $m->format('Y-m');
            $monthly[$key] = [
                'label'     => $m->format('M Y'),
                'withheld'  => 0.0,
                'deposited' => 0.0,
                'entries'   => 0,
            ];
        }

        foreach ($purchases as $p) {
            $k = $p->period_month?->format('Y-m');
            if (!isset($monthly[$k])) continue;
            $monthly[$k]['withheld'] += (float) $p->tax_withheld;
            $monthly[$k]['entries']++;
            if (filled($p->cpr_no)) $monthly[$k]['deposited'] += (float) $p->tax_withheld;
        }

        foreach ($salaries as $s) {
            $k = $s->salary_month?->format('Y-m');
            if (!isset($monthly[$k])) continue;
            $monthly[$k]['withheld'] += (float) $s->tax_deducted;
            $monthly[$k]['entries']++;
            if (filled($s->cpr_no)) $monthly[$k]['deposited'] += (float) $s->tax_deducted;
        }

        foreach ($monthly as $k => $m) {
            $monthly[$k]['gap'] = round($m['withheld'] - $m['deposited'], 2);
        }

        // What is holding the gap open.
        $noPsid = $purchases->filter(fn($p) => blank($p->psid_no))->count()
                + $salaries->filter(fn($s) => blank($s->psid_no))->count();

        $psidNoCpr = $purchases->filter(fn($p) => filled($p->psid_no) && blank($p->cpr_no))->count()
                   + $salaries->filter(fn($s) => filled($s->psid_no) && blank($s->cpr_no))->count();

        $noPsidTax = $purchases->filter(fn($p) => blank($p->psid_no))->sum('tax_withheld')
                   + $salaries->filter(fn($s) => blank($s->psid_no))->sum('tax_deducted');

        $psidNoCprTax = $purchases->filter(fn($p) => filled($p->psid_no) && blank($p->cpr_no))->sum('tax_withheld')
                      + $salaries->filter(fn($s) => filled($s->psid_no) && blank($s->cpr_no))->sum('tax_deducted');

        // Challans touching this period, so the CPR numbers can be checked off.
        $challans = collect();

        foreach ([$purchases, $salaries] as $set) {
            foreach ($set->whereNotNull('cpr_no') as $t) {
                if (blank($t->cpr_no)) continue;
                $key = $t->cpr_no;
                $tax = (float) ($t->tax_withheld ?? $t->tax_deducted);
                $challans[$key] = ($challans[$key] ?? 0) + $tax;
            }
        }

        return [
            'period'   => $period,
            'rows'     => $rows,
            'monthly'  => $monthly,
            'totals'   => [
                'gross'     => round($rows->sum('gross'), 2),
                'tax'       => round($rows->sum('tax'), 2),
                'deposited' => round($rows->sum('deposited'), 2),
                'gap'       => round($rows->sum('gap'), 2),
                'entries'   => $rows->sum('entries'),
            ],
            'exceptions' => [
                'no_psid'          => $noPsid,
                'no_psid_tax'      => round((float) $noPsidTax, 2),
                'psid_no_cpr'      => $psidNoCpr,
                'psid_no_cpr_tax'  => round((float) $psidNoCprTax, 2),
                'uncoded'          => $rows->filter(fn($r) => $r['code'] === '')->pluck('section')->values(),
            ],
            'challans' => collect($challans)->map(fn($amt, $cpr) => ['cpr_no' => $cpr, 'tax' => round($amt, 2)])
                              ->sortKeys()->values(),
        ];
    }

    private function add(array &$byCode, $sections, ?string $section, float $gross, float $tax, bool $deposited, $payeeId, ?string $month): void
    {
        $section = $section ?: '—';
        $meta = $sections->firstWhere('section', $section);
        $code = $meta?->code ?? '';
        $key = $code !== '' ? $code : 'x:' . $section;

        if (!isset($byCode[$key])) {
            $byCode[$key] = [
                'code'      => $code,
                'section'   => $section,
                'nature'    => $meta?->payment_nature ?? '',
                'gross'     => 0.0,
                'tax'       => 0.0,
                'deposited' => 0.0,
                'entries'   => 0,
                'payee_ids' => [],
            ];
        }

        $byCode[$key]['gross'] += $gross;
        $byCode[$key]['tax'] += $tax;
        $byCode[$key]['entries']++;

        if ($deposited) {
            $byCode[$key]['deposited'] += $tax;
        }

        if ($payeeId) {
            $byCode[$key]['payee_ids'][$payeeId] = true;
        }
    }
}
