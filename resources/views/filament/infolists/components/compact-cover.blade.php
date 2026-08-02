@php
    $url = $getRecord()->coverUrl();
@endphp

<div class="w-full flex flex-col items-center justify-center p-1">
    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-2xl border border-gray-200 bg-gray-100 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <img src="{{ $url }}" alt="Sampul" class="w-full h-full object-cover" />
    </div>
</div>
