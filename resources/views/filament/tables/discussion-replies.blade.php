@php($replies = $getRecord()->replies)

<div class="px-3 py-1">
    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
        Balasan ({{ $replies->count() }})
    </p>

    @forelse ($replies as $reply)
        <div @class([
            'flex gap-2.5 py-2',
            'border-t border-gray-100 dark:border-white/5' => ! $loop->first,
        ])>
            <x-filament::icon
                icon="heroicon-m-arrow-turn-down-right"
                class="mt-0.5 h-4 w-4 shrink-0 text-gray-300 dark:text-gray-600"
            />
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $reply->user?->name ?? '—' }}</span>
                    <span>·</span>
                    <span>{{ $reply->created_at->translatedFormat('d M Y H:i') }}</span>
                </div>
                <p class="mt-0.5 whitespace-pre-line text-sm text-gray-950 dark:text-white">{{ $reply->isi }}</p>
            </div>
        </div>
    @empty
        <p class="py-1 text-sm text-gray-500 dark:text-gray-400">Belum ada balasan.</p>
    @endforelse
</div>
