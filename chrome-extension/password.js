/**
 * Resets a client's IRIS password to the firm's standard one.
 *
 * Written against the dialog as observed, not as guessed:
 *
 *  - it is not a Material dialog. It is IRIS's password-expiry component in an
 *    MDB modal, rooted at app-password-expiry-model, and titled "Reset
 *    Password". mat-dialog-container and [role=dialog] find nothing.
 *  - the three inputs keep a value set through the native setter, and Angular
 *    registers it. That part needed no change.
 *  - Save is never disabled and carries mat-dialog-close, so the modal closes
 *    whatever happens. A closed dialog is therefore not evidence of success,
 *    and IRIS shows no inline validation text to read instead. So the outcome
 *    is put to the preparer rather than assumed.
 */
const PW_API  = 'https://app.fairtaxint.com/api/ext';
const DIALOG  = 'app-password-expiry-model';

let pwJob = null;
let standard = null;

const wait = ms => new Promise(r => setTimeout(r, ms));
const txt  = el => ((el && (el.innerText || el.textContent)) || '').trim();

chrome.storage.local.get(['pwJob'], async function (s) {
    pwJob = s.pwJob || null;

    watchForDialog();

    // Armed from the popup: open the dialog rather than leaving the preparer
    // to find it. Two clicks in a menu is the step that was being missed.
    if (pwJob && !document.querySelector(DIALOG)) {
        await openChangePassword();
    }
});

function watchForDialog() {
    const look = function () {
        if (document.getElementById('fairtax-pw-bar')) { return; }
        if (passwordBoxes().length >= 3) { begin(); }
    };

    look();
    new MutationObserver(function () {
        clearTimeout(watchForDialog._t);
        watchForDialog._t = setTimeout(look, 300);
    }).observe(document.body, { childList: true, subtree: true });
}

/**
 * Profile menu, then Change Password.
 *
 * The trigger is matched on its icon ligature: the menu-trigger class alone
 * also catches the Certificates sub-menu once the menu is open. The item lives
 * in the CDK overlay, not under the button, and "Change PIN" carries the same
 * icon - so the item is matched on its text.
 */
async function openChangePassword() {
    const trigger = Array.prototype.slice.call(document.querySelectorAll('button.mat-mdc-menu-trigger'))
        .find(b => txt(b.querySelector('mat-icon')) === 'person_pin');

    if (!trigger) { return false; }

    trigger.click();

    for (let i = 0; i < 12; i++) {
        await wait(250);

        const item = Array.prototype.slice.call(
            document.querySelectorAll('.cdk-overlay-container [role="menuitem"]')
        ).find(b => txt(b).endsWith('Change Password'));

        if (item) {
            item.click();
            return true;
        }
    }

    return false;
}

function passwordBoxes() {
    const root = document.querySelector(DIALOG) || document;

    return Array.prototype.slice.call(root.querySelectorAll('input[type="password"]'))
        .filter(el => el.offsetParent !== null);
}

function labelOf(el) {
    if (el.labels && el.labels[0]) { return el.labels[0].innerText || ''; }

    const aria = el.getAttribute('aria-label') || el.getAttribute('placeholder');
    if (aria) { return aria; }

    const field = el.closest('mat-form-field, .mat-form-field, .mat-mdc-form-field');
    return field ? (field.innerText || '') : '';
}

function fields() {
    const boxes = passwordBoxes();
    const find = re => boxes.find(el => re.test(labelOf(el)));

    const confirm = find(/confirm|re-?enter|again|retype/i);
    const old = find(/old|current|existing|previous/i);
    const neu = boxes.find(el => /new/i.test(labelOf(el)) && el !== confirm);

    if (old && neu && confirm) { return { old, neu, confirm, how: 'labels' }; }
    if (boxes.length >= 3) { return { old: boxes[0], neu: boxes[1], confirm: boxes[2], how: 'order' }; }

    return { old, neu, confirm, how: 'incomplete' };
}

