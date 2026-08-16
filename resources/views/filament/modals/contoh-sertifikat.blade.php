{{-- Berkas PDF disajikan lewat rute pratinjau, bukan ditanam sebagai data URI:
     sertifikat berisi logo dan kode QR sehingga ukurannya hampir 2 MB, dan menyalinnya
     ke dalam HTML modal akan membengkakkan setiap muatan halaman form. --}}
<div class="space-y-3">
    <iframe
        src="{{ $url }}"
        title="Contoh sertifikat"
        class="h-[70vh] w-full rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
    ></iframe>

    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
    >
        <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
        Buka di tab baru
    </a>
</div>
