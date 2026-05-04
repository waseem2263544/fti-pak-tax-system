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
    public function combinedPdf(Process $process, Request $request)
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

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

        // Letterhead pages need enough top room for the full letterhead band (logo + 4 contact lines side-by-side)
        $letterheadMargins = ['mgl' => 18, 'mgr' => 18, 'mgt' => 28, 'mgb' => 22, 'mgh' => 5, 'mgf' => 8];
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
        // Page number sits as a top-right line above the letterhead (mPDF's CSS position:absolute is unreliable in running headers, so a stacked layout is more predictable)
        $letterheadWithPageNum = '<div style="text-align: right; font-size: 18pt; font-weight: 900; line-height: 1; margin: 0 0 2pt;">{PAGENO}</div>'
            . $letterheadHeader;

        // ─── Body section renderer (used in both passes) ────────────────────
        // Renders Appeal Memo through Affidavit + uploaded attachments. Returns the section_start map.
        // mPDF lifecycle quirk: AddPage closes the previous page with the current HTMLFooter and opens the new page with the current HTMLHeader.
        // Pattern used here: SetHTMLHeader before AddPage (so the new page opens with the new header), SetHTMLFooter after AddPage
        // (so the new page closes with the new footer at its next page break).
        $renderBodySections = function (\Mpdf\Mpdf $mpdf) use ($renderDoc, $pageNumHeader, $letterheadWithPageNum, $letterheadFooter, $letterheadMargins, $compactMargins, $meta) {
            $starts = [];
            $writeSection = function ($key, $view, $hdr, $ftr, $useLetterhead = false) use ($mpdf, $renderDoc, $letterheadMargins, $compactMargins, &$starts) {
                $m = $useLetterhead ? $letterheadMargins : $compactMargins;
                $resetpagenum = empty($starts) ? '1' : '';
                $mpdf->SetHTMLHeader($hdr);
                $mpdf->AddPage('', '', $resetpagenum, '', '', $m['mgl'], $m['mgr'], $m['mgt'], $m['mgb'], $m['mgh'], $m['mgf']);
                $mpdf->SetHTMLFooter($ftr);
                $starts[$key] = $mpdf->page;
                $mpdf->WriteHTML($renderDoc($view));
            };

            $writeSection('appeal_memo',  'processes.documents.appeal-memo',        $pageNumHeader, '');
            $writeSection('stay_app',     'processes.documents.stay-application',   $pageNumHeader, '');
            $writeSection('grounds',      'processes.documents.grounds-of-appeal',  $pageNumHeader, '');

            foreach ([
                'order_in_appeal_file'   => 'order_in_appeal',
                'order_in_original_file' => 'order_in_original',
                'recovery_notice_file'   => 'recovery_notice',
            ] as $field => $key) {
                if (empty($meta[$field])) { $starts[$key] = null; continue; }
                $abs = public_path($meta[$field]);
                if (!file_exists($abs)) { $starts[$key] = null; continue; }

                $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));

                if ($ext === 'pdf') {
                    try {
                        $pageCount = $mpdf->setSourceFile($abs);
                        for ($i = 1; $i <= $pageCount; $i++) {
                            $tplId = $mpdf->importPage($i);
                            $size = $mpdf->getTemplateSize($tplId);
                            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                            $mpdf->SetHTMLHeader('');
                            $mpdf->AddPageByArray([
                                'orientation' => $orientation,
                                'sheet-size'  => [$size['width'], $size['height']],
                                'mgl' => 0, 'mgr' => 0, 'mgt' => 0, 'mgb' => 0, 'mgh' => 0, 'mgf' => 0,
                            ]);
                            $mpdf->SetHTMLFooter('');
                            if ($i === 1) $starts[$key] = $mpdf->page;
                            $mpdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
                            $mpdf->SetFont('', 'B', 22);
                            $mpdf->SetTextColor(0, 0, 0);
                            $mpdf->SetXY($size['width'] - 22, 6);
                            // docPageNum honours resetpagenum + pagenumStyle, matching what {PAGENO} prints on generated pages
                            $mpdf->Cell(16, 8, (string) $mpdf->docPageNum($mpdf->page), 0, 0, 'R');
                        }
                    } catch (\Exception $e) {
                        $mpdf->AddPage();
                        $starts[$key] = $mpdf->page;
                        $mpdf->WriteHTML('<p style="text-align:center; padding-top: 3in;">Could not embed attachment.</p>');
                    }
                } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $mpdf->SetHTMLHeader($pageNumHeader);
                    $mpdf->AddPage('', '', '', '', '', $compactMargins['mgl'], $compactMargins['mgr'], $compactMargins['mgt'], $compactMargins['mgb'], $compactMargins['mgh'], $compactMargins['mgf']);
                    $mpdf->SetHTMLFooter('');
                    $starts[$key] = $mpdf->page;
                    $mpdf->WriteHTML('<img src="' . $abs . '" style="max-width: 100%; max-height: 9.5in;">');
                } else {
                    $starts[$key] = null;
                }
            }

            $writeSection('intimation', 'processes.documents.intimation',        $letterheadWithPageNum, $letterheadFooter, true);
            $writeSection('poa',        'processes.documents.power-of-attorney', $pageNumHeader,         '');
            $writeSection('affidavit',  'processes.documents.affidavit',         $pageNumHeader,         '');

            return [$starts, $mpdf->page];
        };

        // ─── PASS 1 ─── Quick count: render each generated doc to a tiny throwaway mPDF and count pages.
        // User PDFs are not imported here -- we already know their page counts from upload-time auto-detection.
        $countDocPages = function ($view, $marginType = 'compact') use ($mpdfConfig, $renderDoc, $compactMargins, $letterheadMargins) {
            $temp = new \Mpdf\Mpdf($mpdfConfig);
            $m = $marginType === 'letterhead' ? $letterheadMargins : $compactMargins;
            $temp->AddPage('', '', '', '', '', $m['mgl'], $m['mgr'], $m['mgt'], $m['mgb'], $m['mgh'], $m['mgf']);
            $temp->WriteHTML($renderDoc($view));
            $count = $temp->page;
            unset($temp);
            return max(1, (int) $count);
        };

        $amPages   = $countDocPages('processes.documents.appeal-memo');
        $saPages   = $countDocPages('processes.documents.stay-application');
        $grPages   = $countDocPages('processes.documents.grounds-of-appeal');
        $intPages  = $countDocPages('processes.documents.intimation',         'letterhead');
        $poaPages  = $countDocPages('processes.documents.power-of-attorney');
        $affPages  = $countDocPages('processes.documents.affidavit');

        $orderInAppealPages   = max(1, (int) ($meta['order_in_appeal_file_pages']   ?? 1));
        $orderInOriginalPages = max(1, (int) ($meta['order_in_original_file_pages'] ?? 1));
        $recoveryNoticePages  = max(1, (int) ($meta['recovery_notice_file_pages']   ?? 1));

        // Section starts are 1-based (Appeal Memo = page 1, since the page counter resets when body rendering begins)
        $pAppealMemo      = 1;
        $pStayApp         = $pAppealMemo      + $amPages;
        $pGrounds         = $pStayApp         + $saPages;
        $pOrderInAppeal   = $pGrounds         + $grPages;
        $pOrderInOriginal = $pOrderInAppeal   + $orderInAppealPages;
        $pRecoveryNotice  = $pOrderInOriginal + $orderInOriginalPages;
        $pIntimation      = $pRecoveryNotice  + $recoveryNoticePages;
        $pPOA             = $pIntimation      + $intPages;
        $pAffidavit       = $pPOA             + $poaPages;

        // For uploaded slots that the user didn't upload, drop them from $starts so the Index hides their row
        $hasOrderInAppeal   = !empty($meta['order_in_appeal_file']);
        $hasOrderInOriginal = !empty($meta['order_in_original_file']);
        $hasRecoveryNotice  = !empty($meta['recovery_notice_file']);

        $starts = [
            'appeal_memo'       => $pAppealMemo,
            'stay_app'          => $pStayApp,
            'grounds'           => $pGrounds,
            'order_in_appeal'   => $hasOrderInAppeal   ? $pOrderInAppeal   : null,
            'order_in_original' => $hasOrderInOriginal ? $pOrderInOriginal : null,
            'recovery_notice'   => $hasRecoveryNotice  ? $pRecoveryNotice  : null,
            'intimation'        => $pIntimation,
            'poa'               => $pPOA,
            'affidavit'         => $pAffidavit,
        ];
        $sectionPages = [
            'appeal_memo'       => $amPages,
            'stay_app'          => $saPages,
            'grounds'           => $grPages,
            'order_in_appeal'   => $orderInAppealPages,
            'order_in_original' => $orderInOriginalPages,
            'recovery_notice'   => $recoveryNoticePages,
            'intimation'        => $intPages,
            'poa'               => $poaPages,
            'affidavit'         => $affPages,
        ];

        // ─── PASS 2 ─── Build final PDF: Index page first (with measured counts), then re-render the body fresh
        // Re-rendering the body in pass 2 (instead of FPDI re-importing the throwaway) avoids the slight whitespace introduced when imported PDFs round-trip through FPDI twice.
        $final = new \Mpdf\Mpdf($mpdfConfig);
        $final->SetHTMLHeader($letterheadHeader);
        $final->AddPage('', '', '', '', '', $letterheadMargins['mgl'], $letterheadMargins['mgr'], $letterheadMargins['mgt'], $letterheadMargins['mgb'], $letterheadMargins['mgh'], $letterheadMargins['mgf']);
        $final->SetHTMLFooter($letterheadFooter);
        $final->WriteHTML($renderDoc('processes.documents.index-page', [
            '__section_starts' => $starts,
            '__section_pages'  => $sectionPages,
        ]));

        // Re-render body fresh (each writeSection inside this call resets page counter at its first AddPage so body pages restart at 1)
        $renderBodySections($final);

        $clientName = $meta['appellant_name'] ?? $process->client->name ?? 'Process';
        $filename = 'Combined Package - ' . $clientName . '.pdf';
        $destination = $request->boolean('download') ? \Mpdf\Output\Destination::DOWNLOAD : \Mpdf\Output\Destination::INLINE;
        $final->Output($filename, $destination);
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
