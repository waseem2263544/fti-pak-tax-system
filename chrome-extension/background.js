/**
 * Opens a portal and fills it, on a request from the Clients page.
 *
 * The popup could only fill a tab that was already open and in front. This
 * lets the app say "log this client into IRIS" and have the tab opened, the
 * credentials fetched and the form filled in one go.
 *
 * Credentials are held in memory against the tab that asked for them, never in
 * storage, and are dropped as soon as they are used or the tab goes away.
 */
const API_BASE = 'https://app.fairtaxint.com/api/ext';
const PSID_API  = API_BASE + '/wht/psid-request/';

// Where a challan is actually created. Not the IRIS home page, and not the
// separate e-Payments site - both were guesses, and both were wrong.
const EPAYMENT_URL = 'https://iris.fbr.gov.pk/payment/single';

const PORTAL_URLS = {
    fbr:  'https://iris.fbr.gov.pk/',
    kpra: 'https://kpra.kp.gov.pk/',
    secp: 'https://eservices.secp.gov.pk/',
};

const pending = new Map();   // tabId -> { portal, credentials }

chrome.runtime.onMessage.addListener(function (msg, sender, sendResponse) {
    if (msg.action === 'openPortal') {
        openPortal(msg).then(sendResponse).catch(function (e) {
            sendResponse({ ok: false, error: e.message });
        });
        return true;                       // the reply is asynchronous
    }

    if (msg.action === 'startPsid') {
        startPsid(msg).then(sendResponse).catch(function (e) {
            sendResponse({ ok: false, error: e.message });
        });
        return true;
    }

    if (msg.action === 'filePsid') {
        filePsid(msg).then(sendResponse).catch(function (e) {
            sendResponse({ ok: false, error: e.message });
        });
        return true;
    }

    // A portal page asking what it should be filled with.
    if (msg.action === 'claimFill') {
        const tabId = sender.tab && sender.tab.id;
        const job = tabId != null ? pending.get(tabId) : null;

        if (job) {
            pending.delete(tabId);
            sendResponse({ ok: true, portal: job.portal, credentials: job.credentials });
        } else {
            sendResponse({ ok: false });
        }
        return false;
    }
});

async function openPortal(msg) {
    const stored = await chrome.storage.local.get(['token']);

    if (!stored.token) {
        return { ok: false, error: 'signed-out' };
    }

    let url = API_BASE + '/credentials/' + encodeURIComponent(msg.clientId) + '?portal=' + encodeURIComponent(msg.portal);
    if (msg.directorId) {
        url += '&director_id=' + encodeURIComponent(msg.directorId);
    }

    const res = await fetch(url, {
        headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
    });

    if (res.status === 401) {
        await chrome.storage.local.remove(['token', 'userName']);
        return { ok: false, error: 'signed-out' };
    }

    if (!res.ok) {
        return { ok: false, error: 'lookup-failed' };
    }

    const credentials = await res.json();

    if (!credentials.password) {
        return { ok: false, error: 'no-credentials' };
    }

    const tab = await chrome.tabs.create({ url: PORTAL_URLS[msg.portal] || PORTAL_URLS.fbr, active: true });
    pending.set(tab.id, { portal: msg.portal, credentials: credentials });

    // The page asks for these itself once it has loaded, but portals that
    // render their form late get a nudge as well.
    setTimeout(function () {
        chrome.tabs.sendMessage(tab.id, { action: 'fill', portal: msg.portal, credentials: credentials }, function () {
            void chrome.runtime.lastError;   // the tab may not be listening yet
        });
    }, 2500);

    return { ok: true, client: credentials.client_name };
}

// Never leave credentials behind for a tab that has gone.
chrome.tabs.onRemoved.addListener(function (tabId) { pending.delete(tabId); });

/**
 * Arm the PSID watcher.
 *
 * The app opens a request and hands over its token; the watcher on the IRIS
 * pages picks it up from storage, so it survives the navigation into
 * ePayments and any reload along the way.
 */
async function startPsid(msg) {
    const stored = await chrome.storage.local.get(['token']);

    if (!stored.token) {
        return { ok: false, error: 'signed-out' };
    }

    const res = await fetch(PSID_API + encodeURIComponent(msg.token), {
        headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
    });

    if (res.status === 401) {
        await chrome.storage.local.remove(['token', 'userName']);
        return { ok: false, error: 'signed-out' };
    }

    if (!res.ok) {
        return { ok: false, error: 'request-not-found' };
    }

    const info = await res.json();

    await chrome.storage.local.set({
        psidJob: {
            token: info.token,
            agent: info.agent,
            kind: info.kind,
            entryCount: info.entry_count,
            totalTax: info.total_tax,
            hasLogin: !!info.has_login,
        },
    });

    // Creating a PSID needs no sign-in: the e-Payments portal takes the NTN,
    // the section and the amount from anyone. Opening IRIS was the mistake -
    // it has nothing to do with generating a challan, and only ever produced a
    // login page.
    await chrome.tabs.create({ url: EPAYMENT_URL, active: true });

    return { ok: true };
}

async function filePsid(msg) {
    const stored = await chrome.storage.local.get(['token']);

    if (!stored.token) {
        return { ok: false, error: 'the extension is signed out' };
    }

    const res = await fetch(PSID_API + encodeURIComponent(msg.token), {
        method: 'POST',
        headers: {
            'X-Extension-Token': stored.token,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ psid_no: msg.psid }),
    });

    if (!res.ok) {
        const body = await res.json().catch(function () { return {}; });
        return { ok: false, error: body.error || ('the app returned ' + res.status) };
    }

    const data = await res.json();

    // The job is done; clear it so the watcher stops offering.
    await chrome.storage.local.remove(['psidJob']);

    return { ok: true, entries: data.entries, psid: data.psid_no };
}
