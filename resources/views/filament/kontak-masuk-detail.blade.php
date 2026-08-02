{{-- Detail pesan masuk "Hubungi Kami" beranda publik (read-only). --}}
<div class="space-y-5 text-sm">
    <dl class="divide-y divide-gray-100 rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10">
        @foreach(array_filter([
            'Kategori' => $kontak->kategori->getLabel(),
            'Pengirim' => $kontak->user_id ? ($kontak->user->name . ' (Akun Internal)') : 'Tamu (Form Publik)',
            'Nama' => $kontak->nama,
            'Email' => $kontak->email,
            'No. HP' => $kontak->no_hp,
            'Nama nagari' => $kontak->nama_nagari,
            'Diajukan' => $kontak->created_at->translatedFormat('d M Y, H:i'),
        ]) as $label => $value)
            <div class="flex gap-4 px-4 py-2.5">
                <dt class="w-32 shrink-0 text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                <dd class="min-w-0 flex-1 font-medium text-gray-950 dark:text-white">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <div>
        <p class="mb-2 font-bold text-gray-950 dark:text-white">Isi Pesan</p>
        <p class="whitespace-pre-line rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">{{ $kontak->isi }}</p>
    </div>

    @if($kontak->balasan)
    <div>
        <p class="mb-2 font-bold text-gray-950 dark:text-white">Balasan Tindak Lanjut</p>
        <p class="whitespace-pre-line rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-primary-800 dark:border-primary-900 dark:bg-primary-900/20 dark:text-primary-300">{{ $kontak->balasan }}</p>
    </div>
    @endif

    @php($lampiran = $kontak->getMedia('file_pendukung'))
    @if($lampiran->isNotEmpty())
    <div>
        <p class="mb-2 font-bold text-gray-950 dark:text-white">File Pendukung ({{ $lampiran->count() }})</p>
        <div class="space-y-2">
            @foreach($lampiran as $file)
                <a
                    href="{{ $file->getUrl() }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 transition hover:border-primary-300 hover:bg-primary-50 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-700 dark:hover:bg-primary-950/20"
                >
                    @if(str_starts_with($file->mime_type ?? '', 'image/'))
                        <img src="{{ $file->getUrl() }}" alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover">
                    @else
                        <x-filament::icon icon="heroicon-o-paper-clip" class="h-6 w-6 shrink-0 text-primary-600" />
                    @endif
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-gray-950 dark:text-white">{{ $file->file_name }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $file->human_readable_size }} · Buka atau unduh lampiran</span>
                    </span>
                    <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-5 w-5 shrink-0 text-gray-400" />
                </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
