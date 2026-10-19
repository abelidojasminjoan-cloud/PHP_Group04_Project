document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('verifyOtpForm');
    if (!form || !window.fetch) return;

    const button = form.querySelector('button[type="submit"]');
    const input = document.getElementById('otp');
    const errorLabel = document.getElementById('otpError');
    let submitting = false;

    function showError(message) {
        errorLabel.textContent = message;
        errorLabel.hidden = message === '';
        input.setAttribute('aria-invalid', message ? 'true' : 'false');
    }

    function finishRequest() {
        if (window.PureViaAuthLoading) window.PureViaAuthLoading.hide();
        form.removeAttribute('aria-busy');
        button.disabled = false;
        submitting = false;
    }

    window.addEventListener('pageshow', () => {
        submitting = false;
        button.disabled = false;
        form.removeAttribute('aria-busy');
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (submitting) return;
        const code = input.value.trim();
        const error = code === '' ? 'Please enter your verification code.'
            : /^[0-9]{6}$/.test(code) ? '' : 'Please enter a valid 6-digit verification code.';
        showError(error);
        if (error) {
            input.focus();
            return;
        }
        submitting = true;
        button.disabled = true;
        form.setAttribute('aria-busy', 'true');

        if (window.PureViaAuthLoading) {
            window.PureViaAuthLoading.show(
                'Verifying your email',
                'Please wait while we check your code.'
            );
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Verification request failed.');
            const result = await response.json();
            if (result.verified === false && typeof result.error === 'string') {
                finishRequest();
                showError(result.error);
                input.focus();
                return;
            }
            if (typeof result.redirect !== 'string') throw new Error('Invalid response.');
            const destination = new URL(result.redirect, window.location.href);
            if (destination.origin !== window.location.origin) throw new Error('Invalid redirect.');

            window.location.replace(destination.href);
        } catch (error) {
            finishRequest();
            showError('Unable to verify your code. Please refresh the page and try again.');
            input.focus();
        }
    });
});
