@props(['overview', 'nagari' => null, 'global' => false])

@php($isNagari = $nagari instanceof \App\Models\Nagari)

<section id="teras" class="relative overflow-hidden bg-background py-section-gap">
    <div class="gonjong-bg absolute inset-0 opacity-30" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
            <x-public.section-heading
                eyebrow="Ringkasan Statistik"
                :title="$isNagari ? 'Angka utama '.$nagari->nama_lengkap.'.' : 'Potret terbuka ekosistem BASAMO NCH.'"
                description="Data ditampilkan dalam bentuk agregat untuk mendukung keterbukaan informasi tanpa mengekspos identitas pribadi warga."
            />
            @unless($isNagari)
                <div class="inline-flex w-fit items-center gap-2 rounded-full border border-success-container/40 bg-success-container px-4 py-2 text-xs font-bold text-on-success-container shadow-sm backdrop-blur-sm transition-transform hover:scale-105">
                    <x-heroicon-s-shield-check class="h-4 w-4 animate-pulse" /> Data agregat non-pribadi
                </div>
            @endunless
        </div>

        <x-public.stat-grid class="stagger-children revealed mt-10" :cols="count($overview['metrics'])">
            @foreach($overview['metrics'] as $metric)
                <x-public.stat-card
                    :label="$metric['label']"
                    :value="$metric['value']"
                    :icon="$metric['icon']"
                    :description="$metric['description']" />
            @endforeach
        </x-public.stat-grid>

        <div class="mt-8 grid gap-5 lg:grid-cols-12">
            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/8 text-primary shadow-sm"><x-heroicon-o-users class="h-6 w-6" /></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-secondary">SID</p>
                        <h3 class="font-extrabold text-primary">Jenis Kelamin</h3>
                    </div>
                </div>
                <div class="mt-6"><x-public.distribution-list :items="$overview['gender']" /></div>
            </article>

            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-secondary-container text-on-secondary-container shadow-sm"><x-heroicon-o-chart-bar class="h-6 w-6" /></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-secondary">Demografi</p>
                        <h3 class="font-extrabold text-primary">Kelompok Usia</h3>
                    </div>
                </div>
                <div class="mt-6"><x-public.distribution-list :items="$overview['ageGroups']" /></div>
            </article>

            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-tertiary-container text-on-tertiary-container shadow-sm"><x-heroicon-o-academic-cap class="h-6 w-6" /></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-secondary">Kapabilitas</p>
                        <h3 class="font-extrabold text-primary">Pendidikan</h3>
                    </div>
                </div>
                <div class="mt-6"><x-public.distribution-list :items="$overview['education']" /></div>
            </article>

            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-container text-on-primary-container shadow-sm"><x-heroicon-o-briefcase class="h-6 w-6" /></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-secondary">Ekonomi Warga</p>
                        <h3 class="font-extrabold text-primary">Pekerjaan Terbanyak</h3>
                    </div>
                </div>
                <div class="mt-6"><x-public.distribution-list :items="$overview['occupations']" /></div>
            </article>

            <article class="relative overflow-hidden rounded-3xl bg-primary p-6 text-on-primary shadow-xl transition-all duration-300 hover:shadow-2xl lg:col-span-4">
                <div class="songket-pattern absolute inset-0 opacity-10" aria-hidden="true"></div>
                <div class="floating-orb floating-orb--gold -right-12 -top-12 h-40 w-40 bg-secondary-container/20"></div>
                <div class="relative">
                    <p class="text-xs font-bold uppercase tracking-widest text-secondary-container">Smart Learning Center</p>
                    <h3 class="mt-2 text-xl font-extrabold">Aktivitas Pembelajaran</h3>
                    <dl class="mt-6 grid grid-cols-2 gap-4">
                        @foreach([
                            ['Pelatihan', $overview['learning']['pelatihans']],
                            ['Modul terbit', $overview['learning']['modules']],
                            ['Modul selesai', $overview['learning']['completed_modules']],
                            ['Percobaan kuis', $overview['learning']['evaluasi_percobaans']],
                        ] as [$label, $value])
                            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm transition-transform hover:scale-105">
                                <dd class="text-2xl font-extrabold text-secondary-container count-up" data-count-target="{{ $value }}">{{ number_format($value, 0, ',', '.') }}</dd>
                                <dt class="mt-1 text-xs font-semibold text-on-primary/75">{{ $label }}</dt>
                            </div>
                        @endforeach
                    </dl>
                    <a href="{{ $global ? route('public.slc', $isNagari ? ['nagari' => $nagari->id] : []) : ($isNagari ? \App\Support\PublicNavigation::rute('public.nagari.slc', $nagari) : route('public.slc')) }}" class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-secondary-container transition-all hover:gap-3">
                        Lihat katalog belajar <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </a>
                </div>
            </article>

            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-4">
                <p class="text-xs font-bold uppercase tracking-widest text-secondary">Lapau Nagari</p>
                <h3 class="mt-2 text-xl font-extrabold text-primary">Ekonomi Digital</h3>
                <dl class="mt-6 grid grid-cols-2 gap-3">
                    @foreach([
                        ['Rumah UMKM', $overview['economy']['profiles']],
                        ['Produk terbit', $overview['economy']['products']],
                        ['Kunjungan produk', $overview['economy']['product_views']],
                        ['UMKM dengan QR', $overview['economy']['qr_profiles']],
                    ] as [$label, $value])
                        <div class="rounded-2xl bg-surface-container-low p-4 transition-transform hover:scale-105">
                            <dd class="text-2xl font-extrabold text-primary count-up" data-count-target="{{ $value }}">{{ number_format($value, 0, ',', '.') }}</dd>
                            <dt class="mt-1 text-xs font-semibold text-on-surface-variant">{{ $label }}</dt>
                        </div>
                    @endforeach
                </dl>
                <a href="{{ $global ? route('public.umkm', $isNagari ? ['nagari' => $nagari->id] : []) : ($isNagari ? \App\Support\PublicNavigation::rute('public.nagari.umkm', $nagari) : route('public.umkm')) }}" class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-primary transition-all hover:gap-3">
                    Jelajahi rumah UMKM <x-heroicon-o-arrow-right class="h-4 w-4" />
                </a>
            </article>

            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-12">
                <p class="text-xs font-bold uppercase tracking-widest text-secondary">Pembangunan dan lingkungan</p>
                <h3 class="mt-2 text-xl font-extrabold text-primary">SDGs dan IDM</h3>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div class="rounded-2xl border border-outline-variant/60 bg-surface-container-low/50 p-4.5">
                        <div class="flex items-end justify-between gap-4">
                            <span class="text-sm font-semibold text-on-surface-variant">Skor SDGs</span>
                            <strong class="text-3xl font-extrabold text-primary">{{ number_format($overview['sdgs']['score'], 1, ',', '.') }}</strong>
                        </div>
                        <p class="mt-2 text-xs text-on-surface-muted">{{ $overview['sdgs']['filled'] }} dari {{ $overview['sdgs']['total'] }} poin nagari terdata</p>
                    </div>
                    <div class="rounded-2xl bg-surface-container-low p-4.5">
                        @if($isNagari && $overview['idm']['latest'])
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-on-surface-muted">IDM {{ $overview['idm']['latest']['tahun'] }}</p>
                                    <p class="mt-1 text-lg font-extrabold text-primary">{{ $overview['idm']['latest']['status'] }}</p>
                                </div>
                                <p class="text-2xl font-extrabold text-primary">{{ number_format((float) $overview['idm']['latest']['skor'], 4, ',', '.') }}</p>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs">
                                @foreach([
                                    ['IKS', $overview['idm']['latest']['skor_iks']],
                                    ['IKE', $overview['idm']['latest']['skor_ike']],
                                    ['IKL', $overview['idm']['latest']['skor_ikl']],
                                ] as [$label, $value])
                                    <div class="rounded-xl bg-surface-container-lowest p-2 shadow-xs">
                                        <p class="font-extrabold text-primary">{{ $value !== null ? number_format((float) $value, 4, ',', '.') : '—' }}</p>
                                        <p class="mt-1 text-on-surface-muted">{{ $label }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-2xl font-extrabold text-primary">{{ number_format($overview['idm']['covered'], 0, ',', '.') }}</p>
                            <p class="mt-1 text-xs font-semibold text-on-surface-variant">{{ $isNagari ? 'Belum ada data IDM' : 'Nagari memiliki data IDM' }}</p>
                            @if(! $isNagari && $overview['idm']['statuses'] !== [])
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($overview['idm']['statuses'] as $status => $total)
                                        <span class="rounded-full bg-surface-container-lowest px-2.5 py-1 text-[11px] font-bold text-on-surface-variant shadow-xs">{{ $status }} · {{ $total }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </article>
        </div>

    </div>
</section>
