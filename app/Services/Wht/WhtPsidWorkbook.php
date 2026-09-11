<?php

namespace App\Services\Wht;

use App\Models\WhtCompany;
use App\Models\WhtSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Builds the workbook uploaded to FBR IRIS to raise a PSID.
 *
 * One PSID covers all vendor withholding for a month, and a second covers
 * salaries, so the vendor file spans several payment sections. Each row
 * therefore carries its own payment code.
 *
 * The column layout is NOT hardcoded: it comes from the `psid_columns` setting,
 * falling back to DEFAULT_LAYOUT. That matters because FBR revises its template
 * and deploys here are awkward — an admin can paste a corrected layout into the
 * Prepare PSID screen and it takes effect immediately.
 */
class WhtPsidWorkbook
{
    public const SETTING_KEY = 'psid_columns';

    /**
     * Best-guess layout. NOT verified against a live FBR template — confirm it
     * against a real one before relying on an upload being accepted.
     */
    public const DEFAULT_LAYOUT = [
        'date_format' => 'd/m/Y',
        'sheet_per_section' => false,
        'include_totals_row' => true,
        'columns' => [
            ['header' => 'Sr. No.',           'field' => 'serial',         'format' => 'integer'],
            ['header' => 'Payment Section',   'field' => 'section',        'format' => 'text'],
            ['header' => 'Payment Code',      'field' => 'section_code',   'format' => 'text'],
            ['header' => 'NTN / CNIC',        'field' => 'payee_cnic_ntn', 'format' => 'text'],
            ['header' => 'Name of Payee',     'field' => 'payee_name',     'format' => 'text'],
            ['header' => 'Taxpayer Status',   'field' => 'atl_status',     'format' => 'text'],
            ['header' => 'Date of Payment',   'field' => 'payment_date',   'format' => 'date'],
            ['header' => 'Amount of Payment', 'field' => 'gross_amount',   'format' => 'money'],
            ['header' => 'Rate (%)',          'field' => 'tax_rate',       'format' => 'rate'],
            ['header' => 'Tax Deducted',      'field' => 'tax_withheld',   'format' => 'money'],
        ],
        'salary_columns' => [
            ['header' => 'Sr. No.',          'field' => 'serial',         'format' => 'integer'],
            ['header' => 'Payment Section',  'field' => 'section',        'format' => 'text'],
            ['header' => 'Payment Code',     'field' => 'section_code',   'format' => 'text'],
            ['header' => 'NTN / CNIC',       'field' => 'payee_cnic_ntn', 'format' => 'text'],
            ['header' => 'Name of Employee', 'field' => 'payee_name',     'format' => 'text'],
            ['header' => 'Date of Payment',  'field' => 'payment_date',   'format' => 'date'],
            ['header' => 'Taxable Salary',   'field' => 'taxable_salary', 'format' => 'money'],
            ['header' => 'Exempt Amount',    'field' => 'exempt_amount',  'format' => 'money'],
            ['header' => 'Total Salary',     'field' => 'gross_amount',   'format' => 'money'],
            ['header' => 'Tax Deducted',     'field' => 'tax_withheld',   'format' => 'money'],
        ],
    ];

    /** The layout in force, with any stored override merged over the default. */
    public static function layout(): array
    {
        $stored = WhtSetting::get(self::SETTING_KEY);

        if (!$stored) {
            return self::DEFAULT_LAYOUT;
        }

        $decoded = json_decode($stored, true);

        // A broken override must not take the feature down.
        if (!is_array($decoded) || empty($decoded['columns'])) {
            return self::DEFAULT_LAYOUT;
        }

        return $decoded + self::DEFAULT_LAYOUT;
    }

    /**
     * @param  Collection<int,array>  $rows  Flat rows, each already carrying
     *                                       section, section_code and payee fields.
     */
    public function build(WhtCompany $company, Carbon $month, string $kind, Collection $rows): Spreadsheet
    {
        $layout = self::layout();
        $isSalary = $kind === 'salaries';

        $columns = $isSalary && !empty($layout['salary_columns'])
            ? $layout['salary_columns']
            : $layout['columns'];

        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('FTI Pak Tax Management')
            ->setTitle(sprintf('PSID %s %s', $isSalary ? 'Salaries' : 'Vendors', $month->format('M Y')))
            ->setCompany($company->name);

        $groups = ($layout['sheet_per_section'] ?? false)
            ? $rows->groupBy('section')
            : collect([$isSalary ? 'Salaries' : 'Vendors' => $rows]);

        $used = [];
        $i = 0;

        foreach ($groups as $label => $groupRows) {
            $sheet = $i === 0 ? $book->getActiveSheet() : $book->createSheet();
            $i++;
            $sheet->setTitle($this->sheetName((string) $label, $used));

            $this->writeSheet($sheet, $company, $month, $kind, $columns, $groupRows, $layout);
        }

        $book->setActiveSheetIndex(0);

        return $book;
    }

