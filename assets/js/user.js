document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const search = document.getElementById('userSearch');
    const roleFilter = document.getElementById('roleFilter');
    const rows = document.querySelectorAll('.user-row');
    const empty = document.getElementById('noUsersFound');
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    if (!modal || !form) return;
    const endpoint = form.getAttribute('action');
    const error = document.getElementById('userFormError');
    const save = document.getElementById('userSaveButton');
    const status = form.elements.status;
    const unlock = form.elements.unlock_account;
    const review = document.getElementById('userAccessReview');
    const unlockSection = document.getElementById('userUnlockSection');
    const reason = form.elements.reason;
    const confirmation = form.elements.confirm_access_change;
    const closeButtons = modal.querySelectorAll('[data-close-user-modal]');
    let busy = false, loading = false, previousFocus, controller, current = null, background = [];
    function filterUsers() {
        let visible = 0;
        const value = search.value.trim().toLowerCase();
        rows.forEach(row => {
            const show = (roleFilter.value === 'all' || row.dataset.role === roleFilter.value) && row.dataset.search.includes(value);
            row.style.display = show ? '' : 'none'; if (show) visible++;
        });
        empty.style.display = visible ? 'none' : 'table-row';
    }
    search.addEventListener('input', filterUsers); roleFilter.addEventListener('change', filterUsers); filterUsers();
    async function request(data, signal) {
        const response = await fetch(data ? endpoint : endpoint + '?id=' + encodeURIComponent(form.elements.id.value), {
            method: data ? 'POST' : 'GET', credentials: 'same-origin', signal,
            headers: { Accept: 'application/json', ...(data ? { 'Content-Type': 'application/json' } : {}) },
            ...(data ? { body: JSON.stringify({ ...data, csrf_token: form.elements.csrf_token.value }) } : {})
        });
        let result;
        try { result = await response.json(); } catch { throw new Error('Unable to load or save this user. Refresh the page and try again.'); }
        if (!response.ok || !result.success) throw new Error(result.message || 'Unable to load or save this user.');
        return result.user;
    }
    function refreshAccessControls() {
        if (!current) return;
        const self = Number(current.id) === Number(form.dataset.adminId);
        const pending = current.status === 'pending';
        status.disabled = busy || self || pending;
        unlockSection.hidden = self || pending || !current.login_locked || !current.email_verified;
        unlock.disabled = busy || unlockSection.hidden || status.value !== 'active';
        unlock.required = !unlockSection.hidden && status.value === 'active' && current.status !== 'active';
        if (unlockSection.hidden || status.value !== 'active') unlock.checked = false;
        const changing = status.value !== current.status || unlock.checked;
        review.hidden = !changing;
        reason.required = changing; confirmation.required = changing;
        reason.disabled = busy || !changing; confirmation.disabled = busy || !changing;
        if (!changing) { reason.value = ''; confirmation.checked = false; }
        document.getElementById('userStatusHelp').textContent = self
            ? 'You cannot change your own administrator access.'
            : pending ? 'Email verification and activation must be completed by this user. Pending access cannot be activated here.'
            : 'Inactive blocks sign-in while preserving orders and history. Changing access signs out existing sessions. Locked accounts require an explicit unlock.';
    }
    function setBusy(value, isLoading = false) {
        busy = value; loading = isLoading;
        form.setAttribute('aria-busy', String(value));
        form.querySelectorAll('input:not([type="hidden"]), select, textarea, button[type="submit"]').forEach(field => { field.disabled = value; });
        closeButtons.forEach(button => { if (button.tagName === 'BUTTON') button.disabled = value && !isLoading; });
        save.textContent = value ? (isLoading ? 'Loading...' : 'Saving...') : 'Save Changes';
        refreshAccessControls();
    }
    function showError(message) { error.textContent = message; error.hidden = false; error.focus(); }
    function close() {
        if (busy && !loading) return;
        controller?.abort();
        modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('form-modal-open');
        background.forEach(({ element, inert }) => { element.inert = inert; }); background = [];
        if (previousFocus?.isConnected) previousFocus.focus(); current = null;
    }
    async function open(trigger, id) {
        if (modal.classList.contains('is-open')) return;
        form.reset(); current = null;
        form.elements.id.value = id; form.elements.revision.value = ''; error.hidden = true;
        unlockSection.hidden = review.hidden = true; reason.required = confirmation.required = false;
        previousFocus = trigger;
        modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('form-modal-open');
        background = Array.from(document.body.children).filter(element => element !== modal && !['SCRIPT','STYLE'].includes(element.tagName)).map(element => ({element, inert: element.inert}));
        background.forEach(({element}) => {element.inert = true;});
        setBusy(true, true); modal.querySelector('.form-modal-close').focus();
        controller = new AbortController(); const activeController = controller;
        try {
            const user = await request(null, activeController.signal);
            if (activeController.signal.aborted || controller !== activeController || !modal.classList.contains('is-open')) return;
            current = user;
            for (const key of ['first_name','last_name','email','phone','role_id','revision']) form.elements.namedItem(key).value = user[key];
            document.getElementById('userRoleLabel').value = user.role;
            status.replaceChildren(...(user.status === 'pending' ? [['pending','Pending verification']] : [['active','Active'],['inactive','Inactive'],['locked','Locked']]).map(([value,label]) => new Option(label,value)));
            const activeOption = status.querySelector('option[value="active"]');
            if (activeOption) activeOption.disabled = user.status !== 'active' && !user.email_verified;
            status.value = user.status;
            form.elements.last_name.required = Number(user.role_id) !== 2;
            document.getElementById('userLastNameRequired').hidden = Number(user.role_id) === 2;
            setBusy(false); form.elements.first_name.focus();
        } catch (failure) {
            if (failure.name === 'AbortError' || activeController.signal.aborted || controller !== activeController) return;
            setBusy(false); save.disabled = true; showError(failure.message);
        }
    }
    status.addEventListener('change', () => {confirmation.checked = false; refreshAccessControls();});
    unlock.addEventListener('change', () => {confirmation.checked = false; refreshAccessControls();});
    document.querySelectorAll('[data-edit-user]').forEach(button => button.addEventListener('click', event => {event.preventDefault(); open(button,button.dataset.editUser);}));
    closeButtons.forEach(button => button.addEventListener('click',close));
    document.addEventListener('keydown',event => {
        if (!modal.classList.contains('is-open')) return;
        if (event.key === 'Escape') {event.preventDefault(); close();}
        if (event.key !== 'Tab') return;
        const fields = Array.from(modal.querySelectorAll('button:not(:disabled), input:not([type="hidden"]):not(:disabled), select:not(:disabled), textarea:not(:disabled)')).filter(field => !field.closest('[hidden]'));
        const first=fields[0],last=fields[fields.length-1];
        if (event.shiftKey && (document.activeElement===first || !fields.includes(document.activeElement))) {event.preventDefault();last.focus();}
        else if (!event.shiftKey && (document.activeElement===last || !fields.includes(document.activeElement))) {event.preventDefault();first.focus();}
    });
    form.addEventListener('submit',async event => {
        event.preventDefault(); if (busy || !current || !form.reportValidity()) return;
        const data = Object.fromEntries(new FormData(form));
        data.action='update'; data.status=status.value; data.unlock_account=unlock.checked;
        data.confirm_access_change=confirmation.checked; data.reason=reason.value;
        error.hidden=true;setBusy(true);
        try {await request(data);window.location.reload();}
        catch (failure) {setBusy(false);showError(failure.message);}
    });
    // The sidebar profile shortcut opens only the signed-in administrator's record.
    const profileUrl = new URL(window.location.href);
    if (profileUrl.searchParams.get('edit_profile') === '1') {
        const trigger = document.querySelector('.sidebar-profile-edit');
        profileUrl.searchParams.delete('edit_profile');
        window.history.replaceState(window.history.state, '', profileUrl.href);
        open(trigger, form.dataset.adminId);
    }
});