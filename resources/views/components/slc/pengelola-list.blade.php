@props([
    'participants',
    'max' => null,
    'compact' => false,
])

{{-- Daftar "Pengajar atau Pengelola" TUNGGAL untuk kartu pelatihan, detail pelatihan,
     dan daftar di beranda portal. Satu tempat supaya nama dan lembaga selalu ditulis
     dengan cara yang sama.

     PERAN SENGAJA TIDAK DITULIS. Warga tidak perlu tahu siapa "Pengajar" dan siapa
     "Operator Nagari"; yang berguna baginya hanyalah nama orangnya dan lembaga yang
     menaunginya. Admin dan operator tetap tampil sebagai jabatan karena nama pribadi
     mereka memang bukan identitas yang relevan di layar warga.

     RESPONSIF SEBANYAK APA PUN ISINYA: nama memakai `min-w-0` + `truncate` agar tidak
     mendorong lebar induknya, chip lembaga boleh turun baris, dan `$max` memotong
     daftar panjang menjadi "+N lainnya". --}}
@php
    $orang = collect($participants);
    $tampil = $max ? $orang->take($max) : $orang;
    $sisa = $orang->count() - $tampil->count();

    $namaTampil = function ($person): string {
        if ($person->isSuperAdmin()) {
            return 'Admin';
        }

        if ($person->isOperator()) {
            return trim('Operator Nagari '.($person->nagari?->nama ?? ''));
        }

        return $person->name;
    };
@endphp

@if($orang->isEmpty())
    <p class="text-[11px] italic text-on-surface-variant">Belum ada pengajar atau pengelola</p>
@else
    <ul class="{{ $compact ? 'space-y-1' : 'grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3' }}">
        @foreach($tampil as $person)
            @php $nama = $namaTampil($person); @endphp
            @if($compact)
                <li class="flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-1 text-xs">
                    <span class="flex min-w-0 items-center gap-1 font-bold text-on-surface" title="{{ $nama }}">
                        <x-heroicon-s-user-circle class="h-3.5 w-3.5 shrink-0 text-primary" />
                        <span class="truncate">{{ $nama }}</span>
                    </span>
                    @if($person->lembaga)
                        <span class="max-w-full truncate rounded bg-primary/10 px-1.5 py-0.5 text-[9px] font-extrabold text-primary" title="{{ $person->lembaga }}">
                            {{ $person->lembaga }}
                        </span>
                    @endif
                </li>
            @else
                <li class="flex min-w-0 items-center gap-2.5 rounded-xl border border-outline-variant bg-surface-container-low p-3">
                    <x-portal.avatar :name="$nama" :src="$person->avatarUrl()" size="sm" />
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-on-surface" title="{{ $nama }}">{{ $nama }}</p>
                        @if($person->lembaga)
                            <p class="truncate text-[10px] font-semibold text-on-surface-variant" title="{{ $person->lembaga }}">{{ $person->lembaga }}</p>
                        @endif
                    </div>
                </li>
            @endif
        @endforeach
    </ul>

    @if($sisa > 0)
        <p class="mt-1 text-right text-[10px] font-medium text-on-surface-variant">+{{ $sisa }} lainnya</p>
    @endif
@endif
