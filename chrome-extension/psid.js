/**
 * Watches an IRIS page for the PSID that FBR issues, while a request is open.
 *
 * Filling the ePayments form outright would mean knowing its markup, and IRIS
 * changes without notice. Watching for the number it produces does not: the
 * preparer works the screen exactly as they do today, and the extension takes
 * care of the part that actually goes wrong - carrying an eleven digit number
 * back and typing it against forty entries.
 *
 * Nothing is filed without being confirmed. A page full of figures has plenty
 * of numbers on it, and filing the wrong one against a deposit is worse than
 * typing it by hand.
 */
const PSID_API = 'https://app.fairtaxint.com/api/ext/wht/psid-request/';

let job = null;          // { token, agent, entryCount, totalTax }
let offered = new Set(); // numbers already put to the user, so we ask once

chrome.storage.local.get(['psidJob'], function (stored) {
    if (!stored.psidJob) { return; }

    job = stored.psidJob;
    arm();
    watch();
});

/**
 * Say that the extension is waiting.
 *
 * Without this the feature is invisible until a PSID happens to appear, which
 * is indistinguishable from it not working - and on a login page, which is
 * where this often starts, nothing appears for some time.
 */
function arm() {
    if (document.getElementById('fairtax-psid-armed')) { return; }

    const chip = document.createElement('div');
    chip.id = 'fairtax-psid-armed';
    chip.style.cssText = [
        'position:fixed', 'right:18px', 'bottom:18px', 'z-index:2147483646',
        'background:#16181d', 'color:#fff', 'border-left:3px solid #2F6FEB',
        'font:12px/1.5 system-ui,-apple-system,Segoe UI,sans-serif',
        'padding:10px 14px', 'border-radius:8px', 'box-shadow:0 6px 20px rgba(0,0,0,.3)',
        'max-width:320px',
    ].join(';');

    chip.innerHTML =
        '<div style="font-weight:600;margin-bottom:2px">Waiting for a PSID</div>'
        + '<div style="opacity:.75">' + (job.agent || 'this agent') + ' &middot; '
        + job.entryCount + ' entries &middot; '
        + Number(job.totalTax || 0).toLocaleString() + ' tax</div>'
        + '<div style="opacity:.6;margin-top:4px">The number is picked up automatically, or type it below.</div>';

    const close = document.createElement('button');
    close.textContent = 'Stop watching';
    close.style.cssText = 'margin-top:8px;background:none;border:1px solid rgba(255,255,255,.3);'
        + 'color:#fff;border-radius:6px;padding:4px 9px;font:11px system-ui;cursor:pointer';
    close.addEventListener('click', function () {
        chrome.storage.local.remove(['psidJob']);
        job = null;
        chip.remove();
    });

    const manual = document.createElement('div');
    manual.style.cssText = 'margin-top:8px;display:flex;gap:6px';
    manual.innerHTML = '<input placeholder="PSID number" '
        + 'style="flex:1;min-width:0;background:#0e1014;border:1px solid rgba(255,255,255,.2);'
        + 'color:#fff;border-radius:6px;padding:5px 8px;font:12px ui-monospace,Menlo,monospace">';

    const file = button('File', '#2F6FEB');
    file.style.padding = '5px 11px';
    file.style.fontSize = '12px';

    file.addEventListener('click', function () {
        const value = (manual.querySelector('input').value || '').replace(/\D/g, '');
        if (value.length < 9) { return; }
        file.disabled = true;
        file.textContent = 'Filing…';
        send(value, chip, file);
    });

    manual.appendChild(file);
    chip.appendChild(manual);
    chip.appendChild(close);
    document.body.appendChild(chip);
}

function watch() {
    scan();

    const observer = new MutationObserver(function () {
        clearTimeout(watch._t);
        watch._t = setTimeout(scan, 400);   // IRIS repaints a great deal
    });

    observer.observe(document.body, { childList: true, subtree: true, characterData: true });
}

/**
 * Look for a PSID.
 *
 * The results grid puts "PSID" once in a header and the numbers far below it,
 * so proximity to the word finds nothing. The column is located instead, and
 * read by position. A plain confirmation line is still matched, for whatever
 * the page shows straight after a submission.
 */
