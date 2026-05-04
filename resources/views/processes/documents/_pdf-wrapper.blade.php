{{-- mPDF render wrapper: bundles the same styles as the preview/.doc generator so individual document templates render correctly inside mPDF. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
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
        /* Aggressively normalise rich-text content (Quill / Word paste) so it looks like the surrounding doc */
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
<body>
{!! $content !!}
</body>
</html>
