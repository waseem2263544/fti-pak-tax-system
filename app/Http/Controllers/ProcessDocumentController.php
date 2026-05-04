<?php

namespace App\Http\Controllers;

use App\Models\Process;
use Illuminate\Http\Request;

class ProcessDocumentController extends Controller
{
    public function generate(Process $process, $document)
    {
        $meta = $process->metadata ?? [];
        $process->load('client');

        $templates = [
            'appeal-memo' => [
                'view' => 'processes.documents.appeal-memo',
                'filename' => 'Appeal Memo - ' . ($meta['appellant_name'] ?? $process->client->name ?? 'Client'),
            ],
            'stay-application' => [
                'view' => 'processes.documents.stay-application',
                'filename' => 'Stay Application - ' . ($meta['appellant_name'] ?? $process->client->name ?? 'Client'),
            ],
            'intimation' => [
                'view' => 'processes.documents.intimation',
                'filename' => 'Intimation - ' . ($meta['appellant_name'] ?? $process->client->name ?? 'Client'),
            ],
            'affidavit' => [
                'view' => 'processes.documents.affidavit',
                'filename' => 'Affidavit - ' . ($meta['appellant_name'] ?? $process->client->name ?? 'Client'),
            ],
            'index' => [
                'view' => 'processes.documents.index-page',
                'filename' => 'Index - ' . ($meta['appellant_name'] ?? $process->client->name ?? 'Client'),
            ],
            'grounds-of-appeal' => [
                'view' => 'processes.documents.grounds-of-appeal',
                'filename' => 'Grounds of Appeal - ' . ($meta['appellant_name'] ?? $process->client->name ?? 'Client'),
            ],
            'power-of-attorney' => [
                'view' => 'processes.documents.power-of-attorney',
                'filename' => 'Power of Attorney - ' . ($meta['appellant_name'] ?? $process->client->name ?? 'Client'),
            ],
        ];

        if (!isset($templates[$document])) {
            return back()->with('error', 'Document template not found');
        }

        $template = $templates[$document];
        $html = view($template['view'], compact('process', 'meta'))->render();

        // Wrap in Word-compatible HTML
        $wordHtml = '
        <html xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:w="urn:schemas-microsoft-com:office:word"
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <!--[if gte mso 9]>
            <xml>
                <w:WordDocument>
                    <w:View>Print</w:View>
                    <w:Zoom>100</w:Zoom>
                    <w:DoNotOptimizeForBrowser/>
                </w:WordDocument>
            </xml>
            <![endif]-->
            <style>
                @page { size: A4; margin: 1in 1in 1in 1in; }
                body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; color: #000; }
                h1 { font-size: 14pt; text-align: center; font-weight: bold; text-transform: uppercase; margin-bottom: 8pt; }
                h2 { font-size: 13pt; text-align: center; font-weight: bold; margin-bottom: 6pt; }
                h3 { font-size: 12pt; font-weight: bold; margin-bottom: 6pt; }
                p { margin: 6pt 0; text-align: justify; }
                .center { text-align: center; }
                .right { text-align: right; }
                .bold { font-weight: bold; }
                .underline { text-decoration: underline; }
                .indent { margin-left: 36pt; }
                .signature { margin-top: 36pt; }
                .page-break { page-break-before: always; }
                table { border-collapse: collapse; width: 100%; }
                td, th { border: 1px solid #000; padding: 6pt 8pt; font-size: 11pt; }
                th { background-color: #f0f0f0; font-weight: bold; }
                .no-border td, .no-border th { border: none; }
                /* Aggressively normalise rich-text content (Quill / Word paste) so it matches the surrounding doc */
                .rich-content, .rich-content * {
                    background: transparent !important;
                    background-color: transparent !important;
                    border: 0 none !important;
                    box-shadow: none !important;
                    outline: 0 none !important;
                    text-shadow: none !important;
                    font-family: "Times New Roman", Times, serif !important;
                    color: #000 !important;
                }
                .rich-content { line-height: 1.5; font-size: 12pt; }
                .rich-content p { margin: 0 0 10pt; line-height: 1.5; text-align: justify; }
                .rich-content p:last-child { margin-bottom: 0; }
                .rich-content ul { list-style: disc; padding-left: 2.2em; margin: 8pt 0 10pt; }
                .rich-content ol { list-style: decimal; padding-left: 2.2em; margin: 8pt 0 10pt; }
                .rich-content li { margin: 0 0 4pt; line-height: 1.5; }
                .rich-content .ql-indent-1 { padding-left: 2em; }
                .rich-content .ql-indent-2 { padding-left: 4em; }
                .rich-content .ql-indent-3 { padding-left: 6em; }
                .rich-content .ql-indent-4 { padding-left: 8em; }
                .rich-content .ql-indent-5 { padding-left: 10em; }
            </style>
        </head>
        <body>' . $html . '</body></html>';

        $filename = $template['filename'] . '.doc';

        return response($wordHtml)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    public function combined(Process $process)
    {
        $meta = $process->metadata ?? [];
        $process->load('client');

        $content = view('processes.documents.combined-package', compact('process', 'meta'))->render();

        return view('processes.documents.preview', [
            'process' => $process,
            'meta' => $meta,
            'content' => $content,
            'documentName' => 'Combined Package',
        ]);
    }

    /**
     * Build a single merged PDF using a TWO-PASS render:
     *  Pass 1: render body (everything except Index) to a temp PDF, tracking
     *          which physical page each section starts on.
     *  Pass 2: render Index page using the measured starts, then merge in
     *          the body PDF pages via FPDI.
     * That way the Index reflects actual page positions even when generated
     * docs (e.g. Grounds of Appeal) overflow to multiple pages.
     */
    public function combinedPdf(Process $process)
    {
        $meta = $process->metadata ?? [];
        $process->load('client');

        $tempDir = storage_path('app/mpdf-temp');
        if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);

        $mpdfConfig = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 18,
            'margin_right' => 18,
            // Default = compact (page-number-only pages)
            'margin_top' => 22,
            'margin_bottom' => 18,
            'margin_header' => 8,
            'margin_footer' => 8,
            'tempDir' => $tempDir,
        ];

        // Letterhead pages need top/bottom margins big enough to fit the running letterhead header/footer
        // (mgt reduced by ~25mm = 1 inch from earlier 35mm; mgh reduced from 8 to 3 so letterhead sits near the top edge)
        $letterheadMargins = ['mgl' => 18, 'mgr' => 18, 'mgt' => 18, 'mgb' => 22, 'mgh' => 3, 'mgf' => 8];
        $compactMargins    = ['mgl' => 18, 'mgr' => 18, 'mgt' => 22, 'mgb' => 18, 'mgh' => 8, 'mgf' => 8];

        $renderDoc = function ($view, $extraMeta = []) use ($process, $meta) {
            $content = view($view, [
                'process' => $process,
                'meta' => array_merge($meta, $extraMeta),
                'inCombinedPdf' => true,
            ])->render();
            return view('processes.documents._pdf-wrapper', ['content' => $content])->render();
        };

        $letterheadHeader = view('processes.documents._letterhead-header')->render();
        $letterheadFooter = '<div style="border-top: 0.6pt solid #303a50; padding-top: 4pt; font-size: 8.5pt; color: #444; text-align: center; line-height: 1.4;">'
            . '<div style="text-align: center;"><b>Services:</b> Financial, Income Tax, Sales Tax, Federal Excise &amp; Corporate Service Providers</div>'
            . '<div style="text-align: center;"><b>Address:</b> Office No. TF-121, 3rd Floor, Deans Trade Center, Islamia Road, Peshawar Cantt.</div>'
            . '</div>';
        $pageNumHeader = '<div style="text-align: right; font-size: 26pt; font-weight: 900; color: #000;">{PAGENO}</div>';
        $letterheadWithPageNum = '<table style="width:100%; border:none; border-collapse:collapse;"><tr>'
            . '<td style="border:none; padding:0; vertical-align:top;">' . $letterheadHeader . '</td>'
            . '<td style="border:none; padding:0; vertical-align:top; width:50pt; text-align:right; font-size:22pt; font-weight:900;">{PAGENO}</td>'
            . '</tr></table>';

        // ─── PASS 1 ─── Render body, track section start pages ────────────
        $body = new \Mpdf\Mpdf($mpdfConfig);

        // Register named headers/footers ONCE so each AddPage can bind exactly the right pair
        $body->DefHTMLHeaderByName('h_pagenum',    $pageNumHeader);
        $body->DefHTMLHeaderByName('h_letterhead', $letterheadWithPageNum);
        $body->DefHTMLHeaderByName('h_blank',      '');
        $body->DefHTMLFooterByName('f_letterhead', $letterheadFooter);
        $body->DefHTMLFooterByName('f_blank',      '');

        $starts = [];
        $writeSection = function ($key, $view, $headerName = 'h_pagenum', $footerName = 'f_blank', $useLetterhead = false) use ($body, $renderDoc, $letterheadMargins, $compactMargins, &$starts) {
            $m = $useLetterhead ? $letterheadMargins : $compactMargins;
            $resetpagenum = empty($starts) ? '1' : '';
            // AddPage with explicit header/footer names so this page (and overflow) gets exactly the right pair.
            // The previous page is closed with whatever header/footer it was opened with -- no leakage.
            $body->AddPage(
                '', '', $resetpagenum, '', '',
                $m['mgl'], $m['mgr'], $m['mgt'], $m['mgb'], $m['mgh'], $m['mgf'],
                $headerName, $headerName,
                $footerName, $footerName
            );
            $starts[$key] = $body->page;
            $body->WriteHTML($renderDoc($view));
        };

        $writeSection('appeal_memo',  'processes.documents.appeal-memo',        'h_pagenum', 'f_blank');
        $writeSection('stay_app',     'processes.documents.stay-application',   'h_pagenum', 'f_blank');
        $writeSection('grounds',      'processes.documents.grounds-of-appeal',  'h_pagenum', 'f_blank');

        // Imported attachments
        foreach ([
            'order_in_appeal_file'    => 'order_in_appeal',
            'order_in_original_file' => 'order_in_original',
            'recovery_notice_file'   => 'recovery_notice',
        ] as $field => $key) {
            if (empty($meta[$field])) { $starts[$key] = null; continue; }
            $abs = public_path($meta[$field]);
            if (!file_exists($abs)) { $starts[$key] = null; continue; }

            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));

            if ($ext === 'pdf') {
                try {
                    $pageCount = $body->setSourceFile($abs);
                    for ($i = 1; $i <= $pageCount; $i++) {
                        $tplId = $body->importPage($i);
                        $size = $body->getTemplateSize($tplId);
                        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                        // Bind blank header/footer explicitly so neither the page-num header nor the letterhead leaks onto this full-bleed page
                        $body->AddPageByArray([
                            'orientation' => $orientation,
                            'sheet-size'  => [$size['width'], $size['height']],
                            'mgl' => 0, 'mgr' => 0, 'mgt' => 0, 'mgb' => 0, 'mgh' => 0, 'mgf' => 0,
                            'ohname' => 'h_blank', 'ehname' => 'h_blank',
                            'ofname' => 'f_blank', 'efname' => 'f_blank',
                        ]);
                        if ($i === 1) $starts[$key] = $body->page;
                        $body->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
                        // Stamp the running page number on top of the imported page (top-right corner)
                        $body->SetFont('', 'B', 22);
                        $body->SetTextColor(0, 0, 0);
                        $body->SetXY($size['width'] - 22, 6);
                        $body->Cell(16, 8, (string) $body->page, 0, 0, 'R');
                    }
                } catch (\Exception $e) {
                    $body->AddPage();
                    $starts[$key] = $body->page;
                    $body->WriteHTML('<p style="text-align:center; padding-top: 3in;">Could not embed attachment.</p>');
                }
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $body->AddPage(
                    '', '', '', '', '',
                    $compactMargins['mgl'], $compactMargins['mgr'], $compactMargins['mgt'], $compactMargins['mgb'], $compactMargins['mgh'], $compactMargins['mgf'],
                    'h_pagenum', 'h_pagenum', 'f_blank', 'f_blank'
                );
                $starts[$key] = $body->page;
                $body->WriteHTML('<img src="' . $abs . '" style="max-width: 100%; max-height: 9.5in;">');
            } else {
                $starts[$key] = null;
            }
        }

