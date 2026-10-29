document.addEventListener('DOMContentLoaded', () => {
    const open = document.getElementById('addStaffButton');
    const dialog = document.getElementById('staffAccountDialog');
    if (!open || !dialog) return;
    const form = document.getElementById('staffAccountForm');
    const message = document.getElementById('staffAccountMessage');
    const submit = document.getElementById('staffAccountSubmit');
    let busy = false, created = false, previousOverflow = '';
    function restore() { document.body.style.overflow = previousOverflow; open.focus(); }
    function close() {
        if (busy) return;
        dialog.close(); restore();
        if (created) window.location.reload();
    }
    open.addEventListener('click', () => {
        form.reset(); created = false; message.hidden = true;
        document.getElementById('staffAccountFields').hidden = false;
        submit.hidden = false;
        dialog.querySelector('.form-modal-footer [data-staff-close]').textContent = 'Cancel';
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden'; dialog.showModal();
        document.getElementById('staffAccountEmail').focus();
    });
    dialog.querySelectorAll('[data-staff-close]').forEach(button => button.addEventListener('click', close));
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.addEventListener('close', () => { if (!dialog.open) restore(); });
    dialog.addEventListener('click', event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) close();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !form.reportValidity()) return;
        const data = new FormData(form);
        busy = true; form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button,input').forEach(control => control.disabled = true);
        submit.textContent = 'Creating…';
        try {
            const response = await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Unable to create this staff account.');
            created = true;
            message.classList.remove('is-error'); message.textContent = result.message;
            document.getElementById('staffAccountFields').hidden = true;
            submit.hidden = true;
            dialog.querySelector('.form-modal-footer [data-staff-close]').textContent = 'Done';
        } catch (error) {
            message.classList.add('is-error');
            message.textContent = error instanceof SyntaxError ? 'The request could not be processed. Please refresh and try again.' : error.message;
        } finally {
            message.hidden = false; message.focus(); busy = false;
            form.removeAttribute('aria-busy'); form.querySelectorAll('button,input').forEach(control => control.disabled = false);
            submit.textContent = 'Create Account';
        }
    });
});