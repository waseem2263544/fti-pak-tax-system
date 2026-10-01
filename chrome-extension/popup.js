const API_BASE = 'https://app.fairtaxint.com/api/ext';

// Portal detection
const PORTALS = {
    'iris.fbr.gov.pk': { name: 'FBR (IRIS)', key: 'fbr', class: 'portal-fbr' },
    'fbr.gov.pk': { name: 'FBR', key: 'fbr', class: 'portal-fbr' },
    'kpra.kp.gov.pk': { name: 'KPRA', key: 'kpra', class: 'portal-kpra' },
    'leap.secp.gov.pk': { name: 'SECP (LEAP)', key: 'secp', class: 'portal-secp' },
    'eservices.secp.gov.pk': { name: 'SECP', key: 'secp', class: 'portal-secp' },
};

let currentPortal = null;
let searchTimer = null;

// On popup open
document.addEventListener('DOMContentLoaded', function () {
    // Shown because a stale unpacked copy is invisible otherwise, and a
    // feature missing from an old build looks exactly like a broken one.
    const v = document.getElementById('extVersion');
    if (v) { v.textContent = 'v' + chrome.runtime.getManifest().version; }

    const add = document.getElementById('addClientBtn');
    if (add) { add.addEventListener('click', function () { openClientForm(null, null); }); }
});

document.addEventListener('DOMContentLoaded', async function () {
    const stored = await chrome.storage.local.get(['token', 'userName']);

    if (stored.token) {
        showMain(stored.userName || 'User');
    } else {
        document.getElementById('loginScreen').classList.remove('hidden');
    }

    // Detect current tab portal
    chrome.tabs.query({ active: true, currentWindow: true }, function (tabs) {
        if (tabs[0]) {
            const url = new URL(tabs[0].url);
            for (const [domain, info] of Object.entries(PORTALS)) {
                if (url.hostname.includes(domain)) {
                    currentPortal = info;
                    break;
                }
            }
        }
        updatePortalBadge();
    });

    // Login
    document.getElementById('loginBtn').addEventListener('click', login);
    document.getElementById('loginPassword').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') login();
    });

    // Search
    document.getElementById('searchInput').addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) {
            document.getElementById('clientList').innerHTML = '<div class="no-results">Type to search clients</div>';
            return;
        }
        searchTimer = setTimeout(() => searchClients(q), 300);
    });

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', async function () {
        await chrome.storage.local.remove(['token', 'userName']);
        document.getElementById('mainScreen').classList.add('hidden');
        document.getElementById('loginScreen').classList.remove('hidden');
        document.getElementById('loginEmail').value = '';
        document.getElementById('loginPassword').value = '';
    });
});

async function login() {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const errorDiv = document.getElementById('loginError');

    errorDiv.classList.add('hidden');

    try {
        const res = await fetch(API_BASE + '/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email, password }),
        });

        const data = await res.json();

        if (res.ok && data.token) {
            await chrome.storage.local.set({ token: data.token, userName: data.user.name });
            showMain(data.user.name);
        } else {
            errorDiv.textContent = data.error || 'Login failed';
            errorDiv.classList.remove('hidden');
        }
    } catch (e) {
        errorDiv.textContent = 'Connection failed. Check your internet.';
        errorDiv.classList.remove('hidden');
    }
}

/**
 * Show the client whose portal is open, before anything is typed.
 *
 * Opening the popup while signed in to a client's portal and being met with
 * "type to search" is a step that need not exist: the page says who it is.
 * Any registration number on it is offered to the app until one is recognised.
 */
