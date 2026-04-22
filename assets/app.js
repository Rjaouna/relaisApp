import './stimulus_bootstrap.js';

const getModalElement = () => document.getElementById('crud-modal');

const getModalInstance = () => {
    const modalElement = getModalElement();

    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
};

const getCityModalElement = () => document.getElementById('city-modal');

const getCityModalInstance = () => {
    const modalElement = getCityModalElement();

    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
};

const getOptionModalElement = () => document.getElementById('option-modal');

const getOptionModalInstance = () => {
    const modalElement = getOptionModalElement();

    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
};

const getZoneModalElement = () => document.getElementById('zone-modal');

const getZoneModalInstance = () => {
    const modalElement = getZoneModalElement();

    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
};

const getClientImportModalElement = () => document.getElementById('client-import-modal');

const getClientImportModalInstance = () => {
    const modalElement = getClientImportModalElement();

    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
};

const refreshCrudList = async (url) => {
    const target = document.querySelector('[data-crud-list]');

    if (!target || !url) {
        return;
    }

    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to refresh list.');
    }

    target.innerHTML = await response.text();
};

const zoneCodeFromName = (value) => value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toUpperCase()
    .replace(/[^A-Z0-9]+/g, '')
    .slice(0, 20);

const geocodeCacheKey = (query) => `rm-geocode:${query}`;

const bindZoneCodeAutofill = (scope = document) => {
    const zoneNameInput = scope.querySelector('[data-zone-name]');
    const zoneCodeInput = scope.querySelector('[data-zone-code]');

    if (!zoneNameInput || !zoneCodeInput || zoneNameInput.dataset.zoneCodeBound === 'true') {
        return;
    }

    const syncZoneCode = () => {
        zoneCodeInput.value = zoneCodeFromName(zoneNameInput.value);
    };

    zoneNameInput.addEventListener('input', syncZoneCode);
    zoneNameInput.dataset.zoneCodeBound = 'true';
    syncZoneCode();
};

const bindVisitStatusLock = (scope = document) => {
    const resultField = scope.querySelector('[data-visit-result]');
    const statusField = scope.querySelector('[data-visit-status]');
    const resultWrap = scope.querySelector('[data-visit-result-wrap]');
    const statusWrap = scope.querySelector('[data-visit-status-wrap]');

    if (!resultField || !statusField || resultField.dataset.statusLockBound === 'true') {
        return;
    }

    const helpElement = statusField.parentElement?.querySelector('.form-text');
    const baseHelp = helpElement ? helpElement.textContent : '';
    const lockedMessage = statusField.dataset.statusLockedMessage || baseHelp || 'Le statut est bloque.';

    const syncStatusLock = () => {
        const shouldLock = !!resultField.value;
        statusField.disabled = shouldLock;
        statusWrap?.classList.toggle('app-form-field-locked', shouldLock);

        if (helpElement) {
            helpElement.textContent = shouldLock ? lockedMessage : baseHelp;
        }
    };

    const highlightResultField = () => {
        if (!statusField.disabled || !resultWrap) {
            return;
        }

        resultWrap.classList.remove('app-form-field-attention');
        void resultWrap.offsetWidth;
        resultWrap.classList.add('app-form-field-attention');
        resultField.focus();
    };

    resultField.addEventListener('change', syncStatusLock);
    resultWrap?.addEventListener('animationend', () => {
        resultWrap.classList.remove('app-form-field-attention');
    });
    statusWrap?.addEventListener('pointerdown', highlightResultField);
    statusWrap?.addEventListener('click', highlightResultField);
    resultField.dataset.statusLockBound = 'true';
    syncStatusLock();
};

const setFieldValue = (form, fieldName, value) => {
    const directField = form.querySelector(`[name$="[${fieldName}]"]`);
    if (directField && directField.type !== 'radio') {
        directField.value = value ?? '';
        directField.dispatchEvent(new Event('change', { bubbles: true }));
        return;
    }

    const radioFields = form.querySelectorAll(`input[type="radio"][name$="[${fieldName}]"]`);
    if (radioFields.length) {
        radioFields.forEach((radio) => {
            radio.checked = value !== null && value !== undefined && String(radio.value) === String(value);
        });
        return;
    }

    const textareaField = form.querySelector(`[data-visit-prefill-field="${fieldName}"]`);
    if (textareaField) {
        textareaField.value = value ?? '';
    }
};

