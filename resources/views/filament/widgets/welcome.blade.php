<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:.9rem;min-width:0;">
                <span style="flex:none;width:.3rem;height:2.75rem;border-radius:9999px;background:{{ $accent }};"></span>
                <div style="min-width:0;">
                    <p class="text-base font-bold text-gray-900 dark:text-white" style="margin:0;">
                        {{ $greeting }}, {{ $name }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400" style="margin:.15rem 0 0;">
                        {{ $roleLabel }}
                    </p>
                </div>
            </div>

            <div style="text-align:right;">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300" style="margin:0;">
                    {{ $dateLabel }}
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
