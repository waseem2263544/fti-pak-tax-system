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
    // IRIS FBR login selectors
    const selectors = [
        // Try common input patterns
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

    // Fallback: fill first text input and first password input
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
    const selectors = [
        { user: 'input[name="username"]', pass: 'input[name="password"]', pin: 'input[name="pin"]' },
        { user: 'input[name="email"]', pass: 'input[name="password"]', pin: null },
        { user: 'input[id="username"]', pass: 'input[id="password"]', pin: 'input[id="pin"]' },
        { user: 'input[type="text"]', pass: 'input[type="password"]', pin: null },
    ];

    for (const sel of selectors) {
        const passEl = document.querySelector(sel.pass);
        if (passEl) {
            setInputValue(passEl, creds.password || '');

            if (sel.user && creds.username) {
                const userEl = document.querySelector(sel.user);
                if (userEl) setInputValue(userEl, creds.username);
            }

            if (sel.pin && creds.pin) {
                const pinEl = document.querySelector(sel.pin);
                if (pinEl) setInputValue(pinEl, creds.pin);
            }
            return true;
        }
    }

    return fillGeneric(null, creds.password, creds.pin);
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
    // Native value setter
    const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    nativeInputValueSetter.call(el, value);

    // Trigger events
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
}
