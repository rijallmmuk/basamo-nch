<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Apa yang dicadangkan</x-slot>
        <x-slot name="description">
            Setiap arsip memuat seluruh basis data ditambah berkas tersimpan: materi
            SLC, foto produk UMKM, dan seluruh unggahan lain. Cadangan otomatis
            berjalan tiap hari pukul 00.30 dan diperiksa keutuhannya pukul 06.15.
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            Arsip tersimpan di server yang sama, sehingga <strong>tidak melindungi dari
            kehilangan server</strong>. Unduh berkalanya ke komputer atau media lain,
            lalu kembalikan lewat "Pulihkan dari Berkas" bila sewaktu-waktu diperlukan.
            Database dan berkas dapat diunduh terpisah agar tidak perlu menarik arsip
            besar hanya untuk mengambil salah satunya.
        </p>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Daftar Cadangan</x-slot>

        @if (empty($this->cadangan))
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Belum ada cadangan. Tekan "Buat Cadangan Sekarang" di atas.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left dark:border-white/10">
                            <th class="py-2 pe-3 font-semibold">Berkas</th>
                            <th class="py-2 pe-3 font-semibold">Dibuat</th>
                            <th class="py-2 pe-3 font-semibold">Ukuran</th>
                            <th class="py-2 font-semibold">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($this->cadangan as $arsip)
                            <tr>
                                <td class="py-3 pe-3 font-mono text-xs">{{ $arsip['nama'] }}</td>
                                <td class="py-3 pe-3 whitespace-nowrap">
                                    {{ $arsip['dibuat']->translatedFormat('d M Y, H:i') }}
                                    <span class="block text-xs text-gray-500">{{ $arsip['dibuat']->diffForHumans() }}</span>
                                </td>
                                <td class="py-3 pe-3 whitespace-nowrap tabular-nums">
                                    {{ number_format($arsip['ukuran'] / 1048576, 1, ',', '.') }} MB
                                </td>
                                <td class="py-3">
                                    <div class="flex flex-wrap gap-2">
                                        {{ ($this->unduhDatabaseAction)(['path' => $arsip['path']]) }}
                                        {{ ($this->unduhBerkasAction)(['path' => $arsip['path']]) }}
                                        {{ ($this->unduhAction)(['path' => $arsip['path']]) }}
                                        {{ ($this->pulihkanAction)(['path' => $arsip['path']]) }}
                                        {{ ($this->hapusAction)(['path' => $arsip['path']]) }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
