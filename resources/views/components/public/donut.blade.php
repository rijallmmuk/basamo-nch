@props([
    'value',
    'max' => 100,
    'label' => null,
    'caption' => null,
    'tone' => 'primary',
    'size' => 168,
])

{{-- Donat persentase sebagai SVG sebaris, TANPA pustaka grafik.

     Halaman publik harus tetap tergambar di jaringan buruk dan pada peramban yang
     memblokir skrip pihak ketiga, dan halaman nagari kerap dibuka lewat sinyal
     seluler yang tipis. Keputusan yang sama sudah dipakai grafik tren EWS.

     Nilai null DIBEDAKAN dari nol: cincinnya dibiarkan kosong dan angkanya diganti
     keterangan, karena "belum ada data" dan "capaiannya nol persen" adalah dua hal
     yang sangat berbeda bagi pembaca. --}}
@php
    $adaNilai = $value !== null && is_numeric($value);
    $maks = max(0.01, (float) $max);
    $persen = $adaNilai ? max(0.0, min(100.0, ((float) $value / $maks) * 100)) : 0.0;

    // r dipilih agar keliling lingkarannya bulat dipakai sebagai dasar dasharray.
    $r = 54;
    $keliling = 2 * M_PI * $r;
    $terisi = round($keliling * $persen / 100, 2);

    $warna = match ($tone) {
        'secondary' => '#c8a86b',
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
        'info' => '#0284c7',
        default => '#003857',
    };
@endphp

<figure {{ $attributes->class('flex flex-col items-center') }}>
    <div class="relative" style="width: {{ $size }}px; height: {{ $size }}px">
        <svg viewBox="0 0 128 128" class="h-full w-full -rotate-90" role="img"
             aria-label="{{ $label ?? 'Capaian' }}: {{ $adaNilai ? number_format($persen, 1, ',', '.').' persen' : 'belum ada data' }}">
            <circle cx="64" cy="64" r="{{ $r }}" fill="none" stroke="currentColor"
                    class="text-surface-container-high" stroke-width="14" />

            @if($adaNilai && $persen > 0)
                <circle cx="64" cy="64" r="{{ $r }}" fill="none" stroke="{{ $warna }}" stroke-width="14"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $terisi }} {{ round($keliling - $terisi, 2) }}" />
            @endif
        </svg>

        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
            @if($adaNilai)
                <span class="text-2xl font-black tabular-nums tracking-tight text-primary">
                    {{ number_format($persen, 1, ',', '.') }}<span class="text-base">%</span>
                </span>
            @else
                <span class="px-4 text-xs font-bold leading-tight text-on-surface-variant">Belum ada data</span>
            @endif
        </div>
    </div>

    @if(filled($label) || filled($caption))
        <figcaption class="mt-3 text-center">
            @if(filled($label))
                <p class="text-sm font-bold text-on-surface">{{ $label }}</p>
            @endif
            @if(filled($caption))
                <p class="mt-0.5 text-xs text-on-surface-variant">{{ $caption }}</p>
            @endif
        </figcaption>
    @endif
</figure>