        $writeSection('intimation', 'processes.documents.intimation',         'h_letterhead', 'f_letterhead', true);
        $writeSection('poa',        'processes.documents.power-of-attorney',  'h_pagenum',    'f_blank');
        $writeSection('affidavit',  'processes.documents.affidavit',          'h_pagenum',    'f_blank');

        $bodyTotalPages = $body->page;
        $bodyPath = $tempDir . '/body-' . $process->id . '-' . uniqid() . '.pdf';
        $body->Output($bodyPath, \Mpdf\Output\Destination::FILE);

        // Compute per-section page counts (end - start + 1) for the index ranges
        $orderedKeys = ['appeal_memo', 'stay_app', 'grounds', 'order_in_appeal', 'order_in_original', 'recovery_notice', 'intimation', 'poa', 'affidavit'];
        $present = array_filter($orderedKeys, fn($k) => isset($starts[$k]) && $starts[$k] !== null);
        $present = array_values($present);
        $sectionPages = [];
        foreach ($present as $i => $k) {
            $startP = $starts[$k];
            $endP = $i + 1 < count($present) ? ($starts[$present[$i + 1]] - 1) : $bodyTotalPages;
            $sectionPages[$k] = $endP - $startP + 1;
        }

        // ─── PASS 2 ─── Build final: Index (with measured counts) + body
        $final = new \Mpdf\Mpdf($mpdfConfig);
        $final->DefHTMLHeaderByName('h_letterhead_only', $letterheadHeader);
        $final->DefHTMLHeaderByName('h_blank',           '');
        $final->DefHTMLFooterByName('f_letterhead',      $letterheadFooter);
        $final->DefHTMLFooterByName('f_blank',           '');

