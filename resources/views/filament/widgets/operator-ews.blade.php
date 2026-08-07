<x-filament-widgets::widget>
    @if ($device)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="h-5 w-5 text-success-500" />
                    <span>Pemantauan EWS (IoT) - {{ $device->namaTampil() }}</span>
                </div>
            </x-slot>

            @php
                $reading = $device->pembacaanTerakhir;
            @endphp

            @if ($reading)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 rounded-xl bg-success-50/50 dark:bg-gray-800/50 border border-success-100 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <x-filament::icon icon="heroicon-o-arrows-up-down" class="h-10 w-10 text-primary-500" />
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $reading->tinggi_air ?? '0' }} cm
                            </p>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                Tinggi Air
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col justify-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Status Sungai</span>
                        <span class="text-sm font-bold {{ $reading->status()->kelasWarna() }}">
                            {{ $reading->status()->getLabel() }}
                        </span>
                    </div>

                    <div class="flex flex-col justify-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Curah Hujan</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            {{ $reading->curah_hujan ?? '0' }} mm
                        </span>
                    </div>

                    <div class="flex flex-col justify-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Terakhir Update</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            {{ $reading->direkam_pada?->diffForHumans() ?? 'Belum ada data' }}
                        </span>
                    </div>
                </div>
            @else
                <div class="p-4 text-center rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-300 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Belum ada data rekaman dari sensor EWS ini.
                    </p>
                </div>
            @endif

            <div class="mt-4 flex justify-end">
                <x-filament::button tag="a" href="{{ \App\Filament\Pages\PemantauanEws::getUrl(['nagari' => $nagari?->id]) }}" color="success" size="sm">
                    Lihat Analitik EWS Lengkap
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
