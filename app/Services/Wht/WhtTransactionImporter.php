<?php

namespace App\Services\Wht;

use App\Models\WhtCompany;
use App\Models\WhtParty;
use App\Models\WhtSection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads a vendor-payment sheet and works out what would be imported.
 *
 * Client sheets vary, so columns are matched by alias and anything absent is
 * derived: the section falls back to the party's default, the period to the
 * payment month, the basis to the party's usual mode.
 *
 * Tax is ALWAYS recomputed by WhtCalculator, never taken from the sheet. If the
 * sheet carries a tax figure it is compared and any disagreement is reported —
 * which is how a client's arithmetic errors get caught instead of imported.
 *
 * Nothing here writes; it returns rows with a verdict for the preview to show.
 */
class WhtTransactionImporter
{
    public const STATUS_OK = 'ok';
    public const STATUS_WARNING = 'warning';
    public const STATUS_BLOCKED = 'blocked';

    /** Header aliases, lower-cased and stripped of anything but a-z0-9. */
    private const ALIASES = [
        'payee_cnic_ntn' => ['cnic', 'ntn', 'cnicntn', 'ntncnic', 'payeecnicntn', 'taxnumber', 'registrationno', 'nic'],
        'payee_name'     => ['name', 'payee', 'vendor', 'supplier', 'party', 'payeename', 'vendorname',
                            'partyname', 'suppliername', 'nameofpayee', 'nameofvendor', 'nameofsupplier',
                            'beneficiary', 'contractor'],
        'payment_date'   => ['date', 'paymentdate', 'paidon', 'dateofpayment', 'transactiondate'],
        'period_month'   => ['period', 'taxperiod', 'month', 'periodmonth', 'taxmonth'],
        'section'        => ['section', 'paymentsection', 'us', 'usection', 'code', 'paymentcode'],
        'amount'         => ['amount', 'gross', 'grossamount', 'payment', 'value', 'amountofpayment', 'invoiceamount'],
        'amount_basis'   => ['basis', 'amountbasis', 'grossnet', 'type', 'mode'],
        'tax_withheld'   => ['tax', 'taxdeducted', 'taxwithheld', 'wht', 'withholdingtax', 'taxamount'],
        'remarks'        => ['remarks', 'note', 'notes', 'description', 'particulars', 'narration'],
    ];

    public function __construct(private WhtCalculator $calculator)
    {
    }

    /**
     * @return array{rows: Collection, headers: array, unmapped: array, summary: array}
     */
    public function analyse(WhtCompany $company, string $path): array
    {
        [$headerRow, $dataRows, $headerIndex] = $this->readSheet($path);

        [$map, $unmapped] = $this->mapHeaders($headerRow);

        // Match on CNIC/NTN first; fall back to a normalised name.
        $parties = $company->parties()->get();
        $byCnic = $parties->filter(fn($p) => filled($p->cnic_ntn))
            ->keyBy(fn($p) => $this->normaliseId($p->cnic_ntn));
        $byName = $parties->keyBy(fn($p) => $this->normaliseName($p->name));

        $sections = WhtSection::all();

        $rows = collect($dataRows)->map(function ($raw, $i) use ($map, $byCnic, $byName, $sections, $headerIndex) {
            // +2: one for the header line itself, one because sheet rows are 1-based.
            return $this->analyseRow($raw, $headerIndex + $i + 2, $map, $byCnic, $byName, $sections);
        })->filter()->values();

        return [
            'rows'     => $rows,
            'headers'  => $headerRow,
            'unmapped' => $unmapped,
            'summary'  => $this->summarise($rows),
        ];
    }

    /**
     * Same analysis, but for rows supplied directly rather than read from a
     * sheet — used by the MCP connector, where Claude has already parsed the
     * spreadsheet and sends structured rows.
     *
     * @param  array<int,array<string,mixed>>  $rows  keyed by the canonical field names
     */
    public function analyseRows(WhtCompany $company, array $rows): array
    {
        $fields = array_keys(self::ALIASES);
        $map = array_flip($fields);

        $parties = $company->parties()->get();
        $byCnic = $parties->filter(fn($p) => filled($p->cnic_ntn))
            ->keyBy(fn($p) => $this->normaliseId($p->cnic_ntn));
        $byName = $parties->keyBy(fn($p) => $this->normaliseName($p->name));

        $sections = WhtSection::all();

        $analysed = collect($rows)->map(function ($row, $i) use ($fields, $map, $byCnic, $byName, $sections) {
            // Flatten to the indexed shape analyseRow expects.
            $indexed = [];
            foreach ($fields as $n => $field) {
                $indexed[$n] = $row[$field] ?? '';
            }

            return $this->analyseRow($indexed, $i + 1, $map, $byCnic, $byName, $sections);
        })->filter()->values();

        return [
            'rows'     => $analysed,
            'headers'  => $fields,
            'unmapped' => [],
            'summary'  => $this->summarise($analysed),
        ];
    }

