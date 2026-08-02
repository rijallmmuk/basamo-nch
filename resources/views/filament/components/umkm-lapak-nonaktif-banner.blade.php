{{-- Peringatan di dashboard "Kelola UMKM" saat lapak pemilik NONAKTIF: tetap bisa
     dikelola tapi tak tampil ke publik. Reaktivasi = wewenang Operator Nagari
     (status aktif/nonaktif lapak dikendalikan operator, bukan pemilik). --}}
<div class="rounded-xl border border-warning-300 bg-warning-50 p-4 dark:border-warning-400/40 dark:bg-warning-400/10">
    <div class="flex items-start gap-3">
        <x-heroicon-o-eye-slash class="mt-0.5 h-6 w-6 shrink-0 text-warning-600 dark:text-warning-400" />
        <div class="text-sm">
            <p class="font-bold text-warning-700 dark:text-warning-300">Lapak Anda sedang NONAKTIF</p>
            <p class="mt-1 leading-relaxed text-warning-700/90 dark:text-warning-200/80">
                Lapak &amp; produk Anda <strong>tidak tampil</strong> di katalog publik. Anda tetap bisa
                menyiapkan produk dan memperbarui profil di sini. Hubungi <strong>Operator Nagari</strong>
                untuk mengaktifkan kembali.
            </p>
        </div>
    </div>
</div>
