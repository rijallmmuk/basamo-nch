@extends('portal.layouts.app')

@section('title', 'Daftar Modul')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Modul Pembelajaran</h1>
        <p class="text-sm text-gray-500 mt-1">Selamat datang, {{ auth()->user()->name }}</p>
    </div>

    @if($modules->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <div class="text-5xl mb-3">📚</div>
            <p class="font-medium">Belum ada modul tersedia</p>
            <p class="text-sm mt-1">Modul akan ditampilkan setelah admin mempublikasikannya</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($modules as $module)
                @php
                    $status = $statusMap[$module->id] ?? 'available';
                    $progress = $module->progress->first();
                    $pagesCompleted = $progress?->pages_completed ?? [];
                    $totalPages = $module->pages()->count();
                    $progressPct = $totalPages > 0 ? (int)(count($pagesCompleted) / $totalPages * 100) : 0;
                @endphp

                <div class="bg-white rounded-xl border {{ $status === 'locked' ? 'border-gray-200 opacity-70' : 'border-gray-200' }} overflow-hidden">
                    <div class="flex items-start gap-4 p-4">

                        {{-- Thumbnail / Status icon --}}
                        <div class="shrink-0 w-14 h-14 rounded-lg overflow-hidden bg-indigo-50 flex items-center justify-center">
                            @if($module->thumbnail)
                                <img src="{{ Storage::url($module->thumbnail) }}" alt="" class="w-full h-full object-cover">
                            @else
                                <span class="text-2xl">
                                    @if($status === 'locked') 🔒
                                    @elseif($status === 'completed') ✅
                                    @else 📖
                                    @endif
                                </span>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-gray-900 text-sm leading-snug">{{ $module->title }}</h3>
                                @if($status === 'completed')
                                    <span class="shrink-0 text-xs font-medium text-green-700 bg-green-50 px-2 py-0.5 rounded-full">Selesai</span>
                                @elseif($status === 'in_progress')
                                    <span class="shrink-0 text-xs font-medium text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-full">Berlangsung</span>
                                @elseif($status === 'locked')
                                    <span class="shrink-0 text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">🔒 Terkunci</span>
                                @endif
                            </div>

                            @if($module->description)
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ strip_tags($module->description) }}</p>
                            @endif

                            @if($status === 'locked' && $module->prerequisite)
                                <p class="text-xs text-amber-600 mt-1">Prasyarat: {{ $module->prerequisite->title }}</p>
                            @endif

                            {{-- Progress bar --}}
                            @if($status === 'in_progress' && $totalPages > 0)
                                <div class="mt-2">
                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                        <span>{{ count($pagesCompleted) }}/{{ $totalPages }} halaman</span>
                                        <span>{{ $progressPct }}%</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full transition-all"
                                            style="width: {{ $progressPct }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Action --}}
                    @if($status !== 'locked')
                        <div class="border-t border-gray-100 px-4 py-3">
                            <a href="{{ route('portal.modules.show', $module) }}"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                @if($status === 'completed') Lihat Modul →
                                @elseif($status === 'in_progress') Lanjutkan Belajar →
                                @else Mulai Belajar →
                                @endif
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
