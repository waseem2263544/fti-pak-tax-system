<?php

namespace App\Services\Wht;

use App\Models\WhtCompany;
use App\Models\WhtSection;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Fills FBR's "Withholding Statement" workbook for a tax period — the s.165
 * filing and reconciliation file.
 *
 * This is NOT the ePayments/PSID template: it identifies each line by FBR CODE
 * rather than section label, carries a transaction date and exemption code, and
 * splits the payee's tax number across a registration and an identification
 * column.
 *
 * The firm's own .xlsm is used as the template and written back as .xlsm, so the
 * validation macro inside it survives. That matters: the macro flags bad rows
 * locally, before anything is uploaded to IRIS.
 */
class WhtStatementFiler
{
    public const TEMPLATE = 'templates/wht-statement-template.xlsm';

    private const SHEET = 'Withholding Data';
    private const FIRST_DATA_ROW = 4;

    /** Where the agent's own details sit in the template's first row. */
    private const CELL_AGENT_REG = 'C1';
    private const CELL_OFFICE_REF = 'G1';

    public static function templatePath(): string
    {
        return resource_path(self::TEMPLATE);
    }

    public static function templateExists(): bool
    {
        return is_readable(self::templatePath());
    }

    /**
     * @param  \Illuminate\Support\Collection  $purchases
     * @param  \Illuminate\Support\Collection  $salaries
     */
    public function build(WhtCompany $company, Carbon $month, $purchases, $salaries): Spreadsheet
    {
        $book = IOFactory::createReader('Xlsx')->load(self::templatePath());
        $sheet = $book->getSheetByName(self::SHEET);

        if (!$sheet) {
            throw new \RuntimeException('The statement template has no "' . self::SHEET . '" sheet.');
        }

        // Agent details. The office reference is not something the app holds, so
        // the template's placeholder is cleared rather than left to look real.
        $sheet->setCellValueExplicit(self::CELL_AGENT_REG, (string) ($company->ntn_cnic ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValue(self::CELL_OFFICE_REF, null);

        // The template ships with its macro's last verdict still in row 2, which
        // would otherwise read "Invalid Records 1" on a file nobody has validated.
        $sheet->setCellValue('B2', null);
        $sheet->setCellValue('F2', null);
        $sheet->setCellValue('H2', null);

        // The template ships with one blank row the macro has already flagged.
        // Clear it cell by cell rather than removing rows: the sheet carries
        // data validation to its maximum row, so getHighestRow() is enormous and
        // removeRow() over that range exhausts memory.
        foreach (range('A', 'J') as $col) {
            $sheet->setCellValue($col . self::FIRST_DATA_ROW, null);
        }

        $codes = WhtSection::all();
        $lines = $this->lines($purchases, $salaries, $codes);

        // A blank CODE fails validation, and silently shipping one wastes a
        // round trip to IRIS. Name the sections instead.
        $uncoded = collect($lines)->filter(fn($l) => $l['code'] === '')
            ->pluck('section')->filter()->unique()->values();

        if ($uncoded->isNotEmpty()) {
            throw new \RuntimeException(
                'These sections have no FBR code, so their rows would fail validation: '
                . $uncoded->implode(', ')
                . '. Add the code under Settings → FBR Sections.'
            );
        }

        $row = self::FIRST_DATA_ROW;

        foreach ($lines as $line) {
            $sheet->setCellValueExplicit("A{$row}", $line['registration'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", $line['identification'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", $line['name'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", $line['date'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$row}", $line['code'], DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", $line['amount']);
            // G is the exemption code — left empty unless the payment is exempt.
            $sheet->setCellValue("H{$row}", $line['tax']);
            // I is VALIDATION STATUS, filled by the template's own macro.

            $row++;
        }

        $book->setActiveSheetIndexByName(self::SHEET);
        $sheet->setSelectedCell('A' . self::FIRST_DATA_ROW);

        return $book;
    }

    /**
     * The taxpayer's registration number as the statement wants it: digits only,
     * and for an NTN without the trailing check digit.
     *
     * A CNIC is 13 digits and goes in unchanged once the dashes are stripped.
     * An NTN is stored as 7 digits plus a check digit (7311412-4); IRIS wants
     * the 7 and rejects the rest.
     */
    public static function registrationNumber(string $raw): string
    {
        $digits = preg_replace('/[^0-9]/', '', $raw);

        return match (strlen($digits)) {
            13 => $digits,
            8  => substr($digits, 0, 7),
            0  => '',
            default => $digits,
        };
    }

    /** One statement line per transaction, both kinds in date order. */
    private function lines($purchases, $salaries, $codes): array
    {
        $lines = [];

        foreach ($purchases as $p) {
            $lines[] = $this->line(
                $p->party,
                $p->payment_date,
                $codes->firstWhere('section', $p->section)?->code,
                (float) $p->gross_amount,
                (float) $p->tax_withheld,
                $p->section,
            );
        }

        foreach ($salaries as $s) {
            $lines[] = $this->line(
                $s->employee,
                $s->payment_date,
                $codes->firstWhere('section', $s->section ?: '149')?->code,
                (float) $s->total_salary,
                (float) $s->tax_deducted,
                $s->section ?: '149',
            );
        }

        usort($lines, fn($a, $b) => strcmp($a['sort'], $b['sort']));

        return $lines;
    }

    private function line($party, $date, ?string $code, float $amount, float $tax, ?string $section = null): array
    {
        return [
            'registration'   => self::registrationNumber((string) ($party?->cnic_ntn ?? '')),
            // IRIS wants the taxpayer's number in REGISTRATION NO whichever kind
            // it is, so IDENTIFICATION NO stays empty.
            'identification' => '',
            'name'           => (string) ($party?->name ?? ''),
            'date'           => $date?->format('d/m/Y') ?? '',
            'code'           => (string) ($code ?? ''),
            'section'        => $section,
            'amount'         => round($amount, 2),
            'tax'            => round($tax, 2),
            'sort'           => ($date?->format('Y-m-d') ?? '') . '|' . (string) ($party?->name ?? ''),
        ];
    }
}
