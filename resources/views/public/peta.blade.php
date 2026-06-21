@extends('public.layouts.app')

@section('title', 'Peta Desa')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #peta { height: 70vh; min-height: 420px; }
        .leaflet-popup-content { margin: 12px 14px; }
    </style>
@endpush

@section('content')
    <section class="mb-5">
        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Peta Desa — Sumatera Barat</h1>
        <p class="mt-1 text-sm text-gray-500">
            Sebaran kabupaten/kota se-Sumatera Barat. Warna menunjukkan jumlah desa/nagari
            terdaftar di platform. Klik kabupaten/kota untuk melihat batas desa di dalamnya.
        </p>
    </section>

    <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div id="peta"></div>

        {{-- Breadcrumb + tombol kembali --}}
        <div id="peta-nav" class="pointer-events-none absolute left-3 top-3 z-[1000] hidden">
            <button type="button" id="peta-kembali"
                class="pointer-events-auto inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white/95 px-3 py-1.5 text-xs font-medium text-gray-700 shadow hover:bg-white">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                <span>Sumatera Barat</span>
            </button>
            <p id="peta-judul" class="mt-1.5 rounded-lg bg-white/95 px-3 py-1 text-xs font-semibold text-gray-700 shadow"></p>
        </div>

        {{-- Status muat --}}
        <div id="peta-status" class="pointer-events-none absolute inset-0 z-[1000] flex items-center justify-center text-sm text-gray-500">
            Memuat peta…
        </div>

        {{-- Legenda --}}
        <div id="peta-legenda" class="pointer-events-none absolute bottom-3 right-3 z-[1000] rounded-lg border border-gray-200 bg-white/95 p-3 text-xs shadow">
            <p class="mb-1.5 font-semibold text-gray-700">Desa terdaftar</p>
            <div class="space-y-1">
                <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm" style="background:#15803d"></span> 6 atau lebih</div>
                <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm" style="background:#22c55e"></span> 3–5</div>
                <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm" style="background:#86efac"></span> 1–2</div>
                <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm" style="background:#e5e7eb"></span> Belum ada</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            const dataUrl = @json(route('public.peta.data'));
            const map = L.map('peta', { scrollWheelZoom: false }).setView([-0.74, 100.6], 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            const status = document.getElementById('peta-status');
            const nav = document.getElementById('peta-nav');
            const judul = document.getElementById('peta-judul');
            const legenda = document.getElementById('peta-legenda');
            const fmt = (v) => v == null ? '—' : Number(v).toLocaleString('id-ID');

            const kabColor = (n) => n >= 6 ? '#15803d' : n >= 3 ? '#22c55e' : n >= 1 ? '#86efac' : '#e5e7eb';
            const kabStyle = (f) => ({ color: '#166534', weight: 1, fillColor: kabColor(f.properties.desa_terdaftar), fillOpacity: 0.55 });
            const desaStyle = (f) => ({
                color: f.properties.terdaftar ? '#166534' : '#94a3b8',
                weight: 1,
                fillColor: f.properties.terdaftar ? '#22c55e' : '#e5e7eb',
                fillOpacity: f.properties.terdaftar ? 0.7 : 0.4,
            });

            const kabPopup = (p) => `
                <div class="text-sm">
                    <div class="flex items-center gap-2">
                        ${p.logo ? `<img src="${p.logo}" alt="" class="h-8 w-8 object-contain">` : ''}
                        <strong>${p.nama}</strong>
                    </div>
                    <dl class="mt-1.5 text-xs text-gray-600">
                        <div>Ibu kota: ${p.ibukota ?? '—'}</div>
                        <div>Luas: ${fmt(p.luas)} km²</div>
                        <div>Penduduk: ${fmt(p.penduduk)} jiwa</div>
                        <div class="mt-1 font-semibold text-emerald-700">Desa terdaftar: ${p.desa_terdaftar}</div>
                    </dl>
                    <p class="mt-1.5 text-[11px] text-gray-400">Klik untuk lihat desa</p>
                </div>`;

            const desaPopup = (p) => `
                <div class="text-sm">
                    <strong>${p.nama}</strong>
                    <p class="mt-0.5 text-xs ${p.terdaftar ? 'font-semibold text-emerald-700' : 'text-gray-500'}">
                        ${p.terdaftar ? '✓ Terdaftar di platform' : 'Belum terdaftar'}
                    </p>
                </div>`;

            let kabLayer = null;
            let desaLayer = null;

            const fetchJson = (url) => fetch(url)
                .then((r) => { if (! r.ok) throw new Error('HTTP ' + r.status); return r.json(); });

            const hover = (layer) => (feature, lyr) => {
                lyr.on('mouseover', () => lyr.setStyle({ weight: 2, fillOpacity: 0.85 }));
                lyr.on('mouseout', () => layer().resetStyle(lyr));
            };

            function showKab() {
                if (desaLayer) { map.removeLayer(desaLayer); desaLayer = null; }
                kabLayer.addTo(map);
                map.fitBounds(kabLayer.getBounds(), { padding: [20, 20] });
                nav.classList.add('hidden');
                legenda.classList.remove('hidden');
            }

            function openDesa(kab, nama) {
                status.style.display = '';
                status.textContent = 'Memuat desa…';
                fetchJson(dataUrl + '?kab=' + encodeURIComponent(kab))
                    .then((geojson) => {
                        if (desaLayer) { map.removeLayer(desaLayer); }
                        map.removeLayer(kabLayer);
                        desaLayer = L.geoJSON(geojson, {
                            style: desaStyle,
                            onEachFeature: (feature, lyr) => {
                                lyr.bindPopup(desaPopup(feature.properties));
                                hover(() => desaLayer)(feature, lyr);
                            },
                        }).addTo(map);
                        if (geojson.features.length) {
                            map.fitBounds(desaLayer.getBounds(), { padding: [20, 20] });
                        }
                        judul.textContent = nama;
                        nav.classList.remove('hidden');
                        legenda.classList.add('hidden');
                        status.style.display = 'none';
                    })
                    .catch(() => { status.textContent = 'Gagal memuat desa. Coba lagi.'; });
            }

            fetchJson(dataUrl)
                .then((geojson) => {
                    kabLayer = L.geoJSON(geojson, {
                        style: kabStyle,
                        onEachFeature: (feature, lyr) => {
                            lyr.bindTooltip(kabPopup(feature.properties), { sticky: true, direction: 'top', opacity: 1 });
                            lyr.on('click', () => openDesa(feature.properties.kode, feature.properties.nama));
                            hover(() => kabLayer)(feature, lyr);
                        },
                    }).addTo(map);

                    if (geojson.features.length) {
                        map.fitBounds(kabLayer.getBounds(), { padding: [20, 20] });
                    }
                    status.style.display = 'none';
                })
                .catch(() => { status.textContent = 'Gagal memuat peta. Coba muat ulang halaman.'; });

            document.getElementById('peta-kembali').addEventListener('click', showKab);
        })();
    </script>
@endpush
