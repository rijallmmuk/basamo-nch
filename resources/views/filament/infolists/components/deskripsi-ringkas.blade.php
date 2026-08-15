@php
    /* Deskripsi dipangkas tampilannya, BUKAN isinya.
       Kolomnya bertipe TEXT sehingga sanggup menampung puluhan ribu karakter, dan
       tanpa pembatas seperti ini satu deskripsi panjang mendorong seluruh isi
       halaman detail ke bawah sampai berlayar-layar.
       Pemangkasan dikerjakan CSS (line-clamp), bukan substr, sebab isinya HTML dari
       RichEditor: memotong string HTML akan memutus tag di tengah jalan. */
    $isi = (string) ($getRecord()->deskripsi ?? '');
    $adaIsi = filled(trim(strip_tags($isi)));
    $panjang = mb_strlen(trim(strip_tags($isi)));

    // Ambang munculnya tombol. Di bawah ini teksnya memang cukup pendek untuk
    // ditampilkan utuh, jadi tombolnya hanya akan jadi kekacauan tanpa guna.
    $perluLipat = $panjang > 240;

    /* ViewEntry dengan view kustom menggantikan SELURUH render entri, termasuk
       pembungkus labelnya, jadi labelnya digambar sendiri di sini. */
    $label = $getLabel();
@endphp

@if(filled($label))
    <div class="mb-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
@endif

@if(! $adaIsi)
    <p class="fi-in-placeholder text-sm text-gray-400 dark:text-gray-500">Belum ada deskripsi</p>
@elseif(! $perluLipat)
    <div class="prose prose-sm max-w-none text-gray-950 dark:prose-invert dark:text-white">
        {!! $isi !!}
    </div>
@else
    <div x-data="{ terbuka: false }">
        <div class="prose prose-sm max-w-none text-gray-950 dark:prose-invert dark:text-white"
             :class="terbuka || 'line-clamp-3'">
            {!! $isi !!}
        </div>

        <button type="button"
                x-on:click="terbuka = ! terbuka"
                class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
            <span x-text="terbuka ? 'Ciutkan' : 'Lihat selengkapnya'"></span>
            <span class="text-xs" x-text="terbuka ? '▲' : '▼'"></span>
        </button>
    </div>
@endif
