<x-filament-widgets::widget>
    {{-- Palet tunggal NCH Deep Blue untuk semua peran (tanpa pembedaan warna). --}}
    <div class="relative isolate overflow-hidden rounded-xl bg-gradient-to-br from-[#1b4f72] via-[#003857] to-[#002338] p-6 shadow-sm ring-1 ring-black/5 sm:p-7">
        {{-- Ornamen ikon besar (aksen emas Minang) --}}
        <x-filament::icon
            icon="heroicon-o-academic-cap"
            class="pointer-events-none absolute -right-6 -top-6 -z-10 h-40 w-40 text-[#fed33e]/15"
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
                </div>
            </div>

            <div class="flex flex-col items-start sm:items-end">
                <p class="inline-flex items-center gap-1.5 text-sm text-white/85">
                    <x-filament::icon icon="heroicon-m-calendar-days" class="h-4 w-4" />
                    {{ $dateLabel }} · {{ $timeLabel }}
                </p>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
