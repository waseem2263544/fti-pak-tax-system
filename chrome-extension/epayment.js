/**
 * Fills FBR's single-payment page as far as a machine honestly can.
 *
 * The page asks for a registration number, a captcha, a tax year and month,
 * and a file. Everything but the captcha is known to the app, so everything
 * but the captcha is filled: the number, the period, and the same upload
 * workbook the Deposit page produces.
 *
 * The captcha is the reason this runs in the preparer's browser rather than on
 * a server. It is not an obstacle to work around - it is the point at which a
 * person confirms what is being filed.
 */
const EP_API = 'https://app.fairtaxint.com/api/ext/wht/psid-request/';

if (location.pathname.indexOf('/payment/single') === 0) {
    chrome.storage.local.get(['psidJob'], function (s) {
        if (s.psidJob) { prepare(s.psidJob); }
    });
}

async function prepare(jobInfo) {
    let data;

    try {
        const res = await chrome.runtime.sendMessage({ action: 'psidFile', token: jobInfo.token });
        if (!res || !res.ok) { return note('Could not load the deposit file: ' + ((res && res.error) || 'no answer'), true); }
        data = res.data;
    } catch (e) {
        return note('Could not load the deposit file: ' + e.message, true);
    }

    if (data.mixed) {
        note('These entries span ' + data.periods.length + ' tax months (' + data.periods.join(', ')
           + '). FBR issues one challan per month, so this needs splitting before it will be accepted.', true);
        return;
    }

    // Angular only notices a value that arrives with the events a person's
    // typing would have produced.
    const set = function (el, value) {
        if (!el) { return false; }
        const proto = el instanceof HTMLTextAreaElement ? HTMLTextAreaElement.prototype : HTMLInputElement.prototype;
        const setter = Object.getOwnPropertyDescriptor(proto, 'value').set;
        setter.call(el, value);
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        el.dispatchEvent(new Event('blur', { bubbles: true }));
        return true;
    };

    const filled = [];

    // Registration type, then the number itself.
    const radio = document.getElementById(data.reg_type === 'cnic' ? 'comm' : 'resident');
    if (radio && !radio.checked) {
        radio.click();
        filled.push(data.reg_type === 'cnic' ? 'CNIC' : 'NTN');
    }

    if (set(document.querySelector('[formcontrolname="regTypeValue"]'), data.registration)) {
        filled.push('registration ' + data.registration);
    }

    // Year and month, once the page offers them.
    const pickPeriod = function () {
        const selects = Array.prototype.slice.call(document.querySelectorAll('select'));

        selects.forEach(function (sel) {
            const texts = Array.prototype.map.call(sel.options, function (o) { return o.text.trim(); });

            const yearIdx = texts.indexOf(String(data.tax_year));
            if (texts.some(function (t) { return /tax year/i.test(t); }) && yearIdx >= 0 && sel.selectedIndex !== yearIdx) {
                sel.selectedIndex = yearIdx;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
                filled.push('tax year ' + data.tax_year);
            }

            if (texts.some(function (t) { return /tax month/i.test(t); })) {
                const monthIdx = texts.findIndex(function (t) {
                    return new RegExp('^' + data.tax_month + '\\b', 'i').test(t)
                        || t === String(data.tax_month_no);
                });
                if (monthIdx >= 0 && sel.selectedIndex !== monthIdx) {
                    sel.selectedIndex = monthIdx;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    filled.push(data.tax_month);
                }
            }
        });
    };

    pickPeriod();
    // The month list is built only after a year is chosen.
    setTimeout(pickPeriod, 900);
    setTimeout(pickPeriod, 2200);

    // Attach the workbook. A script cannot assign to a file input, but it can
    // hand it a DataTransfer, which is what a drop would have done.
    const attach = function () {
        const input = document.querySelector('input[type="file"]');
        if (!input || input.files.length) { return false; }

        const bytes = Uint8Array.from(atob(data.base64), function (c) { return c.charCodeAt(0); });
        const file = new File([bytes],
            data.filename,
            { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        filled.push(data.entries + ' entries attached');
        return true;
    };

    if (!attach()) { setTimeout(attach, 1500); }

    setTimeout(function () {
        note('Filled: ' + filled.join(' · ')
           + '. Enter the captcha and press Submit — that part is yours.');
    }, 2600);
}

function note(message, bad) {
    let el = document.getElementById('fairtax-ep-note');

    if (!el) {
        el = document.createElement('div');
        el.id = 'fairtax-ep-note';
        el.style.cssText = [
            'position:fixed', 'left:18px', 'bottom:18px', 'z-index:2147483646',
            'font:12px/1.5 system-ui,-apple-system,Segoe UI,sans-serif',
            'padding:11px 15px', 'border-radius:8px', 'max-width:360px', 'color:#fff',
            'box-shadow:0 6px 20px rgba(0,0,0,.3)',
        ].join(';');
        document.body.appendChild(el);
    }

    el.style.background = bad ? '#7a1d1d' : '#16181d';
    el.style.borderLeft = '3px solid ' + (bad ? '#ef4444' : '#2F6FEB');
    el.textContent = message;
}
