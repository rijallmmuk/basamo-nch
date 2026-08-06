@props(['program', 'href', 'cta' => 'Lihat Modul'])

@php
    $participants = $program->participants();
@endphp

<a href="{{ $href }}"
   class="group flex h-full flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary/35 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
    
    <div>
        {{-- ── 1. PROGRAM COVER IMAGE (16:9 Aspect Ratio) ──────── --}}
        <div class="relative aspect-[16/9] w-full overflow-hidden bg-surface-container-high shrink-0">
            @if ($program->punyaCover())
                <img src="{{ $program->coverUrl() }}" alt="Cover {{ $program->temaNama() }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
            @else
                <x-slc.tema-cover :nama="$program->temaNama()" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
            @endif
            
        </div>

        {{-- ── 2. CARD CONTENT AREA ── --}}
        <div class="p-4 pb-2">
            <h2 class="line-clamp-2 min-h-12 text-base font-extrabold leading-6 text-on-surface transition-colors group-hover:text-primary">
                {{ $program->temaNama() }}
            </h2>

            <div class="mt-3 min-h-6">
                <x-slc.pengelola-list :participants="$participants" :max="1" compact />
            </div>
        </div>
    </div>

    {{-- ── 3. CARD FOOTER ACTIONS ───────────────────────────── --}}
    <div class="mt-auto px-4 pb-4 pt-3">
        <div class="flex items-center justify-between gap-3 border-t border-outline-variant/70 pt-3 text-xs text-on-surface-variant">
            <span class="flex items-center gap-1.5 font-semibold">
                <x-heroicon-o-book-open class="h-4 w-4 text-primary" />
                {{ $program->modules_count }} Modul
            </span>

            <span class="inline-flex items-center gap-1 font-bold text-primary">
                {{ $cta }} <x-heroicon-o-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
            </span>
        </div>
    </div>
</a>
