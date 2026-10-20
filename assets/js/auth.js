
document.addEventListener('DOMContentLoaded', function () {

    // SHOW / HIDE PASSWORD
    const passwordToggles = document.querySelectorAll(
        '[data-toggle-password]'
    );

    // Font Awesome Free 7.2.0, Fonticons, Inc. https://fontawesome.com/license/free
    const slashedEyePath = 'M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L504.5 470.8C507.2 468.4 509.9 466 512.5 463.6C559.3 420.1 590.6 368.2 605.5 332.5C608.8 324.6 608.8 315.8 605.5 307.9C590.6 272.2 559.3 220.2 512.5 176.8C465.4 133.1 400.7 96.2 319.9 96.2C263.1 96.2 214.3 114.4 173.9 140.4L73 39.1zM236.5 202.7C260 185.9 288.9 176 320 176C399.5 176 464 240.5 464 320C464 351.1 454.1 379.9 437.3 403.5L402.6 368.8C415.3 347.4 419.6 321.1 412.7 295.1C399 243.9 346.3 213.5 295.1 227.2C286.5 229.5 278.4 232.9 271.1 237.2L236.4 202.5zM357.3 459.1C345.4 462.3 332.9 464 320 464C240.5 464 176 399.5 176 320C176 307.1 177.7 294.6 180.9 282.7L101.4 203.2C68.8 240 46.4 279 34.5 307.7C31.2 315.6 31.2 324.4 34.5 332.3C49.4 368 80.7 420 127.5 463.4C174.6 507.1 239.3 544 320.1 544C357.4 544 391.3 536.1 421.6 523.4L357.4 459.2z';

    passwordToggles.forEach(function (button) {

        const input = document.getElementById(button.dataset.togglePassword);
        const iconPath = button.querySelector('svg path');
        if (!input || !iconPath) return;
        const eyePath = iconPath.getAttribute('d');

        function updatePasswordToggle() {
            button.hidden = input.value.length === 0;
            if (button.hidden) input.type = 'password';
            const isVisible = input.type === 'text';
            iconPath.setAttribute('d', isVisible ? slashedEyePath : eyePath);
            button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(isVisible));
        }

        button.addEventListener('click', function () {
            input.type = input.type === 'password' ? 'text' : 'password';
            updatePasswordToggle();
        });

        input.addEventListener('input', updatePasswordToggle);
        input.addEventListener('change', updatePasswordToggle);
        window.addEventListener('pageshow', updatePasswordToggle);
        updatePasswordToggle();

    });


    // Show field errors inline instead of browser validation popups.
    const form = document.querySelector('.auth-form');
    if (!form) return;

    const fields = Array.from(form.querySelectorAll('input:not([type="hidden"])'));
    const formError = document.querySelector('.auth-form-error');
    const registerPassword = document.getElementById('registerPassword');
    let registering = false;

    if (registerPassword) {
        window.addEventListener('pageshow', () => {
            registering = false;
            form.removeAttribute('aria-busy');
            form.querySelector('button[type="submit"]').disabled = false;
        });
    }

    const minimumLength = registerPassword ? registerPassword.minLength : 12;
    const passwordRules = [
        { key: 'length', test: value => new TextEncoder().encode(value).length >= minimumLength, message: `Password must contain at least ${minimumLength} characters.` },
        { key: 'uppercase', test: value => /[A-Z]/.test(value), message: 'Password must contain at least one uppercase letter.' },
        { key: 'lowercase', test: value => /[a-z]/.test(value), message: 'Password must contain at least one lowercase letter.' },
        { key: 'number', test: value => /[0-9]/.test(value), message: 'Password must contain at least one number.' },
        { key: 'symbol', test: value => /[^A-Za-z0-9]/.test(value), message: 'Password must contain at least one special character.' }
    ].filter(rule => !registerPassword || document.getElementById('registerPasswordFeedback')?.querySelector(`[data-password-rule="${rule.key}"]`));

    const passwordFeedback = document.getElementById('registerPasswordFeedback');
    const passwordStrength = document.getElementById('registerPasswordStrength');

    if (registerPassword && passwordFeedback && passwordStrength) {
        function updatePasswordFeedback() {
            const value = registerPassword.value;
            let matched = 0;

            passwordRules.forEach(rule => {
                const met = rule.test(value);
                const item = passwordFeedback.querySelector(`[data-password-rule="${rule.key}"]`);
                if (met) matched++;
                if (!item) return;
                item.dataset.met = String(met);
                const description = item.lastElementChild.textContent;
                item.setAttribute('aria-label', `${met ? 'Met' : 'Not met'}: ${description}`);
            });

            // An estimate only; common prefixes and repeated patterns stay weak.
            const predictable = /^(?:password|qwerty|letmein|welcome|admin|123456|abcdef)/i.test(value)
                || /^(.{1,4})\1+$/.test(value);
            let level = 'empty';
            let label = 'Not set';
            if (value) {
                level = matched === passwordRules.length && !predictable ? 'strong'
                    : value.length >= 8 && matched >= 3 && !predictable ? 'medium' : 'weak';
                label = level === 'strong' ? 'Strong' : level === 'medium' ? 'Medium' : 'Weak';
            }
            passwordStrength.dataset.level = level;
            if (passwordStrength.textContent !== label) passwordStrength.textContent = label;
        }

        const passwordWrapper = registerPassword.closest('.auth-password-wrapper');
        passwordWrapper.addEventListener('focusin', () => {
            passwordFeedback.hidden = false;
        });
        passwordWrapper.addEventListener('focusout', event => {
            if (!passwordWrapper.contains(event.relatedTarget)) {
                passwordFeedback.hidden = true;
            }
        });

        registerPassword.addEventListener('input', updatePasswordFeedback);
        updatePasswordFeedback();
    }

    function validationMessage(input) {
        const isPassword = input.name === 'password' || input.name === 'confirm_password';
        const value = isPassword ? input.value : input.value.trim();

        switch (input.name) {
            case 'first_name':
            case 'last_name': {
                const name = input.name === 'first_name' ? 'first' : 'last';
                if (!value) return `Please enter your ${name} name.`;
                if (value.length < 2 || value.length > 100) {
                    return `${name === 'first' ? 'First' : 'Last'} name must contain 2 to 100 characters.`;
                }
                break;
            }
            case 'email':
                if (!value) return 'Please enter your email address.';
                if (input.validity.typeMismatch) return 'Please enter a valid email address.';
                break;
            case 'password': {
                if (!value) return registerPassword ? 'Please create a password.' : 'Please enter your password.';
                if (!registerPassword) break;
                if (new TextEncoder().encode(value).length > 72 || value.includes('\0')) return 'Password must not exceed 72 bytes or contain null characters.';
                const unmet = passwordRules.find(rule => !rule.test(value));
                if (unmet) return unmet.message;
                break;
            }
            case 'confirm_password':
                if (!value) return 'Please confirm your password.';
                if (value !== registerPassword.value) return 'Passwords do not match.';
                break;
        }

        return '';
    }

    function showError(input, message) {
        const label = document.getElementById(`${input.id}Error`);
        if (!label) return;
        label.textContent = message;
        label.hidden = message === '';
        input.setAttribute('aria-invalid', message ? 'true' : 'false');
    }

    form.addEventListener('submit', event => {
        if (registering) {
            event.preventDefault();
            return;
        }
        if (formError) formError.hidden = true;
        let firstInvalid = null;

        fields.forEach(input => {
            const message = validationMessage(input);
            showError(input, message);
            if (message && !firstInvalid) firstInvalid = input;
        });

        if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
            return;
        }

        if (registerPassword && window.PureViaAuthLoading) {
            registering = true;
            form.setAttribute('aria-busy', 'true');
            form.querySelector('button[type="submit"]').disabled = true;
            window.PureViaAuthLoading.show(
                'Creating your account',
                'Sending a verification code to your email.'
            );
        }
    });

});
