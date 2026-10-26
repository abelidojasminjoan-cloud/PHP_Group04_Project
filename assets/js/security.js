
document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const form = document.getElementById('secPasswordForm');
    if (!form) return;
    const current = document.getElementById('currentPassword');
    const password = document.getElementById('newPassword');
    const confirmation = document.getElementById('confirmPassword');
    const feedback = document.getElementById('newPasswordFeedback');
    const strength = document.getElementById('newPasswordStrength');
    const formError = document.getElementById('secFormError');
    const submit = document.getElementById('secSubmitButton');
    const minimumLength = password ? password.minLength : 12;
    const rules = [
        { key: 'length', test: value => [...value].length >= minimumLength, message: `Password must contain at least ${minimumLength} characters.` },
        { key: 'uppercase', test: value => /[A-Z]/.test(value), message: 'Password must contain at least one uppercase letter.' },
        { key: 'lowercase', test: value => /[a-z]/.test(value), message: 'Password must contain at least one lowercase letter.' },
        { key: 'number', test: value => /[0-9]/.test(value), message: 'Password must contain at least one number.' },
        { key: 'symbol', test: value => /[^A-Za-z0-9]/.test(value), message: 'Password must contain at least one special character.' }
    ].filter(rule => !password || feedback?.querySelector(`[data-password-rule="${rule.key}"]`));
    for (const rule of rules) {
        if (rule.key === 'length') continue;
        const item = (feedback)?.querySelector(`[data-password-rule="${rule.key}"]`);
        const minimum = Number(item?.dataset.minCount || 1);
        const pattern = { uppercase: /[A-Z]/g, lowercase: /[a-z]/g, number: /[0-9]/g, symbol: /[^A-Za-z0-9]/gu }[rule.key];
        rule.test = value => (value.match(pattern) || []).length >= minimum;
        if (item) rule.message = item.textContent.trim() + '.';
    }

    // Font Awesome Free 7.2.0, Fonticons, Inc. https://fontawesome.com/license/free
    const slashedEyePath = 'M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L504.5 470.8C507.2 468.4 509.9 466 512.5 463.6C559.3 420.1 590.6 368.2 605.5 332.5C608.8 324.6 608.8 315.8 605.5 307.9C590.6 272.2 559.3 220.2 512.5 176.8C465.4 133.1 400.7 96.2 319.9 96.2C263.1 96.2 214.3 114.4 173.9 140.4L73 39.1zM236.5 202.7C260 185.9 288.9 176 320 176C399.5 176 464 240.5 464 320C464 351.1 454.1 379.9 437.3 403.5L402.6 368.8C415.3 347.4 419.6 321.1 412.7 295.1C399 243.9 346.3 213.5 295.1 227.2C286.5 229.5 278.4 232.9 271.1 237.2L236.4 202.5zM357.3 459.1C345.4 462.3 332.9 464 320 464C240.5 464 176 399.5 176 320C176 307.1 177.7 294.6 180.9 282.7L101.4 203.2C68.8 240 46.4 279 34.5 307.7C31.2 315.6 31.2 324.4 34.5 332.3C49.4 368 80.7 420 127.5 463.4C174.6 507.1 239.3 544 320.1 544C357.4 544 391.3 536.1 421.6 523.4L357.4 459.2z';

    form.querySelectorAll('.sec-toggle-password').forEach(button => {
        const input = document.getElementById(button.dataset.target);
        const path = button.querySelector('svg path');
        const eyePath = path.getAttribute('d');
        const label = button.getAttribute('aria-label').replace(/^(Show|Hide) /, '');
        function updateToggle() {
            button.hidden = !input.value;
            if (button.hidden) input.type = 'password';
            const visible = input.type === 'text';
            path.setAttribute('d', visible ? slashedEyePath : eyePath);
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', (visible ? 'Hide ' : 'Show ') + label);
        }
        input.addEventListener('input', updateToggle);
        input.addEventListener('change', updateToggle);
        window.addEventListener('pageshow', updateToggle);
        button.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            updateToggle();
        });
        updateToggle();
    });

    const wrapper = password.closest('.sec-input-wrap');
    wrapper.addEventListener('focusin', () => { feedback.hidden = false; });
    wrapper.addEventListener('focusout', event => {
        if (!wrapper.contains(event.relatedTarget)) feedback.hidden = true;
    });
    function updateFeedback() {
        const value = password.value;
        let matched = 0;
        rules.forEach(rule => {
            const met = rule.test(value);
            if (met) matched++;
            const item = feedback.querySelector('[data-password-rule="' + rule.key + '"]');
            item.dataset.met = String(met);
            item.setAttribute('aria-label', (met ? 'Met: ' : 'Not met: ') + item.lastElementChild.textContent);
        });
        const predictable = /^(?:password|qwerty|letmein|welcome|admin|123456|abcdef)/i.test(value)
            || /^(.{1,4})\1+$/.test(value);
        const level = !value ? 'empty' : matched === rules.length && !predictable ? 'strong'
            : value.length >= 8 && matched >= 3 && !predictable ? 'medium' : 'weak';
        strength.dataset.level = level;
        const text = { empty: 'Not set', weak: 'Weak', medium: 'Medium', strong: 'Strong' }[level];
        if (strength.textContent !== text) strength.textContent = text;
    }
    password.addEventListener('input', updateFeedback);
    updateFeedback();

    function error(input, message) {
        const label = document.getElementById(input.id + 'Error');
        label.textContent = message;
        label.hidden = !message;
        input.setAttribute('aria-invalid', String(Boolean(message)));
    }
    form.addEventListener('submit', event => {
        if (submit.disabled) { event.preventDefault(); return; }
        formError.hidden = true;
        const currentMessage = current.value ? '' : 'Please enter your current password.';
        let passwordMessage = '';
        if (!password.value) passwordMessage = 'Please create a new password.';
        else if (new TextEncoder().encode(password.value).length > 72 || password.value.includes('\0')) passwordMessage = 'Password must not exceed 72 bytes or contain null characters.';
        else {
            const missing = rules.find(rule => !rule.test(password.value));
            if (missing) passwordMessage = missing.message;
            else if (current.value === password.value) passwordMessage = 'New password must be different from your current password.';
        }
        const confirmMessage = !confirmation.value ? 'Please confirm your new password.'
            : confirmation.value !== password.value ? 'Passwords do not match.' : '';
        const errors = [[current, currentMessage], [password, passwordMessage], [confirmation, confirmMessage]];
        errors.forEach(([input, message]) => error(input, message));
        const firstInvalid = errors.find(([, message]) => message);
        if (firstInvalid) {
            event.preventDefault();
            firstInvalid[0].focus();
        } else {
            submit.disabled = true;
            submit.textContent = 'Updating Password...';
        }
    });
});
