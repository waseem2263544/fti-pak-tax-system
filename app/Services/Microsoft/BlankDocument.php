<?php

namespace App\Services\Microsoft;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Bytes for a new, empty Office document.
 *
 * Graph will create a zero-byte .docx quite happily, but Word refuses to open
 * one — so "New Word document" has to upload a real, if empty, file. The .docx
 * is assembled here as minimal OOXML rather than pulling in another dependency;
 * the .xlsx comes from PhpSpreadsheet, which is already installed.
 */
class BlankDocument
{
    public static function forExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'docx' => self::word(),
            'xlsx' => self::excel(),
            default => throw new \InvalidArgumentException("No blank template for .{$extension}"),
        };
    }

    public static function excel(): string
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->setTitle('Sheet1');

        $tmp = tempnam(sys_get_temp_dir(), 'blank') . '.xlsx';
        (new Xlsx($book))->save($tmp);
        $bytes = file_get_contents($tmp);

        @unlink($tmp);
        $book->disconnectWorksheets();

        return $bytes;
    }

    /** A minimal but valid WordprocessingML package: three parts in a zip. */
    public static function word(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'blank') . '.docx';

        $zip = new \ZipArchive();

        if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not assemble a blank Word document.');
        }

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML);

        $zip->addFromString('word/document.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:body><w:p/><w:sectPr/></w:body>
</w:document>
XML);

        $zip->close();

        $bytes = file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }
}
