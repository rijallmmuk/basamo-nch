@extends('portal.layouts.app')

@section('title', $module->title)

@php
    $status = $isCompleted ? 'completed' : ($progress ? 'in_progress' : 'available');
    $done = count($pagesCompleted);
    $total = $pages->count();
    $pct = $total > 0 ? (int) ($done / $total * 100) : 0;
    $nextPage = $pages->first(fn ($p) => ! in_array($p->id, $pagesCompleted)) ?? $pages->first();
@endphp

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Modul', 'url' => route('portal.modules.index')],
        ['label' => $module->title],
    ]" />

    <div class="grid gap-5 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-4 lg:col-span-2">

            {{-- Header card --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="h-1.5 w-full {{ $isCompleted ? 'bg-emerald-500' : ($progress ? 'bg-indigo-500' : 'bg-indigo-200') }}"></div>

                <div class="p-5 sm:p-6">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <x-portal.status-badge :status="$status" />
                        @unless($module->nagari_id)
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500">Modul Global</span>
                        @endunless
                    </div>

                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ $module->title }}</h1>

                    @if($module->description)
                        <div class="prose prose-sm mt-2 max-w-none leading-relaxed text-gray-600">
                            {!! $module->description !!}
                        </div>
                    @endif

                    @if($progress && ! $isCompleted)
                        <div class="mt-5">
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="text-gray-500">{{ $done }} dari {{ $total }} materi selesai</span>
                                <span class="font-bold text-indigo-600">{{ $pct }}%</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quiz CTA --}}
            @if($module->quiz)
                @if($isCompleted)
                    <a href="{{ route('portal.modules.quiz', $module) }}"
                        class="flex items-center justify-between gap-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-5 transition-colors hover:bg-indigo-100">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white">
                                <x-heroicon-s-clipboard-document-check class="h-6 w-6" />
                            </span>
                            <div>
                                <p class="font-bold text-indigo-900">Kerjakan Kuis</p>
                                <p class="mt-0.5 text-sm text-indigo-600">{{ $module->quiz->title }}</p>
                            </div>
                        </div>
                        <x-heroicon-o-arrow-right class="h-5 w-5 shrink-0 text-indigo-500" />
                    </a>
                @else
                    <div class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 p-5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-200 text-gray-400">
                            <x-heroicon-s-lock-closed class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="font-bold text-gray-500">Kuis Terkunci</p>
                            <p class="mt-0.5 text-sm text-gray-400">Selesaikan semua materi untuk membuka kuis.</p>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Diskusi CTA --}}
            <a href="{{ route('portal.modules.discuss', $module) }}"
                class="flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-colors hover:bg-slate-50">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                        <x-heroicon-s-chat-bubble-left-right class="h-6 w-6" />
                    </span>
                    <div>
                        <p class="font-bold text-gray-900">Ruang Diskusi</p>
                        <p class="mt-0.5 text-sm text-gray-500">Tanya jawab seputar modul ini.</p>
                    </div>
                </div>
                <x-heroicon-o-arrow-right class="h-5 w-5 shrink-0 text-gray-400" />
            </a>
        </div>

        {{-- RIGHT: course outline --}}
        <div>
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm lg:sticky lg:top-20">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="font-bold text-gray-900">Daftar Materi</h2>
                    <p class="mt-0.5 text-xs text-gray-400">{{ $total }} materi · {{ $done }} selesai</p>
                </div>

                @if($pages->isEmpty())
                    <p class="px-5 py-12 text-center text-sm text-gray-400">Belum ada materi tersedia.</p>
                @else
                    <ol class="divide-y divide-gray-100">
                        @foreach($pages as $p)
                            @php $pDone = in_array($p->id, $pagesCompleted); @endphp
                            <li>
                                <a href="{{ route('portal.modules.pages.show', [$module, $p]) }}"
                                    class="group flex items-center gap-3 px-5 py-3.5 transition-colors hover:bg-slate-50">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                        {{ $pDone ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-400' }}">
                                        @if($pDone)
                                            <x-heroicon-s-check class="h-4 w-4" />
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium {{ $pDone ? 'text-gray-400' : 'text-gray-800' }}">
                                            {{ $p->title }}
                                        </span>
                                    </span>
                                    <x-portal.content-badge :type="$p->type" class="hidden shrink-0 sm:inline-flex" />
                                </a>
                            </li>
                        @endforeach
                    </ol>

                    @unless($isCompleted)
                        <div class="border-t border-gray-100 p-4">
                            <a href="{{ route('portal.modules.pages.show', [$module, $nextPage]) }}"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-indigo-700">
                                <x-heroicon-o-play class="h-5 w-5" />
                                {{ $progress ? 'Lanjutkan Belajar' : 'Mulai Belajar' }}
                            </a>
                        </div>
                    @endunless
                @endif
            </div>
        </div>
    </div>
@endsection
