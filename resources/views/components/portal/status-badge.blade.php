@props(['status' => 'available'])

@php
    // Warna status kanonik (selaras home.blade.php): selesai=hijau sdg-3,
    // sedang=primary, terkunci=surface, belum=biru sdg-14.
    $map = [
        'completed' => ['label' => 'Selesai', 'icon' => 'heroicon-s-check-circle', 'class' => 'bg-sdg-3/10 text-sdg-3'],
        'in_progress' => ['label' => 'Sedang Dipelajari', 'icon' => 'heroicon-s-play-circle', 'class' => 'bg-primary/10 text-primary'],
        'locked' => ['label' => 'Terkunci', 'icon' => 'heroicon-s-lock-closed', 'class' => 'bg-surface-container-high text-outline'],
        'available' => ['label' => 'Belum Dimulai', 'icon' => 'heroicon-s-book-open', 'class' => 'bg-sdg-14/10 text-sdg-14'],
    ];
    $c = $map[$status] ?? $map['available'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {$c['class']}"]) }}>
    <x-dynamic-component :component="$c['icon']" class="h-4 w-4" />
    {{ $c['label'] }}
</span>
