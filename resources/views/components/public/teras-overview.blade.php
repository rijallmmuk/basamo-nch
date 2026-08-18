@props(['overview', 'nagari' => null])

@php($isNagari = $nagari instanceof \App\Models\Nagari)

<section id="teras" class="relative overflow-hidden bg-background py-section-gap">
    <div class="gonjong-bg absolute inset-0 opacity-30" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            eyebrow="Ringkasan Statistik"
            :title="$isNagari ? 'Ringkasan '.$nagari->nama_lengkap.'.' : 'Ekosistem BASAMO NCH dalam angka.'"
            description="Penduduk, layanan belajar, UMKM, dan indikator pembangunan yang tersedia saat ini."
        />

        <x-public.stat-grid class="stagger-children revealed mt-10" :cols="count($overview['metrics'])">
            @foreach($overview['metrics'] as $metric)
                <x-public.stat-card
                    :label="$metric['label']"
                    :value="$metric['value']"
                    :icon="$metric['icon']"
                    :description="$metric['description']" />
            @endforeach
        </x-public.stat-grid>

        <div class="mt-5 flex flex-wrap gap-5">
            <a href="{{ $isNagari ? \App\Support\PublicNavigation::rute('public.nagari.slc', $nagari) : route('public.slc') }}" class="inline-flex items-center gap-2 text-sm font-bold text-primary hover:underline">
                Lihat katalog belajar <x-heroicon-o-arrow-right class="h-4 w-4" />
            </a>
            <a href="{{ $isNagari ? \App\Support\PublicNavigation::rute('public.nagari.umkm', $nagari) : route('public.umkm') }}" class="inline-flex items-center gap-2 text-sm font-bold text-primary hover:underline">
                Jelajahi lapau usaha <x-heroicon-o-arrow-right class="h-4 w-4" />
            </a>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-12">
            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/8 text-primary shadow-sm"><x-heroicon-o-users class="h-6 w-6" /></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-secondary">SID</p>
                        <h3 class="font-extrabold text-primary">Jenis Kelamin</h3>
                    </div>
                </div>
                <div class="mt-6"><x-public.distribution-list :items="$overview['gender']" /></div>
            </article>

            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-secondary-container text-on-secondary-container shadow-sm"><x-heroicon-o-chart-bar class="h-6 w-6" /></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-secondary">Demografi</p>
                        <h3 class="font-extrabold text-primary">Kelompok Usia</h3>
                    </div>
                </div>
                <div class="mt-6"><x-public.distribution-list :items="$overview['ageGroups']" /></div>
            </article>

            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-tertiary-container text-on-tertiary-container shadow-sm"><x-heroicon-o-academic-cap class="h-6 w-6" /></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-secondary">Kapabilitas</p>
                        <h3 class="font-extrabold text-primary">Pendidikan</h3>
                    </div>
                </div>
                <div class="mt-6"><x-public.distribution-list :items="$overview['education']" /></div>
            </article>

            <article class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 card-shadow transition-all duration-300 hover:shadow-lg lg:col-span-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-container text-on-primary-container shadow-sm"><x-heroicon-o-briefcase class="h-6 w-6" /></span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-secondary">Ekonomi Warga</p>
                        <h3 class="font-extrabold text-primary">Pekerjaan Terbanyak</h3>
                    </div>
                </div>
                <div class="mt-6"><x-public.distribution-list :items="$overview['occupations']" /></div>
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
