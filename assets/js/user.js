document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const search = document.getElementById('userSearch');
    const roleFilter = document.getElementById('roleFilter');
    const rows = document.querySelectorAll('.user-row');
    const empty = document.getElementById('noUsersFound');
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const endpoint = form.getAttribute('action');
    const title = document.getElementById('userFormTitle');
    const error = document.getElementById('userFormError');
    const save = document.getElementById('userSaveButton');
    const password = document.getElementById('userPassword');
    const optional = document.getElementById('userPasswordOptional');
    const closeButtons = modal.querySelectorAll('[data-close-user-modal]');
    let busy = false;
    let loading = false;
    let previousFocus;
    let background = [];
    let controller;

    function filterUsers() {
        let visible = 0;
        const value = search.value.trim().toLowerCase();
        rows.forEach(row => {
            const show = (roleFilter.value === 'all' || row.dataset.role === roleFilter.value) && row.dataset.search.includes(value);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        empty.style.display = visible ? 'none' : 'table-row';
    }
    search.addEventListener('input', filterUsers);
    roleFilter.addEventListener('change', filterUsers);
    filterUsers();

    async function request(data, signal) {
        const response = await fetch(data ? endpoint : endpoint + '?id=' + encodeURIComponent(form.elements.id.value), {
            method: data ? 'POST' : 'GET', credentials: 'same-origin', signal,
            headers: { Accept: 'application/json', ...(data ? { 'Content-Type': 'application/json' } : {}) },
            ...(data ? { body: JSON.stringify({ ...data, csrf_token: form.elements.csrf_token.value }) } : {})
        });
        let result;
        try { result = await response.json(); }
        catch { throw new Error('Unable to load or save this user. Refresh the page and try again.'); }
        if (!response.ok || !result.success) throw new Error(result.message || 'Unable to load or save this user.');
        return result.user;
    }

    function setBusy(value, isLoading = false) {
        busy = value;
        loading = isLoading;
        form.setAttribute('aria-busy', String(value));
        form.querySelectorAll('input:not([type="hidden"]), select, button[type="submit"]').forEach(field => { field.disabled = value; });
        closeButtons.forEach(button => { if (button.tagName === 'BUTTON') button.disabled = value && !isLoading; });
        save.textContent = value ? (isLoading ? 'Loading...' : 'Saving...') : (form.elements.action.value === 'create' ? 'Add User' : 'Save Changes');
    }

    function showError(message) {
        error.textContent = message;
        error.hidden = false;
        error.focus();
    }

    function close() {
        if (busy && !loading) return;
        controller?.abort();
        background.forEach(({ element, inert }) => { element.inert = inert; });
        background = [];
        if (previousFocus?.isConnected) previousFocus.focus();
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('form-modal-open');
        password.value = '';
    }

    async function open(trigger, id) {
        if (modal.classList.contains('is-open')) return;
        form.reset();
        form.elements.id.value = id || '';
        form.elements.action.value = id ? 'update' : 'create';
        form.elements.revision.value = '';
        title.textContent = id ? 'Edit User' : 'Add User';
        password.required = !id;
        optional.hidden = !id;
        document.getElementById('userPasswordLabel').textContent = id ? 'New Password (optional)' : 'Password *';
        error.hidden = true;
        previousFocus = trigger;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('form-modal-open');
        background = Array.from(document.body.children)
            .filter(element => element !== modal && !['SCRIPT', 'STYLE'].includes(element.tagName))
            .map(element => ({ element, inert: element.inert }));
        background.forEach(({ element }) => { element.inert = true; });
        setBusy(!!id, !!id);
        modal.querySelector('.form-modal-close').focus();
        if (!id) { form.elements.first_name.focus(); return; }
        controller = new AbortController();
        const activeController = controller;
        try {
            const user = await request(null, activeController.signal);
            for (const key of ['first_name', 'last_name', 'email', 'phone', 'role_id', 'status', 'revision']) form.elements.namedItem(key).value = user[key];
            setBusy(false);
            form.elements.first_name.focus();
        } catch (failure) {
            if (failure.name === 'AbortError') return;
            setBusy(false);
            save.disabled = true;
            showError(failure.message);
        }
    }

    document.getElementById('addUserButton').addEventListener('click', event => { event.preventDefault(); open(event.currentTarget); });
    document.querySelectorAll('[data-edit-user]').forEach(button => button.addEventListener('click', event => {
        event.preventDefault(); open(button, button.dataset.editUser);
    }));
    closeButtons.forEach(button => button.addEventListener('click', close));

    document.addEventListener('keydown', event => {
        if (!modal.classList.contains('is-open')) return;
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        if (event.key !== 'Tab') return;
        const fields = Array.from(modal.querySelectorAll('button:not(:disabled), input:not([type="hidden"]):not(:disabled), select:not(:disabled)'));
        const first = fields[0], last = fields[fields.length - 1];
        if (event.shiftKey && (document.activeElement === first || !fields.includes(document.activeElement))) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && (document.activeElement === last || !fields.includes(document.activeElement))) { event.preventDefault(); first.focus(); }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !form.reportValidity()) return;
        const data = Object.fromEntries(new FormData(form));
        error.hidden = true;
        setBusy(true);
        try { await request(data); password.value = ''; window.location.reload(); }
        catch (failure) { setBusy(false); showError(failure.message); }
    });

    document.querySelectorAll('[data-delete-user]').forEach(button => button.addEventListener('click', () => {
        window.PureViaAdminDelete.open({
            trigger: button, title: 'Delete User?', name: button.dataset.userName,
            message: 'This permanently removes the account and its saved profile, cart, and reviews. Accounts with orders cannot be deleted; set them to Inactive instead.',
            confirmLabel: 'Delete User',
            async onConfirm() {
                await request({ action: 'delete', id: button.dataset.deleteUser, revision: button.dataset.revision });
                window.location.reload();
            }
        });
    }));
});
