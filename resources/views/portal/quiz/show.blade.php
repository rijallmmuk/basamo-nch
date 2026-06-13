@extends('portal.layouts.app')

@section('title', 'Kuis: ' . $quiz->title)

@section('content')
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-4">
        <a href="{{ route('portal.modules.index') }}" class="hover:text-indigo-600">Modul</a>
        <span>/</span>
        <a href="{{ route('portal.modules.show', $module) }}" class="hover:text-indigo-600">{{ $module->title }}</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">Kuis</span>
    </div>

    <livewire:quiz-player :quiz="$quiz" />
@endsection
