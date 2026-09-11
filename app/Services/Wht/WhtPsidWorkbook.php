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
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Builds the workbook uploaded to FBR IRIS e-Payments to raise a PSID.
 *
 * The layout matches FBR's own "ePayments Import Template": ten columns, the
 * header on row 1, data from row 2, on a sheet called Sheet1. No title block,
 * no totals row, no formatting IRIS has to skip past — their parser reads the
 * grid literally, so anything decorative breaks the upload.
 *
 * The layout still comes from the `psid_columns` setting rather than being
 * hardcoded, so a future FBR revision can be handled from the Prepare PSID
 * screen without a deploy.
 */
class WhtPsidWorkbook
{
    public const SETTING_KEY = 'psid_columns';

    /**
     * FBR's ePayments Import Template, confirmed against the file downloaded
     * from IRIS. Do not add columns: IRIS matches on header name and position.
     */
    public const DEFAULT_LAYOUT = [
        'sheet_name'         => 'Sheet1',
        'header_row'         => 1,
        'include_title_block' => false,
        'include_totals_row' => false,
        'sheet_per_section'  => false,
        'columns' => [
            ['header' => 'Payment Section',         'field' => 'section',         'format' => 'text'],
            ['header' => 'TaxPayer_NTN',            'field' => 'taxpayer_ntn',    'format' => 'text'],
            ['header' => 'TaxPayer_CNIC',           'field' => 'taxpayer_cnic',   'format' => 'text'],
            ['header' => 'TaxPayer_Name',           'field' => 'payee_name',      'format' => 'text'],
            ['header' => 'TaxPayer_City',           'field' => 'city',            'format' => 'text'],
            ['header' => 'TaxPayer_Address',        'field' => 'address',         'format' => 'text'],
            ['header' => 'TaxPayer_Status',         'field' => 'taxpayer_status', 'format' => 'text'],
            ['header' => 'TaxPayer_Business_Name',  'field' => 'business_name',   'format' => 'text'],
            ['header' => 'Taxable_Amount',          'field' => 'gross_amount',    'format' => 'plain'],
            ['header' => 'Tax_Amount',              'field' => 'tax_withheld',    'format' => 'plain'],
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
     * @param  Collection<int,array>  $rows  Flat rows from WhtPsidBatcher.
     */
    public function build(WhtCompany $company, Carbon $month, string $kind, Collection $rows): Spreadsheet
    {
        $layout = self::layout();
        $columns = $layout['columns'];

        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('FTI Pak Tax Management')
            ->setTitle(sprintf('PSID %s %s', $kind === 'salaries' ? 'Salaries' : 'Vendors', $month->format('M Y')))
            ->setCompany($company->name);

        $groups = ($layout['sheet_per_section'] ?? false)
            ? $rows->groupBy('section')
            : collect([($layout['sheet_name'] ?? 'Sheet1') => $rows]);

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
        $headRow = max(1, (int) ($layout['header_row'] ?? 1));

        // IRIS reads the grid literally, so a title block is only ever written
        // when someone has deliberately turned it back on.
        if ($layout['include_title_block'] ?? false) {
            $lastCol = Coordinate::stringFromColumnIndex(count($columns));
            $sheet->setCellValue('A1', $company->name);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $sheet->setCellValue('A2', $company->ntn_cnic ? 'NTN: ' . $company->ntn_cnic : '');
            $sheet->setCellValue('A3', sprintf('%s withholding — %s',
                $kind === 'salaries' ? 'Salary' : 'Vendor / Supplier', $month->format('F Y')));
            foreach ([1, 2, 3] as $r) {
                $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            }
            $headRow = max($headRow, 5);
        }

        foreach ($columns as $c => $col) {
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($c + 1) . $headRow,
                $col['header'],
                DataType::TYPE_STRING
            );
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($columns));
        $range = "A{$headRow}:{$lastCol}{$headRow}";
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8EDF3');
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $row = $headRow + 1;
        $first = $row;

        foreach ($rows as $r) {
            $r['serial'] = $row - $headRow;

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

        if (($layout['include_totals_row'] ?? false) && $row > $first) {
            $last = $row - 1;
            $sheet->setCellValue("A{$row}", 'TOTAL');

            foreach ($columns as $c => $col) {
                if (!in_array($col['format'] ?? '', ['money', 'plain'], true)) {
                    continue;
                }
                $L = Coordinate::stringFromColumnIndex($c + 1);
                $sheet->setCellValue("{$L}{$row}", "=SUM({$L}{$first}:{$L}{$last})");
            }

            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFont()->setBold(true);
        }

        foreach (range(1, count($columns)) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
    }

    private function writeCell($sheet, string $ref, $value, string $format, string $dateFormat): void
    {
        if ($value === null || $value === '') {
            return;
        }

        switch ($format) {
            case 'text':
                // As text, so long CNICs and NTNs with dashes survive Excel.
                $sheet->setCellValueExplicit($ref, (string) $value, DataType::TYPE_STRING);
                break;

            case 'date':
                $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);
                $sheet->setCellValueExplicit($ref, $ts ? date($dateFormat, $ts) : (string) $value, DataType::TYPE_STRING);
                break;

            case 'plain':
                // Unformatted number — FBR's parser wants a bare value.
                $sheet->setCellValue($ref, round((float) $value, 2));
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
        $name = mb_substr($name, 0, 31) ?: 'Sheet1';

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
