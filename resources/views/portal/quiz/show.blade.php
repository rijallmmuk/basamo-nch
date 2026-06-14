@extends('portal.layouts.app')

@section('title', 'Kuis: ' . $quiz->title)

@section('content')
    {{-- Breadcrumb --}}
    <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-sm text-gray-400">
        <a href="{{ route('portal.modules.show', $module) }}" class="max-w-[200px] truncate transition-colors hover:text-indigo-600">{{ $module->title }}</a>
        <x-heroicon-o-chevron-right class="h-3.5 w-3.5" />
        <span class="font-medium text-gray-700">Kuis</span>
    </nav>

    <livewire:quiz-player :quiz="$quiz" />
@endsection
