@extends('public.layouts.app')

@section('title', 'Nagari Creative Hub & Smart Learning Center')
@section('main-class', 'w-full')

@section('content')
<x-public.ecosystem-hero />

{{-- Beranda hanya memberi ringkasan. Analitik lintas nagari dan sensor lengkap
     memiliki halaman sendiri agar empat kartu pilar tetap menjadi pintu utama. --}}
<section class="relative overflow-hidden border-t border-outline-variant bg-surface-container-lowest py-section-gap">
    <div class="songket-pattern absolute inset-0 opacity-10" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <x-public.section-heading eyebrow="Ringkasan Ekosistem" title="BASAMO NCH dalam angka." description="Gambaran singkat seluruh nagari mitra dari data agregat non-pribadi." />
            <a href="{{ route('public.teras') }}" class="group inline-flex shrink-0 items-center gap-2 rounded-full bg-primary px-6 py-3.5 text-sm font-extrabold text-on-primary shadow-lg transition-all hover:-translate-y-1 hover:shadow-xl">
                Buka Teras Nagari <x-heroicon-o-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
            </a>
        </div>
        <x-public.stat-grid class="mt-10" :cols="count($overview['metrics'])">
            @foreach($overview['metrics'] as $metric)
                <x-public.stat-card :label="$metric['label']" :value="$metric['value']" :icon="$metric['icon']" :description="$metric['description']" />
            @endforeach
        </x-public.stat-grid>
    </div>
</section>

