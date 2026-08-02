@php
    $record = $getRecord();
    $replies = $record ? $record->replies : collect();
@endphp

<div class="px-4 py-3 bg-gray-50/50 dark:bg-white/[0.02] border-t border-gray-100 dark:border-white/5 space-y-3">
    <div class="flex items-center justify-between">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
            <x-heroicon-m-chat-bubble-left-right class="h-4 w-4 text-primary-600" />
            <span>Balasan ({{ $replies->count() }})</span>
        </p>
    </div>

    @forelse ($replies as $reply)
        @php
            $replier = $reply->user;
            $avatarUrl = $replier?->avatarUrl();
            $initials = strtoupper(substr($replier?->name ?? 'A', 0, 2));
            $roleLabel = ($replier && $replier->relationLoaded('roles')) ? ($replier->roles->first()?->name ?? 'Pengelola') : 'Pengelola';
        @endphp
        <div class="flex gap-3 p-3 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-white/5 shadow-xs">
            <div class="shrink-0 mt-0.5">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $replier?->name }}" class="h-8 w-8 rounded-full object-cover ring-1 ring-primary-500/20" />
                @else
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-[10px] font-black text-white ring-1 ring-primary-500/20">
                        {{ $initials }}
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $replier?->name ?? 'Pengelola Basamo NCH' }}</span>
                        <span class="rounded bg-primary-50 px-1.5 py-0.5 text-[9px] font-extrabold uppercase text-primary-700 dark:bg-primary-950/60 dark:text-primary-300">
                            {{ $roleLabel }}
                        </span>
                    </div>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500">{{ $reply->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
                <p class="mt-1 text-xs leading-relaxed text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $reply->isi }}</p>
            </div>
        </div>
    @empty
        <div class="p-3 text-center rounded-xl border border-dashed border-gray-200 dark:border-white/10 bg-white/50 dark:bg-gray-900/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada balasan. Gunakan aksi <strong>Balas</strong> untuk menjawab pertanyaan warga.</p>
        </div>
    @endforelse
</div>
