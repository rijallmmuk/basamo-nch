@php
    $record = $getRecord();
    $photos = $record->getMedia('photos');
    $urls = $photos->map(fn ($media): string => $record->detailPhotoUrl($media))->values();
@endphp

@if($urls->count() > 0)
    <div x-data="{
            aktif: 0,
            geser(arah) {
                this.$refs.slider.scrollBy({ left: arah * this.$refs.slider.clientWidth, behavior: 'smooth' });
            },
            keFoto(index) {
                this.$refs.slider.scrollTo({ left: index * this.$refs.slider.clientWidth, behavior: 'smooth' });
            },
            pantau() {
                const lebar = this.$refs.slider.clientWidth;
                this.aktif = lebar > 0 ? Math.round(this.$refs.slider.scrollLeft / lebar) : 0;
            }
        }"
        class="group relative mx-auto w-full max-w-md"
    >
        @if($urls->count() > 1)
            <button @click="geser(-1)" type="button" aria-label="Foto sebelumnya"
                class="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full border border-gray-200 bg-white/90 p-2 text-gray-800 shadow-lg ring-2 ring-transparent transition duration-300 hover:bg-white focus:outline-none focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900/90 dark:text-gray-200 dark:hover:bg-black md:opacity-0 md:group-hover:opacity-100">
                <x-heroicon-o-chevron-left class="h-5 w-5" />
            </button>

            <button @click="geser(1)" type="button" aria-label="Foto berikutnya"
                class="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full border border-gray-200 bg-white/90 p-2 text-gray-800 shadow-lg ring-2 ring-transparent transition duration-300 hover:bg-white focus:outline-none focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900/90 dark:text-gray-200 dark:hover:bg-black md:opacity-0 md:group-hover:opacity-100">
                <x-heroicon-o-chevron-right class="h-5 w-5" />
            </button>

            <span class="absolute right-3 top-3 z-10 rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white">
                <span x-text="aktif + 1">1</span>/{{ $urls->count() }}
            </span>
        @endif

        <div x-ref="slider" @scroll.debounce.100ms="pantau()"
            class="flex snap-x snap-mandatory overflow-x-auto [&::-webkit-scrollbar]:hidden">
            @foreach($urls as $index => $url)
                <div class="relative aspect-square w-full shrink-0 snap-center overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-sm dark:border-gray-700/50 dark:bg-gray-800">
                    <img src="{{ $url }}"
                        alt="Foto {{ $index + 1 }} {{ $record->nama_produk }}"
                        @if($index > 0) loading="lazy" @endif
                        class="h-full w-full object-cover" />
                </div>
            @endforeach
        </div>

        @if($urls->count() > 1)
            <div class="mt-3 flex justify-center gap-2">
                @foreach($urls as $index => $url)
                    <button @click="keFoto({{ $index }})" type="button" aria-label="Lihat foto {{ $index + 1 }}"
                        class="h-2 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        :class="aktif === {{ $index }} ? 'w-6 bg-primary-600 dark:bg-primary-400' : 'w-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500'"></button>
                @endforeach
            </div>
        @endif
    </div>
@else
    <div class="mx-auto flex aspect-square w-full max-w-md flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-500">
        <x-heroicon-o-photo class="mb-2 h-12 w-12 opacity-50" />
        <span class="text-sm font-medium">Belum ada foto produk</span>
    </div>
@endif
