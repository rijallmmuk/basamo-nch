@extends('portal.layouts.app')

@section('title', $page->title)

@section('content')
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-4">
        <a href="{{ route('portal.modules.index') }}" class="hover:text-indigo-600">Modul</a>
        <span>/</span>
        <a href="{{ route('portal.modules.show', $module) }}" class="hover:text-indigo-600 truncate max-w-28">
            {{ $module->title }}
        </a>
        <span>/</span>
        <span class="text-gray-700 font-medium truncate">{{ $page->title }}</span>
    </div>

    {{-- Progress strip --}}
    <div class="h-1 bg-gray-200 rounded-full mb-5 overflow-hidden">
        @php
            $totalPages = $pages->count();
            $done = count($pagesCompleted);
            $pct = $totalPages > 0 ? (int)($done / $totalPages * 100) : 0;
        @endphp
        <div class="h-full bg-indigo-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
    </div>

    {{-- Content card --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h1 class="font-semibold text-gray-900">{{ $page->title }}</h1>
            <span class="text-xs text-gray-400">
                @if($page->type === 'video') 🎬 Video
                @elseif($page->type === 'pdf') 📄 PDF
                @else 📝 Teks
                @endif
            </span>
        </div>

        <div class="p-5">
            {{-- TEXT content --}}
            @if($page->type === 'text')
                <div class="prose prose-sm max-w-none text-gray-700">
                    {!! $page->content !!}
                </div>
            @endif

            {{-- VIDEO embed --}}
            @if($page->type === 'video')
                @php
                    $videoId = null;
                    if ($page->video_url && preg_match(
                        '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/',
                        $page->video_url,
                        $m
                    )) {
                        $videoId = $m[1];
                    }
                @endphp

                @if($videoId)
                    <div class="aspect-video rounded-lg overflow-hidden bg-black">
                        <iframe
                            src="https://www.youtube.com/embed/{{ $videoId }}"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            class="w-full h-full">
                        </iframe>
                    </div>
                @elseif($page->video_url)
                    <div class="flex items-center justify-center h-40 bg-gray-50 rounded-lg">
                        <a href="{{ $page->video_url }}" target="_blank" rel="noopener"
                            class="text-indigo-600 font-medium text-sm hover:underline">
                            🎬 Buka Video di Tab Baru
                        </a>
                    </div>
                @endif
            @endif

            {{-- PDF embed --}}
            @if($page->type === 'pdf' && $page->file_path)
                <embed
                    src="{{ Storage::url($page->file_path) }}"
                    type="application/pdf"
                    class="w-full rounded-lg"
                    style="height: 600px;">
                <a href="{{ Storage::url($page->file_path) }}" target="_blank"
                    class="mt-2 inline-block text-sm text-indigo-600 hover:underline">
                    Unduh / Buka di tab baru ↗
                </a>
            @endif
        </div>
    </div>

    {{-- Navigation --}}
    <div class="flex items-center justify-between gap-3">
        @if($prevPage)
            <a href="{{ route('portal.modules.pages.show', [$module, $prevPage]) }}"
                class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                ← Sebelumnya
            </a>
        @else
            <a href="{{ route('portal.modules.show', $module) }}"
                class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                ← Kembali ke Modul
            </a>
        @endif

        @if($nextPage)
            <a href="{{ route('portal.modules.pages.show', [$module, $nextPage]) }}"
                class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                Lanjut →
            </a>
        @else
            <a href="{{ route('portal.modules.show', $module) }}"
                class="flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                ✅ Selesai — Kembali ke Modul
            </a>
        @endif
    </div>
@endsection
