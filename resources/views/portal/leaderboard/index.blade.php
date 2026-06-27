@extends('portal.layouts.app')

@section('title', 'Peringkat')

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Peringkat'],
    ]" />

    <div class="mb-6">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">Peringkat Warga</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Papan peringkat warga {{ auth()->user()->desa?->nama_lengkap ?? 'desa' }} berdasarkan poin (XP).</p>
    </div>

    @if($warga->isEmpty())
        <x-portal.empty icon="heroicon-o-trophy" title="Belum ada peringkat" subtitle="Peringkat muncul setelah warga mengumpulkan XP." />
    @else
            {{-- ── PODIUM TOP 3 (halaman pertama) ─────────────────────────── --}}
            @if($warga->onFirstPage() && $podium->isNotEmpty())
                @php
                    // Gaya per-peringkat: 1=emas (sdg-2), 2=perak (outline), 3=perunggu (sdg-12).
                    $podiumStyle = [
                        1 => ['ped' => 'h-28 bg-sdg-2/15 border-sdg-2/40', 'num' => 'text-sdg-2', 'ring' => 'ring-sdg-2', 'av' => '!h-20 !w-20 !text-2xl', 'badge' => 'bg-sdg-2 text-white'],
                        2 => ['ped' => 'h-20 bg-on-surface/10 border-outline/40', 'num' => 'text-on-surface-variant', 'ring' => 'ring-outline', 'av' => '!h-16 !w-16 !text-xl', 'badge' => 'bg-surface-dim text-on-surface'],
                        3 => ['ped' => 'h-16 bg-sdg-12/15 border-sdg-12/40', 'num' => 'text-sdg-12', 'ring' => 'ring-sdg-12', 'av' => '!h-16 !w-16 !text-xl', 'badge' => 'bg-sdg-12 text-white'],
                    ];
                    // Urutan tampil kiri→kanan: juara 2, juara 1 (tengah, tertinggi), juara 3.
                    $display = [[2, $podium->get(1)], [1, $podium->get(0)], [3, $podium->get(2)]];
                @endphp
                <div class="mx-auto mb-8 max-w-3xl border-b-2 border-outline/30">
                    <div class="grid grid-cols-3 items-end gap-3 sm:gap-5">
                    @foreach($display as [$rank, $person])
                        @php $s = $podiumStyle[$rank]; $isMe = $person && $person->id === auth()->id(); @endphp
                        <div class="flex flex-col items-center justify-end">
                            @if($person)
                                @if($rank === 1)
                                    <x-heroicon-s-trophy class="mb-1.5 h-7 w-7 text-sdg-2" />
                                @endif
                                <div class="relative">
                                    <x-portal.avatar :name="$person->name" :src="$person->avatarUrl()" variant="solid" class="{{ $s['av'] }} ring-4 {{ $s['ring'] }}" />
                                    <span class="absolute -bottom-2 left-1/2 flex h-7 w-7 -translate-x-1/2 items-center justify-center rounded-full text-xs font-bold ring-2 ring-surface {{ $s['badge'] }}">{{ $rank }}</span>
                                </div>
                                <p class="mt-4 line-clamp-1 max-w-full px-1 text-center text-sm font-bold {{ $isMe ? 'text-primary' : 'text-on-surface' }}">
                                    {{ $person->name }}@if($isMe) <span class="text-xs font-normal">(Anda)</span>@endif
                                </p>
                                <p class="text-xs font-bold text-primary">{{ number_format($person->total_xp) }} XP</p>
                            @endif
                            {{-- Tumpuan podium --}}
                            <div class="mt-3 flex w-full items-center justify-center rounded-t-2xl border border-b-0 {{ $s['ped'] }}">
                                <span class="text-2xl font-extrabold {{ $s['num'] }}">{{ $rank }}</span>
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
            @endif

            {{-- Posisi Anda --}}
            <div class="relative mb-5 flex items-center justify-between gap-4 overflow-hidden rounded-2xl bg-primary p-5 text-on-primary shadow-sm">
                <div class="relative z-10 flex items-center gap-3">
                    <x-portal.avatar :name="auth()->user()->name" :src="auth()->user()->avatarUrl()" variant="soft" size="lg" class="!bg-white/20 !text-white" />
                    <div>
                        <p class="text-sm text-white/70">Posisi Anda</p>
                        <p class="text-lg font-bold">Peringkat #{{ $myRank }} <span class="text-sm font-normal text-white/70">dari {{ $totalWarga }} warga</span></p>
                    </div>
                </div>
                <div class="relative z-10 text-right">
                    <p class="text-2xl font-bold">{{ number_format(auth()->user()->total_xp) }}</p>
                    <p class="text-xs text-white/70">XP</p>
                </div>
                <div class="pointer-events-none absolute -right-12 -top-12 z-0 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
            </div>

            {{-- Daftar peringkat (peringkat 4+ di halaman pertama; semua di halaman lain) --}}
            @if(! $warga->onFirstPage() || $warga->total() > 3)
                <x-portal.card :padded="false">
                    <div class="divide-y divide-outline-variant">
                        @foreach($warga as $w)
                            @php
                                $rank = $ranks[$w->id] ?? ($warga->firstItem() + $loop->index);
                                $isMe = $w->id === auth()->id();
                            @endphp
                            @if($warga->onFirstPage() && $rank <= 3) @continue @endif
                            <div class="flex items-center gap-3 px-4 py-3 transition-colors sm:px-5 {{ $isMe ? 'bg-primary-fixed' : 'hover:bg-surface-container-lowest' }}">
                                <span class="w-8 shrink-0 text-center text-sm font-bold {{ $isMe ? 'text-primary' : 'text-on-surface-variant' }}">{{ $rank }}</span>
                                <x-portal.avatar :name="$w->name" :src="$w->avatarUrl()" :variant="$isMe ? 'solid' : 'gray'" size="md" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold {{ $isMe ? 'text-primary' : 'text-on-surface' }}">
                                        {{ $w->name }}@if($isMe) <span class="text-xs font-normal">(Anda)</span>@endif
                                    </p>
                                </div>
                                <span class="shrink-0 text-sm font-bold {{ $isMe ? 'text-primary' : 'text-on-surface' }}">{{ number_format($w->total_xp) }} <span class="text-xs font-normal text-on-surface-variant">XP</span></span>
                            </div>
                        @endforeach
                    </div>
                </x-portal.card>
            @endif

            <div class="mt-5">
                {{ $warga->links() }}
            </div>
    @endif
@endsection
