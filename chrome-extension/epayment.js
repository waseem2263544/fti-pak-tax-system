/**
 * Fills FBR's single-payment page.
 *
 * Written against the page as it actually is, not as it looked from the outside:
 *
 *  - the taxpayer block is read-only and comes from whoever is signed in, so
 *    there is nothing to fill there and the challan is always raised for that
 *    login;
 *  - the regime is a sidebar menu item, not a tab, and choosing it clears the
 *    tax year, so it has to come first;
 *  - the file input does not exist until its own tab is opened;
 *  - the month select takes "09", not "September".
 *
 * Nothing here has a stable id. Everything is found by role and text, which is
 * the least bad option available on this page.
 */
const EP_API = 'https://app.fairtaxint.com/api/ext/wht/psid-request/';

if (location.pathname.indexOf('/payment/single') === 0) {
    chrome.storage.local.get(['psidJob'], function (s) {
        if (s.psidJob) { prepare(s.psidJob); }
    });
}

const wait = ms => new Promise(r => setTimeout(r, ms));
const text = el => (el.innerText || el.textContent || '').trim();

async function prepare(job) {
    let data;

    try {
        const res = await chrome.runtime.sendMessage({ action: 'psidFile', token: job.token });
        if (!res || !res.ok) { return note('Could not load the deposit file: ' + ((res && res.error) || 'no answer'), true); }
        data = res.data;
    } catch (e) {
        return note('Could not load the deposit file: ' + e.message, true);
    }

    if (data.mixed) {
        return note('These entries span ' + data.periods.length + ' tax months (' + data.periods.join(', ')
            + '). FBR issues one challan per month, so this needs splitting.', true);
    }

    if (data.mixed_regime) {
        return note('These entries mix adjustable and final tax sections, which live under '
            + 'different menu items with different payment codes. They need splitting.', true);
    }

    const done = [];

    // 1. Regime first. It clears the tax year, so anything set before it is lost.
    if (await pickRegime(data.regime)) { done.push(data.regime_label); }
    await wait(800);

    // 2. Tax year, then month. The month list is built from the year and takes
    //    a two digit value rather than a name.
    const selects = paymentSelects();

    if (selects.year && setSelectByText(selects.year, String(data.tax_year))) {
        done.push('tax year ' + data.tax_year);
        await wait(900);
    }

    const after = paymentSelects();
    const mm = String(data.tax_month_no).padStart(2, '0');

    if (after.month && setSelectByValue(after.month, mm)) {
        done.push(data.tax_month);
    }

    // 3. The file lives behind its own tab, which only renders once opened.
    const attached = await attachWhenReady(data);
    if (attached) { done.push(data.entries + ' entries attached'); }

    focusCaptcha();
    offerSubmit();

    if (attached) {
        note('Filled: ' + done.join(' · ') + '. Type the captcha, then check the figures before submitting.');
        return;
    }

    // Say so. An attach that quietly did not happen looks like one that did,
    // and the challan would go up with no entries behind it.
    note((done.length ? 'Filled: ' + done.join(' · ') + '. ' : '')
        + 'The file is NOT attached — the Attach File for Payment tab was not found. '
        + 'Open that tab and press the button below.', true);
    offerAttach(data);
}

/**
 * Open the file tab and attach, retrying while Angular builds the page.
 *
 * The input does not exist until its tab is shown, and the tab itself may not
 * be there the moment this runs, so both are retried rather than attempted
 * once and given up on.
 */
async function attachWhenReady(data) {
    for (let i = 0; i < 18; i++) {
        if (attachFile(data)) { return true; }

        const tab = findFileTab();
        if (tab) { tab.click(); }

        await wait(800);
    }

    return false;
}

/** The tab is matched on its words rather than a class, which varies. */
function findFileTab() {
    const candidates = Array.from(document.querySelectorAll(
        '[role="tab"], .mat-tab-label, .mat-mdc-tab, button, a, li, span, div'
    ));

    return candidates.find(function (el) {
        const t = text(el);
        if (t.length > 60) { return false; }              // a container, not a label
        return /attach/i.test(t) && /file/i.test(t);
    });
}

/** A button of last resort, for when the tab cannot be found automatically. */
function offerAttach(data) {
    if (document.getElementById('fairtax-ep-attach')) { return; }

    const btn = document.createElement('button');
    btn.id = 'fairtax-ep-attach';
    btn.textContent = 'Attach the entries file';
    btn.style.cssText = 'position:fixed;left:18px;bottom:114px;z-index:2147483646;'
        + 'background:#D97706;color:#fff;border:0;border-radius:8px;padding:10px 16px;'
        + 'font:600 13px system-ui;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.3)';

    btn.addEventListener('click', function () {
        if (attachFile(data)) {
            btn.remove();
            note('File attached — ' + data.entries + ' entries. Type the captcha and submit.');
        } else {
            note('Still no file input on this page. Open the Attach File for Payment tab first, '
               + 'then press the button again.', true);
        }
    });

    document.body.appendChild(btn);
}

