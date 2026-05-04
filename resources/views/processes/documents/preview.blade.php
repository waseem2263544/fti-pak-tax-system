<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $documentName }} - {{ $meta['appellant_name'] ?? $process->client->name ?? '' }}</title>
    <style>
        @page { size: A4; margin: 1in; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; color: #000; max-width: 800px; margin: 0 auto; padding: 40px; }
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

        /* Print toolbar */
        .toolbar {
            position: fixed; top: 0; left: 0; right: 0;
            background: #303a50; padding: 12px 24px; z-index: 1000;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .toolbar h3 { color: #fff; margin: 0; font-family: sans-serif; font-size: 14px; }
        .toolbar .btns { display: flex; gap: 8px; }
        .toolbar .btn {
            padding: 8px 16px; border-radius: 8px; border: none;
            font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: sans-serif; text-decoration: none;
        }
        .toolbar .btn-print { background: #D7DF27; color: #303a50; }
        .toolbar .btn-download { background: #fff; color: #303a50; }
        .toolbar .btn-back { background: none; color: #fff; border: 1px solid rgba(255,255,255,0.3); }
        body { padding-top: 80px; }
        @media print { .toolbar { display: none; } body { padding-top: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <h3>{{ $documentName }}</h3>
        <div class="btns">
            <a href="{{ route('processes.document.generate', [$process, Str::slug($documentName)]) }}" class="btn btn-download">⬇ Download .doc</a>
            <button onclick="window.print()" class="btn btn-print">🖨 Print / Save PDF</button>
            <a href="{{ route('processes.show', $process) }}" class="btn btn-back">← Back</a>
        </div>
    </div>

    {!! $content !!}
</body>
</html>
