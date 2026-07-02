@extends('portal.layouts.app')

@section('title', 'Diskusi — ' . $module->judul)

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => $module->judul, 'url' => route('portal.modules.show', $module)],
        ['label' => 'Diskusi', 'url' => route('portal.modules.discuss', $module)],
        ['label' => 'Pertanyaan'],
    ]" />

    {{-- Pertanyaan utama --}}
    <div class="rounded-2xl border border-primary/20 bg-primary/5 p-5 shadow-sm">
        <div class="flex items-start gap-3">
            <x-portal.avatar :name="$discussion->user?->name" :src="$discussion->user?->avatarUrl()" variant="solid" size="lg" />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-semibold text-on-surface">{{ $discussion->user?->name ?? 'Pengguna' }}</p>
                    <span class="text-xs text-on-surface-variant">{{ $discussion->created_at->diffForHumans() }}</span>
                </div>
                <p class="mt-2 whitespace-pre-line text-base leading-relaxed text-on-surface">{{ $discussion->isi }}</p>
            </div>
        </div>
    </div>

    {{-- Balasan --}}
    <h2 class="mb-3 mt-6 text-lg font-bold text-on-surface">
        {{ $discussion->replies->count() }} Balasan
    </h2>

    @if($discussion->replies->isEmpty())
        <p class="rounded-2xl border border-dashed border-outline-variant bg-surface-container-lowest py-10 text-center text-base text-on-surface-variant">
            Belum ada balasan. Bantu jawab pertanyaan ini!
        </p>
    @else
        <div class="space-y-3">
            @foreach($discussion->replies as $reply)
                <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <x-portal.avatar :name="$reply->user?->name" :src="$reply->user?->avatarUrl()" variant="gray" size="md" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-on-surface">{{ $reply->user?->name ?? 'Pengguna' }}</p>
                                <span class="text-xs text-on-surface-variant">{{ $reply->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 whitespace-pre-line text-base leading-relaxed text-on-surface-variant">{{ $reply->isi }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Form balasan --}}
    <form method="POST" action="{{ route('portal.modules.discuss.reply', [$module, $discussion]) }}"
        class="mt-6 rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm">
        @csrf
        <label for="isi" class="mb-2 block text-base font-semibold text-on-surface">Tulis balasan</label>
        <textarea name="isi" id="isi" rows="3" required
            class="w-full rounded-xl border-outline-variant text-base focus:border-primary focus:ring-primary @error('isi') border-error @enderror"
            placeholder="Tulis balasanmu...">{{ old('isi') }}</textarea>
        @error('isi')
            <p class="mt-1.5 text-sm text-error">{{ $message }}</p>
        @enderror
        <div class="mt-3 flex justify-end">
            <x-portal.button type="submit" size="lg">
                <x-heroicon-o-paper-airplane class="h-5 w-5" />
                Kirim Balasan
            </x-portal.button>
        </div>
    </form>
@endsection
