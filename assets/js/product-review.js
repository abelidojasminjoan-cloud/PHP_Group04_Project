document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const dialog = document.getElementById('productReviewDialog');
    const config = window.PUREVIA_PRODUCT_REVIEWS;
    if (!dialog || !config) return;
    const form = document.getElementById('productReviewForm');
    const choice = document.getElementById('reviewProductSelect');
    const fields = document.getElementById('productReviewFields');
    const photos = document.getElementById('reviewPhotos');
    const photoList = document.getElementById('reviewPhotoList');
    const submit = document.getElementById('reviewSubmit');
    const message = document.getElementById('productReviewMessage');
    const stars = Array.from(form.querySelectorAll('.review-star'));
    let orderId, opener, previousOverflow = '', busy = false;
    let files = [], objectUrls = [];
    function notice(text, error = false) {
        message.textContent = text;
        message.hidden = !text;
        message.classList.toggle('is-error', error);
        message.setAttribute('role', error ? 'alert' : 'status');
    }
    function showStars(value) { stars.forEach((star, i) => star.classList.toggle('is-selected', i < value)); }
    function selectedRating() { return Number(form.querySelector('[name="rating"]:checked')?.value || 0); }
    stars.forEach(star => {
        star.addEventListener('mouseenter', () => showStars(Number(star.querySelector('input').value)));
        star.querySelector('input').addEventListener('change', () => showStars(selectedRating()));
    });
    form.querySelector('.review-stars').addEventListener('mouseleave', () => showStars(selectedRating()));
    function clearPhotos() {
        objectUrls.forEach(url => URL.revokeObjectURL(url));
        objectUrls = [];
        photoList.replaceChildren();
    }
    function showPhotos() {
        clearPhotos();
        files.forEach((file, index) => {
            const item = document.createElement('li');
            const image = document.createElement('img');
            const url = URL.createObjectURL(file);
            objectUrls.push(url);
            image.src = url;
            image.alt = file.name;
            const name = document.createElement('span');
            name.textContent = file.name;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.setAttribute('aria-label', `Remove ${file.name}`);
            remove.addEventListener('click', () => { files.splice(index, 1); showPhotos(); });
            item.append(image, name, remove);
            photoList.append(item);
        });
    }
    photos.addEventListener('change', () => {
        const additions = Array.from(photos.files || []);
        photos.value = '';
        if (files.length + additions.length > 5) { notice('You can upload up to 5 photos.', true); return; }
        if (additions.some(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024 || file.size === 0)) {
            notice('Choose JPG, PNG, or WebP photos, up to 5 MB each.', true); return;
        }
        files.push(...additions);
        notice('');
        showPhotos();
    });
    document.getElementById('reviewUploadButton').addEventListener('click', () => photos.click());
    function products() { return config.orders[orderId]?.items || []; }
    function selectProduct() {
        const item = products().find(product => String(product.id) === choice.value);
        if (!item) return;
        form.querySelectorAll('[name="rating"]').forEach(input => input.checked = false);
        document.getElementById('reviewText').value = '';
        files = [];
        showPhotos();
        showStars(0);
        document.getElementById('reviewProductId').value = item.id;
        document.getElementById('reviewProductName').textContent = item.name;
        document.getElementById('reviewProductVariant').textContent = item.variant;
        document.getElementById('reviewProductQuantity').textContent = `Qty ${item.quantity}`;
    }
    choice.addEventListener('change', () => { selectProduct(); notice(''); });
    function refreshProducts() {
        choice.replaceChildren(...products().map(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = product.name;
            return option;
        }));
        document.getElementById('reviewProductChoice').hidden = products().length <= 1;
        fields.hidden = products().length === 0;
        submit.hidden = products().length === 0;
        dialog.querySelector('.review-dialog-footer [data-review-close]').textContent = products().length ? 'Cancel' : 'Done';
        if (products().length) selectProduct();
    }
    function restorePage() {
        document.body.style.overflow = previousOverflow;
        files = [];
        clearPhotos();
        const target = opener?.disabled ? opener.closest('.mo-order-card')?.querySelector('a') : opener;
        target?.focus();
    }
    function close() {
        if (busy) return;
        dialog.close();
        restorePage();
    }
    dialog.querySelectorAll('[data-review-close]').forEach(button => button.addEventListener('click', close));
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.addEventListener('click', event => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) close();
    });
    dialog.addEventListener('close', () => { if (!dialog.open) restorePage(); });
    document.querySelectorAll('[data-review-order]').forEach(button => {
        button.addEventListener('click', () => {
            orderId = button.dataset.reviewOrder;
            if (!config.orders[orderId]?.items?.length || busy) return;
            opener = button;
            form.reset();
            notice('');
            refreshProducts();
            document.getElementById('reviewOrderId').value = orderId;
            previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            dialog.showModal();
            dialog.querySelector('.review-dialog-body').scrollTop = 0;
        });
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (busy || !form.reportValidity()) return;
        if (!document.getElementById('reviewText').value.trim()) { notice('Please enter your review.', true); return; }
        const data = new FormData(form);
        data.delete('photos[]');
        files.forEach(file => data.append('photos[]', file));
        const productId = Number(data.get('product_id'));
        busy = true;
        form.setAttribute('aria-busy', 'true');
        const controls = Array.from(form.querySelectorAll('button,input,select,textarea'));
        controls.forEach(control => control.disabled = true);
        submit.textContent = 'Submitting…';
        try {
            const response = await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Your review could not be saved.');
            // One review per purchased product, including other orders for the same product.
            for (const [id, order] of Object.entries(config.orders)) {
                order.items = order.items.filter(item => Number(item.id) !== productId);
                const button = document.querySelector(`[data-review-order="${id}"]`);
                if (button && !order.items.length) { button.disabled = true; button.textContent = 'Review submitted'; }
            }
            refreshProducts();
            notice(result.message);
            message.focus();
        } catch (error) {
            notice(error instanceof SyntaxError ? 'The upload could not be processed. Please try smaller photos.' : error.message || 'Please try again.', true);
        } finally {
            busy = false;
            form.removeAttribute('aria-busy');
            controls.forEach(control => control.disabled = false);
            submit.textContent = 'Submit Review';
        }
    });
});