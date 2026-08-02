@php
    $warga = $rekap['warga'];
    $summary = $rekap['summary'];
    $modules = $rekap['modules'];
    $attempts = $rekap['attempts'];
    $discussions = $rekap['discussions'];
@endphp

<div class="space-y-6">
    <x-filament::section>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xl font-bold text-gray-950 dark:text-white">{{ $warga->name }}</p>
                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                    <span>Nagari: {{ $warga->nagari?->nama ?: '—' }}</span>
                </div>
            </div>
            <div class="text-left text-sm sm:text-right">
                <p class="text-gray-500 dark:text-gray-400">Aktivitas terakhir</p>
                <p class="font-semibold text-gray-950 dark:text-white">
                    {{ $summary['aktivitas_terakhir']?->translatedFormat('d M Y H:i') ?? 'Belum ada aktivitas' }}
                </p>
            </div>
        </div>
    </x-filament::section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-filament::section compact>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pelatihan Tersedia</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $summary['pelatihan_total'] }}</p>
        </x-filament::section>
        <x-filament::section compact>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Progres Modul</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                {{ $summary['modul_selesai'] }} / {{ $summary['modul_total'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $summary['modul_berjalan'] }} sedang dipelajari</p>
        </x-filament::section>
        <x-filament::section compact>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Materi Selesai</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                {{ $summary['materi_selesai'] }} / {{ $summary['materi_total'] }}
            </p>
        </x-filament::section>
        <x-filament::section compact>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Forum Diskusi</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                {{ $summary['diskusi_topik'] + $summary['diskusi_balasan'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $summary['diskusi_topik'] }} topik · {{ $summary['diskusi_balasan'] }} balasan
            </p>
        </x-filament::section>
        <x-filament::section compact>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pre-test</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                {{ $summary['pretest_dikerjakan'] }} / {{ $summary['pretest_total'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">dikerjakan</p>
        </x-filament::section>
        <x-filament::section compact>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Evaluasi Kegiatan</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                {{ $summary['evaluasi_dikerjakan'] }} / {{ $summary['evaluasi_total'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $summary['evaluasi_lulus'] }} lulus</p>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Progres per Modul</x-slot>
        <x-slot name="description">Semua modul yang tersedia untuk warga pada cakupan rekap saat ini.</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-3">Pelatihan / Modul</th>
                        <th class="px-3 py-3 text-center">Status</th>
                        <th class="px-3 py-3 text-center">Materi</th>
                        <th class="px-3 py-3">Pre-test</th>
                        <th class="px-3 py-3">Evaluasi Kegiatan</th>
                        <th class="px-3 py-3">Forum</th>
                        <th class="px-3 py-3">Waktu Progres</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($modules as $row)
                        @php
                            $module = $row['module'];
                            $pretestAttempt = $row['pretest_attempt'];
                            $evaluasiAttempts = $row['evaluasi_attempts'];
                            $latestEvaluasi = $evaluasiAttempts->first();
                            $lulus = $evaluasiAttempts->where('status', \App\Enums\StatusPercobaan::Passed)->isNotEmpty();
                        @endphp
                        <tr class="align-top">
                            <td class="px-3 py-4">
                                <p class="font-semibold text-gray-950 dark:text-white">{{ $module->judul }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $module->pelatihan?->namaTampil() ?? '—' }}
                                </p>
                            </td>
                            <td class="px-3 py-4 text-center">
                                <x-filament::badge :color="$row['status']->getColor()">
                                    {{ $row['status']->getLabel() }}
                                </x-filament::badge>
                            </td>
                            <td class="px-3 py-4 text-center font-semibold text-gray-950 dark:text-white">
                                {{ $row['materi_selesai'] }} / {{ $row['materi_total'] }}
                            </td>
                            <td class="px-3 py-4">
                                @if (! $row['pretest'])
                                    <span class="text-gray-400">Tidak tersedia</span>
                                @elseif ($pretestAttempt)
                                    <p class="font-semibold text-gray-950 dark:text-white">Nilai {{ $pretestAttempt->nilai }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $pretestAttempt->submitted_at?->translatedFormat('d M Y H:i') ?? '—' }}
                                    </p>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">Belum dikerjakan</span>
                                @endif
                            </td>
                            <td class="px-3 py-4">
                                @if (! $row['evaluasi'])
                                    <span class="text-gray-400">Tidak tersedia</span>
                                @elseif ($latestEvaluasi)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-gray-950 dark:text-white">Nilai {{ $latestEvaluasi->nilai }}</span>
                                        <x-filament::badge :color="$lulus ? 'success' : 'danger'">
                                            {{ $lulus ? 'Lulus' : 'Belum Lulus' }}
                                        </x-filament::badge>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $evaluasiAttempts->count() }} percobaan ·
                                        {{ $latestEvaluasi->submitted_at?->translatedFormat('d M Y H:i') ?? '—' }}
                                    </p>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">Belum dikerjakan</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-gray-700 dark:text-gray-200">
                                {{ $row['diskusi_topik'] }} topik<br>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $row['diskusi_balasan'] }} balasan</span>
                            </td>
                            <td class="px-3 py-4 text-xs text-gray-500 dark:text-gray-400">
                                <p>Mulai: {{ $row['started_at']?->translatedFormat('d M Y H:i') ?? '—' }}</p>
                                <p class="mt-1">Terakhir: {{ $row['last_progress_at']?->translatedFormat('d M Y H:i') ?? '—' }}</p>
                                <p class="mt-1">Selesai: {{ $row['completed_at']?->translatedFormat('d M Y H:i') ?? '—' }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada modul yang tersedia dalam cakupan rekap ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Riwayat Pre-test & Evaluasi Kegiatan</x-slot>
            <x-slot name="description">Seluruh percobaan yang tersimpan, diurutkan dari yang terbaru.</x-slot>

            <div class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($attempts as $attempt)
                    <div class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-950 dark:text-white">
                                {{ $attempt->evaluasi?->jenis?->getLabel() ?? 'Evaluasi' }}
                            </p>
                            @if ($attempt->evaluasi?->trashed())
                                <p class="text-xs font-medium text-amber-600 dark:text-amber-400">Evaluasi telah diarsipkan</p>
                            @endif
                            <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                                {{ $attempt->evaluasi?->module?->judul ?? '—' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $attempt->submitted_at?->translatedFormat('d M Y H:i') ?? '—' }}
                            </p>
                        </div>
                        <x-filament::badge :color="$attempt->status->getColor()">
                            Nilai {{ $attempt->nilai }} · {{ $attempt->status->getLabel() }}
                        </x-filament::badge>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada pengerjaan yang tersimpan.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Riwayat Forum Diskusi</x-slot>
            <x-slot name="description">Seluruh topik dan balasan warga, diurutkan dari yang paling lama.</x-slot>

            <div class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($discussions as $discussion)
                    <div class="py-3 first:pt-0 last:pb-0">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <x-filament::badge :color="$discussion->parent_id === null ? 'info' : 'gray'">
                                {{ $discussion->parent_id === null ? 'Topik' : 'Balasan' }}
                            </x-filament::badge>
                            <span>{{ $discussion->module?->judul ?? '—' }}</span>
                            <span>·</span>
                            <span>{{ $discussion->created_at?->translatedFormat('d M Y H:i') ?? '—' }}</span>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-950 dark:text-white">{{ $discussion->isi }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada partisipasi forum diskusi.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</div>
