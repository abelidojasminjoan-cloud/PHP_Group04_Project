document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const form = document.getElementById('pvPayPalPaymentForm');
    if (!form) return;
    const status = document.getElementById('pvPayPalStatus');
    const error = document.getElementById('pvPayPalError');
    let busy = false;
    async function pay(action) {
        if (busy || !['start', 'confirm', 'cancel'].includes(action)) return;
        const data = new FormData(form);
        data.set('action', action);
        busy = true;
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button').forEach(button => button.disabled = true);
        error.hidden = true;
        status.textContent = action === 'start' ? 'Connecting to PayPal…' : action === 'cancel' ? 'Checking payment before cancelling…' : 'Verifying your PayPal payment…';
        try {
            const response = await fetch(form.getAttribute('action'), { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Payment could not be verified. Retry Check payment before placing another order.');
            const target = new URL(result.redirect_url, window.location.href);
            const local = target.origin === window.location.origin;
            const gateway = action === 'start' && target.protocol === 'https:' && target.hostname === form.dataset.paypalHost && !target.port;
            if ((!local && !gateway) || !['http:', 'https:'].includes(target.protocol) || target.username || target.password) throw new Error('Invalid payment destination. Please retry Check payment.');
            window.location.assign(target.href);
        } catch (failure) {
            error.textContent = failure instanceof SyntaxError ? 'The payment server could not complete this request. Retry Check payment before starting another order.' : failure.message;
            form.hidden = false;
            error.hidden = false;
            status.textContent = 'Your order is saved. You can retry payment here.';
            busy = false;
            form.removeAttribute('aria-busy');
            form.querySelectorAll('button').forEach(button => button.disabled = false);
        }
    }
    form.addEventListener('submit', event => {
        event.preventDefault();
        pay(event.submitter?.value || 'confirm');
    });
    if (form.dataset.autoAction) pay(form.dataset.autoAction);
});