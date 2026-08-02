@extends('portal.layouts.app')

@section('title', $evaluasi->title . ' · SLC')

@section('content')
    @if (! empty($isPreview))
        <div class="sticky top-0 z-50 mb-4 flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-950 shadow-md">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <span>Mode Pratinjau Administrator: tampilan nyata warga</span>
        </div>
    @endif
    {{-- Back Link --}}
    <div class="mb-4">
        <a href="{{ ! empty($isPreview)
            ? \App\Filament\Resources\Modules\ModuleResource::getUrl('view', ['record' => $module])
            : route('portal.modules.show', $module) }}"
            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-on-surface-variant hover:text-primary transition-colors">
            <x-heroicon-s-arrow-left class="h-4 w-4" />
            <span>Kembali ke Detail Modul</span>
        </a>
    </div>

    <livewire:evaluasi-player :evaluasi="$evaluasi" :is-preview="! empty($isPreview)" />
@endsection