const applyVisitPrefill = (form, fields) => {
    ['type', 'priority', 'status', 'result', 'objective', 'report', 'nextAction', 'interestLevel'].forEach((fieldName) => {
        setFieldValue(form, fieldName, fields[fieldName] ?? null);
    });

    bindVisitStatusLock(form);
};

const loadVisitPrefill = async (form, clientId) => {
    const template = form.dataset.visitPrefillUrlTemplate;
    const mode = form.dataset.visitPrefillMode || 'new';
    if (!template) {
        return;
    }

    if (!clientId) {
        applyVisitPrefill(form, {
            type: 'prospection',
            priority: 'moyenne',
            status: 'prevue',
            result: null,
            objective: null,
            report: null,
            nextAction: null,
            interestLevel: null,
        });

        return;
    }

    const current = form.dataset.visitPrefillCurrent || '';
    const queryParams = new URLSearchParams();
    if (current) {
        queryParams.set('current', current);
    }
    queryParams.set('mode', mode);
    const query = queryParams.toString();
    const url = `${template.replace('__CLIENT__', clientId)}${query ? `?${query}` : ''}`;
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to load visit prefill.');
    }

    const payload = await response.json();
    applyVisitPrefill(form, payload.fields || {});
};

const bindVisitClientPrefill = (scope = document) => {
    const form = scope.querySelector('[data-visit-prefill-url-template]');
    const clientField = form?.querySelector('[data-visit-client]');

    if (!form || !clientField || clientField.dataset.visitPrefillBound === 'true') {
        return;
    }

    clientField.addEventListener('change', async () => {
        await loadVisitPrefill(form, clientField.value);
    });

    clientField.dataset.visitPrefillBound = 'true';
};

const openCityModal = async (url) => {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to load city form.');
    }

    const modalBody = document.querySelector('[data-city-modal-body]');
    if (!modalBody) {
        return;
    }

    modalBody.innerHTML = await response.text();
    getCityModalInstance()?.show();
};

const openOptionModal = async (url) => {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to load option form.');
    }

    const modalBody = document.querySelector('[data-option-modal-body]');
    if (!modalBody) {
        return;
    }

    modalBody.innerHTML = await response.text();
    getOptionModalInstance()?.show();
};

const openZoneModal = async (url) => {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to load zone form.');
    }

    const modalBody = document.querySelector('[data-zone-modal-body]');
    if (!modalBody) {
        return;
    }

    modalBody.innerHTML = await response.text();
    bindZoneCodeAutofill(modalBody);
    getZoneModalInstance()?.show();
};

const openClientImportModal = async (url) => {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to load import form.');
    }

    const modalBody = document.querySelector('[data-client-import-modal-body]');
    if (!modalBody) {
        return;
    }

    modalBody.innerHTML = await response.text();
    getClientImportModalInstance()?.show();
};

const submitCityForm = async (form) => {
    const formData = new FormData(form);
    const response = await fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to save city.');
    }

    const payload = await response.json();
    if (!payload.success) {
        const modalBody = document.querySelector('[data-city-modal-body]');
        if (modalBody && payload.form) {
            modalBody.innerHTML = payload.form;
        }

        return;
    }

    if (payload.city) {
        const select = document.querySelector('[data-city-select]');
        if (select) {
            const existingOption = Array.from(select.options).find((option) => option.value === String(payload.city.id));
            if (!existingOption) {
                const option = new Option(payload.city.name, payload.city.id, true, true);
                select.add(option);
            } else {
                existingOption.selected = true;
            }
            select.value = String(payload.city.id);
        }
    }

    getCityModalInstance()?.hide();
};

const submitOptionForm = async (form) => {
    const formData = new FormData(form);
    const response = await fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to save option.');
    }

    const payload = await response.json();
    if (!payload.success) {
        const modalBody = document.querySelector('[data-option-modal-body]');
        if (modalBody && payload.form) {
            modalBody.innerHTML = payload.form;
        }

        return;
    }

    if (payload.option?.category) {
        const select = document.querySelector(`[data-option-select="${payload.option.category}"]`);
        if (select) {
            const existingOption = Array.from(select.options).find((option) => option.value === String(payload.option.value));
            if (!existingOption) {
                const option = new Option(payload.option.label, payload.option.value, true, true);
                select.add(option);
            } else {
                existingOption.text = payload.option.label;
                existingOption.selected = true;
            }
            select.value = String(payload.option.value);
        }
    }

    getOptionModalInstance()?.hide();
};