<section class="border-t border-outline-variant bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <x-public.section-heading eyebrow="IoT Nagari" title="Ringkasan titik pantau aktif." description="Status terakhir perangkat EWS nagari. Rincian sensor dan tren tersedia pada halaman IoT." />
            <a href="{{ route('public.iot') }}" class="group inline-flex shrink-0 items-center gap-2 text-sm font-extrabold text-primary hover:underline">Lihat seluruh data IoT <x-heroicon-o-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" /></a>
        </div>
        @if($ewsTitik === [])
            <x-public.empty-state class="mt-8" icon="heroicon-o-signal-slash" title="Belum ada titik pantau aktif" description="Ringkasan akan muncul setelah perangkat aktif terdaftar pada nagari mitra." />
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach(collect($ewsTitik)->take(3) as $titik)
                    @php
                        $pembacaan = $titik['pembacaan'];
                    @endphp
                    <a href="{{ route('public.iot', ['nagari' => $titik['device']->nagari_id]) }}" class="group rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm transition-all hover:-translate-y-1 hover:border-primary/40 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ $titik['device']->nagari?->nama_lengkap }}</p><p class="mt-1 text-2xl font-black {{ $titik['tepercaya'] ? $titik['status']->kelasWarna() : 'text-on-surface-variant' }}">{{ $titik['status']->getLabel() }}</p></div>
                            <span class="mt-1 h-2.5 w-2.5 rounded-full {{ $titik['tepercaya'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div><p class="text-[11px] font-bold uppercase text-on-surface-variant">Tinggi Air</p><p class="font-extrabold text-primary">{{ $pembacaan?->tinggi_air !== null ? number_format($pembacaan->tinggi_air, 0, ',', '.').' cm' : '—' }}</p></div>
                            <div><p class="text-[11px] font-bold uppercase text-on-surface-variant">Curah Hujan</p><p class="font-extrabold text-primary">{{ $pembacaan?->curah_hujan !== null ? number_format($pembacaan->curah_hujan, 0, ',', '.').' mm/jam' : '—' }}</p></div>
                        </div>
                        <p class="mt-4 text-xs {{ $titik['tepercaya'] ? 'text-on-surface-variant' : 'text-amber-700' }}">
                            @if(! $pembacaan)
                                Belum ada pembacaan tersimpan.
                            @elseif(! $titik['terhubung'])
                                Alat tidak terhubung. Angka adalah pembacaan terakhir {{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}, bukan kondisi saat ini.
                            @elseif(! $titik['tepercaya'])
                                Data belum diperbarui. Pembacaan terakhir {{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}.
                            @else
                                Pembacaan {{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}.
                            @endif
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- Analitik lama dipisahkan ke Teras dan IoT; dipertahankan sementara sebagai
     konteks migrasi sampai seluruh regresi lolos. --}}
@if(false)

{{-- ══ TERAS NAGARI — dasbor platform lintas nagari ═════════════════════
     ANGKANYA NYATA, dibaca dari basis data. Sebelumnya bagian ini menampilkan
     angka simulasi yang di-hardcode dengan alasan "sementara untuk presentasi";
     pada situs yang dilayankan ke publik, calon nagari mitra dan pemangku
     kepentingan akan mempercayainya. Rincian per nagari ada di Teras Nagari
     masing-masing situs. ══ --}}
<section id="teras" class="relative overflow-hidden border-t border-outline-variant bg-surface-container-lowest py-section-gap">
    <div class="songket-pattern absolute inset-0 opacity-10" aria-hidden="true"></div>
    <div class="relative z-10 mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            class="mx-auto mb-10 lg:mb-14"
            align="center"
            eyebrow="Teras Nagari"
            title="Sejauh mana ekosistem ini berjalan."
            description="Angka agregat seluruh nagari mitra, tanpa satu pun data pribadi warga."
        />

        <x-public.stat-grid :cols="count($overview['metrics'])">
            @foreach($overview['metrics'] as $metric)
                <x-public.stat-card
                    :label="$metric['label']"
                    :value="$metric['value']"
                    :icon="$metric['icon']"
                    :description="$metric['description']" />
            @endforeach
        </x-public.stat-grid>

        {{-- Capaian SDGs dan sebaran IDM: dua ukuran yang paling sering ditanyakan
             tentang sebuah nagari, disajikan berdampingan.

             Nilainya dihitung di blok PHP di bawah, bukan langsung di atribut
             komponen: pemindai tag Blade berhenti pada tanda lebih-besar yang
             pertama, sehingga perbandingan di dalam atribut memotong tag di
             tengah jalan. --}}
        @php
            $skorSdgs = $overview['sdgs']['filled'] > 0 ? $overview['sdgs']['score'] : null;
            $sebaranIdm = collect($overview['idm']['statuses'])
                ->map(fn (int $jumlah, string $status): array => [
                    'label' => \App\Enums\StatusIdm::tryFrom($status)?->label() ?? $status,
                    'value' => $jumlah,
                ])
                ->values()
                ->all();
        @endphp

        <div class="mt-10 grid gap-5 lg:grid-cols-3">
            <div class="flex flex-col items-center justify-center rounded-3xl border border-outline-variant bg-background p-6 shadow-sm">
                <x-public.donut
                    :value="$skorSdgs"
                    label="Rata-rata Capaian SDGs Desa"
                    :caption="$overview['sdgs']['filled'].' dari '.$overview['sdgs']['total'].' poin terisi'" />
            </div>

            <div class="rounded-3xl border border-outline-variant bg-background p-6 shadow-sm lg:col-span-2">
                <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Status Indeks Desa Membangun</p>
                <p class="mt-1 text-sm text-on-surface-variant">
                    Sebaran status Kemendesa pada {{ $overview['idm']['covered'] }} nagari yang datanya sudah tersedia.
                </p>

                <div class="mt-5">
                    @if($sebaranIdm === [])
                        <x-public.empty-state
                            icon="heroicon-o-trophy"
                            title="Belum ada data IDM"
                            description="Status IDM ditarik dari Kemendesa dan akan muncul di sini setelah nagari mitra pertama tersedia datanya." />
                    @else
                        <x-public.distribution-list :items="$sebaranIdm" />
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabel performa antar nagari, sebangun dengan dasbor superadmin. --}}
        <div class="mt-10">
            <h3 class="text-sm font-bold uppercase tracking-wider text-on-surface-variant">Perbandingan Antar Nagari Mitra</h3>

            @if($performa->isEmpty())
                <x-public.empty-state
                    class="mt-4"
                    icon="heroicon-o-map-pin"
                    title="Belum ada nagari mitra aktif"
                    description="Daftar ini terisi begitu nagari pertama bergabung ke ekosistem BASAMO NCH." />
            @else
                <div class="mt-4 overflow-x-auto rounded-3xl border border-outline-variant bg-background shadow-sm">
                    <table class="w-full min-w-[46rem] text-sm">
                        <thead class="border-b border-outline-variant text-left text-xs uppercase tracking-wider text-on-surface-variant">
                            <tr>
                                <th class="px-5 py-3 font-bold">Nagari</th>
                                <th class="px-5 py-3 font-bold">Penduduk</th>
                                <th class="px-5 py-3 font-bold">Akun Portal</th>
                                <th class="px-5 py-3 font-bold">UMKM</th>
                                <th class="px-5 py-3 font-bold">Produk</th>
                                <th class="px-5 py-3 font-bold">Modul Selesai</th>
                                <th class="px-5 py-3 font-bold">Skor SDGs</th>
                                <th class="px-5 py-3 font-bold">Status IDM</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @foreach($performa as $baris)
                                <tr class="transition-colors hover:bg-surface-container-low">
                                    <td class="px-5 py-3">
                                        <a href="{{ \App\Support\PublicNavigation::rute('public.nagari.home', $baris) }}"
                                           class="font-bold text-primary transition-colors hover:text-primary-highlight">
                                            {{ $baris->nama_lengkap }}
                                        </a>
                                        <span class="block text-xs text-on-surface-variant">{{ $baris->kabupaten }}</span>
                                    </td>
                                    <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->total_penduduk, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->total_warga, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->total_umkm, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->total_produk, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->modul_selesai, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3 tabular-nums">
                                        {{ $baris->skor_sdgs !== null ? number_format((float) $baris->skor_sdgs, 1, ',', '.').'%' : '—' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        {{ $baris->status_idm ? (\App\Enums\StatusIdm::tryFrom((string) $baris->status_idm)?->label() ?? $baris->status_idm) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ══ PEMANTAUAN EWS — ringkasan lintas nagari ═════════════════════════
     Sebelumnya bagian ini berisi tiga kartu karangan: "Cuaca cerah berawan",
     "Sensor normal", "Pembaruan 10 menit lalu". Pada halaman kebencanaan, angka
     karangan bisa dipercaya orang saat keadaan genting. Sekarang isinya pembacaan
     tersimpan dari perangkat sungguhan, berikut keterangan umurnya. ══ --}}
<section id="ews" class="border-t border-outline-variant bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            class="mb-10"
            eyebrow="Pemantauan EWS"
            title="Peringatan dini banjir bandang."
            description="Titik pantau tinggi air, curah hujan, dan status sungai yang terpasang di nagari mitra. Rinciannya tersedia di Teras Nagari masing-masing."
        />

        @if($ewsTitik === [])
            <x-public.empty-state
                icon="heroicon-o-signal-slash"
                title="Belum ada titik pantau terpasang"
                description="Perangkat peringatan dini akan tampil di sini setelah dipasang dan didaftarkan pada nagari mitra." />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($ewsTitik as $titik)
                    @php
                        $pembacaan = $titik['pembacaan'];
                        // Warna status hanya dipakai bila datanya masih boleh dipercaya;
                        // selebihnya dibuat kelabu agar tidak terbaca sebagai kepastian.
                        $warnaStatus = $titik['tepercaya']
                            ? $titik['status']->kelasWarna()
                            : 'text-on-surface-variant';
                    @endphp
                    <a href="{{ \App\Support\PublicNavigation::rute('public.nagari.teras', $titik['device']->nagari) }}#ews"
                       class="group flex h-full flex-col rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm transition-all hover:border-primary/40 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                                    {{ $titik['device']->nagari?->nama ?? 'Nagari' }}
                                </p>
                                <p class="mt-1 text-2xl font-black tracking-tight {{ $warnaStatus }}">
                                    {{ $titik['status']->getLabel() }}
                                </p>
                            </div>
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $titik['tepercaya'] ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                  title="{{ $titik['terhubung'] ? 'Alat terhubung' : 'Alat tidak terhubung' }}"></span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Tinggi Air</p>
                                <p class="font-extrabold tabular-nums text-primary">
                                    {{ $pembacaan?->tinggi_air !== null ? number_format($pembacaan->tinggi_air, 0, ',', '.').' cm' : '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Curah Hujan</p>
                                <p class="font-extrabold tabular-nums text-primary">
                                    {{ $pembacaan?->curah_hujan !== null ? number_format($pembacaan->curah_hujan, 0, ',', '.').' mm' : '—' }}
                                </p>
                            </div>
                        </div>

                        {{-- Umur data selalu disebut: nilai terakhir dari alat yang mati
                             tidak boleh terbaca sebagai kondisi saat ini. --}}
                        <p class="mt-auto min-h-8 pt-4 text-[11px] leading-snug {{ $titik['tepercaya'] ? 'text-on-surface-variant' : 'text-amber-700 dark:text-amber-400' }}">
                            @if(! $pembacaan)
                                Belum ada pembacaan tersimpan.
                            @elseif(! $titik['terhubung'])
                                Alat tidak terhubung. Angka di atas pembacaan terakhir {{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}, bukan kondisi saat ini.
                            @elseif(! $titik['tepercaya'])
                                Data belum diperbarui. Pembacaan terakhir {{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}.
                            @else
                                Pembacaan {{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}.
                            @endif
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endif

{{-- ══ PEMISAH MINANG ══ --}}
<div class="minang-divider" aria-hidden="true"></div>

{{-- ══ NAGARI MITRA — daftar situs tenant aktif ══ --}}
@if($mitraNagari->isNotEmpty())
<section id="mitra" class="relative overflow-hidden bg-background py-section-gap">
    <div class="gonjong-peak absolute right-0 top-0 h-64 w-full -translate-y-32 transform bg-primary/5" aria-hidden="true"></div>
    <div class="relative z-10 mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="mb-12 flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <x-public.section-heading
                eyebrow="Jaringan Mitra"
                title="Nagari yang sudah punya wajah digital."
                description="Setiap nagari mitra memiliki situs resminya sendiri. Kunjungi untuk melihat data Teras, katalog belajar, budaya, dan Lapau UMKM-nya."
            />
            <a href="#peta" class="inline-flex shrink-0 items-center gap-2 font-bold uppercase tracking-wider text-primary transition-all hover:gap-4">
                Lihat di peta <x-heroicon-o-arrow-right class="h-5 w-5" />
            </a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($mitraNagari as $mitra)
                <x-public.partner-nagari-card :nagari="$mitra" />
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══ PETA NAGARI MITRA — interaktif, ditanam langsung (one-page) ══ --}}
<section id="peta" class="relative overflow-hidden bg-primary py-section-gap">
    <div class="songket-pattern absolute inset-0 opacity-10" aria-hidden="true"></div>
    <div class="relative z-10 mx-auto grid max-w-container-page grid-cols-1 items-center gap-14 px-margin-mobile lg:grid-cols-12 lg:gap-20 lg:px-margin-page">
        <div class="order-2 lg:order-1 lg:col-span-7">
            <div class="relative">
                <div class="absolute -inset-1.5 rounded-[2rem] bg-gradient-to-r from-secondary-container/40 to-tertiary/40 opacity-30 blur" aria-hidden="true"></div>
                <div id="peta-bungkus" class="relative overflow-hidden rounded-3xl border border-on-primary/10 bg-surface-container-lowest shadow-2xl">
                    <div id="peta-mitra" class="h-[360px] w-full bg-surface-container sm:h-[420px] lg:h-[520px]"></div>

                    {{-- Tombol kembali + judul kabupaten (mode drill-down) --}}
                    <div id="peta-nav" class="pointer-events-none absolute left-3 top-3 z-[1000] hidden">
                        <button type="button" id="peta-kembali"
                            class="pointer-events-auto inline-flex items-center gap-1.5 rounded-full border border-outline-variant bg-surface-container-lowest/95 px-4 py-1.5 text-xs font-bold text-primary shadow backdrop-blur-sm hover:bg-surface-container-lowest">
                            <x-heroicon-m-arrow-left class="h-3.5 w-3.5" /> Sumatera Barat
                        </button>
                        <p id="peta-judul" class="mt-1.5 inline-block rounded-full bg-surface-container-lowest/95 px-4 py-1 text-xs font-semibold text-on-surface shadow backdrop-blur-sm"></p>
                    </div>

                    {{-- Layar penuh --}}
                    <button type="button" id="peta-fullscreen" aria-label="Layar penuh"
                        class="absolute right-3 top-3 z-[1000] hidden items-center justify-center rounded-full border border-outline-variant bg-surface-container-lowest/95 p-2 text-primary shadow backdrop-blur-sm hover:bg-surface-container-lowest">
                        <x-heroicon-o-arrows-pointing-out class="h-4 w-4" />
                    </button>

                    {{-- Status muat / fallback tanpa JS --}}
                    <div id="peta-status" class="pointer-events-none absolute inset-0 z-[1000] flex items-center justify-center text-sm text-on-surface-variant">
                        Memuat peta…
                    </div>

                    {{-- Legenda --}}
                    <div id="peta-legenda" class="pointer-events-none absolute bottom-3 right-3 z-[1000] rounded-xl border border-outline-variant bg-surface-container-lowest/95 p-3 text-xs text-on-surface-variant shadow backdrop-blur-sm">
                        <p class="mb-1.5 font-bold text-on-surface">Nagari mitra</p>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm bg-tertiary-container"></span> 6 atau lebih</div>
                            <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm bg-on-tertiary-container"></span> 3–5</div>
                            <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm bg-tertiary-fixed"></span> 1–2</div>
                            <div class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-sm bg-surface-container-highest"></span> Belum mitra</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="order-1 space-y-9 lg:order-2 lg:col-span-5">
            <x-public.section-heading
                tone="dark"
                eyebrow="Jaringan Nagari"
                title="Banyak nagari, satu ekosistem digital."
                description="Setiap nagari mitra punya situs resminya sendiri. Temukan lewat peta, kunjungi, dan belanja produk UMKM-nya."
            />
            <ol class="space-y-4 text-on-primary/85">
                @foreach([
                    'Warna menunjukkan sebaran mitra per kabupaten/kota.',
                    'Klik kabupaten/kota untuk melihat wilayah di dalamnya.',
                    'Nagari mitra ditandai hijau, klik untuk membuka situs resminya.',
                ] as $i => $langkah)
                    <li class="flex items-start gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-secondary-container text-sm font-extrabold text-on-secondary-container">{{ $i + 1 }}</span>
                        <span class="pt-1">{{ $langkah }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>

{{-- ══ DIPRAKARSAI & DIDUKUNG — langsung setelah peta ══ --}}
@include('public.partials.collaboration')

{{-- ══ FAQ — daftar accordion (native <details>, tanpa JS). Isi dikelola superadmin
     lewat panel /panel/faqs — sinkron langsung, tanpa cache. ══ --}}
@if($faqs->isNotEmpty())
<section id="faq" class="relative overflow-hidden border-t border-outline-variant bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            class="mx-auto mb-12"
            align="center"
            eyebrow="FAQ"
            title="Pertanyaan Umum"
            description="Jawaban singkat seputar BASAMO NCH, akses warga, dan kemitraan nagari."
        />
        <div class="mx-auto max-w-5xl divide-y divide-outline-variant overflow-hidden rounded-3xl border border-outline-variant bg-surface-container-lowest shadow-sm">
            @foreach($faqs as $faq)
                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5 text-left font-bold text-primary transition-colors hover:bg-surface-container-low group-open:bg-surface-container-low sm:px-8 [&::-webkit-details-marker]:hidden">
                        {{ $faq->pertanyaan }}
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/5 text-primary transition-transform duration-300 group-open:rotate-45">
                            <x-heroicon-m-plus class="h-4 w-4" />
                        </span>
                    </summary>
                    <p class="text-pretty px-6 pb-6 pt-1 leading-relaxed text-on-surface-variant sm:px-8">{{ $faq->jawaban }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══ HUBUNGI KAMI — Jadi Mitra / Keluhan & Saran (tab, vanilla JS — beranda
     publik sengaja TANPA Livewire/Alpine; kirim ke DB, BUKAN email — lihat
     KontakController & panel admin "Kontak Masuk").
     Menggantikan CTA "Jadi Mitra" lama (dihapus 2026-07-13) — sudah fungsional
     lewat form ini, tak perlu kartu terpisah lagi. ══ --}}
<section id="kontak" class="relative overflow-hidden border-t border-outline-variant bg-surface-container-lowest py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            class="mx-auto mb-10"
            align="center"
            eyebrow="Hubungi Kami"
            title="Ingin nagari Anda jadi mitra, atau punya keluhan & saran?"
            description="Isi salah satu formulir di bawah — pesan Anda tersimpan langsung dan ditindaklanjuti tim SLC Basamo NCH."
        />

        @if(session('kontak_sukses'))
            <div class="mx-auto mb-8 flex max-w-2xl items-center gap-3 rounded-2xl border border-tertiary/30 bg-tertiary/10 px-6 py-4 text-sm font-semibold text-tertiary">
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />
                {{ session('kontak_sukses') }}
            </div>
        @endif

        @php
            $tabAwal = old('kategori', 'mitra') === 'mitra' ? 'mitra' : 'lainnya';
            $btnAktif = 'bg-primary text-on-primary shadow-sm';
            $btnPasif = 'text-on-surface-variant hover:text-primary';
        @endphp
        <div class="mx-auto max-w-2xl">
            {{-- Tab (vanilla JS, skripnya didorong ke tumpukan "scripts" di bawah) --}}
            <div class="mb-8 flex gap-2 rounded-full border border-outline-variant bg-background p-1.5">
                <button type="button" data-kontak-tab="mitra"
                    class="flex-1 rounded-full px-5 py-2.5 text-sm font-bold transition-colors {{ $tabAwal === 'mitra' ? $btnAktif : $btnPasif }}">Jadi Mitra</button>
                <button type="button" data-kontak-tab="lainnya"
                    class="flex-1 rounded-full px-5 py-2.5 text-sm font-bold transition-colors {{ $tabAwal === 'lainnya' ? $btnAktif : $btnPasif }}">Keluhan &amp; Saran</button>
            </div>

            @php
                $inputClass = 'w-full rounded-xl border border-control-border bg-background px-4 py-3 text-sm text-on-surface placeholder:text-on-surface-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
                $labelClass = 'mb-1.5 block text-sm font-bold text-on-surface';
                $errorClass = 'mt-1.5 text-xs text-error';
            @endphp

            {{-- Form: Jadi Mitra --}}
            <form data-kontak-form="mitra" method="POST" action="{{ route('public.kontak.store') }}"
                class="{{ $tabAwal === 'mitra' ? '' : 'hidden' }} space-y-4 rounded-3xl border border-outline-variant bg-background p-6 shadow-sm sm:p-8">
                @csrf
                <input type="hidden" name="kategori" value="mitra">

                <div>
                    <label for="mitra_nama" class="{{ $labelClass }}">Nama<span class="text-error">*</span></label>
                    <input type="text" id="mitra_nama" name="nama" required maxlength="150"
                        value="{{ old('kategori') === 'mitra' ? old('nama') : '' }}" class="{{ $inputClass }}">
                    @error('nama') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="mitra_email" class="{{ $labelClass }}">Email<span class="text-error">*</span></label>
                        <input type="email" id="mitra_email" name="email" required maxlength="150"
                            value="{{ old('kategori') === 'mitra' ? old('email') : '' }}" class="{{ $inputClass }}">
                        @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="mitra_no_hp" class="{{ $labelClass }}">No. HP/WhatsApp<span class="text-error">*</span></label>
                        <input type="text" id="mitra_no_hp" name="no_hp" required placeholder="0812…"
                            value="{{ old('kategori') === 'mitra' ? old('no_hp') : '' }}" class="{{ $inputClass }}">
                        @error('no_hp') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="mitra_nagari" class="{{ $labelClass }}">Nama nagari<span class="text-error">*</span></label>
                    <input type="text" id="mitra_nagari" name="nama_nagari" required maxlength="150"
                        value="{{ old('kategori') === 'mitra' ? old('nama_nagari') : '' }}" class="{{ $inputClass }}">
                    @error('nama_nagari') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="mitra_isi" class="{{ $labelClass }}">Ceritakan rencana kemitraannya<span class="text-error">*</span></label>
                    <textarea id="mitra_isi" name="isi" rows="4" required maxlength="5000"
                        class="{{ $inputClass }}">{{ old('kategori') === 'mitra' ? old('isi') : '' }}</textarea>
                    @error('isi') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint">
                    Kirim Pengajuan Mitra <x-heroicon-o-paper-airplane class="h-4 w-4" />
                </button>
            </form>

            {{-- Form: Keluhan & Saran — SATU kategori gabungan (keputusan user
                 2026-07-13), tanpa pilihan jenis lagi. --}}
            <form data-kontak-form="lainnya" method="POST" action="{{ route('public.kontak.store') }}"
                class="{{ $tabAwal === 'lainnya' ? '' : 'hidden' }} space-y-4 rounded-3xl border border-outline-variant bg-background p-6 shadow-sm sm:p-8">
                @csrf
                <input type="hidden" name="kategori" value="keluhan_saran">

                <div>
                    <label for="ls_nama" class="{{ $labelClass }}">Nama<span class="text-error">*</span></label>
                    <input type="text" id="ls_nama" name="nama" required maxlength="150"
                        value="{{ old('kategori') !== 'mitra' ? old('nama') : '' }}" class="{{ $inputClass }}">
                    @error('nama') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ls_email" class="{{ $labelClass }}">Email<span class="text-error">*</span></label>
                        <input type="email" id="ls_email" name="email" required maxlength="150"
                            value="{{ old('kategori') !== 'mitra' ? old('email') : '' }}" class="{{ $inputClass }}">
                        @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ls_no_hp" class="{{ $labelClass }}">No. HP/WhatsApp<span class="text-error">*</span></label>
                        <input type="text" id="ls_no_hp" name="no_hp" required placeholder="0812…"
                            value="{{ old('kategori') !== 'mitra' ? old('no_hp') : '' }}" class="{{ $inputClass }}">
                        @error('no_hp') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="ls_isi" class="{{ $labelClass }}">Isi<span class="text-error">*</span></label>
                    <textarea id="ls_isi" name="isi" rows="4" required maxlength="5000"
                        class="{{ $inputClass }}">{{ old('kategori') !== 'mitra' ? old('isi') : '' }}</textarea>
                    @error('isi') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint">
                    Kirim <x-heroicon-o-paper-airplane class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>

    {{-- Dialog konfirmasi sebelum kirim — vanilla (public site tanpa Alpine),
         gaya disamakan dgn x-portal.confirm-dialog: ikon+judul tengah, Batal kiri/Kirim kanan. --}}
    <div id="kontak-confirm-modal" class="fixed inset-0 z-[70] hidden items-end justify-center p-4 sm:items-center">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" data-kontak-confirm-cancel></div>
        <div class="relative w-full max-w-[26rem] rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 shadow-xl" role="dialog" aria-modal="true">
            <div class="flex flex-col items-center text-center">
                <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <x-heroicon-o-paper-airplane class="h-7 w-7" />
                </span>
                <h3 class="text-lg font-bold text-on-surface">Kirim pesan ini?</h3>
                <p id="kontak-confirm-message" class="mt-1.5 text-sm leading-relaxed text-on-surface-variant"></p>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-center">
                <button type="button" data-kontak-confirm-cancel
                    class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm font-bold text-on-surface-variant transition-colors hover:bg-surface-container-low sm:w-auto">
                    Batal
                </button>
                <button type="button" id="kontak-confirm-submit"
                    class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint sm:w-auto">
                    Ya, Kirim
                </button>
            </div>
        </div>
    </div>
</section>

@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .leaflet-popup-content { margin: 14px 16px; min-width: 200px; max-width: 260px; }
        .leaflet-tooltip { white-space: normal; max-width: 240px; }
        /* leaflet.css memaksa warna <a> biru — kembalikan ke warna tombol kita */
        .leaflet-container a.tautan-situs { color: var(--color-on-secondary-container); }
        #peta-bungkus:fullscreen { border-radius: 0; }
        #peta-bungkus:fullscreen #peta-mitra { height: 100%; }
    </style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Peta nagari mitra (section #peta) — warna token NCH (tertiary green ramp).
    // Robust & hemat: init MALAS via IntersectionObserver (tile & data baru diambil
    // saat section mendekati layar); tanpa Leaflet/fetch gagal → pesan fallback,
    // sisa halaman tak terganggu.
    (function () {
        const el = document.getElementById('peta-mitra');
        const status = document.getElementById('peta-status');
        if (!el || !status) return;

        function initPeta() {
            if (typeof L === 'undefined') { status.textContent = 'Peta tidak dapat dimuat.'; return; }

            const dataUrl = @json(route('public.peta.data'));
            const map = L.map(el, { scrollWheelZoom: false, zoomControl: false }).setView([-0.74, 100.6], 8);
            L.control.zoom({ position: 'bottomleft' }).addTo(map);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            const nav = document.getElementById('peta-nav');
            const judul = document.getElementById('peta-judul');
            const legenda = document.getElementById('peta-legenda');

            // Ramp hijau token NCH: tertiary-container / on-tertiary-container /
            // tertiary-fixed / surface-container-highest (samakan dgn legenda).
            const styles = getComputedStyle(document.documentElement);
            const themeColor = (token) => styles.getPropertyValue(`--color-${token}`).trim();
            const kabColor = (n) => n >= 6 ? themeColor('tertiary-container') : n >= 3 ? themeColor('tertiary-fixed-dim') : n >= 1 ? themeColor('tertiary-fixed') : themeColor('surface-container-highest');
            const kabStyle = (f) => ({ color: themeColor('tertiary'), weight: 1, fillColor: kabColor(f.properties.nagari_terdaftar), fillOpacity: 0.55 });
            const nagariStyle = (f) => ({
                color: f.properties.terdaftar ? themeColor('tertiary') : themeColor('outline'),
                weight: 1,
                fillColor: f.properties.terdaftar ? themeColor('tertiary-fixed-dim') : themeColor('surface-container-highest'),
                fillOpacity: f.properties.terdaftar ? 0.7 : 0.4,
            });

            const kabPopup = (p) => `
                <div class="text-sm text-on-surface">
                    <div class="flex items-center gap-2.5">
                        ${p.logo ? `<img src="${p.logo}" alt="" class="h-8 w-8 shrink-0 object-contain">` : ''}
                        <strong class="leading-snug">${p.nama}</strong>
                    </div>
                    <p class="mt-1.5 text-xs font-bold text-tertiary">Nagari mitra: ${p.nagari_terdaftar}</p>
                    <p class="mt-1 text-[11px] text-outline">Klik untuk lihat wilayah di dalamnya</p>
                </div>`;

            const nagariPopup = (p) => `
                <div class="text-sm text-on-surface">
                    <strong class="block leading-snug">${p.nama}</strong>
                    <p class="mt-0.5 text-xs ${p.terdaftar ? 'font-bold text-tertiary' : 'text-on-surface-variant'}">
                        ${p.terdaftar ? '✓ Mitra platform' : 'Belum menjadi mitra'}
                    </p>
                    ${p.terdaftar && p.url ? `
                        <a href="${p.url}" class="tautan-situs mt-2 inline-flex items-center gap-1 rounded-full bg-secondary-container px-4 py-1.5 text-xs font-extrabold text-on-secondary-container shadow-sm hover:brightness-95">
                            Kunjungi situs nagari →
                        </a>` : ''}
                </div>`;

            let kabLayer = null;
            let nagariLayer = null;

            const fetchJson = (url) => fetch(url)
                .then((r) => { if (! r.ok) throw new Error('HTTP ' + r.status); return r.json(); });

            const hover = (layer) => (feature, lyr) => {
                lyr.on('mouseover', () => lyr.setStyle({ weight: 2, fillOpacity: 0.85 }));
                lyr.on('mouseout', () => layer().resetStyle(lyr));
            };

            function showKab() {
                if (nagariLayer) { map.removeLayer(nagariLayer); nagariLayer = null; }
                kabLayer.addTo(map);
                map.fitBounds(kabLayer.getBounds(), { padding: [20, 20] });
                nav.classList.add('hidden');
                legenda.classList.remove('hidden');
            }

            function openNagari(kab, nama) {
                status.style.display = '';
                status.textContent = 'Memuat nagari…';
                fetchJson(dataUrl + '?kab=' + encodeURIComponent(kab))
                    .then((geojson) => {
                        if (nagariLayer) { map.removeLayer(nagariLayer); }
                        map.removeLayer(kabLayer);
                        nagariLayer = L.geoJSON(geojson, {
                            style: nagariStyle,
                            onEachFeature: (feature, lyr) => {
                                lyr.bindPopup(nagariPopup(feature.properties));
                                hover(() => nagariLayer)(feature, lyr);
                            },
                        }).addTo(map);
                        if (geojson.features.length) {
                            map.fitBounds(nagariLayer.getBounds(), { padding: [20, 20] });
                        }
                        judul.textContent = nama;
                        nav.classList.remove('hidden');
                        legenda.classList.add('hidden');
                        status.style.display = 'none';
                    })
                    .catch(() => { status.textContent = 'Gagal memuat nagari. Coba lagi.'; });
            }

            fetchJson(dataUrl)
                .then((geojson) => {
                    kabLayer = L.geoJSON(geojson, {
                        style: kabStyle,
                        onEachFeature: (feature, lyr) => {
                            lyr.bindTooltip(kabPopup(feature.properties), { sticky: true, direction: 'top', opacity: 1 });
                            lyr.on('click', () => openNagari(feature.properties.kode, feature.properties.nama));
                            hover(() => kabLayer)(feature, lyr);
                        },
                    }).addTo(map);

                    if (geojson.features.length) {
                        map.fitBounds(kabLayer.getBounds(), { padding: [20, 20] });
                    }
                    status.style.display = 'none';
                })
                .catch(() => { status.textContent = 'Gagal memuat peta. Coba muat ulang halaman.'; });

            document.getElementById('peta-kembali').addEventListener('click', showKab);

            // Layar penuh (Fullscreen API native) — tombol muncul hanya bila didukung.
            const bungkus = document.getElementById('peta-bungkus');
            const tombolFs = document.getElementById('peta-fullscreen');
            if (tombolFs && bungkus && bungkus.requestFullscreen) {
                tombolFs.classList.remove('hidden');
                tombolFs.classList.add('flex');
                tombolFs.addEventListener('click', () => {
                    document.fullscreenElement ? document.exitFullscreen() : bungkus.requestFullscreen();
                });
                document.addEventListener('fullscreenchange', () => {
                    setTimeout(() => map.invalidateSize(), 150);
                });
            }
        }

        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                if (entries.some((e) => e.isIntersecting)) { io.disconnect(); initPeta(); }
            }, { rootMargin: '400px' });
            io.observe(el);
        } else {
            initPeta();
        }
    })();
</script>
<script>
    // Reveal-on-scroll (panutan halaman publik): section memudar masuk saat terlihat.
    // Aman-degradasi: tanpa JS/tanpa IntersectionObserver/prefers-reduced-motion →
    // konten langsung tampil (kelas penyembunyi hanya ditambahkan bila fitur ada).
    document.addEventListener('DOMContentLoaded', () => {
        if (!('IntersectionObserver' in window)
            || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('opacity-100');
                    entry.target.classList.remove('opacity-0');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.05, rootMargin: '0px 0px -50px 0px' });

        document.querySelectorAll('main section').forEach((el) => {
            el.classList.add('transition-opacity', 'duration-1000', 'opacity-0');
            observer.observe(el);
        });
    });
</script>
<script>
    // Tab "Hubungi Kami" (Jadi Mitra / Keluhan & Saran) — vanilla, beranda publik
    // TANPA Alpine (cuma dimuat lewat Livewire di portal, lihat catatan komentar
    // section #kontak). Delegasi di document → tetap kerja meski elemen berubah.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-kontak-tab]');
        if (!btn) return;

        const tab = btn.dataset.kontakTab;

        document.querySelectorAll('[data-kontak-tab]').forEach((b) => {
            const aktif = b.dataset.kontakTab === tab;
            b.classList.toggle('bg-primary', aktif);
            b.classList.toggle('text-on-primary', aktif);
            b.classList.toggle('shadow-sm', aktif);
            b.classList.toggle('text-on-surface-variant', !aktif);
            b.classList.toggle('hover:text-primary', !aktif);
        });

        document.querySelectorAll('[data-kontak-form]').forEach((form) => {
            form.classList.toggle('hidden', form.dataset.kontakForm !== tab);
        });
    });
