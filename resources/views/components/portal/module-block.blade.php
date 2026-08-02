@props(['block', 'module', 'page', 'blockIndex'])

@php
    $type = $block['type'] ?? null;
    $data = $block['data'] ?? [];
    $proseClass = 'prose prose-base sm:prose-lg max-w-none text-on-surface leading-relaxed prose-headings:font-black prose-headings:tracking-tight prose-a:text-primary prose-a:font-bold prose-a:no-underline hover:prose-a:underline prose-img:rounded-2xl prose-img:shadow-sm prose-blockquote:border-l-primary prose-blockquote:bg-surface-container-low/60 prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:rounded-r-xl prose-code:text-primary prose-code:bg-surface-container-high prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded-md prose-code:before:content-none prose-code:after:content-none';
    $fileUrl = fn (bool $download = false): string => route(
        'portal.modules.materi.files.show',
        [$module, $page, $blockIndex, ...($download ? ['download' => 1] : [])],
        absolute: false,
    );
@endphp

@switch($type)
    {{-- ── 1. TEKS BACAAN (RICH EDITOR KONTEN) ────────────────────────────────── --}}
    @case('teks')
        @if(filled($data['konten'] ?? null))
            <div class="slc-readable-text mx-auto {{ $proseClass }}">
                {!! str($data['konten'])->sanitizeHtml() !!}
            </div>
        @endif
        @break

    {{-- ── 2. VIDEO EMBED (YOUTUBE / GOOGLE DRIVE / DIRECT LINK) ──────────────── --}}
    @case('video')
        @php
            $url = $data['url'] ?? null;
            $videoId = null;
            $driveId = null;
            if ($url) {
                if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $vm)) {
                    $videoId = $vm[1];
                } elseif (preg_match('#drive\.google\.com/file/d/([A-Za-z0-9_-]+)#', $url, $dm)) {
                    $driveId = $dm[1];
                }
            }
            $hasVideo = $videoId || $driveId
                || ($url && \Illuminate\Support\Str::startsWith(strtolower($url), ['http://', 'https://']));
        @endphp

        @if($hasVideo)
            <figure class="space-y-2">
                <div class="slc-media-wide mx-auto aspect-video w-full max-w-4xl overflow-hidden rounded-2xl bg-black border border-outline-variant shadow-sm relative group">
                    @if($videoId)
                        <iframe src="https://www.youtube.com/embed/{{ $videoId }}?rel=0"
                            title="{{ $data['caption'] ?? 'Video Materi YouTube' }}"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen class="h-full w-full border-0"></iframe>
                    @elseif($driveId)
                        <iframe src="https://drive.google.com/file/d/{{ $driveId }}/preview"
                            title="{{ $data['caption'] ?? 'Video Materi Google Drive' }}"
                            allow="autoplay" allowfullscreen class="h-full w-full border-0"></iframe>
                    @elseif($url && \Illuminate\Support\Str::startsWith(strtolower($url), ['http://', 'https://']))
                        <div class="flex h-full w-full flex-col items-center justify-center bg-surface-container-high p-8 text-center">
                            <x-heroicon-s-play-circle class="h-16 w-16 text-primary mb-3" />
                            <p class="text-sm font-bold text-on-surface mb-3">Tautan Video Eksternal</p>
                            <a href="{{ $url }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-xs sm:text-sm font-extrabold text-on-primary hover:bg-surface-tint transition-all shadow-xs">
                                <x-heroicon-s-arrow-top-right-on-square class="h-4 w-4" />
                                <span>Putar Video di Tab Baru</span>
                            </a>
                        </div>
                    @endif
                </div>

                @if(filled($data['caption'] ?? null))
                    <figcaption class="flex items-center justify-center gap-1.5 text-xs text-on-surface-variant italic font-medium">
                        <x-heroicon-s-video-camera class="h-3.5 w-3.5 text-primary shrink-0" />
                        <span>{{ $data['caption'] }}</span>
                    </figcaption>
                @endif
            </figure>
        @endif
        @break

    {{-- ── 3. PDF / DOKUMEN ─────────────────────────────────────────────────── --}}
    @case('pdf')
        @if(filled($data['file'] ?? null))
            @php $pdfUrl = $fileUrl(); @endphp
            <div class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm space-y-0">
                
                {{-- Document Header --}}
                <div class="flex items-center justify-between border-b border-outline-variant p-4 bg-surface-container-low">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-500/10 text-red-600">
                            <x-heroicon-s-document-text class="h-6 w-6" />
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-extrabold text-sm text-on-surface truncate">
                                {{ $data['judul'] ?? 'Dokumen Materi (PDF)' }}
                            </h4>
                            <p class="text-[11px] text-on-surface-variant">Pratinjau Dokumen Interaktif</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ $pdfUrl }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-1.5 text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors">
                            <x-heroicon-s-arrow-top-right-on-square class="h-3.5 w-3.5 text-primary" />
                            <span class="hidden sm:inline">Layar Penuh</span>
                        </a>
                        <a href="{{ $fileUrl(true) }}"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3 py-1.5 text-xs font-extrabold text-on-primary hover:bg-surface-tint transition-all">
                            <x-heroicon-s-arrow-down-tray class="h-3.5 w-3.5" />
                            <span>Unduh</span>
                        </a>
                    </div>
                </div>

                {{-- Pratinjau PDF. Gunakan iframe karena CSP aplikasi sengaja
                     memblokir object/embed, sedangkan frame sesama-origin diizinkan. --}}
                <div class="relative w-full bg-surface-container">
                    <iframe
                        src="{{ $pdfUrl }}"
                        title="{{ $data['judul'] ?? 'Pratinjau Dokumen Materi PDF' }}"
                        class="block w-full border-0"
                        style="height: min(70vh, 650px)"
                    ></iframe>
                </div>

            </div>
        @endif
        @break

    {{-- ── 4. GAMBAR MATERI (HIGH-RES WITH CAPTION) ────────────────────────── --}}
    @case('gambar')
        @if(filled($data['file'] ?? null))
            <figure class="space-y-2">
                <div class="slc-media-wide mx-auto max-w-4xl overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-high shadow-sm">
                    <img src="{{ $fileUrl() }}" 
                         alt="{{ $data['alt'] ?? ($data['caption'] ?? 'Gambar Materi') }}"
                         loading="lazy"
                         class="mx-auto h-auto max-h-[75vh] w-auto max-w-full object-contain">
                </div>
                @if(filled($data['caption'] ?? null))
                    <figcaption class="flex items-center justify-center gap-1.5 text-xs text-on-surface-variant italic font-medium">
                        <x-heroicon-s-photo class="h-3.5 w-3.5 text-primary shrink-0" />
                        <span>{{ $data['caption'] }}</span>
                    </figcaption>
                @endif
            </figure>
        @endif
        @break

    {{-- ── 5. AUDIO PLAYER (REKAMAN SUARA / PODCAST MATERI) ──────────────────── --}}
    @case('audio')
        @if(filled($data['file'] ?? null))
            <div class="rounded-2xl border border-outline-variant bg-surface-container-low p-5 shadow-sm space-y-3 max-w-2xl mx-auto">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <x-heroicon-s-musical-note class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="font-extrabold text-sm text-on-surface">Audio Rekaman Materi</h4>
                        <p class="text-xs text-on-surface-variant">Putar rekaman penjelas materi pembelajaran.</p>
                    </div>
                </div>

                <audio controls preload="metadata" class="w-full rounded-xl">
                    <source src="{{ $fileUrl() }}">
                    Browser Anda tidak mendukung pemutar audio HTML5.
                </audio>

                @if(filled($data['caption'] ?? null))
                    <p class="text-xs text-on-surface-variant italic">{{ $data['caption'] }}</p>
                @endif
            </div>
        @endif
        @break

    {{-- ── 6. LAMPIRAN BERKAS (DOWNLOAD ATTACHMENT CARD) ────────────────────── --}}
    @case('lampiran')
        @if(filled($data['file'] ?? null))
            @php $name = $data['label'] ?? basename($data['file']); @endphp
            <a href="{{ $fileUrl(true) }}" download
                class="group flex items-center justify-between gap-4 rounded-2xl border border-outline-variant bg-surface-container-lowest p-4 shadow-2xs hover:border-primary/40 hover:bg-surface-container-low transition-all max-w-2xl">
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors">
                        <x-heroicon-s-paper-clip class="h-6 w-6" />
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-sm text-on-surface truncate group-hover:text-primary transition-colors">
                            {{ $name }}
                        </h4>
                        <p class="text-xs text-on-surface-variant">Klik untuk mengunduh lampiran berkas</p>
                    </div>
                </div>

                <span class="inline-flex items-center gap-1.5 rounded-xl bg-primary/10 px-3.5 py-2 text-xs font-extrabold text-primary group-hover:bg-primary group-hover:text-on-primary transition-colors shrink-0">
                    <x-heroicon-s-arrow-down-tray class="h-4 w-4" />
                    <span>Unduh</span>
                </span>
            </a>
        @endif
        @break
@endswitch
