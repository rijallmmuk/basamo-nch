{{-- Pemilih nagari untuk halaman superadmin lintas-nagari (Capaian SDGs, Cuaca, Warga,
     Wilayah, Daftar UMKM, Daftar Produk UMKM). Native <select> (tanpa JS tambahan),
     gaya "outlined field": label kecil mengambang di garis batas atas + nilai tebal
     di bawahnya, TANPA ikon dekoratif (koreksi user 2026-07-14) — chevron kanan
     dipertahankan sbg penanda fungsional bahwa elemen ini bisa diklik/dipilih. --}}
@props(['pilihan', 'id' => 'pilih-nagari'])

@if ($pilihan->isNotEmpty())
    <div class="relative mb-2 block w-full">
        <label for="{{ $id }}"
            class="absolute -top-2 left-3 bg-white px-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:bg-gray-900 dark:text-gray-500">
            Mengelola Nagari
        </label>
        <select id="{{ $id }}" wire:model.live="nagariId"
            class="w-full min-w-[15rem] cursor-pointer appearance-none rounded-xl border border-gray-300 bg-white py-2.5 pl-3.5 pr-9 text-sm font-bold text-gray-950 shadow-sm transition-colors hover:border-gray-400 focus:border-[#003857] focus:outline-none focus:ring-2 focus:ring-[#003857]/10 dark:border-white/15 dark:bg-gray-900 dark:text-white dark:hover:border-white/25">
            @foreach ($pilihan as $nagari)
                <option value="{{ $nagari->id }}">{{ $nagari->nama_lengkap }}</option>
            @endforeach
        </select>
        <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </div>
@endif
