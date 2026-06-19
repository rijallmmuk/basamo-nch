@extends('portal.layouts.app')

@section('title', 'Peringkat XP')

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Peringkat XP'],
    ]" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Peringkat XP</h1>
        <p class="mt-1 text-sm text-gray-500">Papan peringkat warga {{ auth()->user()->nagari?->nama ?? 'nagari' }} berdasarkan XP.</p>
    </div>

    {{-- Posisimu --}}
    <div class="mb-5 flex items-center justify-between gap-4 rounded-2xl bg-gradient-to-br from-indigo-700 to-violet-700 p-5 text-white shadow-sm">
        <div class="flex items-center gap-3">
            <x-portal.avatar :name="auth()->user()->name" variant="soft" size="lg" class="!bg-white/20 !text-white" />
            <div>
                <p class="text-sm text-indigo-100">Posisimu</p>
                <p class="text-lg font-bold">Peringkat #{{ $myRank }} <span class="text-sm font-normal text-indigo-200">dari {{ $totalWarga }} warga</span></p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-2xl font-bold">{{ number_format(auth()->user()->total_xp) }}</p>
            <p class="text-xs text-indigo-200">XP</p>
        </div>
    </div>

    {{-- Daftar peringkat --}}
    @if($warga->isEmpty())
        <x-portal.empty icon="heroicon-o-trophy" title="Belum ada peringkat" subtitle="Peringkat muncul setelah warga mengumpulkan XP." />
    @else
        <x-portal.card :padded="false">
            <div class="divide-y divide-gray-100">
                @foreach($warga as $w)
                    @php
                        $rank = $warga->firstItem() + $loop->index;
                        $isMe = $w->id === auth()->id();
                        $medal = match ($rank) { 1 => 'bg-amber-400 text-white', 2 => 'bg-gray-300 text-white', 3 => 'bg-orange-300 text-white', default => 'bg-gray-100 text-gray-500' };
                    @endphp
                    <div class="flex items-center gap-3 px-4 py-3 sm:px-5 {{ $isMe ? 'bg-indigo-50/60' : '' }}">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $medal }}">{{ $rank }}</span>
                        <x-portal.avatar :name="$w->name" :variant="$isMe ? 'solid' : 'gray'" size="md" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold {{ $isMe ? 'text-indigo-700' : 'text-gray-800' }}">
                                {{ $w->name }}@if($isMe) <span class="text-xs font-normal text-indigo-500">(kamu)</span>@endif
                            </p>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-indigo-600">{{ number_format($w->total_xp) }} <span class="text-xs font-normal text-gray-400">XP</span></span>
                    </div>
                @endforeach
            </div>
        </x-portal.card>

        <div class="mt-5">
            {{ $warga->links() }}
        </div>
    @endif
@endsection
