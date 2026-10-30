/* Homepage and shop cards: fade the second image over the original without replacing it. */
document.querySelectorAll('.bestsellers .product-image[data-hover-image], .shop-product-image > img[data-hover-image]').forEach(image => {
    const link = image.closest('.product-image-wrapper, .shop-product-image');
    if (!link) return;
    let hovering = false;
    let focused = false;
    let preview = null;
    let ready = false;
    let failed = false;
    link.classList.add('pv-home-image-hover');

    function update() {
        link.classList.toggle('pv-home-image-active', ready && (hovering || focused));
    }
    function loadPreview() {
        if (preview || failed) return;
        preview = new Image();
        preview.className = 'product-image pv-home-hover-image';
        preview.alt = '';
        preview.setAttribute('aria-hidden', 'true');
        preview.decoding = 'async';
        preview.onload = () => {
            if (failed) return;
            // Establish the transparent layer before starting the fade, including cached loads.
            void preview.offsetWidth;
            ready = true;
            update();
        };
        preview.onerror = () => {
            failed = true;
            ready = false;
            update();
            preview.remove();
        };
        link.appendChild(preview);
        preview.src = image.dataset.hoverImage;
    }
    link.addEventListener('pointerenter', event => {
        if (event.pointerType === 'touch') return;
        hovering = true;
        loadPreview();
        update();
    });
    link.addEventListener('pointerleave', () => { hovering = false; update(); });
    link.addEventListener('focus', () => { focused = true; loadPreview(); update(); });
    link.addEventListener('blur', () => { focused = false; update(); });
});