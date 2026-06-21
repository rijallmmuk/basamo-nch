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

            const layer = L.featureGroup().addTo(map);
            const colorFor = (n) => n >= 6 ? '#15803d' : n >= 3 ? '#22c55e' : n >= 1 ? '#86efac' : '#e5e7eb';
            const fmt = (v) => v == null ? '—' : Number(v).toLocaleString('id-ID');

            // Path Kepmendagri tak konsisten: bisa satu ring [[lat,lng],…] atau banyak ring.
            const toRings = (p) => {
                if (!Array.isArray(p) || !p.length) return [];
                return typeof p[0][0] === 'number' ? [p] : p;
            };

            const popupHtml = (f) => `
                <div class="text-sm">
                    <div class="flex items-center gap-2">
                        ${f.logo ? `<img src="${f.logo}" alt="" class="h-8 w-8 object-contain">` : ''}
                        <strong>${f.nama}</strong>
                    </div>
                    <dl class="mt-1.5 text-xs text-gray-600">
                        <div>Ibu kota: ${f.ibukota ?? '—'}</div>
                        <div>Luas: ${fmt(f.luas)} km²</div>
                        <div>Penduduk: ${fmt(f.penduduk)} jiwa</div>
                        <div class="mt-1 font-semibold text-emerald-700">Desa terdaftar: ${f.desa_terdaftar}</div>
                    </dl>
                </div>`;

            fetch(@json(route('public.peta.data')))
                .then((r) => r.json())
                .then((items) => {
                    items.forEach((f) => {
                        const base = { color: '#166534', weight: 1, fillColor: colorFor(f.desa_terdaftar), fillOpacity: 0.55 };
                        toRings(f.path).forEach((ring) => {
                            if (ring.length < 3) return;
                            const poly = L.polygon(ring, base).addTo(layer);
                            poly.bindPopup(popupHtml(f));
                            poly.on('mouseover', () => poly.setStyle({ weight: 2, fillOpacity: 0.75 }));
                            poly.on('mouseout', () => poly.setStyle(base));
                        });
                    });
                    if (layer.getLayers().length) {
                        map.fitBounds(layer.getBounds(), { padding: [20, 20] });
                    }
                });
        })();
    </script>
@endpush
