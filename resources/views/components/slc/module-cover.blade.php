@props([
    'judul' => '',
    'ratio' => '16:9',
    /* Kunci warna aksen; lihat penjelasan yang sama pada tema-cover. */
    'seed' => null,
])

@php
    /* Sampul modul digambar, bukan diunggah, dengan pola yang sama seperti
       {@see components/slc/tema-cover}. Warna aksen diturunkan TETAP dari judul,
       jadi satu modul selalu tampil sama sementara antar modul berbeda. */
    $judul = trim((string) $judul) !== '' ? trim((string) $judul) : 'Modul';

    [$w, $h] = $ratio === '4:3' ? [1200, 900] : [1200, 675];

    $aksen = ['#fed33e', '#e8a33d', '#7ac0a0', '#8fb8e0', '#d9a7c7', '#c9d97a'];
    // Kunci angka dipakai apa adanya; lihat penjelasan pada tema-cover.
    $kunci = $seed ?? $judul;
    $warna = $aksen[(is_numeric($kunci) ? (int) $kunci : crc32((string) $kunci)) % count($aksen)];

    $panjang = mb_strlen($judul);
    $fontSize = match (true) {
        $panjang <= 18 => 88,
        $panjang <= 34 => 70,
        $panjang <= 60 => 56,
        default => 46,
    };
    $perBaris = max(12, (int) floor(($w - 200) / ($fontSize * 0.56)));

    $baris = collect(explode("\n", wordwrap($judul, $perBaris, "\n", true)));
    if ($baris->count() > 3) {
        $baris = $baris->take(3);
        $baris[2] = mb_substr($baris[2], 0, max(1, $perBaris - 1)).'…';
    }

    $lineHeight = $fontSize * 1.18;
    $mulaiY = ($h / 2) - (($baris->count() - 1) * $lineHeight / 2) + ($fontSize * 0.34);
    $uid = 'modul-'.substr(md5($judul), 0, 8);
@endphp

<svg {{ $attributes->merge(['class' => 'h-auto w-full', 'role' => 'img']) }}
     viewBox="0 0 {{ $w }} {{ $h }}" xmlns="http://www.w3.org/2000/svg"
     aria-label="Sampul modul {{ $judul }}" preserveAspectRatio="xMidYMid slice">
    <defs>
        <linearGradient id="{{ $uid }}-bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#1b4f72" />
            <stop offset="55%" stop-color="#003857" />
            <stop offset="100%" stop-color="#002338" />
        </linearGradient>
    </defs>

    <rect width="{{ $w }}" height="{{ $h }}" fill="url(#{{ $uid }}-bg)" />

    {{-- Motif gonjong, sama dengan sampul pelatihan supaya keduanya satu keluarga. --}}
    <g fill="{{ $warna }}" opacity="0.16">
        <path d="M0 {{ $h }} L{{ $w * 0.18 }} {{ $h * 0.52 }} L{{ $w * 0.36 }} {{ $h }} Z" />
        <path d="M{{ $w * 0.62 }} {{ $h }} L{{ $w * 0.8 }} {{ $h * 0.44 }} L{{ $w }} {{ $h }} Z" />
    </g>

    {{-- Tebal garis mengikuti tinggi kotak, bukan angka tetap. Pada thumbnail 64 px
         garis 14 unit menyusut jadi kurang dari satu piksel, sehingga warna aksen yang
         membedakan tiap catatan hilang sama sekali. --}}
    <rect x="0" y="0" width="{{ $w }}" height="{{ round($h * 0.035) }}" fill="{{ $warna }}" opacity="0.9" />

    {{-- Judul SELALU digambar; lihat alasan yang sama pada tema-cover. --}}
    <text x="{{ $w / 2 }}" y="{{ $mulaiY }}" text-anchor="middle"
          font-family="'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif"
          font-size="{{ $fontSize }}" font-weight="800" fill="#ffffff">
        @foreach ($baris as $i => $teks)
            <tspan x="{{ $w / 2 }}" dy="{{ $i === 0 ? 0 : $lineHeight }}">{{ $teks }}</tspan>
        @endforeach
    </text>

    <text x="{{ $w / 2 }}" y="{{ $h - 38 }}" text-anchor="middle"
          font-family="'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif"
          font-size="26" font-weight="800" fill="{{ $warna }}" letter-spacing="5">
        MODUL
    </text>
</svg>
