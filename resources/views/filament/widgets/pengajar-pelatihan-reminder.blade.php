<x-filament-widgets::widget>
    <section class="overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm">
        <header class="flex flex-col gap-4 border-b border-amber-100 bg-amber-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                    <x-filament::icon icon="heroicon-o-bell-alert" class="h-5 w-5" />
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-bold text-gray-950">Pelatihan Siap Dibuka</h2>
                        <x-filament::badge color="warning">{{ $siapDibuka->count() }}</x-filament::badge>
                    </div>
                    <p class="mt-1 text-sm leading-6 text-gray-600">
                        Sasaran dan materi sudah tersedia. Buka pelatihan ketika warga sudah boleh mulai belajar.
                    </p>
                </div>
            </div>

            <a href="{{ $pelatihanUrl }}" class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-primary-700 hover:text-primary-600">
                Kelola pelatihan
                <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
            </a>
        </header>

        <div class="grid gap-2 p-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($siapDibuka as $pelatihan)
                <a href="{{ \App\Filament\Resources\Pelatihans\PelatihanResource::getUrl('view', ['record' => $pelatihan]) }}" class="group flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <x-filament::icon icon="heroicon-o-lock-open" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-gray-950">{{ $pelatihan->namaTampil() }}</span>
                        <span class="mt-0.5 block text-xs text-emerald-700">Buka untuk Warga</span>
                    </span>
                    <x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-emerald-600" />
                </a>
            @endforeach
        </div>
    </section>
</x-filament-widgets::widget>