function setValue(el, value) {
    const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
    setter.call(el, value);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.dispatchEvent(new Event('blur', { bubbles: true }));
}

async function fetchJson(path) {
    const stored = await chrome.storage.local.get(['token']);
    if (!stored.token) { return null; }

    try {
        const res = await fetch(PW_API + path, {
            headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
        });
        return res.ok ? await res.json() : null;
    } catch (e) {
        return null;
    }
}

async function identify() {
    const body = document.body.innerText || '';
    const seen = [];

    [/\b(\d{5}-\d{7}-\d)\b/g, /\b(\d{7}-\d)\b/g, /\b(\d{13})\b/g, /\b(\d{8})\b/g].forEach(function (re) {
        let m;
        while ((m = re.exec(body)) !== null) {
            if (seen.indexOf(m[1]) < 0) { seen.push(m[1]); }
        }
    });

    for (const reg of seen.slice(0, 12)) {
        const hit = await fetchJson('/clients/by-registration?reg=' + encodeURIComponent(reg));
        if (hit) { return hit; }
    }

    return null;
}

async function begin() {
    const bar = shell();

    const std = await fetchJson('/portal-password');
    standard = std && std.password;

    if (!standard) {
        return setBar(bar, 'No standard password is configured in the app, or the extension is signed out.', true);
    }

    let client = pwJob && pwJob.oldPassword
        ? { id: pwJob.clientId, name: pwJob.clientName, password: pwJob.oldPassword, has_password: true }
        : await identify();

    if (!client) {
        return setBar(bar,
            'Reset Password is open, but the extension cannot tell which client this login is. '
            + 'Open the extension, find the client and press "Change this to the standard password".', true);
    }

    if (!client.password) {
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

    offer(bar);
}

function shell() {
    const bar = document.createElement('div');
    bar.id = 'fairtax-pw-bar';
    bar.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);'
        + 'z-index:2147483647;background:#16181d;color:#fff;border-left:3px solid #D97706;'
        + 'font:13px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;padding:14px 18px;'
        + 'border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,.35);display:flex;gap:14px;'
        + 'align-items:center;max-width:min(660px,92vw)';
    bar.innerHTML = '<div>Checking this login…</div>';
    document.body.appendChild(bar);
    return bar;
}

function setBar(bar, html, bad) {
    bar.style.borderLeftColor = bad ? '#ef4444' : '#D97706';
    bar.innerHTML = '<div>' + html + '</div>';

    const close = mk('Dismiss', 'transparent');
    close.style.border = '1px solid rgba(255,255,255,.28)';
    close.addEventListener('click', () => bar.remove());
    bar.appendChild(close);
}

function mk(label, bg) {
    const b = document.createElement('button');
    b.textContent = label;
    b.style.cssText = 'background:' + bg + ';color:#fff;border:0;border-radius:7px;padding:8px 14px;'
        + 'font:600 13px system-ui;cursor:pointer;white-space:nowrap';
    return b;
}

function offer(bar) {
    bar.innerHTML = '';

    const f = fields();
    const seen = passwordBoxes().map(el => (labelOf(el) || '(no label)').replace(/\s+/g, ' ').trim().slice(0, 26));

    const msg = document.createElement('div');
    msg.innerHTML = 'Reset the IRIS password for <strong>' + (pwJob.clientName || 'this client')
        + '</strong> to the firm\'s standard one?'
        + '<div style="opacity:.65;font-size:11.5px;margin-top:3px">Fills all three boxes and presses Save.</div>'
        + '<div style="opacity:.5;font-size:10.5px;margin-top:4px">'
        + passwordBoxes().length + ' boxes, matched by ' + f.how + ' — ' + seen.join(' / ') + '</div>';

    const go = mk('Do it', '#D97706');
    const no = mk('Not now', 'transparent');
    no.style.border = '1px solid rgba(255,255,255,.28)';
    no.addEventListener('click', () => bar.remove());

    go.addEventListener('click', function () {
        go.disabled = true;
        go.textContent = 'Working…';
        run(bar, msg, go);
    });

    bar.appendChild(msg);
    bar.appendChild(no);
    bar.appendChild(go);
}

