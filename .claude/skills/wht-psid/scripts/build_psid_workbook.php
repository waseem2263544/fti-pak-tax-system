<?php
/**
 * build_psid_workbook.php — turn WHT period data into the FBR PSID upload workbook.
 *
 *   php build_psid_workbook.php <data.json> <output.xlsx> [columns.json]
 *
 * The column layout is NOT hardcoded here — it comes from references/psid-columns.json
 * so it can be conformed to FBR's real template without touching this script.
 *
 * Uses the PhpSpreadsheet already vendored in the Laravel app.
 */

$argvIn = $argv;
$dataPath = $argvIn[1] ?? null;
$outPath  = $argvIn[2] ?? null;
$colsPath = $argvIn[3] ?? __DIR__ . '/../references/psid-columns.json';

if (!$dataPath || !$outPath) {
    fwrite(STDERR, "usage: php build_psid_workbook.php <data.json> <output.xlsx> [columns.json]\n");
    exit(2);
}

// The app's vendor directory: skill lives at <project>/.claude/skills/wht-psid/scripts
$autoload = __DIR__ . '/../../../../vendor/autoload.php';

if (!is_readable($autoload)) {
    fwrite(STDERR, "Cannot find the Laravel vendor autoloader at: $autoload\n"
        . "Run this from inside the project, or install phpoffice/phpspreadsheet.\n");
    exit(3);
}

require $autoload;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

foreach (['zip', 'xmlwriter', 'dom', 'simplexml', 'mbstring'] as $ext) {
    if (!extension_loaded($ext)) {
        fwrite(STDERR, "Missing PHP extension: $ext (required to write .xlsx)\n");
        exit(4);
    }
}

$data = json_decode(file_get_contents($dataPath), true, 512, JSON_THROW_ON_ERROR);
$conf = json_decode(file_get_contents($colsPath), true, 512, JSON_THROW_ON_ERROR);

$dateFormat = $conf['date_format'] ?? 'd/m/Y';
$perSection = (bool) ($conf['sheet_per_section'] ?? true);
$wantTotals = (bool) ($conf['include_totals_row'] ?? true);

$agent  = $data['agent'] ?? [];
$period = $data['period'] ?? '';
$sections = $data['sections'] ?? [];

if (!$sections) {
    fwrite(STDERR, "No sections in the data — nothing to build.\n");
    exit(5);
}

$book = new Spreadsheet();
$book->getProperties()
    ->setCreator('FTI Pak Tax Management')
    ->setTitle('PSID upload ' . $period)
    ->setCompany($agent['name'] ?? '');

$used = [];

/** Excel forbids \ / ? * [ ] : and caps sheet names at 31 chars. */
$sheetName = function (string $raw) use (&$used): string {
    $n = preg_replace('/[\\\\\/\?\*\[\]:]/', '-', trim($raw));
    $n = mb_substr($n, 0, 31) ?: 'Section';
    $base = $n;
    $i = 2;
    while (isset($used[mb_strtolower($n)])) {
        $sfx = ' (' . $i . ')';
        $n = mb_substr($base, 0, 31 - mb_strlen($sfx)) . $sfx;
        $i++;
    }
    $used[mb_strtolower($n)] = true;
    return $n;
};

$writeCell = function ($sheet, string $ref, $value, string $format) use ($dateFormat) {
    if ($value === null || $value === '') {
        return;
    }

    switch ($format) {
        case 'text':
            // Written as text so long CNICs and leading zeros survive Excel.
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
};

$colLetter = fn(int $i) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);

$sheetIndex = 0;
$grandRows = 0;

foreach ($sections as $section) {
    $isSalary = ($section['kind'] ?? 'purchase') === 'salary';
    $columns = $isSalary && !empty($conf['salary_columns'])
        ? $conf['salary_columns']
        : $conf['columns'];

    $sheet = $sheetIndex === 0 ? $book->getActiveSheet() : $book->createSheet();
    $sheetIndex++;
    $sheet->setTitle($sheetName($perSection ? ($section['section'] ?? 'Sheet') : 'PSID'));

    $lastCol = $colLetter(count($columns));

    // Header block — FBR uploads normally want a clean grid, so this stays
    // above the table and can be deleted in one go if their parser objects.
    $sheet->setCellValue('A1', $agent['name'] ?? '');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

    $sheet->setCellValue('A2', trim(
        ($agent['ntn_cnic'] ?? '' ? 'NTN: ' . $agent['ntn_cnic'] : '')
        . (($section['code'] ?? '') ? '   ·   Payment Code: ' . $section['code'] : '')
    ));
    $sheet->getStyle('A2')->getFont()->setSize(9);

    $sheet->setCellValue('A3', sprintf(
        'Section %s — %s   ·   Tax Period: %s',
        $section['section'] ?? '',
        $section['payment_nature'] ?? '',
        $period
    ));
    $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(10);

    foreach ([1, 2, 3] as $r) {
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
    }

    // Header row
    $headRow = 5;
    foreach ($columns as $i => $col) {
        $sheet->setCellValue($colLetter($i + 1) . $headRow, $col['header']);
    }

    $range = "A{$headRow}:{$lastCol}{$headRow}";
    $sheet->getStyle($range)->getFont()->setBold(true);
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8EDF3');
    $sheet->getStyle($range)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($headRow)->setRowHeight(24);

    // Data rows
    $row = $headRow + 1;
    $first = $row;
    $serial = 1;

    foreach ($section['rows'] ?? [] as $r) {
        $r['serial'] = $serial++;
        $r['section'] = $r['section'] ?? ($section['section'] ?? '');
        $r['section_code'] = $r['section_code'] ?? ($section['code'] ?? '');
        $r['payment_nature'] = $r['payment_nature'] ?? ($section['payment_nature'] ?? '');
        $r['period_month'] = $r['period_month'] ?? $period;

        foreach ($columns as $i => $col) {
            $writeCell($sheet, $colLetter($i + 1) . $row, $r[$col['field']] ?? null, $col['format'] ?? 'text');
        }

        $row++;
        $grandRows++;
    }

    $last = $row - 1;

    if ($wantTotals && $last >= $first) {
        $sheet->setCellValue('A' . $row, 'TOTAL');

        foreach ($columns as $i => $col) {
            if (($col['format'] ?? '') !== 'money') {
                continue;
            }
            $L = $colLetter($i + 1);
            $sheet->setCellValue("{$L}{$row}", "=SUM({$L}{$first}:{$L}{$last})");
            $sheet->getStyle("{$L}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $tr = "A{$row}:{$lastCol}{$row}";
        $sheet->getStyle($tr)->getFont()->setBold(true);
        $sheet->getStyle($tr)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($tr)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
    }

    if ($last >= $first) {
        $sheet->getStyle("A{$headRow}:{$lastCol}" . ($wantTotals ? $row : $last))
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('B9C4D0');
    }

    foreach (range(1, count($columns)) as $i) {
        $sheet->getColumnDimension($colLetter($i))->setAutoSize(true);
    }

    $sheet->freezePane('A' . ($headRow + 1));
}

$book->setActiveSheetIndex(0);

@mkdir(dirname($outPath), 0775, true);

$writer = new Xlsx($book);
$writer->setPreCalculateFormulas(false);
$writer->save($outPath);
$book->disconnectWorksheets();

printf("%s\n", json_encode([
    'ok' => true,
    'output' => $outPath,
    'bytes' => filesize($outPath),
    'sheets' => $sheetIndex,
    'rows' => $grandRows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
