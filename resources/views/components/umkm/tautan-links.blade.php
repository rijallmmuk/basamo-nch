{{--
    Deret tautan promosi UMKM (sosmed & e-commerce). Menerima Collection hasil
    UmkmProfile::tautanLinks() / UmkmProduct::tautanLinks() — tiap item
    {platform: TautanPlatform, url}. Ikon = Heroicon berbasis kategori (aturan
    proyek Heroicons-only); identitas platform lewat label teks.
--}}
@props(['links'])

@if($links->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
        @foreach($links as $link)
            <a
                href="{{ $link['url'] }}"
                target="_blank"
                rel="noopener nofollow"
                class="inline-flex items-center gap-1.5 rounded-full border border-outline-variant bg-surface-container-lowest px-3 py-1.5 text-xs font-semibold text-on-surface transition-colors hover:border-primary hover:text-primary"
            >
                <x-dynamic-component :component="$link['platform']->icon()" class="h-4 w-4" />
                {{ $link['platform']->getLabel() }}
            </a>
        @endforeach
    </div>
@endif
