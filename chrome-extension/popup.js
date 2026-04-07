const API_BASE = 'https://app.fairtaxint.com/ext';

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
        // Get CSRF token first
        await fetch('https://app.fairtaxint.com/sanctum/csrf-cookie', { credentials: 'include' });

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

    list.innerHTML = clients.map(c => {
        const portalKey = currentPortal ? currentPortal.key : 'fbr';
        const hasCredentials = portalKey === 'fbr' ? c.has_fbr : (portalKey === 'kpra' ? c.has_kpra : c.has_secp);
        return '<div class="client-item" data-id="' + c.id + '">'
            + '<div><div class="client-name">' + c.name + '</div>'
            + '<div class="client-type">' + c.status + (hasCredentials ? ' · Credentials available' : ' · No credentials') + '</div></div>'
            + (hasCredentials ? '<button class="fill-btn" data-id="' + c.id + '"><i class="bi bi-key-fill me-1"></i>Fill</button>' : '')
            + '</div>';
    }).join('');

    // Attach fill handlers
    list.querySelectorAll('.fill-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            fillCredentials(this.dataset.id);
        });
    });
}

async function fillCredentials(clientId) {
    const stored = await chrome.storage.local.get(['token']);
    if (!stored.token) return;

    const portalKey = currentPortal ? currentPortal.key : 'fbr';
    const statusDiv = document.getElementById('fillStatus');

    try {
        const res = await fetch(API_BASE + '/credentials/' + clientId + '?portal=' + portalKey, {
            headers: { 'X-Extension-Token': stored.token, 'Accept': 'application/json' },
        });

        const creds = await res.json();

        // Send to content script
        chrome.tabs.query({ active: true, currentWindow: true }, function (tabs) {
            chrome.tabs.sendMessage(tabs[0].id, {
                action: 'fill',
                portal: portalKey,
                credentials: creds,
            }, function (response) {
                if (response && response.success) {
                    statusDiv.className = 'status success';
                    statusDiv.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Filled for ' + creds.client_name;
                    statusDiv.classList.remove('hidden');
                    setTimeout(() => statusDiv.classList.add('hidden'), 3000);
                } else {
                    statusDiv.className = 'status error';
                    statusDiv.textContent = 'Could not fill. Make sure you are on the login page.';
                    statusDiv.classList.remove('hidden');
                }
            });
        });
    } catch (e) {
        statusDiv.className = 'status error';
        statusDiv.textContent = 'Failed to fetch credentials';
        statusDiv.classList.remove('hidden');
    }
}
