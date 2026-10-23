
/* =========================================
   PUREVIA - TWO-FACTOR VERIFICATION
   Client-Side Form Interaction
========================================= */

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById(
        'twoFactorForm'
    );

    const codeInput = document.getElementById(
        'verificationCode'
    );

    const verifyButton = document.getElementById(
        'verifyButton'
    );

    const errorText = document.getElementById(
        'codeError'
    );

    if (!form || !codeInput || !verifyButton || !errorText) {
        return;
    }

    let submitting = false;

    function clearError() {
        errorText.textContent = '';
        errorText.hidden = true;
        codeInput.removeAttribute('aria-invalid');
    }

    function showError(message) {
        errorText.textContent = message;
        errorText.hidden = false;
        codeInput.setAttribute('aria-invalid', 'true');
    }

    /* NORMALIZE CODE INPUT */

    codeInput.addEventListener('input', function () {

        let value = codeInput.value
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '')
            .slice(0, 10);

        codeInput.value = value;

        clearError();
    });

    /* VALIDATE BEFORE FORM SUBMISSION */

    form.addEventListener('submit', function (event) {

        if (submitting) {
            event.preventDefault();
            return;
        }

        const code = codeInput.value.trim();

        const isAuthenticatorCode = /^[0-9]{6}$/.test(
            code
        );

        const isRecoveryCode = /^[A-F0-9]{10}$/.test(
            code
        );

        if (!isAuthenticatorCode && !isRecoveryCode) {

            event.preventDefault();

            showError(
                'Enter a valid 6-digit code or ' +
                '10-character recovery code.'
            );

            codeInput.focus();

            return;
        }

        clearError();

        submitting = true;
        verifyButton.disabled = true;
        verifyButton.textContent = 'Verifying...';
    });

});