async function showCurrentClient() {
    const stored = await chrome.storage.local.get(['token']);
    if (!stored.token) { return; }

    let found = [];

    try {
        const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
        if (!tabs[0]) { return; }

        const results = await chrome.scripting.executeScript({
            target: { tabId: tabs[0].id },
            func: function () {
                const body = document.body.innerText || '';
                const out = [];
                [/\b(\d{5}-\d{7}-\d)\b/g, /\b(\d{7}-\d)\b/g, /\b(\d{13})\b/g, /\b(\d{8})\b/g]
                    .forEach(function (re) {
                        let m;
                        while ((m = re.exec(body)) !== null) {
                            if (out.indexOf(m[1]) < 0) { out.push(m[1]); }
                        }
                    });
                return out.slice(0, 12);
            },
        });

        found = (results && results[0] && results[0].result) || [];
    } catch (e) {
        return;                       // not a page we may read; the search still works
    }

    for (const reg of found) {
        try {
            const res = await fetch(API_BASE + '/clients/by-registration?reg=' + encodeURIComponent(reg), {
                headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
            });
            if (!res.ok) { continue; }

            const c = await res.json();
            const list = document.getElementById('clientList');

            list.innerHTML =
                '<div style="font-size:10.5px;color:#77828f;text-transform:uppercase;'
                + 'letter-spacing:.6px;margin-bottom:5px">Signed in on this page</div>'
                + '<div class="client-item" data-id="' + c.id + '">'
                + '<div><div class="client-name">' + c.name + '</div>'
                + '<div class="client-type">' + reg + (c.has_password ? ' · Credentials stored' : ' · No password stored') + '</div></div>'
                + '<button class="edit-btn" data-id="' + c.id + '" data-name="' + String(c.name).replace(/"/g, '&quot;')
                + '" style="background:#2F6FEB;color:#fff;border:0;border-radius:5px;padding:6px 10px;'
                + 'font-size:11px;cursor:pointer;font-weight:600">Edit / password</button>'
                + '</div>';

            list.querySelectorAll('.edit-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    openClientForm(this.dataset.id, this.dataset.name);
                });
            });

            return;
        } catch (e) { /* try the next candidate */ }
    }
}

function showMain(name) {
    document.getElementById('loginScreen').classList.add('hidden');
    document.getElementById('mainScreen').classList.remove('hidden');
    document.getElementById('userName').textContent = name;
    document.getElementById('searchInput').focus();
    showCurrentClient();
}

function updatePortalBadge() {
    const badge = document.getElementById('portalBadge');
    if (currentPortal) {
        badge.innerHTML = '<span class="portal-badge ' + currentPortal.class + '"><i class="bi bi-globe me-1"></i>' + currentPortal.name + ' detected</span>';
    } else {
        badge.innerHTML = '<span class="portal-badge portal-unknown"><i class="bi bi-globe me-1"></i>Open FBR/KPRA/SECP portal first</span>';
    }
}

async function searchClients(q) {
    const stored = await chrome.storage.local.get(['token']);
    if (!stored.token) return;

    try {
        const res = await fetch(API_BASE + '/clients?q=' + encodeURIComponent(q), {
            headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
        });

        if (res.status === 401) {
            await chrome.storage.local.remove(['token', 'userName']);
            document.getElementById('mainScreen').classList.add('hidden');
            document.getElementById('loginScreen').classList.remove('hidden');
            return;
        }

        const clients = await res.json();
        renderClients(clients);
    } catch (e) {
        document.getElementById('clientList').innerHTML = '<div class="no-results">Search failed</div>';
    }
}

