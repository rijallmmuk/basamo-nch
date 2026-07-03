{{-- Galeri foto modal tinjau: klik memperbesar di overlay (tanpa meninggalkan modal). --}}
<div class="mt-3" x-data="{ zoom: null }">
    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
        @foreach($photos as $media)
            <button type="button" x-on:click="zoom = @js($media->getUrl())"
                class="group relative cursor-zoom-in overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-white/10">
                <img src="{{ $media->getUrl('card') }}" alt=""
                    class="aspect-square w-full object-cover transition duration-200 group-hover:scale-105">
            </button>
        @endforeach
    </div>
    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Klik foto untuk memperbesar.</p>

    <template x-teleport="body">
        <div x-show="zoom" x-on:click="zoom = null" x-on:keydown.escape.window="zoom = null"
            x-transition.opacity.duration.150ms
            class="fixed inset-0 z-[70] flex cursor-zoom-out items-center justify-center bg-black/80 p-4 sm:p-10"
            style="display: none">
            <img x-bind:src="zoom" alt="" class="max-h-full max-w-full rounded-xl object-contain shadow-2xl">
        </div>
    </template>
</div>