    private function writeSheet($sheet, WhtCompany $company, Carbon $month, string $kind, array $columns, Collection $rows, array $layout): void
    {
        $lastCol = Coordinate::stringFromColumnIndex(count($columns));

        $sheet->setCellValue('A1', $company->name);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->setCellValue('A2', $company->ntn_cnic ? 'NTN: ' . $company->ntn_cnic : '');
        $sheet->getStyle('A2')->getFont()->setSize(9);

        $sheet->setCellValue('A3', sprintf(
            '%s withholding — Tax Period %s',
            $kind === 'salaries' ? 'Salary' : 'Vendor / Supplier',
            $month->format('F Y')
        ));
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(10);

        foreach ([1, 2, 3] as $r) {
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        }

        $headRow = 5;

        foreach ($columns as $c => $col) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($c + 1) . $headRow, $col['header']);
        }

        $range = "A{$headRow}:{$lastCol}{$headRow}";
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8EDF3');
        $sheet->getStyle($range)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($headRow)->setRowHeight(24);

        $row = $headRow + 1;
        $first = $row;
        $serial = 1;

        foreach ($rows as $r) {
            $r['serial'] = $serial++;

            foreach ($columns as $c => $col) {
                $this->writeCell(
                    $sheet,
                    Coordinate::stringFromColumnIndex($c + 1) . $row,
                    $r[$col['field']] ?? null,
                    $col['format'] ?? 'text',
                    $layout['date_format'] ?? 'd/m/Y'
                );
            }

            $row++;
        }

        $last = $row - 1;

        if (($layout['include_totals_row'] ?? true) && $last >= $first) {
            $sheet->setCellValue("A{$row}", 'TOTAL');

            foreach ($columns as $c => $col) {
                if (($col['format'] ?? '') !== 'money') {
                    continue;
                }
                $L = Coordinate::stringFromColumnIndex($c + 1);
                $sheet->setCellValue("{$L}{$row}", "=SUM({$L}{$first}:{$L}{$last})");
                $sheet->getStyle("{$L}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            }

            $tr = "A{$row}:{$lastCol}{$row}";
            $sheet->getStyle($tr)->getFont()->setBold(true);
            $sheet->getStyle($tr)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($tr)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
            $last = $row;
        }

        if ($last >= $headRow) {
            $sheet->getStyle("A{$headRow}:{$lastCol}{$last}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('B9C4D0');
        }

        foreach (range(1, count($columns)) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $sheet->freezePane('A' . ($headRow + 1));
    }

    private function writeCell($sheet, string $ref, $value, string $format, string $dateFormat): void
    {
        if ($value === null || $value === '') {
            return;
        }

        switch ($format) {
            case 'text':
                // As text, so long CNICs and leading zeros survive Excel.
                $sheet->setCellValueExplicit($ref, (string) $value, DataType::TYPE_STRING);
                break;

            case 'date':
                $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
                $sheet->setCellValueExplicit($ref, $ts ? date($dateFormat, $ts) : (string) $value, DataType::TYPE_STRING);
                break;

            case 'money':
                $sheet->setCellValue($ref, round((float) $value, 2));
                $sheet->getStyle($ref)->getNumberFormat()->setFormatCode('#,##0.00');
                break;

            case 'rate':
                $sheet->setCellValue($ref, round((float) $value, 3));
                $sheet->getStyle($ref)->getNumberFormat()->setFormatCode('0.00');
                break;

            case 'integer':
                $sheet->setCellValue($ref, (int) $value);
                break;

            default:
                $sheet->setCellValue($ref, $value);
        }
    }

    /** Excel forbids \ / ? * [ ] : in sheet names and caps them at 31 characters. */
    private function sheetName(string $raw, array &$used): string
    {
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/', '-', trim($raw));
        $name = mb_substr($name, 0, 31) ?: 'Sheet';

        $base = $name;
        $n = 2;

        while (isset($used[mb_strtolower($name)])) {
            $suffix = ' (' . $n . ')';
            $name = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
            $n++;
        }

        $used[mb_strtolower($name)] = true;

        return $name;
    }
}
