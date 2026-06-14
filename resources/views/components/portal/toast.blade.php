{{-- Toast global. Picu dari Livewire: $this->dispatch('toast', type:'success', title:'', message:'') --}}
<div
    x-data="{
        toasts: [],
        accent: { success: 'border-l-emerald-500', error: 'border-l-red-500', info: 'border-l-blue-500', xp: 'border-l-amber-500' },
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
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-l-4 border-gray-200 bg-white p-3.5 shadow-lg"
            :class="accent[t.type] || 'border-l-gray-400'"
        >
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-gray-900" x-show="t.title" x-text="t.title"></p>
                <p class="text-sm text-gray-600" x-show="t.message" x-text="t.message"></p>
            </div>
            <button @click="remove(t.id)" class="shrink-0 text-gray-300 transition-colors hover:text-gray-500" aria-label="Tutup">
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>
