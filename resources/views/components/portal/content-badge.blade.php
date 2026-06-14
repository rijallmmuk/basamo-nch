@props(['type' => 'text'])

@php
    $map = [
        'video' => ['label' => 'Video', 'icon' => 'heroicon-s-play-circle', 'class' => 'bg-purple-50 text-purple-700'],
        'pdf' => ['label' => 'PDF', 'icon' => 'heroicon-s-document-text', 'class' => 'bg-rose-50 text-rose-700'],
        'text' => ['label' => 'Teks', 'icon' => 'heroicon-s-document', 'class' => 'bg-sky-50 text-sky-700'],
    ];
    $c = $map[$type] ?? $map['text'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {$c['class']}"]) }}>
    <x-dynamic-component :component="$c['icon']" class="h-3.5 w-3.5" />
    {{ $c['label'] }}
</span>
