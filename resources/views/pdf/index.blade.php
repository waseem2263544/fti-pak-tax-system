@extends('layouts.app')
@section('title', 'PDF Tools')
@section('page-title', 'PDF Tools')

@section('styles')
<style>
    /* Every tool is one card: drop a file, set the options, get a result. */
    .pt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
    .pt-tool { border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--surface);
               padding: 14px 16px; cursor: pointer; transition: border-color .12s, background .12s; }
    .pt-tool:hover { border-color: var(--n-300); background: var(--n-25); }
    .pt-tool.on { border-color: var(--accent); background: var(--accent-glow); }
    .pt-tool i { font-size: 1.1rem; color: var(--accent-dark); }
    .pt-tool h3 { font-size: .88rem; font-weight: 600; margin: 6px 0 2px; }
    .pt-tool p  { font-size: .76rem; color: var(--text-muted); margin: 0; line-height: 1.45; }

    .pt-drop { border: 2px dashed var(--border-strong); border-radius: var(--radius-lg);
               padding: 28px 20px; text-align: center; background: var(--surface);
               transition: border-color .12s, background .12s; cursor: pointer; }
    .pt-drop.over { border-color: var(--accent); background: var(--accent-glow); }
    .pt-drop i { font-size: 1.6rem; color: var(--text-faint); }

    .pt-files { display: flex; flex-direction: column; gap: 6px; margin-top: 12px; }
    .pt-file  { display: flex; align-items: center; gap: 10px; padding: 8px 12px; font-size: .83rem;
                border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface); }
    .pt-file .nm { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pt-file .sz { color: var(--text-muted); font-size: .75rem; font-variant-numeric: tabular-nums; }

    .pt-pages { display: grid; grid-template-columns: repeat(auto-fill, minmax(104px, 1fr)); gap: 10px; }
    .pt-page  { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden;
                background: var(--surface); position: relative; cursor: pointer; }
    .pt-page canvas { width: 100%; display: block; }
    .pt-page .no { position: absolute; top: 3px; left: 4px; background: rgba(0,0,0,.6); color: #fff;
                   font-size: .66rem; padding: 1px 5px; border-radius: 3px; }
    .pt-page.drop-sel { outline: 2px solid var(--danger); outline-offset: -2px; opacity: .45; }
    .pt-page .rot { position: absolute; bottom: 3px; right: 4px; background: rgba(0,0,0,.6); color: #fff;
                    font-size: .62rem; padding: 1px 5px; border-radius: 3px; }

    .pt-log { font: 11.5px/1.6 ui-monospace, Menlo, monospace; background: var(--n-900); color: #cfd6df;
              border-radius: var(--radius); padding: 10px 12px; max-height: 160px; overflow: auto; }
    .pt-busy { opacity: .55; pointer-events: none; }
</style>
@endsection

@section('content')
<p class="text-muted mb-3" style="font-size: .85rem;">
    Everything here runs in your browser. Files are never uploaded, so client documents stay on this
    machine &mdash; which also means large files are limited by this computer, not the server.
</p>

<div class="pt-grid mb-4" id="tools">
    <div class="pt-tool on" data-tool="merge">
        <i class="bi bi-union"></i><h3>Merge</h3>
        <p>Join several PDFs into one, in the order you arrange them.</p>
    </div>
    <div class="pt-tool" data-tool="pages">
        <i class="bi bi-grid-3x3-gap"></i><h3>Pages</h3>
        <p>Delete, rotate and reorder pages. Click a page to mark it for removal.</p>
    </div>
    <div class="pt-tool" data-tool="split">
        <i class="bi bi-scissors"></i><h3>Split / extract</h3>
        <p>Pull out a page range, or break a file into single pages.</p>
    </div>
    <div class="pt-tool" data-tool="clean">
        <i class="bi bi-eraser"></i><h3>Clean scan watermark</h3>
        <p>Cover or crop the CamScanner stamp along the foot of each page.</p>
    </div>
    <div class="pt-tool" data-tool="img2pdf">
        <i class="bi bi-file-earmark-image"></i><h3>Images to PDF</h3>
        <p>Turn photographs or screenshots of documents into one PDF.</p>
    </div>
    <div class="pt-tool" data-tool="pdf2img">
        <i class="bi bi-images"></i><h3>PDF to images</h3>
        <p>Save each page as a JPEG, at the resolution you choose.</p>
    </div>
    <div class="pt-tool" data-tool="compress">
        <i class="bi bi-file-zip"></i><h3>Compress</h3>
        <p>Re-render pages as images at lower quality. Best on scans.</p>
    </div>
    <div class="pt-tool" data-tool="unlock">
        <i class="bi bi-unlock"></i><h3>Remove password</h3>
        <p>Unlock a PDF you have the password for, or lift printing and copying restrictions.</p>
    </div>
    <div class="pt-tool" data-tool="stamp">
        <i class="bi bi-type"></i><h3>Number / stamp</h3>
        <p>Add page numbers or a line of text along the foot of each page.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong id="toolTitle">Merge</strong></div>
    <div class="card-body">
        <div class="pt-drop" id="drop">
            <i class="bi bi-cloud-arrow-up"></i>
            <div style="font-weight: 600; font-size: .9rem; margin-top: 6px;">Drop files here, or click to choose</div>
            <div class="text-muted" style="font-size: .78rem;" id="dropHint">PDFs, in the order you want them joined</div>
            <input type="file" id="picker" multiple accept="application/pdf,image/*" hidden>
        </div>

        <div class="pt-files" id="files"></div>

        <div id="options" class="mt-3"></div>

        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
            <button class="btn btn-accent btn-sm" id="run" disabled>Run</button>
            <button class="btn btn-outline-primary btn-sm" id="clear">Clear</button>
            <span class="text-muted" id="status" style="font-size: .82rem;"></span>
        </div>
    </div>
</div>

<div class="card mb-3 d-none" id="previewCard">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Pages</strong>
        <span class="text-muted" style="font-size: .78rem;" id="previewHint"></span>
    </div>
    <div class="card-body"><div class="pt-pages" id="pages"></div></div>
</div>

<div class="card d-none" id="outCard">
    <div class="card-header"><strong>Result</strong></div>
    <div class="card-body">
        <div class="pt-files" id="outFiles"></div>
        <div class="pt-log mt-3 d-none" id="log"></div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf-lib/1.17.1/pdf-lib.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
/*
 * PDF tools, entirely in the browser.
 *
 * pdf-lib rewrites the document; pdf.js renders pages, which is what makes the
 * page previews, the image export and the watermark clean possible. Nothing is
 * sent anywhere: a tax practice's scans have no business on a shared host just
 * to be rotated.
 */
pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const { PDFDocument, degrees, rgb, StandardFonts } = PDFLib;

let tool = 'merge';
let picked = [];          // { file, name, size, bytes }
let pageState = [];       // { index, rotate, drop } for the Pages tool

const el = id => document.getElementById(id);
const fmt = n => n > 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.round(n / 1024) + ' KB';
const say = m => { el('status').textContent = m; };

const HINTS = {
    merge:    'PDFs, in the order you want them joined',
    pages:    'One PDF',
    split:    'One PDF',
    clean:    'One or more scanned PDFs',
    img2pdf:  'Images — JPEG or PNG',
    pdf2img:  'One PDF',
    compress: 'One or more PDFs',
    stamp:    'One or more PDFs',
    unlock:   'One or more protected PDFs',
};

const OPTIONS = {
    split: `<div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label" for="spMode">How</label>
            <select id="spMode" class="form-select form-select-sm">
                <option value="range">Extract a range</option>
                <option value="each">One file per page</option>
            </select></div>
        <div class="col-auto"><label class="form-label" for="spFrom">From page</label>
            <input type="number" id="spFrom" class="form-control form-control-sm" value="1" min="1" style="width:110px"></div>
        <div class="col-auto"><label class="form-label" for="spTo">To page</label>
            <input type="number" id="spTo" class="form-control form-control-sm" value="1" min="1" style="width:110px"></div>
    </div>`,

    clean: `<div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label" for="clMode">Method</label>
            <select id="clMode" class="form-select form-select-sm">
                <option value="cover">Cover it in white</option>
                <option value="crop">Crop it away</option>
            </select></div>
        <div class="col-auto"><label class="form-label" for="clHeight">Strip height (% of page)</label>
            <input type="number" id="clHeight" class="form-control form-control-sm" value="5" min="1" max="25" step="0.5" style="width:130px"></div>
        <div class="col-12"><div class="form-text">
            CamScanner stamps the foot of the page. Covering keeps the page size; cropping shortens it.
            Check the first page of the result before doing a batch.
        </div></div>
    </div>`,

    pdf2img: `<div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label" for="imScale">Resolution</label>
            <select id="imScale" class="form-select form-select-sm">
                <option value="1">Screen (72 dpi)</option>
                <option value="2" selected>Good (144 dpi)</option>
                <option value="3">Print (216 dpi)</option>
            </select></div>
        <div class="col-auto"><label class="form-label" for="imQual">JPEG quality</label>
            <input type="number" id="imQual" class="form-control form-control-sm" value="85" min="40" max="100" style="width:110px"></div>
    </div>`,

    compress: `<div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label" for="cmScale">Render at</label>
            <select id="cmScale" class="form-select form-select-sm">
                <option value="1">72 dpi — smallest</option>
                <option value="1.5" selected>108 dpi — readable</option>
                <option value="2">144 dpi — safe</option>
            </select></div>
        <div class="col-auto"><label class="form-label" for="cmQual">JPEG quality</label>
            <input type="number" id="cmQual" class="form-control form-control-sm" value="70" min="30" max="95" style="width:110px"></div>
        <div class="col-12"><div class="form-text">
            Every page becomes an image, so text stops being selectable. Good for scans, poor for
            anything with small type or a signature you may need to search.
        </div></div>
    </div>`,

    unlock: `<div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label" for="ulPass">Password</label>
            <input type="password" id="ulPass" class="form-control form-control-sm"
                   placeholder="Leave blank if it opens without one" style="width:240px"></div>
        <div class="col-auto"><label class="form-label" for="ulScale">Rebuild at</label>
            <select id="ulScale" class="form-select form-select-sm">
                <option value="2">144 dpi — readable</option>
                <option value="3" selected>216 dpi — print</option>
                <option value="4">288 dpi — archival</option>
            </select></div>
        <div class="col-12"><div class="form-text">
            For a file you hold the password to, or one that opens freely but blocks printing and
            copying. The unlocked copy is rebuilt from the rendered pages, so its text is no longer
            selectable &mdash; the only decryption available in a browser is the renderer's.
            It will not open a file whose password you do not have.
        </div></div>
    </div>`,

    stamp: `<div class="row g-2 align-items-end">
        <div class="col-auto"><label class="form-label" for="stText">Text</label>
            <input type="text" id="stText" class="form-control form-control-sm" placeholder="Leave blank for page numbers only" style="width:260px"></div>
        <div class="col-auto"><label class="form-label" for="stNum">Page numbers</label>
            <select id="stNum" class="form-select form-select-sm">
                <option value="1">Yes</option><option value="0">No</option>
            </select></div>
    </div>`,
};

/* ── choosing a tool ───────────────────────────────────────────── */
el('tools').addEventListener('click', e => {
    const card = e.target.closest('.pt-tool');
    if (!card) return;

    document.querySelectorAll('.pt-tool').forEach(t => t.classList.remove('on'));
    card.classList.add('on');

    tool = card.dataset.tool;
    el('toolTitle').textContent = card.querySelector('h3').textContent;
    el('dropHint').textContent = HINTS[tool];
    el('options').innerHTML = OPTIONS[tool] || '';
    el('picker').setAttribute('accept', tool === 'img2pdf' ? 'image/*' : 'application/pdf');

    el('previewCard').classList.add('d-none');
    el('outCard').classList.add('d-none');
    say('');
    refreshFiles();
});

/* ── picking files ─────────────────────────────────────────────── */
el('drop').addEventListener('click', () => el('picker').click());
el('picker').addEventListener('change', e => add([...e.target.files]));

['dragenter', 'dragover'].forEach(n => el('drop').addEventListener(n, e => {
    e.preventDefault(); el('drop').classList.add('over');
}));
['dragleave', 'drop'].forEach(n => el('drop').addEventListener(n, e => {
    e.preventDefault(); el('drop').classList.remove('over');
}));
el('drop').addEventListener('drop', e => add([...e.dataTransfer.files]));

async function add(files) {
    for (const f of files) {
        picked.push({ file: f, name: f.name, size: f.size, bytes: new Uint8Array(await f.arrayBuffer()) });
    }

    // Tools that work on one document keep only the last one dropped.
    if (['pages', 'split', 'pdf2img'].includes(tool) && picked.length > 1) {
        picked = picked.slice(-1);
        say('This tool works on one file; keeping the last one.');
    }

    refreshFiles();
    if (tool === 'pages') await showPages();
}

function refreshFiles() {
    el('files').innerHTML = picked.map((p, i) =>
        `<div class="pt-file"><i class="bi bi-file-earmark"></i>
            <span class="nm">${p.name}</span><span class="sz">${fmt(p.size)}</span>
            <button class="btn btn-sm btn-outline-danger" data-rm="${i}"><i class="bi bi-x"></i></button>
         </div>`).join('');

    el('files').querySelectorAll('[data-rm]').forEach(b => b.addEventListener('click', () => {
        picked.splice(+b.dataset.rm, 1);
        refreshFiles();
        if (tool === 'pages') showPages();
    }));

    el('run').disabled = picked.length === 0;
}

el('clear').addEventListener('click', () => {
    picked = []; pageState = [];
    const pw = el('ulPass');
    if (pw) { pw.value = ''; }
    refreshFiles();
    el('previewCard').classList.add('d-none');
    el('outCard').classList.add('d-none');
    say('');
});

/* ── page previews, for the Pages tool ─────────────────────────── */
async function showPages() {
    if (!picked.length) { el('previewCard').classList.add('d-none'); return; }

    say('Rendering pages…');
    const doc = await pdfjsLib.getDocument({ data: picked[0].bytes.slice() }).promise;
    pageState = Array.from({ length: doc.numPages }, (_, i) => ({ index: i, rotate: 0, drop: false }));

    const box = el('pages');
    box.innerHTML = '';

    for (let n = 1; n <= doc.numPages; n++) {
        const page = await doc.getPage(n);
        const vp = page.getViewport({ scale: 0.3 });
        const c = document.createElement('canvas');
        c.width = vp.width; c.height = vp.height;
        await page.render({ canvasContext: c.getContext('2d'), viewport: vp }).promise;

        const cell = document.createElement('div');
        cell.className = 'pt-page';
        cell.dataset.n = n - 1;
        cell.appendChild(c);
        cell.insertAdjacentHTML('beforeend', `<span class="no">${n}</span><span class="rot"></span>`);

        cell.addEventListener('click', ev => {
            const st = pageState[n - 1];
            if (ev.shiftKey) {
                st.rotate = (st.rotate + 90) % 360;
                cell.querySelector('.rot').textContent = st.rotate ? st.rotate + '°' : '';
            } else {
                st.drop = !st.drop;
                cell.classList.toggle('drop-sel', st.drop);
            }
        });

        box.appendChild(cell);
    }

    el('previewHint').textContent = 'Click to mark for removal · Shift-click to rotate';
    el('previewCard').classList.remove('d-none');
    say(doc.numPages + ' pages');
}

/* ── running ───────────────────────────────────────────────────── */
el('run').addEventListener('click', async () => {
    document.body.classList.add('pt-busy');
    el('outFiles').innerHTML = '';
    el('log').classList.add('d-none');
    el('log').textContent = '';

    try {
        const out = await RUN[tool]();
        show(out);
        say('Done.');
    } catch (e) {
        say('');
        el('log').classList.remove('d-none');
        el('log').textContent = 'Failed: ' + (e && e.message ? e.message : e)
            + '\n\nEncrypted or damaged PDFs are the usual cause. Opening and re-saving the file in a '
            + 'PDF reader often clears it.';
    } finally {
        document.body.classList.remove('pt-busy');
    }
});

const RUN = {
    async merge() {
        const out = await PDFDocument.create();
        for (const p of picked) {
            say('Adding ' + p.name + '…');
            const src = await PDFDocument.load(p.bytes.slice(), { ignoreEncryption: true });
            const pages = await out.copyPages(src, src.getPageIndices());
            pages.forEach(pg => out.addPage(pg));
        }
        return [{ name: 'merged.pdf', bytes: await out.save() }];
    },

    async pages() {
        const src = await PDFDocument.load(picked[0].bytes.slice(), { ignoreEncryption: true });
        const out = await PDFDocument.create();
        const keep = pageState.filter(s => !s.drop);

        if (!keep.length) throw new Error('every page is marked for removal');

        const copied = await out.copyPages(src, keep.map(s => s.index));
        copied.forEach((pg, i) => {
            if (keep[i].rotate) {
                pg.setRotation(degrees((pg.getRotation().angle + keep[i].rotate) % 360));
            }
            out.addPage(pg);
        });

        return [{ name: clean(picked[0].name) + '-edited.pdf', bytes: await out.save() }];
    },

    async split() {
        const src = await PDFDocument.load(picked[0].bytes.slice(), { ignoreEncryption: true });
        const total = src.getPageCount();

        if (el('spMode').value === 'each') {
            const files = [];
            for (let i = 0; i < total; i++) {
                const out = await PDFDocument.create();
                const [pg] = await out.copyPages(src, [i]);
                out.addPage(pg);
                files.push({ name: clean(picked[0].name) + '-p' + (i + 1) + '.pdf', bytes: await out.save() });
            }
            return files;
        }

        const from = Math.max(1, +el('spFrom').value) - 1;
        const to = Math.min(total, +el('spTo').value) - 1;
        if (to < from) throw new Error('the last page is before the first');

        const out = await PDFDocument.create();
        const idx = [];
        for (let i = from; i <= to; i++) idx.push(i);
        const pgs = await out.copyPages(src, idx);
        pgs.forEach(p => out.addPage(p));

        return [{ name: clean(picked[0].name) + `-p${from + 1}-${to + 1}.pdf`, bytes: await out.save() }];
    },

    async clean() {
        const pct = Math.max(0.5, +el('clHeight').value) / 100;
        const mode = el('clMode').value;
        const files = [];

        for (const p of picked) {
            say('Cleaning ' + p.name + '…');
            const doc = await PDFDocument.load(p.bytes.slice(), { ignoreEncryption: true });

            doc.getPages().forEach(page => {
                const { width, height } = page.getSize();
                const strip = height * pct;

                if (mode === 'crop') {
                    // Lift the bottom of the visible box, which leaves the
                    // content intact and simply stops showing that band.
                    const box = page.getCropBox();
                    page.setCropBox(box.x, box.y + strip, box.width, box.height - strip);
                } else {
                    page.drawRectangle({
                        x: 0, y: 0, width: width, height: strip,
                        color: rgb(1, 1, 1), borderWidth: 0,
                    });
                }
            });

            files.push({ name: clean(p.name) + '-clean.pdf', bytes: await doc.save() });
        }

        return files;
    },

    async img2pdf() {
        const out = await PDFDocument.create();

        for (const p of picked) {
            say('Placing ' + p.name + '…');
            const isPng = /\.png$/i.test(p.name) || p.file.type === 'image/png';
            const img = isPng ? await out.embedPng(p.bytes.slice()) : await out.embedJpg(p.bytes.slice());
            const page = out.addPage([img.width, img.height]);
            page.drawImage(img, { x: 0, y: 0, width: img.width, height: img.height });
        }

        return [{ name: 'images.pdf', bytes: await out.save() }];
    },

    async pdf2img() {
        const scale = +el('imScale').value;
        const q = Math.min(100, Math.max(40, +el('imQual').value)) / 100;
        const doc = await pdfjsLib.getDocument({ data: picked[0].bytes.slice() }).promise;
        const files = [];

        for (let n = 1; n <= doc.numPages; n++) {
            say('Page ' + n + ' of ' + doc.numPages + '…');
            const blob = await renderPage(doc, n, scale, q);
            files.push({ name: clean(picked[0].name) + '-p' + n + '.jpg', blob });
        }

        return files;
    },

    async compress() {
        const scale = +el('cmScale').value;
        const q = Math.min(95, Math.max(30, +el('cmQual').value)) / 100;
        const files = [];

        for (const p of picked) {
            const doc = await pdfjsLib.getDocument({ data: p.bytes.slice() }).promise;
            const out = await PDFDocument.create();

            for (let n = 1; n <= doc.numPages; n++) {
                say(p.name + ' — page ' + n + ' of ' + doc.numPages + '…');
                const blob = await renderPage(doc, n, scale, q);
                const img = await out.embedJpg(new Uint8Array(await blob.arrayBuffer()));
                const page = out.addPage([img.width, img.height]);
                page.drawImage(img, { x: 0, y: 0, width: img.width, height: img.height });
            }

            const bytes = await out.save();
            files.push({ name: clean(p.name) + '-small.pdf', bytes, was: p.size });
        }

        return files;
    },

    /**
     * Unlock a protected PDF.
     *
     * pdf.js is the only thing here that can decrypt, and it decrypts in order
     * to draw, so the unlocked copy is rebuilt from rendered pages. That loses
     * selectable text, which is a real cost and is stated on the form rather
     * than discovered afterwards.
     *
     * A file whose password is not known stays shut. Guessing at one is not a
     * feature.
     */
    async unlock() {
        const password = el('ulPass').value;
        const scale = +el('ulScale').value;
        const files = [];

        for (const p of picked) {
            say('Opening ' + p.name + '…');

            let doc;
            try {
                doc = await pdfjsLib.getDocument({
                    data: p.bytes.slice(),
                    password: password || undefined,
                }).promise;
            } catch (e) {
                if (e && e.name === 'PasswordException') {
                    throw new Error(password
                        ? 'that password was not accepted for ' + p.name
                        : p.name + ' needs a password — type it in the box above');
                }
                throw e;
            }

            const out = await PDFDocument.create();

            for (let n = 1; n <= doc.numPages; n++) {
                say(p.name + ' — page ' + n + ' of ' + doc.numPages + '…');
                const blob = await renderPage(doc, n, scale, 0.92);
                const img = await out.embedJpg(new Uint8Array(await blob.arrayBuffer()));
                const page = out.addPage([img.width, img.height]);
                page.drawImage(img, { x: 0, y: 0, width: img.width, height: img.height });
            }

            files.push({ name: clean(p.name) + '-unlocked.pdf', bytes: await out.save(), was: p.size });
        }

        return files;
    },

    async stamp() {
        const text = el('stText').value.trim();
        const numbers = el('stNum').value === '1';
        const files = [];

        for (const p of picked) {
            const doc = await PDFDocument.load(p.bytes.slice(), { ignoreEncryption: true });
            const font = await doc.embedFont(StandardFonts.Helvetica);
            const pages = doc.getPages();

            pages.forEach((page, i) => {
                const { width } = page.getSize();
                const line = [text, numbers ? (i + 1) + ' / ' + pages.length : ''].filter(Boolean).join('   ');
                if (!line) return;

                const size = 9;
                page.drawText(line, {
                    x: width - font.widthOfTextAtSize(line, size) - 28,
                    y: 18, size, font, color: rgb(0.35, 0.38, 0.42),
                });
            });

            files.push({ name: clean(p.name) + '-stamped.pdf', bytes: await doc.save() });
        }

        return files;
    },
};

async function renderPage(doc, n, scale, quality) {
    const page = await doc.getPage(n);
    const vp = page.getViewport({ scale });
    const c = document.createElement('canvas');
    c.width = vp.width; c.height = vp.height;
    c.getContext('2d').fillStyle = '#fff';
    c.getContext('2d').fillRect(0, 0, c.width, c.height);
    await page.render({ canvasContext: c.getContext('2d'), viewport: vp }).promise;

    return await new Promise(res => c.toBlob(res, 'image/jpeg', quality));
}

const clean = n => n.replace(/\.[^.]+$/, '');

/* ── results ───────────────────────────────────────────────────── */
function show(files) {
    el('outFiles').innerHTML = '';

    files.forEach(f => {
        const blob = f.blob || new Blob([f.bytes], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        const saved = f.was ? ` · was ${fmt(f.was)}, now ${fmt(blob.size)}` : '';

        const row = document.createElement('div');
        row.className = 'pt-file';
        row.innerHTML = `<i class="bi bi-check-circle" style="color:var(--ok-ink)"></i>
            <span class="nm">${f.name}</span>
            <span class="sz">${fmt(blob.size)}${saved}</span>`;

        const a = document.createElement('a');
        a.className = 'btn btn-sm btn-accent';
        a.href = url; a.download = f.name; a.textContent = 'Download';
        row.appendChild(a);

        el('outFiles').appendChild(row);
    });

    el('outCard').classList.remove('d-none');
}
</script>
@endsection
