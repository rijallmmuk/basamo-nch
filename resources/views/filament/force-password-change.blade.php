{{-- Overlay pemblokir: tak bisa ditutup, menutupi seluruh panel sampai sandi diganti. --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/70 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        @if ($saved)
            {{-- Langkah sukses: jelaskan harus login ulang (sesi dibatalkan saat sandi berubah). --}}
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-success-100">
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-7 w-7 text-success-600" />
                </div>
                <h2 class="text-lg font-bold text-gray-950">Kata sandi berhasil diganti</h2>
                <p class="m-1 mt-2 text-sm text-gray-500">
                    Demi keamanan, sesi Anda diakhiri. Silakan <strong>masuk lagi</strong> menggunakan
                    kata sandi baru Anda.
                </p>

                <form method="POST" action="{{ route('filament.admin.auth.logout') }}" class="mt-5">
                    @csrf
                    <x-filament::button type="submit" class="w-full">
                        Masuk lagi
                    </x-filament::button>
                </form>
            </div>
        @else
            <div class="mb-1 flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-key" class="h-5 w-5 text-primary-600" />
                <h2 class="text-lg font-bold text-gray-950">Ganti Kata Sandi</h2>
            </div>
            <p class="mb-5 text-sm text-gray-500">
                Demi keamanan, ganti sandi sementara (OTP) Anda sebelum melanjutkan. Minimal 8 karakter,
                bebas. No. HP &amp; email opsional.
            </p>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label for="fpc-password" class="mb-1 block text-sm font-medium text-gray-700">Kata Sandi Baru</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="password" id="fpc-password" wire:model="password" placeholder="Minimal 8 karakter" autocomplete="new-password" />
                    </x-filament::input.wrapper>
                    @error('password') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="fpc-password2" class="mb-1 block text-sm font-medium text-gray-700">Ulangi Kata Sandi</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="password" id="fpc-password2" wire:model="password_confirmation" placeholder="••••••••" autocomplete="new-password" />
                    </x-filament::input.wrapper>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <p class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-400">Kontak (opsional)</p>
                    <div class="space-y-4">
                        <div>
                            <label for="fpc-phone" class="mb-1 block text-sm font-medium text-gray-700">No. HP</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="tel" id="fpc-phone" wire:model="phone" placeholder="0812xxxxxxxx" />
                            </x-filament::input.wrapper>
                            @error('phone') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="fpc-email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="email" id="fpc-email" wire:model="email" placeholder="nama@contoh.com" />
                            </x-filament::input.wrapper>
                            @error('email') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <x-filament::button type="submit" class="w-full">
                    Simpan &amp; Lanjutkan
                </x-filament::button>
            </form>
        @endif
    </div>
</div>
