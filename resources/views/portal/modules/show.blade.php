@extends('portal.layouts.app')

@section('title', $module->title)

@section('content')
    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-4">
        <a href="{{ route('portal.modules.index') }}" class="hover:text-indigo-600">Modul</a>
        <span>/</span>
        <span class="text-gray-700 font-medium truncate">{{ $module->title }}</span>
    </div>

    {{-- Module header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-4">
        @if($module->thumbnail)
            <img src="{{ Storage::url($module->thumbnail) }}" alt=""
                class="w-full h-40 object-cover rounded-lg mb-4">
        @endif
        <h1 class="text-xl font-bold text-gray-900">{{ $module->title }}</h1>
        @if($module->description)
            <div class="text-sm text-gray-600 mt-2 prose prose-sm max-w-none">
                {!! $module->description !!}
            </div>
        @endif

        @if($isCompleted && $module->quiz)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <a href="{{ route('portal.modules.quiz', $module) }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    📝 Kerjakan Kuis
                </a>
            </div>
        @endif
    </div>

    {{-- Pages list --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800 text-sm">
                Materi Pembelajaran
                <span class="text-gray-400 font-normal">({{ $pages->count() }} halaman)</span>
            </h2>
        </div>

        @if($pages->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">
                Belum ada materi di modul ini
            </div>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($pages as $page)
                    @php $done = in_array($page->id, $pagesCompleted); @endphp
                    <a href="{{ route('portal.modules.pages.show', [$module, $page]) }}"
                        class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition-colors">

                        {{-- Status icon --}}
                        <div class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center
                            {{ $done ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                            @if($done)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <span class="text-xs font-bold">{{ $loop->iteration }}</span>
                            @endif
                        </div>

                        {{-- Title & type --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $page->title }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                @if($page->type === 'video') 🎬 Video
                                @elseif($page->type === 'pdf') 📄 PDF
                                @else 📝 Teks
                                @endif
                            </p>
                        </div>

                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if(!$isCompleted && $pages->isNotEmpty())
        @php $firstPage = $pages->first(); @endphp
        <div class="mt-4">
            <a href="{{ route('portal.modules.pages.show', [$module, $firstPage]) }}"
                class="block w-full text-center py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors">
                @if($progress) Lanjutkan Belajar @else Mulai Belajar @endif
            </a>
        </div>
    @endif
@endsection
