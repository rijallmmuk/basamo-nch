<x-filament-panels::page>
    <x-filament.nagari-picker :pilihan="$this->pilihanNagari" />

    {{-- Banner Skor SDGs — cincin progres emas di atas biru NCH --}}
    @php
        $skor = $this->ringkasan['skor'];
        $lengkap = $this->ringkasan['kelengkapan'];
        $keliling = 2 * M_PI * 40;
        $offset = $keliling * (1 - min(100, max(0, $skor)) / 100);
    @endphp
    <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-[#003857] to-[#0b5e8d] shadow-sm">
        <div class="flex items-center gap-5 p-5 sm:gap-7 sm:p-6">
            <div class="relative h-24 w-24 shrink-0 sm:h-28 sm:w-28">
                <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.16)" stroke-width="9" />
                    <circle
                        cx="50" cy="50" r="40" fill="none"
                        stroke="#fed33e" stroke-width="9" stroke-linecap="round"
                        stroke-dasharray="{{ $keliling }}" stroke-dashoffset="{{ $offset }}"
                    />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    @if ($lengkap['terisi'] === 0)
                        <span class="text-3xl font-bold text-white/70">—</span>
                    @else
                        <span class="text-xl font-bold tracking-tight text-white sm:text-2xl">{{ number_format($skor, 1) }}<span class="text-sm font-semibold text-white/85">%</span></span>
                    @endif
                </div>
            </div>

            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-widest text-[#fed33e]">Skor SDGs</p>
                <p class="mt-1 truncate text-xl font-bold tracking-tight text-white sm:text-2xl">
                    {{ $this->nagariTerpilih?->nama_lengkap ?? '—' }}
                </p>
                <p class="mt-1 text-sm text-white/85">
                    Rata-rata nilai 18 Poin SDGs · {{ $lengkap['terisi'] }} dari {{ $lengkap['total'] }} poin terisi
                </p>
            </div>
        </div>
    </div>

    @if ($this->nagariTerpilih && $lengkap['terisi'] === 0)
        <div class="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            <x-filament::icon icon="heroicon-o-information-circle" class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-semibold">Data SDGs belum tersedia untuk nagari ini.</p>
                <p class="mt-0.5">
                    Skor 18 poin ditarik otomatis dari Kemendesa saat nagari dibuat. Bila belum
                    muncul (server Kemendesa sedang sibuk), pengelola dapat menekan tombol
                    <span class="font-semibold">Perbarui dari Kemendesa</span> di kanan atas.
                </p>
            </div>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Skor ditarik otomatis dari API Kemendesa (diperbarui berkala) — klik sebuah poin untuk melihat rincian & panduan.
        </p>
    @endif

    {{-- Grid 18 kartu poin — 6 per baris --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($this->kartu as $nomor => $p)
            <a
                href="{{ $p['url'] ?? '#' }}"
                @if (! $p['url']) tabindex="-1" aria-disabled="true" onclick="return false" @endif
                wire:key="poin-{{ $nomor }}"
                class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 {{ $p['url'] ? 'transition hover:-translate-y-0.5 hover:shadow-md' : 'pointer-events-none opacity-60' }}"
            >
                <img
                    src="{{ $p['goal']->ikonUrl() }}"
                    alt="Poin {{ $nomor }} — {{ $p['goal']->nama }}"
                    class="aspect-square w-full object-cover"
                >

                <div class="flex flex-1 flex-col gap-2 p-3">
                    <p class="line-clamp-2 text-xs font-semibold leading-4 text-gray-950 dark:text-white">
                        {{ $nomor }} · {{ $p['goal']->nama }}
                    </p>

                    <div class="mt-auto">
                        @if (! $p['terisi'])
                            <x-filament::badge color="gray">Belum ada data</x-filament::badge>
                        @else
                            <x-filament::badge :color="$p['nilai'] >= 75 ? 'success' : ($p['nilai'] >= 40 ? 'warning' : 'danger')">
                                {{ number_format($p['nilai'], 1) }}%
                            </x-filament::badge>
                        @endif
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
