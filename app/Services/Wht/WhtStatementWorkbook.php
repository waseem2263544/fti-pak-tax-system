<?php

namespace App\Services\Wht;

use App\Models\WhtCompany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds the monthly withholding statement (u/s 165) as an Excel workbook.
 *
 * A summary sheet lists each section, with one detail sheet per section behind
 * it. The summary's figures are SUM formulas pointing at the detail sheets
 * rather than baked-in numbers, so edits to the detail flow through — which is
 * the point of handing someone a workbook instead of a PDF.
 */
class WhtStatementWorkbook
{
    private const MONEY = '#,##0.00';
    private const HEADER_FILL = 'E8EDF3';
    private const TITLE_SIZE = 14;

    /** Sheet titles already used, to keep them unique. */
    private array $usedNames = [];

    public function build(WhtCompany $company, Carbon $month, Collection $grouped): Spreadsheet
    {
        $this->usedNames = [];

        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('FTI Pak Tax Management')
            ->setTitle('Withholding Statement ' . $month->format('M Y'))
            ->setSubject('Monthly withholding statement u/s 165')
            ->setCompany($company->name);

        $summary = $book->getActiveSheet();
        $summary->setTitle('Summary');

        // Detail sheets first, so the summary can reference them by name.
        $sheets = [];
        foreach ($grouped as $i => $group) {
            $sheet = $book->createSheet();
            $sheets[$i] = $this->buildDetailSheet($sheet, $company, $month, $group);
        }

        $this->buildSummarySheet($summary, $company, $month, $grouped, $sheets);

        $book->setActiveSheetIndex(0);

        return $book;
    }

    // ── SUMMARY ──────────────────────────────────────────────────────────────

