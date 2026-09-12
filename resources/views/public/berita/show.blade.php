@extends('public.layouts.app')

@php
    $canonical = url()->current();
    $coverUrl = $berita->sampulUrl('hero');
    $summary = $berita->ringkasan ?: \Illuminate\Support\Str::limit(strip_tags((string) $berita->konten), 160);
    $backUrl = $nagari ? \App\Support\PublicNavigation::rute('public.nagari.kabar', $nagari) : route('public.kabar');
    
    $detailUrl = function (\App\Models\Berita $item) use ($nagari) {
        if ($nagari) {
            return \App\Support\PublicNavigation::rute('public.nagari.kabar.detail', $nagari, ['berita' => $item->slug]);
        }
        return route('public.kabar.detail', ['berita' => $item->slug]);
    };
@endphp

@section('title', $berita->judul)
@section('meta_description', $summary)
@if($coverUrl)
    @section('meta_image', $coverUrl)
@endif
@section('main-class', 'w-full')

@push('structured-data')
    <x-public.structured-data :data="[
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $berita->judul,
        'description' => $summary,
        'image' => $coverUrl ? [$coverUrl] : [],
        'datePublished' => $berita->published_at?->toIso8601String() ?? $berita->created_at->toIso8601String(),
        'dateModified' => $berita->updated_at->toIso8601String(),
        'author' => [
            '@type' => 'Person',
            'name' => $berita->authorLabel(),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $nagari ? $nagari->nama_lengkap : 'BASAMO Nagari Creative Hub',
            'url' => rtrim((string) config('app.url'), '/'),
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $canonical,
        ],
    ]" />
@endpush

@section('content')
{{-- ══ PRATINJAU DRAF BANNER (KHUSUS PENGELOLA) ════════════════════════════ --}}
@if($isDraftPreview ?? false)
    <div class="bg-amber-500 py-3 text-center text-xs font-bold text-white shadow-inner">
        <div class="mx-auto flex max-w-container-page items-center justify-center gap-2 px-4">
            <x-heroicon-s-exclamation-triangle class="h-4 w-4" />
            <span>Mode Pratinjau: Publikasi ini berstatus {{ $berita->status->getLabel() }} dan belum tayang untuk umum.</span>
        </div>
    </div>
@endif

{{-- ══ BREADCRUMB & NAVIGASI KEMBALI ═══════════════════════════════════════ --}}
<div class="border-b border-outline-variant bg-surface-container-lowest py-3.5">
    <div class="mx-auto flex max-w-4xl items-center justify-between gap-3 px-margin-mobile lg:px-0">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-xs font-bold text-on-surface-variant transition hover:text-primary">
            <x-heroicon-o-arrow-left class="h-4 w-4" />
            <span>Kembali ke Kabar Nagari</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $berita->kategori->badgeColor() }}">
                {{ $berita->kategori->getLabel() }}
            </span>
            <span class="rounded-full bg-surface-container-high px-2.5 py-0.5 text-[10px] font-bold text-primary">
                {{ $berita->targetAudienceLabel() }}
            </span>
        </div>
    </div>
</div>

