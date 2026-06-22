@php
    // Aksen gradien mengikuti peran (kedua set kelas ditulis literal agar ter-compile).
    $grad = $super
        ? 'from-indigo-500 via-indigo-600 to-indigo-800'
        : 'from-teal-500 via-teal-600 to-teal-800';
@endphp

<x-filament-widgets::widget>
    <div class="relative isolate overflow-hidden rounded-xl bg-gradient-to-br {{ $grad }} p-6 shadow-sm ring-1 ring-black/5 sm:p-7">
        {{-- Ornamen ikon besar transparan --}}
        <x-filament::icon
            icon="heroicon-o-academic-cap"
            class="pointer-events-none absolute -right-6 -top-6 -z-10 h-40 w-40 text-white/10"
        />

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white/15 text-lg font-bold text-white ring-1 ring-inset ring-white/30">
                    {{ $initials }}
                </span>

                <div class="min-w-0">
                    <p class="text-lg font-bold leading-tight text-white sm:text-xl">
                        {{ $greeting }}, {{ $name }}
                    </p>
                    <p class="mt-1 truncate text-sm text-white/80">
                        {{ $roleLabel }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col items-start gap-2 sm:items-end">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-inset ring-white/30">
                    <x-filament::icon icon="heroicon-m-shield-check" class="h-3.5 w-3.5" />
                    {{ $roleBadge }}
                </span>

                <p class="inline-flex items-center gap-1.5 text-sm text-white/85">
                    <x-filament::icon icon="heroicon-m-calendar-days" class="h-4 w-4" />
                    {{ $dateLabel }} · {{ $timeLabel }}
                </p>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
