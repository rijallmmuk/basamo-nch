@props([
    'nama' => '',
    'ratio' => '4:3',
])

@php
    // Cover pelatihan digambar, bukan diunggah: nol berkas, otomatis ikut berubah bila
    // nama tema diperbaiki, dan tetap tajam di layar besar. Warna aksen diturunkan
    // secara TETAP dari nama tema (crc32) supaya satu tema selalu tampil sama.
    $nama = trim((string) $nama) !== '' ? trim((string) $nama) : 'Tanpa Tema';

    [$w, $h] = $ratio === '16:9' ? [1200, 675] : [1200, 900];

    $aksen = ['#fed33e', '#e8a33d', '#7ac0a0', '#8fb8e0', '#d9a7c7', '#c9d97a'];
    $warna = $aksen[crc32($nama) % count($aksen)];

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
    <g fill="{{ $warna }}" opacity="0.10">
        <path d="M0 {{ $h }} L{{ $w * 0.18 }} {{ $h * 0.52 }} L{{ $w * 0.36 }} {{ $h }} Z" />
        <path d="M{{ $w * 0.62 }} {{ $h }} L{{ $w * 0.8 }} {{ $h * 0.44 }} L{{ $w }} {{ $h }} Z" />
    </g>

    <rect x="0" y="0" width="{{ $w }}" height="14" fill="{{ $warna }}" opacity="0.9" />

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
