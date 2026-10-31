(() => {
    'use strict';
    const nonce = document.currentScript?.nonce || '';
    let sdkPromise;
    const instances = new Set();
    const valid = point => point && Number.isFinite(point.lat) && Number.isFinite(point.lng)
        && point.lat >= 4 && point.lat <= 22 && point.lng >= 112 && point.lng <= 128;
    const pointOf = value => value ? ({lat: Number(typeof value.lat === 'function' ? value.lat() : value.lat), lng: Number(typeof value.lng === 'function' ? value.lng() : value.lng)}) : null;
    // Saved-address notes are a single-line input; support its separator and old newline pins.
    const pinPattern = /\s*(?:·\s*)?Google Maps: https:\/\/www\.google\.com\/maps\/search\/\?api=1&query=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)(?=\s*(?:$|\n|·))/g;
    function splitNotes(value) {
        let point = null;
        const notes = value.replace(pinPattern, (_, lat, lng) => {
            const candidate = {lat: Number(lat), lng: Number(lng)};
            if (valid(candidate)) point = candidate;
            return '';
        }).trim();
        return {notes, point};
    }
    function loadSdk(key) {
        if (window.google?.maps?.importLibrary) return Promise.resolve(window.google.maps);
        if (!key) return Promise.reject(new Error('MAP_NOT_CONFIGURED'));
        if (!sdkPromise) {
            sdkPromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                const callback = 'pureViaDeliveryMapsReady';
                let timer;
                const fail = () => {
                    clearTimeout(timer); delete window[callback]; script.remove(); sdkPromise = null;
                    reject(new Error('MAP_UNAVAILABLE'));
                };
                window[callback] = () => { clearTimeout(timer); delete window[callback]; resolve(window.google.maps); };
                const params = new URLSearchParams({key, callback, loading:'async', v:'quarterly', language:'en', region:'PH'});
                script.src = `https://maps.googleapis.com/maps/api/js?${params}`;
                script.async = true; script.nonce = nonce; script.onerror = fail;
                timer = setTimeout(fail, 20000);
                document.head.appendChild(script);
            });
        }
        return sdkPromise;
    }
    const previousAuthFailure = window.gm_authFailure;
    window.gm_authFailure = () => {
        instances.forEach(instance => instance.unavailable());
        if (typeof previousAuthFailure === 'function') previousAuthFailure();
    };
    function mount(root) {
        const notes = document.getElementById(root.dataset.notesId);
        const fields = root.dataset.fieldIds.split(',').map(id => document.getElementById(id)).filter(Boolean);
        const form = root.closest('form');
        if (!notes || !form) return;
        const canvas = root.querySelector('[data-map-canvas]');
        const status = root.querySelector('[data-map-status]');
        const coordinates = root.querySelector('[data-map-coordinates]');
        const confirm = root.querySelector('[data-map-confirm]');
        const clear = root.querySelector('[data-map-clear]');
        const links = [root.querySelector('[data-map-link]'), document.getElementById(root.dataset.linkId)].filter(Boolean);
        const findButtons = [root.querySelector('[data-map-find]'), document.getElementById(root.dataset.findButton)].filter(Boolean);
        const currentButtons = [root.querySelector('[data-map-current]'), document.getElementById(root.dataset.currentButton)].filter(Boolean);
        let map, marker, geocoder, pending = null, saved = null, initializing, generation = 0, broken = false;
        const center = {lat:12.8797,lng:121.7740};
        const message = (text, type = '') => { status.textContent = text; status.className = `pv-delivery-map-status ${type}`; };
        const showLinks = point => links.forEach(link => {
            link.hidden = !point;
            if (point) link.href = `https://www.google.com/maps/search/?api=1&query=${point.lat.toFixed(6)},${point.lng.toFixed(6)}`;
            else link.removeAttribute('href');
        });
        const draw = (point, zoom = 17) => {
            if (!map || !marker) return;
            marker.map = point ? map : null;
            if (point) { marker.position = point; map.setCenter(point); map.setZoom(zoom); }
        };
        const preview = point => {
            if (!valid(point)) { pending = null; confirm.disabled = true; draw(saved); message('Choose a delivery location within the Philippines.', 'is-error'); return false; }
            pending = point; confirm.disabled = false; clear.disabled = false;
            coordinates.textContent = `${point.lat.toFixed(6)}, ${point.lng.toFixed(6)}`;
            draw(point);
            message('Check the marker, then select Use this pin to save this delivery location.');
            return true;
        };
        const savePin = () => {
            if (!valid(pending)) return;
            const url = `https://www.google.com/maps/search/?api=1&query=${pending.lat.toFixed(6)},${pending.lng.toFixed(6)}`;
            const value = [splitNotes(notes.value).notes, `Google Maps: ${url}`].filter(Boolean).join(' · ');
            if (value.length > 255) { message('Shorten the additional address details before saving this pin.', 'is-error'); return; }
            notes.value = value; saved = {...pending}; showLinks(saved); confirm.disabled = true;
            message('Delivery pin saved. Your written address is unchanged.', 'is-saved');
        };
        const restore = (invalidate = true) => {
            if (invalidate) generation++;
            saved = splitNotes(notes.value).point; pending = saved;
            showLinks(saved); confirm.disabled = true; clear.disabled = !saved;
            coordinates.textContent = saved ? `${saved.lat.toFixed(6)}, ${saved.lng.toFixed(6)}` : '';
            draw(saved);
            if (saved) message('Saved delivery pin loaded. Drag the marker or click the map to change it.', 'is-saved');
            else if (!broken) message('Click the map or drag the marker, then select Use this pin.');
        };
        const removePin = (changed = false) => {
            generation++; notes.value = splitNotes(notes.value).notes;
            saved = null; pending = null; showLinks(null); draw(null);
            confirm.disabled = true; clear.disabled = true; coordinates.textContent = '';
            if (!broken) message(changed ? 'Address changed. Find your address and choose a new delivery pin.' : 'Pin removed. You can still use your written delivery address.');
        };
        const unavailable = () => {
            broken = true;
            if (!map) canvas.replaceChildren(Object.assign(document.createElement('p'), {textContent:'The map is currently unavailable. You can still enter and save your delivery address.'}));
            if (!map) canvas.firstElementChild.setAttribute('data-map-placeholder','');
            message('The map could not load. Your written delivery address can still be used.', 'is-error');
            confirm.disabled = true;
        };
        instances.add({unavailable});
        async function ensureMap() {
            if (broken) throw new Error('MAP_UNAVAILABLE');
            if (map) return map;
            if (!initializing) initializing = (async () => {
                try {
                    const maps = await loadSdk(root.dataset.apiKey);
                    const [{Map}, {AdvancedMarkerElement}, {Geocoder}] = await Promise.all([
                        maps.importLibrary('maps'), maps.importLibrary('marker'), maps.importLibrary('geocoding')
                    ]);
                    canvas.replaceChildren();
                    map = new Map(canvas, {center:saved || center, zoom:saved?17:6, mapId:root.dataset.mapId,
                        streetViewControl:false, mapTypeControl:false, fullscreenControl:true, gestureHandling:'cooperative'});
                    geocoder = new Geocoder();
                    marker = new AdvancedMarkerElement({map:saved?map:null, position:saved || center, gmpDraggable:true, title:'Delivery pin. Drag to choose the exact location.'});
                    marker.addListener('dragend', () => { generation++; preview(pointOf(marker.position)); });
                    map.addListener('click', event => { generation++; if (event.latLng) preview(pointOf(event.latLng)); });
                    restore(false);
                    return map;
                } catch (error) { unavailable(); throw error; }
            })();
            return initializing;
        }
        async function findAddress() {
            if (!fields.some(field => field.value.trim())) { message('Enter your delivery address before finding it on the map.', 'is-error'); return; }
            findButtons.forEach(button => button.disabled = true);
            const request = ++generation;
            try {
                await ensureMap();
                if (request !== generation) return;
                message('Finding your delivery address…');
                const address = fields.map(field => field.value.trim()).filter(Boolean).concat('Philippines').join(', ');
                const result = await geocoder.geocode({address, componentRestrictions:{country:'PH'}});
                if (request !== generation) return;
                if (!result.results?.length || !preview(pointOf(result.results[0].geometry.location))) throw new Error('NOT_FOUND');
            } catch (error) {
                if (!broken) message('This address could not be located. Check the address or click the map to choose a pin.', 'is-error');
            } finally { findButtons.forEach(button => button.disabled = false); }
        }
        async function currentLocation() {
            if (!navigator.geolocation) { message('Current location is unavailable. Find your address or choose a pin on the map.', 'is-error'); return; }
            const request = ++generation;
            currentButtons.forEach(button => button.disabled = true);
            message('Finding your current location…');
            navigator.geolocation.getCurrentPosition(async position => {
                if (request !== generation) { currentButtons.forEach(button => button.disabled = false); return; }
                try {
                    const point = {lat:position.coords.latitude,lng:position.coords.longitude};
                    if (!valid(point)) { message('Your current location is outside the delivery area. Choose your Philippine delivery address on the map.', 'is-error'); return; }
                    // Pin saving still works if the Google map cannot load.
                    try { await ensureMap(); } catch (_) { /* The written address remains usable. */ }
                    if (request === generation && preview(point)) savePin();
                } finally { currentButtons.forEach(button => button.disabled = false); }
            }, () => {
                if (request === generation) message('Location access was denied or unavailable. Find your address or choose a pin on the map.', 'is-error');
                currentButtons.forEach(button => button.disabled = false);
            }, {enableHighAccuracy:true,timeout:10000,maximumAge:60000});
        }
        findButtons.forEach(button => button.addEventListener('click', findAddress));
        currentButtons.forEach(button => button.addEventListener('click', currentLocation));
        confirm.addEventListener('click', savePin); clear.addEventListener('click', () => removePin());
        fields.forEach(field => {
            field.addEventListener('input', () => removePin(true));
            field.addEventListener('change', () => removePin(true));
        });
        const region = form.querySelector('#pvDelivery_region, #addressRegion');
        region?.addEventListener('input', () => removePin(true));
        region?.addEventListener('change', () => removePin(true));
        notes.addEventListener('input', restore);
        form.addEventListener('pv:address-restored', restore);
        restore();
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => {
                if (entries.some(entry => entry.isIntersecting)) { observer.disconnect(); ensureMap().catch(() => {}); }
            }, {rootMargin:'120px'});
            observer.observe(root);
        } else if (root.getClientRects().length) ensureMap().catch(() => {});
    }
    document.querySelectorAll('[data-delivery-map]').forEach(mount);
})();
