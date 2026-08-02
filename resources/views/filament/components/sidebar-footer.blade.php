<div>
    @if (auth()->check())
        <div class="nch-sidebar-context" title="{{ $roleLabel }} · {{ $contextLabel }}">
            <div class="nch-sidebar-context-icon" aria-hidden="true">
                <x-filament::icon icon="heroicon-o-map-pin" />
            </div>

            <div class="nch-sidebar-context-copy">
                <p class="nch-sidebar-context-eyebrow">{{ $roleLabel }}</p>
                <p class="nch-sidebar-context-title">{{ $contextLabel }}</p>
                <p class="nch-sidebar-context-description">{{ $contextDescription }}</p>
            </div>
        </div>

        <div class="nch-sidebar-pillars" aria-label="Empat pilar Nagari Creative Hub">
            <span>LMS</span>
            <span>SDGs</span>
            <span>UMKM</span>
            <span>IoT</span>
        </div>
    @endif
</div>
