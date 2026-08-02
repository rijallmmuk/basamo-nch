@php($itemLabel = $label ?? 'data')

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-3">
        {{-- Ringkas (mobile) --}}
        <div class="flex flex-1 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="rounded-full border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded-full border border-outline-variant bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-primary hover:border-primary/40">Sebelumnya</a>
            @endif
            <span class="text-sm text-on-surface-variant">Hal. {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded-full border border-outline-variant bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-primary hover:border-primary/40">Berikutnya</a>
            @else
                <span class="rounded-full border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface-muted">Berikutnya</span>
            @endif
        </div>

        {{-- Bernomor (desktop) --}}
        <div class="hidden flex-1 items-center justify-between sm:flex">
            <p class="text-sm text-on-surface-variant">
                Menampilkan <span class="font-semibold text-on-surface">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-on-surface">{{ $paginator->lastItem() }}</span>
                dari <span class="font-semibold text-on-surface">{{ number_format($paginator->total(), 0, ',', '.') }}</span> {{ $itemLabel }}
            </p>
            <div class="flex items-center gap-1.5">
                @if ($paginator->onFirstPage())
                    <span class="flex h-10 w-10 items-center justify-center rounded-full text-on-surface-muted"><x-heroicon-o-chevron-left class="h-4 w-4" /></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant bg-surface-container-lowest text-primary transition-colors hover:border-primary/40"><x-heroicon-o-chevron-left class="h-4 w-4" /></a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1 text-on-surface-variant">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $materi => $url)
                            @if ($materi == $paginator->currentPage())
                                <span aria-current="page" class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-sm font-bold text-on-primary">{{ $materi }}</span>
                            @else
                                <a href="{{ $url }}" class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant bg-surface-container-lowest text-sm font-semibold text-on-surface transition-colors hover:border-primary/40 hover:text-primary">{{ $materi }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant bg-surface-container-lowest text-primary transition-colors hover:border-primary/40"><x-heroicon-o-chevron-right class="h-4 w-4" /></a>
                @else
                    <span class="flex h-10 w-10 items-center justify-center rounded-full text-on-surface-muted"><x-heroicon-o-chevron-right class="h-4 w-4" /></span>
                @endif
            </div>
        </div>
    </nav>
@endif
