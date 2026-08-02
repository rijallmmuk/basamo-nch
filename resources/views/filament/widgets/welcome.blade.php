<x-filament-widgets::widget>
    <div class="nch-welcome-card">
        <x-filament::icon
            icon="heroicon-o-academic-cap"
            class="nch-welcome-ornament"
        />

        <div class="nch-welcome-content">
            <div class="nch-welcome-identity">
                <span class="nch-welcome-avatar" aria-hidden="true">
                    {{ $initials }}
                </span>

                <div class="min-w-0">
                    <p class="nch-welcome-heading">
                        {{ $greeting }}, {{ $name }}
                    </p>
                </div>
            </div>

            <div class="nch-welcome-date">
                <p>
                    <x-filament::icon icon="heroicon-m-calendar-days" class="h-4 w-4" />
                    <span>{{ $dateLabel }}</span>
                </p>
                <span class="nch-welcome-time">{{ $timeLabel }}</span>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
