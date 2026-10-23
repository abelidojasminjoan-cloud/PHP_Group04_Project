/* Generate the authenticator QR locally; no setup secrets go to a QR service. */
(() => {
    'use strict';
    const container = document.getElementById('authenticatorQr');
    if (!container) return;
    try {
        const qr = qrcodegen.QrCode.encodeText(container.dataset.uri, qrcodegen.QrCode.Ecc.MEDIUM);
        const border = 4;
        const size = qr.size + border * 2;
        const ns = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('viewBox', `0 0 ${size} ${size}`);
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Scan with Google Authenticator to set up your PureVia account');
        svg.setAttribute('shape-rendering', 'crispEdges');
        const background = document.createElementNS(ns, 'rect');
        background.setAttribute('width', '100%');
        background.setAttribute('height', '100%');
        background.setAttribute('fill', '#fff');
        svg.append(background);
        const modules = [];
        for (let y = 0; y < qr.size; y++) {
            for (let x = 0; x < qr.size; x++) {
                if (qr.getModule(x, y)) modules.push(`M${x + border},${y + border}h1v1h-1z`);
            }
        }
        const path = document.createElementNS(ns, 'path');
        path.setAttribute('d', modules.join(''));
        path.setAttribute('fill', '#000');
        svg.append(path);
        container.replaceChildren(svg);
    } catch (error) {
        container.hidden = true;
        document.getElementById('qrError').hidden = false;
        document.getElementById('manualSetup').open = true;
    }
})();