function renderClients(clients) {
    const list = document.getElementById('clientList');

    if (clients.length === 0) {
        list.innerHTML = '<div class="no-results">No clients found</div>';
        return;
    }

    const portalKey = currentPortal ? currentPortal.key : 'fbr';

    list.innerHTML = clients.map(c => {
        const hasCredentials = portalKey === 'fbr' ? c.has_fbr : (portalKey === 'kpra' ? c.has_kpra : c.has_secp);
        let extra = '';
        if (portalKey === 'secp' && c.secp_directors_count > 0) {
            extra = ' · ' + c.secp_directors_count + ' director(s)';
        }
        let buttons = '';
        if (hasCredentials) {
            if (portalKey === 'secp') {
                buttons = '<button class="fill-btn" data-id="' + c.id + '"><i class="bi bi-key-fill me-1"></i>Select</button>';
            } else {
                buttons = '<div style="display:flex;gap:4px;">'
                    + '<button class="fill-btn" data-id="' + c.id + '"><i class="bi bi-key-fill me-1"></i>Fill</button>'
                    + '<button class="fill-btn pin-btn" data-id="' + c.id + '" style="background:#f59e0b;"><i class="bi bi-eye me-1"></i>PIN</button>'
                    + '</div>';
            }
        }
        return '<div class="client-item" data-id="' + c.id + '">'
            + '<div><div class="client-name">' + c.name + '</div>'
            + '<div class="client-type">' + c.status + (hasCredentials ? ' · Credentials available' + extra : ' · No credentials') + '</div></div>'
            + '<div style="display:flex;gap:4px;align-items:center;">'
            + buttons
            + '<button class="edit-btn" data-id="' + c.id + '" data-name="' + String(c.name).replace(/"/g, '&quot;')
            + '" title="Edit details and credentials" style="background:#55606f;color:#fff;border:0;'
            + 'border-radius:5px;padding:5px 8px;font-size:11px;cursor:pointer;">Edit</button>'
            + '</div></div>';
    }).join('');

    // Fill handlers
    list.querySelectorAll('.fill-btn:not(.pin-btn)').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (portalKey === 'secp') {
                showDirectorSelection(this.dataset.id);
            } else {
                fillCredentials(this.dataset.id);
            }
        });
    });

    list.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openClientForm(this.dataset.id, this.dataset.name);
        });
    });

    // PIN reveal handlers for FBR/KPRA
    list.querySelectorAll('.pin-btn').forEach(btn => {
        btn.addEventListener('click', async function (e) {
            e.stopPropagation();
            const clientId = this.dataset.id;
            const pinBtn = this;

            if (pinBtn.dataset.revealed) {
                pinBtn.innerHTML = '<i class="bi bi-eye me-1"></i>PIN';
                pinBtn.style.background = '#f59e0b';
                delete pinBtn.dataset.revealed;
                return;
            }

            const stored = await chrome.storage.local.get(['token']);
            if (!stored.token) return;

            try {
                const res = await fetch(API_BASE + '/credentials/' + clientId + '?portal=' + portalKey, {
                    headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
                });
                const creds = await res.json();
                const pin = creds.pin || 'N/A';
                pinBtn.innerHTML = '<i class="bi bi-hash me-1"></i>' + pin;
                pinBtn.style.background = '#10b981';
                pinBtn.dataset.revealed = '1';
                setTimeout(() => {
                    pinBtn.innerHTML = '<i class="bi bi-eye me-1"></i>PIN';
                    pinBtn.style.background = '#f59e0b';
                    delete pinBtn.dataset.revealed;
                }, 5000);
            } catch (err) {
                pinBtn.innerHTML = '<i class="bi bi-x me-1"></i>Error';
                setTimeout(() => { pinBtn.innerHTML = '<i class="bi bi-eye me-1"></i>PIN'; }, 2000);
            }
        });
    });
}

async function fillCredentials(clientId, directorId) {
    const stored = await chrome.storage.local.get(['token']);
    if (!stored.token) return;

    const portalKey = currentPortal ? currentPortal.key : 'fbr';
    const statusDiv = document.getElementById('fillStatus');

    try {
        let url = API_BASE + '/credentials/' + clientId + '?portal=' + portalKey;
        if (directorId) url += '&director_id=' + directorId;
        const res = await fetch(url, {
            headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
        });

        const creds = await res.json();

        // Inject fill script directly into the page
        chrome.tabs.query({ active: true, currentWindow: true }, async function (tabs) {
            try {
                const results = await chrome.scripting.executeScript({
                    target: { tabId: tabs[0].id },
                    func: injectFill,
                    args: [portalKey, creds],
                });
                const result = results && results[0] && results[0].result;
                if (result && result.success) {
                    statusDiv.className = 'status success';
                    statusDiv.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Filled for ' + creds.client_name;
                    statusDiv.classList.remove('hidden');
                    setTimeout(() => statusDiv.classList.add('hidden'), 3000);
                } else {
                    statusDiv.className = 'status error';
                    statusDiv.textContent = result ? (result.debug || 'Could not find form fields.') : 'Could not fill. Make sure you are on the login page.';
                    statusDiv.classList.remove('hidden');
                }
            } catch (err) {
                statusDiv.className = 'status error';
                statusDiv.textContent = 'Cannot access this page. Check extension permissions.';
                statusDiv.classList.remove('hidden');
            }
        });
    } catch (e) {
        statusDiv.className = 'status error';
        statusDiv.textContent = 'Failed to fetch credentials';
        statusDiv.classList.remove('hidden');
    }
}

// ── SECP Director Selection ──

