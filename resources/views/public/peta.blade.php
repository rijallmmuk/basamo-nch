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
            yang sudah terdaftar di platform. Klik wilayah untuk detail.
        </p>
    </section>

    <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div id="peta"></div>

        {{-- Status muat --}}
        <div id="peta-status" class="pointer-events-none absolute inset-0 z-[1000] flex items-center justify-center text-sm text-gray-500">
            Memuat peta…
        </div>

        {{-- Legenda --}}
        <div class="pointer-events-none absolute bottom-3 right-3 z-[1000] rounded-lg border border-gray-200 bg-white/95 p-3 text-xs shadow">
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
            const map = L.map('peta', { scrollWheelZoom: false }).setView([-0.74, 100.6], 8);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            const status = document.getElementById('peta-status');
            const colorFor = (n) => n >= 6 ? '#15803d' : n >= 3 ? '#22c55e' : n >= 1 ? '#86efac' : '#e5e7eb';
            const fmt = (v) => v == null ? '—' : Number(v).toLocaleString('id-ID');

            const styleFor = (feature) => ({
                color: '#166534',
                weight: 1,
                fillColor: colorFor(feature.properties.desa_terdaftar),
                fillOpacity: 0.55,
            });

            const popupHtml = (p) => `
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
                </div>`;

            fetch(@json(route('public.peta.data')))
                .then((r) => { if (! r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then((geojson) => {
                    const layer = L.geoJSON(geojson, {
                        style: styleFor,
                        onEachFeature: (feature, lyr) => {
                            lyr.bindPopup(popupHtml(feature.properties));
                            lyr.on('mouseover', () => lyr.setStyle({ weight: 2, fillOpacity: 0.75 }));
                            lyr.on('mouseout', () => layer.resetStyle(lyr));
                        },
                    }).addTo(map);

                    if (geojson.features.length) {
                        map.fitBounds(layer.getBounds(), { padding: [20, 20] });
                    }
                    status.remove();
                })
                .catch(() => { status.textContent = 'Gagal memuat peta. Coba muat ulang halaman.'; });
        })();
    </script>
@endpush
