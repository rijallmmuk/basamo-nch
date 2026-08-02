<x-filament-panels::page>
    <x-filament::card>
        <p class="text-sm text-gray-500 mb-4">
            Kirimkan laporan kendala sistem, pertanyaan, atau saran Anda ke melalui formulir di bawah ini.
        </p>

        <form wire:submit="submit">
            {{ $this->form }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit">
                    Kirim Pesan
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>

    <div class="mt-8">
        <h2 class="text-lg font-bold mb-4">Riwayat Laporan Anda</h2>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
