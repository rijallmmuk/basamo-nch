@extends('portal.layouts.app')

@section('title', 'Peringkat')

@section('content')
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Peringkat</h1>
        <p class="mt-1 text-sm text-gray-500">Papan peringkat warga se-nagari.</p>
    </div>

    {{-- Banner: data sementara --}}
    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-700">
        <x-heroicon-s-information-circle class="mt-0.5 h-4 w-4 shrink-0" />
        <span>Sistem poin masih difinalisasi. Data peringkat di bawah ini <strong>hanya contoh tampilan</strong>.</span>
    </div>

    @php
        $top = $entries->take(3);
        $rest = $entries->slice(3);
        $podiumOrder = [$top->get(1), $top->get(0), $top->get(2)]; // 2 - 1 - 3
        $podiumHeights = ['h-20', 'h-28', 'h-16'];
        $podiumColors = ['bg-gray-300', 'bg-amber-400', 'bg-orange-300'];
    @endphp

    {{-- Podium top 3 --}}
    <div class="mb-4 grid grid-cols-3 items-end gap-2 sm:gap-4">
        @foreach($podiumOrder as $i => $entry)
            @if($entry)
                <div class="flex flex-col items-center">
                    <x-portal.avatar :name="$entry['name']" size="lg" :variant="$entry['rank'] === 1 ? 'solid' : 'soft'" class="mb-2 !h-12 !w-12 !text-lg" />
                    <p class="max-w-full truncate text-center text-xs font-semibold text-gray-800">{{ $entry['name'] }}</p>
                    <p class="text-[11px] font-bold text-indigo-600">{{ number_format($entry['points']) }} poin</p>
                    <div class="mt-2 flex w-full {{ $podiumHeights[$i] }} items-start justify-center rounded-t-xl {{ $podiumColors[$i] }} pt-2">
                        <span class="text-lg font-extrabold text-white">{{ $entry['rank'] }}</span>
                    </div>
                </div>
            @else
                <div></div>
            @endif
        @endforeach
    </div>

    {{-- Daftar peringkat selanjutnya --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="divide-y divide-gray-100">
            @foreach($rest as $entry)
                <div class="flex items-center gap-3 px-4 py-3 {{ ($entry['is_me'] ?? false) ? 'bg-indigo-50/60' : '' }}">
                    <span class="w-6 shrink-0 text-center text-sm font-bold text-gray-400">{{ $entry['rank'] }}</span>
                    <x-portal.avatar :name="$entry['name']" size="md" :variant="($entry['is_me'] ?? false) ? 'solid' : 'gray'" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold {{ ($entry['is_me'] ?? false) ? 'text-indigo-700' : 'text-gray-800' }}">{{ $entry['name'] }}</p>
                        <p class="text-xs text-gray-400">{{ $entry['modules'] }} modul selesai</p>
                    </div>
                    <span class="shrink-0 text-sm font-bold text-indigo-600">{{ number_format($entry['points']) }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endsection