/** The regime lives in the sidebar, under a panel that may be collapsed. */
async function pickRegime(regime) {
    const wanted = regime === 'final' ? /fixed\s*\/?\s*final\s*income\s*tax/i : /adjustable\s*income\s*tax/i;

    const find = () => Array.from(document.querySelectorAll('button.menu-item-btn'))
        .find(b => wanted.test(text(b.querySelector('.menu-item-text') || b)));

    let btn = find();

    if (!btn) {
        // The Withholding panel is probably closed; Income Tax opens by default.
        const header = Array.from(document.querySelectorAll('button, [role="button"], mat-panel-title, .mat-expansion-panel-header'))
            .find(el => /withholding/i.test(text(el)));
        if (header) { header.click(); await wait(600); }
        btn = find();
    }

    if (!btn) { return false; }

    if (!btn.classList.contains('active')) { btn.click(); }
    return true;
}

/** The two period selects, identified by what they contain rather than by id. */
function paymentSelects() {
    const all = Array.from(document.querySelectorAll('select'));
    const has = (sel, re) => Array.from(sel.options).some(o => re.test(text(o)));

    return {
        year:  all.find(s => has(s, /tax year/i) || Array.from(s.options).some(o => /^20\d\d$/.test(text(o)))),
        month: all.find(s => has(s, /tax month/i) || Array.from(s.options).some(o => /^(jan|feb|mar)$/i.test(text(o)))),
    };
}

/*
 * Angular only sees a change that arrives the way a person's would. A plain
 * assignment to .value updates the DOM and nothing else.
 */
function commit(el) {
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
}

function setSelectByText(sel, wanted) {
    const i = Array.from(sel.options).findIndex(o => text(o) === wanted);
    if (i < 0 || sel.selectedIndex === i) { return false; }
    sel.selectedIndex = i;
    commit(sel);
    return true;
}

function setSelectByValue(sel, value) {
    const i = Array.from(sel.options).findIndex(o => o.value === value);
    if (i < 0 || sel.selectedIndex === i) { return false; }
    sel.selectedIndex = i;
    commit(sel);
    return true;
}

/**
 * Hand the workbook to the file input.
 *
 * A script cannot assign to input.files, but it can give it a DataTransfer,
 * which is what a drop would have produced. The page uploads and parses on
 * change, so this is the first irreversible thing that happens - it is done
 * last, once everything else is in place.
 */
function attachFile(data) {
    const input = document.querySelector('input[type="file"][accept*="xlsx"]')
        || document.querySelector('input[type="file"]');

    if (!input || input.files.length) { return false; }

    const bytes = Uint8Array.from(atob(data.base64), c => c.charCodeAt(0));
    const file = new File([bytes], data.filename,
        { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    commit(input);
    return true;
}

function focusCaptcha() {
    const box = document.querySelector('[formcontrolname="userInput"]');
    if (!box || box.dataset.ftBound) { return; }

    box.dataset.ftBound = '1';
    box.focus();

    box.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();
        const after = Array.from(document.querySelectorAll('button'))
            .filter(b => box.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING)
            .filter(b => b.id !== 'fairtax-ep-submit');
        if (after.length) { after[0].click(); }
    });
}

/**
 * The page's own Submit, pressed on request.
 *
 * Only offered while a Submit button is actually on screen: the sidebar keeps
 * this script alive across views where submitting means nothing.
 */
function offerSubmit() {
    const find = () => Array.from(document.querySelectorAll('button'))
        .filter(b => b.id !== 'fairtax-ep-submit')
        .find(b => /^\s*submit\s*$/i.test(text(b)));

    const sync = function () {
        const target = find();
        const existing = document.getElementById('fairtax-ep-submit');

        if (!target) { if (existing) { existing.remove(); } return; }
        if (existing) { return; }

        const btn = document.createElement('button');
        btn.id = 'fairtax-ep-submit';
        btn.textContent = 'Submit to FBR';
        btn.style.cssText = 'position:fixed;left:18px;bottom:66px;z-index:2147483646;'
            + 'background:#2F6FEB;color:#fff;border:0;border-radius:8px;padding:10px 16px;'
            + 'font:600 13px system-ui;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,.3)';
        btn.addEventListener('click', function () {
            btn.disabled = true;
            btn.textContent = 'Submitting…';
            find() && find().click();
        });
        document.body.appendChild(btn);
    };

    sync();
    new MutationObserver(sync).observe(document.body, { childList: true, subtree: true });
}

function note(message, bad) {
    let el = document.getElementById('fairtax-ep-note');

    if (!el) {
        el = document.createElement('div');
        el.id = 'fairtax-ep-note';
        el.style.cssText = 'position:fixed;left:18px;bottom:18px;z-index:2147483646;'
            + 'font:12px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;padding:11px 15px;'
            + 'border-radius:8px;max-width:380px;color:#fff;box-shadow:0 6px 20px rgba(0,0,0,.3)';
        document.body.appendChild(el);
    }

    el.style.background = bad ? '#7a1d1d' : '#16181d';
    el.style.borderLeft = '3px solid ' + (bad ? '#ef4444' : '#2F6FEB');
    el.textContent = message;
}
