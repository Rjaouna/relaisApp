
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

let tourLiveRefreshIntervalId = null;
let dashboardPendingClosureIntervalId = null;
let clientMapRefreshTimeoutId = null;
const VISIT_RESULT_APPOINTMENT_BOOKED = 'rdv_pris';
let chartJsLoaderPromise = null;
let leafletLoaderPromise = null;
let clientMapState = null;

const triggerHapticFeedback = () => {
    if (typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function') {
        navigator.vibrate(12);
    }
};

const setTriggerBusy = (trigger, label = null) => {
    if (!trigger || trigger.dataset.busy === 'true') {
        return;
    }

    triggerHapticFeedback();
    trigger.dataset.busy = 'true';
    trigger.dataset.originalHtml = trigger.innerHTML;
    trigger.classList.add('is-busy');

    if ('disabled' in trigger) {
        trigger.disabled = true;
    } else {
        trigger.setAttribute('aria-disabled', 'true');
        trigger.style.pointerEvents = 'none';
    }

    const busyLabel = label || trigger.dataset.loadingLabel || 'Traitement...';
    trigger.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${busyLabel}`;
};

const clearTriggerBusy = (trigger) => {
    if (!trigger || trigger.dataset.busy !== 'true') {
        return;
    }

    trigger.classList.remove('is-busy');
    if (trigger.dataset.originalHtml) {
        trigger.innerHTML = trigger.dataset.originalHtml;
    }

    delete trigger.dataset.originalHtml;
    delete trigger.dataset.busy;

    if ('disabled' in trigger) {
        trigger.disabled = false;
    } else {
        trigger.removeAttribute('aria-disabled');
        trigger.style.pointerEvents = '';
    }
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

const ensureChartJsLoaded = async () => {
    if (typeof window !== 'undefined' && typeof window.Chart !== 'undefined') {
        return window.Chart;
    }

    if (chartJsLoaderPromise) {
        return chartJsLoaderPromise;
    }

    chartJsLoaderPromise = new Promise((resolve, reject) => {
        const existingScript = document.querySelector('script[data-chartjs-loader]');
        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(window.Chart), { once: true });
            existingScript.addEventListener('error', () => reject(new Error('Unable to load Chart.js.')), { once: true });

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js';
        script.async = true;
        script.dataset.chartjsLoader = 'true';
        script.addEventListener('load', () => resolve(window.Chart), { once: true });
        script.addEventListener('error', () => reject(new Error('Unable to load Chart.js.')), { once: true });
        document.head.appendChild(script);
    });

    return chartJsLoaderPromise;
};

const ensureLeafletLoaded = async () => {
    if (typeof window !== 'undefined' && typeof window.L !== 'undefined' && typeof window.L.markerClusterGroup === 'function') {
        return window.L;
    }

    if (leafletLoaderPromise) {
        return leafletLoaderPromise;
    }

    const appendStylesheet = (href, marker) => {
        if (document.querySelector(`link[${marker}]`)) {
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.setAttribute(marker, 'true');
        document.head.appendChild(link);
    };

    leafletLoaderPromise = new Promise((resolve, reject) => {
        appendStylesheet('https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css', 'data-leaflet-style');
        appendStylesheet('https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.css', 'data-markercluster-style');
        appendStylesheet('https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css', 'data-markercluster-default-style');

        const loadScript = (src, marker) => new Promise((resolveScript, rejectScript) => {
            const existing = document.querySelector(`script[${marker}]`);
            if (existing) {
                existing.addEventListener('load', resolveScript, { once: true });
                existing.addEventListener('error', () => rejectScript(new Error(`Unable to load ${src}`)), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.setAttribute(marker, 'true');
            script.addEventListener('load', resolveScript, { once: true });
            script.addEventListener('error', () => rejectScript(new Error(`Unable to load ${src}`)), { once: true });
            document.head.appendChild(script);
        });

        loadScript('https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js', 'data-leaflet-script')
            .then(() => loadScript('https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', 'data-markercluster-script'))
            .then(() => resolve(window.L))
            .catch(reject);
    });

    return leafletLoaderPromise;
};

const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const parseJsonDataset = (value, fallback = {}) => {
    try {
        return value ? JSON.parse(value) : fallback;
    } catch {
        return fallback;
    }
};

const setVisitChartEmptyState = (canvasId, hasData) => {
    const canvas = document.getElementById(canvasId);
    const emptyState = document.querySelector(`[data-visit-chart-empty="${canvasId}"]`);

    if (canvas) {
        canvas.classList.toggle('d-none', !hasData);
    }

    if (emptyState) {
        emptyState.classList.toggle('d-none', hasData);
    }
};

const destroyChartIfExists = (canvasId) => {
    if (typeof window === 'undefined' || typeof window.Chart === 'undefined') {
        return null;
    }

    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        return null;
    }

    const existingChart = window.Chart.getChart(canvas);
    if (existingChart) {
        existingChart.destroy();
    }

    return canvas;
};

const bindVisitInsightsCharts = async () => {
    const container = document.querySelector('[data-visit-insights]');
    if (!container) {
        return;
    }

    await ensureChartJsLoaded();

    const chartPalette = {
        primary: '#4bc2c4',
        secondary: '#2d6464',
        dark: '#111827',
        soft: '#bfe9ea',
        accent: '#88d7d8',
        warning: '#f59e0b',
    };

    const clientsData = parseJsonDataset(container.dataset.visitClients, { labels: [], values: [] });
    const citiesData = parseJsonDataset(container.dataset.visitCities, { labels: [], values: [] });
    const resultsData = parseJsonDataset(container.dataset.visitResults, { labels: [], values: [] });

    const renderChart = (canvasId, type, dataset, options = {}) => {
        const labels = Array.isArray(dataset.labels) ? dataset.labels : [];
        const values = Array.isArray(dataset.values) ? dataset.values : [];
        const hasData = labels.length > 0 && values.some((value) => Number(value) > 0);

        setVisitChartEmptyState(canvasId, hasData);
        if (!hasData) {
            destroyChartIfExists(canvasId);
            return;
        }

        const canvas = destroyChartIfExists(canvasId);
        if (!canvas) {
            return;
        }

        new window.Chart(canvas, {
            type,
            data: {
                labels,
                datasets: [dataset.dataset],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                ...options,
            },
        });
    };

    renderChart('visitClientsChart', 'bar', {
        labels: clientsData.labels,
        values: clientsData.values,
        dataset: {
            data: clientsData.values,
            backgroundColor: chartPalette.primary,
            borderRadius: 12,
        },
    }, {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    });

    renderChart('visitCitiesChart', 'doughnut', {
        labels: citiesData.labels,
        values: citiesData.values,
        dataset: {
            data: citiesData.values,
            backgroundColor: [chartPalette.primary, chartPalette.secondary, chartPalette.dark, chartPalette.accent, chartPalette.soft, chartPalette.warning],
        },
    }, {
        plugins: { legend: { position: 'bottom' } },
    });

    renderChart('visitResultsChart', 'polarArea', {
        labels: resultsData.labels,
        values: resultsData.values,
        dataset: {
            data: resultsData.values,
            backgroundColor: [chartPalette.primary, chartPalette.secondary, chartPalette.dark, chartPalette.accent, chartPalette.soft, chartPalette.warning],
        },
    }, {
        plugins: { legend: { position: 'bottom' } },
    });
};

const getClientMapToneClass = (tone) => `app-client-map-marker--${tone || 'primary'}`;

const buildClientMapMarkerIcon = (tone) => new window.L.DivIcon({
    className: 'app-client-map-marker-wrapper',
    html: `<span class="app-client-map-marker ${getClientMapToneClass(tone)}"></span>`,
    iconSize: [18, 18],
    iconAnchor: [9, 9],
});

const buildClientMapPopupHtml = (marker) => `
    <div class="app-client-map-popup">
        <div class="fw-semibold mb-1">${escapeHtml(marker.popup.title)}</div>
        <div class="small text-body-secondary mb-2">${escapeHtml(marker.popup.code)}</div>
        <div class="small"><strong>Ville :</strong> ${escapeHtml(marker.popup.city || 'Non renseignee')}</div>
        <div class="small"><strong>Zone :</strong> ${escapeHtml(marker.popup.zone || 'Non renseignee')}</div>
        <div class="small"><strong>Commercial :</strong> ${escapeHtml(marker.popup.commercial || 'Non affecte')}</div>
        <div class="small"><strong>Visite :</strong> ${escapeHtml(marker.popup.visit_status || 'Aucune')}</div>
        <div class="small"><strong>Tournee :</strong> ${escapeHtml(marker.popup.tour_status || 'Aucune')}</div>
    </div>
`;

const buildClientMapRecentVisitsHtml = (client) => {
    const visits = Array.isArray(client.recent_visits) ? client.recent_visits : [];
    if (!visits.length) {
        return '<div class="small text-body-secondary">Aucune visite exploitable pour ce client.</div>';
    }

    return `
        <div class="d-flex flex-column gap-2">
            ${visits.map((visit) => `
                <div class="app-client-map-visit-item">
                    <div class="fw-semibold small">${escapeHtml(visit.date || 'Date non definie')}</div>
                    <div class="small text-body-secondary">${escapeHtml(visit.status_label || 'Statut non renseigne')}</div>
                    <div class="small">${escapeHtml(visit.result_label || 'Resultat non renseigne')}</div>
                </div>
            `).join('')}
        </div>
    `;
};

const buildClientMapSelectedHtml = (client) => {
    const canPlanVisit = !!client.actions?.plan_visit_url;
    const hasTour = !!client.actions?.tour_url;
    const tourLabel = hasTour ? 'Voir la tournee' : 'Ajouter a une tournee';
    const tourUrl = hasTour ? client.actions.tour_url : client.actions?.tour_prepare_url;

    return `
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <div class="app-section-kicker text-secondary mb-2">${escapeHtml(client.code || '')}</div>
                <h3 class="h5 mb-1">${escapeHtml(client.name || 'Client')}</h3>
                <div class="small text-body-secondary">${escapeHtml(client.city || 'Ville non renseignee')} · ${escapeHtml(client.zone || 'Zone non renseignee')}</div>
            </div>
            <span class="badge rounded-pill text-bg-light">${escapeHtml(client.status_label || 'Client')}</span>
        </div>

        <div class="app-client-map-detail-grid mb-3">
            <div>
                <div class="small text-uppercase text-body-secondary mb-1">Commercial</div>
                <div class="fw-semibold">${escapeHtml(client.commercial || 'Non affecte')}</div>
            </div>
            <div>
                <div class="small text-uppercase text-body-secondary mb-1">Telephone</div>
                <div class="fw-semibold">${escapeHtml(client.phone || 'Non renseigne')}</div>
            </div>
            <div>
                <div class="small text-uppercase text-body-secondary mb-1">Visite</div>
                <div class="fw-semibold">${escapeHtml(client.visit_status_label || 'Aucune')}</div>
            </div>
            <div>
                <div class="small text-uppercase text-body-secondary mb-1">Tournee</div>
                <div class="fw-semibold">${escapeHtml(client.tour_status_label || 'Aucune')}</div>
            </div>
        </div>

        <div class="small text-body-secondary mb-3">${escapeHtml(client.address || 'Adresse non renseignee')}</div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="${escapeHtml(client.actions?.show_url || '#')}" class="btn btn-outline-secondary btn-sm rounded-pill">Voir la fiche</a>
            <button type="button" class="btn btn-primary btn-sm rounded-pill" ${canPlanVisit ? `data-client-map-plan-visit="${escapeHtml(client.actions.plan_visit_url)}"` : 'disabled'}>${canPlanVisit ? 'Creer une visite' : 'Visite deja preparee'}</button>
            ${tourUrl ? `<a href="${escapeHtml(tourUrl)}" class="btn btn-outline-primary btn-sm rounded-pill">${escapeHtml(tourLabel)}</a>` : ''}
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" data-client-map-show-visits>Voir les visites</button>
        </div>

        <div class="pt-3 border-top" data-client-map-visit-history>
            <div class="app-section-kicker text-secondary mb-2">Historique recent</div>
            ${buildClientMapRecentVisitsHtml(client)}
        </div>
    `;
};

const buildClientMapListItemHtml = (client) => `
    <button type="button" class="app-client-map-list-item" data-client-map-focus-client="${escapeHtml(client.id)}">
        <span class="app-client-map-list-tone ${getClientMapToneClass(client.tone)}"></span>
        <span class="d-flex flex-column text-start">
            <span class="fw-semibold">${escapeHtml(client.name || 'Client')}</span>
            <span class="small text-body-secondary">${escapeHtml(client.city || 'Ville non renseignee')} · ${escapeHtml(client.commercial || 'Non affecte')}</span>
        </span>
    </button>
`;

const buildClientMapUnlocalizedItemHtml = (client) => `
    <div class="app-client-map-unlocalized-item">
        <div class="fw-semibold">${escapeHtml(client.name || 'Client')}</div>
        <div class="small text-body-secondary">${escapeHtml(client.city || 'Ville non renseignee')} · ${escapeHtml(client.zone || 'Zone non renseignee')}</div>
        <a href="${escapeHtml(client.actions?.show_url || '#')}" class="small fw-semibold">Completer la fiche</a>
    </div>
`;

const updateClientMapSlider = () => {
    if (!clientMapState?.container) {
        return;
    }

    const slider = clientMapState.container.querySelector('[data-client-map-results-slider]');
    const track = clientMapState.container.querySelector('[data-client-map-results]');
    const windowElement = clientMapState.container.querySelector('.app-client-map-results-window');
    const prevButton = clientMapState.container.querySelector('[data-client-map-slider-prev]');
    const nextButton = clientMapState.container.querySelector('[data-client-map-slider-next]');

    if (!slider || !track || !windowElement || !prevButton || !nextButton) {
        return;
    }

    const items = Array.from(track.querySelectorAll('[data-client-map-focus-client]'));
    slider.classList.toggle('app-client-map-results-slider-inactive', items.length <= 3);

    const itemHeight = items[0]?.offsetHeight ?? 0;
    const gap = 10;
    const step = itemHeight > 0 ? itemHeight + gap : 0;
    const maxScroll = Math.max(0, windowElement.scrollHeight - windowElement.clientHeight);

    if (clientMapState.pendingSliderReset) {
        windowElement.scrollTop = 0;
        clientMapState.pendingSliderReset = false;
    }

    prevButton.disabled = windowElement.scrollTop <= 0;
    nextButton.disabled = windowElement.scrollTop >= (maxScroll - 4);
    clientMapState.sliderStep = step;
};

const renderClientMapSidebar = (payload) => {
    if (!clientMapState?.container) {
        return;
    }

    const selectedWrap = clientMapState.container.querySelector('[data-client-map-selected]');
    const resultsWrap = clientMapState.container.querySelector('[data-client-map-results]');
    const unlocalizedWrap = clientMapState.container.querySelector('[data-client-map-unlocalized]');
    const countWrap = clientMapState.container.querySelector('[data-client-map-count]');
    const visibleCountWrap = clientMapState.container.querySelector('[data-client-map-visible-count]');
    const unlocalizedCountWrap = clientMapState.container.querySelector('[data-client-map-unlocalized-count]');
    const unlocalizedBadge = clientMapState.container.querySelector('[data-client-map-unlocalized-badge]');
    const geocodeButton = clientMapState.container.querySelector('[data-client-map-geocode-url]');

    const clients = Array.isArray(payload.clients) ? payload.clients : [];
    const nonLocalizableClients = Array.isArray(payload.non_localizable_clients) ? payload.non_localizable_clients : [];

    if (countWrap) {
        countWrap.textContent = String(payload.summary?.localized ?? clients.length);
    }
    if (visibleCountWrap) {
        visibleCountWrap.textContent = String(clients.length);
    }
    if (unlocalizedCountWrap) {
        unlocalizedCountWrap.textContent = String(nonLocalizableClients.length);
    }
    if (unlocalizedBadge) {
        unlocalizedBadge.textContent = String(nonLocalizableClients.length);
    }
    if (geocodeButton) {
        geocodeButton.disabled = Number(payload.summary?.missing_coordinates || 0) < 1;
    }

    resultsWrap.innerHTML = clients.length
        ? clients.map((client) => buildClientMapListItemHtml(client)).join('')
        : '<div class="small text-body-secondary">Aucun client ne correspond aux filtres actuels.</div>';

    unlocalizedWrap.innerHTML = nonLocalizableClients.length
        ? nonLocalizableClients.map((client) => buildClientMapUnlocalizedItemHtml(client)).join('')
        : '<div class="small text-body-secondary">Tous les clients filtres sont localisables.</div>';

    const selectedClientId = clientMapState.selectedClientId;
    const selectedClient = clients.find((client) => String(client.id) === String(selectedClientId)) ?? clients[0] ?? null;
    if (selectedClient) {
        selectedWrap.innerHTML = buildClientMapSelectedHtml(selectedClient);
        clientMapState.selectedClientId = selectedClient.id;
    } else {
        selectedWrap.innerHTML = '<div class="small text-body-secondary">Clique sur un marqueur ou une ligne pour consulter le detail du client.</div>';
        clientMapState.selectedClientId = null;
    }

    updateClientMapSlider();
};

const renderClientMapMarkers = (payload, keepBounds = false) => {
    if (!clientMapState?.map || !clientMapState?.clusterLayer) {
        return;
    }

    const markers = Array.isArray(payload.map?.markers) ? payload.map.markers : [];
    clientMapState.clusterLayer.clearLayers();
    clientMapState.markerIndex = {};

    markers.forEach((marker) => {
        if (!Number.isFinite(marker.lat) || !Number.isFinite(marker.lng)) {
            return;
        }

        const leafletMarker = window.L.marker([marker.lat, marker.lng], {
            icon: buildClientMapMarkerIcon(marker.tone),
        });

        leafletMarker.bindPopup(buildClientMapPopupHtml(marker));
        leafletMarker.on('click', () => {
            clientMapState.selectedClientId = marker.client.id;
            renderClientMapSidebar(payload);
        });

        clientMapState.clusterLayer.addLayer(leafletMarker);
        clientMapState.markerIndex[String(marker.client.id)] = leafletMarker;
    });

    const bounds = clientMapState.clusterLayer.getBounds();
    if (bounds.isValid()) {
        if (keepBounds && clientMapState.map.getBounds().isValid()) {
            clientMapState.map.fitBounds(bounds, {
                padding: [24, 24],
                maxZoom: 10,
            });
        } else {
            clientMapState.map.setView(payload.map?.center || [31.85, -7.10], payload.map?.zoom || 6);
        }
    } else {
        clientMapState.map.setView(payload.map?.center || [31.85, -7.10], payload.map?.zoom || 6);
    }
};

const refreshClientMapData = async ({ keepBounds = true } = {}) => {
    if (!clientMapState?.container || !clientMapState?.filtersForm) {
        return;
    }

    const loading = clientMapState.container.querySelector('[data-client-map-loading]');
    loading?.classList.remove('d-none');

    const searchParams = new URLSearchParams(new FormData(clientMapState.filtersForm));
    const response = await fetch(`${clientMapState.dataUrl}?${searchParams.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        loading?.classList.add('d-none');
        throw new Error('Unable to refresh client map.');
    }

    const payload = await response.json();
    clientMapState.payload = payload;
    renderClientMapSidebar(payload);
    renderClientMapMarkers(payload, keepBounds);
    loading?.classList.add('d-none');
};