async function showDirectorSelection(clientId) {
    const stored = await chrome.storage.local.get(['token']);
    if (!stored.token) return;

    const list = document.getElementById('clientList');
    list.innerHTML = '<div class="no-results">Loading directors...</div>';

    try {
        const res = await fetch(API_BASE + '/credentials/' + clientId + '?portal=secp', {
            headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
        });
        const data = await res.json();
        const directors = data.directors || [];

        if (directors.length === 0) {
            list.innerHTML = '<div class="no-results">No directors found for this client</div>';
            return;
        }

        list.innerHTML = '<div style="padding: 8px 12px; font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Select Director for ' + data.client_name + '</div>'
            + '<div style="padding: 4px 12px 8px;"><button class="fill-btn" style="background: none; border: 1px solid #e5e7eb; color: #6b7280; width: 100%; text-align: left; padding: 6px 10px; border-radius: 6px; font-size: 0.75rem;" id="back-to-search"><i class="bi bi-arrow-left me-1"></i>Back to search</button></div>'
            + directors.map(d => {
                return '<div class="client-item" style="flex-direction: column; align-items: stretch; gap: 6px;">'
                    + '<div style="display: flex; justify-content: space-between; align-items: center;">'
                    + '<div>'
                    + '<div class="client-name" style="font-size: 0.88rem;">' + d.name + '</div>'
                    + '<div class="client-type">' + (d.cnic || 'No CNIC') + '</div>'
                    + '</div>'
                    + '<div style="display: flex; gap: 4px;">'
                    + '<button class="fill-btn director-fill" data-client="' + clientId + '" data-director="' + d.id + '"><i class="bi bi-key-fill me-1"></i>Fill</button>'
                    + (d.pin ? '<button class="fill-btn pin-reveal" data-pin="' + d.pin + '" style="background: #f59e0b; border-color: #f59e0b;"><i class="bi bi-eye me-1"></i>PIN</button>' : '')
                    + '</div>'
                    + '</div>'
                    + '</div>';
            }).join('');

        // Back button
        document.getElementById('back-to-search').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            list.innerHTML = '<div class="no-results">Type to search clients</div>';
        });

        // Fill handlers
        list.querySelectorAll('.director-fill').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                fillCredentials(this.dataset.client, this.dataset.director);
            });
        });

        // PIN reveal handlers
        list.querySelectorAll('.pin-reveal').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const pin = this.dataset.pin;
                const statusDiv = document.getElementById('fillStatus');
                if (this.textContent.includes('PIN')) {
                    this.innerHTML = '<i class="bi bi-hash me-1"></i>' + pin;
                    this.style.background = '#10b981';
                    // Auto-hide after 5 seconds
                    setTimeout(() => {
                        this.innerHTML = '<i class="bi bi-eye me-1"></i>PIN';
                        this.style.background = '#f59e0b';
                    }, 5000);
                } else {
                    this.innerHTML = '<i class="bi bi-eye me-1"></i>PIN';
                    this.style.background = '#f59e0b';
                }
            });
        });

    } catch (e) {
        list.innerHTML = '<div class="no-results">Failed to load directors</div>';
    }
}

// ── Injected Fill Function (runs in page context) ──