    private function analyseRow(array $raw, int $lineNo, array $map, $byCnic, $byName, $sections): ?array
    {
        $get = fn(string $field) => isset($map[$field]) ? trim((string) ($raw[$map[$field]] ?? '')) : '';

        $rawPayee = $get('payee_name') ?: $get('payee_cnic_ntn');

        // A row with neither a payee nor an amount is blank padding, not an error.
        if ($rawPayee === '' && $get('amount') === '') {
            return null;
        }

        $problems = [];   // block the row
        $warnings = [];   // import, but the user should look
        $notes = [];      // informational only

        // ── party ──
        $cnic = $this->normaliseId($get('payee_cnic_ntn'));
        $party = $cnic !== '' ? $byCnic->get($cnic) : null;

        if (!$party && $get('payee_name') !== '') {
            $party = $byName->get($this->normaliseName($get('payee_name')));

            if ($party && $cnic !== '' && $this->normaliseId($party->cnic_ntn) !== $cnic) {
                $warnings[] = 'Matched by name; the CNIC/NTN in the sheet differs from the one on file.';
            }
        }

        if (!$party) {
            $problems[] = 'Payee not found in this agent\'s parties — add them first.';
        }

        // ── date ──
        $date = $this->parseDate($get('payment_date'));

        if (!$date) {
            $problems[] = 'Payment date missing or unreadable.';
        }

        // ── period ──
        $period = $this->parseMonth($get('period_month')) ?? $date?->copy()->startOfMonth();

        // Deriving the period from the payment date is the normal case, so this
        // is informational — treating it as a warning would flag every row.
        if ($date && !$get('period_month')) {
            $notes[] = 'Tax period taken from the payment date.';
        }

        // ── section ──
        $section = $get('section') ?: $party?->default_section;

        if ($section && !$sections->firstWhere('section', $section)) {
            // Might be a payment code rather than a section label.
            $bySection = $sections->firstWhere('code', $section);

            if ($bySection) {
                $notes[] = "Read '{$section}' as payment code for section {$bySection->section}.";
                $section = $bySection->section;
            } else {
                $problems[] = "Section '{$section}' is not in the section list.";
            }
        }

        if (!$section) {
            $problems[] = 'No section given and the payee has no default section.';
        }

        // ── amount ──
        $amount = $this->parseNumber($get('amount'));

        if ($amount === null || $amount < 0) {
            $problems[] = 'Amount missing or not a number.';
        }

        $basis = strtolower($get('amount_basis'));
        $basis = str_contains($basis, 'net') ? 'net' : (str_contains($basis, 'gross') ? 'gross' : null);
        $basis ??= $party?->default_calc_mode ?? 'gross';

        // ── compute ──
        $result = null;

        if (!$problems && $party && $date && $amount !== null) {
            $result = $this->calculator->purchase([
                'party'        => $party,
                'section'      => $section,
                'period_month' => $period,
                'amount'       => $amount,
                'calc_mode'    => $basis,
            ]);

            if ($result['rate_source'] !== 'matrix') {
                $warnings[] = sprintf(
                    'No rate rule for %s / %s / %s in %s — tax computed at 0%%.',
                    $section, $party->category, $party->atl_status, $period->format('M Y')
                );
            }

            // The sheet's own tax figure is a cross-check, never the source.
            $sheetTax = $this->parseNumber($get('tax_withheld'));

            if ($sheetTax !== null && abs($sheetTax - $result['tax_withheld']) > 1.00) {
                $warnings[] = sprintf(
                    'Sheet says tax %s, calculated %s — difference %s.',
                    number_format($sheetTax, 2),
                    number_format($result['tax_withheld'], 2),
                    number_format($sheetTax - $result['tax_withheld'], 2)
                );
            }
        }

        $status = $problems
            ? self::STATUS_BLOCKED
            : ($warnings ? self::STATUS_WARNING : self::STATUS_OK);

        return [
            'line'         => $lineNo,
            'status'       => $status,
            'problems'     => $problems,
            'warnings'     => $warnings,
            'notes'        => $notes,
            'raw_payee'    => $rawPayee,
            'party_id'     => $party?->id,
            'party_name'   => $party?->name,
            'cnic_ntn'     => $party?->cnic_ntn ?: $get('payee_cnic_ntn'),
            'atl_status'   => $party?->atl_status,
            'section'      => $section,
            'period_month' => $period?->format('Y-m'),
            'payment_date' => $date?->toDateString(),
            'calc_mode'    => $basis,
            'input_amount' => $amount,
            'gross_amount' => $result['gross_amount'] ?? null,
            'tax_rate'     => $result['tax_rate'] ?? null,
            'tax_rate_id'  => $result['tax_rate_id'] ?? null,
            'rate_source'  => $result['rate_source'] ?? null,
            'tax_withheld' => $result['tax_withheld'] ?? null,
            'net_payment'  => $result['net_payment'] ?? null,
            'remarks'      => $get('remarks') ?: null,
        ];
    }