const submitZoneForm = async (form) => {
    const formData = new FormData(form);
    const response = await fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to save zone.');
    }

    const payload = await response.json();
    if (!payload.success) {
        const modalBody = document.querySelector('[data-zone-modal-body]');
        if (modalBody && payload.form) {
            modalBody.innerHTML = payload.form;
            bindZoneCodeAutofill(modalBody);
        }

        return;
    }

    if (payload.zone) {
        const select = document.querySelector('[data-zone-select]');
        if (select) {
            const existingOption = Array.from(select.options).find((option) => option.value === String(payload.zone.id));
            if (!existingOption) {
                const option = new Option(payload.zone.label, payload.zone.id, true, true);
                select.add(option);
            } else {
                existingOption.text = payload.zone.label;
                existingOption.selected = true;
            }
            select.value = String(payload.zone.id);
        }
    }

    getZoneModalInstance()?.hide();
};

const resetClientImportStatus = (form) => {
    const statusPanel = form.querySelector('[data-client-import-status]');
    const counter = form.querySelector('[data-client-import-counter]');
    const meta = form.querySelector('[data-client-import-meta]');
    const progressBar = form.querySelector('[data-client-import-progress-bar]');
    const log = form.querySelector('[data-client-import-log]');
    const created = form.querySelector('[data-client-import-created]');
    const updated = form.querySelector('[data-client-import-updated]');
    const skipped = form.querySelector('[data-client-import-skipped]');

    statusPanel?.classList.remove('d-none');
    if (counter) counter.textContent = '0 / 0';
    if (meta) meta.textContent = 'Analyse du fichier en cours...';
    if (progressBar) {
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        progressBar.setAttribute('aria-valuenow', '0');
        progressBar.classList.add('progress-bar-animated', 'progress-bar-striped');
    }
    if (log) log.innerHTML = '';
    if (created) created.textContent = '0';
    if (updated) updated.textContent = '0';
    if (skipped) skipped.textContent = '0';
};

const appendClientImportLog = (form, entries = []) => {
    const log = form.querySelector('[data-client-import-log]');
    if (!log) {
        return;
    }

    entries.forEach((entry) => {
        const item = document.createElement('div');
        item.className = `app-import-log-item app-import-log-item-${entry.level || 'info'} rounded-4 px-3 py-2`;
        item.textContent = entry.message;
        log.appendChild(item);
    });

    log.scrollTop = log.scrollHeight;
};

const updateClientImportProgress = (form, payload) => {
    const counter = form.querySelector('[data-client-import-counter]');
    const meta = form.querySelector('[data-client-import-meta]');
    const progressBar = form.querySelector('[data-client-import-progress-bar]');
    const created = form.querySelector('[data-client-import-created]');
    const updated = form.querySelector('[data-client-import-updated]');
    const skipped = form.querySelector('[data-client-import-skipped]');
    const total = Number(payload.total || 0);
    const processed = Number(payload.processed || 0);
    const progress = total > 0 ? Math.round((processed / total) * 100) : 0;

    if (counter) {
        counter.textContent = `${processed} / ${total}`;
    }

    if (meta) {
        meta.textContent = payload.done
            ? 'Import termine. La liste clients a ete mise a jour.'
            : `Import en cours... ${processed} ligne(s) traitee(s) sur ${total}.`;
    }

    if (progressBar) {
        progressBar.style.width = `${progress}%`;
        progressBar.textContent = `${progress}%`;
        progressBar.setAttribute('aria-valuenow', String(progress));
        if (payload.done) {
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
        }
    }

    if (created) created.textContent = String(payload.stats?.created ?? 0);
    if (updated) updated.textContent = String(payload.stats?.updated ?? 0);
    if (skipped) skipped.textContent = String(payload.stats?.skipped ?? 0);
};

const processClientImport = async (form, token, batchSize) => {
    const template = form.dataset.clientImportProcessTemplate;
    const refreshUrl = form.dataset.clientImportRefreshUrl;
    const submitButton = form.querySelector('[data-client-import-submit]');
    let offset = 0;
    let done = false;

    while (!done) {
        const response = await fetch(template.replace('__TOKEN__', token), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                offset,
                limit: batchSize,
            }),
        });

        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Import impossible.');
        }

        appendClientImportLog(form, payload.logs || []);
        updateClientImportProgress(form, payload);

        done = !!payload.done;
        offset = Number(payload.nextOffset || offset);
    }

    appendClientImportLog(form, [{
        level: 'success',
        message: 'Import termine. Les prospects sont maintenant disponibles dans la rubrique Clients.',
    }]);

    await refreshCrudList(refreshUrl);

    if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Import termine';
    }
};