    private function buildSummarySheet(
        Worksheet $sheet,
        WhtCompany $company,
        Carbon $month,
        Collection $grouped,
        array $sheets
    ): void {
        $this->titleBlock($sheet, $company, $month, 'MONTHLY WITHHOLDING STATEMENT (u/s 165)', 'E');

        $headers = ['Section', 'Nature of Payment', 'Payees', 'Gross Amount', 'Tax Withheld'];
        $headRow = 6;
        $sheet->fromArray($headers, null, 'A' . $headRow);
        $this->styleHeaderRow($sheet, $headRow, 'A', 'E');

        $row = $headRow + 1;
        $first = $row;

        foreach ($grouped as $i => $group) {
            $meta = $sheets[$i];
            $ref = $this->sheetRef($meta['name']);

            $sheet->setCellValue("A{$row}", $group['section']);
            $sheet->setCellValue("B{$row}", $group['nature']);
            $sheet->setCellValue("C{$row}", $group['payees']);

            // Live totals from the detail sheet rather than a copied number.
            $sheet->setCellValue("D{$row}", "={$ref}{$meta['grossCol']}{$meta['totalRow']}");
            $sheet->setCellValue("E{$row}", "={$ref}{$meta['taxCol']}{$meta['totalRow']}");

            $row++;
        }

        $last = $row - 1;

        if ($last >= $first) {
            $sheet->setCellValue("A{$row}", 'TOTAL');
            $sheet->setCellValue("C{$row}", "=SUM(C{$first}:C{$last})");
            $sheet->setCellValue("D{$row}", "=SUM(D{$first}:D{$last})");
            $sheet->setCellValue("E{$row}", "=SUM(E{$first}:E{$last})");
            $this->styleTotalRow($sheet, $row, 'A', 'E');

            $sheet->getStyle("D{$first}:E{$row}")->getNumberFormat()->setFormatCode(self::MONEY);
            $sheet->getStyle("C{$first}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->border($sheet, "A{$headRow}:E{$row}");
        }

        $sheet->setCellValue('A' . ($row + 2), 'Figures are grouped by tax period, so a liability deposited in a later month still appears in the month it belongs to.');
        $sheet->getStyle('A' . ($row + 2))->getFont()->setItalic(true)->setSize(9);

        $this->autoSize($sheet, 'A', 'E');
        $sheet->getColumnDimension('B')->setAutoSize(false)->setWidth(42);
        $sheet->freezePane('A' . ($headRow + 1));
        $this->pageSetup($sheet, 'A:E', $headRow);
    }

    // ── DETAIL ───────────────────────────────────────────────────────────────

    /**
     * @return array{name:string, totalRow:int, grossCol:string, taxCol:string}
     */
    private function buildDetailSheet(Worksheet $sheet, WhtCompany $company, Carbon $month, array $group): array
    {
        $isSalary = ($group['kind'] ?? 'purchase') === 'salary';

        $name = $this->sheetName($group['section']);
        $sheet->setTitle($name);

        $this->titleBlock(
            $sheet,
            $company,
            $month,
            'SECTION ' . $group['section'] . ' — ' . mb_strtoupper($group['nature']),
            $isSalary ? 'I' : 'I'
        );

        $headers = $isSalary
            ? ['Employee', 'CNIC / NTN', 'Payment Date', 'Taxable Salary', 'Exempt', 'Total Salary', 'Tax Deducted', 'PSID No.', 'CPR No.']
            : ['Payee', 'CNIC / NTN', 'ATL Status', 'Payment Date', 'Gross Amount', 'Rate %', 'Tax Withheld', 'PSID No.', 'CPR No.'];

        $headRow = 6;
        $sheet->fromArray($headers, null, 'A' . $headRow);
        $this->styleHeaderRow($sheet, $headRow, 'A', 'I');

        $row = $headRow + 1;
        $first = $row;

        foreach ($group['items'] as $item) {
            $party = $isSalary ? $item->employee : $item->party;

            if ($isSalary) {
                $sheet->setCellValue("A{$row}", $party?->name ?? '—');
                $sheet->setCellValueExplicit("B{$row}", (string) ($party?->cnic_ntn ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("C{$row}", $item->payment_date?->format('d-M-Y'));
                $sheet->setCellValue("D{$row}", (float) $item->taxable_salary);
                $sheet->setCellValue("E{$row}", (float) $item->exempt_amount);
                $sheet->setCellValue("F{$row}", (float) $item->total_salary);
                $sheet->setCellValue("G{$row}", (float) $item->tax_deducted);
                $sheet->setCellValueExplicit("H{$row}", (string) ($item->psid_no ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("I{$row}", (string) ($item->cpr_no ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue("A{$row}", $party?->name ?? '—');
                $sheet->setCellValueExplicit("B{$row}", (string) ($party?->cnic_ntn ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("C{$row}", $party?->atl_status === 'non-filer' ? 'Non-filer' : 'Filer');
                $sheet->setCellValue("D{$row}", $item->payment_date?->format('d-M-Y'));
                $sheet->setCellValue("E{$row}", (float) $item->gross_amount);
                $sheet->setCellValue("F{$row}", (float) $item->tax_rate);
                $sheet->setCellValue("G{$row}", (float) $item->tax_withheld);
                $sheet->setCellValueExplicit("H{$row}", (string) ($item->psid_no ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("I{$row}", (string) ($item->cpr_no ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }

            $row++;
        }

        $last = $row - 1;
        $totalRow = $row;

        // Column layout differs between the two shapes; the summary needs to know.
        $grossCol = $isSalary ? 'F' : 'E';
        $taxCol = 'G';

        if ($last >= $first) {
            $sheet->setCellValue("A{$totalRow}", 'TOTAL');

            foreach ($isSalary ? ['D', 'E', 'F', 'G'] : ['E', 'G'] as $col) {
                $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}{$first}:{$col}{$last})");
            }

            $this->styleTotalRow($sheet, $totalRow, 'A', 'I');

            $moneyCols = $isSalary ? "D{$first}:G{$totalRow}" : "E{$first}:E{$totalRow}";
            $sheet->getStyle($moneyCols)->getNumberFormat()->setFormatCode(self::MONEY);
            $sheet->getStyle("G{$first}:G{$totalRow}")->getNumberFormat()->setFormatCode(self::MONEY);

            if (!$isSalary) {
                $sheet->getStyle("F{$first}:F{$last}")->getNumberFormat()->setFormatCode('0.00"%"');
                $sheet->getStyle("F{$first}:F{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $this->border($sheet, "A{$headRow}:I{$totalRow}");
        } else {
            // No rows: still give the summary a cell to point at.
            $sheet->setCellValue("{$grossCol}{$totalRow}", 0);
            $sheet->setCellValue("{$taxCol}{$totalRow}", 0);
        }

        $this->autoSize($sheet, 'A', 'I');
        $sheet->freezePane('A' . ($headRow + 1));
        $this->pageSetup($sheet, 'A:I', $headRow);

        return [
            'name' => $name,
            'totalRow' => $totalRow,
            'grossCol' => $grossCol,
            'taxCol' => $taxCol,
        ];
    }

    // ── SHARED STYLING ───────────────────────────────────────────────────────

    private function titleBlock(Worksheet $sheet, WhtCompany $company, Carbon $month, string $heading, string $lastCol): void
    {
        $sheet->setCellValue('A1', $company->name);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(self::TITLE_SIZE);

        $sheet->setCellValue('A2', trim(($company->ntn_cnic ? 'NTN: ' . $company->ntn_cnic : '') . ($company->address ? '  ·  ' . $company->address : '')));
        $sheet->getStyle('A2')->getFont()->setSize(9);

        $sheet->setCellValue('A3', $heading);
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11);

        $sheet->setCellValue('A4', 'Tax Period: ' . $month->format('F Y') . '   ·   Generated: ' . now()->format('d M Y'));
        $sheet->getStyle('A4')->getFont()->setSize(9);

        foreach (['A1', 'A2', 'A3', 'A4'] as $i => $cell) {
            $sheet->mergeCells('A' . ($i + 1) . ':' . $lastCol . ($i + 1));
        }
    }

    private function styleHeaderRow(Worksheet $sheet, int $row, string $from, string $to): void
    {
        $range = "{$from}{$row}:{$to}{$row}";
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_FILL);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function styleTotalRow(Worksheet $sheet, int $row, string $from, string $to): void
    {
        $range = "{$from}{$row}:{$to}{$row}";
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($range)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
    }

    private function border(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('B9C4D0');
    }

    private function autoSize(Worksheet $sheet, string $from, string $to): void
    {
        foreach (range($from, $to) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function pageSetup(Worksheet $sheet, string $cols, int $headRow): void
    {
        $setup = $sheet->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $setup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $setup->setFitToWidth(1);
        $setup->setFitToHeight(0);
        $setup->setPrintArea($cols);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headRow, $headRow);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
    }

    /**
     * Excel forbids \ / ? * [ ] : in sheet names and caps them at 31 characters,
     * which matters here because FBR section labels look like "153(1)(a)/9".
     */
    private function sheetName(string $raw): string
    {
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/', '-', trim($raw));
        $name = mb_substr($name, 0, 31);

        if ($name === '') {
            $name = 'Section';
        }

        $base = $name;
        $n = 2;

        while (isset($this->usedNames[mb_strtolower($name)])) {
            $suffix = ' (' . $n . ')';
            $name = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
            $n++;
        }

        $this->usedNames[mb_strtolower($name)] = true;

        return $name;
    }

    /** A quoted sheet reference usable inside a formula. */
    private function sheetRef(string $name): string
    {
        return "'" . str_replace("'", "''", $name) . "'!";
    }
}
