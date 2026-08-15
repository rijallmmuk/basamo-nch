@extends('portal.layouts.app')

@section('title', 'Beranda Portal Warga')
@section('main-class', 'py-6 pb-28 lg:pb-10')

@php
    $user = auth()->user();
    $hour = now()->hour;
    $greeting = match (true) {
        $hour >= 4 && $hour < 11 => 'Selamat pagi',
        $hour >= 11 && $hour < 15 => 'Selamat siang',
        $hour >= 15 && $hour < 19 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $firstName = explode(' ', $user->name)[0];
    $totalCount = $modules->count();
    $pelatihans = $pelatihans ?? collect();
@endphp

{{-- ── HERO & PINTASAN BELAJAR TERAKHIR (Quick Shortcut) ─────────── --}}
@section('hero')
<div class="bg-background">
    <div class="mx-auto max-w-[120rem] px-margin-mobile pt-6 sm:px-6 lg:px-margin-desktop">
        <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#003857] via-[#004f7a] to-[#0a6291] p-6 text-white shadow-md md:p-8">
            <div class="relative z-10 flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
                
                {{-- Greetings --}}
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-sky-200 backdrop-blur-sm mb-3">
                        <x-heroicon-s-academic-cap class="h-4 w-4 text-sky-300" />
                        <span>Portal Belajar Warga {{ $user->nagari?->nama_lengkap ?? 'Nagari' }}</span>
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">{{ $greeting }}, {{ $firstName }}!</h1>
                    <p class="mt-2 max-w-xl text-sm text-slate-200">
                        @if($totalCount === 0)
                            Belum ada modul pembelajaran yang dipublikasikan untuk nagari Anda.
                        @elseif($pintasan->jenis === \App\Support\Dashboard\PintasanBelajar::TUNTAS)
                            Selamat! Anda telah menuntaskan seluruh modul literasi digital yang tersedia.
                        @elseif(! $pintasan->ada())
                            Selamat datang! Pilih pelatihan di bawah untuk memulai modul pertama Anda.
                        @else
                            Pantau progres materi dan evaluasi, lalu lanjutkan sesi belajar Anda dengan 1 klik.
                        @endif
                    </p>
                </div>

                {{-- PINTASAN BELAJAR TERAKHIR. Hanya untuk warga yang PERNAH belajar:
                     tanpa riwayat tak ada yang bisa "dilanjutkan", dan menawarkan modul
                     sembarang sebagai lanjutan justru menyesatkan. --}}
                @if($pintasan->ada())
                    @php $jenisPintasan = \App\Support\Dashboard\PintasanBelajar::class; @endphp
                    <div class="shrink-0 w-full lg:w-auto">
                        <div class="rounded-xl border border-white/20 bg-white/10 p-4 backdrop-blur-md shadow-lg lg:w-96">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-amber-300">
                                    <x-heroicon-s-play-circle class="h-4 w-4" />
                                    <span>Pintasan Belajar Terakhir</span>
                                </span>
                                <span class="text-[10px] font-semibold text-slate-300">1-Klik Akses</span>
                            </div>

                            <h3 class="font-bold text-sm text-white truncate">{{ $pintasan->module->judul }}</h3>

                            @switch($pintasan->jenis)
                                @case($jenisPintasan::PRETEST)
                                    <p class="mt-0.5 text-xs text-amber-200">Kerjakan pre-test dulu untuk membuka materinya.</p>
                                    <a href="{{ route('portal.modules.pretest', $pintasan->module) }}"
                                        class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-amber-400 px-4 py-2.5 text-xs font-black text-[#003857] shadow-sm transition-all hover:bg-amber-300 active:scale-95">
                                        <span>Kerjakan Pre-Test</span>
                                        <x-heroicon-s-clipboard-document-list class="h-4 w-4" />
                                    </a>
                                    @break

                                @case($jenisPintasan::MATERI)
                                    <p class="mt-0.5 text-xs text-slate-200 truncate">Materi: <span class="font-semibold text-white">{{ $pintasan->materi->judul }}</span></p>
                                    <a href="{{ route('portal.modules.materi.show', [$pintasan->module, $pintasan->materi]) }}"
                                        class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-amber-400 px-4 py-2.5 text-xs font-black text-[#003857] shadow-sm transition-all hover:bg-amber-300 active:scale-95">
                                        <span>Lanjutkan Belajar Sekarang</span>
                                        <x-heroicon-s-arrow-right class="h-4 w-4" />
                                    </a>
                                    @break

                                @case($jenisPintasan::EVALUASI)
                                    <p class="mt-0.5 text-xs text-amber-200">Materi selesai! Evaluasi Kegiatan siap dikerjakan.</p>
                                    <a href="{{ route('portal.modules.evaluasi', $pintasan->module) }}"
                                        class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-emerald-400 px-4 py-2.5 text-xs font-black text-[#003857] shadow-sm transition-all hover:bg-emerald-300 active:scale-95">
                                        <span>Kerjakan Evaluasi Kegiatan</span>
                                        <x-heroicon-s-clipboard-document-check class="h-4 w-4" />
                                    </a>
                                    @break

                                @default
                                    <p class="mt-0.5 text-xs text-slate-200">Mulai langkah awal pembelajaran modul ini.</p>
                                    <a href="{{ route('portal.modules.show', $pintasan->module) }}"
                                        class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-xs font-black text-[#003857] shadow-sm transition-all hover:bg-slate-100 active:scale-95">
                                        <span>Mulai Belajar Modul</span>
                                        <x-heroicon-s-book-open class="h-4 w-4" />
                                    </a>
                            @endswitch
                        </div>
                    </div>
                @endif

            </div>
            
            <div class="absolute -bottom-12 -right-12 h-48 w-48 rounded-full bg-amber-400/10 blur-2xl"></div>
        </section>
    </div>
</div>
@endsection

{{-- ── CONTENT: STATS, CHARTS, PROGRAMS & MODULES ───────────────────── --}}
@section('content')
<div class="space-y-8">

    {{-- ── 1. RINGKASAN KPIS (STAT CARDS FILAMENT STYLE) ──────────────── --}}
    <section class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        {{-- Card 1: Pelatihan Tuntas --}}
        @php $pelatihanPct = $totalPelatihan > 0 ? (int) round($pelatihanTuntasCount / $totalPelatihan * 100) : 0; @endphp
        <div class="flex flex-col justify-between rounded-xl border border-outline-variant bg-surface p-4 shadow-xs transition-all hover:border-primary/40">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Pelatihan Tuntas</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <x-heroicon-s-academic-cap class="h-4 w-4" />
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-on-surface sm:text-3xl">{{ $pelatihanTuntasCount }} <span class="text-xs font-normal text-on-surface-variant">/ {{ $totalPelatihan }} pelatihan</span></p>
                <div class="mt-2 flex items-center gap-2">
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-container-high">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $pelatihanPct }}%"></div>
                    </div>
                    <span class="text-[11px] font-semibold text-on-surface-variant">{{ $pelatihanPct }}%</span>
                </div>
            </div>
        </div>

        {{-- Card 2: Modul Selesai --}}
        @php $modulPct = $totalCount > 0 ? (int) round($completedCount / $totalCount * 100) : 0; @endphp
        <div class="flex flex-col justify-between rounded-xl border border-outline-variant bg-surface p-4 shadow-xs transition-all hover:border-primary/40">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Modul Selesai</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400">
                    <x-heroicon-s-book-open class="h-4 w-4" />
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-on-surface sm:text-3xl">{{ $completedCount }} <span class="text-xs font-normal text-on-surface-variant">/ {{ $totalCount }} modul</span></p>
                <div class="mt-2 flex items-center gap-2">
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-container-high">
                        <div class="h-full rounded-full bg-sky-500" style="width: {{ $modulPct }}%"></div>
                    </div>
                    <span class="text-[11px] font-semibold text-on-surface-variant">{{ $modulPct }}%</span>
                </div>
            </div>
        </div>

        {{-- Card 3: Evaluasi Lulus --}}
        <div class="flex flex-col justify-between rounded-xl border border-outline-variant bg-surface p-4 shadow-xs transition-all hover:border-primary/40">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Evaluasi Lulus</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <x-heroicon-s-check-badge class="h-4 w-4" />
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-on-surface sm:text-3xl">{{ $passedEvaluasiCount }} <span class="text-xs font-normal text-on-surface-variant">evaluasi</span></p>
                <p class="mt-1 text-[11px] text-on-surface-variant">
                    @if($totalEvaluasiPercobaans > 0)
                        Tingkat Kelulusan: <span class="font-bold text-on-surface">{{ $evaluasiPassRate }}%</span>
                    @else
                        Belum ada evaluasi
                    @endif
                </p>
            </div>
        </div>

        {{-- Card 4: Rata-rata Skor Evaluasi --}}
        <div class="flex flex-col justify-between rounded-xl border border-outline-variant bg-surface p-4 shadow-xs transition-all hover:border-primary/40">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Rata-rata Skor Evaluasi</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <x-heroicon-s-academic-cap class="h-4 w-4" />
                </div>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-black text-on-surface sm:text-3xl">{{ $averageEvaluasiScore }} <span class="text-xs font-normal text-on-surface-variant">/ 100</span></p>
                <p class="mt-1 text-[11px] text-on-surface-variant">Skor Tertinggi: <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $highestEvaluasiScore }}</span></p>
            </div>
        </div>
    </section>

    {{-- ── 2. VISUALISASI CHART PROGRES BELAJAR ───────────────────────── --}}
    @if($totalCount > 0)
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        {{-- Chart 1: Bar Chart Progres Halaman Per Modul (2 Columns) --}}
        <div class="lg:col-span-2 rounded-xl border border-outline-variant bg-surface p-5 shadow-xs">
            <div class="flex items-center justify-between border-b border-outline-variant pb-3 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-on-surface flex items-center gap-2">
                        <x-heroicon-s-chart-bar class="h-4 w-4 text-primary" />
                        <span>Grafik Progres Pembelajaran Per Modul</span>
                    </h2>
                    <p class="text-[11px] text-on-surface-variant">Persentase halaman materi yang telah Anda selesaikan.</p>
                </div>
                <span class="inline-flex items-center rounded-md bg-surface-container-high px-2.5 py-1 text-[11px] font-semibold text-on-surface">
                    {{ $doneMateris }} / {{ $totalMateris }} Halaman
                </span>
            </div>

            {{-- Fallback HTML/SVG Chart + Container ApexCharts --}}
            <div class="relative min-h-[220px]">
                <div id="moduleProgressChart"></div>
                
                {{-- Fallback Visual Progress List --}}
                <div id="moduleProgressFallback" class="space-y-3 pt-1">
                    @foreach($modules as $mod)
                        @php
                            $pCount = $mod->materis_count;
                            $pDone = count($mod->progress->first()?->halaman_selesai ?? []);
                            $pct = $pCount > 0 ? (int) round($pDone / $pCount * 100) : 0;
                            $barColor = $pct === 100 ? 'bg-emerald-500' : ($pct > 0 ? 'bg-sky-500' : 'bg-slate-300 dark:bg-slate-700');
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-bold text-on-surface truncate max-w-[70%]">{{ $mod->judul }}</span>
                                <span class="font-semibold text-on-surface-variant">{{ $pDone }}/{{ $pCount }} materi ({{ $pct }}%)</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-surface-container-high">
                                <div class="h-full rounded-full {{ $barColor }} transition-all duration-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Chart 2: Donut Chart Distribusi Status Modul (1 Column) --}}
        <div class="rounded-xl border border-outline-variant bg-surface p-5 shadow-xs flex flex-col justify-between">
            <div class="border-b border-outline-variant pb-3 mb-4">
                <h2 class="text-sm font-bold text-on-surface flex items-center gap-2">
                    <x-heroicon-s-chart-pie class="h-4 w-4 text-amber-500" />
                    <span>Distribusi Status Modul</span>
                </h2>
                <p class="text-[11px] text-on-surface-variant">Ringkasan status seluruh modul Anda.</p>
            </div>

            <div class="relative min-h-[220px] flex flex-col justify-center">
                <div id="statusDonutChart"></div>

                {{-- Fallback Status List --}}
                <div id="statusDonutFallback" class="space-y-2.5 py-2">
                    <div class="flex items-center justify-between rounded-lg border border-outline-variant p-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-semibold text-on-surface">Selesai</span>
                        </div>
                        <span class="text-xs font-bold text-on-surface">{{ $statusCounts['completed'] ?? 0 }} Modul</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-outline-variant p-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-sky-500"></span>
                            <span class="text-xs font-semibold text-on-surface">Sedang Dipelajari</span>
                        </div>
                        <span class="text-xs font-bold text-on-surface">{{ $statusCounts['in_progress'] ?? 0 }} Modul</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-outline-variant p-2.5">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            <span class="text-xs font-semibold text-on-surface">Belum Dimulai</span>
                        </div>
                        <span class="text-xs font-bold text-on-surface">{{ $statusCounts['available'] ?? 0 }} Modul</span>
                    </div>
                    @if(($statusCounts['locked'] ?? 0) > 0)
                        <div class="flex items-center justify-between rounded-lg border border-outline-variant p-2.5">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-slate-400"></span>
                                <span class="text-xs font-semibold text-on-surface">Terkunci</span>
                            </div>
                            <span class="text-xs font-bold text-on-surface">{{ $statusCounts['locked'] ?? 0 }} Modul</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </section>
    @endif

    {{-- ── 3. PELATIHAN AKTIF NAGARI ─────────────────────────────────── --}}
    <section class="overflow-hidden rounded-xl border border-outline-variant bg-surface shadow-xs">
        <div class="flex items-center justify-between border-b border-outline-variant bg-surface-bright p-4">
            <h3 class="flex min-w-0 items-center gap-2 text-sm font-bold text-on-surface">
                <x-heroicon-s-academic-cap class="h-5 w-5 shrink-0 text-primary" />
                <span class="truncate">Pelatihan Aktif Nagari</span>
            </h3>
            <a href="{{ route('portal.pelatihan.index') }}" class="shrink-0 text-xs font-bold uppercase tracking-wider text-primary transition-colors hover:text-surface-tint">Lihat Semua Pelatihan →</a>
        </div>

        @if($pelatihans->isEmpty())
            <div class="flex flex-1 flex-col items-center justify-center px-4 py-12 text-center">
                <x-heroicon-o-academic-cap class="h-10 w-10 text-outline-variant" />
                <p class="mt-3 font-semibold text-on-surface">Belum ada pelatihan</p>
                <p class="mt-1 text-xs text-on-surface-variant">Pelatihan akan muncul setelah pengelola membukanya.</p>
            </div>
        @else
            <div class="flex flex-1 flex-col divide-y divide-outline-variant">
                @foreach($pelatihans->take(3) as $prog)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-4 py-3.5 hover:bg-surface-container-lowest transition-colors">
                        {{-- Cover Image Thumbnail + Info --}}
                        <div class="flex items-center gap-3.5 min-w-0 flex-1">
                            <div class="relative h-16 w-24 sm:h-20 sm:w-32 shrink-0 overflow-hidden rounded-lg ring-1 ring-outline-variant bg-surface-container-high">
                                @if ($prog->punyaCover())
                                    <img src="{{ $prog->coverUrl() }}" alt="Cover {{ $prog->temaNama() }}" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <x-slc.tema-cover :nama="$prog->temaNama()" ringkas class="h-full w-full object-cover" />
                                @endif
                                @if(! $prog->dapatDimasuki())
                                    <span class="absolute left-1 top-1 flex items-center gap-1 rounded bg-amber-500/90 px-1.5 py-0.5 text-[9px] font-bold text-white shadow-xs">
                                        <x-heroicon-s-lock-closed class="h-2.5 w-2.5" />
                                        <span>Terkunci</span>
                                    </span>
                                @else
                                    <span class="absolute left-1 top-1 flex items-center gap-1 rounded bg-emerald-600/90 px-1.5 py-0.5 text-[9px] font-bold text-white shadow-xs">
                                        <x-heroicon-s-check-circle class="h-2.5 w-2.5" />
                                        <span>Terbuka</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Sasaran nagari tidak disebut: seluruh daftar ini memang
                                 sudah ter-scope ke nagari warga yang sedang melihatnya. --}}
                            <div class="min-w-0 flex-1 space-y-1">
                                <h4 class="font-bold text-sm text-on-surface truncate">{{ $prog->temaNama() }}</h4>

                                @if($prog->deskripsi)
                                    <p class="text-xs text-on-surface-variant line-clamp-1">{{ strip_tags($prog->deskripsi) }}</p>
                                @endif

                                <p class="text-[11px] font-medium text-on-surface-variant">Modul: <strong>{{ $prog->modules_count }}</strong></p>

                                <x-slc.pengelola-list :participants="$prog->participants()" :max="2" compact />
                            </div>
                        </div>

                        {{-- Action Button --}}
                        <div class="flex items-center gap-3 shrink-0 self-end sm:self-center">
                            <a href="{{ route('portal.pelatihan.show', $prog) }}"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-3 py-1.5 text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors">
                                <span>Detail Pelatihan</span>
                                <x-heroicon-o-arrow-right class="h-3.5 w-3.5 text-primary" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

</div>

{{-- APEXCHARTS INTEGRATION WITH FALLBACK REMOVAL --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const moduleLabels = @json($chartModuleLabels);
        const moduleData = @json($chartModuleData);
        const statusCounts = @json($statusCounts);

        if (typeof ApexCharts !== 'undefined' && moduleLabels.length > 0) {
            // Sembunyikan fallback static list jika ApexCharts berhasil dimuat
            const fbBar = document.getElementById('moduleProgressFallback');
            const fbDonut = document.getElementById('statusDonutFallback');
            if (fbBar) fbBar.style.display = 'none';
            if (fbDonut) fbDonut.style.display = 'none';

            // 1. Bar Chart Progres Modul
            const barOptions = {
                series: [{
                    name: 'Progres Selesai',
                    data: moduleData
                }],
                chart: {
                    type: 'bar',
                    height: 220,
                    toolbar: { show: false },
                    fontFamily: 'inherit'
                },
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '45%',
                        distributed: true,
                        dataLabels: { position: 'top' }
                    }
                },
                colors: moduleData.map(val => val === 100 ? '#10b981' : (val > 0 ? '#0284c7' : '#94a3b8')),
                dataLabels: {
                    enabled: true,
                    formatter: function (val) { return val + "%"; },
                    offsetY: -20,
                    style: { fontSize: '11px', colors: ["#64748b"] }
                },
                legend: { show: false },
                xaxis: {
                    categories: moduleLabels.map(title => title.length > 20 ? title.substring(0, 18) + '...' : title),
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { fontSize: '11px' } }
                },
                yaxis: {
                    max: 100,
                    labels: { formatter: function (val) { return val + "%"; } }
                },
                grid: { borderColor: '#e2e8f0', strokeDashArray: 4 }
            };
            const barChart = new ApexCharts(document.querySelector("#moduleProgressChart"), barOptions);
            barChart.render();

            // 2. Donut Chart Status Modul
            const donutOptions = {
                series: [
                    statusCounts.completed || 0,
                    statusCounts.in_progress || 0,
                    statusCounts.available || 0,
                    statusCounts.locked || 0
                ],
                labels: ['Selesai', 'Sedang Dipelajari', 'Belum Dimulai', 'Terkunci'],
                chart: {
                    type: 'donut',
                    height: 220,
                    fontFamily: 'inherit'
                },
                colors: ['#10b981', '#0284c7', '#f59e0b', '#94a3b8'],
                legend: { position: 'bottom', fontSize: '11px' },
                dataLabels: { enabled: true, style: { fontSize: '11px' } },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Modul',
                                    formatter: function () { return moduleLabels.length; }
                                }
                            }
                        }
                    }
                }
            };
            const donutChart = new ApexCharts(document.querySelector("#statusDonutChart"), donutOptions);
            donutChart.render();
        }
    });
</script>
@endpush
@endsection
