@extends('portal.layouts.app')

@section('title', 'Diskusi — ' . $module->judul)

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => $module->judul, 'url' => route('portal.modules.show', $module)],
        ['label' => 'Diskusi'],
    ]" />

    {{-- Header --}}
    <div class="mb-5">
        <div class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-secondary-container text-on-secondary-container">
                <x-heroicon-s-chat-bubble-left-right class="h-5 w-5" />
            </span>
            <h1 class="text-2xl font-bold text-on-surface">Ruang Diskusi</h1>
        </div>
        <p class="mt-1 text-base text-on-surface-variant">{{ $module->judul }}</p>
    </div>

    {{-- Form pertanyaan baru --}}
    <form method="POST" action="{{ route('portal.modules.discuss.store', $module) }}"
        class="mb-6 rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm">
        @csrf
        <label for="isi" class="mb-2 block text-base font-semibold text-on-surface">Ajukan pertanyaan</label>
        <textarea name="isi" id="isi" rows="3" required
            class="w-full rounded-xl border-outline-variant text-base focus:border-primary focus:ring-primary @error('isi') border-error @enderror"
            placeholder="Tulis pertanyaan atau hal yang ingin kamu diskusikan...">{{ old('isi') }}</textarea>
        @error('isi')
            <p class="mt-1.5 text-sm text-error">{{ $message }}</p>
        @enderror
        <div class="mt-3 flex justify-end">
            <x-portal.button type="submit" size="lg">
                <x-heroicon-o-paper-airplane class="h-5 w-5" />
                Kirim
            </x-portal.button>
        </div>
    </form>

    {{-- Daftar thread --}}
    @if($threads->isEmpty())
        <x-portal.empty
            icon="heroicon-o-chat-bubble-left-right"
            title="Belum ada diskusi"
            subtitle="Jadilah yang pertama mengajukan pertanyaan." />
    @else
        <div class="space-y-3">
            @foreach($threads as $thread)
                <a href="{{ route('portal.modules.discuss.show', [$module, $thread]) }}"
                    class="block rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm transition-colors hover:bg-surface-container-low">
                    <div class="flex items-start gap-3">
                        <x-portal.avatar :name="$thread->user?->name" size="md" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-on-surface">{{ $thread->user?->name ?? 'Pengguna' }}</p>
                                @if($thread->is_pinned)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2 py-0.5 text-xs font-semibold text-on-secondary-container">
                                        <x-heroicon-s-bookmark class="h-3 w-3" /> Disematkan
                                    </span>
                                @endif
                                <span class="text-xs text-on-surface-variant">{{ $thread->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-base text-on-surface-variant">{{ $thread->isi }}</p>
                            <p class="mt-2 inline-flex items-center gap-1.5 text-sm text-on-surface-variant">
                                <x-heroicon-o-chat-bubble-oval-left class="h-4 w-4" />
                                {{ $thread->replies_count }} balasan
                            </p>
                        </div>
                        <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-outline-variant" />
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
