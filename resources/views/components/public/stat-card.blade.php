@props([
    'label',
    'value',
    'icon' => 'heroicon-o-chart-bar',
    'description' => null,
    'suffix' => null,
    'tone' => 'primary',
    'href' => null,
])

{{-- Kartu angka TUNGGAL untuk seluruh halaman publik: beranda induk, beranda
     nagari, dan Teras Nagari. Jangan menyalin bentuknya ke halaman lain; perbedaan
     kecil antar salinan adalah cara tercepat membuat tampilan tidak konsisten.

     ATURAN ISIAN OPSIONAL: `description` boleh kosong, dan barisnya TETAP memakan
     tinggi yang sama lewat `min-h`. Tanpa itu, satu kartu tanpa keterangan akan
     lebih pendek daripada tetangganya dan seluruh baris grid ikut miring. Aturan
     yang sama berlaku untuk setiap kartu publik: yang opsional tidak boleh
     mengubah proporsi yang wajib. --}}
@php
    $warna = match ($tone) {
        'secondary' => 'bg-secondary/12 text-secondary',
        'success' => 'bg-emerald-500/12 text-emerald-600',
        'warning' => 'bg-amber-500/12 text-amber-600',
        'info' => 'bg-sky-500/12 text-sky-600',
        default => 'bg-primary/8 text-primary',
    };

    $tampilNilai = is_numeric($value)
        ? number_format((float) $value, 0, ',', '.')
        : (string) $value;

    $kelas = 'group flex h-full flex-col rounded-2xl border border-outline-variant bg-surface-container-lowest p-4 shadow-sm transition-all hover:border-primary/40 hover:shadow-md sm:p-5';
    $tag = filled($href) ? 'a' : 'div';
@endphp

<{{ $tag }} @if(filled($href)) href="{{ $href }}" @endif {{ $attributes->class($kelas) }}>
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $warna }}">
        <x-dynamic-component :component="$icon" class="h-5 w-5" />
    </span>

    <p class="mt-3 flex items-baseline gap-1 text-2xl font-extrabold tabular-nums tracking-tight text-primary">
        {{ $tampilNilai }}
        @if(filled($suffix))
            <span class="text-sm font-bold text-on-surface-variant">{{ $suffix }}</span>
        @endif
    </p>

    <p class="mt-0.5 text-xs font-bold leading-snug text-on-surface">{{ $label }}</p>

    {{-- Baris keterangan selalu ada, terisi atau tidak. --}}
    <p class="mt-1 min-h-8 text-[11px] leading-snug text-on-surface-variant">{{ $description }}</p>

    @if(filled($href))
        <span class="mt-auto inline-flex items-center gap-1 pt-2 text-[11px] font-bold uppercase tracking-wider text-primary transition-all group-hover:gap-2">
            Lihat <x-heroicon-o-arrow-right class="h-3 w-3" />
        </span>
    @endif
</{{ $tag }}>
