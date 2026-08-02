<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-cloud" class="h-5 w-5 text-primary-500" />
                <span>Prakiraan Cuaca Nagari {{ $nagari?->nama ?? '' }}</span>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Atribusi Data Resmi: BMKG (Cache 3 Jam)
            </span>
        </x-slot>

        @if ($cuaca && !empty($cuaca['saat_ini']))
            @php
                $saatIni = $cuaca['saat_ini'];
                $hari = $cuaca['hari'] ?? [];
            @endphp
            <div class="space-y-6">
                <!-- Ringkasan Terkini -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 rounded-xl bg-primary-50/50 dark:bg-gray-800/50 border border-primary-100 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        @if (!empty($saatIni['ikon']))
                            <img src="{{ $saatIni['ikon'] }}" alt="{{ $saatIni['kondisi'] ?? 'Cuaca' }}" class="h-12 w-12 object-contain" />
                        @else
                            <x-filament::icon icon="heroicon-o-sun" class="h-10 w-10 text-amber-500" />
                        @endif
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $saatIni['suhu'] ?? '—' }}°C
                            </p>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                {{ $saatIni['kondisi'] ?? 'Cerah' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col justify-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Kelembapan Udara</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            {{ $saatIni['kelembapan'] ?? '—' }}%
                        </span>
                    </div>

                    <div class="flex flex-col justify-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Kecepatan & Arah Angin</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            {{ $saatIni['kecepatan_angin'] ?? '—' }} km/jam ({{ $saatIni['arah_angin_dari'] ?? 'Utara' }})
                        </span>
                    </div>

                    <div class="flex flex-col justify-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Jarak Pandang</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            {{ $saatIni['jarak_pandang'] ?? '—' }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-filament::button tag="a" href="{{ \App\Filament\Pages\CuacaNagari::getUrl(['nagari' => $nagari?->id]) }}" color="primary" size="sm">
                        Lihat Detail Cuaca & 3 Hari Ke Depan
                    </x-filament::button>
                </div>
            </div>
        @else
            <!-- Fallback Sederhana saat API BMKG sedang memuat / tidak tersedia -->
            <div class="p-6 text-center rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-300 dark:border-gray-700">
                <x-filament::icon icon="heroicon-o-cloud" class="h-10 w-10 text-gray-400 mx-auto mb-2" />
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Prakiraan Cuaca BMKG untuk Nagari {{ $nagari?->nama ?? 'Ini' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Data cuaca di-cache secara otomatis selama 3 jam untuk menjaga efisiensi koneksi.
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
