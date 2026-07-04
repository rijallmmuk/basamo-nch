{{-- Input kata sandi + tombol lihat/sembunyikan. Meneruskan semua atribut
     (id, name, class, autocomplete, placeholder, autofocus, dll) ke <input>.
     Toggle digerakkan handler vanilla di app.js (data-password-toggle) — TIDAK
     bergantung Alpine, agar tetap berfungsi di halaman login (tanpa Livewire/Alpine). --}}
<div class="relative">
    <input {{ $attributes->merge(['type' => 'password', 'class' => 'pr-11']) }} />

    <button type="button" tabindex="-1" data-password-toggle aria-label="Lihat kata sandi"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-on-surface-variant transition-colors hover:text-on-surface">
        <x-heroicon-o-eye data-eye class="h-5 w-5" />
        <x-heroicon-o-eye-slash data-eye class="hidden h-5 w-5" />
    </button>
</div>
