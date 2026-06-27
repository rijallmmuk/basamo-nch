@props([
    'title' => 'Konfirmasi',
    'message' => '',
    'confirmLabel' => 'Ya, Lanjutkan',
    'cancelLabel' => 'Batal',
    'tone' => 'primary',          // primary | danger
    'icon' => 'heroicon-o-question-mark-circle',
    'onConfirm' => '',            // ekspresi Alpine dijalankan saat tombol konfirmasi ditekan
    'form' => null,              // alternatif: id <form> yang di-submit saat konfirmasi
    'triggerClass' => '',         // class untuk tombol pemicu (slot default = isinya)
])

@php
    $toneMap = [
        'primary' => ['btn' => 'bg-primary text-on-primary hover:bg-surface-tint', 'tile' => 'bg-primary/10 text-primary'],
        'danger' => ['btn' => 'bg-error text-on-error hover:bg-on-error-container', 'tile' => 'bg-error-container text-on-error-container'],
    ];
    $t = $toneMap[$tone] ?? $toneMap['primary'];

    // Submit form by-id (jika diberi) lebih aman dari merangkai ekspresi JS bertanda kutip.
    $confirmExpr = $form ? "document.getElementById('{$form}').submit()" : $onConfirm;
@endphp

{{-- Pembungkus display:contents agar tak mengganggu layout tombol pemicu. --}}
<div x-data="{ open: false }" style="display: contents">
    <button type="button" @click="open = true" {{ $attributes->merge(['class' => $triggerClass]) }}>
        {{ $slot }}
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
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                class="relative w-full max-w-[26rem] rounded-3xl border border-outline-variant bg-surface-container-lowest p-6 shadow-xl"
                role="dialog" aria-modal="true">

                <div class="flex flex-col items-center text-center">
                    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl {{ $t['tile'] }}">
                        <x-dynamic-component :component="$icon" class="h-7 w-7" />
                    </span>
                    <h3 class="text-lg font-bold text-on-surface">{{ $title }}</h3>
                    @if($message)
                        <p class="mt-1.5 text-sm leading-relaxed text-on-surface-variant">{{ $message }}</p>
                    @endif
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row">
                    <button type="button" @click="open = false"
                        class="flex-1 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm font-bold text-on-surface-variant transition-colors hover:bg-surface-container-low">
                        {{ $cancelLabel }}
                    </button>
                    <button type="button" @click="open = false; {{ $confirmExpr }}"
                        class="flex-1 rounded-xl px-4 py-3 text-sm font-bold shadow-sm transition-colors {{ $t['btn'] }}">
                        {{ $confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
