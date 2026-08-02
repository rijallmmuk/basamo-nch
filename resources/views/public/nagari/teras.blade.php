@extends('public.layouts.app')

@section('title', 'Teras Nagari')
@section('meta_description', 'Data terbuka '.$nagari->nama_lengkap.': capaian 18 poin SDGs Desa, Indeks Desa Membangun, prakiraan cuaca BMKG, dan demografi kependudukan.')
@section('main-class', 'w-full')

@section('content')
@php
    use App\Enums\DimensiIdm;
    use App\Enums\StatusIdm;

    $isFallback = request()->routeIs('*.fallback');
    $globalTeras = $globalTeras ?? false;
    $statusIdm = $idm
        ? ($idm->status instanceof StatusIdm ? $idm->status : StatusIdm::tryFrom((string) $idm->status))
        : null;

    /* Nilai IDM disimpan sebagai pecahan 0..1 oleh Kemendesa; ditampilkan empat
       desimal seperti pada dokumen resminya. */
    $angkaIdm = fn ($nilai) => $nilai === null ? '—' : number_format((float) $nilai, 4, ',', '.');

    $dimensiIdm = $idm ? [
        ['enum' => DimensiIdm::IKS, 'skor' => (float) $idm->skor_iks, 'bar' => 'bg-sky-500'],
        ['enum' => DimensiIdm::IKE, 'skor' => (float) $idm->skor_ike, 'bar' => 'bg-amber-500'],
        ['enum' => DimensiIdm::IKL, 'skor' => (float) $idm->skor_ikl, 'bar' => 'bg-emerald-500'],
    ] : [];

    $poinTerisi = $sdgPilar->flatMap(fn (array $p) => $p['poin'])->filter(fn (array $p): bool => $p['terisi']);

    /* Tautan buka/tutup panduan sebuah poin. Jangkarnya menunjuk kartu poin itu
       sendiri, bukan kepala section, supaya pembaca kembali tepat di tempat ia
       menekan alih-alih terlempar ke atas daftar. */
    $tautanPoin = function (?int $nomor) use ($nagari, $isFallback, $globalTeras): string {
        if ($globalTeras) {
            return route('public.teras', array_filter([
                'nagari' => $nagari->id,
                'poin' => $nomor,
            ])).($nomor === null ? '#sdgs' : '#poin-'.$nomor);
        }

        $dasar = route($isFallback ? 'public.nagari.teras.fallback' : 'public.nagari.teras', $nagari);

        return $nomor === null
            ? $dasar.'#sdgs'
            : $dasar.'?poin='.$nomor.'#poin-'.$nomor;
    };
@endphp

<x-public.pillar-header
    eyebrow="Pilar 1 · Teras Nagari"
    :title="'Data terbuka '.$nagari->nama_lengkap.'.'"
    description="Capaian SDGs Desa, Indeks Desa Membangun, prakiraan cuaca BMKG, dan demografi kependudukan disajikan sebagai data agregat tanpa identitas pribadi warga.">
    <x-slot:aside>
        <div class="inline-flex w-fit items-center gap-2 rounded-full border border-on-primary/20 bg-on-primary/10 px-4 py-2 text-xs font-bold">
            <x-heroicon-s-shield-check class="h-4 w-4" /> Data agregat non-pribadi
        </div>
    </x-slot:aside>
</x-public.pillar-header>

