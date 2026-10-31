(() => {
    'use strict';
    const dataUrl = new URL('../data/ph-locations.json?v=2026-09-30', document.currentScript.src).href;
    let dataset;
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase()
        .replace(/\(capital\)/g,'').replace(/^(?:city|municipality|province) of\s+/,'').replace(/\s+city$/,'').replace(/[^a-z0-9]/g,'');
    const matches = (items, value) => items.find(item => normalize(item.name) === normalize(value));
    function load() {
        if (!dataset) dataset = fetch(dataUrl, {cache:'force-cache',credentials:'same-origin'})
            .then(response => { if (!response.ok) throw new Error('Locations unavailable'); return response.json(); })
            .then(data => {
                if (data.regions?.length !== 18 || data.counts?.barangays !== 42010) throw new Error('Invalid location data');
                return data;
            }).catch(error => { dataset = null; throw error; });
        return dataset;
    }
    window.PureViaPhAddress = {
        create({region,province,city,barangay,onChange = () => {}}) {
            const fields = [region,province,city,barangay];
            const placeholders = ['Select region','Select province','Select city / municipality','Select barangay'];
            const status = document.createElement('p');
            status.className = 'pv-location-directory-status'; status.setAttribute('role','status');
            region.closest('label, .pv-field').after(status);
            let data, selectedRegion, selectedProvince, selectedCity, restoring = 0, savedFallback = false;
            const api = {isReady:false,ready:null,setAddress,isComplete};
            const fill = (field, items, selected = '') => {
                field.replaceChildren(new Option(placeholders[fields.indexOf(field)],''));
                items.forEach(item => { const name = typeof item === 'string' ? item : item.name; field.add(new Option(name,name)); });
                field.disabled = items.length === 0; field.required = true;
                if (selected) field.value = selected;
            };
            fields.forEach((field,index) => { fill(field,[]); field.setAttribute('aria-label',placeholders[index]); });
            status.textContent = 'Loading Philippine locations…';
            const update = () => { status.textContent = ''; status.hidden = true; onChange(); };
            const currentRegion = () => selectedRegion?.name === region.value ? selectedRegion : matches(data?.regions || [],region.value);
            const currentProvince = () => currentRegion()?.provinces.find(item=>item.name===province.value);
            const currentCity = () => currentProvince()?.cities.find(item=>item.name===city.value);
            region.addEventListener('change', () => {
                savedFallback = false; selectedRegion = matches(data?.regions || [],region.value);
                fill(province,selectedRegion?.provinces || []); fill(city,[]); fill(barangay,[]); update();
            });
            province.addEventListener('change', () => {
                savedFallback = false; selectedProvince = currentProvince();
                fill(city,selectedProvince?.cities || []); fill(barangay,[]); update();
            });
            city.addEventListener('change', () => {
                savedFallback = false; selectedCity = currentCity(); fill(barangay,selectedCity?.barangays || []); update();
            });
            barangay.addEventListener('change', update);
            api.ready = load().then(directory => {
                data = directory; api.isReady = true; fill(region,data.regions); update(); return true;
            }).catch(() => {
                status.hidden = false; status.textContent = 'Philippine locations could not load. Reload this page before saving an address.';
                onChange(); return false;
            });
            async function setAddress(address = {}) {
                const revision = ++restoring;
                if (!await api.ready || revision !== restoring) return false;
                selectedRegion = selectedProvince = selectedCity = null; savedFallback = false;
                const requestedRegion = normalize(address.region)==='metromanila' ? data.regions.find(item=>item.code==='1300000000') : matches(data.regions,address.region);
                const candidates = [];
                for (const r of requestedRegion ? [requestedRegion] : data.regions) {
                    for (const p of r.provinces) {
                        if (address.province && normalize(p.name)!==normalize(address.province)) continue;
                        const c = matches(p.cities,address.city);
                        if (c || !address.city) candidates.push({r,p,c});
                    }
                }
                let found = (address.province || address.city) ? candidates.find(item=>item.c) || candidates[0] : undefined;
                // Older saved addresses can use a geographic province for a now-independent city.
                if (!found && address.city) {
                    const byCity = [];
                    for (const r of requestedRegion ? [requestedRegion] : data.regions) for (const p of r.provinces) {
                        const c = matches(p.cities,address.city); if (c) byCity.push({r,p,c});
                    }
                    if (byCity.length === 1) found = byCity[0];
                }
                if (found) {
                    selectedRegion=found.r;selectedProvince=found.p;selectedCity=found.c;
                    fill(region,data.regions,found.r.name);fill(province,found.r.provinces,found.p.name);
                    fill(city,found.p.cities,found.c?.name || '');
                    const b = found.c?.barangays.find(name=>normalize(name)===normalize(address.barangay));
                    const barangays = [...(found.c?.barangays || [])];
                    if (address.barangay && !b) { barangays.push(address.barangay); savedFallback=true; }
                    fill(barangay,barangays,b || address.barangay || '');
                } else if (address.province || address.city || address.barangay) {
                    // Preserve an existing saved location; new selections still use the full directory.
                    selectedCity={name:address.city || '',barangays:address.barangay?[address.barangay]:[]};
                    selectedProvince={name:address.province || '',cities:[selectedCity]};
                    selectedRegion={name:'Saved location',provinces:[selectedProvince]};savedFallback=true;
                    fill(region,[...data.regions,selectedRegion],selectedRegion.name);
                    fill(province,[selectedProvince],selectedProvince.name);fill(city,[selectedCity],selectedCity.name);
                    fill(barangay,selectedCity.barangays,address.barangay || '');
                } else {
                    fill(region,data.regions,requestedRegion?.name || '');selectedRegion=requestedRegion;
                    fill(province,requestedRegion?.provinces || []);fill(city,[]);fill(barangay,[]);
                }
                update(); return true;
            }
            function isComplete() {
                if (!api.isReady || fields.some(field=>field.disabled || !field.value)) return false;
                const c=currentCity();
                return !!c && (c.barangays.includes(barangay.value) || (savedFallback && selectedCity===c));
            }
            return api;
        }
    };
})();