function injectFill(portal, creds) {
    function setValue(el, value) {
        if (!el) return;
        var nativeSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
        nativeSetter.call(el, value);
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        el.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
        el.dispatchEvent(new KeyboardEvent('keydown', { bubbles: true }));
    }

    var filled = false;
    var inputs = document.querySelectorAll('input:not([type="hidden"])');
    var debugInfo = 'Found ' + inputs.length + ' inputs. ';

    if (portal === 'fbr') {
        var userEl = document.querySelector('input[name="userId"]')
            || document.querySelector('input[name="username"]')
            || document.querySelector('input[id="userId"]')
            || document.querySelector('input[placeholder*="CNIC"]')
            || document.querySelector('input[placeholder*="User"]')
            || document.querySelector('input[placeholder*="NTN"]');
        var passEl = document.querySelector('input[type="password"]')
            || document.querySelector('input[name="password"]');
        if (userEl) { setValue(userEl, creds.username || ''); filled = true; }
        if (passEl) { setValue(passEl, creds.password || ''); filled = true; }
        if (creds.pin) {
            var pinEl = document.querySelector('input[name="pin"]') || document.querySelector('input[placeholder*="Pin"]');
            if (pinEl) setValue(pinEl, creds.pin);
        }
    } else if (portal === 'kpra') {
        var userEl = document.querySelector('input[name="username"]')
            || document.querySelector('input[name="userId"]')
            || document.querySelector('input[placeholder*="User"]');
        var passEl = document.querySelector('input[type="password"]')
            || document.querySelector('input[name="password"]');
        if (userEl) { setValue(userEl, creds.username || ''); filled = true; }
        if (passEl) { setValue(passEl, creds.password || ''); filled = true; }
        if (creds.pin) {
            var pinEl = document.querySelector('input[name="pin"]') || document.querySelector('input[placeholder*="Pin"]');
            if (pinEl) setValue(pinEl, creds.pin);
        }
    } else if (portal === 'secp') {
        var cnic = creds.cnic || '';
        var password = creds.password || '';

        // SECP LEAP Angular Material - try multiple strategies
        var cnicEl = document.querySelector('input[formcontrolname="username"]')
            || document.querySelector('input[formControlName="username"]')
            || document.querySelector('input#mat-input-1')
            || document.querySelector('input.mat-input-element');

        var matInputs = document.querySelectorAll('input.mat-input-element');
        var passEl = null;

        if (matInputs.length >= 2) {
            if (!cnicEl) cnicEl = matInputs[0];
            passEl = matInputs[1];
        } else {
            passEl = document.querySelector('input[formcontrolname="password"]')
                || document.querySelector('input[formControlName="password"]')
                || document.querySelector('input#mat-input-2')
                || document.querySelector('input[type="password"]');
        }

        // If still no luck, try all visible inputs
        if (!cnicEl && !passEl) {
            var allInputs = document.querySelectorAll('input:not([type="hidden"]):not([readonly])');
            if (allInputs.length >= 2) {
                cnicEl = allInputs[0];
                passEl = allInputs[1];
            }
        }

        debugInfo += 'CNIC el: ' + (cnicEl ? 'found' : 'NOT found') + ', Pass el: ' + (passEl ? 'found' : 'NOT found') + '. ';
        debugInfo += 'mat-inputs: ' + matInputs.length + '. ';

        if (cnicEl && cnic) { setValue(cnicEl, cnic); filled = true; }
        if (passEl && password) { setValue(passEl, password); filled = true; }
    }

    return { success: filled, debug: debugInfo };
}

/*
 * Capture the shape of whatever FBR screen is open.
 *
 * Teaching the extension a new screen - the ePayments form behind a PSID, say -
 * means knowing what its fields are called. This reports names, ids, types and
 * labels, and deliberately never reads a value: the point is the form's shape,
 * and the page may well have a password on it.
 */
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('captureBtn');
    if (!btn) { return; }

    btn.addEventListener('click', function () {
        const status = document.getElementById('captureStatus');

        chrome.tabs.query({ active: true, currentWindow: true }, async function (tabs) {
            if (!tabs[0]) { return; }

            try {
                const results = await chrome.scripting.executeScript({
                    target: { tabId: tabs[0].id },
                    func: describeForms,
                });

                const text = JSON.stringify(results[0].result, null, 2);
                await navigator.clipboard.writeText(text);

                status.textContent = 'Copied. Paste it to whoever is adding the screen.';
                status.className = 'status success';
            } catch (e) {
                status.textContent = 'Could not read this page: ' + e.message;
                status.className = 'status error';
            }
        });
    });
});

/** Runs in the page. Structure only - no values leave the tab. */
function describeForms() {
    const describe = function (el) {
        const label =
            (el.labels && el.labels[0] && el.labels[0].innerText.trim()) ||
            el.getAttribute('aria-label') ||
            el.getAttribute('placeholder') || '';

        return {
            tag: el.tagName.toLowerCase(),
            type: el.type || null,
            name: el.getAttribute('name') || null,
            id: el.id || null,
            formControlName: el.getAttribute('formcontrolname') || null,
            label: label.slice(0, 80),
            required: el.required || false,
            options: el.tagName === 'SELECT'
                ? Array.prototype.slice.call(el.options, 0, 25).map(function (o) { return o.text.trim(); })
                : undefined,
        };
    };

    return {
        url: location.href,
        title: document.title,
        forms: Array.prototype.map.call(document.forms, function (f) {
            return {
                action: f.getAttribute('action') || null,
                method: f.method || null,
                fields: Array.prototype.map.call(f.elements, describe),
            };
        }),
        // Angular apps like IRIS often render controls outside any <form>.
        loose: Array.prototype.map.call(
            document.querySelectorAll('input:not(form input), select:not(form select), textarea:not(form textarea)'),
            describe
        ),
        buttons: Array.prototype.map.call(
            document.querySelectorAll('button, input[type=submit]'),
            function (b) { return (b.innerText || b.value || '').trim().slice(0, 40); }
        ).filter(Boolean),
        fileInputs: document.querySelectorAll('input[type=file]').length,
    };
}