@if($globalTeras)
    <section class="border-b border-outline-variant bg-surface-container-lowest py-6">
        <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
            <form method="GET" action="{{ route('public.teras') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="min-w-0 flex-1">
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Tampilkan data nagari</span>
                    <select name="nagari" class="select-nch w-full rounded-full">
                        <option value="">Semua nagari</option>
                        @foreach($nagariOptions as $option)
                            <option value="{{ $option->id }}" @selected($option->id === $nagari->id)>
                                {{ $option->nama }}{{ $option->kabupaten ? ' · '.$option->kabupaten : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <button class="mt-0 rounded-full bg-primary px-6 py-3 text-sm font-extrabold text-on-primary sm:mt-5">Terapkan</button>
            </form>
        </div>
    </section>
@endif

{{-- ══ RINGKASAN ANGKA + DEMOGRAFI LENGKAP ══ --}}
<x-public.teras-overview :overview="$overview" :nagari="$nagari" :global="$globalTeras" />

@if($ews)
    {{-- EWS adalah bagian pusat data dan komando. Hanya muncul pada nagari yang
         memiliki perangkat aktif; tidak ada placeholder maupun angka rekaan. --}}
    <section id="ews" class="border-t border-outline-variant bg-background py-section-gap">
        <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
            <x-public.section-heading
                eyebrow="Sistem Peringatan Dini"
                title="Pemantauan EWS nagari."
                description="Status sungai, pembacaan sensor, dan tren 24 jam dari titik pantau yang terdaftar. Perhatikan status sambungan serta waktu pembacaan sebelum menggunakan data."
            />
            <div class="mt-10">
                <x-public.ews-panel :panel="$ews" />
            </div>
        </div>
    </section>
@endif

{{-- ══ SDGs DESA — 18 POIN ══════════════════════════════════════════════
     Disusun per poin lengkap dengan pilarnya, sepadan dengan resource Capaian
     SDGs di panel. --}}
<section id="sdgs" class="border-t border-outline-variant bg-surface-container-lowest py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <x-public.section-heading
                eyebrow="SDGs Desa"
                title="Capaian 18 poin pembangunan nagari."
                description="Skor ditarik otomatis dari sistem Kemendesa dan diperbarui berkala."
            />
            <div class="shrink-0 rounded-2xl border border-outline-variant bg-background px-6 py-4 text-center shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Skor Keseluruhan</p>
                <p class="text-3xl font-black tabular-nums text-primary">
                    {{ $poinTerisi->isNotEmpty() ? number_format((float) $sdgSkor, 1, ',', '.').'%' : '—' }}
                </p>
            </div>
        </div>

        @if($poinTerisi->isEmpty())
            {{-- Nilai SDGs TIDAK PERNAH dikarang. Halaman ini wajah resmi nagari;
                 angka rekaan di sini terbaca sebagai capaian sungguhan. --}}
            <x-public.empty-state
                class="mt-10"
                icon="heroicon-o-chart-pie"
                title="Capaian SDGs belum tersedia"
                description="Data ditarik dari sistem Kemendesa. Angka tampil di sini setelah penarikan pertama berhasil." />
        @else
            {{-- Dikelompokkan per pilar, sepadan dengan resource Capaian SDGs di
                 panel. Delapan belas kartu berjajar rata tidak menunjukkan bahwa
                 poin-poin itu sebenarnya bernaung di bawah empat tema besar, dan
                 pengelompokan inilah yang membuat skor tiap tema bisa dibaca. --}}
            <div class="mt-10 space-y-8">
                @foreach($sdgPilar as $kelompok)
                    @php
                        $pilar = $kelompok['pilar'];
                        $warnaPilar = $pilar?->warna ?: '#003857';
                    @endphp
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant pb-3">
                            <div class="flex items-center gap-3">
                                <span class="h-8 w-1.5 shrink-0 rounded-full" style="background-color: {{ $warnaPilar }}" aria-hidden="true"></span>
                                <div>
                                    <h3 class="text-lg font-extrabold tracking-tight text-primary">{{ $pilar?->nama ?? 'Tanpa pilar' }}</h3>
                                    <p class="text-xs text-on-surface-variant">{{ $kelompok['poin']->count() }} poin pembangunan</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Rata-rata pilar</p>
                                <p class="text-2xl font-black tabular-nums text-primary">
                                    {{ number_format($kelompok['skor'], 1, ',', '.') }}%
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach($kelompok['poin'] as $nomor => $poin)
                                @php
                                    $goal = $poin['goal'];
                                    $nilai = (float) $poin['nilai'];
                                    $warna = $goal->warna ?: $warnaPilar;
                                @endphp

                                {{-- Tautan biasa, bukan <details> maupun komponen ber-JavaScript.
                                     Panduannya besar (214 sasaran, 386 indikator seluruhnya),
                                     jadi hanya poin yang sedang dibuka yang benar-benar dimuat.
                                     Akibatnya alamatnya bisa dibagikan, isinya terbaca mesin
                                     pencari, dan halaman tetap jalan tanpa JavaScript. --}}
                                <div class="overflow-hidden rounded-2xl border bg-background shadow-sm transition-all {{ $poin['terbuka'] ? 'border-primary/50 shadow-md' : 'border-outline-variant hover:border-primary/40' }}">
                                    <a href="{{ $poin['terbuka'] ? $tautanPoin(null) : $tautanPoin($nomor) }}"
                                       id="poin-{{ $nomor }}"
                                       class="flex items-center gap-4 p-5"
                                       aria-expanded="{{ $poin['terbuka'] ? 'true' : 'false' }}">
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-sm font-black text-white"
                                              style="background-color: {{ $warna }}">
                                            {{ $nomor }}
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-extrabold text-on-surface">{{ $goal->nama }}</span>
                                            <span class="mt-2 flex items-center gap-3">
                                                <span class="h-2 flex-1 overflow-hidden rounded-full bg-surface-container-high">
                                                    <span class="block h-full rounded-full" style="width: {{ max(0, min(100, $nilai)) }}%; background-color: {{ $warna }}"></span>
                                                </span>
                                                <span class="shrink-0 text-sm font-black tabular-nums text-primary">
                                                    {{ $poin['terisi'] ? number_format($nilai, 1, ',', '.').'%' : 'Belum ada' }}
                                                </span>
                                            </span>
                                        </span>

                                        <span class="hidden shrink-0 text-right text-[11px] leading-tight text-on-surface-variant sm:block">
                                            {{ $poin['jumlah_sasaran'] }} sasaran<br>{{ $poin['jumlah_indikator'] }} indikator
                                        </span>
                                        <x-heroicon-o-chevron-down class="h-5 w-5 shrink-0 text-on-surface-variant transition-transform {{ $poin['terbuka'] ? 'rotate-180' : '' }}" />
                                    </a>

                                    @if($poin['terbuka'])
                                        <div class="border-t border-outline-variant px-5 py-5">
                                            @if($goal->targets->isEmpty())
                                                <p class="text-sm text-on-surface-variant">Sasaran untuk poin ini belum terdata.</p>
                                            @else
                                                <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                                                    Sasaran dan indikator pengukurannya
                                                </p>
                                                <div class="mt-4 space-y-4">
                                                    @foreach($goal->targets as $sasaran)
                                                        <div class="rounded-xl bg-surface-container-lowest p-4">
                                                            <p class="text-sm font-bold text-on-surface">
                                                                <span class="tabular-nums text-primary">{{ $sasaran->kode }}</span>
                                                                {{ $sasaran->deskripsi }}
                                                            </p>

                                                            @if($sasaran->indicators->isNotEmpty())
                                                                <ul class="mt-3 space-y-2 border-l-2 border-outline-variant pl-4">
                                                                    @foreach($sasaran->indicators as $indikator)
                                                                        <li class="text-xs leading-relaxed text-on-surface-variant">
                                                                            <span class="font-bold tabular-nums text-on-surface">{{ $indikator->kode }}</span>
                                                                            {{ $indikator->deskripsi }}
                                                                            @if($indikator->target_nilai !== null)
                                                                                <span class="font-semibold text-primary">
                                                                                    (target {{ rtrim(rtrim(number_format((float) $indikator->target_nilai, 2, ',', '.'), '0'), ',') }}{{ $indikator->satuan_acuan ? ' '.$indikator->satuan_acuan : '' }})
                                                                                </span>
                                                                            @endif
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ══ INDEKS DESA MEMBANGUN ══ --}}
<section id="idm" class="border-t border-outline-variant bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            eyebrow="Indeks Desa Membangun"
            title="Status nagari menurut Kemendesa."
            description="Gabungan tiga dimensi: ketahanan sosial, ekonomi, dan lingkungan."
        />

        @if(! $idm)
            <x-public.empty-state
                class="mt-10"
                icon="heroicon-o-trophy"
                title="Status IDM belum tersedia"
                description="Data ditarik dari idm.kemendesa.go.id. Status tampil setelah penarikan pertama berhasil." />
        @else
            <div class="mt-10 grid gap-5 lg:grid-cols-12">
                <div class="overflow-hidden rounded-2xl bg-primary p-7 text-on-primary shadow-sm lg:col-span-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-secondary-container">IDM {{ $idm->tahun }}</p>
                    <p class="mt-3 text-4xl font-black tracking-tight">{{ $statusIdm?->label() ?? $idm->status }}</p>
                    <p class="mt-1 text-on-primary/70">Skor {{ $angkaIdm($idm->skor) }}</p>

                    @if($idm->target_status)
                        <div class="mt-6 space-y-1 border-t border-on-primary/15 pt-5 text-sm">
                            <p class="text-on-primary/70">
                                Target berikutnya
                                <span class="font-extrabold text-on-primary">{{ ($idm->target_status instanceof StatusIdm ? $idm->target_status : StatusIdm::tryFrom((string) $idm->target_status))?->label() ?? $idm->target_status }}</span>
                            </p>
                            {{-- Skor minimal = ambang batas status target. Tanpa angka ini,
                                 "perlu tambahan sekian" menggantung tanpa titik tuju. --}}
                            @if($idm->skor_minimal !== null)
                                <p class="text-on-primary/70">
                                    Ambang skornya <span class="font-extrabold text-on-primary">{{ $angkaIdm($idm->skor_minimal) }}</span>
                                </p>
                            @endif
                            @if($idm->penambahan !== null)
                                <p class="text-on-primary/70">
                                    Perlu tambahan skor <span class="font-extrabold text-on-primary">{{ $angkaIdm($idm->penambahan) }}</span>
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="space-y-4 rounded-2xl border border-outline-variant bg-surface-container-lowest p-7 shadow-sm lg:col-span-7">
                    <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Tiga Dimensi Penyusun</p>
                    @foreach($dimensiIdm as $dimensi)
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-extrabold text-on-surface">{{ $dimensi['enum']->label() }}</span>
                                <span class="text-sm font-black tabular-nums text-primary">{{ $angkaIdm($dimensi['skor']) }}</span>
                            </div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-surface-container-high">
                                {{-- Skor 0..1 dijadikan persen lebar batang. --}}
                                <div class="h-full rounded-full {{ $dimensi['bar'] }}" style="width: {{ max(0, min(100, $dimensi['skor'] * 100)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($idm->indicators->isNotEmpty())
                <div class="mt-5 overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                    <div class="border-b border-outline-variant px-6 py-4">
                        <p class="font-extrabold text-primary">Indikator yang perlu diperbaiki</p>
                        <p class="mt-0.5 text-sm text-on-surface-variant">Rekomendasi kegiatan dari Kemendesa untuk menaikkan status nagari.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[64rem] text-left text-sm">
                            <thead class="bg-surface-container-low text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                                <tr>
                                    <th class="px-5 py-3">Dimensi</th>
                                    <th class="px-5 py-3">Indikator</th>
                                    <th class="px-5 py-3">Keterangan</th>
                                    <th class="px-5 py-3">Kegiatan Disarankan</th>
                                    <th class="px-5 py-3">Pelaksana</th>
                                    <th class="px-5 py-3 text-right">Skor</th>
                                    <th class="px-5 py-3 text-right">Tambahan Indeks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                                @foreach($idm->indicators as $indikator)
                                    <tr class="align-top">
                                        {{-- `dimensi` sudah di-cast jadi enum oleh model,
                                             jadi jangan dipaksa jadi string dulu. --}}
                                        <td class="px-5 py-3 font-bold text-primary">
                                            {{ $indikator->dimensi instanceof DimensiIdm ? $indikator->dimensi->label() : $indikator->dimensi }}
                                        </td>
                                        <td class="px-5 py-3 font-semibold text-on-surface">{{ $indikator->indikator }}</td>
                                        <td class="px-5 py-3 text-on-surface-variant">{{ $indikator->keterangan ?: '—' }}</td>
                                        <td class="px-5 py-3 text-on-surface-variant">{{ $indikator->kegiatan ?: '—' }}</td>
                                        <td class="px-5 py-3 text-on-surface-variant">
                                            {{-- Pelaksana tersimpan sebagai {tingkat: instansi},
                                                 misal {"KAB": "Dinkes, PU"}. --}}
                                            @if(filled($indikator->pelaksana))
                                                <ul class="space-y-0.5">
                                                    @foreach($indikator->pelaksana as $tingkat => $instansi)
                                                        <li>
                                                            <span class="font-bold text-on-surface">{{ $tingkat }}</span>
                                                            {{ $instansi }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-right font-black tabular-nums text-primary">{{ $angkaIdm($indikator->skor) }}</td>
                                        <td class="px-5 py-3 text-right tabular-nums text-on-surface-variant">{{ $angkaIdm($indikator->nilai) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>
</section>

{{-- ══ PRAKIRAAN CUACA BMKG ══ --}}
<section id="cuaca" class="border-t border-outline-variant bg-surface-container-lowest py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            eyebrow="Prakiraan Cuaca"
            title="Cuaca nagari dari BMKG."
            description="Diperbarui berkala mengikuti sumber resmi BMKG."
        />

        @if(! $cuaca)
            <x-public.empty-state
                class="mt-10"
                icon="heroicon-o-sun"
                title="Prakiraan cuaca belum tersedia"
                description="Layanan BMKG sedang tidak dapat dihubungi, atau kode wilayah nagari belum terdaftar di sana." />
        @else
            @if($cuaca['saat_ini'])
                <x-public.cuaca-saat-ini class="mt-10" :cuaca="$cuaca['saat_ini']" :lokasi="$cuaca['lokasi']" />
            @endif

            <div class="mt-5 space-y-4">
                @foreach($cuaca['hari'] as $hari)
                    <div class="overflow-hidden rounded-2xl border border-outline-variant bg-background shadow-sm">
                        <div class="border-b border-outline-variant bg-surface-container-low px-5 py-3">
                            <p class="text-sm font-extrabold text-primary">{{ $hari['label'] ?? ($hari['tanggal'] ?? '') }}</p>
                        </div>
                        {{-- Kelembapan dan angin ikut ditampilkan per jam, sepadan
                             dengan halaman Cuaca di panel. Datanya sejak awal sudah
                             ikut terambil dari BMKG, hanya tidak pernah dipakai. --}}
                        <div class="flex gap-3 overflow-x-auto p-4">
                            @foreach($hari['slots'] as $slot)
                                <div class="w-32 shrink-0 rounded-xl border border-outline-variant bg-surface-container-lowest p-3 text-center">
                                    <p class="text-xs font-bold text-on-surface-variant">{{ $slot['jam'] }}</p>
                                    @if($slot['ikon'])
                                        <img src="{{ $slot['ikon'] }}" alt="" class="mx-auto mt-1 h-10 w-10" loading="lazy">
                                    @endif
                                    <p class="mt-1 text-lg font-black text-primary">{{ $slot['suhu'] }}°</p>
                                    <p class="mt-0.5 line-clamp-2 min-h-[2rem] text-[11px] leading-tight text-on-surface-variant">{{ $slot['kondisi'] }}</p>

                                    <dl class="mt-2 space-y-1 border-t border-outline-variant pt-2 text-[11px] leading-tight">
                                        <div class="flex items-center justify-between gap-1">
                                            <dt class="text-on-surface-variant">Lembap</dt>
                                            <dd class="font-bold tabular-nums text-on-surface">{{ $slot['kelembapan'] !== null ? $slot['kelembapan'].'%' : '—' }}</dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-1">
                                            <dt class="text-on-surface-variant">Angin</dt>
                                            <dd class="font-bold tabular-nums text-on-surface">
                                                {{ $slot['kecepatan_angin'] !== null ? number_format((float) $slot['kecepatan_angin'], 0, ',', '.').' km/j' : '—' }}
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-1">
                                            <dt class="text-on-surface-variant">Dari</dt>
                                            <dd class="truncate font-bold text-on-surface">{{ $slot['arah_angin_dari'] ?? '—' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