const bindClientMapPage = async () => {
    const container = document.querySelector('[data-client-map-page]');
    if (!container) {
        if (clientMapState?.map) {
            clientMapState.map.remove();
        }
        clientMapState = null;
        return;
    }

    const mapCanvas = container.querySelector('[data-client-map-canvas]');
    const filtersForm = container.querySelector('[data-client-map-filters]');
    if (!mapCanvas || !filtersForm) {
        return;
    }

    await ensureLeafletLoaded();

    if (clientMapState?.container && clientMapState.container !== container && clientMapState.map) {
        clientMapState.map.remove();
        clientMapState = null;
    }

    const initialPayload = parseJsonDataset(container.dataset.clientMapInitial, null);
    const map = clientMapState?.map ?? window.L.map(mapCanvas, {
        zoomControl: true,
    }).setView([31.85, -7.10], 6);

    if (!clientMapState?.tileLayer) {
        const tileLayer = window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        });
        tileLayer.addTo(map);
        clientMapState = {
            ...clientMapState,
            tileLayer,
        };
    }

    const clusterLayer = clientMapState?.clusterLayer ?? window.L.markerClusterGroup({
        showCoverageOnHover: false,
        spiderfyOnMaxZoom: true,
        maxClusterRadius: 45,
    });

    if (!clientMapState?.clusterLayer) {
        map.addLayer(clusterLayer);
    }

    clientMapState = {
        ...clientMapState,
        container,
        filtersForm,
        dataUrl: container.dataset.clientMapDataUrl,
        map,
        clusterLayer,
        markerIndex: clientMapState?.markerIndex || {},
        selectedClientId: clientMapState?.selectedClientId || null,
        sliderStep: clientMapState?.sliderStep || 0,
        pendingSliderReset: true,
        payload: initialPayload,
    };

    if (initialPayload) {
        renderClientMapSidebar(initialPayload);
        renderClientMapMarkers(initialPayload, false);
    } else {
        await refreshClientMapData({ keepBounds: false });
    }

    window.setTimeout(() => {
        clientMapState?.map?.invalidateSize();
    }, 80);

    if (filtersForm.dataset.clientMapBound !== 'true') {
        const triggerRefresh = () => {
            if (clientMapRefreshTimeoutId !== null) {
                window.clearTimeout(clientMapRefreshTimeoutId);
            }

            clientMapRefreshTimeoutId = window.setTimeout(() => {
                void refreshClientMapData();
            }, 220);
        };

        filtersForm.addEventListener('change', triggerRefresh);
        filtersForm.addEventListener('input', triggerRefresh);
        filtersForm.dataset.clientMapBound = 'true';
    }
};

