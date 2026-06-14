@extends('portal.layouts.app')

@section('title', 'Diskusi — ' . $module->title)

@section('content')
    {{-- Back --}}
    <a href="{{ route('portal.modules.show', $module) }}"
        class="mb-5 inline-flex items-center gap-2 text-base font-medium text-gray-500 hover:text-indigo-600 transition-colors">
        <x-heroicon-o-arrow-left class="h-5 w-5" />
        Kembali ke Modul
    </a>

    {{-- Header --}}
    <div class="mb-5">
        <div class="flex items-center gap-2.5">
            <x-heroicon-s-chat-bubble-left-right class="h-6 w-6 text-amber-600" />
            <h1 class="text-2xl font-bold text-gray-900">Ruang Diskusi</h1>
        </div>
        <p class="mt-1 text-base text-gray-500">{{ $module->title }}</p>
    </div>

    {{-- Form pertanyaan baru --}}
    <form method="POST" action="{{ route('portal.modules.discuss.store', $module) }}"
        class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        <label for="body" class="mb-2 block text-base font-semibold text-gray-800">Ajukan pertanyaan</label>
        <textarea name="body" id="body" rows="3" required
            class="w-full rounded-xl border-gray-200 text-base focus:border-indigo-500 focus:ring-indigo-500 @error('body') border-red-300 @enderror"
            placeholder="Tulis pertanyaan atau hal yang ingin kamu diskusikan...">{{ old('body') }}</textarea>
        @error('body')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <div class="mt-3 flex justify-end">
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-base font-bold text-white transition-colors hover:bg-indigo-700">
                <x-heroicon-o-paper-airplane class="h-5 w-5" />
                Kirim
            </button>
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
                            <p class="mt-1 line-clamp-2 text-base text-gray-700">{{ $thread->body }}</p>
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