const submitClientImportForm = async (form) => {
    resetClientImportStatus(form);

    const submitButton = form.querySelector('[data-client-import-submit]');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Preparation...';
    }

    const response = await fetch(form.action, {
        method: form.method,
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const payload = await response.json();

    if (!response.ok || !payload.success) {
        const modalBody = document.querySelector('[data-client-import-modal-body]');
        if (modalBody && payload.form) {
            modalBody.innerHTML = payload.form;
        }

        if (submitButton) {
            submitButton.disabled = false;
        }

        return;
    }

    appendClientImportLog(form, [{
        level: 'info',
        message: `${payload.total} ligne(s) exploitable(s) detectee(s). Debut de l import...`,
    }]);

    if (submitButton) {
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Import en cours...';
    }

    await processClientImport(form, payload.token, Number(payload.batchSize || 10));
};

const getCurrentPosition = () => new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
        reject(new Error('Geolocation unsupported.'));
        return;
    }

    navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000,
    });
});

const haversineDistance = (from, to) => {
    const earthRadius = 6371;
    const toRadians = (degrees) => degrees * (Math.PI / 180);
    const dLat = toRadians(to.lat - from.lat);
    const dLon = toRadians(to.lng - from.lng);
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(toRadians(from.lat))
        * Math.cos(toRadians(to.lat))
        * Math.sin(dLon / 2) ** 2;

    return 2 * earthRadius * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

const geocodeDestination = async (query) => {
    const cacheKey = geocodeCacheKey(query);
    const cached = window.localStorage.getItem(cacheKey);
    if (cached) {
        return JSON.parse(cached);
    }

    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`;
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to geocode destination.');
    }

    const [result] = await response.json();
    if (!result?.lat || !result?.lon) {
        throw new Error('Destination not found.');
    }

    const coordinates = {
        lat: Number(result.lat),
        lng: Number(result.lon),
    };

    window.localStorage.setItem(cacheKey, JSON.stringify(coordinates));

    return coordinates;
};

const formatDistance = (distance) => `${distance.toFixed(1)} km`;

const computeTourDistances = async ({ sortByDistance = false } = {}) => {
    const rows = Array.from(document.querySelectorAll('[data-tour-stop]'));
    if (!rows.length) {
        return;
    }

    const position = await getCurrentPosition();
    const origin = {
        lat: position.coords.latitude,
        lng: position.coords.longitude,
    };

    for (const row of rows) {
        const badge = row.querySelector('[data-distance-badge]');
        const destinationQuery = row.dataset.destination;

        if (!destinationQuery) {
            if (badge) {
                badge.textContent = 'Adresse incomplete';
                badge.className = 'badge rounded-pill text-bg-secondary';
            }
            row.dataset.distanceValue = '';
            continue;
        }

        try {
            const destination = await geocodeDestination(destinationQuery);
            const distance = haversineDistance(origin, destination);
            row.dataset.distanceValue = String(distance);

            if (badge) {
                badge.textContent = formatDistance(distance);
                badge.className = 'badge rounded-pill text-bg-primary';
            }
        } catch {
            row.dataset.distanceValue = '';
            if (badge) {
                badge.textContent = 'Indisponible';
                badge.className = 'badge rounded-pill text-bg-warning';
            }
        }
    }

    if (!sortByDistance) {
        return;
    }

    const tbody = document.querySelector('[data-tour-table] tbody');
    if (!tbody) {
        return;
    }

    rows
        .sort((left, right) => {
            const leftDistance = Number(left.dataset.distanceValue || Number.MAX_SAFE_INTEGER);
            const rightDistance = Number(right.dataset.distanceValue || Number.MAX_SAFE_INTEGER);

            return leftDistance - rightDistance;
        })
        .forEach((row) => tbody.appendChild(row));
};

const bindLiveSearch = () => {
    document.addEventListener('input', (event) => {
        const input = event.target.closest('[data-list-search-input]');
        if (!input) {
            return;
        }

        const scope = input.closest('[data-list-search-scope]');
        const selector = input.dataset.searchTarget || 'tbody tr';
        const query = input.value.trim().toLowerCase();

        scope?.querySelectorAll(selector).forEach((item) => {
            const haystack = item.textContent?.toLowerCase() ?? '';
            item.classList.toggle('d-none', query !== '' && !haystack.includes(query));
        });
    });
};

const bindFlashAlerts = () => {
    document.querySelectorAll('[data-flash-alert]').forEach((alertElement) => {
        if (alertElement.dataset.flashBound === 'true') {
            return;
        }

        window.setTimeout(() => {
            bootstrap.Alert.getOrCreateInstance(alertElement).close();
        }, 6000);

        alertElement.dataset.flashBound = 'true';
    });
};

const parseMoney = (value) => {
    const normalized = String(value ?? '')
        .replace(/\s/g, '')
        .replace(',', '.')
        .replace(/[^0-9.-]/g, '');

    const parsed = Number.parseFloat(normalized);

    return Number.isFinite(parsed) ? parsed : 0;
};

const formatMoney = (value) => `${new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
}).format(value)} MAD`;

const syncOfferItemRow = (row) => {
    const productField = row.querySelector('[data-offer-item-product]');
    const unitPriceField = row.querySelector('[data-offer-item-unit-price]');
    const quantityField = row.querySelector('[data-offer-item-quantity]');

    if (!productField || !unitPriceField || !quantityField) {
        return 0;
    }

    const selectedOption = productField.options?.[productField.selectedIndex];
    const selectedPrice = selectedOption?.dataset.salePrice;

    if (selectedPrice && (unitPriceField.value === '' || parseMoney(unitPriceField.value) <= 0)) {
        unitPriceField.value = selectedPrice;
    }

    const quantity = Math.max(1, Number.parseInt(quantityField.value || '1', 10) || 1);
    quantityField.value = String(quantity);

    return quantity * parseMoney(unitPriceField.value);
};

const updateOfferTotal = (scope = document) => {
    const container = scope.querySelector('[data-offer-items]');
    const totalDisplay = scope.querySelector('[data-offer-total-display]');

    if (!container || !totalDisplay) {
        return;
    }

    let total = 0;
    container.querySelectorAll('[data-offer-item-row]').forEach((row) => {
        total += syncOfferItemRow(row);
    });

    totalDisplay.textContent = formatMoney(total);
    const amountField = scope.querySelector('[name$="[amount]"]');
    if (amountField) {
        amountField.value = total.toFixed(2);
    }
};

const bindOfferItems = (scope = document) => {
    const container = scope.querySelector('[data-offer-items]');
    if (!container || container.dataset.offerItemsBound === 'true') {
        updateOfferTotal(scope);
        return;
    }

    const toggleEmptyState = () => {
        const emptyState = container.querySelector('[data-offer-items-empty]');
        const hasRows = container.querySelector('[data-offer-item-row]');
        if (emptyState) {
            emptyState.classList.toggle('d-none', !!hasRows);
        }
    };

    const addItemRow = () => {
        const prototype = container.dataset.offerItemsPrototype;
        if (!prototype) {
            return;
        }

        const index = Number.parseInt(container.dataset.offerItemsIndex || '0', 10);
        const html = prototype.replace(/__name__/g, String(index));
        container.dataset.offerItemsIndex = String(index + 1);
        container.insertAdjacentHTML('beforeend', html);
        toggleEmptyState();
        updateOfferTotal(scope);
    };

    scope.querySelectorAll('[data-offer-item-add]').forEach((button) => {
        if (button.dataset.offerItemAddBound === 'true') {
            return;
        }

        button.addEventListener('click', () => addItemRow());
        button.dataset.offerItemAddBound = 'true';
    });

    container.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-offer-item-remove]');
        if (!removeButton) {
            return;
        }

        event.preventDefault();
        removeButton.closest('[data-offer-item-row]')?.remove();
        toggleEmptyState();
        updateOfferTotal(scope);
    });

    container.addEventListener('change', (event) => {
        if (event.target.closest('[data-offer-item-product], [data-offer-item-quantity], [data-offer-item-unit-price]')) {
            updateOfferTotal(scope);
        }
    });

    container.dataset.offerItemsBound = 'true';
    toggleEmptyState();
    updateOfferTotal(scope);
};

const openCrudModal = async (url) => {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to load form.');
    }

    const modalBody = document.querySelector('[data-crud-modal-body]');
    if (!modalBody) {
        return;
    }

    modalBody.innerHTML = await response.text();
    bindZoneCodeAutofill(modalBody);
    bindVisitStatusLock(modalBody);
    bindVisitClientPrefill(modalBody);
    bindOfferItems(modalBody);
    getModalInstance()?.show();
};

const submitCrudForm = async (form) => {
    const formData = new FormData(form);
    const disabledStatusField = form.querySelector('[data-visit-status]:disabled');

    if (disabledStatusField?.name && !formData.has(disabledStatusField.name)) {
        formData.append(disabledStatusField.name, disabledStatusField.value ?? '');
    }

    const response = await fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to save form.');
    }

    const payload = await response.json();
    if (!payload.success) {
        const modalBody = document.querySelector('[data-crud-modal-body]');
        if (modalBody && payload.form) {
            modalBody.innerHTML = payload.form;
            bindZoneCodeAutofill(modalBody);
            bindVisitStatusLock(modalBody);
            bindVisitClientPrefill(modalBody);
            bindOfferItems(modalBody);
        }

        return;
    }

    getModalInstance()?.hide();
    await refreshCrudList(form.dataset.crudRefreshUrl);
};

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-crud-modal-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    await openCrudModal(trigger.dataset.crudModalUrl);
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-city-modal-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    await openCityModal(trigger.dataset.cityModalUrl);
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-option-modal-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    await openOptionModal(trigger.dataset.optionModalUrl);
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-zone-modal-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    await openZoneModal(trigger.dataset.zoneModalUrl);
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-client-import-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    await openClientImportModal(trigger.dataset.clientImportUrl);
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-crud-ajax-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    await submitCrudForm(form);
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-city-ajax-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    await submitCityForm(form);
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-option-ajax-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    await submitOptionForm(form);
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-zone-ajax-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    await submitZoneForm(form);
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-client-import-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    await submitClientImportForm(form);
});

document.addEventListener('click', async (event) => {
    const locateButton = event.target.closest('[data-tour-locate]');
    if (locateButton) {
        event.preventDefault();
        try {
            await getCurrentPosition();
            locateButton.classList.remove('btn-outline-secondary');
            locateButton.classList.add('btn-success');
            locateButton.innerHTML = '<i class="bi bi-check-circle me-2"></i>Position detectee';
        } catch {
            locateButton.classList.remove('btn-outline-secondary');
            locateButton.classList.add('btn-danger');
            locateButton.innerHTML = '<i class="bi bi-x-circle me-2"></i>Position indisponible';
        }

        return;
    }

    const distanceButton = event.target.closest('[data-tour-distance]');
    if (distanceButton) {
        event.preventDefault();
        distanceButton.disabled = true;
        try {
            await computeTourDistances();
            distanceButton.classList.remove('btn-outline-primary');
            distanceButton.classList.add('btn-success');
            distanceButton.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Distances calculees';
        } catch {
            distanceButton.classList.remove('btn-outline-primary');
            distanceButton.classList.add('btn-danger');
            distanceButton.innerHTML = '<i class="bi bi-x-circle me-2"></i>Calcul impossible';
        } finally {
            distanceButton.disabled = false;
        }

        return;
    }

    const optimizeButton = event.target.closest('[data-tour-optimize]');
    if (optimizeButton) {
        event.preventDefault();
        optimizeButton.disabled = true;
        try {
            await computeTourDistances({ sortByDistance: true });
            optimizeButton.classList.remove('btn-primary');
            optimizeButton.classList.add('btn-success');
            optimizeButton.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Ordre optimise';
        } catch {
            optimizeButton.classList.remove('btn-primary');
            optimizeButton.classList.add('btn-danger');
            optimizeButton.innerHTML = '<i class="bi bi-x-circle me-2"></i>Optimisation indisponible';
        } finally {
            optimizeButton.disabled = false;
        }
    }
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-crud-delete-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();

    if (!window.confirm(trigger.dataset.crudConfirm ?? 'Confirmer la suppression ?')) {
        return;
    }

    const response = await fetch(trigger.dataset.crudDeleteUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
        throw new Error(payload?.message || 'Unable to delete item.');
    }

    await refreshCrudList(trigger.dataset.crudRefreshUrl);
});

document.addEventListener('DOMContentLoaded', () => {
    bindZoneCodeAutofill(document);
    bindVisitStatusLock(document);
    bindVisitClientPrefill(document);
    bindLiveSearch();
    bindOfferItems(document);
    bindFlashAlerts();
});
