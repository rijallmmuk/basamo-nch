@props([
    'nagari' => null,
    'photos' => null,
    'photo' => null,
    'metrics' => [],
])

@php
    $isNagari = $nagari instanceof \App\Models\Nagari;
    $photoList = collect($photos ?? ($photo ? [$photo] : []))->filter()->values();
    $hasPhotos = $photoList->isNotEmpty();
@endphp

<section class="hero-gradient-mesh relative isolate overflow-hidden {{ $isNagari ? 'min-h-[min(92svh,46rem)]' : 'min-h-[min(88svh,42rem)]' }}">
    {{-- Animated Floating Luminous Orbs --}}
    <div class="floating-orb floating-orb--gold -right-24 -top-24 h-96 w-96 bg-secondary-container/20"></div>
    <div class="floating-orb floating-orb--blue -bottom-48 left-1/4 h-[30rem] w-[30rem] bg-primary-highlight/30"></div>
    <div class="floating-orb floating-orb--green bottom-10 right-1/3 h-80 w-80 bg-tertiary-fixed/15"></div>

    <div class="songket-pattern absolute inset-0 opacity-10" aria-hidden="true"></div>

    @if($hasPhotos)
        <div id="hero-sampul" class="absolute inset-0" data-hero-slider>
            @foreach($photoList as $index => $url)
                <img
                    src="{{ $url }}"
                    alt=""
                    class="ken-burns absolute inset-0 h-full w-full object-cover transition-opacity duration-1000 {{ $index === 0 ? 'opacity-25' : 'opacity-0' }}"
                    data-hero-slide
                    @if($index === 0) fetchpriority="high" @else loading="lazy" @endif
                >
            @endforeach
            <div class="absolute inset-0 bg-gradient-to-br from-primary/95 via-primary/88 to-primary/80" aria-hidden="true"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-primary to-transparent" aria-hidden="true"></div>
        </div>
    @else
        <div class="gonjong-bg absolute inset-0 opacity-20" aria-hidden="true"></div>
        @unless($isNagari)
            <img
                src="{{ asset('img/home/hero-smart-nagari.webp') }}"
                alt=""
                class="pointer-events-none absolute -right-8 bottom-0 hidden h-[72%] w-auto max-w-[42%] object-contain opacity-25 lg:block transition-all duration-700 hover:opacity-40"
                fetchpriority="high"
            >
        @endunless
    @endif

    <div class="relative mx-auto flex min-h-[inherit] max-w-container-page flex-col justify-center gap-12 px-margin-mobile py-14 sm:py-16 lg:gap-16 lg:px-margin-page lg:py-20">
        <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-5">
                @if($isNagari)
                    <h1 class="text-hero text-balance text-on-primary font-extrabold tracking-tight">
                        Basamo Nagari Creative Hub <span class="text-secondary-container gold-underline">Smart Learning Center.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-lead text-pretty text-on-primary/80 leading-relaxed font-light">
                        Data, pembelajaran, budaya, dan ekonomi lokal terhubung dalam satu ekosistem digital untuk mendukung kemajuan {{ $nagari->nama_lengkap }}.
                    </p>
                @else
                    <h1 class="text-hero text-balance text-on-primary font-extrabold tracking-tight">
                        Empat pilar, <span class="text-secondary-container gold-underline">satu ekosistem nagari.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-lead text-pretty text-on-primary/80 leading-relaxed font-light">
                        Basamo NCH menghubungkan data, pembelajaran warga, ekonomi lokal, dan teknologi nagari dalam satu pengalaman digital.
                    </p>
                @endif

            </div>

            <div class="lg:col-span-7">
                @if($isNagari)
                    <x-public.pillar-grid id="pilar" :nagari="$nagari" />
                @else
                    <x-public.pillar-grid />
                @endif
            </div>
        </div>

        @if($hasPhotos && $photoList->count() > 1)
            <div class="flex items-center justify-center gap-2" data-hero-dots aria-hidden="true">
                @foreach($photoList as $index => $url)
                    <button
                        type="button"
                        class="h-2 rounded-full transition-all duration-500 {{ $index === 0 ? 'w-8 bg-secondary-container' : 'w-2 bg-on-primary/35 hover:bg-on-primary/60' }}"
                        data-hero-dot
                        data-index="{{ $index }}"
                        aria-label="Foto sampul {{ $index + 1 }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
</section>

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Count-up animation for metric numbers
            const countElements = document.querySelectorAll('[data-count-target]');
            if (countElements.length > 0 && 'IntersectionObserver' in window) {
                const countObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const el = entry.target;
                            const target = parseInt(el.getAttribute('data-count-target'), 10);
                            if (isNaN(target) || target <= 0) return;
                            
                            let start = 0;
                            const duration = 1200;
                            const startTime = performance.now();
                            
                            const updateCount = (currentTime) => {
                                const elapsed = currentTime - startTime;
                                const progress = Math.min(elapsed / duration, 1);
                                // EaseOutExpo formula
                                const easeProgress = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                                const current = Math.floor(easeProgress * target);
                                el.textContent = current.toLocaleString('id-ID');
                                
                                if (progress < 1) {
                                    requestAnimationFrame(updateCount);
                                } else {
                                    el.textContent = target.toLocaleString('id-ID');
                                }
                            };
                            
                            requestAnimationFrame(updateCount);
                            countObserver.unobserve(el);
                        }
                    });
                }, { threshold: 0.2 });
                
                countElements.forEach((el) => countObserver.observe(el));
            }

            // Hero Slideshow logic
            document.querySelectorAll('[data-hero-slider]').forEach((root) => {
                const slides = [...root.querySelectorAll('[data-hero-slide]')];
                const hero = root.closest('section');
                const dots = [...(hero?.querySelectorAll('[data-hero-dot]') ?? [])];
                if (slides.length < 2) return;

                let index = 0;
                let timer = null;
                const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                const show = (next) => {
                    index = (next + slides.length) % slides.length;
                    slides.forEach((slide, i) => {
                        slide.classList.toggle('opacity-25', i === index);
                        slide.classList.toggle('opacity-0', i !== index);
                    });
                    dots.forEach((dot, i) => {
                        const active = i === index;
                        dot.classList.toggle('w-8', active);
                        dot.classList.toggle('w-2', !active);
                        dot.classList.toggle('bg-secondary-container', active);
                        dot.classList.toggle('bg-on-primary/35', !active);
                    });
                };

                const start = () => {
                    if (reduced) return;
                    stop();
                    timer = window.setInterval(() => show(index + 1), 5000);
                };
                const stop = () => {
                    if (timer) window.clearInterval(timer);
                    timer = null;
                };

                dots.forEach((dot) => {
                    dot.addEventListener('click', () => {
                        show(Number(dot.dataset.index || 0));
                        start();
                    });
                });

                start();
            });
        });
    </script>
@endpushOnce
