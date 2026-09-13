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
