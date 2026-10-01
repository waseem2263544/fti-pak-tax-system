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
let standard = null;

/*
 * The bar appears whenever the Change Password dialog is open, whether or not
 * anything was armed in the popup. Being signed in to the client's portal is
 * the whole context needed: who they are can be read off the page, and the
 * standard password comes from the app.
 */
chrome.storage.local.get(['pwJob'], function (s) {
    pwJob = s.pwJob || null;
    watchForDialog();
});

function watchForDialog() {
    const look = function () {
        if (document.getElementById('fairtax-pw-bar')) { return; }
        // Three visible password boxes is the dialog, whatever the labels say.
        // Requiring all three labels to match first meant one unexpected
        // wording left nothing on screen at all.
        if (passwordBoxes().length >= 3) { begin(); }
    };

    look();
    new MutationObserver(function () {
        clearTimeout(watchForDialog._t);
        watchForDialog._t = setTimeout(look, 300);
    }).observe(document.body, { childList: true, subtree: true });
}

/**
 * Work out who is signed in.
 *
 * Any registration number on the page is tried against the app. The profile
 * block carries it, but so can a dozen other places, so every candidate is
 * offered until one is recognised.
 */
async function identify() {
    const body = document.body.innerText || '';
    const seen = [];

    const patterns = [
        /\b(\d{5}-\d{7}-\d)\b/g,        // CNIC
        /\b(\d{7}-\d)\b/g,               // NTN
        /\b(\d{13})\b/g,
        /\b(\d{8})\b/g,
    ];

    for (const re of patterns) {
        let m;
        while ((m = re.exec(body)) !== null) {
            if (seen.indexOf(m[1]) < 0) { seen.push(m[1]); }
        }
    }

    const stored = await chrome.storage.local.get(['token']);
    if (!stored.token) { return null; }

    for (const reg of seen.slice(0, 12)) {
        try {
            const res = await fetch(PW_API + '/clients/by-registration?reg=' + encodeURIComponent(reg), {
                headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
            });
            if (res.ok) { return await res.json(); }
        } catch (e) { /* try the next one */ }
    }

    return null;
}

async function fetchStandard() {
    const stored = await chrome.storage.local.get(['token']);
    if (!stored.token) { return null; }

    try {
        const res = await fetch(PW_API + '/portal-password', {
            headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
        });
        if (!res.ok) { return null; }
        return (await res.json()).password;
    } catch (e) {
        return null;
    }
}

async function begin() {
    const bar = shell();

    standard = await fetchStandard();

    if (!standard) {
        return setBar(bar, 'No standard password is configured in the app, so there is nothing to set.', true);
    }

    // Armed from the popup, or worked out from the page.
    let client = pwJob
        ? { id: pwJob.clientId, name: pwJob.clientName, password: pwJob.oldPassword, has_password: !!pwJob.oldPassword }
        : await identify();

    if (!client) {
        return setBar(bar,
            'Change Password is open, but the extension cannot tell which client this login belongs to. '
            + 'Open the extension, find the client and press "Change this to the standard password", then come back.',
            true);
    }

    if (!client.has_password || !client.password) {
        return setBar(bar,
            'No current password is stored for ' + client.name + ', and the old one has to be filled. '
            + 'Add it in the extension first.', true);
    }

    pwJob = {
        clientId: client.id,
        clientName: client.name,
        oldPassword: client.password,
        newPassword: standard,
    };

    offer(bar, client);
}

function shell() {
    const bar = document.createElement('div');
    bar.id = 'fairtax-pw-bar';
    bar.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);'
        + 'z-index:2147483647;background:#16181d;color:#fff;border-left:3px solid #D97706;'
        + 'font:13px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;padding:14px 18px;'
        + 'border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.35);display:flex;gap:14px;'
        + 'align-items:center;max-width:min(640px,92vw)';
    bar.innerHTML = '<div>Checking this login…</div>';
    document.body.appendChild(bar);
    return bar;
}

function setBar(bar, html, bad) {
    bar.style.borderLeftColor = bad ? '#ef4444' : '#D97706';
    bar.innerHTML = '<div>' + html + '</div>';

    const close = mk('Dismiss', 'transparent');
    close.style.border = '1px solid rgba(255,255,255,.28)';
    close.addEventListener('click', function () { bar.remove(); });
    bar.appendChild(close);
}

/** Label text for an input, however Material has attached it. */
function labelOf(el) {
    if (el.labels && el.labels[0]) { return el.labels[0].innerText || ''; }
    const aria = el.getAttribute('aria-label') || el.getAttribute('placeholder');
    if (aria) { return aria; }

    const field = el.closest('mat-form-field, .mat-form-field, .mat-mdc-form-field');
    return field ? (field.innerText || '') : '';
}

function passwordBoxes() {
    return Array.prototype.slice.call(document.querySelectorAll('input[type="password"]'))
        .filter(function (el) { return el.offsetParent !== null; });
}

/**
 * Which box is which.
 *
 * By label where the labels say so, and by the order they appear where they do
 * not - the dialog puts old, new and confirm in that order, and a wording this
 * code has not seen should not stop it working.
 */
function fields() {
    const boxes = passwordBoxes();
    const find = re => boxes.find(el => re.test(labelOf(el)));

    const confirm = find(/confirm|re-?enter|again|retype/i);
    const old = find(/old|current|existing|previous/i);
    const neu = boxes.find(el => /new/i.test(labelOf(el)) && el !== confirm);

    if (old && neu && confirm) {
        return { old: old, neu: neu, confirm: confirm, how: 'labels' };
    }

    if (boxes.length >= 3) {
        return { old: boxes[0], neu: boxes[1], confirm: boxes[2], how: 'order' };
    }

    return { old: old, neu: neu, confirm: confirm, how: 'incomplete' };
}

function setValue(el, value) {
    const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
    setter.call(el, value);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.dispatchEvent(new Event('blur', { bubbles: true }));
}

function offer(bar, client) {
    bar.innerHTML = '';

    const f = fields();
    const seen = passwordBoxes().map(function (el) {
        const l = (labelOf(el) || '').replace(/\s+/g, ' ').trim();
        return l ? l.slice(0, 26) : '(no label)';
    });

    const msg = document.createElement('div');
    msg.innerHTML = 'Set the IRIS password for <strong>' + (pwJob.clientName || 'this client')
        + '</strong> to the firm\'s standard one?'
        + '<div style="opacity:.65;font-size:11.5px;margin-top:3px">'
        + 'Fills all three boxes and presses Save. The app is updated only if it goes through.</div>'
        + '<div style="opacity:.5;font-size:10.5px;margin-top:4px">'
        + passwordBoxes().length + ' password boxes found, matched by ' + f.how
        + ' — ' + seen.join(' / ') + '</div>';

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

    // Angular can re-render a field straight back to empty. Pressing Save on
    // three blank boxes achieves nothing and reads as a silent failure, so the
    // values are read back before going any further.
    const stuck = [f.old, f.neu, f.confirm].filter(function (el) { return !el.value; });

    if (stuck.length) {
        return fail(bar, msg, go,
            stuck.length + ' of the three boxes would not take a value. '
            + 'Type the passwords in by hand this time, and tell me so I can fix the filling.');
    }

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
