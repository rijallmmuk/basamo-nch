@extends('portal.layouts.app')

@section('title', $quiz->title)

@section('content')
    {{-- Breadcrumb --}}
    <x-portal.breadcrumb :items="[
        ['label' => $module->judul, 'url' => route('portal.modules.show', $module)],
        ['label' => 'Kuis'],
    ]" />

    <livewire:quiz-player :quiz="$quiz" />
@endsection
