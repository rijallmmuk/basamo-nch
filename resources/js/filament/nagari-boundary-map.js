import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const DEFAULT_CENTER = [-0.74, 100.6];

function updateStatus(container, message, { hidden = false } = {}) {
    const status = container.querySelector('[data-map-status]');
    const copy = container.querySelector('[data-map-status-copy]');
    const spinner = container.querySelector('[data-map-spinner]');
    const icon = container.querySelector('[data-map-status-icon]');

    status?.classList.toggle('hidden', hidden);

    if (copy && message) {
        copy.textContent = message;
    }

    spinner?.classList.add('hidden');
    icon?.classList.toggle('hidden', hidden);
}

async function initializeMap(container) {
    if (container.dataset.mapInitialized === 'true') {
        return;
    }

    const canvas = container.querySelector('[data-map-canvas]');
    const url = container.dataset.boundaryUrl;

    if (!canvas || !url) {
        updateStatus(container, 'Peta belum dapat ditampilkan karena data Nagari tidak tersedia.');
        return;
    }

    container.dataset.mapInitialized = 'true';

    const map = L.map(canvas, {
        scrollWheelZoom: false,
        zoomControl: true,
    }).setView(DEFAULT_CENTER, 9);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`Boundary request failed with status ${response.status}`);
        }

        const payload = await response.json();
        const center = Array.isArray(payload.center) && payload.center.length === 2
            ? payload.center
            : DEFAULT_CENTER;

        if (payload.boundaryAvailable && payload.geometry) {
            const layer = L.geoJSON(payload.geometry, {
                style: {
                    color: '#003857',
                    fillColor: '#1b4f72',
                    fillOpacity: 0.18,
                    weight: 2.5,
                },
            }).addTo(map);

            layer.bindTooltip(payload.nama, { sticky: true });

            const bounds = layer.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [20, 20], maxZoom: 14 });
            }

            updateStatus(container, null, { hidden: true });
        } else {
            L.circleMarker(center, {
                color: '#003857',
                fillColor: '#fed33e',
                fillOpacity: 1,
                radius: 7,
                weight: 3,
            }).addTo(map).bindTooltip(payload.nama);

            map.setView(center, payload.centerSource === 'fallback' ? 9 : 14);

            const message = payload.centerSource === 'fallback'
                ? 'Batas dan titik koordinat belum tersedia. Peta menampilkan posisi umum Sumatera Barat.'
                : 'Batas wilayah belum tersedia. Peta menampilkan titik koordinat Nagari.';

            updateStatus(container, message);
        }
    } catch {
        updateStatus(container, 'Peta gagal memuat data batas wilayah. Muat ulang halaman atau periksa koneksi.');
    }

    window.setTimeout(() => map.invalidateSize(), 200);
}

function initializeBoundaryMaps() {
    document.querySelectorAll('[data-nagari-boundary-map]').forEach(initializeMap);
}

document.addEventListener('DOMContentLoaded', initializeBoundaryMaps, { once: true });
document.addEventListener('livewire:navigated', initializeBoundaryMaps);

if (document.readyState !== 'loading') {
    initializeBoundaryMaps();
}
