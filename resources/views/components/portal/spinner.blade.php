{{-- Spinner "sedang proses" — dipakai tombol submit dua-state (x-portal.button,
     x-portal.confirm-dialog) & auth (login/ganti-sandi). Satu markup, jangan disalin ulang. --}}
@props(['class' => 'h-4 w-4'])

<svg {{ $attributes->merge(['class' => $class.' animate-spin']) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
</svg>