        // Index page binds the letterhead-only header and letterhead footer
        $final->AddPage(
            '', '', '', '', '',
            $letterheadMargins['mgl'], $letterheadMargins['mgr'], $letterheadMargins['mgt'], $letterheadMargins['mgb'], $letterheadMargins['mgh'], $letterheadMargins['mgf'],
            'h_letterhead_only', 'h_letterhead_only',
            'f_letterhead',      'f_letterhead'
        );
        $final->WriteHTML($renderDoc('processes.documents.index-page', [
            '__section_starts' => $starts,
            '__section_pages'  => $sectionPages,
        ]));

        // Import body pages -- each binds blank header/footer so the index's letterhead does not leak
        try {
            $bodyCount = $final->setSourceFile($bodyPath);
            for ($i = 1; $i <= $bodyCount; $i++) {
                $tplId = $final->importPage($i);
                $size = $final->getTemplateSize($tplId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $final->AddPageByArray([
                    'orientation' => $orientation,
                    'sheet-size' => [$size['width'], $size['height']],
                    'mgl' => 0, 'mgr' => 0, 'mgt' => 0, 'mgb' => 0, 'mgh' => 0, 'mgf' => 0,
                    'ohname' => 'h_blank', 'ehname' => 'h_blank',
                    'ofname' => 'f_blank', 'efname' => 'f_blank',
                ]);
                $final->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
            }
        } catch (\Exception $e) {
            // body PDF couldn't be re-imported; leave Index as is
        }

        $clientName = $meta['appellant_name'] ?? $process->client->name ?? 'Process';
        $filename = 'Combined Package - ' . $clientName . '.pdf';
        $final->Output($filename, \Mpdf\Output\Destination::INLINE);

        @unlink($bodyPath);
    }

    public function preview(Process $process, $document)
    {
        $meta = $process->metadata ?? [];
        $process->load('client');

        $views = [
            'appeal-memo' => 'processes.documents.appeal-memo',
            'stay-application' => 'processes.documents.stay-application',
            'intimation' => 'processes.documents.intimation',
            'affidavit' => 'processes.documents.affidavit',
            'index' => 'processes.documents.index-page',
            'grounds-of-appeal' => 'processes.documents.grounds-of-appeal',
            'power-of-attorney' => 'processes.documents.power-of-attorney',
        ];

        if (!isset($views[$document])) {
            return back()->with('error', 'Document not found');
        }

        return view('processes.documents.preview', [
            'process' => $process,
            'meta' => $meta,
            'content' => view($views[$document], compact('process', 'meta'))->render(),
            'documentName' => ucwords(str_replace('-', ' ', $document)),
        ]);
    }
}