const extractTourRowIds = (scope) => Array.from(
    scope.querySelectorAll('[data-tour-row-id]'),
    (row) => row.dataset.tourRowId,
);

const refreshTourListSilently = async (container) => {
    const url = container?.dataset.tourLiveRefreshUrl;
    const target = container?.querySelector('[data-crud-list]');

    if (!url || !target) {
        return;
    }

    const previousIds = extractTourRowIds(target);
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        return;
    }

    const html = await response.text();
    target.innerHTML = html;

    const nextIds = extractTourRowIds(target);
    const hasNewTour = nextIds.some((id) => !previousIds.includes(id));

    if (hasNewTour && typeof window !== 'undefined') {
        const title = document.title;
        document.title = `Nouvelle tournee - ${title}`;
        window.setTimeout(() => {
            document.title = title;
        }, 4000);
    }
};

const stopTourLiveRefresh = () => {
    if (tourLiveRefreshIntervalId !== null) {
        window.clearInterval(tourLiveRefreshIntervalId);
        tourLiveRefreshIntervalId = null;
    }
};

const stopDashboardPendingClosureRefresh = () => {
    if (dashboardPendingClosureIntervalId !== null) {
        window.clearInterval(dashboardPendingClosureIntervalId);
        dashboardPendingClosureIntervalId = null;
    }
};

