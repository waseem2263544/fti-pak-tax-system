<?php

namespace App\Services\FirmRegistration;

use App\Models\FirmRegistration as Firm;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Style\Tab;

/**
 * Builds the three filing documents for a firm registration as Word files.
 *
 * House layout for stamp paper (confirmed against a printed affidavit):
 *  - every file on Legal paper, 8.5 x 14 in;
 *  - every page that goes on stamp paper (affidavit, all of the deed) starts
 *    11.25 cm down, leaving the e-stamp header and details block blank. The gap
 *    is the page's top margin - never blank header lines, which shift on edit;
 *  - Form A is not stamp paper: normal margins, one page.
 * Alignment uses tables with white borders, so nothing moves when edited.
 */
class FirmDocuments
{
    public const DOCS = [
        'form-a'    => 'Form A',
        'affidavit' => 'Affidavit',
        'deed'      => 'Partnership Deed',
    ];

    private const LEGAL_W = 12240;   // twips, 8.5 in
    private const LEGAL_H = 20160;   // twips, 14 in

    public function __construct(private Firm $firm) {}

    public function filename(string $doc): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|]+/', '', $this->firm->firm_name);
        return self::DOCS[$doc] . ' - ' . trim($name) . '.docx';
    }

    /** Write one document to $path. */
    public function save(string $doc, string $path): void
    {
        $word = match ($doc) {
            'form-a' => $this->formA(),
            'affidavit' => $this->affidavit(),
            'deed' => $this->deed(),
        };
        IOFactory::createWriter($word, 'Word2007')->save($path);
        self::fixIndents($path);
    }

    /**
     * PhpWord writes firstLine="0" beside every hanging indent; the two are
     * mutually exclusive in OOXML and Word/LibreOffice let the zero win, which
     * drops wrapped lines back to the margin. Keep only the meaningful one.
     */
    private static function fixIndents(string $path): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return;
        $xml = $zip->getFromName('word/document.xml');
        $xml = preg_replace('/ w:firstLine="0" w:hanging="(?!0")/', ' w:hanging="', $xml);
        $xml = str_replace(' w:hanging="0"', '', $xml);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
    }

    /** Write all three into a zip at $path. */
    public function saveZip(string $path): void
    {
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $tmp = [];
        foreach (array_keys(self::DOCS) as $doc) {
            $file = tempnam(sys_get_temp_dir(), 'firm');
            $this->save($doc, $file);
            $zip->addFile($file, $this->filename($doc));
            $tmp[] = $file;
        }
        $zip->close();
        array_map('unlink', $tmp);
    }

    // ------------------------------------------------------------------ helpers

    private static function cm(float $cm): int { return (int) round($cm * 566.929); }

    private function stampTop(): int { return self::cm($this->firm->stamp_top_cm ?: 11.25); }

    private function newDoc(string $font, float $size, float $lineHeight = 1.0): PhpWord
    {
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);   // names like "Tiles & Sanitary"
        $w = new PhpWord();
        $w->setDefaultFontName($font);
        $w->setDefaultFontSize($size);
        $w->setDefaultParagraphStyle(['spaceAfter' => 0, 'spaceBefore' => 0, 'lineHeight' => $lineHeight]);
        $w->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('en-US'));
        return $w;
    }

    private function section(PhpWord $w, int $top, int $bottom, int $left, int $right): Section
    {
        return $w->addSection([
            'pageSizeW' => self::LEGAL_W, 'pageSizeH' => self::LEGAL_H,
            'marginTop' => $top, 'marginBottom' => $bottom,
            'marginLeft' => $left, 'marginRight' => $right,
            'headerHeight' => 720, 'footerHeight' => 720,
        ]);
    }

    /** Table whose borders are white: lays text out in columns, prints invisible. */
    private function grid(Section|\PhpOffice\PhpWord\Element\Cell $parent, bool $visible = false): Table
    {
        return $parent->addTable([
            'alignment' => JcTable::CENTER,
            'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
            'borderSize' => $visible ? 6 : 4,
            'borderColor' => $visible ? '000000' : 'FFFFFF',
            'cellMarginLeft' => 108, 'cellMarginRight' => 108,
        ]);
    }

    /** Add text runs: [[text, 'bius'], ...]  b=bold i=italic u=underline s=superscript */
    private function runs(TextRun $tr, array $runs, array $base = []): TextRun
    {
        foreach ($runs as [$text, $flags]) {
            if ($text === "\n") { $tr->addTextBreak(); continue; }
            $tr->addText($text, $base + [
                'bold' => str_contains($flags, 'b'),
                'italic' => str_contains($flags, 'i'),
                'underline' => str_contains($flags, 'u') ? 'single' : 'none',
                'superScript' => str_contains($flags, 's'),
            ]);
        }
        return $tr;
    }

    private function para($parent, array $runs, array $pStyle = [], array $font = []): TextRun
    {
        return $this->runs($parent->addTextRun($pStyle), $runs, $font);
    }

    private static function upper(string $s): string { return mb_strtoupper($s); }

    private function partners(): array { return array_values($this->firm->partners ?? []); }

    private function witnesses(): array { return array_values($this->firm->witnesses ?? []); }

    private function rel(array $p, bool $lower = false): string
    {
        $r = $p['relation'] ?? 'S/O';
        return $lower ? ucfirst(strtolower(substr($r, 0, 1))) . '/o' : $r;   // "S/o" for Form A / affidavit
    }

    /** "October 21, 2026" */
    private function longDate($d): string { return $d->format('F j, Y'); }

    // ------------------------------------------------------------------ Form A

    private function formA(): PhpWord
    {
        $f = $this->firm;
        $w = $this->newDoc('Calibri', 11);
        $s = $this->section($w, self::cm(1.2), self::cm(1.0), self::cm(2.2), self::cm(2.2));
        $C = ['alignment' => Jc::CENTER];

        $this->para($s, [['PAKISTAN PARTNERSHIP ACT, 1932', 'b']], $C, ['size' => 14]);
        $this->para($s, [['FORM "A"', 'b']], $C, ['size' => 13]);
        $this->para($s, [['APPLICATION FOR REGISTRATION OF FIRM', 'b']], $C, ['size' => 13]);
        $this->para($s, [['(See Section – 58)', 'b']], $C + ['spaceAfter' => 240], ['size' => 13]);
        $this->para($s, [['We, the undersigned being partners, hereby apply for the registration of a Firm and for that purpose apply for the following particulars pursuant to Section – 58 of the Pakistan Partnership Act, 1932;', 'b']],
            ['alignment' => Jc::BOTH, 'indentation' => ['firstLine' => self::cm(1.2)], 'spaceAfter' => 200]);

        // (a)-(f) on one grid: label | description | value
        [$LAB, $DESC, $VAL] = [self::cm(1.2), self::cm(6.4), self::cm(9.0)];
        $t = $this->grid($s);
        $items = [
            ['(a)', 'Firm Name: -', '"' . self::upper($f->firm_name) . '"'],
            ['(b)', 'Place or Principal place of Business or Firm:', '"' . $f->principal_place . '"'],
            ['(c)', 'Name of any other place where the Firm carries on business:', $f->other_places ?: 'NIL'],
            ['(d)', 'Date on which each partner joined the Firm:', $this->longDate($f->deed_date)],
            ['(e)', 'Name in Full, Permanent', ''],
            ['(f)', 'Address & Signature of the Partners:', ''],
        ];
        foreach ($items as [$lab, $desc, $val]) {
            $before = $lab === '(f)' ? 40 : 120;
            $row = $t->addRow(null, ['cantSplit' => true]);
            $this->para($row->addCell($LAB), [[$lab, 'b']], ['spaceBefore' => $before], ['size' => 10]);
            $this->para($row->addCell($DESC), [[$desc, 'b']], ['spaceBefore' => $before], ['size' => 10]);
            $cell = $row->addCell($VAL);
            $this->para($cell, $val === '' ? [] : [[$val, 'bu']], ['spaceBefore' => $before], ['size' => 10.5]);
        }

        $s->addTextBreak(1, ['size' => 6]);
        // partners: details | signature
        $pt = $this->grid($s);
        foreach ($this->partners() as $i => $p) {
            $row = $pt->addRow(null, ['cantSplit' => true]);
            $cell = $row->addCell(self::cm(12.4), ['valign' => 'center']);
            $tr = $cell->addTextRun([
                'indentation' => ['left' => $LAB + self::cm(0.6), 'hanging' => self::cm(0.6)],
                'tabs' => [new Tab('left', $LAB + self::cm(0.6))],
                'spaceBefore' => 120, 'spaceAfter' => 80,
            ]);
            $runs = [[($i + 1) . ".\t", 'b'], [$p['name'] ?? '', 'b'],
                [' ' . $this->rel($p, true) . ' ' . ($p['father_name'] ?? '') . ' CNIC No. ' . ($p['cnic'] ?? ''), ''],
                ["\n", ''], [rtrim($p['address'] ?? '', '.') . '.', '']];
            if (!empty($p['cell'])) { $runs[] = ["\n", '']; $runs[] = ['Cell No. ' . $p['cell'], '']; }
            $this->runs($tr, $runs, ['size' => 10]);
            $this->para($row->addCell(self::cm(4.2), ['valign' => 'center']), [['Signature: ____________', '']], [], ['size' => 10]);
        }

        $s->addTextBreak(1, ['size' => 6]);
        // (g) duration
        $gt = $this->grid($s);
        $row = $gt->addRow(null, ['cantSplit' => true]);
        $this->para($row->addCell($LAB), [['(g)', 'b']], ['spaceBefore' => 120], ['size' => 10]);
        $this->para($row->addCell($DESC), [['Duration of Firm:', 'b']], ['spaceBefore' => 120], ['size' => 10]);
        $row->addCell($VAL);
        foreach ([['1.', 'Period, since where Firm is Carrying on business:', $this->longDate($f->commencement_date)],
                  ['2.', 'Future period for which the Firms is floated:', self::upper($f->duration ?: 'AT WILL')]] as [$n, $desc, $val]) {
            $row = $gt->addRow(null, ['cantSplit' => true]);
            $row->addCell($LAB);
            $c = $row->addCell($DESC);
            $this->para($c, [["$n\t$desc", 'b']], ['spaceBefore' => 120,
                'indentation' => ['left' => self::cm(0.6), 'hanging' => self::cm(0.6)], 'tabs' => [new Tab('left', self::cm(0.6))]], ['size' => 10]);
            $this->para($row->addCell($VAL, ['valign' => 'bottom']), [[$val, 'bu']], [], ['size' => 10.5]);
        }

        [$d, $suf] = Firm::ordinalParts($f->deed_date);
        $this->para($s, [['Dated:  ', 'b'], [$d, 'bu'], [$suf, 'bus'], [' Day of ' . $f->deed_date->format('F, Y') . '.', 'b']],
            ['spaceBefore' => 280, 'spaceAfter' => 280, 'indentation' => ['left' => $LAB]], ['size' => 10]);
        $this->para($s, [['WITNESS TO THE SIGNATURE', 'b']], ['alignment' => Jc::CENTER, 'spaceAfter' => 200], ['size' => 10]);
        $this->para($s, [['This form must be signed by all partners or their agents especially authorized in this behalf, in presence of two witnesses and Notary Public or a 1', ''], ['st', 's'], [' Class Magistrate:', '']],
            ['alignment' => Jc::BOTH, 'indentation' => ['firstLine' => self::cm(1.2)], 'spaceAfter' => 120], ['size' => 10]);

        // witnesses + notary, visible borders
        $wt = $this->grid($s, true);
        $W = self::cm(5.55);
        $row = $wt->addRow(null, ['cantSplit' => true]);
        foreach (['Witness No. 1', 'Witness No. 2', 'Notary Public / Magistrate 1st'] as $h) {
            $this->para($row->addCell($W), [[$h, 'biu']], ['alignment' => Jc::CENTER], ['size' => 10]);
        }
        $row = $wt->addRow(null, ['cantSplit' => true]);
        $wit = $this->witnesses();
        for ($i = 0; $i < 2; $i++) {
            $p = $wit[$i] ?? [];
            $cell = $row->addCell($W);
            $lines = [
                ['Signature ', '______________________', false],
                ['Name: ', $p['name'] ?? '', true],
                ["Father's Name: ", $p['father_name'] ?? '', false],
                ['Address: ', $p['address'] ?? '', false],
                ['CNIC No. ', $p['cnic'] ?? '', false],
            ];
            if (!empty($p['cell'])) $lines[] = ['Cell No. ', $p['cell'], false];
            foreach ($lines as $k => [$lab, $val, $bold]) {
                $blank = $val === '' || str_starts_with($val, '_');
                $this->para($cell, [[$lab, ''], [$val === '' ? '________________' : $val, ($blank ? '' : 'u') . ($bold && !$blank ? 'b' : '')]],
                    ['spaceBefore' => 100, 'spaceAfter' => $k === count($lines) - 1 ? 120 : 0], ['size' => 9.5]);
            }
        }
        $cell = $row->addCell($W);
        foreach (['Signature: __________________', 'Name: ____________________', 'License No: ________________', '', 'Seal:'] as $l) {
            $this->para($cell, [[$l, '']], ['spaceBefore' => 100], ['size' => 9.5]);
        }
        return $w;
    }

    // ------------------------------------------------------------------ Affidavit

    private function affidavit(): PhpWord
    {
        $f = $this->firm;
        $w = $this->newDoc('Arial', 10);
        $s = $this->section($w, $this->stampTop(), self::cm(1.0), self::cm(2.2), self::cm(2.2));
        $IND = ['indentation' => ['left' => self::cm(1.2)]];

        $this->para($s, [['AFFIDAVIT', 'biu']], ['alignment' => Jc::CENTER, 'spaceAfter' => 200], ['size' => 12]);
        $partners = $this->partners();
        foreach ($partners as $i => $p) {
            $this->para($s, [[$p['name'] . ' ' . $this->rel($p, true) . ' ' . ($p['father_name'] ?? '') . ', CNIC No. ' . ($p['cnic'] ?? ''), 'u']], $IND + ['spaceAfter' => 60]);
            $this->para($s, [['Resident of ', ''], [rtrim($p['address'] ?? '', '.') . '.', 'u']], $IND + ['spaceAfter' => $i === count($partners) - 1 ? 200 : 160]);
        }
        $this->para($s, [['M/s “', 'b'], [self::upper($f->firm_name), 'bu'], ['”', 'b']], $IND + ['spaceAfter' => 120]);
        $this->para($s, [['Head Office situated at ', ''], [rtrim($f->principal_place, '.') . '.', 'u']], $IND + ['spaceAfter' => 160]);

        $stmts = [
            'That our firm name is not registered before this and address also not registered on the same name before this.',
            'That we have not copied the aforesaid name and address from anyone.',
            'We are the title owners of the aforesaid name and address.',
            'No firm is registered in the name under Partnership Act 1932.',
            'No Civil/Criminal suit is pending against the partners or aforesaid firm in any court of law.',
            'That the all documents submitting to the office in good faith.',
            'That in future if any problem creates for the aforesaid firm we will be answerable in this situation the registrar of Firms will be not responsible.',
            'In all govt departments/all courts of Pakistan we will be responsible department will not be responsible.',
        ];
        foreach ($stmts as $i => $st) {
            $this->para($s, [[($i + 1) . ".\t" . $st, '']], ['alignment' => Jc::BOTH, 'spaceAfter' => 20,
                'indentation' => ['left' => self::cm(1.2), 'hanging' => self::cm(0.6)], 'tabs' => [new Tab('left', self::cm(1.2))]]);
        }
        $this->para($s, [['Whatever stated above is true and correct to the best of our knowledge and nothing has been concealed therein.', '']],
            $IND + ['alignment' => Jc::BOTH, 'spaceBefore' => 120, 'spaceAfter' => 160]);

        $this->signBlock($s, 'DEPONENTS', array_map(fn ($i, $p) => ['Deponents No.' . ($i + 1) . '.', $p['name'] ?? ''], array_keys($partners), $partners), 'Arial', 10);
        $wit = $this->witnesses();
        $this->signBlock($s, 'WITNESSES', array_map(fn ($i, $p) => ['Witness No.' . ($i + 1) . '.', $p['name'] ?? ''], array_keys($wit), $wit), 'Arial', 10);
        return $w;
    }

    /** Title, then signers two per row: label / line / name. Rows never split. */
    private function signBlock(Section $s, string $title, array $signers, string $font, float $size): void
    {
        $this->para($s, [[$title, 'bu']], ['alignment' => Jc::CENTER, 'spaceAfter' => 80, 'keepNext' => true], ['size' => $size]);
        $t = $this->grid($s);
        foreach (array_chunk($signers, 2) as $pair) {
            $row = $t->addRow(null, ['cantSplit' => true]);
            foreach ([0, 1] as $k) {
                $cell = $row->addCell(self::cm(8.3));
                if (!isset($pair[$k])) continue;
                [$lab, $name] = $pair[$k];
                $this->para($cell, [[$lab, 'b']], ['spaceAfter' => 280], ['size' => $size]);
                $this->para($cell, [['____________________________', '']], [], ['size' => $size]);
                $this->para($cell, [[$name, '']], ['spaceAfter' => 120], ['size' => $size - 0.5]);
            }
        }
    }

    // ------------------------------------------------------------------ Deed

    private function deed(): PhpWord
    {
        $f = $this->firm;
        $w = $this->newDoc('Times New Roman', 12, 1.3);
        // Every page of the deed is printed on stamp paper: same gap on all pages, continuous flow.
        $s = $this->section($w, $this->stampTop(), self::cm(1.5), self::cm(2.5), self::cm(2.0));
        $J = ['alignment' => Jc::BOTH];
        $partners = $this->partners();
        $firmQ = '“' . $f->firm_name . '”';
        $place = rtrim($f->principal_place, '.');
        [$dd, $dsuf] = Firm::ordinalParts($f->deed_date);
        [$cd, $csuf] = Firm::ordinalParts($f->commencement_date);

        $this->para($s, [['PARTNERSHIP DEED', 'bu']], ['alignment' => Jc::CENTER, 'spaceAfter' => 200], ['size' => 18]);
        $this->para($s, [['THIS DEED OF PARTNERSHIP,', 'b'], [" made on this $dd", ''], [$dsuf, 's'], [' day of ' . $f->deed_date->format('F, Y') . ' between:', '']], $J + ['spaceAfter' => 160]);
        $this->para($s, [['NAME OF THE PARTNERS AND THEIR CNICs', 'bu']], ['spaceAfter' => 120]);
        foreach ($partners as $i => $p) {
            $this->para($s, [['Partner No. ' . ($i + 1) . '.', 'b'],
                ["\t" . Firm::fullName($p) . ', ' . rtrim($p['address'] ?? '', '.') . ' Having CNIC # ' . ($p['cnic'] ?? ''), '']],
                $J + ['spaceAfter' => 120, 'indentation' => ['left' => self::cm(2.8), 'hanging' => self::cm(2.8)], 'tabs' => [new Tab('left', self::cm(2.8))]]);
        }
        $this->para($s, [['WITNESSTH WHERE OF AS FOLLOWED:', 'bu']], ['spaceBefore' => 80, 'spaceAfter' => 120]);
        $this->para($s, [['WHEREAS', 'b'], [' the parties above mentioned have agreed to carry on the business in partnership, it is hereby declared by them that, the terms and conditions for the said business be reduced to writing.', '']], $J + ['spaceAfter' => 160]);
        $this->para($s, [['NOW, THEREFORE,', 'b'], [' both the parties above described, bind themselves and their executors, administrators and legal heirs as under;', '']], $J + ['spaceAfter' => 200]);

        $n = 0;
        $clause = function (string $head, array $body, int $after = 200) use ($s, &$n, $J) {
            $n++;
            $this->para($s, array_merge([["$n.", 'b'], ["\t", ''], [$head, 'bu'], [': ', 'b']], $body),
                $J + ['spaceAfter' => $after, 'indentation' => ['left' => self::cm(1.0), 'hanging' => self::cm(1.0)], 'tabs' => [new Tab('left', self::cm(1.0))]]);
        };
        $shareTable = function (string $key) use ($s, $partners) {
            $t = $this->grid($s);
            foreach ($partners as $i => $p) {
                $row = $t->addRow(null, ['cantSplit' => true]);
                $this->para($row->addCell(self::cm(13.0)), [[($i + 1) . '.  ' . Firm::fullName($p), '']], ['indentation' => ['left' => self::cm(0.8)]]);
                $this->para($row->addCell(self::cm(3.5)), [[rtrim((string) ($p[$key] ?? ''), '%') . '%', 'b']]);
            }
            $s->addTextBreak(1, ['size' => 6]);
        };

        $clause('COMMENCEMENT OF BUSINESS', [["That, the partnership hereby constituted shall deemed to have been commenced from $cd", ''], [$csuf, 's'],
            [' ' . $f->commencement_date->format('F, Y') . ' (' . $f->commencement_date->format('d-m-Y') . ').', '']]);
        $clause('FIRMS NAME, BUSINESS & ADDRESS', [['That, the parties shall carry on the business of ' . rtrim(trim($f->business_description), '.') . ', and/or may carry on any other business under the name and style of, ', ''],
            [$firmQ, 'b'], [', and that, the head office of the firm shall be situated at ', ''], [$place . '.', 'b']]);
        $clause('THE CAPITAL', [['That, the capital of the firm shall be, ', ''], ['“AS PER BOOKS OF ACCOUNTS”', 'b'],
            [', it is further agreed that, the capital of the firm may be increased or decreased according to the magnitude, and requirements of the business or by the desire of parties.', '']], 120);
        $this->para($s, [['Entitlement / ascertainment of Capital of firm shall be as follows.', '']], ['indentation' => ['left' => self::cm(1.0)], 'spaceAfter' => 80]);
        $shareTable('capital_share');
        $clause('SHARE IN PROFIT & LOSS', [['That, the shares of partners in profit & loss including the loss of capital of the firm shall be as under:', '']], 80);
        $shareTable('profit_share');
        $clause('BOOKS OF ACCOUNTS', [['That, usual book of accounts shall be maintained by business. The books of accounts shall be opened to inspect to all partner during the working hours of the firm and shall be kept at the head office of the firm ', ''], [$place . '.', 'b']]);
        $clause('ANNUAL ACCOUNTS & DRAWINGS', [['The annual accounts shall be closed at the end of 30', ''], ['th', 's'],
            [' June every year and profit or loss, as the case may be, shall be carried out to respective personal accounts of the partners according to their stipulated / allocated shares.', '']]);

        $mp = $f->managingPartner();
        $mgr = $mp ? 'the Managing Partner' : 'the partners';
        if ($mp) {
            $clause('MANAGING PARTNER: RIGHTS AND OBLIGATIONS', [], 80);
            $this->para($s, [['Mr. ' . Firm::fullName($mp) . ',', 'b'], [' being the duly appointed Managing Partner of the firm, shall have the following rights, powers, duties, and obligations in connection with the partnership business:', '']],
                $J + ['indentation' => ['left' => self::cm(1.0)], 'spaceAfter' => 80]);
            $powers = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $f->managing_powers ?: Firm::DEFAULT_MANAGING_POWERS))));
            $powers[] = [['The Managing Partner, ', ''], ['Mr. ' . Firm::fullName($mp) . ',', 'b'],
                [' shall have the right and authority to induct, admit, or include new partners into the firm in the future, subject to the terms of the Partnership Deed and applicable laws of Pakistan. He shall have the authority to determine and negotiate the incoming partner’s capital contribution, profit and loss sharing ratio, rights, duties, and other terms and conditions of admission, which shall be documented in writing through a supplementary or amended Partnership Deed and the necessary registration or statutory formalities shall be completed, where applicable.', '']];
            if (count($partners) > 1) {
                $powers[] = 'All other partners shall be considered silent partners and shall not participate in the day-to-day management or operational affairs of the firm.';
            }
            foreach ($powers as $k => $pw) {
                $runs = is_array($pw) ? $pw : [[$pw, '']];
                array_unshift($runs, [($k + 1) . ".\t", '']);
                $this->para($s, $runs, $J + ['spaceAfter' => 60, 'indentation' => ['left' => self::cm(1.8), 'hanging' => self::cm(0.8)], 'tabs' => [new Tab('left', self::cm(1.8))]]);
            }
            $s->addTextBreak(1, ['size' => 6]);
        }

        $clause('THE BANKERS', [['That, the firm may open its bank account(s) with any schedule bank /banks and the same, if any, shall be operated jointly by either of the partner with the mutual consent of other. The firm or any one of the partner can approach scheduled bank(s)/financial institution for finance facility/loan(s) in any shape.', '']]);
        $clause('ATTENTION TO BUSINESS', [["The duration of the firm shall be indefinite and shall continue unless and until its dissolution is decided by $mgr of the firm in accordance with the terms of this Partnership Deed and applicable law.", '']]);
        $clause('DEATH', [['That, the firm shall not be dissolved by the death of either of its partner(s), but the deceased partner or partners’ legal/personal representative or for the matter nominee or nominees shall be taken as partner/partners by drawing a fresh deed of partnership by the consent of existing partner/partners.', '']]);
        $clause('WINDING UP / WITHDRAWAL OF PARTNER', [['That the partnership shall be a partnership at will. Upon dissolution of the partnership, its affairs shall be wound up in accordance with the provisions of the Partnership Act, 1932, as applicable in Pakistan, and all other applicable laws. No partner shall be entitled to sell, transfer, assign, or otherwise dispose of their share or interest in the partnership to any outsider or third party. Any partner intending to withdraw, retire, or discontinue their participation in the business shall provide prior written notice to ' . ($mp ? 'the Managing Partner' : 'the other partners') . '. Any such withdrawal, retirement, or related matter shall be considered and settled with the mutual consent of ' . ($mp ? 'the Managing Partner and ' : '') . 'all partners. Any withdrawal or retirement shall be subject to the terms and conditions of this Partnership Deed and applicable law.', '']]);
        $clause('PAYMENT OF INCOME TAX ETC', [['That, every partner shall be responsible for the payment of income tax etc, levied on the firm and partners as well, according to their stipulated shares.', '']]);
        $clause('ARBITRATION', [['That, in case of any dispute or disagreement connected with partnership business, it shall be amicably settled by the parties giving, as far as possible due respect and weight to the opinion of executive director, or otherwise the partners shall have the right to refer the matter to one or two arbitrators appointed by mutual agreement, whose decision shall be final.', '']]);
        $clause('MISCELLANEOUS', [['That, the terms and conditions not specified in the deed shall be covered by the provisions of partnership act, 1932, as applicable in Pakistan & all other enabling laws.', '']], 240);

        $all = count($partners) === 2 ? 'both' : 'all';
        $this->para($s, [['IN WITNESS THEREOF,', 'b'], [" we $all the partners have hereunto affixed our signatures in the presence of witnesses:", '']], $J + ['spaceAfter' => 240, 'keepNext' => true]);

        $this->deedSignatures($s, 'P A R T N E R S', $partners);
        $this->deedSignatures($s, 'W I T N E S S E S', $this->witnesses());
        return $w;
    }

    private function deedSignatures(Section $s, string $title, array $people): void
    {
        $this->para($s, [[$title, 'bu']], ['alignment' => Jc::CENTER, 'spaceAfter' => 160, 'keepNext' => true], ['size' => 13]);
        $t = $this->grid($s);
        foreach (array_chunk($people, 2, true) as $pair) {
            $row = $t->addRow(null, ['cantSplit' => true]);
            foreach (array_values(array_pad(array_keys($pair), 2, null)) as $k) {
                $cell = $row->addCell(self::cm(8.25));
                if ($k === null) continue;
                $p = $pair[$k];
                $this->para($cell, [[sprintf('%02d- %s', $k + 1, $p['name'] ?? ''), '']]);
                $this->para($cell, [[($p['relation'] ?? 'S/O') . ' ' . ($p['father_name'] ?? ''), '']], ['spaceAfter' => 360]);
                $this->para($cell, [['Sign: ____________________', '']], ['spaceAfter' => 200]);
            }
        }
    }
}
