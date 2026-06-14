@props(['items' => []])

{{-- $items: array of ['label' => string, 'url' => ?string]. Item terakhir = halaman aktif. --}}
<nav {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-center gap-1.5 text-sm text-gray-400']) }} aria-label="Breadcrumb">
    @foreach($items as $item)
        @if(! $loop->last && ! empty($item['url']))
            <a href="{{ $item['url'] }}" class="max-w-[200px] truncate transition-colors hover:text-indigo-600">{{ $item['label'] }}</a>
        @else
            <span class="max-w-[220px] truncate font-medium text-gray-700" @if($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
        @endif
        @unless($loop->last)
            <x-heroicon-o-chevron-right class="h-3.5 w-3.5 shrink-0" />
        @endunless
    @endforeach
</nav>
