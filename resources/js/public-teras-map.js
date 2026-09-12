import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const root = document.querySelector('[data-teras-map]');

if (root) {
    const canvas = document.getElementById('teras-map-canvas');
    const status = document.getElementById('teras-map-status');
    const list = [...root.querySelectorAll('[data-teras-nagari]')];
    const search = root.querySelector('[data-teras-search]');
    const searchEmpty = root.querySelector('[data-teras-search-empty]');
    const kabupatenFilter = root.querySelector('[data-teras-filter-kabupaten]');
    const filterChips = [...root.querySelectorAll('[data-teras-filter-chips] .teras-chip')];
    const reset = root.querySelector('[data-teras-reset-map]');
    const modal = document.querySelector('[data-teras-modal]');
    const modalDialog = modal?.querySelector('[data-teras-modal-dialog]');
    const modalTitle = modal?.querySelector('[data-teras-modal-title]');
    const modalLocation = modal?.querySelector('[data-teras-modal-location]');
    const modalTabs = modal?.querySelector('[data-teras-modal-tabs]');
    const modalContent = modal?.querySelector('[data-teras-modal-content]');
    const modalFull = modal?.querySelector('[data-teras-modal-full]');
    const modalClose = modal?.querySelector('[data-teras-modal-close]');
    const modalBackdrop = modal?.querySelector('[data-teras-modal-backdrop]');

    // Map layer toggle buttons
    const toggleKabupatenBtn = root.querySelector('[data-teras-toggle-kabupaten]');
    const toggleNagariBtn = root.querySelector('[data-teras-toggle-nagari]');
    const toggleIotBtn = root.querySelector('[data-teras-toggle-iot]');

    // Mobile drawer elements
    const drawer = root.querySelector('[data-teras-drawer]');
    const drawerToggles = root.querySelectorAll('[data-teras-drawer-toggle]');
    const drawerIconOpen = root.querySelector('[data-drawer-icon-open]');
    const drawerIconClose = root.querySelector('[data-drawer-icon-close]');

    const toggleDrawer = (expand = null) => {
        if (!drawer) return;
        const isCurrentlyCollapsed = drawer.dataset.collapsed === 'true';
        const willBeCollapsed = expand !== null ? !expand : !isCurrentlyCollapsed;
        drawer.dataset.collapsed = willBeCollapsed ? 'true' : 'false';
        if (drawerIconOpen && drawerIconClose) {
            drawerIconOpen.classList.toggle('hidden', willBeCollapsed);
            drawerIconClose.classList.toggle('hidden', !willBeCollapsed);
        }
    };

    drawerToggles.forEach((btn) => btn.addEventListener('click', (e) => {
        e.preventDefault();
        toggleDrawer();
    }));

    // Leaderboard table elements
    const tableLeaderboard = document.querySelector('[data-teras-leaderboard]');
    const tableSearch = tableLeaderboard?.querySelector('[data-table-search]');
    const tableKabupaten = tableLeaderboard?.querySelector('[data-table-filter-kabupaten]');
    const tableIdm = tableLeaderboard?.querySelector('[data-table-filter-idm]');
    const tableRows = [...(tableLeaderboard?.querySelectorAll('[data-table-row]') ?? [])];
    const tableEmpty = tableLeaderboard?.querySelector('[data-table-empty]');

    let activeChipFilter = 'all';
    let showKabupatenLayer = true;
    let showNagariLayer = true;
    let focusIotOnly = false;

    // Viewport padding for desktop sidebar offset
    const getDesktopPadding = () => (window.innerWidth >= 1024
        ? { paddingTopLeft: [420, 40], paddingBottomRight: [40, 40] }
        : { padding: [20, 20] });

    const map = L.map(canvas, {
        zoomControl: false,
        scrollWheelZoom: false,
        minZoom: 6,
    }).setView([-0.74, 100.8], 8);

    L.control.zoom({ position: 'bottomright' }).addTo(map);
    const boundaryRenderer = L.canvas({ padding: 0.5 });

    const updateZoomLevelClasses = () => {
        const zoom = map.getZoom();
        const container = map.getContainer();
        if (zoom >= 11) {
            container.classList.add('teras-show-nagari-labels');
        } else {
            container.classList.remove('teras-show-nagari-labels');
        }
    };

    map.on('zoomend', updateZoomLevelClasses);
    updateZoomLevelClasses();

    // OpenStreetMap standard tile layer (gratis & tanpa butuh API key)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
    }).addTo(map);

    const styles = getComputedStyle(document.documentElement);
    const color = (token, fallback) => styles.getPropertyValue(`--color-${token}`).trim() || fallback;
    const markers = new Map();
    const polygonLayers = new Map();
    const nagariItems = new Map();
    const boundaryCodes = new Set();
    let mapBounds = null;
    let boundaryLayer = null;
    let kabupatenLayer = null;
    let activeItem = null;
    let modalRequest = null;
    let focusBeforeModal = null;

    const markerIcon = (nagariName, active = false, idmStatus = null, hasIot = false) => {
        let dotBg = active ? 'bg-amber-600 border-amber-800' : 'bg-primary border-primary';
        let badgeBg = active ? 'bg-amber-900 text-white' : 'bg-slate-900/95 text-white';

        if (!active) {
            if (idmStatus === 'MANDIRI' || idmStatus === 'Mandiri') {
                dotBg = 'bg-emerald-600 border-emerald-800';
                badgeBg = 'bg-emerald-950/95 text-white';
            } else if (idmStatus === 'MAJU' || idmStatus === 'Maju') {
                dotBg = 'bg-sky-600 border-sky-800';
                badgeBg = 'bg-sky-950/95 text-white';
            } else if (idmStatus === 'BERKEMBANG' || idmStatus === 'Berkembang') {
                dotBg = 'bg-amber-600 border-amber-800';
                badgeBg = 'bg-amber-950/95 text-white';
            }
        }

        const size = active ? 28 : 22;
        const iconHtml = `
            <div class="group relative flex flex-col items-center cursor-pointer transition-transform duration-200 hover:scale-110">
                <div class="relative flex items-center justify-center">
                    ${hasIot ? '<span class="absolute h-8 w-8 animate-ping rounded-full bg-emerald-400 opacity-75"></span>' : ''}
                    <span class="relative flex h-[${size}px] w-[${size}px] items-center justify-center rounded-full border-2 border-white shadow-xl ${dotBg}">
                        <span class="h-2.5 w-2.5 rounded-full bg-white"></span>
                    </span>
                </div>
                <div class="mt-1 flex items-center gap-1.5 rounded-full ${badgeBg} px-3 py-1 text-xs font-black tracking-tight shadow-xl backdrop-blur-sm whitespace-nowrap border border-white/40">
                    <span>${escapeHtml(nagariName)}</span>
                    ${hasIot ? '<span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse" title="Sensor IoT Aktif"></span>' : ''}
                </div>
            </div>
        `;

        return L.divIcon({
            className: 'teras-custom-marker',
            html: iconHtml,
            iconSize: [220, 50],
            iconAnchor: [110, 14],
        });
    };

    const nagariStyle = (feature, active = false) => {
        const item = feature.properties.slug ? nagariItems.get(feature.properties.slug) : null;
        const isRegistered = feature.properties.terdaftar || Boolean(item);
        const idmStatus = item?.ringkasan?.idm;

        let strokeColor = '#94a3b8';
        let fillColor = '#e2e8f0';
        let fillOpacity = 0.12;
        let weight = 0.8;

        if (isRegistered) {
            weight = active ? 3.5 : 2;
            fillOpacity = active ? 0.85 : 0.65;

            if (active) {
                strokeColor = '#b45309';
                fillColor = '#fef3c7';
            } else if (idmStatus === 'MANDIRI' || idmStatus === 'Mandiri') {
                strokeColor = '#059669';
                fillColor = '#a7f3d0';
            } else if (idmStatus === 'MAJU' || idmStatus === 'Maju') {
                strokeColor = '#0284c7';
                fillColor = '#bae6fd';
            } else if (idmStatus === 'BERKEMBANG' || idmStatus === 'Berkembang') {
                strokeColor = '#d97706';
                fillColor = '#fde68a';
            } else {
                strokeColor = '#003857';
                fillColor = '#bbf7d0';
            }
        }

        return {
            renderer: boundaryRenderer,
            color: strokeColor,
            weight,
            fillColor,
            fillOpacity,
        };
    };

    const kabupatenStyle = (feature, hovered = false) => ({
        renderer: boundaryRenderer,
        color: hovered ? '#003857' : '#64748b',
        weight: hovered ? 2.8 : 1.4,
        dashArray: hovered ? undefined : '6, 6',
        fillColor: hovered ? '#0284c7' : '#94a3b8',
        fillOpacity: hovered ? 0.14 : 0.02,
    });

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const formatValue = (value, decimals = 1) => typeof value === 'number'
        ? value.toLocaleString('id-ID', { maximumFractionDigits: decimals })
        : String(value ?? '—');

    const makeElement = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined && text !== null) element.textContent = String(text);
        return element;
    };

    const createPopupContent = (item) => {
        const hasIot = (item.ringkasan?.titik_iot || 0) > 0;
        const sdgScore = item.ringkasan?.sdgs != null ? `${formatValue(item.ringkasan.sdgs)}%` : '—';
        const idmStatus = item.ringkasan?.idm || 'Belum terdata';

        const wrapper = makeElement('div', 'p-3 text-on-surface font-sans max-w-[20rem]');
        wrapper.innerHTML = `
            <div class="flex items-start justify-between gap-2 border-b border-slate-200 pb-2.5">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-600">Nagari Mitra BASAMO</span>
                    <h3 class="text-sm font-black text-slate-900 leading-tight">${escapeHtml(item.nama)}</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">${escapeHtml([item.kecamatan, item.kabupaten].filter(Boolean).join(', '))}</p>
                </div>
                ${hasIot ? '<span class="inline-flex items-center rounded-md bg-emerald-100 px-2 py-0.5 text-[9px] font-black text-emerald-800">IoT Aktif</span>' : ''}
            </div>
            <div class="mt-2.5 grid grid-cols-3 gap-1.5 text-center text-xs">
                <div class="rounded-xl bg-slate-100 p-2">
                    <span class="block text-[9px] font-bold text-slate-500 uppercase">Penduduk</span>
                    <strong class="text-xs font-black text-slate-900">${formatValue(item.ringkasan?.penduduk ?? 0, 0)}</strong>
                </div>
                <div class="rounded-xl bg-slate-100 p-2">
                    <span class="block text-[9px] font-bold text-slate-500 uppercase">SDGs</span>
                    <strong class="text-xs font-black text-blue-900">${sdgScore}</strong>
                </div>
                <div class="rounded-xl bg-slate-100 p-2">
                    <span class="block text-[9px] font-bold text-slate-500 uppercase">Status IDM</span>
                    <strong class="text-[11px] font-black text-slate-900 truncate">${escapeHtml(idmStatus)}</strong>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <button type="button" data-popup-open-modal="${item.slug}" class="flex-1 rounded-xl bg-blue-900 py-2 px-3 text-center text-xs font-black text-white shadow-sm transition hover:bg-blue-800">
                    Buka Analisis Detail
                </button>
                <a href="${item.url_teras}" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100 hover:text-blue-900" title="Kunjungi Teras Nagari Lengkap">
                    Kunjungi ↗
                </a>
            </div>
        `;

        wrapper.querySelector(`[data-popup-open-modal="${item.slug}"]`)?.addEventListener('click', (e) => {
            e.preventDefault();
            map.closePopup();
            openModal(item);
        });

        return wrapper;
    };

    const selectNagari = (item, focusMap = true, dispatch = true, showPopup = true) => {
        list.forEach((link) => link.classList.toggle('bg-primary/10', link.dataset.slug === item.slug));
        markers.forEach((marker, slug) => {
            const nagariItem = nagariItems.get(slug);
            marker.setIcon(markerIcon(nagariItem?.nama || slug, slug === item.slug, nagariItem?.ringkasan?.idm, Boolean(nagariItem?.ringkasan?.titik_iot)));
        });
        polygonLayers.forEach((layer, slug) => layer.setStyle(nagariStyle(layer.feature, slug === item.slug)));

        const link = list.find((candidate) => candidate.dataset.slug === item.slug);
        link?.scrollIntoView({ block: 'nearest' });

        const polygon = polygonLayers.get(item.slug);
        let targetLatLng = null;

        if (polygon) {
            targetLatLng = polygon.getBounds().getCenter();
            if (focusMap) {
                map.fitBounds(polygon.getBounds(), { ...getDesktopPadding(), maxZoom: 13 });
            }
        } else if (item.koordinat) {
            targetLatLng = [item.koordinat[1], item.koordinat[0]];
            if (focusMap) {
                map.flyTo(targetLatLng, Math.max(map.getZoom(), 12), { duration: 0.7 });
            }
        }

        if (showPopup && targetLatLng) {
            L.popup({ offset: [0, -20], closeButton: true })
                .setLatLng(targetLatLng)
                .setContent(createPopupContent(item))
                .openOn(map);
        }

        if (window.innerWidth < 640 && dispatch) {
            toggleDrawer(false);
        }

        if (dispatch) {
            root.dispatchEvent(new CustomEvent('teras:nagari-selected', { detail: item }));
        }
    };

    const fetchJson = async (url) => {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (! response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    };

    const metricGrid = (metrics = []) => {
        const grid = makeElement('div', 'grid gap-3 sm:grid-cols-2 xl:grid-cols-3');

        metrics.forEach((metric) => {
            const card = makeElement('article', 'rounded-2xl border border-outline-variant bg-surface-container-lowest p-4 shadow-sm');
            card.append(
                makeElement('p', 'text-2xl font-black tabular-nums text-primary', formatValue(metric.value)),
                makeElement('h4', 'mt-1 text-xs font-extrabold text-on-surface', metric.label),
                makeElement('p', 'mt-1 text-[11px] leading-snug text-on-surface-variant', metric.description),
            );
            grid.append(card);
        });

        return grid;
    };

    const section = (title, description, content) => {
        const wrapper = makeElement('section', 'space-y-4');
        const heading = makeElement('div');
        heading.append(
            makeElement('h3', 'text-lg font-black text-primary', title),
            makeElement('p', 'mt-1 text-xs leading-relaxed text-on-surface-variant', description),
        );
        wrapper.append(heading, content);
        return wrapper;
    };

    const distribution = (items = []) => {
        const listElement = makeElement('div', 'space-y-3');
        const total = items.reduce((sum, item) => sum + (Number(item.value) || 0), 0);
        const maximum = Math.max(...items.map((item) => Number(item.value) || 0), 1);

        items.forEach((item) => {
            const row = makeElement('div');
            const val = Number(item.value) || 0;
            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0';

            const label = makeElement('div', 'flex items-center justify-between gap-3 text-xs');
            label.append(
                makeElement('span', 'font-semibold text-on-surface', item.label),
                makeElement('span', 'text-right font-bold tabular-nums text-primary', `${formatValue(item.value)} (${pct}%)`),
            );
            const track = makeElement('div', 'mt-1.5 h-2 overflow-hidden rounded-full bg-surface-container-high');
            const bar = makeElement('span', 'block h-full rounded-full bg-primary');
            bar.style.width = `${Math.max(0, Math.min(100, (val / maximum) * 100))}%`;
            track.append(bar);
            row.append(label, track);
            listElement.append(row);
        });

        return listElement;
    };

    const genderDistribution = (genderItems = []) => {
        const wrapper = makeElement('div', 'rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 space-y-4');
        const lk = genderItems.find((g) => g.label.toLowerCase().includes('laki'))?.value || 0;
        const pr = genderItems.find((g) => g.label.toLowerCase().includes('perempuan'))?.value || 0;
        const total = lk + pr;
        const lkPct = total > 0 ? ((lk / total) * 100).toFixed(1) : 50;
        const prPct = total > 0 ? ((pr / total) * 100).toFixed(1) : 50;

        const header = makeElement('div', 'flex items-center justify-between gap-4 text-xs font-bold');
        const lkLabel = makeElement('div', 'flex items-center gap-2 text-sky-700');
        lkLabel.append(makeElement('span', 'h-3 w-3 rounded-full bg-sky-500'), makeElement('span', null, `Laki-laki: ${formatValue(lk)} (${lkPct}%)`));

        const prLabel = makeElement('div', 'flex items-center gap-2 text-rose-700');
        prLabel.append(makeElement('span', 'h-3 w-3 rounded-full bg-rose-500'), makeElement('span', null, `Perempuan: ${formatValue(pr)} (${prPct}%)`));

        header.append(lkLabel, prLabel);

        const barContainer = makeElement('div', 'h-3.5 flex overflow-hidden rounded-full bg-surface-container-high');
        const lkBar = makeElement('div', 'h-full bg-sky-500');
        lkBar.style.width = `${lkPct}%`;
        const prBar = makeElement('div', 'h-full bg-rose-500');
        prBar.style.width = `${prPct}%`;

        barContainer.append(lkBar, prBar);
        wrapper.append(header, barContainer);
        return wrapper;
    };

    const weatherView = (weather) => {
        if (! weather) {
            return makeElement('p', 'rounded-2xl border border-dashed border-outline-variant p-5 text-sm text-on-surface-variant', 'Prakiraan BMKG belum tersedia untuk nagari ini.');
        }

        const wrapper = makeElement('div', 'space-y-4');
        const current = weather.saat_ini;
        if (current) {
            const card = makeElement('article', 'rounded-2xl bg-primary p-5 text-on-primary shadow-sm');
            const top = makeElement('div', 'flex flex-wrap items-end justify-between gap-4');
            const identity = makeElement('div');
            identity.append(
                makeElement('p', 'text-xs font-bold uppercase tracking-wider text-on-primary/65', weather.lokasi || 'Prakiraan BMKG Nagari'),
                makeElement('p', 'mt-1 text-2xl font-black', current.kondisi || 'Prakiraan Tersedia'),
            );
            top.append(identity, makeElement('strong', 'text-5xl font-black tabular-nums', current.suhu != null ? `${current.suhu}°C` : '—'));
            const facts = makeElement('div', 'mt-5 grid grid-cols-2 gap-3 text-xs sm:grid-cols-4');
            [
                ['Kelembapan', current.kelembapan != null ? `${current.kelembapan}%` : '—'],
                ['Kecepatan Angin', current.kecepatan_angin != null ? `${formatValue(current.kecepatan_angin)} km/j` : '—'],
                ['Arah Angin', current.arah_angin_dari || '—'],
                ['Jarak Pandang', current.jarak_pandang || '—'],
            ].forEach(([label, value]) => {
                const fact = makeElement('div', 'rounded-xl bg-white/10 p-3');
                fact.append(makeElement('p', 'text-on-primary/60 text-[10px] font-bold uppercase', label), makeElement('strong', 'mt-1 block text-sm', value));
                facts.append(fact);
            });
            card.append(top, facts);
            wrapper.append(card);
        }

        (weather.hari ?? []).forEach((day) => {
            const details = makeElement('details', 'group rounded-2xl border border-outline-variant bg-surface-container-lowest');
            const summary = makeElement('summary', 'flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-extrabold text-primary', day.label || day.tanggal || 'Prakiraan Harian');
            summary.append(makeElement('span', 'text-on-surface-variant transition group-open:rotate-180', '⌄'));
            const slots = makeElement('div', 'grid grid-cols-2 gap-2 border-t border-outline-variant p-3 sm:grid-cols-4');
            (day.slots ?? []).forEach((slot) => {
                const item = makeElement('div', 'rounded-xl bg-surface-container-low p-3 text-xs');
                item.append(
                    makeElement('p', 'font-bold text-on-surface-variant', slot.jam),
                    makeElement('p', 'mt-1 text-lg font-black text-primary', slot.suhu != null ? `${slot.suhu}°` : '—'),
                    makeElement('p', 'mt-0.5 text-on-surface font-semibold', slot.kondisi || '—'),
                    makeElement('p', 'mt-1 text-[10px] text-on-surface-variant', slot.kelembapan != null ? `Lembap ${slot.kelembapan}%` : ''),
                );
                slots.append(item);
            });
            details.append(summary, slots);
            wrapper.append(details);
        });

        return wrapper;
    };

    const ewsView = (panels = []) => {
        if (! panels.length) {
            return makeElement('p', 'rounded-2xl border border-dashed border-outline-variant p-5 text-sm text-on-surface-variant', 'Belum ada titik pantau EWS aktif pada nagari ini.');
        }

        const wrapper = makeElement('div', 'space-y-4');
        panels.forEach((panel) => {
            const trusted = panel.terhubung && ! panel.basi;
            const card = makeElement('article', 'rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm');
            const header = makeElement('div', 'flex flex-wrap items-start justify-between gap-3 border-b border-outline-variant pb-3');
            const name = makeElement('div');
            name.append(
                makeElement('h4', 'text-base font-black text-primary', panel.nama),
                makeElement('p', `mt-0.5 text-xs font-bold ${trusted ? 'text-emerald-700' : 'text-amber-700'}`, trusted ? 'Data terkini (Terhubung)' : 'Data perlu diperiksa (Basi/Terputus)'),
            );

            const badge = makeElement('span', `rounded-full px-3 py-1 text-xs font-black uppercase ${
                panel.status?.toLowerCase() === 'normal' ? 'bg-emerald-100 text-emerald-800' :
                panel.status?.toLowerCase() === 'waspada' ? 'bg-amber-100 text-amber-800' :
                panel.status?.toLowerCase() === 'siaga' ? 'bg-orange-100 text-orange-800' :
                'bg-rose-100 text-rose-800'
            }`, panel.status);

            header.append(name, badge);

            const sensors = makeElement('dl', 'mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4');
            [
                ['Tinggi Air', panel.pembacaan.tinggi_air, 'cm'],
                ['Curah Hujan', panel.pembacaan.curah_hujan, 'mm/jam'],
                ['pH Air', panel.pembacaan.ph_air, 'pH'],
                ['Getaran', panel.pembacaan.getaran, 'skala'],
            ].forEach(([label, value, unit]) => {
                const sensor = makeElement('div', 'rounded-xl bg-surface-container-low p-3');
                sensor.append(
                    makeElement('dt', 'text-[10px] font-bold uppercase tracking-wide text-on-surface-variant', label),
                    makeElement('dd', 'mt-1 text-lg font-black tabular-nums text-primary', value != null ? `${formatValue(value, 2)} ${unit}` : '—'),
                );
                sensors.append(sensor);
            });
            card.append(header, sensors);
            wrapper.append(card);
        });

        return wrapper;
    };

    const sdgView = (pillars = []) => {
        if (! pillars.length) {
            return makeElement('p', 'rounded-2xl border border-dashed border-outline-variant p-5 text-sm text-on-surface-variant', 'Belum ada data SDGs untuk nagari ini.');
        }

        const wrapper = makeElement('div', 'space-y-4');

        const pilarGrid = makeElement('div', 'grid gap-3 sm:grid-cols-2 lg:grid-cols-4');
        pillars.forEach((pillar) => {
            const pCard = makeElement('div', 'rounded-xl border border-outline-variant bg-surface-container-low p-3');
            pCard.append(
                makeElement('span', 'block text-xs font-bold text-on-surface truncate', pillar.nama),
                makeElement('span', 'mt-1 block text-xl font-black tabular-nums text-primary', pillar.skor != null ? `${formatValue(pillar.skor)}%` : '—'),
                makeElement('span', 'mt-0.5 block text-[10px] text-on-surface-variant', `${pillar.poin?.filter((g) => g.terisi).length ?? 0}/18 poin terisi`),
            );
            pilarGrid.append(pCard);
        });
        wrapper.append(pilarGrid);

        pillars.forEach((pillar) => {
            const details = makeElement('details', 'group rounded-2xl border border-outline-variant bg-surface-container-lowest');
            const summary = makeElement('summary', 'flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3');
            const identity = makeElement('span', 'min-w-0');
            identity.append(
                makeElement('strong', 'block truncate text-sm text-primary', pillar.nama),
                makeElement('span', 'mt-0.5 block text-[11px] text-on-surface-variant', `${pillar.poin?.filter((goal) => goal.terisi).length ?? 0} dari ${pillar.poin?.length ?? 0} poin terdata`),
            );
            summary.append(
                identity,
                makeElement('span', 'shrink-0 text-sm font-black tabular-nums text-primary', pillar.skor != null ? `${formatValue(pillar.skor)}%` : '—'),
            );

            const goals = makeElement('div', 'space-y-2.5 border-t border-outline-variant p-3');
            (pillar.poin ?? []).forEach((goal) => {
                const row = makeElement('div', 'flex items-center gap-3 rounded-xl bg-surface-container-low p-3 text-xs');
                const badge = makeElement('span', 'flex h-7 w-7 shrink-0 items-center justify-center rounded-lg font-black text-white text-xs', goal.nomor);
                badge.style.backgroundColor = goal.warna || pillar.warna || '#003857';

                const goalInfo = makeElement('div', 'min-w-0 flex-1');
                goalInfo.append(
                    makeElement('span', 'block font-semibold text-on-surface truncate', goal.nama),
                    makeElement('span', 'mt-0.5 block text-[10px] text-on-surface-variant', `${goal.jumlah_sasaran ?? 0} sasaran · ${goal.jumlah_indikator ?? 0} indikator`),
                );

                const scoreBox = makeElement('strong', `shrink-0 tabular-nums font-black ${goal.terisi ? 'text-primary' : 'text-on-surface-variant'}`, goal.terisi ? `${formatValue(goal.nilai)}%` : 'Belum ada');

                row.append(badge, goalInfo, scoreBox);
                goals.append(row);
            });
            details.append(summary, goals);
            wrapper.append(details);
        });

        return wrapper;
    };

    const idmDetailView = (idmData, idmDetail) => {
        if (! idmDetail && ! idmData?.latest) {
            return makeElement('p', 'rounded-2xl border border-dashed border-outline-variant p-5 text-sm text-on-surface-variant', 'Belum ada data Indeks Desa Membangun (IDM) untuk nagari ini.');
        }

        const wrapper = makeElement('div', 'space-y-4');
        const idm = idmDetail || idmData.latest;

        const heroCard = makeElement('div', 'rounded-2xl bg-primary p-6 text-on-primary shadow-sm space-y-4');
        const heroTop = makeElement('div', 'flex flex-wrap items-center justify-between gap-4 border-b border-on-primary/15 pb-4');
        const statusBox = makeElement('div');
        statusBox.append(
            makeElement('p', 'text-xs font-bold uppercase tracking-wider text-on-primary/70', `IDM Tahun ${idm.tahun || ''}`),
            makeElement('h4', 'mt-1 text-2xl font-black', idm.status || 'Tersedia'),
        );
        heroTop.append(statusBox, makeElement('div', 'text-right', `Skor: ${formatValue(idm.skor, 4)}`));
        heroCard.append(heroTop);

        if (idm.dimensi && Array.isArray(idm.dimensi)) {
            const dimensiBox = makeElement('div', 'space-y-3 pt-2');
            dimensiBox.append(makeElement('p', 'text-xs font-bold uppercase tracking-wider text-on-primary/80', 'Tiga Dimensi Penyusun'));
            idm.dimensi.forEach((dim) => {
                const row = makeElement('div');
                const label = makeElement('div', 'flex items-center justify-between gap-2 text-xs font-semibold text-on-primary');
                label.append(makeElement('span', null, dim.label), makeElement('span', null, formatValue(dim.skor, 4)));
                const track = makeElement('div', 'mt-1 h-2 overflow-hidden rounded-full bg-white/20');
                const bar = makeElement('div', `h-full rounded-full ${dim.bar || 'bg-white'}`);
                bar.style.width = `${Math.max(0, Math.min(100, (Number(dim.skor) || 0) * 100))}%`;
                track.append(bar);
                row.append(label, track);
                dimensiBox.append(row);
            });
            heroCard.append(dimensiBox);
        }
        wrapper.append(heroCard);

        if (idm.indicators && idm.indicators.length > 0) {
            const indWrapper = makeElement('div', 'rounded-2xl border border-outline-variant bg-surface-container-lowest overflow-hidden shadow-sm');
            const indHeader = makeElement('div', 'border-b border-outline-variant bg-surface-container-low p-4');
            indHeader.append(
                makeElement('h4', 'text-sm font-extrabold text-primary', 'Indikator Prioritas & Rekomendasi Kegiatan Kemendesa'),
                makeElement('p', 'mt-0.5 text-xs text-on-surface-variant', 'Daftar kegiatan yang disarankan untuk peningkatan status nagari.'),
            );
            indWrapper.append(indHeader);

            const tableContainer = makeElement('div', 'overflow-x-auto');
            const table = makeElement('table', 'w-full min-w-[38rem] text-left text-xs');
            const thead = makeElement('thead', 'bg-surface-container-high/60 text-[10px] font-bold uppercase text-on-surface-variant');
            thead.innerHTML = `
                <tr>
                    <th class="px-4 py-2.5">Dimensi</th>
                    <th class="px-4 py-2.5">Indikator</th>
                    <th class="px-4 py-2.5">Kegiatan Disarankan</th>
                    <th class="px-4 py-2.5 text-right">Skor</th>
                </tr>
            `;
            const tbody = makeElement('tbody', 'divide-y divide-outline-variant');
            idm.indicators.slice(0, 10).forEach((ind) => {
                const tr = makeElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-2.5 font-bold text-primary">${escapeHtml(ind.dimensi)}</td>
                    <td class="px-4 py-2.5 font-semibold text-on-surface">${escapeHtml(ind.indikator)}</td>
                    <td class="px-4 py-2.5 text-on-surface-variant">${escapeHtml(ind.kegiatan || ind.keterangan || '—')}</td>
                    <td class="px-4 py-2.5 text-right font-black tabular-nums text-primary">${escapeHtml(ind.skor ?? '—')}</td>
                `;
                tbody.append(tr);
            });
            table.append(thead, tbody);
            tableContainer.append(table);
            indWrapper.append(tableContainer);
            wrapper.append(indWrapper);
        }

        return wrapper;
    };

    const businessView = (businesses = [], directoryUrl = '#') => {
        const wrapper = makeElement('div', 'space-y-3');

        if (! businesses.length) {
            wrapper.append(makeElement('p', 'rounded-2xl border border-dashed border-outline-variant p-5 text-sm text-on-surface-variant', 'Belum ada UMKM aktif yang dipublikasikan pada nagari ini.'));
        } else {
            const grid = makeElement('div', 'grid gap-3 sm:grid-cols-2');
            businesses.forEach((business) => {
                const link = makeElement('a', 'group flex items-center justify-between gap-3 rounded-2xl border border-outline-variant bg-surface-container-lowest p-4 shadow-sm transition hover:border-primary/40 hover:bg-primary/5');
                link.href = business.url;
                const identity = makeElement('span', 'min-w-0');
                identity.append(
                    makeElement('strong', 'block truncate text-sm text-primary', business.nama),
                    makeElement('span', 'mt-1 block text-xs text-on-surface-variant', `${formatValue(business.produk)} produk dipublikasikan`),
                );
                link.append(identity, makeElement('span', 'shrink-0 font-black text-primary transition group-hover:translate-x-0.5', '→'));
                grid.append(link);
            });
            wrapper.append(grid);
        }

        const directory = makeElement('a', 'inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2.5 text-xs font-extrabold text-on-primary shadow-sm transition hover:opacity-90', 'Buka Direktori Lapau Nagari →');
        directory.href = directoryUrl;
        wrapper.append(directory);

        return wrapper;
    };

    const renderTab = (payload) => {
        modalContent.replaceChildren();
        const stack = makeElement('div', 'space-y-6');

        if (payload.tab === 'ringkasan') {
            const banner = makeElement('div', 'grid gap-3 sm:grid-cols-2');
            const idmCard = makeElement('div', 'rounded-2xl bg-surface-container-low p-4 border border-outline-variant flex items-center justify-between');
            idmCard.append(
                makeElement('div', null, null),
            );
            idmCard.firstChild.append(
                makeElement('p', 'text-[10px] font-bold uppercase tracking-wider text-on-surface-variant', 'Status IDM'),
                makeElement('p', 'text-lg font-black text-primary', payload.data.status_idm || 'Belum terdata'),
            );
            if (payload.data.skor_idm !== null) {
                idmCard.append(makeElement('span', 'text-xs font-bold text-on-surface-variant', `Skor: ${formatValue(payload.data.skor_idm, 4)}`));
            }

            const weatherCard = makeElement('div', 'rounded-2xl bg-surface-container-low p-4 border border-outline-variant flex items-center justify-between');
            const wInfo = makeElement('div');
            wInfo.append(
                makeElement('p', 'text-[10px] font-bold uppercase tracking-wider text-on-surface-variant', 'Cuaca Terkini BMKG'),
                makeElement('p', 'text-lg font-black text-primary', payload.data.cuaca_ringkas?.kondisi || 'Prakiraan Tersedia'),
            );
            weatherCard.append(wInfo);
            if (payload.data.cuaca_ringkas?.suhu != null) {
                weatherCard.append(makeElement('span', 'text-2xl font-black text-primary', `${payload.data.cuaca_ringkas.suhu}°`));
            }
            banner.append(idmCard, weatherCard);
            stack.append(banner);

            stack.append(
                section('Ringkasan Utama', 'Angka pokok nagari yang terdata saat ini.', metricGrid(payload.data.overview.metrics)),
                section('Aktivitas Pembelajaran', 'Rekap kegiatan belajar warga nagari.', metricGrid(payload.data.pembelajaran)),
                section('Kesiapan Cuaca & IoT', 'Kesiapan data lingkungan dan perangkat aktif.', metricGrid(payload.data.lingkungan)),
            );
        } else if (payload.tab === 'penduduk') {
            const population = (payload.data.metrics || []).filter((metric) => metric.label === 'Penduduk');
            if (population.length) {
                stack.append(section('Jumlah Penduduk Terdata', 'Penduduk yang tercatat dalam SID.', metricGrid(population)));
            }

            if (payload.data.gender?.length) {
                stack.append(section('Proporsi Jenis Kelamin', 'Perbandingan laki-laki dan perempuan.', genderDistribution(payload.data.gender)));
            }

            if (payload.data.ageGroups?.length) {
                stack.append(section('Distribusi Kelompok Usia', 'Piramida sebaran usia warga nagari.', distribution(payload.data.ageGroups)));
            }

            if (payload.data.education?.length) {
                stack.append(section('Tingkat Pendidikan', 'Distribusi jenjang pendidikan yang ditempuh.', distribution(payload.data.education)));
            }

            if (payload.data.occupations?.length) {
                stack.append(section('Mata Pencaharian Terbanyak', 'Sebaran profesi dan mata pencaharian warga.', distribution(payload.data.occupations.slice(0, 8))));
            }
        } else if (payload.tab === 'pembangunan') {
            const sdgs = payload.data.sdgs;
            stack.append(section('SDGs Desa', `${sdgs.filled} dari ${sdgs.total} poin terdata.`, metricGrid([{
                label: 'Skor Capaian SDGs', value: sdgs.filled > 0 ? `${formatValue(sdgs.score)}%` : 'Belum tersedia', description: 'Rata-rata skor 18 poin',
            }])));
            stack.append(section('Capaian 4 Pilar & 18 SDGs Desa', 'Rincian capaian per pilar dan tujuan pembangunan.', sdgView(payload.data.sdg_pilar)));
            stack.append(section('Indeks Desa Membangun (IDM)', 'Status resmi dan tiga dimensi ketahanan nagari.', idmDetailView(payload.data.idm, payload.data.idm_detail)));
        } else if (payload.tab === 'ekonomi') {
            stack.append(
                section('Ringkasan Ekonomi', 'UMKM aktif dan produk yang dipublikasikan.', metricGrid(payload.data.metrics)),
                section('Etalase Usaha Lokal', 'Usaha aktif dengan produk terbanyak pada nagari ini.', businessView(payload.data.usaha, payload.data.url_direktori)),
            );
        } else if (payload.tab === 'pembelajaran') {
            const catalog = makeElement('div', 'space-y-4');
            catalog.append(metricGrid(payload.data.katalog));
            const link = makeElement('a', 'inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2.5 text-xs font-extrabold text-on-primary shadow-sm transition hover:opacity-90', 'Buka Katalog Pelatihan Nagari →');
            link.href = payload.data.url_katalog;
            catalog.append(link);
            stack.append(
                section('Katalog Pembelajaran', 'Pelatihan dan modul yang siap dipelajari warga nagari.', catalog),
                section('Aktivitas Warga', 'Rekap agregat tanpa identitas peserta.', metricGrid(payload.data.aktivitas)),
            );
        } else if (payload.tab === 'lingkungan') {
            stack.append(
                section('Ringkasan Lingkungan', 'Kesiapan cuaca serta keadaan perangkat EWS.', metricGrid(payload.data.metrics)),
                section('Prakiraan Cuaca BMKG', 'Prakiraan resmi untuk wilayah nagari yang dipilih.', weatherView(payload.data.cuaca)),
                section('IoT dan Sistem Peringatan Dini', 'Pembacaan seluruh titik pantau aktif pada nagari ini.', ewsView(payload.data.ews)),
            );
        }

        modalContent.append(stack);
        modalContent.focus({ preventScroll: true });
    };

    const syncModalUrl = (slug, tab, replace = false) => {
        const url = new URL(window.location.href);
        if (slug) {
            url.searchParams.set('nagari', slug);
            url.searchParams.set('tab', tab || 'ringkasan');
        } else {
            url.searchParams.delete('nagari');
            url.searchParams.delete('tab');
        }
        window.history[replace ? 'replaceState' : 'pushState']({ terasModal: Boolean(slug) }, '', url);
    };

    const loadModalTab = async (url, updateUrl = true) => {
        modalRequest?.abort();
        modalRequest = new AbortController();
        modalContent.setAttribute('aria-busy', 'true');
        modalContent.replaceChildren(makeElement('div', 'flex min-h-52 items-center justify-center text-sm font-semibold text-on-surface-variant', 'Memuat data nagari…'));

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: modalRequest.signal });
            if (! response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();

            modalTitle.textContent = payload.nagari.nama;
            modalLocation.textContent = payload.nagari.lokasi;
            modalFull.href = payload.nagari.url_teras;
            modalTabs.replaceChildren();
            payload.tabs.forEach((tab) => {
                const button = makeElement('button', `shrink-0 rounded-full px-3.5 py-2 text-xs font-extrabold transition ${tab.key === payload.tab ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-primary/7 hover:text-primary'}`, tab.label);
                button.type = 'button';
                button.setAttribute('aria-current', tab.key === payload.tab ? 'page' : 'false');
                button.addEventListener('click', () => loadModalTab(tab.url));
                modalTabs.append(button);
            });
            renderTab(payload);
            if (updateUrl) syncModalUrl(payload.nagari.slug, payload.tab);
        } catch (error) {
            if (error.name !== 'AbortError') {
                modalContent.replaceChildren(makeElement('p', 'rounded-2xl border border-error/30 bg-error/5 p-5 text-sm text-error', 'Data belum dapat dimuat. Silakan coba lagi.'));
            }
        } finally {
            modalContent.removeAttribute('aria-busy');
        }
    };

    const openModal = (item, options = {}) => {
        activeItem = item;
        focusBeforeModal = options.keepFocus ? focusBeforeModal : document.activeElement;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        modalTitle.textContent = item.nama;
        modalLocation.textContent = [item.kecamatan, item.kabupaten].filter(Boolean).join(', ');
        modalFull.href = item.url_teras;
        modalClose.focus({ preventScroll: true });
        const tab = options.tab || 'ringkasan';
        loadModalTab(`${item.url_detail}?tab=${encodeURIComponent(tab)}`, options.updateUrl !== false);
    };

    const closeModal = (updateUrl = true) => {
        if (modal.classList.contains('hidden')) return;
        modalRequest?.abort();
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        activeItem = null;
        if (updateUrl) syncModalUrl(null);
        focusBeforeModal?.focus?.({ preventScroll: true });
    };

    root.addEventListener('teras:nagari-selected', (event) => openModal(event.detail));
    modalClose?.addEventListener('click', () => closeModal());
    modalBackdrop?.addEventListener('click', () => closeModal());
    modalDialog?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        if (event.key !== 'Tab') return;
        const focusable = [...modalDialog.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')]
            .filter((element) => ! element.closest('[hidden]'));
        if (! focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (! event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    const filterNagariList = () => {
        const query = search?.value.trim().toLocaleLowerCase('id-ID') || '';
        const selectedKabupaten = kabupatenFilter?.value || '';
        let visibleCount = 0;

        list.forEach((link) => {
            const matchesQuery = ! query || link.dataset.search.includes(query);
            const matchesKabupaten = ! selectedKabupaten || link.dataset.kabupaten === selectedKabupaten;

            let matchesChip = true;
            if (activeChipFilter === 'iot') {
                matchesChip = Number(link.dataset.iot) > 0;
            } else if (activeChipFilter === 'mandiri-maju') {
                matchesChip = link.dataset.idm === 'MANDIRI' || link.dataset.idm === 'MAJU';
            } else if (activeChipFilter === 'sdgs') {
                matchesChip = Number(link.dataset.sdgTerisi) > 0;
            }

            const visible = matchesQuery && matchesKabupaten && matchesChip;
            link.classList.toggle('hidden', ! visible);
            if (visible) visibleCount += 1;

            const slug = link.dataset.slug;
            const marker = markers.get(slug);
            if (marker) {
                if (visible) {
                    if (! map.hasLayer(marker)) marker.addTo(map);
                } else {
                    if (map.hasLayer(marker)) map.removeLayer(marker);
                }
            }
        });

        searchEmpty?.classList.toggle('hidden', visibleCount !== 0);
    };

    filterChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            filterChips.forEach((c) => {
                c.classList.remove('active', 'border-primary', 'bg-primary', 'text-on-primary', 'font-extrabold');
                c.classList.add('border-outline-variant', 'bg-background', 'text-on-surface', 'font-semibold');
            });
            chip.classList.add('active', 'border-primary', 'bg-primary', 'text-on-primary', 'font-extrabold');
            chip.classList.remove('border-outline-variant', 'bg-background', 'text-on-surface', 'font-semibold');
            activeChipFilter = chip.dataset.filter;
            filterNagariList();
        });
    });

    kabupatenFilter?.addEventListener('change', () => {
        filterNagariList();
        const selectedKab = kabupatenFilter.value;
        if (selectedKab && tableKabupaten) {
            tableKabupaten.value = selectedKab;
            filterLeaderboardTable();
        }

        // Also zoom map to the selected kabupaten if it exists
        if (kabupatenLayer && selectedKab) {
            let foundLayer = null;
            kabupatenLayer.eachLayer((layer) => {
                if (layer.feature.properties.nama.toLowerCase().includes(selectedKab.toLowerCase()) || selectedKab.toLowerCase().includes(layer.feature.properties.nama.toLowerCase())) {
                    foundLayer = layer;
                }
            });
            if (foundLayer) {
                map.fitBounds(foundLayer.getBounds(), { ...getDesktopPadding(), maxZoom: 11 });
            }
        }
    });
    search?.addEventListener('input', filterNagariList);

    // Leaderboard table filter logic
    const filterLeaderboardTable = () => {
        const query = tableSearch?.value.trim().toLocaleLowerCase('id-ID') || '';
        const kab = tableKabupaten?.value || '';
        const idm = tableIdm?.value || '';
        let visibleRows = 0;

        tableRows.forEach((row) => {
            const matchesQuery = ! query || row.dataset.search.includes(query);
            const matchesKab = ! kab || row.dataset.kabupaten === kab;
            const matchesIdm = ! idm || row.dataset.idm === idm;

            const visible = matchesQuery && matchesKab && matchesIdm;
            row.classList.toggle('hidden', ! visible);
            if (visible) visibleRows += 1;
        });

        tableEmpty?.classList.toggle('hidden', visibleRows !== 0);
    };

    tableSearch?.addEventListener('input', filterLeaderboardTable);
    tableKabupaten?.addEventListener('change', () => {
        filterLeaderboardTable();
        if (kabupatenFilter) {
            kabupatenFilter.value = tableKabupaten.value;
            filterNagariList();
        }
    });
    tableIdm?.addEventListener('change', filterLeaderboardTable);

    // Leaderboard row select nagari button
    document.querySelectorAll('[data-table-select-nagari]').forEach((button) => {
        button.addEventListener('click', () => {
            const slug = button.dataset.tableSelectNagari;
            const item = nagariItems.get(slug);
            if (! item) return;

            root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            selectNagari(item, true, true, true);
        });
    });

    // Layer toggle controls
    toggleKabupatenBtn?.addEventListener('click', () => {
        showKabupatenLayer = ! showKabupatenLayer;
        toggleKabupatenBtn.classList.toggle('bg-primary/10', showKabupatenLayer);
        toggleKabupatenBtn.classList.toggle('text-primary', showKabupatenLayer);
        toggleKabupatenBtn.classList.toggle('bg-surface-container-low', ! showKabupatenLayer);
        toggleKabupatenBtn.classList.toggle('text-on-surface-variant', ! showKabupatenLayer);

        if (kabupatenLayer) {
            if (showKabupatenLayer) {
                if (! map.hasLayer(kabupatenLayer)) kabupatenLayer.addTo(map);
            } else {
                if (map.hasLayer(kabupatenLayer)) map.removeLayer(kabupatenLayer);
            }
        }
    });

    toggleNagariBtn?.addEventListener('click', () => {
        showNagariLayer = ! showNagariLayer;
        toggleNagariBtn.classList.toggle('bg-emerald-50', showNagariLayer);
        toggleNagariBtn.classList.toggle('text-emerald-800', showNagariLayer);
        toggleNagariBtn.classList.toggle('bg-surface-container-low', ! showNagariLayer);
        toggleNagariBtn.classList.toggle('text-on-surface-variant', ! showNagariLayer);

        if (boundaryLayer) {
            if (showNagariLayer) {
                if (! map.hasLayer(boundaryLayer)) boundaryLayer.addTo(map);
            } else {
                if (map.hasLayer(boundaryLayer)) map.removeLayer(boundaryLayer);
            }
        }
    });

    toggleIotBtn?.addEventListener('click', () => {
        focusIotOnly = ! focusIotOnly;
        toggleIotBtn.classList.toggle('bg-teal-600', focusIotOnly);
        toggleIotBtn.classList.toggle('text-white', focusIotOnly);
        toggleIotBtn.classList.toggle('bg-surface-container-low', ! focusIotOnly);
        toggleIotBtn.classList.toggle('text-on-surface-variant', ! focusIotOnly);

        activeChipFilter = focusIotOnly ? 'iot' : 'all';
        filterChips.forEach((c) => {
            const isMatch = c.dataset.filter === activeChipFilter;
            c.classList.toggle('active', isMatch);
            c.classList.toggle('border-primary', isMatch);
            c.classList.toggle('bg-primary', isMatch);
            c.classList.toggle('text-on-primary', isMatch);
            c.classList.toggle('font-extrabold', isMatch);
            c.classList.toggle('border-outline-variant', ! isMatch);
            c.classList.toggle('bg-background', ! isMatch);
            c.classList.toggle('text-on-surface', ! isMatch);
            c.classList.toggle('font-semibold', ! isMatch);
        });
        filterNagariList();
    });

    const kabupatenUrl = root.dataset.kabupatenUrl;
    const boundaryUrl = root.dataset.boundaryUrl;
    const markerUrl = root.dataset.markerUrl;

    Promise.allSettled([
        fetchJson(boundaryUrl),
        fetchJson(markerUrl),
        kabupatenUrl ? fetchJson(kabupatenUrl) : Promise.resolve(null),
    ]).then(([boundaryResult, markerResult, kabupatenResult]) => {
        if (markerResult.status !== 'fulfilled') {
            status.textContent = 'Data nagari belum dapat dimuat.';
            return;
        }

        const items = markerResult.value.data ?? [];
        items.forEach((item) => nagariItems.set(item.slug, item));
        const itemsByCode = new Map(items.filter((item) => item.kode_wilayah).map((item) => [item.kode_wilayah, item]));
        mapBounds = L.latLngBounds([]);

        // 1. Batas Kabupaten / Kota (Rendered as base boundary layer)
        if (kabupatenResult?.status === 'fulfilled' && kabupatenResult.value?.features?.length) {
            kabupatenLayer = L.geoJSON(kabupatenResult.value, {
                style: (feature) => kabupatenStyle(feature),
                onEachFeature: (feature, layer) => {
                    const kabNama = feature.properties.nama;
                    const nagariCount = feature.properties.nagari_terdaftar ?? 0;

                    layer.bindTooltip(`
                        <div class="font-sans px-1 py-0.5 text-left">
                            <strong class="font-extrabold text-slate-900">${escapeHtml(kabNama)}</strong>
                            ${nagariCount > 0 ? `<span class="ml-1 text-[10px] font-black text-blue-800 bg-blue-100 rounded px-1.5 py-0.5">${nagariCount} Mitra</span>` : ''}
                        </div>
                    `, {
                        sticky: true,
                        direction: 'auto',
                        className: 'teras-kabupaten-tooltip',
                    });

                    layer.on('mouseover', () => layer.setStyle(kabupatenStyle(feature, true)));
                    layer.on('mouseout', () => layer.setStyle(kabupatenStyle(feature, false)));

                    layer.on('click', () => {
                        map.fitBounds(layer.getBounds(), { ...getDesktopPadding(), maxZoom: 11 });
                        if (kabupatenFilter) {
                            const option = [...kabupatenFilter.options].find((opt) => opt.value.toLowerCase().includes(kabNama.toLowerCase()) || kabNama.toLowerCase().includes(opt.value.toLowerCase()));
                            if (option) {
                                kabupatenFilter.value = option.value;
                                filterNagariList();
                            }
                        }
                    });
                },
            }).addTo(map);

            if (kabupatenLayer.getBounds().isValid()) {
                mapBounds.extend(kabupatenLayer.getBounds());
            }
        }

        const boundaryNagariCenters = [];
        const labelLayerGroup = L.layerGroup().addTo(map);

        const renderDynamicLabels = () => {
            labelLayerGroup.clearLayers();
            if (! showNagariLayer) return;

            const zoom = map.getZoom();
            if (zoom < 10) return; // Tampilan luas tetap lapang dan bersih

            const bounds = map.getBounds();
            const visibleNagaris = boundaryNagariCenters.filter((n) => bounds.contains([n.lat, n.lng]));

            // Kerapatan label menyesuaikan zoom agar tidak bertabrakan
            let stride = 1;
            if (zoom === 10) stride = 4;
            else if (zoom === 11) stride = 2;
            else stride = 1; // Zoom >= 12: seluruh nama nagari di viewport tampil

            visibleNagaris.forEach((nagari, index) => {
                if (nagari.isRegistered) return; // Nagari mitra sudah memiliki marker badge permanen

                if (index % stride === 0) {
                    const labelIcon = L.divIcon({
                        className: 'teras-map-nagari-label',
                        html: `<span class="teras-map-label-text">${escapeHtml(nagari.name)}</span>`,
                        iconSize: null,
                    });
                    labelLayerGroup.addLayer(L.marker([nagari.lat, nagari.lng], { icon: labelIcon, interactive: false }));
                }
            });
        };

        map.on('zoomend moveend', renderDynamicLabels);

        // 2. Batas Nagari Polygons
        if (boundaryResult.status === 'fulfilled' && boundaryResult.value.features?.length) {
            boundaryResult.value.features.forEach((feature) => {
                const item = itemsByCode.get(feature.properties.kode);
                feature.properties.terdaftar = Boolean(item);
                feature.properties.slug = item?.slug ?? null;
                boundaryCodes.add(feature.properties.kode);
            });

            boundaryLayer = L.geoJSON(boundaryResult.value, {
                style: (feature) => nagariStyle(feature),
                onEachFeature: (feature, layer) => {
                    const item = feature.properties.slug ? nagariItems.get(feature.properties.slug) : null;
                    const isRegistered = feature.properties.terdaftar || Boolean(item);
                    const center = layer.getBounds().getCenter();

                    boundaryNagariCenters.push({
                        name: feature.properties.nama,
                        lat: center.lat,
                        lng: center.lng,
                        slug: feature.properties.slug,
                        isRegistered,
                    });

                    // Tooltip interaktif saat di-hover/di-sentuh untuk SEMUA nagari di semua level zoom
                    layer.bindTooltip(`<span class="font-sans text-xs font-bold text-slate-800">${escapeHtml(feature.properties.nama)}</span>`, {
                        sticky: true,
                        direction: 'auto',
                        className: 'teras-nagari-hover-tooltip',
                    });

                    if (isRegistered && item) {
                        polygonLayers.set(item.slug, layer);
                        mapBounds.extend(layer.getBounds());

                        layer.on('click', (e) => {
                            L.DomEvent.stopPropagation(e);
                            selectNagari(item, false, true, true);
                        });
                        layer.on('mouseover', () => layer.setStyle({ weight: 3.5, fillOpacity: 0.85 }));
                        layer.on('mouseout', () => layer.setStyle(nagariStyle(feature, feature.properties.slug === activeItem?.slug)));
                    } else {
                        layer.on('click', (e) => {
                            L.DomEvent.stopPropagation(e);
                            L.popup({ offset: [0, -10] })
                                .setLatLng(layer.getBounds().getCenter())
                                .setContent(`<div class="p-1 font-sans text-xs font-bold text-slate-800">${escapeHtml(feature.properties.nama)}</div>`)
                                .openOn(map);
                        });
                        layer.on('mouseover', () => layer.setStyle({ weight: 2, color: '#475569', fillOpacity: 0.35 }));
                        layer.on('mouseout', () => layer.setStyle(nagariStyle(feature, false)));
                    }
                },
            }).addTo(map);

            renderDynamicLabels();
        }

        // 3. Markers for Nagari points (showing all partner nagari names permanently on map)
        items.forEach((item) => {
            let latLng = null;
            if (item.koordinat && Array.isArray(item.koordinat) && item.koordinat.length === 2) {
                latLng = [item.koordinat[1], item.koordinat[0]];
            } else if (polygonLayers.has(item.slug)) {
                const center = polygonLayers.get(item.slug).getBounds().getCenter();
                latLng = [center.lat, center.lng];
            }

            if (! latLng) return;

            const marker = L.marker(latLng, {
                icon: markerIcon(item.nama, false, item.ringkasan?.idm, Boolean(item.ringkasan?.titik_iot)),
                riseOnHover: true,
                zIndexOffset: 1000,
            })
                .on('click', (e) => {
                    L.DomEvent.stopPropagation(e);
                    selectNagari(item, false, true, true);
                })
                .addTo(map);

            markers.set(item.slug, marker);
            mapBounds.extend(latLng);
        });

        const partnerBounds = L.latLngBounds([]);
        items.forEach((item) => {
            if (item.koordinat && Array.isArray(item.koordinat)) {
                partnerBounds.extend([item.koordinat[1], item.koordinat[0]]);
            }
        });

        if (partnerBounds.isValid()) {
            map.fitBounds(partnerBounds.pad(0.4), { ...getDesktopPadding(), maxZoom: 10 });
        } else if (mapBounds.isValid()) {
            map.fitBounds(mapBounds, { ...getDesktopPadding(), maxZoom: 9 });
        } else if (boundaryLayer?.getBounds().isValid()) {
            map.fitBounds(boundaryLayer.getBounds(), { ...getDesktopPadding() });
        }

        list.forEach((link) => {
            const item = nagariItems.get(link.dataset.slug);
            if (! item) return;
            link.addEventListener('click', (event) => {
                event.preventDefault();
                selectNagari(item, true, true, true);
            });
        });

        status.classList.add('hidden');

        const params = new URL(window.location.href).searchParams;
        const initial = nagariItems.get(params.get('nagari'));
        if (initial) {
            selectNagari(initial, true, false, false);
            openModal(initial, { tab: params.get('tab') || 'ringkasan', updateUrl: false });
        }
    });

    reset?.addEventListener('click', () => {
        if (kabupatenLayer?.getBounds().isValid()) {
            map.fitBounds(kabupatenLayer.getBounds(), { ...getDesktopPadding() });
        } else if (mapBounds?.isValid()) {
            map.fitBounds(mapBounds, { ...getDesktopPadding(), maxZoom: 9 });
        }
    });

    window.addEventListener('resize', () => {
        map.invalidateSize();
    });
    window.addEventListener('popstate', () => {
        const params = new URL(window.location.href).searchParams;
        const item = nagariItems.get(params.get('nagari'));
        if (item) {
            selectNagari(item, true, false, false);
            openModal(item, { tab: params.get('tab') || 'ringkasan', updateUrl: false, keepFocus: true });
        } else {
            closeModal(false);
        }
    });
}
