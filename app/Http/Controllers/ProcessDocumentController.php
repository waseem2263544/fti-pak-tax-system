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
                /* Strip pasted highlights from rich-text content (Quill / Word paste) */
                .rich-content, .rich-content * { background-color: transparent !important; }
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
