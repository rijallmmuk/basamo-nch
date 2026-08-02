@props([
    'name' => '?',
    'size' => 'md',
    'variant' => 'soft',
    'src' => null,
])

<x-app.avatar
    :name="$name"
    :size="$size"
    :variant="$variant === 'gray' ? 'neutral' : $variant"
    :src="$src"
    {{ $attributes }} />
