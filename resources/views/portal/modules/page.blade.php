@extends('portal.layouts.app')

@section('title', $page->title)
@section('main-class', 'py-6 pb-28')

@php
    $totalPages = $pages->count();
    $done = count($pagesCompleted);
    $pct = $totalPages > 0 ? (int) ($done / $totalPages * 100) : 0;
    $currentIdx = $pages->search(fn ($p) => $p->id === $page->id) + 1;
@endphp

{{-- Reader nav menggantikan bottom nav mobile --}}
@section('bottom-navigation')
<div class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white shadow-[0_-1px_3px_rgba(0,0,0,0.04)] lg:left-64">
    <div class="h-1 bg-gray-100">
        <div class="h-full bg-indigo-500 transition-all" style="width: {{ $pct }}%"></div>
    </div>
    <div class="mx-auto flex h-16 max-w-5xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        @if($prevPage)
            <a href="{{ route('portal.modules.pages.show', [$module, $prevPage]) }}"
                class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-50">
                <x-heroicon-o-arrow-left class="h-4 w-4" />
                <span class="hidden sm:inline">Sebelumnya</span>
            </a>
        @else
            <a href="{{ route('portal.modules.show', $module) }}"
                class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-50">
                <x-heroicon-o-arrow-left class="h-4 w-4" />
                <span class="hidden sm:inline">Ke Modul</span>
            </a>
        @endif

        <div class="flex items-center gap-1.5">
            @foreach($pages as $p)
                @php $isDone = in_array($p->id, $pagesCompleted); $isCurrent = $p->id === $page->id; @endphp
                <span class="rounded-full transition-all
                    @if($isCurrent) h-2 w-6 bg-indigo-500
                    @elseif($isDone) h-2 w-2 bg-emerald-400
                    @else h-2 w-2 bg-gray-200 @endif"></span>
            @endforeach
        </div>

        @if($nextPage)
            <a href="{{ route('portal.modules.pages.show', [$module, $nextPage]) }}"
                class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-indigo-700">
                <span class="hidden sm:inline">Selanjutnya</span>
                <x-heroicon-o-arrow-right class="h-4 w-4" />
            </a>
        @else
            <a href="{{ route('portal.modules.show', $module) }}"
                class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-emerald-700">
                <span class="hidden sm:inline">Selesai</span>
                <x-heroicon-s-check-circle class="h-4 w-4" />
            </a>
        @endif
    </div>
</div>
@endsection

@section('content')
    {{-- Breadcrumb --}}
    <x-portal.breadcrumb :items="[
        ['label' => $module->title, 'url' => route('portal.modules.show', $module)],
        ['label' => $page->title],
    ]" />

    @if($c = session('celebrate'))
        <script>
            window.addEventListener('load', () => {
                window.fireConfetti?.();
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'xp', title: @json($c['title']), message: @json($c['message']) } }));
            });
        </script>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">

        {{-- Content --}}
        <div class="lg:col-span-2">
            <x-portal.card :padded="false">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-400">Materi {{ $currentIdx }} dari {{ $totalPages }}</p>
                        <h1 class="mt-0.5 text-lg font-bold leading-snug text-gray-900">{{ $page->title }}</h1>
                    </div>
                    <x-portal.content-badge :type="$page->type" class="shrink-0" />
                </div>

                <div class="p-5 sm:p-6 lg:p-8">
                    @if($page->type === 'text')
                        <div class="prose prose-sm max-w-none leading-relaxed text-gray-700 prose-headings:text-gray-900 prose-a:text-indigo-600 prose-img:rounded-xl sm:prose-base">
                            {!! $page->content !!}
                        </div>
                    @elseif($page->type === 'video')
                        @php
                            $videoId = null;
                            $driveId = null;
                            if ($page->video_url) {
                                if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $page->video_url, $vm)) {
                                    $videoId = $vm[1];
                                } elseif (preg_match('#drive\.google\.com/file/d/([A-Za-z0-9_-]+)#', $page->video_url, $dm)) {
                                    $driveId = $dm[1];
                                }
                            }
                        @endphp
                        @if($videoId)
                            <div class="aspect-video overflow-hidden rounded-xl bg-black">
                                <iframe src="https://www.youtube.com/embed/{{ $videoId }}?rel=0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen class="h-full w-full"></iframe>
                            </div>
                        @elseif($driveId)
                            <div class="aspect-video overflow-hidden rounded-xl bg-black">
                                <iframe src="https://drive.google.com/file/d/{{ $driveId }}/preview"
                                    allow="autoplay" allowfullscreen class="h-full w-full"></iframe>
                            </div>
                        @elseif($page->video_url)
                            <div class="flex items-center justify-center rounded-xl bg-gray-50 py-14">
                                <a href="{{ $page->video_url }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-indigo-700">
                                    <x-heroicon-s-play-circle class="h-5 w-5" />
                                    Buka Video
                                </a>
                            </div>
                        @endif
                    @elseif($page->type === 'pdf' && $page->file_path)
                        <div class="overflow-hidden rounded-xl border border-gray-200">
                            <embed src="{{ Storage::disk('public')->url($page->file_path) }}" type="application/pdf" class="w-full" style="height: min(70vh, 650px)">
                        </div>
                        <a href="{{ Storage::disk('public')->url($page->file_path) }}" target="_blank"
                            class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:underline">
                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                            Unduh PDF
                        </a>
                    @endif
                </div>
            </x-portal.card>
        </div>

        {{-- Sidebar outline --}}
        <aside class="hidden lg:block">
            <x-portal.card :padded="false" class="sticky top-20">
                <div class="border-b border-gray-100 px-5 py-4">
                    <p class="font-bold text-gray-900">Daftar Materi</p>
                    <p class="mt-0.5 text-xs text-gray-400">{{ $done }} dari {{ $totalPages }} selesai</p>
                </div>
                <ol class="divide-y divide-gray-50">
                    @foreach($pages as $p)
                        @php $isDone = in_array($p->id, $pagesCompleted); $isCurrent = $p->id === $page->id; @endphp
                        <li>
                            <a href="{{ route('portal.modules.pages.show', [$module, $p]) }}"
                                class="flex items-center gap-3 px-5 py-3 transition-colors {{ $isCurrent ? 'bg-indigo-50' : 'hover:bg-slate-50' }}">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold
                                    {{ $isDone ? 'bg-emerald-100 text-emerald-600' : ($isCurrent ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400') }}">
                                    @if($isDone)
                                        <x-heroicon-s-check class="h-3.5 w-3.5" />
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>
                                <span class="truncate text-sm font-medium {{ $isCurrent ? 'text-indigo-700' : ($isDone ? 'text-gray-400' : 'text-gray-700') }}">
                                    {{ $p->title }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </x-portal.card>
        </aside>
    </div>
@endsection