/* ══════════════════════════════════════════════════════════════════
   Adding and editing a client without leaving the portal.
   ══════════════════════════════════════════════════════════════════ */

/**
 * Open the client form.
 *
 * With an id it edits; without one it adds. Passwords come back from the
 * server so they can be corrected, and a field left blank on save means
 * "leave what is stored" rather than "clear it" - clearing a password by
 * accident would lock the firm out of a client's portal.
 */
async function openClientForm(clientId, name) {
    const stored = await chrome.storage.local.get(['token']);
    if (!stored.token) { return; }

    // A normal block, not an overlay. Positioned absolutely inside a container
    // with no height of its own, the form was confined to a box a fraction of
    // its size and everything below the first few fields - the reset button
    // included - sat under a scrollbar nobody would find.
    const main = document.querySelector('.main');
    const hidden = Array.prototype.slice.call(main.children);
    hidden.forEach(function (el) { el.style.display = 'none'; });

    const panel = document.createElement('div');
    panel.id = 'clientForm';

    let existing = {};

    if (clientId) {
        try {
            const res = await fetch(API_BASE + '/credentials/' + clientId + '?portal=fbr', {
                headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
            });
            if (res.ok) { existing = await res.json(); }
        } catch (e) { /* the form still opens, just empty */ }

        try {
            const k = await fetch(API_BASE + '/credentials/' + clientId + '?portal=kpra', {
                headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
            });
            if (k.ok) {
                const kd = await k.json();
                existing.kpra_username = kd.username;
                existing.kpra_password = kd.password;
                existing.kpra_pin = kd.pin;
            }
        } catch (e) { /* ignore */ }
    }

    const field = (label, id, value, type) =>
        '<label style="display:block;font-size:11px;color:#55606f;margin:8px 0 3px">' + label + '</label>'
        + '<input id="' + id + '" type="' + (type || 'text') + '" value="' + (value ? String(value).replace(/"/g, '&quot;') : '')
        + '" style="width:100%;box-sizing:border-box;border:1px solid #d5dae1;border-radius:6px;padding:7px 9px;font-size:12px">';

    panel.innerHTML =
        '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">'
        + '<strong style="font-size:13px">' + (clientId ? 'Edit ' + name : 'Add a client') + '</strong>'
        + '<button id="cfClose" style="background:none;border:0;font-size:16px;cursor:pointer;color:#77828f">×</button></div>'
        + field('Client name', 'cfName', clientId ? name : '')
        + '<label style="display:block;font-size:11px;color:#55606f;margin:8px 0 3px">Type</label>'
        + '<select id="cfStatus" style="width:100%;box-sizing:border-box;border:1px solid #d5dae1;border-radius:6px;padding:7px 9px;font-size:12px">'
        + '<option>Individual</option><option>AOP</option><option>Company</option></select>'
        + '<div style="margin-top:12px;padding-top:8px;border-top:1px solid #eef1f4;font-size:11px;font-weight:600;color:#55606f">FBR / IRIS</div>'
        + field('Username', 'cfFbrUser', existing.username)
        + field('Password', 'cfFbrPass', existing.password)
        + (clientId ? '<button id="cfReset" style="width:100%;margin-top:6px;background:#fff7ed;'
            + 'color:#8a5100;border:1px solid #D97706;border-radius:6px;padding:8px;font-size:11.5px;'
            + 'font-weight:600;cursor:pointer">Change this to the standard password</button>' : '')
        + field('PIN', 'cfFbrPin', existing.pin)
        + '<div style="margin-top:12px;padding-top:8px;border-top:1px solid #eef1f4;font-size:11px;font-weight:600;color:#55606f">KPRA</div>'
        + field('Username', 'cfKpraUser', existing.kpra_username)
        + field('Password', 'cfKpraPass', existing.kpra_password)
        + field('PIN', 'cfKpraPin', existing.kpra_pin)
        + '<div id="cfStatusMsg" style="font-size:11px;margin-top:10px"></div>'
        + '<button id="cfSave" style="width:100%;margin-top:10px;background:#2F6FEB;color:#fff;border:0;'
        + 'border-radius:6px;padding:9px;font-size:12px;font-weight:600;cursor:pointer">'
        + (clientId ? 'Save changes' : 'Add client') + '</button>'
        + '<div style="font-size:10px;color:#77828f;margin-top:8px;line-height:1.45">'
        + 'A password left blank keeps whatever is stored. Saving here records the credential in the '
        + 'firm\'s system; it does not change it on the portal.</div>';

    main.appendChild(panel);

    const closeForm = function () {
        panel.remove();
        hidden.forEach(function (el) { el.style.display = ''; });
    };

    document.getElementById('cfStatus').value = (existing.status || 'Individual');
    document.getElementById('cfClose').addEventListener('click', closeForm);

    document.getElementById('cfSave').addEventListener('click', async function () {
        const msg = document.getElementById('cfStatusMsg');
        const val = id => document.getElementById(id).value.trim();

        const body = {
            name: val('cfName'),
            status: document.getElementById('cfStatus').value,
            fbr_username: val('cfFbrUser'),
            fbr_password: val('cfFbrPass'),
            it_pin_code: val('cfFbrPin'),
            kpra_username: val('cfKpraUser'),
            kpra_password: val('cfKpraPass'),
            kpra_pin: val('cfKpraPin'),
        };

        if (!body.name) {
            msg.textContent = 'A name is needed.';
            msg.style.color = '#a01f1a';
            return;
        }

        this.disabled = true;
        this.textContent = 'Saving…';

        try {
            const res = await fetch(API_BASE + '/clients' + (clientId ? '/' + clientId : ''), {
                method: clientId ? 'PUT' : 'POST',
                headers: {
                    'X-Extension-Token': stored.token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                msg.textContent = data.error || ('Save failed (' + res.status + ').');
                msg.style.color = '#a01f1a';
                this.disabled = false;
                this.textContent = clientId ? 'Save changes' : 'Add client';
                return;
            }

            msg.textContent = 'Saved.';
            msg.style.color = '#0b6640';
            setTimeout(function () {
                closeForm();
                searchClients(document.getElementById('searchInput').value);
            }, 700);
        } catch (e) {
            msg.textContent = 'Could not reach the app.';
            msg.style.color = '#a01f1a';
            this.disabled = false;
            this.textContent = clientId ? 'Save changes' : 'Add client';
        }
    });

    const resetBtn = document.getElementById('cfReset');

    if (resetBtn) {
        resetBtn.addEventListener('click', async function () {
            const msg = document.getElementById('cfStatusMsg');
            const current = document.getElementById('cfFbrPass').value.trim();

            if (!current) {
                msg.textContent = 'The current password has to be known before it can be changed on IRIS.';
                msg.style.color = '#a01f1a';
                return;
            }

            if (!confirm('Change ' + name + "'s IRIS password to the firm's standard one?\n\n"
                + 'Open IRIS, sign in as this client and open Change Password. The extension will '
                + 'fill all three boxes and press Save, then update the app.')) { return; }

            this.disabled = true;

            try {
                const res = await fetch(API_BASE + '/portal-password', {
                    headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
                });
                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    msg.textContent = data.error || 'No standard password is configured.';
                    msg.style.color = '#a01f1a';
                    this.disabled = false;
                    return;
                }

                await chrome.storage.local.set({
                    pwJob: {
                        clientId: clientId,
                        clientName: name,
                        oldPassword: current,
                        newPassword: data.password,
                    },
                });

                msg.textContent = 'Opening Reset Password in IRIS…';
                msg.style.color = '#8a5100';

                // Bring the IRIS tab forward and reload it, so the content
                // script starts with the job already armed and opens the
                // dialog itself. Leaving the preparer to find it in a profile
                // menu is the step that was being missed.
                const tabs = await chrome.tabs.query({ url: 'https://iris.fbr.gov.pk/*' });

                if (tabs.length) {
                    await chrome.tabs.update(tabs[0].id, { active: true });
                    await chrome.tabs.reload(tabs[0].id);
                    window.close();
                } else {
                    await chrome.tabs.create({ url: 'https://iris.fbr.gov.pk/dashboard', active: true });
                    window.close();
                }
            } catch (e) {
                msg.textContent = 'Could not reach the app.';
                msg.style.color = '#a01f1a';
                this.disabled = false;
            }
        });
    }
}