    // ── parsing helpers ──────────────────────────────────────────────────────

    /** @return array{0: array, 1: array, 2: int} header row, data rows, header's 0-based index */
    private function readSheet(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();

        $rows = $sheet->toArray(null, true, false, false);

        // Skip any title rows above the real header.
        $headerIndex = 0;

        foreach ($rows as $i => $row) {
            $filled = count(array_filter($row, fn($c) => trim((string) $c) !== ''));

            if ($filled >= 3) {
                $headerIndex = $i;
                break;
            }
        }

        $header = array_map(fn($c) => trim((string) $c), $rows[$headerIndex] ?? []);

        return [$header, array_slice($rows, $headerIndex + 1), $headerIndex];
    }

    /** @return array{0: array<string,int>, 1: array} field => column index, plus unmapped headers */
    private function mapHeaders(array $header): array
    {
        $map = [];
        $used = [];

        foreach ($header as $i => $label) {
            $key = preg_replace('/[^a-z0-9]/', '', strtolower($label));

            if ($key === '') {
                continue;
            }

            foreach (self::ALIASES as $field => $aliases) {
                if (isset($map[$field])) {
                    continue;
                }

                if (in_array($key, $aliases, true) || $key === $field) {
                    $map[$field] = $i;
                    $used[$i] = true;
                    break;
                }
            }
        }

        $unmapped = [];

        foreach ($header as $i => $label) {
            if (!isset($used[$i]) && trim((string) $label) !== '') {
                $unmapped[] = $label;
            }
        }

        return [$map, $unmapped];
    }

    private function parseDate(string $v): ?Carbon
    {
        $v = trim($v);

        if ($v === '') {
            return null;
        }

        // Excel serial dates survive setReadDataOnly as plain numbers.
        if (is_numeric($v) && (float) $v > 20000 && (float) $v < 60000) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v));
            } catch (\Throwable) {
                return null;
            }
        }

        // Pakistani sheets are overwhelmingly d/m/Y, which strtotime reads as m/d/Y.
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y', 'd-M-Y', 'j F Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $v);
                if ($d && $d->format($fmt) === $v) {
                    return $d->startOfDay();
                }
            } catch (\Throwable) {
                // try the next format
            }
        }

        try {
            return Carbon::parse($v)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseMonth(string $v): ?Carbon
    {
        $v = trim($v);

        if ($v === '') {
            return null;
        }

        foreach (['Y-m', 'm/Y', 'M Y', 'F Y', 'm-Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $v);
                if ($d) {
                    return $d->startOfMonth();
                }
            } catch (\Throwable) {
                // try the next format
            }
        }

        return $this->parseDate($v)?->startOfMonth();
    }

    private function parseNumber(string $v): ?float
    {
        $v = trim($v);

        if ($v === '') {
            return null;
        }

        // Strip thousands separators, currency text and stray spaces.
        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $v));

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function normaliseId(?string $v): string
    {
        return preg_replace('/[^0-9]/', '', (string) $v);
    }

    private function summarise(Collection $rows): array
    {
        return [
            'total'   => $rows->count(),
            'ok'      => $rows->where('status', self::STATUS_OK)->count(),
            'warning' => $rows->where('status', self::STATUS_WARNING)->count(),
            'blocked' => $rows->where('status', self::STATUS_BLOCKED)->count(),
            'gross'   => $rows->where('status', '!=', self::STATUS_BLOCKED)->sum('gross_amount'),
            'tax'     => $rows->where('status', '!=', self::STATUS_BLOCKED)->sum('tax_withheld'),
            // Only rows blocked *because of* the payee — not ones blocked for a
            // bad date that happen to name a known vendor.
            'unknown' => $rows->filter(fn($r) => collect($r['problems'])
                                   ->contains(fn($p) => str_contains($p, 'Payee not found')))
                              ->pluck('raw_payee')->filter()->unique()->values(),
        ];
    }

    private function normaliseName(?string $v): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $v));
    }
}
