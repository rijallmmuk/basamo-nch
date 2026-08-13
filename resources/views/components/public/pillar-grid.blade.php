@props(['nagari' => null])

@php
    $isNagari = $nagari instanceof \App\Models\Nagari;
    $pillars = [
        [
            'number' => '01',
            'name' => 'Teras Nagari',
            'function' => 'Pusat Data & Komando',
            'implementation' => 'Dashboard analitik, SDGs Desa, SID, & statistik nagari.',
            'icon' => 'heroicon-o-presentation-chart-line',
            'href' => $isNagari
                ? (request()->routeIs('*.fallback')
                    ? route('public.nagari.teras.fallback', $nagari)
                    : route('public.nagari.teras', $nagari))
                : route('public.teras'),
            'iconBg' => 'bg-primary text-on-primary',
            'badgeColor' => 'bg-primary/10 text-primary border border-primary/20',
            'btnColor' => 'bg-primary text-on-primary group-hover:bg-primary-highlight',
            'ctaText' => 'Jelajahi Teras Nagari',
            'isComingSoon' => false,
        ],
        [
            'number' => '02',
            'name' => 'Medan Nan Balinduang',
            'function' => 'Smart Learning Center',
            'implementation' => 'LMS, Pelatihan digital, modul, & Sertifikasi warga.',
            'icon' => 'heroicon-o-academic-cap',
            'href' => $isNagari
                ? (request()->routeIs('*.fallback')
                    ? route('public.nagari.slc.fallback', $nagari)
                    : route('public.nagari.slc', $nagari))
                : route('public.slc'),
            'iconBg' => 'bg-emerald-600 text-white',
            'badgeColor' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'btnColor' => 'bg-emerald-600 text-white group-hover:bg-emerald-700',
            'ctaText' => 'Masuk Ruang Belajar',
            'isComingSoon' => false,
        ],
        [
            'number' => '03',
            'name' => 'Medan Nan Bapaneh',
            'function' => 'Budaya & Inovasi',
            'implementation' => 'Arsip budaya, media digital, & publikasi nagari.',
            'icon' => 'heroicon-o-sparkles',
            'href' => $isNagari
                ? (request()->routeIs('*.fallback')
                    ? route('public.nagari.bapaneh.fallback', $nagari)
                    : route('public.nagari.bapaneh', $nagari))
                // Dulu menunjuk `#peta`, bagian beranda yang isinya sama sekali lain,
                // semata karena induk belum punya halaman pilar ini.
                : route('public.bapaneh'),
            'iconBg' => 'bg-sky-500 text-white',
            'badgeColor' => 'bg-sky-50 text-sky-800 border border-sky-200',
            'btnColor' => 'bg-sky-600 text-white group-hover:bg-sky-700',
            'ctaText' => 'Coming Soon (Dalam Perencanaan)',
            'isComingSoon' => true,
        ],
        [
            'number' => '04',
            'name' => 'Lapau Nagari',
            'function' => 'Ekonomi Digital',
            'implementation' => 'Marketplace, etalase UMKM, & QR Traceability.',
            'icon' => 'heroicon-o-building-storefront',
            'href' => $isNagari
                ? (request()->routeIs('*.fallback')
                    ? route('public.nagari.umkm.fallback', $nagari)
                    : route('public.nagari.umkm', $nagari))
                : route('public.umkm'),
            'iconBg' => 'bg-amber-500 text-white',
            'badgeColor' => 'bg-amber-50 text-amber-800 border border-amber-200',
            'btnColor' => 'bg-amber-500 text-white group-hover:bg-amber-600',
            'ctaText' => 'Jelajahi Lapau Nagari',
            'isComingSoon' => false,
        ],
    ];
@endphp

<div {{ $attributes->class(['grid grid-cols-1 items-stretch gap-6 sm:grid-cols-2']) }}>
    @foreach($pillars as $pillar)
        <a href="{{ $pillar['href'] }}"
           aria-label="{{ $pillar['ctaText'] }}"
           class="group relative flex min-h-[18rem] cursor-pointer flex-col justify-between overflow-hidden rounded-3xl border-2 border-outline-variant/70 bg-surface-container-lowest p-6 text-left shadow-lg transition-all duration-300 hover:-translate-y-2 hover:border-primary/40 hover:shadow-2xl focus-visible:outline focus-visible:outline-4 focus-visible:outline-secondary-container">

            <div>
                <div class="flex items-center justify-between">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl shadow-md transition-transform duration-300 group-hover:scale-110 {{ $pillar['iconBg'] }}">
                        <x-dynamic-component :component="$pillar['icon']" class="h-7 w-7" />
                    </span>

                    <div class="flex items-center gap-2">
                        @if($pillar['isComingSoon'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/90 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-white shadow-xs">
                                <x-heroicon-s-clock class="h-3 w-3" />
                                <span>Coming Soon</span>
                            </span>
                        @endif
                        <span class="rounded-full px-3 py-1 text-xs font-black tracking-wider uppercase {{ $pillar['badgeColor'] }}">
                            Pilar {{ $pillar['number'] }}
                        </span>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-xs font-extrabold uppercase tracking-widest text-on-surface-variant/80">
                        {{ $pillar['function'] }}
                    </p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-primary group-hover:text-primary-highlight transition-colors flex items-center gap-2">
                        <span>{{ $pillar['name'] }}</span>
                        <x-heroicon-o-arrow-up-right class="h-5 w-5 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 text-secondary-container" />
                    </h2>
                    <p class="mt-2 text-sm leading-relaxed text-on-surface-variant font-normal">
                        {{ $pillar['implementation'] }}
                    </p>
                </div>
            </div>

            <div class="mt-6 border-t border-outline-variant/40 pt-4">
                <span class="flex w-full items-center justify-between gap-2 rounded-xl px-4 py-3 text-xs font-extrabold shadow-sm transition-all duration-300 group-hover:shadow-md {{ $pillar['btnColor'] }}">
                    <span>{{ $pillar['ctaText'] }}</span>
                    <x-heroicon-o-arrow-right class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                </span>
            </div>
        </a>
    @endforeach
</div>
