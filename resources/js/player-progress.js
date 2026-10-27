export function initPlayerProgress() {
    document.querySelectorAll('[data-player-progress]').forEach(function (panel) {
        const results = panel.querySelector('[data-progress-results]');
        const connection = panel.querySelector('[data-progress-connection]');
        let timer, controller, etag, stopped = false, delay = 15000;
        const message = function (text) { if (connection.textContent !== text) connection.textContent = text; };
        const schedule = function (milliseconds = delay) {
            window.clearTimeout(timer);
            if (!stopped && !document.hidden) timer = window.setTimeout(refresh, milliseconds);
        };
        const refresh = async function () {
            if (controller || stopped || document.hidden) return;
            if (!navigator.onLine) {
                message('Offline. Showing the last recorded update.');
                schedule(30000);
                return;
            }
            controller = new AbortController();
            const timeout = window.setTimeout(function () { controller?.abort(); }, 10000);
            try {
                const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
                if (etag) headers['If-None-Match'] = etag;
                const response = await fetch(panel.dataset.feedUrl, {
                    headers, credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
                });
                if (response.status === 401 || response.status === 403 || response.redirected) {
                    stopped = true;
                    message('Access expired. Reload the page to sign in again.');
                    return;
                }
                if (response.status !== 304) {
                    if (!response.ok) throw new Error('Progress update failed.');
                    const body = await response.json();
                    if (typeof body.html !== 'string') throw new Error('Invalid progress response.');
                    const nextEtag = response.headers.get('ETag');
                    if (!etag || nextEtag !== etag) results.innerHTML = body.html;
                    etag = nextEtag;
                }
                delay = 15000;
                message('Updates automatically every 15 seconds.');
            } catch {
                if (!document.hidden) {
                    delay = Math.min(delay * 2, 120000);
                    message('Update unavailable. Showing saved results; retrying automatically.');
                }
            } finally {
                window.clearTimeout(timeout);
                controller = null;
                schedule();
            }
        };
        document.addEventListener('visibilitychange', function () {
            window.clearTimeout(timer);
            if (document.hidden) controller?.abort();
            else schedule(0);
        });
        window.addEventListener('online', function () { delay = 15000; schedule(0); });
        window.addEventListener('pagehide', function () { stopped = true; window.clearTimeout(timer); controller?.abort(); });
        window.addEventListener('pageshow', function (event) { if (event.persisted) { stopped = false; schedule(0); } });
        schedule();
    });
}
