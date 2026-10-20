(() => {
    'use strict';

    const script = document.currentScript;
    if (!script || window.pureViaSessionTimeoutStarted) return;
    window.pureViaSessionTimeoutStarted = true;

    const checkUrl = script.dataset.sessionCheckUrl;
    const loginUrl = script.dataset.loginUrl;
    let checking = false;
    let redirecting = false;
    let timer;

    function checkDelay(seconds) {
        return Math.min(30000, Math.max(1000, seconds * 1000));
    }

    async function checkSession() {
        if (checking || redirecting) return;
        checking = true;
        clearTimeout(timer);
        let nextCheck = 5000;

        try {
            const response = await fetch(checkUrl, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' }
            });

            if (response.status === 401) {
                redirecting = true;
                window.location.replace(loginUrl);
                return;
            }
            if (!response.ok) throw new Error('Session check unavailable.');

            const status = await response.json();
            if (status.authenticated === false) {
                redirecting = true;
                window.location.replace(loginUrl);
                return;
            }
            if (status.authenticated === true && Number.isFinite(status.remaining_seconds)) {
                nextCheck = checkDelay(status.remaining_seconds);
            }
        } catch {
            // Retry a temporary connection failure without resetting the session.
        } finally {
            checking = false;
            if (!redirecting) timer = setTimeout(checkSession, nextCheck);
        }
    }

    timer = setTimeout(checkSession, checkDelay(Number(script.dataset.sessionRemainingSeconds) || 0));
    window.addEventListener('pageshow', checkSession);
    window.addEventListener('focus', checkSession);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) checkSession();
    });
})();
