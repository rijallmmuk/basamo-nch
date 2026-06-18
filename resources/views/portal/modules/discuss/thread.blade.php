@extends('portal.layouts.app')

@section('title', 'Diskusi — ' . $module->title)

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => $module->title, 'url' => route('portal.modules.show', $module)],
        ['label' => 'Diskusi', 'url' => route('portal.modules.discuss', $module)],
        ['label' => 'Pertanyaan'],
    ]" />

    {{-- Pertanyaan utama --}}
    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/50 p-5 shadow-sm">
        <div class="flex items-start gap-3">
            <x-portal.avatar :name="$discussion->user?->name" variant="solid" size="lg" />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-semibold text-gray-900">{{ $discussion->user?->name ?? 'Pengguna' }}</p>
                    <span class="text-xs text-gray-400">{{ $discussion->created_at->diffForHumans() }}</span>
                </div>
                <p class="mt-2 whitespace-pre-line text-base leading-relaxed text-gray-800">{{ $discussion->body }}</p>
            </div>
        </div>
    </div>

    {{-- Balasan --}}
    <h2 class="mb-3 mt-6 text-lg font-bold text-gray-900">
        {{ $discussion->replies->count() }} Balasan
    </h2>

    @if($discussion->replies->isEmpty())
        <p class="rounded-2xl border border-dashed border-gray-300 bg-white py-10 text-center text-base text-gray-400">
            Belum ada balasan. Bantu jawab pertanyaan ini!
        </p>
    @else
        <div class="space-y-3">
            @foreach($discussion->replies as $reply)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <x-portal.avatar :name="$reply->user?->name" variant="gray" size="md" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-gray-800">{{ $reply->user?->name ?? 'Pengguna' }}</p>
                                <span class="text-xs text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 whitespace-pre-line text-base leading-relaxed text-gray-700">{{ $reply->body }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Form balasan --}}
    <form method="POST" action="{{ route('portal.modules.discuss.reply', [$module, $discussion]) }}"
        class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        <label for="body" class="mb-2 block text-base font-semibold text-gray-800">Tulis balasan</label>
        <textarea name="body" id="body" rows="3" required
            class="w-full rounded-xl border-gray-200 text-base focus:border-indigo-500 focus:ring-indigo-500 @error('body') border-red-300 @enderror"
            placeholder="Tulis balasanmu...">{{ old('body') }}</textarea>
        @error('body')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <div class="mt-3 flex justify-end">
            <x-portal.button type="submit" size="lg">
                <x-heroicon-o-paper-airplane class="h-5 w-5" />
                Kirim Balasan
            </x-portal.button>
        </div>
    </form>
@endsection
