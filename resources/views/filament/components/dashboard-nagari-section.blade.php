{{-- Pembatas antara dua bagian dasbor superadmin/DPMD: di atasnya angka lintas
     nagari, di bawahnya angka satu nagari. Pemilih nagari sengaja ditaruh DI SINI,
     bukan di puncak halaman (koreksi user 2026-07-31), supaya jelas apa saja yang
     dipengaruhinya. --}}
<div class="mt-4 border-t border-gray-200 pt-5 dark:border-white/10">
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
        <div>
            <h2 class="text-base font-bold text-gray-950 dark:text-white">Analisis Per Nagari</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                Seluruh angka di bawah ini hanya untuk nagari yang dipilih.
            </p>
        </div>

        <div class="w-full sm:w-72">
            <x-filament.nagari-picker :pilihan="$pilihan" />
        </div>
    </div>
</div>
