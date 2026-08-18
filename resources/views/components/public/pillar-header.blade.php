@props([
    'eyebrow',
    'title',
    'description',
])

{{-- Kepala halaman tunggal untuk empat pilar NCH. Navigasi utama sudah selalu
     tersedia di layout publik, sehingga hero tidak mengulang tombol kembali. --}}
<section {{ $attributes->class(['relative overflow-hidden bg-primary py-12 text-on-primary lg:py-16']) }}>
    <div class="songket-pattern absolute inset-0 opacity-10" aria-hidden="true"></div>
    <div class="absolute -left-24 top-8 h-64 w-64 rounded-full bg-secondary-container/10 blur-3xl" aria-hidden="true"></div>
    <div class="absolute -right-24 bottom-0 h-72 w-72 rounded-full bg-sky-400/10 blur-3xl" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="grid items-end gap-8 lg:grid-cols-12">
            <div @class([
                'lg:col-span-8' => isset($aside),
                'lg:col-span-11' => ! isset($aside),
            ])>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-secondary-container">{{ $eyebrow }}</p>
                <h1 class="mt-3 max-w-5xl text-hero text-balance text-on-primary">{{ $title }}</h1>
                <p class="mt-5 max-w-4xl text-lead text-pretty text-on-primary/72">{{ $description }}</p>
            </div>

            @isset($aside)
                <div class="lg:col-span-4">
                    {{ $aside }}
                </div>
            @endisset
        </div>
    </div>
</section>
