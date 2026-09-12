@props([
    'berita',
    'nagari' => null,
])

@php
    $detailUrl = $nagari
        ? \App\Support\PublicNavigation::rute('public.nagari.kabar.detail', $nagari, ['berita' => $berita->slug])
        : route('public.kabar.detail', ['berita' => $berita->slug]);

    $coverUrl = $berita->sampulUrl('card');
    $summary = $berita->ringkasan ?: \Illuminate\Support\Str::limit(strip_tags((string) $berita->konten), 100);
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-primary/40 hover:shadow-lg focus-within:ring-2 focus-within:ring-primary">
    {{-- Foto Sampul / Thumbnail --}}
    <a href="{{ $detailUrl }}" class="relative block aspect-[16/10] w-full overflow-hidden bg-surface-container-high shrink-0 focus:outline-none" tabindex="-1">
        @if($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $berita->judul }}"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/10 via-primary/5 to-surface-container-low text-primary/30">
                <x-heroicon-o-newspaper class="h-10 w-10" />
            </div>
        @endif

        {{-- Badges --}}
        <div class="absolute left-2.5 top-2.5 flex flex-wrap gap-1.5">
            <span class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm {{ $berita->kategori->badgeColor() }}">
                {{ $berita->kategori->getLabel() }}
            </span>
            @if($berita->is_pinned)
                <span class="rounded-full bg-amber-500 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-white shadow-sm flex items-center gap-0.5">
                    <x-heroicon-s-bookmark class="h-2.5 w-2.5" /> Pinned
                </span>
            @endif
        </div>
    </a>

    {{-- Konten Kartu --}}
    <div class="flex flex-1 flex-col justify-between p-4 pb-2.5">
        <div>
            {{-- Metadata Waktu & Sasaran --}}
            <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-medium text-on-surface-variant">
                <span>{{ $berita->published_at?->translatedFormat('d M Y') ?? 'Baru saja' }}</span>
                <span>•</span>
                <span>{{ $berita->readingTime() }} mnt</span>
                @if(! $nagari && ($berita->nagari || ! $berita->semua_nagari))
                    <span>•</span>
                    <span class="truncate max-w-[130px] font-bold text-primary">{{ $berita->targetAudienceLabel() }}</span>
                @endif
            </div>

            {{-- Judul --}}
            <h3 class="mt-1.5 line-clamp-2 min-h-[2.5rem] text-sm font-extrabold leading-snug text-on-surface transition-colors group-hover:text-primary">
                <a href="{{ $detailUrl }}" class="focus:outline-none">
                    {{ $berita->judul }}
                </a>
            </h3>

            {{-- Ringkasan --}}
            <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-on-surface-variant/90">
                {{ $summary }}
            </p>
        </div>
    </div>

    {{-- Footer Kartu --}}
    <div class="mt-auto flex items-center justify-between border-t border-outline-variant/60 px-4 py-2.5 text-[11px] text-on-surface-variant">
        <span class="truncate max-w-[150px] font-medium">
            {{ $berita->authorLabel() }}
        </span>

        <span class="flex shrink-0 items-center gap-1 font-bold text-primary">
            <x-heroicon-o-eye class="h-3.5 w-3.5" />
            <span>{{ number_format($berita->views_count, 0, ',', '.') }}</span>
        </span>
    </div>
</article>
