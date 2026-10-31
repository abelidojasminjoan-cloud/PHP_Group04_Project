document.querySelectorAll('[data-home-reviews]').forEach(section => {
    const viewport = section.querySelector('[data-review-viewport]');
    const track = section.querySelector('[data-review-track]');
    const group = section.querySelector('[data-review-group]');
    const button = section.querySelector('[data-review-toggle]');
    if (!viewport || !track || !group || !button) return;
    const count = group.children.length;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let paused = false;
    const layout = () => {
        const width = viewport.clientWidth;
        const visible = Math.min(count, width >= 1000 ? 3 : width >= 600 ? 2 : 1);
        section.style.setProperty('--home-review-width', `${Math.max(1, (width - (visible - 1) * 24) / visible)}px`);
        section.style.setProperty('--home-review-duration', `${(group.scrollWidth + 24) / 26}s`);
        const moving = count > 1 && !reducedMotion.matches;
        let clone = track.querySelector('[data-review-clone]');
        if (moving && !clone) {
            clone = group.cloneNode(true);
            clone.removeAttribute('data-review-group');
            clone.setAttribute('data-review-clone', '');
            clone.setAttribute('aria-hidden', 'true');
            clone.inert = true;
            track.appendChild(clone);
        } else if (!moving && clone) {
            clone.remove();
        }
        section.classList.toggle('is-moving', moving);
        button.hidden = !moving;
    };
    button.addEventListener('click', () => {
        paused = !paused;
        section.classList.toggle('is-paused', paused);
        button.setAttribute('aria-pressed', String(paused));
        button.textContent = paused ? 'Resume reviews' : 'Pause reviews';
    });
    reducedMotion.addEventListener('change', layout);
    if ('ResizeObserver' in window) new ResizeObserver(layout).observe(viewport);
    else window.addEventListener('resize', layout);
    layout();
});
