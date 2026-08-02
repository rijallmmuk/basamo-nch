@props([
    'options',
    'selected' => null,
])

<section class="border-b border-outline-variant bg-surface-container-lowest py-6">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <form method="GET" action="{{ route('public.teras') }}" class="max-w-2xl">
            <label>
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                    Tampilkan data nagari
                </span>
                <select
                    name="nagari"
                    class="select-nch w-full rounded-full"
                    onchange="this.form.requestSubmit()"
                    aria-describedby="nagari-filter-help"
                >
                    <option value="">Semua nagari</option>
                    @foreach($options as $option)
                        <option value="{{ $option->id }}" @selected((int) $selected === (int) $option->id)>
                            {{ $option->nama }}{{ $option->kabupaten ? ' · '.$option->kabupaten : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <p id="nagari-filter-help" class="mt-2 text-xs text-on-surface-variant">
                Tampilan diperbarui otomatis setelah pilihan berubah.
            </p>
            <noscript>
                <button class="mt-3 rounded-full bg-primary px-5 py-2.5 text-sm font-extrabold text-on-primary">
                    Terapkan
                </button>
            </noscript>
        </form>
    </div>
</section>
