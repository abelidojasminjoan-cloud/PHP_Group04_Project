/* Image fallbacks without inline event handlers, compatible with the site CSP. */
document.addEventListener('error', event => {
    const image = event.target;
    if (!(image instanceof HTMLImageElement)) return;
    if (image.dataset.imageFallback) {
        const fallback = image.dataset.imageFallback;
        delete image.dataset.imageFallback;
        image.src = fallback;
    } else if (image.hasAttribute('data-hide-broken-image')) {
        image.hidden = true;
        image.style.display = 'none';
        if (image.hasAttribute('data-show-image-placeholder') && image.nextElementSibling) {
            image.nextElementSibling.style.display = 'flex';
        }
    }
}, true);
