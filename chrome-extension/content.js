// Content script - runs on FBR, KPRA, SECP portals
// Listens for fill commands from popup

chrome.runtime.onMessage.addListener(function (msg, sender, sendResponse) {
    if (msg.action !== 'fill') return;

    const creds = msg.credentials;
    let filled = false;

    switch (msg.portal) {
        case 'fbr':
            filled = fillFBR(creds);
            break;
        case 'kpra':
            filled = fillKPRA(creds);
            break;
        case 'secp':
            filled = fillSECP(creds);
            break;
    }

    sendResponse({ success: filled });
});

function fillFBR(creds) {
    const selectors = [
        { user: 'input[name="userId"]', pass: 'input[name="password"]', pin: 'input[name="pin"]' },
        { user: 'input[name="username"]', pass: 'input[name="password"]', pin: 'input[name="pin"]' },
        { user: 'input[id="userId"]', pass: 'input[id="password"]', pin: 'input[id="pin"]' },
        { user: 'input[placeholder*="CNIC"]', pass: 'input[type="password"]', pin: 'input[placeholder*="PIN"]' },
        { user: 'input[placeholder*="User"]', pass: 'input[type="password"]', pin: 'input[placeholder*="Pin"]' },
        { user: 'input[placeholder*="NTN"]', pass: 'input[type="password"]', pin: null },
    ];

    for (const sel of selectors) {
        const userEl = document.querySelector(sel.user);
        const passEl = document.querySelector(sel.pass);

        if (userEl && passEl) {
            setInputValue(userEl, creds.username || '');
            setInputValue(passEl, creds.password || '');

            if (sel.pin && creds.pin) {
                const pinEl = document.querySelector(sel.pin);
                if (pinEl) setInputValue(pinEl, creds.pin);
            }
            return true;
        }
    }

    return fillGeneric(creds.username, creds.password, creds.pin);
}

function fillKPRA(creds) {
    const selectors = [
        { user: 'input[name="username"]', pass: 'input[name="password"]', pin: 'input[name="pin"]' },
        { user: 'input[name="userId"]', pass: 'input[name="password"]', pin: 'input[name="pin"]' },
        { user: 'input[id="username"]', pass: 'input[id="password"]', pin: 'input[id="pin"]' },
        { user: 'input[placeholder*="User"]', pass: 'input[type="password"]', pin: 'input[placeholder*="Pin"]' },
        { user: 'input[placeholder*="NTN"]', pass: 'input[type="password"]', pin: null },
    ];

    for (const sel of selectors) {
        const userEl = document.querySelector(sel.user);
        const passEl = document.querySelector(sel.pass);

        if (userEl && passEl) {
            setInputValue(userEl, creds.username || '');
            setInputValue(passEl, creds.password || '');

            if (sel.pin && creds.pin) {
                const pinEl = document.querySelector(sel.pin);
                if (pinEl) setInputValue(pinEl, creds.pin);
            }
            return true;
        }
    }

    return fillGeneric(creds.username, creds.password, creds.pin);
}

function fillSECP(creds) {
    // SECP uses CNIC as the username field
    // SECP LEAP uses Angular Material with formcontrolname attributes
    // CNIC: formcontrolname="username", id="mat-input-1"
    // Password: formcontrolname="password", id="mat-input-2", type="text" (not password!)
    const cnic = creds.cnic || '';
    const password = creds.password || '';
    let filled = false;

    // Try exact SECP LEAP selectors (Angular Material)
    const cnicEl = document.querySelector('input[formcontrolname="username"]')
        || document.querySelector('input[formControlName="username"]')
        || document.querySelector('input[ng-reflect-name="username"]')
        || document.querySelector('input#mat-input-1')
        || document.querySelector('input.mat-input-element[aria-required="true"]');

    const passEl = document.querySelector('input[formcontrolname="password"]')
        || document.querySelector('input[formControlName="password"]')
        || document.querySelector('input[ng-reflect-name="password"]')
        || document.querySelector('input#mat-input-2');

    if (cnicEl && cnic) {
        setInputValue(cnicEl, cnic);
        filled = true;
    }
    if (passEl && password) {
        setInputValue(passEl, password);
        filled = true;
    }

    if (filled) return true;

    // Fallback: try all mat-input elements (SECP uses type="text" for both fields)
    const matInputs = document.querySelectorAll('input.mat-input-element');
    if (matInputs.length >= 2) {
        if (cnic) setInputValue(matInputs[0], cnic);
        if (password) setInputValue(matInputs[1], password);
        return true;
    }
    if (matInputs.length === 1 && password) {
        setInputValue(matInputs[0], password);
        return true;
    }

    // Last fallback: all visible text inputs
    const allInputs = document.querySelectorAll('input:not([type="hidden"]):not([readonly])');
    if (allInputs.length >= 2) {
        if (cnic) setInputValue(allInputs[0], cnic);
        if (password) setInputValue(allInputs[1], password);
        return true;
    }

    return false;
}

function fillGeneric(username, password, pin) {
    let filled = false;
    const textInputs = document.querySelectorAll('input[type="text"]:not([readonly])');
    const passInputs = document.querySelectorAll('input[type="password"]');

    if (username && textInputs.length > 0) {
        setInputValue(textInputs[0], username);
        filled = true;
    }

    if (password && passInputs.length > 0) {
        setInputValue(passInputs[0], password);
        filled = true;
    }

    return filled;
}

// Set value and trigger events (for React/Angular forms)
function setInputValue(el, value) {
    const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    nativeInputValueSetter.call(el, value);

    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
}
