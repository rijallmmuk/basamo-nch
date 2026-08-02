@props([
    'title' => 'Konfirmasi',
    'message' => '',
    'confirmLabel' => 'Ya, Lanjutkan',
    'cancelLabel' => 'Batal',
    'tone' => 'primary',          // primary | danger
    'icon' => 'heroicon-o-question-mark-circle',
    'onConfirm' => '',            // ekspresi Alpine dijalankan saat tombol konfirmasi ditekan
    'form' => null,              // alternatif: id <form> yang di-submit saat konfirmasi
    'triggerClass' => '',         // class NON-display utk tombol pemicu (komponen sendiri yg pasang inline-flex/inline-grid)
    'loadingLabel' => null,       // isi mis. "Menyimpan…" utk tombol pemicu dua-state (label↔spinner,
                                  // lebar tak berubah) selama form (mode form=) diproses; null = tombol biasa
])

@php
    $toneMap = [
        'primary' => ['btn' => 'bg-primary text-on-primary hover:bg-surface-tint', 'tile' => 'bg-primary/10 text-primary'],
        'danger' => ['btn' => 'bg-error text-on-error hover:bg-on-error-container', 'tile' => 'bg-error-container text-on-error-container'],
        'amber' => ['btn' => 'bg-amber-600 text-white hover:bg-amber-700 font-extrabold shadow-sm', 'tile' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400'],
        'warning' => ['btn' => 'bg-amber-600 text-white hover:bg-amber-700 font-extrabold shadow-sm', 'tile' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400'],
    ];
    $t = $toneMap[$tone] ?? $toneMap['primary'];

    // Mode form (submit by-id) lebih aman dari merangkai ekspresi JS bertanda kutip.
    // - Pemicu: validasi HTML5 dulu (reportValidity) — modal hanya muncul bila form valid,
    //   jadi field wajib yang kosong ketahuan SEBELUM konfirmasi.
    // - Konfirmasi: requestSubmit() (bukan .submit()) agar validasi & event submit tetap
    //   jalan (spinner app.js), lalu tandai `submitting` untuk state tombol pemicu.
    $openExpr = $form ? "document.getElementById('{$form}').reportValidity() && (open = true)" : 'open = true';
    $confirmExpr = $form ? "submitting = true; document.getElementById('{$form}').requestSubmit()" : $onConfirm;

    // Dua-state hanya masuk akal saat form= (submit sungguhan yg butuh jeda) DAN
    // loadingLabel diisi eksplisit — tombol seperti "Hapus" tetap satu-state seperti biasa.
    $duaState = $form && $loadingLabel;
    // TEPAT SATU utility display (lihat catatan gotcha di x-portal.button) — jangan gabung.
    $triggerClasses = ($duaState ? 'inline-grid ' : 'inline-flex ').$triggerClass;

    $cid = 'cd-'.\Illuminate\Support\Str::random(6);
@endphp

{{-- Pembungkus display:contents agar tak mengganggu layout tombol pemicu. --}}
<div x-data="{ open: false, submitting: false }" style="display: contents">
    <button type="button" @click="{{ $openExpr }}" :disabled="submitting" {{ $attributes->merge(['class' => $triggerClasses]) }}>
        @if($duaState)
            <span class="col-start-1 row-start-1 inline-flex items-center justify-center gap-2" :class="{ invisible: submitting }">{{ $slot }}</span>
            <span class="invisible col-start-1 row-start-1 inline-flex items-center justify-center gap-2" :class="{ invisible: ! submitting }">
                <x-portal.spinner class="h-4 w-4" />
                {{ $loadingLabel }}
            </span>
        @else
            {{ $slot }}
        @endif
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak
            class="fixed inset-0 z-[70] flex items-end justify-center p-4 sm:items-center"
            @keydown.escape.window="open = false">

            {{-- Overlay --}}
            <div x-show="open" x-transition.opacity @click="open = false"
                class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

            {{-- Panel --}}
            <div x-show="open"
                x-trap.inert.noscroll="open"
                @keydown.escape.stop="open = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="relative w-full max-w-[26rem] rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 shadow-xl"
                role="dialog" aria-modal="true" aria-labelledby="{{ $cid }}-title"
                @if($message) aria-describedby="{{ $cid }}-desc" @endif>

                <div class="flex flex-col items-center text-center">
                    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl {{ $t['tile'] }}">
                        <x-dynamic-component :component="$icon" class="h-7 w-7" />
                    </span>
                    <h3 id="{{ $cid }}-title" class="text-lg font-bold text-on-surface">{{ $title }}</h3>
                    @if($message)
                        <p id="{{ $cid }}-desc" class="mt-1.5 text-sm leading-relaxed text-on-surface-variant">{{ $message }}</p>
                    @endif
                </div>

                {{-- Dipusatkan di desktop (2026-07-13, keputusan user: dialog konfirmasi
                     rata tengah) — mobile tetap tumpuk selebar penuh (lebih mudah disentuh). --}}
                <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-center">
                    <button type="button" @click="open = false"
                        class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm font-bold text-on-surface-variant transition-colors hover:bg-surface-container-low sm:w-auto">
                        {{ $cancelLabel }}
                    </button>
                    <button type="button" @click="open = false; {{ $confirmExpr }}"
                        class="w-full rounded-xl px-4 py-3 text-sm font-bold shadow-sm transition-colors disabled:opacity-60 sm:w-auto {{ $t['btn'] }}"
                        :disabled="submitting">
                        {{ $confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
