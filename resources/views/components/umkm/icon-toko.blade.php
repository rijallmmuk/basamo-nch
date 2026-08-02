{{-- Ikon toko/lapak (bukan Heroicons — tak ada padanan persis di set Heroicons,
     sudah dicek). Dipakai kartu produk (product-card) & pratinjau (product-form) —
     satu markup, jangan disalin ulang. --}}
@props(['class' => 'h-3.5 w-3.5 shrink-0 text-outline'])

<svg {{ $attributes->merge(['class' => $class]) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2.345 2.245A1.5 1.5 0 0 1 3.5 1.75h9a1.5 1.5 0 0 1 1.155.495l.086.1c.899 1.05.163 2.655-1.216 2.655a1.6 1.6 0 0 1-1.317-.69.25.25 0 0 0-.416 0 1.6 1.6 0 0 1-2.634 0 .25.25 0 0 0-.416 0 1.6 1.6 0 0 1-2.634 0 .25.25 0 0 0-.416 0 1.6 1.6 0 0 1-1.317.69C2.096 5 1.36 3.395 2.259 2.345l.086-.1ZM3 6.694V12.5h-.25a.75.75 0 0 0 0 1.5h10.5a.75.75 0 0 0 0-1.5H13V6.694a3.1 3.1 0 0 1-1.517-.395.25.25 0 0 0-.24-.004A3.1 3.1 0 0 1 9.8 6.65a3.1 3.1 0 0 1-1.443-.355.25.25 0 0 0-.24.004A3.1 3.1 0 0 1 6.2 6.65a3.1 3.1 0 0 1-1.443-.355.25.25 0 0 0-.24.004A3.1 3.1 0 0 1 3 6.694ZM5.5 8.25a.75.75 0 0 0-.75.75v1.5c0 .414.336.75.75.75h5a.75.75 0 0 0 .75-.75V9a.75.75 0 0 0-.75-.75h-5Z"/></svg>
