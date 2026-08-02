<x-filament-panels::page>
    <x-filament.nagari-picker :pilihan="$this->pilihanNagari" />

    @php
        $panel = $this->panel;
        $angka = fn (?float $nilai, int $desimal = 0): string => $nilai === null
            ? '—'
            : number_format($nilai, $desimal, ',', '.');
    @endphp

    @if (! $this->nagariTerpilih)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada nagari untuk ditampilkan.</p>
        </x-filament::section>
    @elseif (! $panel)
        <x-filament::section>
            <div class="flex items-start gap-3">
                <x-heroicon-o-signal-slash class="h-5 w-5 shrink-0 text-gray-400" />
                <div>
                    <p class="font-medium text-gray-950 dark:text-white">
                        {{ $this->nagariTerpilih->nama_lengkap }} belum memiliki perangkat EWS
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if (auth()->user()?->isSuperAdmin())
                            Perangkat dipasang lewat environment (<code class="text-xs">EWS_TOKEN_*</code>) lalu
                            <code class="text-xs">php artisan db:seed --class=EwsDeviceSeeder</code>.
                            Tokennya sengaja tidak dapat diisi dari layar ini.
                        @else
                            Hubungi Super Admin bila nagari ini seharusnya sudah terpasang sensor.
                        @endif
                    </p>
                </div>
            </div>
        </x-filament::section>
    @else
        @php
            $pembacaan = $panel['pembacaan'];
            $status = $panel['status'];
        @endphp

        {{-- Keadaan data lebih dulu, baru angkanya: operator perlu tahu sejauh mana
             angka di bawah boleh dijadikan dasar tindakan. --}}
        @if (! $panel['terhubung'] || $panel['basi'] || ! $pembacaan)
            <x-filament::section>
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-warning-500" />
                    <div>
                        @if (! $pembacaan)
                            <p class="font-medium text-gray-950 dark:text-white">Belum ada pembacaan tersimpan</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Perangkat terdaftar, tetapi datanya belum pernah berhasil diambil. Periksa token dan
                                pastikan penjadwal (<code class="text-xs">ews:record</code>) berjalan.
                            </p>
                        @elseif (! $panel['terhubung'])
                            <p class="font-medium text-gray-950 dark:text-white">Alat sedang tidak terhubung</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Angka di bawah adalah pembacaan terakhir yang tersimpan
                                ({{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}), bukan kondisi saat ini.
                                Blynk tetap menjawab dengan nilai terakhir yang ia ingat walau perangkat mati.
                            </p>
                        @else
                            <p class="font-medium text-gray-950 dark:text-white">Data belum diperbarui</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Pembacaan terakhir {{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}.
                                Penjadwal seharusnya merekam tiap 5 menit.
                            </p>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        @endif

        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status Sungai</p>
                    <p @class([
                        'mt-1 text-3xl font-bold',
                        'text-success-600 dark:text-success-400' => $status->getColor() === 'success',
                        'text-warning-600 dark:text-warning-400' => $status->getColor() === 'warning',
                        'text-danger-600 dark:text-danger-400' => $status->getColor() === 'danger',
                        'text-gray-500 dark:text-gray-400' => $status->getColor() === 'gray',
                    ])>{{ $status->getLabel() }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Titik pantau {{ $panel['device']->namaTampil() }}
                    </p>
                </div>

                <x-filament::badge :color="$panel['terhubung'] ? 'success' : 'gray'">
                    {{ $panel['terhubung'] ? 'Alat terhubung' : 'Alat tidak terhubung' }}
                </x-filament::badge>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ([
                    ['Tinggi Air', $angka($pembacaan?->tinggi_air), 'cm', false],
                    ['Curah Hujan', $angka($pembacaan?->curah_hujan), 'mm/jam', false],
                    ['pH Air', $angka($pembacaan?->ph_air, 1), $pembacaan?->phMencurigakan() ? 'sensor perlu kalibrasi' : 'pH', (bool) $pembacaan?->phMencurigakan()],
                    ['Getaran', $angka($pembacaan?->getaran), 'skala', false],
                ] as [$label, $nilai, $satuan, $sorot])
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $nilai }}</p>
                        <p @class([
                            'mt-1 text-xs font-medium',
                            'text-warning-600 dark:text-warning-400' => $sorot,
                            'text-gray-500 dark:text-gray-400' => ! $sorot,
                        ])>{{ $satuan }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        @if (count($panel['tren']['labels']) > 1)
            <x-filament::section :heading="'Tren '.\App\Services\Ews\EwsPanelService::RENTANG_TREN_JAM.' Jam Terakhir'"
                                 :description="count($panel['tren']['labels']).' pembacaan terekam'">
                <div class="space-y-5">
                    <x-public.ews-sparkline
                        label="Tinggi air (cm)"
                        :labels="$panel['tren']['labels']"
                        :nilai="$panel['tren']['tinggi_air']"
                        warna="#0284c7" />

                    <x-public.ews-sparkline
                        label="Curah hujan (mm/jam)"
                        :labels="$panel['tren']['labels']"
                        :nilai="$panel['tren']['curah_hujan']"
                        warna="#10b981" />
                </div>
            </x-filament::section>
        @endif

        {{-- Peran lintas nagari perlu melihat ketiga titik sekaligus: banjir bandang
             tidak berhenti di batas nagari, dan satu titik naik lebih dulu daripada
             yang lain adalah petunjuk paling awal yang tersedia. --}}
        @if ($this->seluruhTitik->isNotEmpty())
            <x-filament::section heading="Seluruh Titik Pantau" description="Ringkasan semua nagari yang terpasang sensor">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="pb-2 pr-4 font-semibold">Nagari</th>
                                <th class="pb-2 pr-4 font-semibold">Status</th>
                                <th class="pb-2 pr-4 font-semibold">Tinggi Air</th>
                                <th class="pb-2 pr-4 font-semibold">Curah Hujan</th>
                                <th class="pb-2 pr-4 font-semibold">Alat</th>
                                <th class="pb-2 font-semibold">Terakhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($this->seluruhTitik as $titik)
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-950 dark:text-white">
                                        {{ $titik['device']->nagari?->nama ?? '—' }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        <x-filament::badge :color="$titik['status']->getColor()">
                                            {{ $titik['status']->getLabel() }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="py-2 pr-4">{{ $angka($titik['pembacaan']?->tinggi_air) }} cm</td>
                                    <td class="py-2 pr-4">{{ $angka($titik['pembacaan']?->curah_hujan) }} mm/jam</td>
                                    <td class="py-2 pr-4">
                                        <x-filament::badge :color="$titik['terhubung'] ? 'success' : 'gray'" size="sm">
                                            {{ $titik['terhubung'] ? 'Terhubung' : 'Mati' }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="py-2 text-gray-500 dark:text-gray-400">
                                        {{ $titik['pembacaan']?->direkam_pada?->locale('id')?->diffForHumans() ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
