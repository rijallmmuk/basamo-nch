@props(['program', 'href', 'cta' => 'Lihat Modul'])

{{-- Kartu pelatihan TUNGGAL untuk portal warga dan katalog publik. Jangan menyalin
     tampilannya ke halaman lain: perbedaan kecil antar salinan adalah cara paling
     cepat membuat UI tidak konsisten. Yang boleh berbeda hanya tujuan tautan dan
     tulisan tombolnya. --}}
@php
    $participants = $program->participants();
@endphp

<a href="{{ $href }}"
   class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition-all hover:border-primary/40 hover:shadow-md h-full cursor-pointer">
    
    <div>
        {{-- ── 1. PROGRAM COVER IMAGE (16:9 Aspect Ratio) ──────── --}}
        <div class="relative aspect-[16/9] w-full overflow-hidden bg-surface-container-high shrink-0">
            @if ($program->punyaCover())
<img src="{{ $program->coverUrl() }}" alt="Cover {{ $program->temaNama() }}" class="h-full w-full object-cover" loading="lazy">
@else
<x-slc.tema-cover :nama="$program->temaNama()" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
@endif
            
            {{-- Overlay Gradient --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>

            {{-- Status pelatihan. Sasaran nagari SENGAJA TIDAK ditampilkan: pembaca
                 kartu ini sudah berada di dalam konteks nagarinya sendiri, jadi label
                 itu hanya mengulang yang sudah ia tahu. Dulu bahkan tampil dua kali,
                 di sudut atas dan sudut bawah gambar yang sama. --}}
            <div class="absolute top-2.5 right-2.5">
                @if(! $program->dapatDimasuki())
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-amber-500/95 backdrop-blur-md px-2 py-0.5 text-[10px] font-bold text-white shadow-xs">
                        <x-heroicon-s-lock-closed class="h-3 w-3" />
                        <span>Terkunci</span>
                    </span>
                @else
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-600/95 backdrop-blur-md px-2 py-0.5 text-[10px] font-bold text-white shadow-xs">
                        <x-heroicon-s-check-circle class="h-3 w-3" />
                        <span>Terbuka</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- ── 2. CARD CONTENT AREA ── --}}
        <div class="p-4 space-y-2">
            {{-- Pelatihan Title --}}
            <h2 class="font-extrabold text-sm text-on-surface line-clamp-2 min-h-[2.5rem] leading-snug group-hover:text-primary transition-colors">
                {{ $program->temaNama() }}
            </h2>

            {{-- Deskripsi dipotong dua baris. Kartu berdiri berdampingan dalam grid,
                 jadi satu deskripsi panjang akan menarik tinggi seluruh barisnya. --}}
            <p class="line-clamp-2 min-h-10 text-xs leading-relaxed text-on-surface-variant">
                {{ filled($program->deskripsi) ? strip_tags($program->deskripsi) : '' }}
            </p>

            {{-- ── 3. PENGAJAR ATAU PENGELOLA ────────────────────────────────── --}}
            <div class="pt-1">
                <div class="min-h-[5.5rem] space-y-1.5 rounded-xl border border-outline-variant bg-surface-container-low p-2.5">
                    <div class="border-b border-outline-variant/60 pb-1 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">
                        Pengajar atau Pengelola
                    </div>

                    <x-slc.pengelola-list :participants="$participants" :max="3" compact />
                </div>
            </div>
        </div>
    </div>

    {{-- ── 4. CARD FOOTER ACTIONS ───────────────────────────── --}}
    <div class="p-4 pt-0">
        <div class="pt-2.5 border-t border-outline-variant flex items-center justify-between gap-2">
            <span class="flex items-center gap-1 text-[11px] font-bold text-on-surface-variant shrink-0">
                <x-heroicon-s-book-open class="h-3.5 w-3.5 text-primary" />
                <span>{{ $program->modules_count }} Modul</span>
            </span>

            <span class="inline-flex items-center justify-center gap-1 rounded-lg bg-primary/10 px-2.5 py-1.5 text-xs font-extrabold text-primary group-hover:bg-primary group-hover:text-on-primary transition-all shrink-0">
                <span>{{ $cta }}</span>
                <x-heroicon-s-arrow-right class="h-3.5 w-3.5" />
            </span>
        </div>
    </div>
</a>
