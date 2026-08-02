@props(['cuaca', 'lokasi' => null])

{{-- Kartu "Saat Ini" BMKG — dipakai bersama oleh beranda nagari & halaman /cuaca. --}}
<div {{ $attributes->class(['relative overflow-hidden rounded-3xl bg-gradient-to-br from-secondary-container/40 via-primary/5 to-secondary-container/20 p-6 sm:p-8']) }}>
    <div class="flex flex-col items-start gap-6 sm:flex-row sm:items-center">
        @if($cuaca['ikon'])
            <img src="{{ $cuaca['ikon'] }}" alt="{{ $cuaca['kondisi'] }}" class="h-20 w-20 shrink-0 sm:h-24 sm:w-24">
        @endif
        <div class="min-w-0 flex-1 space-y-3">
            <p class="text-xs font-bold uppercase tracking-widest text-secondary">Saat ini</p>
            <p class="flex flex-wrap items-baseline gap-2">
                <span class="text-5xl font-extrabold tracking-tighter text-primary sm:text-6xl">{{ $cuaca['suhu'] }}&deg;C</span>
                <span class="text-lg font-bold text-on-surface-variant">{{ $cuaca['kondisi'] }}</span>
                @if($lokasi)
                    <span class="text-sm text-on-surface-variant">&middot; di {{ $lokasi }}</span>
                @endif
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach([
                    ['heroicon-o-cloud', 'Kelembapan', $cuaca['kelembapan'] !== null ? $cuaca['kelembapan'].'%' : '—'],
                    ['heroicon-o-flag', 'Kecepatan Angin', $cuaca['kecepatan_angin'] !== null ? number_format($cuaca['kecepatan_angin'], 1, ',', '.').' km/jam' : '—'],
                    ['heroicon-o-arrow-up-right', 'Arah Angin dari', $cuaca['arah_angin_dari'] ?? '—'],
                    ['heroicon-o-eye', 'Jarak Pandang', $cuaca['jarak_pandang'] ?? '—'],
                ] as [$icon, $label, $nilai])
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-background/70 px-3 py-1.5 text-xs font-semibold text-on-surface-variant">
                        <x-dynamic-component :component="$icon" class="h-3.5 w-3.5 shrink-0 text-secondary" />
                        {{ $label }}: <span class="font-bold text-primary">{{ $nilai }}</span>
                    </span>
                @endforeach
            </div>
            <p class="text-[11px] font-bold uppercase tracking-widest text-on-surface-muted">Sumber: BMKG</p>
        </div>
    </div>
</div>