function scan() {
    if (!job) { return; }

    const fromGrid = scanGrid();
    if (fromGrid && !offered.has(fromGrid)) {
        offered.add(fromGrid);
        return offer(fromGrid);
    }

    const near = /(?:psid|payment\s*slip\s*id|p\.?s\.?i\.?d)\D{0,40}(\d{9,14})/gi;
    const body = document.body.innerText || '';

    let m;
    while ((m = near.exec(body)) !== null) {
        if (!offered.has(m[1])) {
            offered.add(m[1]);
            return offer(m[1]);
        }
    }
}

/**
 * Find the PSID column, then read it.
 *
 * The grid is built from divs rather than a table, so the header is matched on
 * its text and the same offset is read from each row.
 */
function scanGrid() {
    const headers = Array.prototype.slice.call(
        document.querySelectorAll('[role="columnheader"], th, .grid-header, .datatable-header-cell')
    );

    let header = headers.find(function (h) { return /^\s*psid\s*$/i.test(h.textContent || ''); });

    if (header) {
        const siblings = Array.prototype.slice.call(header.parentElement.children);
        const index = siblings.indexOf(header);

        const rows = Array.prototype.slice.call(
            document.querySelectorAll('[role="row"], .datatable-body-row, .grid-row')
        );

        for (const row of rows) {
            const cells = Array.prototype.slice.call(row.children);
            const cell = cells[index];
            if (!cell) { continue; }
            const digits = (cell.textContent || '').trim().match(/^(\d{9,14})$/);
            if (digits) { return digits[1]; }
        }
    }

    return null;
}

function offer(number) {
    if (document.getElementById('fairtax-psid-bar')) { return; }

    const bar = document.createElement('div');
    bar.id = 'fairtax-psid-bar';
    bar.style.cssText = [
        'position:fixed', 'left:50%', 'bottom:24px', 'transform:translateX(-50%)',
        'z-index:2147483647', 'background:#16181d', 'color:#fff',
        'font:14px/1.45 system-ui,-apple-system,Segoe UI,sans-serif',
        'padding:14px 18px', 'border-radius:10px', 'box-shadow:0 10px 30px rgba(0,0,0,.35)',
        'display:flex', 'gap:14px', 'align-items:center', 'max-width:min(640px,92vw)',
    ].join(';');

    const text = document.createElement('div');
    text.innerHTML =
        'File PSID <strong style="font-family:ui-monospace,Menlo,monospace">' + number + '</strong>'
        + ' against ' + job.entryCount + ' ' + (job.kind === 'salaries' ? 'salary' : 'vendor')
        + ' entries for <strong>' + (job.agent || 'this agent') + '</strong>?'
        + '<div style="opacity:.65;font-size:12px;margin-top:3px">'
        + Number(job.totalTax || 0).toLocaleString() + ' in tax</div>';

    const yes = button('File it', '#2F6FEB');
    const no  = button('Not this one', 'transparent');
    no.style.border = '1px solid rgba(255,255,255,.28)';

    yes.addEventListener('click', function () {
        yes.disabled = true;
        yes.textContent = 'Filing…';
        send(number, bar, yes);
    });

    no.addEventListener('click', function () { bar.remove(); });

    bar.appendChild(text);
    bar.appendChild(no);
    bar.appendChild(yes);
    document.body.appendChild(bar);
}

function button(label, bg) {
    const b = document.createElement('button');
    b.textContent = label;
    b.style.cssText = 'background:' + bg + ';color:#fff;border:0;border-radius:7px;'
        + 'padding:8px 14px;font:600 13px system-ui;cursor:pointer;white-space:nowrap';
    return b;
}

function send(number, bar, btn) {
    chrome.runtime.sendMessage({ action: 'filePsid', token: job.token, psid: number }, function (reply) {
        if (chrome.runtime.lastError || !reply || !reply.ok) {
            btn.disabled = false;
            btn.textContent = 'File it';
            const why = (reply && reply.error) || 'could not reach the app';
            bar.style.background = '#7a1d1d';
            bar.firstChild.innerHTML = 'Could not file it — ' + why + '.';
            return;
        }

        job = null;
        offered.clear();
        const chip = document.getElementById('fairtax-psid-armed');
        if (chip) { chip.remove(); }
        bar.style.background = '#0a6b4d';
        bar.innerHTML = '<div>Filed. PSID <strong>' + number + '</strong> recorded against '
            + reply.entries + ' entries.</div>';
        setTimeout(function () { bar.remove(); }, 6000);
    });
}
