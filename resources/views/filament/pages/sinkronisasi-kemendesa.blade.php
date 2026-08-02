<x-filament-panels::page>
    <div class="flex flex-col gap-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h2 class="text-lg font-semibold tracking-tight text-gray-950 dark:text-white">Mengapa Perlu Sinkronisasi?</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Karena sistem pengamanan (Firewall) server Kemendesa memblokir seluruh koneksi dari Data Center, aplikasi yang dijalankan di layanan hosting seperti Hostinger tidak dapat menarik data IDM dan SDGs secara otomatis. Penarikan data hanya bisa dilakukan jika aplikasi dijalankan di komputer lokal menggunakan jaringan internet perumahan biasa (seperti Indihome).
            </p>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Fitur ini hadir untuk mempermudah alur kerja Anda tanpa perlu menyentuh database:
            </p>
            <ol class="mt-4 list-decimal pl-5 text-sm space-y-2 text-gray-500 dark:text-gray-400">
                <li>Buka aplikasi Basamo NCH ini di komputer lokal (Localhost) Anda.</li>
                <li>Lakukan pembaruan IDM dan SDGs seperti biasa di halaman Status Desa (ini pasti berhasil karena memakai internet rumah).</li>
                <li>Setelah data masuk ke komputer lokal, buka halaman ini di localhost, lalu klik tombol <b>Ekspor Data (Localhost)</b>. Anda akan mendapatkan file <code class="rounded bg-gray-100 px-1 py-0.5 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-300">.json</code>.</li>
                <li>Buka website produksi Anda (di Hostinger), buka halaman ini, klik tombol <b>Impor Data (Hostinger)</b>, dan unggah file <code class="rounded bg-gray-100 px-1 py-0.5 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-300">.json</code> tersebut. Data akan ter-sinkronisasi sempurna!</li>
            </ol>
        </div>
    </div>
</x-filament-panels::page>
