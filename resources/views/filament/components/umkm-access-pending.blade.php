@php($pemilikMenunggu = $this->pemilikMenunggu)

@if ($pemilikMenunggu->isNotEmpty())
    <x-filament::section
        icon="heroicon-o-clock"
        collapsible
    >
        <x-slot name="heading">Menunggu Profil Usaha</x-slot>
        <x-slot name="description">
            {{ $pemilikMenunggu->count() }} warga sudah memiliki akses UMKM, tetapi belum melengkapi profil usahanya.
        </x-slot>

        <div class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($pemilikMenunggu as $pemilik)
                <div class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $pemilik->name }}
                            </p>
                            <x-filament::badge color="warning">Belum mengisi profil</x-filament::badge>
                        </div>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            NIK {{ $pemilik->nik ?: $pemilik->penduduk?->nik ?: 'belum tersedia' }}
                            <span aria-hidden="true">&middot;</span>
                            Akses diberikan {{ $pemilik->umkm_access_granted_at?->diffForHumans() ?? '—' }}
                        </p>
                    </div>

                    @if ($this->canManageUmkmAccess())
                        <div class="shrink-0">
                            {{ ($this->cabutAksesTertundaAction)(['user' => $pemilik->id]) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
@endif
