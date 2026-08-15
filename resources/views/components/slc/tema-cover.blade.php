@props([
    'nama' => '',
    'ratio' => '4:3',
    /* Kunci warna aksen. Bila kosong, warnanya diturunkan dari nama tema, sehingga
       dua pelatihan bertema sama mendapat warna yang sama persis. Isi dengan id
       pelatihan supaya masing-masing punya warnanya sendiri. */
    'seed' => null,
])

@php
    // Cover pelatihan digambar, bukan diunggah: nol berkas, otomatis ikut berubah bila
    // nama tema diperbaiki, dan tetap tajam di layar besar. Warna aksen diturunkan
    // secara TETAP dari nama tema (crc32) supaya satu tema selalu tampil sama.
    $nama = trim((string) $nama) !== '' ? trim((string) $nama) : 'Tanpa Tema';

    [$w, $h] = $ratio === '16:9' ? [1200, 675] : [1200, 900];

    $aksen = ['#fed33e', '#e8a33d', '#7ac0a0', '#8fb8e0', '#d9a7c7', '#c9d97a'];
    /* Kunci angka (id) dipakai APA ADANYA, bukan lewat crc32. crc32 atas angka
       berurutan menumpuk: "4", "5", dan "6" jatuh ke warna yang sama, sehingga tiga
       pelatihan berbeda tetap tampil kembar. Modulo langsung membuat id berurutan
       memutari seluruh palet. */
    $kunci = $seed ?? $nama;
    $warna = $aksen[(is_numeric($kunci) ? (int) $kunci : crc32((string) $kunci)) % count($aksen)];

    // Ukuran teks menyesuaikan panjang nama: nama pendek besar, nama panjang mengecil
    // dan dipecah menjadi beberapa baris (maks 4 baris, sisanya dipangkas dengan elipsis).
    $panjang = mb_strlen($nama);
    $fontSize = match (true) {
        $panjang <= 18 => 104,
        $panjang <= 34 => 84,
        $panjang <= 60 => 66,
        default => 54,
    };
    $perBaris = max(12, (int) floor(($w - 200) / ($fontSize * 0.56)));

    $baris = collect(explode("\n", wordwrap($nama, $perBaris, "\n", true)));
    if ($baris->count() > 4) {
        $baris = $baris->take(4);
        $baris[3] = mb_substr($baris[3], 0, max(1, $perBaris - 1)).'…';
    }

    $lineHeight = $fontSize * 1.18;
    $mulaiY = ($h / 2) - (($baris->count() - 1) * $lineHeight / 2) + ($fontSize * 0.34);
    $uid = 'tema-'.substr(md5($nama), 0, 8);
@endphp

<svg {{ $attributes->merge(['class' => 'h-auto w-full', 'role' => 'img']) }}
     viewBox="0 0 {{ $w }} {{ $h }}" xmlns="http://www.w3.org/2000/svg"
     aria-label="Cover pelatihan {{ $nama }}" preserveAspectRatio="xMidYMid slice">
    <defs>
        <linearGradient id="{{ $uid }}-bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#1b4f72" />
            <stop offset="55%" stop-color="#003857" />
            <stop offset="100%" stop-color="#002338" />
        </linearGradient>
    </defs>

    <rect width="{{ $w }}" height="{{ $h }}" fill="url(#{{ $uid }}-bg)" />

    {{-- Motif gonjong: siluet atap rumah gadang, samar sebagai tekstur latar. --}}
    <g fill="{{ $warna }}" opacity="0.16">
        <path d="M0 {{ $h }} L{{ $w * 0.18 }} {{ $h * 0.52 }} L{{ $w * 0.36 }} {{ $h }} Z" />
        <path d="M{{ $w * 0.62 }} {{ $h }} L{{ $w * 0.8 }} {{ $h * 0.44 }} L{{ $w }} {{ $h }} Z" />
    </g>

    {{-- Tebal garis mengikuti tinggi kotak, bukan angka tetap. Pada thumbnail 64 px
         garis 14 unit menyusut jadi kurang dari satu piksel, sehingga warna aksen yang
         membedakan tiap catatan hilang sama sekali. --}}
    <rect x="0" y="0" width="{{ $w }}" height="{{ round($h * 0.035) }}" fill="{{ $warna }}" opacity="0.9" />

    {{-- Nama tema SELALU digambar, sekecil apa pun kotaknya. Sampul bawaan adalah satu
         satunya penanda yang dimiliki pelatihan tanpa unggahan; tanpa nama di dalamnya,
         dua pelatihan hanya terbedakan oleh warna. --}}
    <text x="{{ $w / 2 }}" y="{{ $mulaiY }}" text-anchor="middle"
          font-family="'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif"
          font-size="{{ $fontSize }}" font-weight="800" fill="#ffffff">
        @foreach ($baris as $i => $teks)
            <tspan x="{{ $w / 2 }}" dy="{{ $i === 0 ? 0 : $lineHeight }}">{{ $teks }}</tspan>
        @endforeach
    </text>

    <text x="{{ $w / 2 }}" y="{{ $h - 72 }}" text-anchor="middle"
          font-family="'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif"
          font-size="34" font-weight="800" fill="{{ $warna }}" letter-spacing="6">
        BASAMO NCH
    </text>
    <text x="{{ $w / 2 }}" y="{{ $h - 36 }}" text-anchor="middle"
          font-family="'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif"
          font-size="18" font-weight="600" fill="#ffffff" opacity="0.78" letter-spacing="4">
        SMART LEARNING CENTER
    </text>
</svg>