const bindTourLiveRefresh = () => {
    stopTourLiveRefresh();

    const container = document.querySelector('[data-tour-live-refresh-url]');
    if (!container) {
        return;
    }

    const interval = Number.parseInt(container.dataset.tourLiveRefreshInterval || '15000', 10);
    if (!Number.isFinite(interval) || interval < 5000) {
        return;
    }

    tourLiveRefreshIntervalId = window.setInterval(async () => {
        if (document.hidden) {
            return;
        }

        const modalOpen = document.querySelector('.modal.show');
        if (modalOpen) {
            return;
        }

        await refreshTourListSilently(container);
        await refreshTourCommercialAlerts();
    }, interval);
};

const refreshDashboardPendingClosures = async () => {
    const container = document.querySelector('[data-dashboard-pending-closures-url]');
    const url = container?.dataset.dashboardPendingClosuresUrl;

    if (!container || !url) {
        return;
    }

    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        return;
    }

    container.innerHTML = await response.text();
};

const refreshTourCommercialAlerts = async () => {
    const container = document.querySelector('[data-tour-commercial-alerts-url]');
    const url = container?.dataset.tourCommercialAlertsUrl;

    if (!container || !url) {
        return;
    }

    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        return;
    }

    container.innerHTML = await response.text();
};

const bindDashboardPendingClosureRefresh = () => {
    stopDashboardPendingClosureRefresh();

    const container = document.querySelector('[data-dashboard-pending-closures-url]');
    if (!container) {
        return;
    }

    dashboardPendingClosureIntervalId = window.setInterval(async () => {
        if (document.hidden) {
            return;
        }

        await refreshDashboardPendingClosures();
    }, 10000);
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

const formatAppointmentPreview = (value) => {
    if (!value) {
        return 'Aucune date definie.';
    }

    const [datePart, timePart = ''] = String(value).split('T');
    const normalizedDate = datePart.split('-').reverse().join('/');

    return `${normalizedDate} ${timePart.slice(0, 5)}`.trim();
};

const bindAppointmentScheduling = (scope = document) => {
    const resultField = scope.querySelector('[data-visit-result]');
    if (!resultField || resultField.dataset.appointmentBound === 'true') {
        return;
    }

    const inlineWrap = scope.querySelector('[data-appointment-inline-wrap]');
    const inlineField = scope.querySelector('[data-appointment-inline]');
    const sourceField = scope.querySelector('[data-appointment-modal-source]');
    const summary = scope.querySelector('[data-appointment-summary]');
    const summaryText = scope.querySelector('[data-appointment-summary-text]');
    const openButton = scope.querySelector('[data-appointment-open-modal]');
    const form = scope.querySelector('[data-appointment-modal-form]') || scope.closest('form');
    const modalElement = document.getElementById('appointmentSchedulingModal');
    const modalInput = modalElement?.querySelector('[data-appointment-modal-input]');
    const confirmButton = modalElement?.querySelector('[data-appointment-modal-confirm]');
    const modalInstance = modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;

    const targetField = sourceField || inlineField;

    const syncState = () => {
        const isAppointmentResult = resultField.value === VISIT_RESULT_APPOINTMENT_BOOKED;

        inlineWrap?.classList.toggle('d-none', !isAppointmentResult);
        summary?.classList.toggle('d-none', !isAppointmentResult);

        if (!isAppointmentResult && targetField) {
            targetField.value = '';
        }

        if (!isAppointmentResult && modalInput) {
            modalInput.value = '';
            modalInput.classList.remove('is-invalid');
        }

        if (summaryText) {
            summaryText.textContent = formatAppointmentPreview(targetField?.value);
        }

        if (openButton) {
            openButton.innerHTML = targetField?.value
                ? '<i class="bi bi-calendar-event me-2"></i>Modifier le RDV'
                : '<i class="bi bi-calendar-event me-2"></i>Programmer le RDV';
        }
    };

    const openSchedulingModal = () => {
        if (!modalInstance || !targetField || resultField.value !== VISIT_RESULT_APPOINTMENT_BOOKED) {
            return;
        }

        modalInput.value = targetField.value ?? '';
        modalInput.classList.remove('is-invalid');
        modalInstance.show();
        window.setTimeout(() => modalInput.focus(), 150);
    };

    resultField.addEventListener('change', () => {
        const hadValue = !!targetField?.value;
        syncState();

        if (!hadValue) {
            openSchedulingModal();
        }
    });

    openButton?.addEventListener('click', () => {
        openSchedulingModal();
    });

    confirmButton?.addEventListener('click', () => {
        if (!targetField || !modalInput) {
            return;
        }

        if (!modalInput.value) {
            modalInput.classList.add('is-invalid');
            modalInput.focus();

            return;
        }

        targetField.value = modalInput.value;
        targetField.dispatchEvent(new Event('change', { bubbles: true }));
        syncState();
        modalInstance?.hide();
    });

    if (form && form.dataset.appointmentSubmitBound !== 'true') {
        form.addEventListener('submit', (event) => {
            if (resultField.value !== VISIT_RESULT_APPOINTMENT_BOOKED) {
                return;
            }

            if (targetField?.value) {
                return;
            }

            event.preventDefault();
            openSchedulingModal();
            modalInput?.classList.add('is-invalid');
        });

        form.dataset.appointmentSubmitBound = 'true';
    }

    resultField.dataset.appointmentBound = 'true';
    syncState();
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
    ['type', 'priority', 'status', 'result', 'appointmentScheduledAt', 'objective', 'report', 'nextAction', 'interestLevel'].forEach((fieldName) => {
        setFieldValue(form, fieldName, fields[fieldName] ?? null);
    });

    bindVisitStatusLock(form);
    bindAppointmentScheduling(form);
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

const bindVisitBatchSelection = (scope = document) => {
    const form = scope.querySelector('[data-visit-batch-form]');
    if (!form || form.dataset.visitBatchBound === 'true') {
        return;
    }

    const checkboxes = () => Array.from(form.querySelectorAll('[data-visit-batch-checkbox]'));
    const counter = form.querySelector('[data-visit-batch-selected-count]');

    const syncCount = () => {
        if (counter) {
            counter.textContent = String(checkboxes().filter((checkbox) => checkbox.checked).length);
        }
    };

    form.querySelector('[data-visit-batch-select-all]')?.addEventListener('click', () => {
        checkboxes().forEach((checkbox) => {
            checkbox.checked = true;
        });
        syncCount();
    });

    form.querySelector('[data-visit-batch-deselect-all]')?.addEventListener('click', () => {
        checkboxes().forEach((checkbox) => {
            checkbox.checked = false;
        });
        syncCount();
    });

    form.addEventListener('change', (event) => {
        if (event.target.closest('[data-visit-batch-checkbox]')) {
            syncCount();
        }
    });

    form.dataset.visitBatchBound = 'true';
    syncCount();
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
        const latitude = Number.parseFloat(row.dataset.latitude || '');
        const longitude = Number.parseFloat(row.dataset.longitude || '');

        if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
            if (badge) {
                badge.textContent = 'Coordonnees absentes';
                badge.className = 'badge rounded-pill text-bg-secondary';
            }
            row.dataset.distanceValue = '';
            continue;
        }

        try {
            const destination = {
                lat: latitude,
                lng: longitude,
            };
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

    const sortGroups = Array.from(document.querySelectorAll('[data-tour-sort-group]'));
    sortGroups.forEach((group) => {
        const groupRows = Array.from(group.querySelectorAll(':scope > [data-tour-stop]')).filter((row) => row.offsetParent !== null);
        if (!groupRows.length) {
            return;
        }

        groupRows
            .sort((left, right) => {
                const leftDistance = Number(left.dataset.distanceValue || Number.MAX_SAFE_INTEGER);
                const rightDistance = Number(right.dataset.distanceValue || Number.MAX_SAFE_INTEGER);

                return leftDistance - rightDistance;
            })
            .forEach((row) => group.appendChild(row));
    });
};

const bindTourAutoOptimize = async () => {
    const groups = Array.from(document.querySelectorAll('[data-tour-sort-group]'));
    if (!groups.length) {
        return;
    }

    const rows = Array.from(document.querySelectorAll('[data-tour-stop]'));
    const hasCoordinates = rows.some((row) => row.dataset.latitude && row.dataset.longitude);
    if (!hasCoordinates) {
        return;
    }

    try {
        await computeTourDistances({ sortByDistance: true });
    } catch {
        // Ne pas bloquer l'ecran si la geolocalisation du commercial n'est pas disponible.
    }
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

const resetNavigationOverlays = () => {
    document.querySelectorAll('.dropdown-toggle').forEach((toggle) => {
        const instance = bootstrap.Dropdown.getInstance(toggle);
        instance?.hide();
        toggle.setAttribute('aria-expanded', 'false');
    });

    document.querySelectorAll('.dropdown-menu.show').forEach((menu) => {
        menu.classList.remove('show');
    });

    document.querySelectorAll('.offcanvas.show').forEach((panel) => {
        bootstrap.Offcanvas.getInstance(panel)?.hide();
    });

    document.querySelectorAll('.navbar-collapse.show').forEach((collapseElement) => {
        bootstrap.Collapse.getInstance(collapseElement)?.hide();
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

const renderObjectiveContext = (form, context) => {
    const panel = form.querySelector('[data-objective-context-panel]');
    if (!panel) {
        return;
    }

    const duplicateAlert = panel.querySelector('[data-objective-duplicate]');
    const emptyMessage = panel.querySelector('[data-objective-empty]');

    if (!context?.ready) {
        if (emptyMessage) {
            emptyMessage.textContent = context?.message || 'Selectionne un commercial.';
            emptyMessage.classList.remove('d-none');
        }
        panel.querySelectorAll('[data-objective-clients],[data-objective-visits],[data-objective-load],[data-objective-reco-sales],[data-objective-reco-visits],[data-objective-reco-new-clients],[data-objective-last-sales],[data-objective-last-visits],[data-objective-last-new-clients]').forEach((node) => {
            node.textContent = '--';
        });
        if (duplicateAlert) {
            duplicateAlert.classList.add('d-none');
        }
        return;
    }

    if (emptyMessage) {
        emptyMessage.classList.add('d-none');
    }

    const setText = (selector, value) => {
        const element = panel.querySelector(selector);
        if (element) {
            element.textContent = value ?? '--';
        }
    };

    setText('[data-objective-clients]', context.commercial?.clientsAssigned ?? '--');
    setText('[data-objective-visits]', context.commercial?.plannedVisits ?? '--');
    setText('[data-objective-load]', context.commercial?.load ?? '--');
    setText('[data-objective-reco-sales]', context.recommendedTargets?.salesTarget ?? '--');
    setText('[data-objective-reco-visits]', context.recommendedTargets?.visitsTarget ?? '--');
    setText('[data-objective-reco-new-clients]', context.recommendedTargets?.newClientsTarget ?? '--');

    const lastObjective = context.lastObjective;
    const lastEmpty = panel.querySelector('[data-objective-last-empty]');
    if (lastObjective) {
        setText('[data-objective-last-sales]', lastObjective.salesTarget ?? '--');
        setText('[data-objective-last-visits]', lastObjective.visitsTarget ?? '--');
        setText('[data-objective-last-new-clients]', lastObjective.newClientsTarget ?? '--');
        if (lastEmpty) {
            lastEmpty.classList.add('d-none');
        }
    } else if (lastEmpty) {
        lastEmpty.classList.remove('d-none');
    }

    if (duplicateAlert) {
        duplicateAlert.textContent = context.duplicateMessage || '';
        duplicateAlert.classList.toggle('d-none', !context.duplicate);
    }
};

const loadObjectiveContext = async (form) => {
    const template = form.dataset.objectiveContextUrlTemplate;
    const commercialField = form.querySelector('[data-objective-commercial]');
    const periodField = form.querySelector('[data-objective-period]');

    if (!template || !commercialField?.value) {
        renderObjectiveContext(form, {
            ready: false,
            message: 'Selectionne un commercial pour afficher sa charge actuelle et ses reperes.',
        });
        return;
    }

    const query = new URLSearchParams();
    if (periodField?.value) {
        query.set('period', periodField.value);
    }

    const response = await fetch(`${template.replace('__COMMERCIAL__', commercialField.value)}?${query.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to load objective context.');
    }

    renderObjectiveContext(form, await response.json());
};

const bindObjectivePlanning = (scope = document) => {
    const form = scope.querySelector('[data-objective-context-url-template]');
    const commercialField = form?.querySelector('[data-objective-commercial]');
    const periodField = form?.querySelector('[data-objective-period]');

    if (!form || !commercialField || !periodField || form.dataset.objectivePlanningBound === 'true') {
        return;
    }

    const sync = async () => {
        await loadObjectiveContext(form);
    };

    commercialField.addEventListener('change', sync);
    periodField.addEventListener('input', sync);
    form.dataset.objectivePlanningBound = 'true';
    void sync();
};

const bindTourMoveMode = (scope = document) => {
    const form = scope.querySelector('[data-tour-move-mode]')?.closest('form');
    const modeField = form?.querySelector('[data-tour-move-mode]');
    const existingWrap = form?.querySelector('[data-tour-move-existing]');
    const newWrap = form?.querySelector('[data-tour-move-new]');

    if (!form || !modeField || form.dataset.tourMoveBound === 'true') {
        return;
    }

    const syncMode = () => {
        const useNewTour = modeField.value === 'new';
        existingWrap?.classList.toggle('d-none', useNewTour);
        newWrap?.classList.toggle('d-none', !useNewTour);
    };

    modeField.addEventListener('change', syncMode);
    form.dataset.tourMoveBound = 'true';
    syncMode();
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
    bindAppointmentScheduling(modalBody);
    bindVisitClientPrefill(modalBody);
    bindOfferItems(modalBody);
    bindObjectivePlanning(modalBody);
    bindVisitBatchSelection(modalBody);
    bindTourMoveMode(modalBody);
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
            bindAppointmentScheduling(modalBody);
            bindVisitClientPrefill(modalBody);
            bindOfferItems(modalBody);
            bindObjectivePlanning(modalBody);
            bindVisitBatchSelection(modalBody);
            bindTourMoveMode(modalBody);
        }

        return;
    }

    getModalInstance()?.hide();
    if (form.dataset.crudReload === 'true') {
        window.location.reload();
        return;
    }

    await refreshCrudList(form.dataset.crudRefreshUrl);
};

document.addEventListener('click', async (event) => {
    const focusClientTrigger = event.target.closest('[data-client-map-focus-client]');
    if (focusClientTrigger && clientMapState?.payload) {
        event.preventDefault();
        const clientId = focusClientTrigger.dataset.clientMapFocusClient;
        const selectedClient = (clientMapState.payload.clients || []).find((client) => String(client.id) === String(clientId));
        if (!selectedClient) {
            return;
        }

        clientMapState.selectedClientId = selectedClient.id;
        renderClientMapSidebar(clientMapState.payload);

        const marker = clientMapState.markerIndex?.[String(selectedClient.id)];
        if (marker) {
            marker.openPopup();
            clientMapState.map.panTo(marker.getLatLng());
        }
    }

    const resetTrigger = event.target.closest('[data-client-map-reset]');
    if (resetTrigger && clientMapState?.filtersForm) {
        event.preventDefault();
        clientMapState.filtersForm.reset();
        await refreshClientMapData({ keepBounds: false });
        return;
    }

    const planVisitTrigger = event.target.closest('[data-client-map-plan-visit]');
    if (planVisitTrigger) {
        event.preventDefault();
        const response = await fetch(planVisitTrigger.dataset.clientMapPlanVisit, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            window.alert(payload.message || 'Impossible de creer la visite.');
            return;
        }

        window.alert(payload.message || 'La visite a ete creee.');
        await refreshClientMapData();
        return;
    }

    const showVisitsTrigger = event.target.closest('[data-client-map-show-visits]');
    if (showVisitsTrigger) {
        event.preventDefault();
        const historyBlock = showVisitsTrigger.closest('[data-client-map-selected]')?.querySelector('[data-client-map-visit-history]');
        historyBlock?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return;
    }

    const sliderPrevTrigger = event.target.closest('[data-client-map-slider-prev]');
    if (sliderPrevTrigger && clientMapState) {
        event.preventDefault();
        const windowElement = clientMapState.container?.querySelector('.app-client-map-results-window');
        if (!windowElement) {
            return;
        }

        const nextTop = Math.max(0, windowElement.scrollTop - (clientMapState.sliderStep || 0));
        windowElement.scrollTo({ top: nextTop, behavior: 'smooth' });
        window.setTimeout(updateClientMapSlider, 320);
        updateClientMapSlider();
        return;
    }

    const sliderNextTrigger = event.target.closest('[data-client-map-slider-next]');
    if (sliderNextTrigger && clientMapState) {
        event.preventDefault();
        const windowElement = clientMapState.container?.querySelector('.app-client-map-results-window');
        if (!windowElement) {
            return;
        }

        const maxScroll = Math.max(0, windowElement.scrollHeight - windowElement.clientHeight);
        const nextTop = Math.min(maxScroll, windowElement.scrollTop + (clientMapState.sliderStep || 0));
        windowElement.scrollTo({ top: nextTop, behavior: 'smooth' });
        window.setTimeout(updateClientMapSlider, 320);
        updateClientMapSlider();
        return;
    }

    const geocodeTrigger = event.target.closest('[data-client-map-geocode-url]');
    if (geocodeTrigger) {
        event.preventDefault();
        geocodeTrigger.disabled = true;

        const response = await fetch(geocodeTrigger.dataset.clientMapGeocodeUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            window.alert(payload.message || 'Impossible de generer les coordonnees.');
            geocodeTrigger.disabled = false;
            return;
        }

        window.alert(payload.message || 'Coordonnees generees.');
        await refreshClientMapData({ keepBounds: false });
    }
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-crud-modal-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    setTriggerBusy(trigger, 'Ouverture...');
    try {
        await openCrudModal(trigger.dataset.crudModalUrl);
    } finally {
        clearTriggerBusy(trigger);
    }
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-city-modal-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    setTriggerBusy(trigger, 'Ouverture...');
    try {
        await openCityModal(trigger.dataset.cityModalUrl);
    } finally {
        clearTriggerBusy(trigger);
    }
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-option-modal-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    setTriggerBusy(trigger, 'Ouverture...');
    try {
        await openOptionModal(trigger.dataset.optionModalUrl);
    } finally {
        clearTriggerBusy(trigger);
    }
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-zone-modal-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    setTriggerBusy(trigger, 'Ouverture...');
    try {
        await openZoneModal(trigger.dataset.zoneModalUrl);
    } finally {
        clearTriggerBusy(trigger);
    }
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-client-import-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    setTriggerBusy(trigger, 'Ouverture...');
    try {
        await openClientImportModal(trigger.dataset.clientImportUrl);
    } finally {
        clearTriggerBusy(trigger);
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-crud-ajax-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    const submitter = event.submitter;
    setTriggerBusy(submitter, 'Enregistrement...');
    try {
        await submitCrudForm(form);
    } finally {
        clearTriggerBusy(submitter);
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-city-ajax-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    const submitter = event.submitter;
    setTriggerBusy(submitter, 'Enregistrement...');
    try {
        await submitCityForm(form);
    } finally {
        clearTriggerBusy(submitter);
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-option-ajax-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    const submitter = event.submitter;
    setTriggerBusy(submitter, 'Enregistrement...');
    try {
        await submitOptionForm(form);
    } finally {
        clearTriggerBusy(submitter);
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-zone-ajax-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    const submitter = event.submitter;
    setTriggerBusy(submitter, 'Enregistrement...');
    try {
        await submitZoneForm(form);
    } finally {
        clearTriggerBusy(submitter);
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-client-import-form]');
    if (!form) {
        return;
    }

    event.preventDefault();
    const submitter = event.submitter;
    setTriggerBusy(submitter, 'Import...');
    try {
        await submitClientImportForm(form);
    } finally {
        clearTriggerBusy(submitter);
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

    setTriggerBusy(trigger, 'Traitement...');
    try {
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
    } finally {
        clearTriggerBusy(trigger);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    bindZoneCodeAutofill(document);
    bindVisitStatusLock(document);
    bindAppointmentScheduling(document);
    bindVisitClientPrefill(document);
    bindLiveSearch();
    bindOfferItems(document);
    bindFlashAlerts();
    bindObjectivePlanning(document);
    bindTourLiveRefresh();
    void bindVisitInsightsCharts();
    bindVisitBatchSelection(document);
    bindDashboardPendingClosureRefresh();
    bindTourMoveMode(document);
    void bindClientMapPage();
    void bindTourAutoOptimize();
});

document.addEventListener('turbo:before-cache', () => {
    resetNavigationOverlays();
    stopTourLiveRefresh();
    stopDashboardPendingClosureRefresh();
    if (clientMapState?.map) {
        clientMapState.map.remove();
        clientMapState = null;
    }
});

document.addEventListener('turbo:load', () => {
    bindZoneCodeAutofill(document);
    bindVisitStatusLock(document);
    bindAppointmentScheduling(document);
    bindVisitClientPrefill(document);
    bindOfferItems(document);
    bindFlashAlerts();
    bindObjectivePlanning(document);
    bindTourLiveRefresh();
    resetNavigationOverlays();
    void bindVisitInsightsCharts();
    bindVisitBatchSelection(document);
    bindDashboardPendingClosureRefresh();
    bindTourMoveMode(document);
    void bindClientMapPage();
    void bindTourAutoOptimize();
});

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-crud-post-url]');
    if (!trigger) {
        return;
    }

    event.preventDefault();

    if (trigger.dataset.crudConfirm && !window.confirm(trigger.dataset.crudConfirm)) {
        return;
    }

    setTriggerBusy(trigger, 'Traitement...');
    try {
        const response = await fetch(trigger.dataset.crudPostUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const contentType = response.headers.get('content-type') || '';
        const payload = contentType.includes('application/json') ? await response.json() : null;
        if (!response.ok) {
            throw new Error(payload?.message || 'Action impossible.');
        }

        if (trigger.dataset.crudRefreshUrl) {
            await refreshCrudList(trigger.dataset.crudRefreshUrl);
        }

        if (trigger.dataset.crudReload === 'true') {
            window.location.reload();
        }
    } finally {
        clearTriggerBusy(trigger);
    }
});
