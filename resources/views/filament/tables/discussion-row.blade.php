@php
    $record = $getRecord();
    $user = auth()->user();
    $author = $record->user;
    $isRead = $user ? $record->isReadBy($user) : true;
    $nagariNama = $author?->nagari?->nama_lengkap ?? $author?->nagari?->nama ?? 'Nagari Terdaftar';
    $avatarUrl = $author?->avatarUrl();
    $initials = strtoupper(substr($author?->name ?? 'W', 0, 2));
    $repliesCount = $record->replies_count ?? $record->replies()->count();
@endphp

<div class="flex flex-col gap-3 p-4 transition-colors hover:bg-gray-50/50 dark:hover:bg-white/5">
    {{-- Header: Avatar, Penanya Info, Nagari Badge, Time & Badges --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-start gap-3 min-w-0">
            {{-- User Avatar --}}
            <div class="relative shrink-0 mt-0.5">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $author?->name }}" class="h-10 w-10 rounded-full object-cover ring-2 ring-primary-500/20 shadow-sm" />
                @else
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-primary-600 to-primary-800 text-xs font-black text-white shadow-sm ring-2 ring-primary-500/20">
                        {{ $initials }}
                    </div>
                @endif
                @if(!$isRead)
                    <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    </span>
                @endif
            </div>

            {{-- Name, Nagari, Time --}}
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-bold text-gray-900 dark:text-white truncate" title="{{ $author?->name }}">
                        {{ $author?->name ?? 'Warga Anonymous' }}
                    </span>
                    
                    {{-- Nagari Origin Badge --}}
                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10">
                        <x-heroicon-m-map-pin class="h-3 w-3 text-blue-500 shrink-0" />
                        <span class="truncate">{{ $nagariNama }}</span>
                    </span>
                </div>
                
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                    <span>{{ $record->created_at->diffForHumans() }}</span>
                    <span>·</span>
                    <span>{{ $record->created_at->translatedFormat('d M Y, H:i') }}</span>
                </p>
            </div>
        </div>

        {{-- Status Badges --}}
        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            @if($record->is_pinned)
                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 ring-1 ring-inset ring-amber-600/20">
                    <x-heroicon-s-bookmark class="h-3.5 w-3.5 text-amber-500" />
                    <span>Disematkan</span>
                </span>
            @endif

            @if(!$isRead)
                <span class="inline-flex items-center gap-1 rounded-md bg-amber-500/15 px-2 py-1 text-xs font-black text-amber-800 dark:text-amber-300 ring-1 ring-inset ring-amber-500/30">
                    <x-heroicon-s-bell class="h-3.5 w-3.5 text-amber-600" />
                    <span>BARU / Belum Dibaca</span>
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    <x-heroicon-m-check-circle class="h-3.5 w-3.5 text-gray-400" />
                    <span>Sudah Dibaca</span>
                </span>
            @endif

            @if($repliesCount === 0)
                <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-1 text-xs font-bold text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 ring-1 ring-inset ring-rose-600/20">
                    <x-heroicon-m-chat-bubble-left-right class="h-3.5 w-3.5 text-rose-500" />
                    <span>Belum Dibalas</span>
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-md bg-primary-50 px-2 py-1 text-xs font-bold text-primary-700 dark:bg-primary-950/50 dark:text-primary-300 ring-1 ring-inset ring-primary-600/20">
                    <x-heroicon-m-chat-bubble-left-right class="h-3.5 w-3.5 text-primary-600" />
                    <span>{{ $repliesCount }} Balasan</span>
                </span>
            @endif
        </div>
    </div>

    {{-- Diskusi Content --}}
    <div class="pl-1">
        <p @class([
            'text-sm leading-relaxed whitespace-pre-line',
            'font-semibold text-gray-950 dark:text-white' => !$isRead,
            'text-gray-800 dark:text-gray-200' => $isRead,
            'line-through opacity-60' => $record->trashed(),
        ])>
            {{ $record->isi }}
        </p>
    </div>

    {{-- Footer Source Meta & Reply Toggle --}}
    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-100 dark:border-white/5">
        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300 ring-1 ring-inset ring-gray-500/10">
                <x-heroicon-m-book-open class="h-3.5 w-3.5 text-primary-600" />
                <span>{{ $record->module?->judul ?? 'Modul tidak tersedia' }}</span>
            </span>
            @if($record->module?->pelatihan)
                <span>·</span>
                <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">
                    Pelatihan: {{ $record->module->pelatihan->namaTampil() }}
                </span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if($record->trashed())
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 dark:text-rose-400 mr-2">
                    <x-heroicon-m-trash class="h-3.5 w-3.5" />
                    <span>Dihapus {{ $record->deleted_at?->translatedFormat('d M Y, H:i') }}</span>
                </span>
            @endif

            <button type="button"
                @click.stop="isCollapsed = ! isCollapsed"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition-all cursor-pointer shadow-xs">
                <x-heroicon-m-chat-bubble-left-right class="h-3.5 w-3.5 text-primary-600" />
                <span>{{ $repliesCount > 0 ? 'Lihat ' . $repliesCount . ' Balasan' : 'Buka Thread Balasan' }}</span>
                <x-heroicon-m-chevron-down class="h-3.5 w-3.5 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': !isCollapsed }" />
            </button>
        </div>
    </div>
</div>
