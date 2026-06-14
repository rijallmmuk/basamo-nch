@props(['status' => 'available'])

@php
    $map = [
        'completed' => ['label' => 'Selesai', 'icon' => 'heroicon-s-check-circle', 'class' => 'bg-emerald-100 text-emerald-700'],
        'in_progress' => ['label' => 'Sedang Dipelajari', 'icon' => 'heroicon-s-play-circle', 'class' => 'bg-indigo-100 text-indigo-700'],
        'locked' => ['label' => 'Terkunci', 'icon' => 'heroicon-s-lock-closed', 'class' => 'bg-gray-100 text-gray-500'],
        'available' => ['label' => 'Belum Dimulai', 'icon' => 'heroicon-s-book-open', 'class' => 'bg-sky-50 text-sky-700'],
    ];
    $c = $map[$status] ?? $map['available'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {$c['class']}"]) }}>
    <x-dynamic-component :component="$c['icon']" class="h-4 w-4" />
    {{ $c['label'] }}
</span>
