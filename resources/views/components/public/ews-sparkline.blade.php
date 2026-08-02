@props(['label', 'labels', 'nilai', 'warna' => '#0284c7'])

{{-- Grafik tren sederhana sebagai SVG sebaris.

     Sengaja tanpa pustaka grafik: halaman kebencanaan harus tetap tergambar di
     jaringan buruk dan pada peramban yang memblokir skrip pihak ketiga. Titik
     yang kosong memutus garis, TIDAK ditarik ke nol: pada tinggi air, nol berarti
     sungai kering dan garis yang menukik ke dasar akan terbaca sebagai kejadian
     yang tidak pernah terjadi. --}}
@php
    $lebar = 600;
    $tinggi = 90;
    $jumlah = count($nilai);

    $terisi = array_values(array_filter($nilai, fn ($n) => $n !== null));
    $min = $terisi === [] ? 0.0 : (float) min($terisi);
    $maks = $terisi === [] ? 1.0 : (float) max($terisi);
    $rentang = $maks - $min;

    $atas = 6.0;
    $bawah = (float) $tinggi - 10.0;

    $x = fn (int $i): float => $jumlah > 1 ? round($i * ($lebar / ($jumlah - 1)), 2) : 0.0;

    // Deret DATAR (semua pembacaan sama, keadaan paling lazim saat sungai tenang)
    // digambar di TENGAH, bukan di dasar. Menempatkannya di dasar membuat nilai
    // tetap 430 cm terlihat seperti titik terendah yang pernah tercatat, padahal
    // tidak ada yang naik maupun turun sama sekali.
    $y = fn (float $n): float => $rentang > 0
        ? round($bawah - (($n - $min) / $rentang) * ($bawah - $atas), 2)
        : round(($atas + $bawah) / 2, 2);

    // Bangun potongan-potongan garis; tiap null memulai potongan baru.
    $potongan = [];
    $sekarang = [];

    foreach ($nilai as $i => $n) {
        if ($n === null) {
            if (count($sekarang) > 1) {
                $potongan[] = $sekarang;
            }
            $sekarang = [];

            continue;
        }

        $sekarang[] = $x($i).','.$y((float) $n);
    }

    if (count($sekarang) > 1) {
        $potongan[] = $sekarang;
    }

    $terakhir = null;
    foreach (array_reverse($nilai, true) as $i => $n) {
        if ($n !== null) {
            $terakhir = ['x' => $x($i), 'y' => $y((float) $n), 'n' => (float) $n];
            break;
        }
    }
@endphp

<figure>
    <figcaption class="mb-1 flex items-baseline justify-between text-xs">
        <span class="font-semibold text-on-surface-variant">{{ $label }}</span>
        <span class="font-bold text-on-surface">
            @if($terisi === [])
                --
            @elseif($rentang <= 0)
                {{ number_format($min, 0, ',', '.') }} <span class="font-normal text-on-surface-variant">(tetap)</span>
            @else
                {{ number_format($min, 0, ',', '.') }} – {{ number_format($maks, 0, ',', '.') }}
            @endif
        </span>
    </figcaption>

    {{-- Tinggi dikunci lewat style, bukan hanya kelas: komponen ini juga dipakai di
         dalam panel Filament, yang reset CSS-nya membuat SVG menjulur jauh lebih
         tinggi daripada di halaman publik. --}}
    <svg viewBox="0 0 {{ $lebar }} {{ $tinggi }}" preserveAspectRatio="none"
         style="height: 5rem"
         class="w-full overflow-visible rounded-xl bg-surface-container-low"
         role="img"
         aria-label="{{ $label }}, {{ $jumlah }} pembacaan dari {{ $labels[0] ?? '-' }} sampai {{ $labels[$jumlah - 1] ?? '-' }}">
        @foreach($potongan as $garis)
            <polyline points="{{ implode(' ', $garis) }}"
                      fill="none" stroke="{{ $warna }}" stroke-width="2"
                      stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
        @endforeach

        @if($terakhir)
            <circle cx="{{ $terakhir['x'] }}" cy="{{ $terakhir['y'] }}" r="3" fill="{{ $warna }}" />
        @endif
    </svg>

    <div class="mt-1 flex justify-between text-[10px] text-on-surface-muted">
        <span>{{ $labels[0] ?? '' }}</span>
        <span>{{ $labels[$jumlah - 1] ?? '' }}</span>
    </div>
</figure>
