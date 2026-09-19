/**
 * The link between the app and the extension.
 *
 * Runs only on app.fairtaxint.com. The page cannot talk to the extension
 * directly, so it posts a message to itself and this relays it. Going through
 * a content script rather than externally_connectable means the page never
 * needs to know the extension's id, which changes every time the extension is
 * loaded unpacked.
 */
window.addEventListener('message', function (event) {
    if (event.source !== window) { return; }

    const data = event.data;
    if (!data || data.source !== 'fairtax-app') { return; }
    if (data.action !== 'openPortal' && data.action !== 'startPsid') { return; }

    const payload = data.action === 'startPsid'
        ? { action: 'startPsid', token: data.token }
        : { action: 'openPortal', clientId: data.clientId, portal: data.portal, directorId: data.directorId };

    chrome.runtime.sendMessage(
        payload,
        function (reply) {
            window.postMessage({
                source: 'fairtax-extension',
                action: data.action + 'Result',
                requestId: data.requestId,
                result: reply || { ok: false, error: 'no-response' },
            }, '*');
        }
    );
});

// Lets the page know the extension is installed, so it can offer the button
// rather than a link that would do nothing.
document.documentElement.setAttribute('data-fairtax-extension', 'ready');
