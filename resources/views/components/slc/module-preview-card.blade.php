@props(['module', 'order' => null])

{{-- Pratinjau modul untuk pengunjung umum. Visualnya mengikuti kartu modul di
     portal warga, tetapi sengaja tidak membawa progres, status terkunci,
     evaluasi, atau isi materi yang hanya boleh tersedia setelah masuk. --}}
<article data-public-module-card
    class="group flex h-full flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary/35 hover:shadow-lg">
    <div class="aspect-[16/9] w-full shrink-0 overflow-hidden bg-surface-container-high">
        @if($module->punyaCover())
            <img src="{{ $module->coverUrl() }}"
                 alt="Sampul {{ $module->judul }}"
                 loading="lazy"
                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
        @else
            <x-slc.module-cover :judul="$module->judul"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
        @endif
    </div>

    <div class="flex flex-1 flex-col p-4">
        @if($order !== null)
            <p class="mb-1.5 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                Modul {{ str_pad((string) $order, 2, '0', STR_PAD_LEFT) }}
            </p>
        @endif

        <h3 class="line-clamp-2 min-h-10 text-sm font-extrabold leading-5 text-on-surface transition-colors group-hover:text-primary">
            {{ $module->judul }}
        </h3>

        <p class="mt-2 line-clamp-2 min-h-9 text-xs leading-relaxed text-on-surface-variant">
            {{ filled($module->deskripsi) ? strip_tags($module->deskripsi) : 'Materi pembelajaran dalam pelatihan ini.' }}
        </p>

        <div class="mt-auto pt-4">
            <a href="{{ \App\Support\PublicNavigation::masukUrl(\App\Enums\GerbangLogin::Belajar, $module->pelatihan_id) }}"
               class="inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-xs font-extrabold text-on-primary shadow-sm transition hover:bg-primary-highlight focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                Masuk untuk Belajar
                <x-heroicon-o-arrow-right class="h-3.5 w-3.5" />
            </a>
        </div>
    </div>
</article>
