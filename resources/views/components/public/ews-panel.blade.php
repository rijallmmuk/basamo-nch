@props(['panel', 'endpoint' => null, 'panelId' => null])

{{-- Kartu pembacaan sensor EWS banjir bandang.

     ATURAN HALAMAN INI: tidak ada angka karangan, dan tidak ada angka tanpa
     keterangan umurnya. Sebelumnya bagian ini pernah berisi pembacaan sensor
     yang seluruhnya fiktif; pada halaman kebencanaan itu bukan sekadar tidak
     rapi, karena orang bisa mempercayainya saat keadaan genting. Karena itu
     status sambungan alat dan waktu pembacaan selalu ikut ditampilkan.

     Nilai disegarkan dari endpoint JSON aplikasi sendiri, BUKAN dari Blynk
     langsung, supaya token perangkat tidak pernah sampai ke peramban. --}}
@php
    $pembacaan = $panel['pembacaan'];
    $status = $panel['status'];
    $device = $panel['device'];

    $angka = fn (?float $nilai, int $desimal = 0): string => $nilai === null
        ? '--'
        : number_format($nilai, $desimal, ',', '.');
@endphp

<div id="{{ $panelId ?: 'ews-panel' }}"
     @if(filled($endpoint)) data-ews-endpoint="{{ $endpoint }}" @endif
     class="space-y-6">

    {{-- Peringatan keadaan data. Ditaruh PALING ATAS, sebelum angkanya, supaya
         pembaca tahu sejauh mana angka di bawah boleh dipercaya. --}}
    @if(! $panel['terhubung'] || $panel['basi'] || ! $pembacaan)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4">
            <x-heroicon-s-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
            <div class="text-sm text-on-surface">
                @if(! $pembacaan)
                    <p class="font-bold">Belum ada pembacaan tersimpan.</p>
                    <p class="mt-0.5 text-on-surface-variant">Perangkat sudah terdaftar, tetapi datanya belum pernah berhasil diambil.</p>
                @elseif(! $panel['terhubung'])
                    <p class="font-bold">Alat sedang tidak terhubung.</p>
                    <p class="mt-0.5 text-on-surface-variant">
                        Angka di bawah adalah pembacaan terakhir yang tersimpan
                        (<span data-ews-waktu>{{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}</span>),
                        bukan kondisi saat ini.
                    </p>
                @else
                    <p class="font-bold">Data belum diperbarui.</p>
                    <p class="mt-0.5 text-on-surface-variant">
                        Pembacaan terakhir <span data-ews-waktu>{{ $pembacaan->direkam_pada->locale('id')->diffForHumans() }}</span>.
                    </p>
                @endif
            </div>
        </div>
    @endif

    {{-- Status sungai: satu-satunya angka yang benar-benar menuntut tindakan,
         jadi diberi ruang tersendiri di atas kartu-kartu lain. --}}
    <div class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Status Sungai</p>
                <p class="mt-1 text-4xl font-black tracking-tight {{ $status->kelasWarna() }}" data-ews-status>
                    {{ $status->getLabel() }}
                </p>
                <p class="mt-2 text-sm text-on-surface-variant">
                    Titik pantau {{ $device->namaTampil() }}
                </p>
            </div>

            <div class="flex items-center gap-2 rounded-full border border-outline-variant px-4 py-2 text-xs font-semibold">
                <span class="h-2 w-2 rounded-full {{ $panel['terhubung'] ? 'bg-emerald-500' : 'bg-slate-400' }}" data-ews-lampu></span>
                <span data-ews-sambungan>{{ $panel['terhubung'] ? 'Alat terhubung' : 'Alat tidak terhubung' }}</span>
            </div>
        </div>
    </div>

    {{-- Empat besaran sensor. --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Tinggi Air</p>
            <p class="mt-3 text-3xl font-black text-primary" data-ews-tinggi>{{ $angka($pembacaan?->tinggi_air) }}</p>
            <p class="mt-1 text-xs font-semibold text-on-surface-muted">cm</p>
        </div>

        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Curah Hujan</p>
            <p class="mt-3 text-3xl font-black text-primary" data-ews-hujan>{{ $angka($pembacaan?->curah_hujan) }}</p>
            <p class="mt-1 text-xs font-semibold text-on-surface-muted">mm/jam</p>
        </div>

        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">pH Air</p>
            <p class="mt-3 text-3xl font-black text-primary" data-ews-ph>{{ $angka($pembacaan?->ph_air, 1) }}</p>
            {{-- pH di luar 0..14 tidak mungkin secara fisik. Angkanya tetap ditampilkan
                 apa adanya, tetapi tidak boleh dibaca sebagai fakta lingkungan. --}}
            <p class="mt-1 text-xs font-semibold {{ $pembacaan?->phMencurigakan() ? 'text-amber-600 dark:text-amber-400' : 'text-on-surface-muted' }}"
               data-ews-ph-catatan>
                {{ $pembacaan?->phMencurigakan() ? 'sensor perlu kalibrasi' : 'pH' }}
            </p>
        </div>

        <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Getaran</p>
            <p class="mt-3 text-3xl font-black text-primary" data-ews-getaran>{{ $angka($pembacaan?->getaran) }}</p>
            <p class="mt-1 text-xs font-semibold text-on-surface-muted">skala</p>
        </div>
    </div>

    {{-- Tren. Inilah yang tidak bisa diberikan angka sesaat: melihat tinggi air
         merangkak naik sebelum banjir. --}}
    @if(count($panel['tren']['labels']) > 1)
        <div class="rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h3 class="text-sm font-bold text-on-surface">Tren {{ \App\Services\Ews\EwsPanelService::RENTANG_TREN_JAM }} Jam Terakhir</h3>
                <p class="text-xs text-on-surface-variant">{{ count($panel['tren']['labels']) }} pembacaan</p>
            </div>

            <div class="mt-4 space-y-5">
                <x-public.ews-sparkline
                    label="Tinggi air (cm)"
                    :labels="$panel['tren']['labels']"
                    :nilai="$panel['tren']['tinggi_air']"
                    warna="#0284c7" />

                <x-public.ews-sparkline
                    label="Curah hujan (mm/jam)"
                    :labels="$panel['tren']['labels']"
                    :nilai="$panel['tren']['curah_hujan']"
                    warna="#10b981" />
            </div>
        </div>
    @else
        <p class="rounded-2xl border border-dashed border-outline-variant p-6 text-center text-sm text-on-surface-variant">
            Grafik tren muncul setelah ada beberapa pembacaan berurutan.
        </p>
    @endif

    <p class="text-center text-xs text-on-surface-muted">
        Sumber: perangkat sensor nagari. Pembacaan terakhir
        <span data-ews-waktu>{{ $pembacaan?->direkam_pada?->locale('id')?->diffForHumans() ?? 'belum ada' }}</span>.
    </p>
</div>
