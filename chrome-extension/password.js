/**
 * Changes a client's IRIS password to the firm's standard one.
 *
 * Armed from the popup, which knows which client is meant; completed here,
 * where the dialog is. The three fields carry no name or formcontrolname and
 * their ids shift between loads, so they are told apart by their labels.
 *
 * The app's record is written only after the change appears to have gone
 * through. Recording first would leave the app holding a password the portal
 * never accepted, which locks the firm out of the account.
 */
const PW_API = 'https://app.fairtaxint.com/api/ext';

let pwJob = null;

chrome.storage.local.get(['pwJob'], function (s) {
    if (!s.pwJob) { return; }
    pwJob = s.pwJob;
    watchForDialog();
});

function watchForDialog() {
    const look = function () {
        if (!pwJob) { return; }
        if (document.getElementById('fairtax-pw-bar')) { return; }
        if (fields().old && fields().neu && fields().confirm) { offer(); }
    };

    look();
    new MutationObserver(function () {
        clearTimeout(watchForDialog._t);
        watchForDialog._t = setTimeout(look, 300);
    }).observe(document.body, { childList: true, subtree: true });
}

/** Label text for an input, however Material has attached it. */
function labelOf(el) {
    if (el.labels && el.labels[0]) { return el.labels[0].innerText || ''; }
    const aria = el.getAttribute('aria-label') || el.getAttribute('placeholder');
    if (aria) { return aria; }

    const field = el.closest('mat-form-field, .mat-form-field, .mat-mdc-form-field');
    return field ? (field.innerText || '') : '';
}

function fields() {
    const boxes = Array.prototype.slice.call(document.querySelectorAll('input[type="password"]'))
        .filter(function (el) { return el.offsetParent !== null; });

    const find = re => boxes.find(el => re.test(labelOf(el)));

    const confirm = find(/confirm|re-?enter|again/i);

    return {
        old: find(/old|current|existing/i),
        // "New" also matches "Confirm New Password", so the confirm box is
        // excluded rather than relying on the order they appear in.
        neu: boxes.find(el => /new/i.test(labelOf(el)) && el !== confirm),
        confirm: confirm,
    };
}

function setValue(el, value) {
    const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
    setter.call(el, value);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.dispatchEvent(new Event('blur', { bubbles: true }));
}

function offer() {
    const bar = document.createElement('div');
    bar.id = 'fairtax-pw-bar';
    bar.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);'
        + 'z-index:2147483647;background:#16181d;color:#fff;border-left:3px solid #D97706;'
        + 'font:13px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;padding:14px 18px;'
        + 'border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.35);display:flex;gap:14px;'
        + 'align-items:center;max-width:min(620px,92vw)';

    const msg = document.createElement('div');
    msg.innerHTML = 'Set the IRIS password for <strong>' + (pwJob.clientName || 'this client')
        + '</strong> to the firm\'s standard one?'
        + '<div style="opacity:.65;font-size:11.5px;margin-top:3px">'
        + 'Fills all three boxes and presses Save. The app is updated only if it goes through.</div>';

    const go = mk('Do it', '#D97706');
    const no = mk('Not now', 'transparent');
    no.style.border = '1px solid rgba(255,255,255,.28)';

    no.addEventListener('click', function () { bar.remove(); });

    go.addEventListener('click', function () {
        go.disabled = true;
        go.textContent = 'Changing…';
        run(bar, msg, go);
    });

    bar.appendChild(msg);
    bar.appendChild(no);
    bar.appendChild(go);
    document.body.appendChild(bar);
}

function mk(label, bg) {
    const b = document.createElement('button');
    b.textContent = label;
    b.style.cssText = 'background:' + bg + ';color:#fff;border:0;border-radius:7px;padding:8px 14px;'
        + 'font:600 13px system-ui;cursor:pointer;white-space:nowrap';
    return b;
}

async function run(bar, msg, go) {
    const f = fields();

    if (!f.old || !f.neu || !f.confirm) {
        return fail(bar, msg, go, 'the three password boxes are no longer on screen');
    }

    if (!pwJob.oldPassword) {
        return fail(bar, msg, go, 'no current password is stored for this client, so the old one cannot be filled');
    }

    setValue(f.old, pwJob.oldPassword);
    setValue(f.neu, pwJob.newPassword);
    setValue(f.confirm, pwJob.newPassword);

    await new Promise(r => setTimeout(r, 400));

    const save = Array.prototype.slice.call(document.querySelectorAll('button'))
        .filter(b => !b.id.startsWith('fairtax-'))
        .find(b => /^\s*save\s*$/i.test(b.innerText || ''));

    if (!save) { return fail(bar, msg, go, 'the Save button could not be found'); }

    save.click();

    // Treat the dialog closing with no error on screen as success. There is no
    // reliable signal on this page, so the record is written on that basis and
    // the preparer is told to check.
    await new Promise(r => setTimeout(r, 2500));

    const stillOpen = !!fields().old;
    const errorText = (document.body.innerText || '').match(/incorrect|invalid|does not match|failed|wrong/i);

    if (stillOpen || errorText) {
        return fail(bar, msg, go,
            errorText ? 'IRIS reported: ' + errorText[0] : 'the dialog is still open, so it may not have saved');
    }

    try {
        const stored = await chrome.storage.local.get(['token']);
        const res = await fetch(PW_API + '/clients/' + pwJob.clientId + '/reset-password?portal=fbr', {
            method: 'POST',
            headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
        });

        if (!res.ok) { throw new Error('the app returned ' + res.status); }
    } catch (e) {
        bar.style.borderLeftColor = '#ef4444';
        msg.innerHTML = 'IRIS accepted the change, but the app was not updated (' + e.message
            + '). Set the password in the app by hand or the two will disagree.';
        go.remove();
        return;
    }

    await chrome.storage.local.remove(['pwJob']);
    pwJob = null;

    bar.style.borderLeftColor = '#10b981';
    msg.innerHTML = 'Changed on IRIS and recorded in the app. Sign out and in once to confirm it took.';
    go.remove();
    setTimeout(() => bar.remove(), 8000);
}

function fail(bar, msg, go, why) {
    bar.style.borderLeftColor = '#ef4444';
    msg.innerHTML = 'Not changed — ' + why + '. Nothing was recorded in the app.';
    go.disabled = false;
    go.textContent = 'Try again';
}
