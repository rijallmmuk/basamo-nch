<x-filament-panels::page>
    {{-- Identitas resmi desa (read-only) — gaya header profil --}}
    <x-filament::section icon="heroicon-o-identification">
        <x-slot name="heading">Identitas Desa</x-slot>

        <div class="space-y-1.5">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $this->desa?->nama_lengkap ?? '—' }}
                </h2>
                @if ($this->desa?->wilayah_kode)
                    <x-filament::badge color="primary">{{ $this->desa->wilayah_kode }}</x-filament::badge>
                @endif
            </div>

            <p class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                @svg('heroicon-m-map-pin', 'h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500')
                <span>
                    {{ $this->desa?->provinsi ?: '—' }}
                    <span class="mx-0.5 opacity-50">&rsaquo;</span>
                    {{ $this->desa?->kabupaten ?: '—' }}
                    <span class="mx-0.5 opacity-50">&rsaquo;</span>
                    {{ $this->desa?->kecamatan ?: '—' }}
                </span>
            </p>
        </div>
    </x-filament::section>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">
                Simpan
            </x-filament::button>
        </div>
    </form>

    {{-- Peta desa di paling bawah --}}
    <x-filament::section icon="heroicon-o-map">
        <x-slot name="heading">Peta Desa</x-slot>
        <x-slot name="description">
            Batas wilayah administratif {{ $this->desa?->nama_lengkap }}.
        </x-slot>

        <div wire:ignore>
            <div id="peta-desa" style="height: 420px; width: 100%; border-radius: 0.75rem; overflow: hidden; z-index: 0;"></div>
        </div>
    </x-filament::section>

    @assets
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
    @endassets

    @script
        <script>
            // Geometri di-fetch dari endpoint ter-cache (payload Livewire tetap ringan).
            const endpoint = @js(route('admin.desa.boundary'));

            const render = () => {
                if (typeof L === 'undefined') {
                    return setTimeout(render, 120);
                }

                const el = document.getElementById('peta-desa');
                if (! el) {
                    return;
                }

                // Bersihkan instance lama (mis. setelah navigasi SPA).
                if (el._leaflet_map) {
                    el._leaflet_map.remove();
                }

                const map = L.map(el, { scrollWheelZoom: false }).setView([-0.74, 100.6], 11);
                el._leaflet_map = map;

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);

                fetch(endpoint, { headers: { Accept: 'application/json' } })
                    .then((r) => r.ok ? r.json() : Promise.reject(r.status))
                    .then((cfg) => {
                        map.setView(cfg.center, 12);

                        if (cfg.geometry) {
                            const layer = L.geoJSON(cfg.geometry, {
                                style: { color: '#003857', weight: 2, fillColor: '#1b4f72', fillOpacity: 0.15 },
                            }).addTo(map);
                            try {
                                map.fitBounds(layer.getBounds(), { padding: [20, 20] });
                            } catch (e) {}
                        }

                        setTimeout(() => map.invalidateSize(), 150);
                    })
                    .catch(() => {});

                setTimeout(() => map.invalidateSize(), 200);
            };

            render();
        </script>
    @endscript
</x-filament-panels::page>