async function run(bar, msg, go) {
    const f = fields();

    if (!f.old || !f.neu || !f.confirm) {
        return fail(bar, msg, go, 'the three password boxes are no longer on screen');
    }

    setValue(f.old, pwJob.oldPassword);
    setValue(f.neu, pwJob.newPassword);
    setValue(f.confirm, pwJob.newPassword);

    await wait(400);

    const empty = [f.old, f.neu, f.confirm].filter(el => !el.value).length;
    if (empty) { return fail(bar, msg, go, empty + ' of the three boxes would not take a value'); }

    const before = document.body.innerText || '';

    const save = Array.prototype.slice.call(
        (document.querySelector(DIALOG) || document).querySelectorAll('button')
    ).find(b => /^\s*save\s*$/i.test(txt(b)));

    if (!save) { return fail(bar, msg, go, 'the Save button could not be found'); }

    save.click();
    await wait(3000);

    // Save closes the modal whatever the outcome, and IRIS writes no inline
    // message, so there is nothing here that proves it worked. Whatever is new
    // on the page is shown, and the preparer decides - the app's record is only
    // written on their word.
    const after = document.body.innerText || '';
    const added = after.split('\n')
        .filter(line => line.trim() && before.indexOf(line.trim()) < 0)
        .slice(0, 3)
        .join(' · ')
        .slice(0, 180);

    confirmOutcome(bar, added);
}

function confirmOutcome(bar, evidence) {
    bar.innerHTML = '';
    bar.style.borderLeftColor = '#2F6FEB';

    const msg = document.createElement('div');
    msg.innerHTML = 'Save pressed for <strong>' + pwJob.clientName + '</strong>.'
        + (evidence
            ? '<div style="opacity:.75;font-size:11.5px;margin-top:3px">IRIS says: ' + evidence + '</div>'
            : '<div style="opacity:.6;font-size:11.5px;margin-top:3px">IRIS showed no message. '
              + 'Its Save closes the dialog either way, so this cannot be read automatically.</div>')
        + '<div style="opacity:.6;font-size:11px;margin-top:4px">Did it accept the new password? '
        + 'The app is only updated if you say yes.</div>';

    const yes = mk('Yes, record it', '#0a6b4d');
    const no  = mk('No', 'transparent');
    no.style.border = '1px solid rgba(255,255,255,.28)';

    no.addEventListener('click', function () {
        setBar(bar, 'Nothing recorded. The app still holds the old password, which matches the portal.', true);
    });

    yes.addEventListener('click', async function () {
        yes.disabled = true;
        yes.textContent = 'Recording…';

        const stored = await chrome.storage.local.get(['token']);

        try {
            const res = await fetch(PW_API + '/clients/' + pwJob.clientId + '/reset-password?portal=fbr', {
                method: 'POST',
                headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
            });
            if (!res.ok) { throw new Error('the app returned ' + res.status); }
        } catch (e) {
            return setBar(bar, 'IRIS accepted it but the app was not updated (' + e.message
                + '). Set it in the app by hand, or the two will disagree.', true);
        }

        await chrome.storage.local.remove(['pwJob']);
        pwJob = null;

        bar.style.borderLeftColor = '#10b981';
        bar.innerHTML = '<div>Recorded. Sign out and in once to be sure it took.</div>';
        setTimeout(() => bar.remove(), 7000);
    });

    bar.appendChild(msg);
    bar.appendChild(no);
    bar.appendChild(yes);
}

function fail(bar, msg, go, why) {
    bar.style.borderLeftColor = '#ef4444';
    msg.innerHTML = 'Not changed — ' + why + '. Nothing was recorded in the app.';
    go.disabled = false;
    go.textContent = 'Try again';
}
