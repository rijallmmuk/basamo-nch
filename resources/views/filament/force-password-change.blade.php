{{-- Overlay pemblokir: tak bisa ditutup, menutupi seluruh panel sampai sandi diganti. --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/70 p-4 backdrop-blur-sm">
    <div class="max-h-[calc(100svh-2rem)] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
        <div>
            <div class="mb-1 flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-key" class="h-5 w-5 text-primary-600" />
                <h2 class="text-lg font-bold text-gray-950">Ganti Kata Sandi</h2>
            </div>
            <p class="mb-5 text-sm text-gray-500">
                Demi keamanan, ganti password awal default sebelum melanjutkan. Minimal 8 karakter.
            </p>

            <form wire:submit="save" class="space-y-4">
                @if ($canSetName)
                    <div>
                        <label for="fpc-name" class="mb-1 block text-sm font-medium text-gray-700">Nama Lengkap <span class="text-danger-600">*</span></label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" id="fpc-name" wire:model="name" placeholder="Masukkan nama lengkap" autocomplete="name" class="w-full" />
                        </x-filament::input.wrapper>
                        <p class="mt-1 text-xs text-gray-500">Nama ini akan ditampilkan pada profil dan aktivitas di sistem.</p>
                        @error('name') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($canChangeUsername)
                    <div>
                        <label for="fpc-username" class="mb-1 block text-sm font-medium text-gray-700">Username (Opsional)</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" id="fpc-username" wire:model="username" placeholder="Masukkan username baru" class="w-full" />
                        </x-filament::input.wrapper>
                        <p class="mt-1 text-xs text-gray-500">Username otomatis dibuat dari nama. Kamu boleh menyesuaikannya sekarang.</p>
                        @error('username') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($canSetLembaga)
                    <div>
                        <label for="fpc-lembaga" class="mb-1 block text-sm font-medium text-gray-700">Asal Lembaga / Instansi <span class="text-danger-600">*</span></label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" id="fpc-lembaga" wire:model="lembaga" placeholder="mis. Universitas Andalas / Dinas Pendidikan" class="w-full" />
                        </x-filament::input.wrapper>
                        <p class="mt-1 text-xs text-gray-500">Wajib diisi untuk pengajar program LMS.</p>
                        @error('lembaga') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label for="fpc-password" class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi Baru</label>
                    <div x-data="{ show: false }" class="relative">
                        <x-filament::input.wrapper>
                            <x-filament::input x-bind:type="show ? 'text' : 'password'" type="password" id="fpc-password" wire:model="password" placeholder="Minimal 8 karakter" autocomplete="new-password" class="pr-10" />
                        </x-filament::input.wrapper>
                        <button type="button" tabindex="-1" @click="show = ! show"
                            x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition-colors hover:text-gray-600">
                            <x-filament::icon x-show="! show" icon="heroicon-o-eye" class="h-5 w-5" />
                            <x-filament::icon x-show="show" x-cloak icon="heroicon-o-eye-slash" class="h-5 w-5" />
                        </button>
                    </div>
                    @error('password') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="fpc-password2" class="mb-1 block text-sm font-medium text-gray-700">Ulangi Kata Sandi Baru</label>
                    <div x-data="{ show: false }" class="relative">
                        <x-filament::input.wrapper>
                            <x-filament::input x-bind:type="show ? 'text' : 'password'" type="password" id="fpc-password2" wire:model="password_confirmation" placeholder="••••••••" autocomplete="new-password" class="pr-10" />
                        </x-filament::input.wrapper>
                        <button type="button" tabindex="-1" @click="show = ! show"
                            x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition-colors hover:text-gray-600">
                            <x-filament::icon x-show="! show" icon="heroicon-o-eye" class="h-5 w-5" />
                            <x-filament::icon x-show="show" x-cloak icon="heroicon-o-eye-slash" class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <x-filament::button type="submit" class="w-full">
                    Simpan &amp; Lanjutkan
                </x-filament::button>
            </form>
        </div>
    </div>
</div>
