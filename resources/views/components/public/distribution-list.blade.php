@props(['items', 'empty' => 'Belum ada data'])

@php($maximum = max(1, collect($items)->max('value') ?? 1))

@if(collect($items)->sum('value') === 0)
    <div class="flex min-h-36 flex-col items-center justify-center rounded-2xl bg-surface-container-low px-5 text-center border border-dashed border-outline-variant">
        <x-heroicon-o-chart-bar class="h-8 w-8 text-outline" />
        <p class="mt-2 text-sm font-semibold text-on-surface-variant">{{ $empty }}</p>
    </div>
@else
    <div class="space-y-4" data-distribution-list>
        @foreach($items as $item)
            @php($percent = max(4, round($item['value'] / $maximum * 100, 1)))
            <div class="group">
                <div class="mb-1.5 flex items-center justify-between gap-4 text-sm">
                    <span class="truncate font-semibold text-on-surface group-hover:text-primary transition-colors">{{ $item['label'] }}</span>
                    <span class="shrink-0 font-extrabold tabular-nums text-primary">{{ number_format($item['value'], 0, ',', '.') }}</span>
                </div>
                <div class="h-2.5 overflow-hidden rounded-full bg-surface-container-high p-0.5">
                    <div class="bar-animated h-full rounded-full bg-gradient-to-r from-primary via-primary-highlight to-secondary-container transition-all duration-700 shadow-sm"
                         data-bar-target="{{ $percent }}%"
                         style="width: {{ $percent }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!('IntersectionObserver' in window)) return;
            
            const barObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.querySelectorAll('.bar-animated').forEach((bar) => {
                            bar.classList.add('bar-revealed');
                        });
                        barObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });

            document.querySelectorAll('[data-distribution-list]').forEach((el) => barObserver.observe(el));
        });
    </script>
@endpushOnce

