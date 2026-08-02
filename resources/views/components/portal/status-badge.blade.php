@props(['status' => 'available'])

@php
    // Warna status memakai token semantik; warna resmi SDGs hanya untuk visualisasi data.
    $map = [
        'completed' => ['label' => 'Selesai', 'icon' => 'heroicon-s-check-circle', 'class' => 'bg-success-container text-on-success-container'],
        'in_progress' => ['label' => 'Sedang Dipelajari', 'icon' => 'heroicon-s-play-circle', 'class' => 'bg-primary/10 text-primary'],
        'locked' => ['label' => 'Terkunci', 'icon' => 'heroicon-s-lock-closed', 'class' => 'bg-surface-container-high text-outline'],
        'available' => ['label' => 'Belum Dimulai', 'icon' => 'heroicon-s-book-open', 'class' => 'bg-info-container text-on-info-container'],
    ];
    $c = $map[$status] ?? $map['available'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {$c['class']}"]) }}>
    <x-dynamic-component :component="$c['icon']" class="h-4 w-4" />
    {{ $c['label'] }}
</span>
