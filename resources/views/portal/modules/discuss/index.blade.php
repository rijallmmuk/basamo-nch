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
            <x-heroicon-s-chat-bubble-left-right class="h-6 w-6 text-amber-600" />
            <h1 class="text-2xl font-bold text-gray-900">Ruang Diskusi</h1>
        </div>
        <p class="mt-1 text-base text-gray-500">{{ $module->judul }}</p>
    </div>

    {{-- Form pertanyaan baru --}}
    <form method="POST" action="{{ route('portal.modules.discuss.store', $module) }}"
        class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        <label for="isi" class="mb-2 block text-base font-semibold text-gray-800">Ajukan pertanyaan</label>
        <textarea name="isi" id="isi" rows="3" required
            class="w-full rounded-xl border-gray-200 text-base focus:border-indigo-500 focus:ring-indigo-500 @error('isi') border-red-300 @enderror"
            placeholder="Tulis pertanyaan atau hal yang ingin kamu diskusikan...">{{ old('isi') }}</textarea>
        @error('isi')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
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
                    class="block rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-colors hover:bg-slate-50">
                    <div class="flex items-start gap-3">
                        <x-portal.avatar :name="$thread->user?->name" size="md" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-gray-800">{{ $thread->user?->name ?? 'Pengguna' }}</p>
                                @if($thread->is_pinned)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                        <x-heroicon-s-bookmark class="h-3 w-3" /> Disematkan
                                    </span>
                                @endif
                                <span class="text-xs text-gray-400">{{ $thread->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-base text-gray-700">{{ $thread->isi }}</p>
                            <p class="mt-2 inline-flex items-center gap-1.5 text-sm text-gray-400">
                                <x-heroicon-o-chat-bubble-oval-left class="h-4 w-4" />
                                {{ $thread->replies_count }} balasan
                            </p>
                        </div>
                        <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-gray-300" />
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
