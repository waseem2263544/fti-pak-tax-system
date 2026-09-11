<?php

namespace App\Services\Wht;

use App\Models\WhtCompany;
use App\Models\WhtSection;
use Illuminate\Support\Carbon;

/**
 * A PSID "batch": every transaction of one kind for one agent and tax period.
 *
 * The firm raises two challans a month per agent — one for all vendor
 * withholding (spanning whatever sections apply) and one for salaries — so the
 * batch is keyed on kind + period, not on section.
 *
 * Shared by the Prepare PSID screen and the read-only API so the two cannot
 * disagree about what is in a batch.
 */
class WhtPsidBatcher
{
    public const KINDS = ['purchases', 'salaries'];

    /** The underlying query, so callers can also update in bulk. */
    public function query(WhtCompany $company, Carbon $monthStart, string $kind)
    {
        return $kind === 'salaries'
            ? $company->salaries()->whereDate('salary_month', $monthStart)
            : $company->purchases()->whereDate('period_month', $monthStart);
    }

    /**
     * Punctuate a tax number the way IRIS expects.
     *
     * CNIC: 13 digits as xxxxx-xxxxxxx-x
     * NTN:  8 digits as xxxxxxx-x
     *
     * Anything else is passed through as stored — better to send what we have
     * and let IRIS name the row than to mangle an unusual number silently.
     */
    public static function formatTaxNumber(string $digits, string $raw = ''): string
    {
        return match (strlen($digits)) {
            13 => substr($digits, 0, 5) . '-' . substr($digits, 5, 7) . '-' . substr($digits, 12, 1),
            8  => substr($digits, 0, 7) . '-' . substr($digits, 7, 1),
            0  => '',
            default => $raw !== '' ? $raw : $digits,
        };
    }

    public function batch(WhtCompany $company, Carbon $monthStart, string $kind): array
    {
        $isSalary = $kind === 'salaries';

        $items = $this->query($company, $monthStart, $kind)
            ->with($isSalary ? 'employee' : 'party')
            ->orderBy('payment_date')
            ->get();

        $rows = $this->mapRows($items, $kind);

        return $this->summariseBatch($rows, $kind);
    }

    /**
     * Rows for an explicit set of transaction ids — the manual selection the
     * Prepare PSID screen works from.
     */
    public function rowsForIds(WhtCompany $company, string $kind, array $ids)
    {
        if ($ids === []) {
            return collect();
        }

        $isSalary = $kind === 'salaries';

        $items = ($isSalary ? $company->salaries() : $company->purchases())
            ->with($isSalary ? 'employee' : 'party')
            ->whereIn('id', $ids)
            ->orderBy('payment_date')
            ->get();

        return $this->mapRows($items, $kind);
    }

    /** Turn transactions into the flat rows the workbook and preview use. */
    public function mapRows($items, string $kind)
    {
        $isSalary = $kind === 'salaries';
        $sections = WhtSection::all();

        return $items->map(function ($t) use ($isSalary, $sections) {
            $party = $isSalary ? $t->employee : $t->party;
            $section = $t->section ?: ($isSalary ? '149' : '');
            $meta = $sections->firstWhere('section', $section);

            // FBR's template splits the tax number into two columns, and IRIS
            // validates the punctuation: a CNIC must read xxxxx-xxxxxxx-x and an
            // NTN xxxxxxx-x. Parties are stored as bare digits, so format here.
            $raw = (string) ($party?->cnic_ntn ?? '');
            $digits = preg_replace('/[^0-9]/', '', $raw);
            $isCnic = strlen($digits) === 13;
            $formatted = self::formatTaxNumber($digits, $raw);

            return [
                'id'             => $t->id,
                'section'        => $section,
                'section_code'   => $meta?->code ?? '',
                'payment_nature' => $meta?->payment_nature ?? ($isSalary ? 'Salary' : ''),
                'payee_name'     => $party?->name,
                'payee_cnic_ntn' => $party?->cnic_ntn,
                'taxpayer_ntn'   => $isCnic ? null : ($formatted ?: null),
                'taxpayer_cnic'  => $isCnic ? $formatted : null,
                'taxpayer_status' => match ($party?->category) {
                    'company'    => 'COMPANY',
                    'aop'        => 'AOP',
                    'individual' => 'INDIVIDUAL',
                    default      => null,
                },
                'city'           => $party?->city,
                'address'        => $party?->address,
                // FBR wants a trading name; only companies and AOPs reliably have one.
                'business_name'  => in_array($party?->category, ['company', 'aop'], true) ? $party?->name : null,
                'atl_status'     => $party?->atl_status === 'non-filer' ? 'Non-filer' : 'Filer',
                'payment_date'   => $t->payment_date?->toDateString(),
                'gross_amount'   => (float) ($isSalary ? $t->total_salary : $t->gross_amount),
                'taxable_salary' => $isSalary ? (float) $t->taxable_salary : null,
                'exempt_amount'  => $isSalary ? (float) $t->exempt_amount : null,
                'tax_rate'       => $isSalary ? null : (float) $t->tax_rate,
                'tax_withheld'   => (float) ($isSalary ? $t->tax_deducted : $t->tax_withheld),
                'psid_no'        => $t->psid_no,
                'cpr_no'         => $t->cpr_no,
                'cpr_date'       => $t->cpr_date?->toDateString(),
                'period'         => $isSalary ? $t->salary_month?->format('Y-m') : $t->period_month?->format('Y-m'),
            ];
        });
    }

    /** Headline figures for a set of rows. */
    public function summariseBatch($rows, string $kind): array
    {
        $isSalary = $kind === 'salaries';
        $count = $rows->count();
        $withPsid = $rows->filter(fn($r) => filled($r['psid_no']))->count();
        $withCpr = $rows->filter(fn($r) => filled($r['cpr_no']))->count();

        return [
            'kind'      => $kind,
            'label'     => $isSalary ? 'Salaries' : 'Vendors & Suppliers',
            'rows'      => $rows,
            'count'     => $count,
            'payees'    => $rows->pluck('payee_cnic_ntn')->filter()->unique()->count(),
            'gross'     => $rows->sum('gross_amount'),
            'tax'       => $rows->sum('tax_withheld'),
            'sections'  => $rows->pluck('section')->filter()->unique()->sort()->values(),
            'psid_no'   => $rows->pluck('psid_no')->filter()->unique()->values(),
            'cpr_no'    => $rows->pluck('cpr_no')->filter()->unique()->values(),
            'with_psid' => $withPsid,
            'with_cpr'  => $withCpr,
            'status'    => match (true) {
                $count === 0         => 'empty',
                $withCpr === $count  => 'paid',
                $withPsid === $count => 'psid',
                $withPsid > 0        => 'partial',
                default              => 'pending',
            },
        ];
    }
}