{{-- ══ ARTIKEL UTAMA ══════════════════════════════════════════════════════ --}}
<article class="bg-background py-10 sm:py-16">
    <div class="mx-auto max-w-4xl px-margin-mobile lg:px-0">
        
        {{-- Header Artikel --}}
        <header class="text-center sm:text-left">
            <h1 class="text-2xl font-black leading-tight tracking-tight text-primary sm:text-4xl lg:text-5xl">
                {{ $berita->judul }}
            </h1>

            {{-- Metadata Bar --}}
            <div class="mt-6 flex flex-wrap items-center justify-center gap-y-2 gap-x-4 border-y border-outline-variant/60 py-3 text-xs font-semibold text-on-surface-variant sm:justify-start">
                <div class="flex items-center gap-1.5 text-primary">
                    <x-heroicon-o-user class="h-4 w-4 text-primary" />
                    <span>{{ $berita->authorLabel() }}</span>
                </div>

                <span>•</span>

                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-calendar class="h-4 w-4 text-outline" />
                    <time datetime="{{ $berita->published_at?->toIso8601String() }}">
                        {{ $berita->published_at?->translatedFormat('d F Y, H:i') ?? $berita->created_at->translatedFormat('d F Y') }} WIB
                    </time>
                </div>

                <span>•</span>

                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-clock class="h-4 w-4 text-outline" />
                    <span>{{ $berita->readingTime() }} menit baca</span>
                </div>

                <span>•</span>

                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-eye class="h-4 w-4 text-outline" />
                    <span>{{ number_format($berita->views_count, 0, ',', '.') }} kali dilihat</span>
                </div>
            </div>
        </header>

        {{-- Foto Sampul Utama --}}
        @if($coverUrl)
            <figure class="mt-8 overflow-hidden rounded-3xl border border-outline-variant bg-surface-container-low shadow-sm">
                <img src="{{ $coverUrl }}" alt="{{ $berita->judul }}" class="w-full object-cover max-h-[520px]" loading="eager">
            </figure>
        @endif

        {{-- Ringkasan / Lead Text --}}
        @if($berita->ringkasan)
            <div class="mt-8 rounded-2xl border-l-4 border-primary bg-primary/5 p-5 text-sm font-medium leading-relaxed text-on-surface sm:text-base">
                {{ $berita->ringkasan }}
            </div>
        @endif

        {{-- Konten Utama --}}
        <div class="mt-8 text-base leading-relaxed text-on-surface/90 sm:text-lg sm:leading-loose">
            <div class="prose prose-lg dark:prose-invert max-w-none prose-headings:font-black prose-headings:text-primary prose-a:text-primary prose-img:rounded-2xl prose-img:shadow-sm">
                {!! $berita->konten !!}
            </div>
        </div>

        {{-- ══ BERKAS LAMPIRAN DOKUMEN ═════════════════════════════════════ --}}
        @php($lampirans = $berita->getMedia('lampiran'))
        @if($lampirans->isNotEmpty())
            <div class="mt-12 rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
                <div class="flex items-center gap-2.5 text-sm font-black text-primary">
                    <x-heroicon-o-paper-clip class="h-5 w-5 text-primary" />
                    <span>Berkas Lampiran Dokumen ({{ $lampirans->count() }})</span>
                </div>
                <p class="mt-1 text-xs text-on-surface-variant">Dokumen resmi atau berkas pendukung yang dapat diunduh langsung.</p>

                <div class="mt-4 divide-y divide-outline-variant/60">
                    @foreach($lampirans as $file)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0 flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <x-heroicon-o-document-text class="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-on-surface sm:text-sm">{{ $file->file_name }}</p>
                                    <p class="text-[11px] text-on-surface-variant">{{ $file->human_readable_size }}</p>
                                </div>
                            </div>

                            <a href="{{ $file->getUrl() }}" target="_blank" download
                               class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-on-primary shadow-sm transition hover:bg-primary/90">
                                <x-heroicon-o-arrow-down-tray class="h-3.5 w-3.5" />
                                <span>Unduh</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ══ BAGIKAN BERITA ═════════════════════════════════════════════ --}}
        <div class="mt-10 flex flex-col items-center justify-between gap-4 rounded-2xl border border-outline-variant/80 bg-surface-container-lowest p-5 sm:flex-row">
            <div class="text-xs font-bold text-on-surface">
                <span>Bagikan kabar ini:</span>
            </div>

            <div class="flex items-center gap-2">
                {{-- WhatsApp --}}
                <a href="https://api.whatsapp.com/send?text={{ urlencode($berita->judul . "\n" . $canonical) }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-[#25D366] px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:opacity-90"
                   aria-label="Bagikan ke WhatsApp">
                    <span>WhatsApp</span>
                </a>

                {{-- Facebook --}}
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($canonical) }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-[#1877F2] px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:opacity-90"
                   aria-label="Bagikan ke Facebook">
                    <span>Facebook</span>
                </a>

                {{-- Salin Tautan --}}
                <button type="button"
                        onclick="navigator.clipboard.writeText('{{ $canonical }}'); alert('Tautan berhasil disalin ke clipboard!');"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant bg-white px-3.5 py-2 text-xs font-bold text-on-surface shadow-sm transition hover:bg-surface-container-high">
                    <x-heroicon-o-link class="h-3.5 w-3.5 text-on-surface-variant" />
                    <span>Salin Tautan</span>
                </button>
            </div>
        </div>

    </div>
</article>

{{-- ══ KABAR LAINNYA / TERKAIT ════════════════════════════════════════════ --}}
@if($related->isNotEmpty())
    <section class="border-t border-outline-variant bg-surface-container-lowest py-section-gap">
        <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-primary sm:text-xl">Kabar Lainnya</h3>
                    <p class="text-xs text-on-surface-variant">Publikasi terkait dari nagari</p>
                </div>

                <a href="{{ $backUrl }}" class="text-xs font-bold text-primary hover:underline">
                    Lihat Semua
                </a>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($related as $rel)
                    <x-berita.card :berita="$rel" :nagari="$nagari" />
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection
