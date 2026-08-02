{{-- Kartu pemilih nagari di atas tabel — dipakai ListPenduduks/ListUmkmProfiles/
     ListUmkmProducts (2026-07-14: diminta user agar setara SDGs/Cuaca, bukan lagi
     pola "auto nagari pertama + tombol Kembali ke Nagari"). Hanya superadmin (operator
     sudah ter-scope ke nagarinya sendiri, tanpa perlu memilih). wire:model.live="nagariId"
     mengikat ke properti Livewire HALAMAN ini sendiri (Schema View tak membuat
     boundary Livewire baru) — lihat updatedNagariId() di tiap halaman utk penerapannya
     ke NagariContext (query tabel di-scope lewat resource statis, hanya baca session). --}}
@php
    $allowedRoles = $roles ?? ['superadmin', 'dpmd'];
    $pilihanNagari = auth()->user()?->hasAnyRole($allowedRoles)
        ? \App\Models\Nagari::query()->orderBy('nama')->get()
        : collect();
@endphp
<x-filament.nagari-picker :pilihan="$pilihanNagari" />
