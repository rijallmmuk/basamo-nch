<div x-data="{ open: false }"
     @open-switch-portal-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fi-modal fixed inset-0 z-50 flex items-center justify-center p-4">
    {{-- Backdrop --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-gray-950/50 backdrop-blur-xs"></div>

    {{-- Modal Card (Standard Filament Action Modal Style) --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fi-modal-window relative w-full max-w-md overflow-hidden rounded-xl bg-white p-6 shadow-xl space-y-4 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
        
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                <x-heroicon-o-academic-cap class="h-6 w-6" />
            </div>
            <div class="space-y-1">
                <h3 class="fi-modal-heading text-base font-bold text-gray-950 dark:text-white">
                    Portal Belajar Warga
                </h3>
                <p class="fi-modal-description text-sm text-gray-500 dark:text-gray-400">
                    Apakah Anda yakin ingin beralih dari Panel Kelola UMKM ke Portal Belajar Warga?
                </p>
            </div>
        </div>

        <div class="fi-modal-footer flex items-center justify-end gap-3 pt-4">
            <button type="button" @click="open = false"
                class="fi-btn fi-color-gray fi-btn-size-md inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Batal
            </button>
            <a href="{{ route('portal.home') }}"
                class="fi-btn fi-color-primary fi-btn-size-md inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500">
                Ya, Beralih
            </a>
        </div>
    </div>
</div>
