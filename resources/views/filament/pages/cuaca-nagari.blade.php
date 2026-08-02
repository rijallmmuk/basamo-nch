<x-filament-panels::page>
    <x-filament.nagari-picker :pilihan="$this->pilihanNagari" />

    @if (! $this->nagariTerpilih)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada nagari untuk ditampilkan.</p>
        </x-filament::section>
    @elseif (! $this->nagariTerpilih->wilayah_kode)
        <x-filament::section>
            <div class="flex items-start gap-3">
                <x-heroicon-o-map-pin class="h-5 w-5 shrink-0 text-gray-400" />
                <div>
                    <p class="font-medium text-gray-950 dark:text-white">{{ $this->nagariTerpilih->nama_lengkap }} belum punya kode wilayah</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if (auth()->user()?->isSuperAdmin())
                            Atur lewat menu Nagari → Ubah → field Wilayah, supaya prakiraan cuaca BMKG bisa ditampilkan.
                        @else
                            Hubungi Super Admin untuk mengatur kode wilayah nagari ini — prakiraan cuaca BMKG belum bisa ditampilkan sebelum itu.
                        @endif
                    </p>
                </div>
            </div>
        </x-filament::section>
    @elseif (! $this->cuaca)
        <x-filament::section>
            <div class="flex items-start gap-3">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-warning-500" />
                <div>
                    <p class="font-medium text-gray-950 dark:text-white">Gagal mengambil data cuaca dari BMKG</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Layanan BMKG mungkin sedang gangguan. Coba muat ulang beberapa saat lagi.</p>
                </div>
            </div>
        </x-filament::section>
    @else
        @php
            $saatIni = $this->cuaca['saat_ini'];
            $lebarKolom = 88;
            $lebarLabel = 132;
        @endphp

        {{-- ══ SAAT INI — gradient biru NCH, pola sama dgn banner skor SDGs ══ --}}
        <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-[#003857] to-[#0b5e8d] shadow-sm">
            <div class="flex flex-col gap-4 p-5 sm:p-6">
                <div class="flex items-center gap-5">
                    @if ($saatIni['ikon'])
                        <img src="{{ $saatIni['ikon'] }}" alt="{{ $saatIni['kondisi'] }}" class="h-16 w-16 shrink-0 sm:h-20 sm:w-20">
                    @endif
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#fed33e]">Saat ini</p>
                        <p class="mt-1 flex flex-wrap items-baseline gap-2">
                            <span class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $saatIni['suhu'] }}&deg;C</span>
                            <span class="text-base font-semibold text-white/85">{{ $saatIni['kondisi'] }}</span>
                        </p>
                        <p class="mt-0.5 text-sm text-white/85">di {{ $this->cuaca['lokasi'] ?? $this->nagariTerpilih->nama_lengkap }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach([
                        ['heroicon-o-cloud', 'Kelembapan', $saatIni['kelembapan'] !== null ? $saatIni['kelembapan'].'%' : '—'],
                        ['heroicon-o-flag', 'Kecepatan Angin', $saatIni['kecepatan_angin'] !== null ? number_format($saatIni['kecepatan_angin'], 1, ',', '.').' km/jam' : '—'],
                        ['heroicon-o-arrow-up-right', 'Arah Angin dari', $saatIni['arah_angin_dari'] ?? '—'],
                        ['heroicon-o-eye', 'Jarak Pandang', $saatIni['jarak_pandang'] ?? '—'],
                    ] as [$icon, $label, $nilai])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white/85">
                            <x-dynamic-component :component="$icon" class="h-3.5 w-3.5 shrink-0 text-[#fed33e]" />
                            {{ $label }}: <span class="font-semibold text-white">{{ $nilai }}</span>
                        </span>
                    @endforeach
                </div>

                <p class="text-[11px] font-semibold uppercase tracking-widest text-white/85">Sumber: BMKG</p>
            </div>
        </div>

        {{-- ══ PRAKIRAAN PER JAM — scroll horizontal, dikelompokkan per hari ══ --}}
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Prakiraan per Jam (WIB)</h2>
                <div class="flex gap-2">
                    <button type="button" id="jam-prev" aria-label="Geser ke kiri" class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:bg-gray-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5">
                        <x-heroicon-o-chevron-left class="h-4 w-4" />
                    </button>
                    <button type="button" id="jam-next" aria-label="Geser ke kanan" class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:bg-gray-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5">
                        <x-heroicon-o-chevron-right class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div id="jam-scroll" class="inline-block max-w-full overflow-x-auto rounded-xl border border-gray-200 align-top [scrollbar-width:thin] dark:border-white/10">
                <div class="flex flex-col">
                    {{-- Baris tanggal --}}
                    <div class="flex border-b border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                        <div class="sticky left-0 z-10 shrink-0 border-r border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400" style="width: {{ $lebarLabel }}px">Tanggal</div>
                        @foreach($this->cuaca['hari'] as $h)
                            <div class="shrink-0 truncate border-r border-gray-200 px-2 py-2 text-center text-xs font-semibold text-gray-500 dark:border-white/10 dark:text-gray-400" style="width: {{ count($h['slots']) * $lebarKolom }}px">
                                {{ $h['tanggal'] }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Baris jam --}}
                    <div class="flex border-b border-gray-200 dark:border-white/10">
                        <div class="sticky left-0 z-10 shrink-0 border-r border-gray-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400" style="width: {{ $lebarLabel }}px">Jam</div>
                        @foreach($this->cuaca['hari'] as $h)
                            @foreach($h['slots'] as $slot)
                                <div class="shrink-0 border-r border-gray-100 py-2 text-center text-xs font-semibold text-gray-500 dark:border-white/5 dark:text-gray-400" style="width: {{ $lebarKolom }}px">{{ $slot['jam'] }}</div>
                            @endforeach
                        @endforeach
                    </div>

                    {{-- Baris cuaca/suhu/kelembapan --}}
                    <div class="flex border-b border-gray-200 dark:border-white/10">
                        <div class="sticky left-0 z-10 flex shrink-0 items-center border-r border-gray-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400" style="width: {{ $lebarLabel }}px">Cuaca, Suhu, Kelembapan</div>
                        @foreach($this->cuaca['hari'] as $h)
                            @foreach($h['slots'] as $slot)
                                <div class="flex shrink-0 flex-col items-center gap-0.5 border-r border-gray-100 py-2 dark:border-white/5" style="width: {{ $lebarKolom }}px">
                                    @if($slot['ikon'])
                                        <img src="{{ $slot['ikon'] }}" alt="{{ $slot['kondisi'] }}" class="h-8 w-8">
                                    @endif
                                    <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $slot['suhu'] }}&deg;</span>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ $slot['kelembapan'] }}%</span>
                                </div>
                            @endforeach
                        @endforeach
                    </div>

                    {{-- Baris angin --}}
                    <div class="flex border-b border-gray-200 dark:border-white/10">
                        <div class="sticky left-0 z-10 flex shrink-0 items-center border-r border-gray-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400" style="width: {{ $lebarLabel }}px">Angin</div>
                        @foreach($this->cuaca['hari'] as $h)
                            @foreach($h['slots'] as $slot)
                                <div class="flex shrink-0 flex-col items-center gap-0.5 border-r border-gray-100 px-1 py-2 dark:border-white/5" style="width: {{ $lebarKolom }}px">
                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $slot['kecepatan_angin'] !== null ? number_format($slot['kecepatan_angin'], 1, ',', '.') : '—' }} km/j</span>
                                    <span class="flex max-w-full items-center gap-1 text-[10px] text-gray-400 dark:text-gray-500">
                                        <span class="truncate">{{ $slot['arah_angin_dari'] }}</span>
                                        @if($slot['arah_derajat_tujuan'] !== null)
                                            <x-heroicon-o-arrow-up class="h-2.5 w-2.5 shrink-0" style="transform: rotate({{ $slot['arah_derajat_tujuan'] }}deg)" />
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        @endforeach
                    </div>

                    {{-- Baris jarak pandang --}}
                    <div class="flex">
                        <div class="sticky left-0 z-10 flex shrink-0 items-center border-r border-gray-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400" style="width: {{ $lebarLabel }}px">Jarak Pandang</div>
                        @foreach($this->cuaca['hari'] as $h)
                            @foreach($h['slots'] as $slot)
                                <div class="shrink-0 truncate border-r border-gray-100 px-1 py-2 text-center text-[11px] font-medium text-gray-500 dark:border-white/5 dark:text-gray-400" style="width: {{ $lebarKolom }}px">{{ $slot['jarak_pandang'] ?? '—' }}</div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            Data prakiraan cuaca bersumber dari <span class="font-semibold">BMKG</span> (Badan Meteorologi, Klimatologi, dan Geofisika), diperbarui berkala. Ini adalah prakiraan, bukan pengukuran langsung di lokasi.
        </p>
    @endif

    @pushOnce('scripts')
    <script>
        // Delegasi di document (bukan addEventListener per-elemen saat DOMContentLoaded):
        // halaman ini Livewire (selektor nagari wire:model.live bisa memunculkan/mengganti
        // tabel & tombolnya kapan saja), jadi listener harus tetap bekerja walau elemen
        // #jam-scroll/#jam-prev/#jam-next baru muncul belakangan lewat re-render.
        document.addEventListener('click', (e) => {
            const scroller = document.getElementById('jam-scroll');
            if (!scroller) return;

            if (e.target.closest('#jam-prev')) {
                scroller.scrollBy({ left: -288, behavior: 'smooth' });
            } else if (e.target.closest('#jam-next')) {
                scroller.scrollBy({ left: 288, behavior: 'smooth' });
            }
        });
    </script>
    @endPushOnce
</x-filament-panels::page>
