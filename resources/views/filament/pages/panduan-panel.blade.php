{{-- Panduan panel: daftar isi yang menempel di kanan, isi panduan di kiri.
     Tautan lompatnya memakai id judul yang dipasang App\Support\Panduan. --}}
<x-filament-panels::page>
    <details data-panduan-mobile-toc class="panduan-daftar-isi-ringkas group rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 xl:hidden dark:bg-gray-900 dark:ring-white/10">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-sm font-semibold text-gray-950 marker:content-none dark:text-white">
            <span class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-list-bullet" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                <span>Daftar Isi</span>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-white/10 dark:text-gray-400">
                    {{ count($this->getDaftarIsi()) }} bagian
                </span>
            </span>
            <x-filament::icon icon="heroicon-m-chevron-down"
                class="h-5 w-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180 dark:text-gray-400" />
        </summary>
        <nav class="panduan-daftar-isi-mobile overflow-y-auto border-t border-gray-200 px-3 py-3 dark:border-white/10" aria-label="Daftar isi panduan">
            @include('filament.pages.partials.panduan-daftar-isi', ['daftarIsi' => $this->getDaftarIsi()])
        </nav>
    </details>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">

        <article class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 sm:p-8 xl:col-span-3 dark:bg-gray-900 dark:ring-white/10">
            <div class="panduan-isi max-w-none">
                {!! $this->getIsi() !!}
            </div>
        </article>

        <aside class="hidden xl:block">
            <nav data-panduan-desktop-toc class="panduan-daftar-isi-desktop sticky top-24 overflow-y-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                 aria-label="Daftar isi panduan">
                <div class="sticky top-0 z-10 flex items-center gap-2 border-b border-gray-200 bg-white px-5 py-4 dark:border-white/10 dark:bg-gray-900">
                    <x-filament::icon icon="heroicon-o-list-bullet" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">Daftar Isi</p>
                </div>
                <div class="px-3 py-3">
                    @include('filament.pages.partials.panduan-daftar-isi', ['daftarIsi' => $this->getDaftarIsi()])
                </div>
            </nav>
        </aside>
    </div>

    @push('styles')
        <style>
            .panduan-daftar-isi-mobile {
                max-height: 60vh;
                overscroll-behavior: contain;
            }
            .panduan-daftar-isi-desktop {
                max-height: calc(100vh - 7rem);
                overscroll-behavior: contain;
                scrollbar-gutter: stable;
            }
            @media (min-width: 1280px) {
                .panduan-daftar-isi-ringkas { display: none; }
            }
            /* Judul diberi jarak aman dari bilah atas panel supaya tidak tertutup
               saat pembaca melompat lewat daftar isi. */
            .panduan-isi h2, .panduan-isi h3 { scroll-margin-top: 6rem; }
            .panduan-isi h2 {
                margin: 2rem 0 .75rem; padding-left: .6rem;
                border-left: 4px solid rgb(var(--primary-500));
                font-size: 1.25rem; font-weight: 700; line-height: 1.3;
            }
            .panduan-isi h2:first-child { margin-top: 0; }
            .panduan-isi h3 { margin: 1.5rem 0 .5rem; font-size: 1rem; font-weight: 700; }
            .panduan-isi p, .panduan-isi ul, .panduan-isi ol { margin-bottom: .75rem; }
            .panduan-isi ul { list-style: disc; padding-left: 1.25rem; }
            .panduan-isi ol { list-style: decimal; padding-left: 1.25rem; }
            .panduan-isi li { margin-bottom: .25rem; }
            .panduan-isi a { color: rgb(var(--primary-600)); text-decoration: underline; }
            .panduan-isi code {
                background: rgba(0, 0, 0, .05); padding: .1rem .3rem;
                border-radius: .25rem; font-size: .875em;
            }
            .panduan-isi pre {
                background: #0e2a59; color: #fff; padding: .9rem 1rem;
                border-radius: .6rem; overflow-x: auto; margin-bottom: .75rem;
            }
            .panduan-isi pre code { background: none; color: inherit; padding: 0; }
            .panduan-isi blockquote {
                border-left: 4px solid #c9932c; background: #fff8e8;
                padding: .7rem 1rem; border-radius: 0 .5rem .5rem 0; margin-bottom: .75rem;
            }
            .panduan-isi blockquote p:last-child { margin-bottom: 0; }
            .panduan-isi img {
                width: 100%; height: auto; border-radius: .6rem; margin: 1rem 0;
                border: 1px solid rgba(0, 0, 0, .08);
            }
            /* Tabel panduan kerap lebar; ia menggulir di dalam kotaknya sendiri
               supaya halaman panel tidak ikut menggulir menyamping. */
            .panduan-isi table {
                width: 100%; border-collapse: collapse; margin-bottom: 1rem;
                font-size: .875rem; display: block; overflow-x: auto;
            }
            .panduan-isi th {
                background: #0e2a59; color: #fff; text-align: left;
                padding: .5rem .7rem; font-weight: 600; white-space: nowrap;
            }
            .panduan-isi td {
                padding: .5rem .7rem; border-bottom: 1px solid rgba(0, 0, 0, .08);
                vertical-align: top;
            }
            .panduan-isi hr { margin: 1.75rem 0; border-color: rgba(0, 0, 0, .08); }
            .dark .panduan-isi code { background: rgba(255, 255, 255, .08); }
            .dark .panduan-isi blockquote { background: rgba(201, 147, 44, .12); }
            .dark .panduan-isi td { border-color: rgba(255, 255, 255, .08); }
            .dark .panduan-isi img { border-color: rgba(255, 255, 255, .1); }
        </style>
    @endpush
</x-filament-panels::page>
