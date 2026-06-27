@props(['block'])

@php
    $type = $block['type'] ?? null;
    $data = $block['data'] ?? [];
    $disk = Storage::disk(config('media-library.disk_name'));
    $prose = 'prose prose-base max-w-none prose-img:rounded-xl sm:prose-lg';
@endphp

@switch($type)
    @case('teks')
        @if(filled($data['konten'] ?? null))
            <div class="{{ $prose }}">
                {!! str($data['konten'])->sanitizeHtml() !!}
            </div>
        @endif
        @break

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
        @if($videoId)
            <div class="aspect-video overflow-hidden rounded-xl bg-black">
                <iframe src="https://www.youtube.com/embed/{{ $videoId }}?rel=0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen class="h-full w-full"></iframe>
            </div>
        @elseif($driveId)
            <div class="aspect-video overflow-hidden rounded-xl bg-black">
                <iframe src="https://drive.google.com/file/d/{{ $driveId }}/preview"
                    allow="autoplay" allowfullscreen class="h-full w-full"></iframe>
            </div>
        @elseif($url && \Illuminate\Support\Str::startsWith(strtolower($url), ['http://', 'https://']))
            {{-- Hanya tautkan URL http/https (defense-in-depth: cegah javascript:/data:) --}}
            <div class="flex items-center justify-center rounded-xl bg-surface-container py-14">
                <a href="{{ $url }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-on-primary transition-colors hover:bg-surface-tint">
                    <x-heroicon-s-play-circle class="h-5 w-5" />
                    Buka Video
                </a>
            </div>
        @endif
        @if($hasVideo && filled($data['caption'] ?? null))
            <p class="mt-2 text-center text-sm text-on-surface-variant">{{ $data['caption'] }}</p>
        @endif
        @break

    @case('pdf')
        @if(filled($data['file'] ?? null))
            @php $pdfUrl = $disk->url($data['file']); @endphp
            @if(filled($data['judul'] ?? null))
                <p class="mb-2 font-semibold text-on-surface">{{ $data['judul'] }}</p>
            @endif
            {{-- Preview inline (gracefully degrade di HP) + tombol akses yang selalu tersedia --}}
            <div class="overflow-hidden rounded-xl border border-outline-variant bg-surface-container">
                <object data="{{ $pdfUrl }}" type="application/pdf" class="hidden w-full sm:block" style="height: min(70vh, 650px)">
                    <div class="px-5 py-10 text-center text-sm text-on-surface-variant">Pratinjau PDF tidak tersedia di perangkat ini.</div>
                </object>
                <div class="flex flex-wrap items-center gap-3 border-t border-outline-variant bg-surface-container-lowest px-4 py-3 sm:border-t-0">
                    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-on-primary transition-colors hover:bg-surface-tint">
                        <x-heroicon-s-document-text class="h-5 w-5" />
                        Buka PDF
                    </a>
                    <a href="{{ $pdfUrl }}" download
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                        Unduh
                    </a>
                </div>
            </div>
        @endif
        @break

    @case('gambar')
        @if(filled($data['file'] ?? null))
            <figure>
                <img src="{{ $disk->url($data['file']) }}" alt="{{ $data['alt'] ?? '' }}"
                    class="w-full rounded-xl" loading="lazy">
                @if(filled($data['caption'] ?? null))
                    <figcaption class="mt-2 text-center text-sm text-on-surface-variant">{{ $data['caption'] }}</figcaption>
                @endif
            </figure>
        @endif
        @break

    @case('audio')
        @if(filled($data['file'] ?? null))
            <audio controls preload="none" class="w-full">
                <source src="{{ $disk->url($data['file']) }}">
                Browser Anda tidak mendukung pemutar audio.
            </audio>
            @if(filled($data['caption'] ?? null))
                <p class="mt-2 text-sm text-on-surface-variant">{{ $data['caption'] }}</p>
            @endif
        @endif
        @break

    @case('lampiran')
        @if(filled($data['file'] ?? null))
            @php $name = $data['label'] ?? basename($data['file']); @endphp
            <a href="{{ $disk->url($data['file']) }}" download
                class="flex items-center gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest p-4 transition-colors hover:bg-surface-container-low">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <x-heroicon-o-paper-clip class="h-6 w-6" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-semibold text-on-surface">{{ $name }}</span>
                    <span class="text-xs text-on-surface-variant">Ketuk untuk mengunduh</span>
                </span>
                <x-heroicon-o-arrow-down-tray class="h-5 w-5 shrink-0 text-outline-variant" />
            </a>
        @endif
        @break
@endswitch
