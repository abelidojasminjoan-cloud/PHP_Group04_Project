(() => {
    'use strict';
    const modal = document.getElementById('adminDeleteModal');
    if (!modal) return;
    const form = document.getElementById('adminDeleteForm');
    const title = document.getElementById('adminDeleteTitle');
    const name = document.getElementById('adminDeleteName');
    const note = document.getElementById('adminDeleteNote');
    const error = document.getElementById('adminDeleteError');
    const submit = document.getElementById('adminDeleteSubmit');
    const cancel = document.getElementById('adminDeleteCancel');
    const closeButtons = modal.querySelectorAll('[data-close-admin-delete]');
    let active = null;
    let busy = false;
    let background = [];
    let wasScrollLocked = false;
    let previousFocus = null;
    let fallbackFocus = null;

    function close() {
        if (busy || !active) return;
        background.forEach(({ element, inert }) => { element.inert = inert; });
        background = [];
        const returnTo = previousFocus?.isConnected ? previousFocus : fallbackFocus;
        if (returnTo?.isConnected) returnTo.focus();
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.toggle('form-modal-open', wasScrollLocked);
        active = null;
    }

    function open(options) {
        if (active || typeof options.onConfirm !== 'function') return;
        active = options;
        previousFocus = options.trigger || document.activeElement;
        fallbackFocus = previousFocus?.closest('form')?.querySelector('input:not([type="hidden"]), button');
        title.textContent = options.title || 'Confirm deletion';
        name.textContent = options.name || 'this item';
        note.textContent = options.message || 'This action cannot be undone.';
        error.textContent = '';
        error.hidden = true;
        submit.textContent = options.confirmLabel || 'Delete';
        submit.disabled = false;
        closeButtons.forEach(button => { if (button.tagName === 'BUTTON') button.disabled = false; });
        form.removeAttribute('aria-busy');
        wasScrollLocked = document.body.classList.contains('form-modal-open');
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('form-modal-open');
        background = Array.from(document.body.children)
            .filter(element => element !== modal && !['SCRIPT', 'STYLE'].includes(element.tagName))
            .map(element => ({ element, inert: element.inert }));
        background.forEach(({ element }) => { element.inert = true; });
        cancel.focus();
    }

    closeButtons.forEach(button => button.addEventListener('click', close));
    // Capture keys before the underlying product dialog handles Escape or Tab.
    document.addEventListener('keydown', event => {
        if (!active || !['Escape', 'Tab'].includes(event.key)) return;
        event.stopImmediatePropagation();
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            return;
        }
        const buttons = Array.from(modal.querySelectorAll('button:not(:disabled)'));
        if (!buttons.length) { event.preventDefault(); return; }
        const first = buttons[0];
        const last = buttons[buttons.length - 1];
        if (event.shiftKey && (document.activeElement === first || !buttons.includes(document.activeElement))) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || !buttons.includes(document.activeElement))) {
            event.preventDefault();
            first.focus();
        }
    }, true);

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!active || busy) return;
        busy = true;
        submit.disabled = true;
        submit.textContent = active.pendingLabel || 'Deleting…';
        closeButtons.forEach(button => { if (button.tagName === 'BUTTON') button.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        error.hidden = true;
        try {
            await active.onConfirm();
            busy = false;
            close();
        } catch (failure) {
            error.textContent = failure.message || 'Unable to delete this item. Please try again.';
            error.hidden = false;
            error.focus();
        } finally {
            busy = false;
            submit.disabled = false;
            submit.textContent = active?.confirmLabel || 'Delete';
            closeButtons.forEach(button => { if (button.tagName === 'BUTTON') button.disabled = false; });
            form.removeAttribute('aria-busy');
        }
    });

    window.PureViaAdminDelete = { open };

    const sections = [
        { selector: '.delete-product-button', key: 'product', label: 'Product' },
        { selector: '.delete-category-button', key: 'category', label: 'Category' },
        { selector: '.delete-ingredient-button', key: 'ingredient', label: 'Ingredient' },
        { selector: '.delete-concern-button', key: 'concern', label: 'Skin Concern' },
        { selector: '.delete-button[data-user-id]', key: 'user', label: 'User' }
    ];

    document.addEventListener('click', event => {
        const section = sections.find(item => event.target.closest(item.selector));
        if (!section) return;
        const trigger = event.target.closest(section.selector);
        if (trigger.disabled || !trigger.dataset[section.key + 'Id']) return;
        event.preventDefault();
        const formIds = { product: 'addProductForm', category: 'addCategoryForm', ingredient: 'addIngredientForm', concern: 'addConcernForm' };
        const recordForm = document.getElementById(formIds[section.key] || '');
        open({
            trigger,
            title: 'Delete ' + section.label + '?',
            name: trigger.dataset[section.key + 'Name'] || 'this ' + section.label.toLowerCase(),
            message: section.key === 'product'
                ? 'Its sizes and images will also be removed. Past order records will be kept.'
                : section.key === 'category'
                    ? 'Move any assigned products to another category first. This action cannot be undone.'
                    : section.key === 'ingredient'
                        ? 'Remove any links to products or customer ingredient preferences first. This action cannot be undone.'
                        : section.key === 'concern'
                            ? 'Remove any links to products or customer skin profiles first. This action cannot be undone.'
                            : 'This action cannot be undone.',
            confirmLabel: 'Delete ' + section.label,
            async onConfirm() {
                // These sections currently have no delete processors. Keep the record
                // and show a clear error instead of navigating to a nonexistent page.
                if (!['product', 'category', 'ingredient', 'concern'].includes(section.key)) {
                    throw new Error('Deleting ' + section.label.toLowerCase() + ' records is not available yet. No record has been deleted.');
                }
                const action = recordForm?.dataset.deleteAction;
                const token = recordForm?.elements.namedItem('csrf_token')?.value;
                if (!action || !token) throw new Error('Please refresh the page and try again.');
                const response = await fetch(action, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                    body: new URLSearchParams({ [section.key + '_id']: trigger.dataset[section.key + 'Id'], csrf_token: token })
                });
                let result;
                try { result = await response.json(); }
                catch { throw new Error('Unable to delete this ' + section.label.toLowerCase() + '. Please refresh and try again.'); }
                if (!response.ok || !result.success) throw new Error(result.message || 'Unable to delete this ' + section.label.toLowerCase() + '.');
                window.location.reload();
            }
        });
    });
})();
