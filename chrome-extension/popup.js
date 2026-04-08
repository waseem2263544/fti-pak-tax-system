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

function showMain(name) {
    document.getElementById('loginScreen').classList.add('hidden');
    document.getElementById('mainScreen').classList.remove('hidden');
    document.getElementById('userName').textContent = name;
    document.getElementById('searchInput').focus();
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
            + buttons
            + '</div>';
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
