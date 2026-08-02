<x-filament-panels::page>
    <x-filament.nagari-picker :pilihan="$this->pilihanNagari" />

    @php
        $idm = $this->idm;
    @endphp

    @if (! $this->nagariTerpilih)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada nagari untuk ditampilkan.</p>
        </x-filament::section>
    @elseif (! $idm)
        {{-- Belum ada data IDM untuk nagari ini --}}
        <x-filament::section>
            <div class="flex items-start gap-3">
                <x-heroicon-o-information-circle class="mt-0.5 h-6 w-6 shrink-0 text-amber-500" />
                <div>
                    <p class="font-semibold text-gray-950 dark:text-white">Data IDM belum tersedia untuk {{ $this->nagariTerpilih->nama_lengkap }}.</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Status IDM ditarik otomatis dari Kemendesa saat nagari dibuat.
                        @unless (auth()->user()?->isDpmd())
                            Bila belum muncul, tekan <span class="font-semibold">Perbarui dari Kemendesa</span> di kanan atas.
                        @endunless
                    </p>
                </div>
            </div>
        </x-filament::section>
    @else
        @php
            $statusEnum = $idm->statusEnum();
            $dimensi = [
                ['enum' => \App\Enums\DimensiIdm::IKS, 'skor' => (float) $idm->skor_iks, 'bar' => 'bg-sky-500'],
                ['enum' => \App\Enums\DimensiIdm::IKE, 'skor' => (float) $idm->skor_ike, 'bar' => 'bg-amber-500'],
                ['enum' => \App\Enums\DimensiIdm::IKL, 'skor' => (float) $idm->skor_ikl, 'bar' => 'bg-emerald-500'],
            ];
            $grup = $idm->indicators->groupBy(fn ($i) => $i->dimensi->value);
        @endphp

        {{-- Banner skor IDM + status --}}
        <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-[#003857] to-[#0b5e8d] p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#fed33e]">Indeks Desa Membangun {{ $idm->tahun }}</p>
                    <p class="mt-1 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">{{ number_format($idm->skor, 4, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-white/80">Rata-rata tiga dimensi ketahanan · diperbarui {{ $idm->fetched_at?->diffForHumans() }}</p>
                </div>
                <div class="text-right">
                    <x-filament::badge :color="$statusEnum?->color() ?? 'gray'" size="lg">
                        {{ $statusEnum?->label() ?? $idm->status }}
                    </x-filament::badge>
                    @if ($idm->target_status)
                        <p class="mt-2 text-xs text-white/70">Target berikutnya: {{ \App\Enums\StatusIdm::tryFrom($idm->target_status)?->label() ?? $idm->target_status }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Prioritas perbaikan: indikator terlemah (skor ≤ 2) --}}
        @php $prioritas = $this->prioritas; @endphp
        @if ($prioritas->isNotEmpty())
            <x-filament::section icon="heroicon-o-exclamation-triangle">
                <x-slot name="heading">Prioritas Perbaikan</x-slot>
                <x-slot name="description">{{ $prioritas->count() }} indikator berskor rendah (≤ 2) yang paling menahan kenaikan status.</x-slot>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($prioritas as $i)
                        <div class="rounded-xl border border-red-200 bg-red-50 p-3 dark:border-red-500/30 dark:bg-red-500/10">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $i->indikator }}</span>
                                <x-filament::badge color="danger">{{ $i->skor }}/5</x-filament::badge>
                            </div>
                            <p class="mt-1 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <span>{{ $i->dimensi->label() }}</span>
                                @if ($i->nilai > 0)
                                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">potensi +{{ number_format($i->nilai, 4, ',', '.') }}</span>
                                @endif
                            </p>
                            @if ($i->kegiatan)
                                <p class="mt-1.5 text-xs font-medium text-gray-950 dark:text-white">{{ $i->kegiatan }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Diagram 3 dimensi --}}
        <x-filament::section icon="heroicon-o-chart-bar">
            <x-slot name="heading">Tiga Dimensi Penyusun IDM</x-slot>
            <div class="space-y-5">
                @foreach ($dimensi as $d)
                    <div>
                        <div class="mb-1 flex items-baseline justify-between">
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $d['enum']->label() }} <span class="text-gray-400">({{ $d['enum']->value }})</span></span>
                            <span class="text-sm font-bold tabular-nums text-gray-950 dark:text-white">{{ number_format($d['skor'], 4, ',', '.') }}</span>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full {{ $d['bar'] }}" style="width: {{ max(2, min(100, $d['skor'] * 100)) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Tabel indikator per dimensi --}}
        @foreach ($dimensi as $d)
            @php $rows = $grup->get($d['enum']->value, collect()); @endphp
            @if ($rows->isNotEmpty())
                <x-filament::section collapsible>
                    <x-slot name="heading">{{ $d['enum']->label() }} · {{ $rows->count() }} indikator</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                                    <th class="py-2 pr-3 font-semibold">Indikator</th>
                                    <th class="px-3 py-2 text-center font-semibold">Skor</th>
                                    <th class="px-3 py-2 font-semibold">Kondisi</th>
                                    <th class="px-3 py-2 text-right font-semibold">+Nilai</th>
                                    <th class="pl-3 py-2 font-semibold">Kegiatan &amp; pelaksana</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach ($rows as $i)
                                    <tr class="align-top">
                                        <td class="py-2.5 pr-3 font-medium text-gray-950 dark:text-white">{{ $i->indikator }}</td>
                                        <td class="px-3 py-2.5 text-center">
                                            <x-filament::badge :color="$i->skor >= 5 ? 'success' : ($i->skor >= 3 ? 'warning' : 'danger')">
                                                {{ $i->skor }}/5
                                            </x-filament::badge>
                                        </td>
                                        <td class="px-3 py-2.5 text-gray-600 dark:text-gray-300">{{ $i->keterangan ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums {{ $i->nilai > 0 ? 'font-semibold text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                            {{ $i->nilai > 0 ? '+'.number_format($i->nilai, 4, ',', '.') : '—' }}
                                        </td>
                                        <td class="pl-3 py-2.5">
                                            @if ($i->kegiatan)
                                                <p class="font-medium text-gray-950 dark:text-white">{{ $i->kegiatan }}</p>
                                                @if ($i->pelaksana)
                                                    <p class="mt-0.5 flex flex-wrap gap-1.5">
                                                        @foreach ($i->pelaksana as $level => $instansi)
                                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $level }}: {{ $instansi }}</span>
                                                        @endforeach
                                                    </p>
                                                @endif
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endforeach
    @endif
</x-filament-panels::page>
