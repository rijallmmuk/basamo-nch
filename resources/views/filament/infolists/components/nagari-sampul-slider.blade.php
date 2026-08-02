@php
    $urls = $getRecord()->sampulUrls();
@endphp

@if($urls->count() > 0)
    <div x-data="{
            scrollLeft() {
                this.$refs.slider.scrollBy({ left: -(this.$refs.slider.clientWidth * 0.8), behavior: 'smooth' });
            },
            scrollRight() {
                this.$refs.slider.scrollBy({ left: (this.$refs.slider.clientWidth * 0.8), behavior: 'smooth' });
            }
        }"
        class="relative group w-full"
    >
        @if($urls->count() > 1)
            <!-- Tombol Navigasi Kiri -->
            <button @click="scrollLeft()" type="button" class="absolute left-2 md:left-4 top-1/2 -translate-y-1/2 bg-white/90 dark:bg-gray-900/90 hover:bg-white dark:hover:bg-black text-gray-800 dark:text-gray-200 rounded-full p-2 shadow-lg z-10 transition duration-300 opacity-0 group-hover:opacity-100 border border-gray-200 dark:border-gray-700 focus:outline-none ring-2 ring-transparent focus:ring-primary-500">
                <x-heroicon-o-chevron-left class="w-6 h-6" />
            </button>

            <!-- Tombol Navigasi Kanan -->
            <button @click="scrollRight()" type="button" class="absolute right-2 md:right-4 top-1/2 -translate-y-1/2 bg-white/90 dark:bg-gray-900/90 hover:bg-white dark:hover:bg-black text-gray-800 dark:text-gray-200 rounded-full p-2 shadow-lg z-10 transition duration-300 opacity-0 group-hover:opacity-100 border border-gray-200 dark:border-gray-700 focus:outline-none ring-2 ring-transparent focus:ring-primary-500">
                <x-heroicon-o-chevron-right class="w-6 h-6" />
            </button>
        @endif

        <div x-ref="slider" class="flex overflow-x-auto snap-x snap-mandatory gap-4 pb-2 [&::-webkit-scrollbar]:hidden">
            @foreach($urls as $url)
                <div class="snap-center shrink-0 w-full aspect-video rounded-2xl overflow-hidden relative shadow-sm border border-gray-200 dark:border-gray-700/50">
                    <img src="{{ $url }}" alt="Sampul Nagari" class="w-full h-full object-cover transition-transform duration-500 hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="w-full aspect-[21/9] sm:aspect-[3/1] rounded-2xl bg-gray-50 dark:bg-gray-800/50 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-700 text-gray-400 dark:text-gray-500">
        <x-heroicon-o-photo class="w-12 h-12 mb-2 opacity-50" />
        <span class="text-sm font-medium">Belum ada foto sampul</span>
    </div>
@endif
