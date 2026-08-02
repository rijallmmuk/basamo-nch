@props(['nagari'])

@php
    $sampul = $nagari->sampulUrls()->first();
    $logo = $nagari->kabupatenLogoUrl();
@endphp

<a
    href="{{ route('public.nagari.home', $nagari) }}"
    {{ $attributes->class([
        'group relative flex h-full flex-col overflow-hidden rounded-3xl border border-outline-variant/80 bg-surface-container-lowest shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-primary/40 hover:shadow-xl',
    ]) }}
>
    <div class="relative aspect-[16/10] overflow-hidden bg-primary/10">
        @if($sampul)
            <img src="{{ $sampul }}" alt="" class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105" loading="lazy" decoding="async">
        @else
            <div class="gonjong-bg absolute inset-0 opacity-40" aria-hidden="true"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <x-heroicon-o-map-pin class="h-10 w-10 text-primary/40 transition-transform duration-500 group-hover:scale-125" />
            </div>
        @endif

        @if($logo)
            <img
                src="{{ $logo }}"
                alt="Logo {{ $nagari->kabupaten }}"
                class="absolute left-3 top-3 h-10 w-10 rounded-xl bg-white/95 p-1 object-contain shadow-md backdrop-blur-sm transition-transform duration-300 group-hover:scale-110"
                loading="lazy"
            >
        @endif

        <span class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-surface-container-lowest/90 px-2.5 py-1 text-[11px] font-extrabold text-primary backdrop-blur-md shadow-xs">
            <span class="h-1.5 w-1.5 rounded-full bg-tertiary animate-pulse"></span> Situs Resmi
        </span>
    </div>

    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs font-bold uppercase tracking-widest text-secondary group-hover:text-primary transition-colors">Nagari Mitra</p>
        <h3 class="mt-2 text-lg font-extrabold tracking-tight text-primary transition-colors group-hover:text-primary-highlight">
            {{ $nagari->nama_lengkap }}
        </h3>
        <p class="mt-1 text-sm font-medium text-on-surface-variant">
            {{ $nagari->kecamatan }} · {{ $nagari->kabupaten }}
        </p>
        <span class="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-bold text-primary group-hover:text-primary-highlight">
            Kunjungi situs
            <x-heroicon-o-arrow-right class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1.5" />
        </span>
    </div>
</a>

