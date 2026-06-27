{{-- Toast global. Picu dari Livewire: $this->dispatch('toast', type:'success', title:'', message:'') --}}
<div
    x-data="{
        toasts: [],
        accent: { success: 'border-l-sdg-3', error: 'border-l-error', info: 'border-l-sdg-14', xp: 'border-l-secondary' },
        add(d) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: d.type || 'success', title: d.title || '', message: d.message || '' });
            setTimeout(() => this.remove(id), d.timeout || 4000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
    }"
    @toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6"
>
    <template x-for="t in toasts" :key="t.id">
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-[-8px]"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-[26rem] items-start gap-3 rounded-2xl border border-l-4 border-outline-variant bg-surface-container-lowest p-4 shadow-lg"
            :class="accent[t.type] || 'border-l-outline'"
            role="status" aria-live="polite"
        >
            {{-- Ikon per tipe (di-clone Alpine per toast, toggle via x-show) --}}
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                :class="{
                    'bg-sdg-3/10 text-sdg-3': t.type === 'success',
                    'bg-sdg-14/10 text-sdg-14': t.type === 'info',
                    'bg-error-container text-on-error-container': t.type === 'error',
                    'bg-secondary-container text-on-secondary-container': t.type === 'xp',
                }">
                <span x-show="t.type === 'success'"><x-heroicon-s-check-circle class="h-5 w-5" /></span>
                <span x-show="t.type === 'info'"><x-heroicon-s-information-circle class="h-5 w-5" /></span>
                <span x-show="t.type === 'error'"><x-heroicon-s-exclamation-triangle class="h-5 w-5" /></span>
                <span x-show="t.type === 'xp'"><x-heroicon-s-sparkles class="h-5 w-5" /></span>
            </span>

            <div class="min-w-0 flex-1 pt-0.5">
                <p class="text-sm font-bold text-on-surface" x-show="t.title" x-text="t.title"></p>
                <p class="text-sm text-on-surface-variant" x-show="t.message" x-text="t.message"></p>
            </div>
            <button @click="remove(t.id)" class="-mr-1 -mt-1 shrink-0 rounded-lg p-1 text-outline-variant transition-colors hover:bg-surface-container-high hover:text-on-surface-variant" aria-label="Tutup">
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>