</script>
<script>
    // Konfirmasi sebelum kirim form kontak (Jadi Mitra / Keluhan & Saran).
    (function () {
        const modal = document.getElementById('kontak-confirm-modal');
        const pesan = document.getElementById('kontak-confirm-message');
        const btnKirim = document.getElementById('kontak-confirm-submit');
        let pendingForm = null;

        function buka(form) {
            pendingForm = form;
            pesan.textContent = form.dataset.kontakForm === 'mitra'
                ? 'Pastikan data pengajuan kemitraan sudah benar sebelum dikirim.'
                : 'Pastikan pesan keluhan/saran sudah benar sebelum dikirim.';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function tutup() {
            pendingForm = null;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.querySelectorAll('[data-kontak-form]').forEach((form) => {
            form.addEventListener('submit', (e) => {
                if (form.dataset.kontakConfirmed === '1') return; // sudah dikonfirmasi → lanjut kirim asli
                e.preventDefault();
                if (form.reportValidity()) buka(form);
            });
        });

        btnKirim.addEventListener('click', () => {
            if (!pendingForm) return;
            const form = pendingForm;
            tutup();
            form.dataset.kontakConfirmed = '1';
            form.requestSubmit();
        });

        modal.querySelectorAll('[data-kontak-confirm-cancel]').forEach((el) => el.addEventListener('click', tutup));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) tutup();
        });
    })();
</script>
@endpush
