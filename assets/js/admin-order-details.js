document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const config = window.PUREVIA_ADMIN_ORDERS;
    const modal = document.getElementById('adminOrderModal');
    if (!config || !modal) return;
    const el = id => document.getElementById(id);
    const form = el('adminOrderForm');
    const dialog = modal.querySelector('.aom-dialog');
    const money = value => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
    let current = null, opener = null, controller = null, saving = false, previousOverflow = '', inertState = [];
    const node = (tag, text, className) => {
        const element = document.createElement(tag);
        if (text !== undefined) element.textContent = text;
        if (className) element.className = className;
        return element;
    };
    function error(message) { el('aomError').textContent = message; el('aomError').hidden = false; }
    function close() {
        if (saving) return;
        controller?.abort(); controller = null;
        modal.hidden = true;
        document.body.style.overflow = previousOverflow;
        inertState.forEach(([element, value]) => { element.inert = value; }); inertState = [];
        if (opener?.getClientRects().length) opener.focus(); else el('orderSearch')?.focus();
    }
    function render(order) {
        current = order;
        const values = {
            aomNumber: order.number, aomCustomer: order.customer, aomCustomerId: `Customer ID: ${order.customer_id}`,
            aomDate: order.date, aomItemCount: `${order.total_items} total ${order.total_items === 1 ? 'item' : 'items'}`,
            aomTotal: money(order.total), aomMethod: `via ${order.payment_method}`,
            aomProductCount: `${order.items.length} ${order.items.length === 1 ? 'product' : 'products'}`,
            aomRecipient: order.recipient, aomPhone: order.phone,
            aomSubtotal: money(order.subtotal), aomShipping: money(order.shipping), aomSummaryTotal: money(order.total)
        };
        Object.entries(values).forEach(([id, value]) => { el(id).textContent = value; });
        el('aomAddress').replaceChildren(...order.address.map(line => node('p', line)));
        el('aomItems').replaceChildren(...order.items.map(item => {
            const row = node('article', undefined, 'aom-item');
            const image = node('img'); image.src = item.image; image.alt = item.product_name;
            image.addEventListener('error', () => { image.src = `${config.baseUrl}/assets/images/products/default-product.svg`; }, { once: true });
            const name = node('div', undefined, 'aom-item-description');
            name.append(node('strong', item.product_name));
            name.append(node('p', [item.category_name, item.product_code ? `SKU ${item.product_code}` : ''].filter(Boolean).join(' · ')));
            const metrics = node('dl', undefined, 'aom-item-metrics');
            for (const [label, value] of [['Quantity', item.quantity], ['Unit Price', money(item.unit_price)], ['Subtotal', money(item.subtotal)]]) {
                const field = node('div'); field.append(node('dt', label), node('dd', value)); metrics.append(field);
            }
            row.append(image, name, metrics); return row;
        }));
        el('aomStatus').value = order.status;
        el('aomPayment').value = order.payment_status;
        el('aomPayment').disabled = !order.has_payment;
        el('aomLoading').hidden = true; el('aomContent').hidden = false; el('aomSave').disabled = false;
    }
    async function request(url, options = {}) {
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options });
        let data;
        try { data = await response.json(); } catch { throw new Error('Unable to load this order. Refresh the page and try again.'); }
        if (!response.ok || !data.success) throw new Error(data.message || 'Unable to load or save this order.');
        return data.order;
    }
    document.querySelectorAll('[data-order-record]').forEach(button => {
        button.addEventListener('click', async () => {
            controller?.abort(); const load = new AbortController(); controller = load;
            current = null; opener = button;
            if (modal.hidden) {
                previousOverflow = document.body.style.overflow;
                inertState = [...document.body.children].filter(element => element !== modal && !['SCRIPT', 'STYLE', 'LINK'].includes(element.tagName)).map(element => [element, element.inert]);
                inertState.forEach(([element]) => { element.inert = true; });
            }
            document.body.style.overflow = 'hidden'; modal.hidden = false;
            el('aomError').hidden = true; el('aomContent').hidden = true; el('aomLoading').hidden = false;
            el('aomSave').disabled = true; el('aomNumber').textContent = '';
            form.setAttribute('aria-busy', 'true'); modal.querySelector('.aom-body').scrollTop = 0; dialog.focus();
            try {
                const url = new URL(config.endpoint, window.location.href); url.searchParams.set('id', button.dataset.orderRecord);
                const order = await request(url, { signal: load.signal });
                if (!load.signal.aborted) render(order);
            } catch (failure) {
                if (failure.name !== 'AbortError') { el('aomLoading').hidden = true; error(failure.message); }
            } finally { if (controller === load) { controller = null; form.removeAttribute('aria-busy'); } }
        });
    });
    modal.querySelectorAll('[data-order-close]').forEach(button => button.addEventListener('click', close));
    document.addEventListener('keydown', event => {
        if (modal.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        if (event.key !== 'Tab') return;
        const controls = [...dialog.querySelectorAll('button:not(:disabled), select:not(:disabled)')].filter(element => element.getClientRects().length);
        const first = controls[0], last = controls[controls.length - 1];
        if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog)) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && (document.activeElement === last || document.activeElement === dialog)) { event.preventDefault(); first?.focus(); }
    });
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (saving || !current) return;
        saving = true; el('aomError').hidden = true; el('aomSave').textContent = 'Saving...';
        const controls = [...modal.querySelectorAll('button, select')].map(element => [element, element.disabled]);
        controls.forEach(([element]) => { element.disabled = true; }); form.setAttribute('aria-busy', 'true');
        try {
            const order = await request(config.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: current.id, revision: current.revision, status: el('aomStatus').value, payment_status: el('aomPayment').value, csrf_token: config.csrfToken }) });
            const row = document.querySelector(`.order-row[data-record-id="${order.id}"]`);
            row.dataset.status = order.status; row.dataset.payment = order.payment_status;
            const status = row.querySelector('.order-status-badge'); status.className = `order-status-badge order-state-${order.status}`; status.textContent = config.statuses[order.status].toUpperCase();
            const payment = row.querySelector('.payment-badge'); payment.className = `payment-badge payment-${order.payment_status}`; payment.textContent = config.payments[order.payment_status].toUpperCase();
            el('orderStatusFilter')?.dispatchEvent(new Event('change'));
            saving = false; close();
        } catch (failure) { error(failure.message); }
        finally {
            saving = false; controls.forEach(([element, disabled]) => { element.disabled = disabled; });
            el('aomSave').textContent = 'Save Changes'; form.removeAttribute('aria-busy');
        }
    });
});
