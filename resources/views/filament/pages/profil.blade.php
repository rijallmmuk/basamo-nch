<x-filament-panels::page>
    @php
        $u = $this->getUser();
        $peran = $u->isSuperAdmin() ? 'Super Admin' : 'Admin Desa';
    @endphp

    {{-- Aksi "Ubah Profil" & "Ubah Keamanan" tampil di header halaman (getHeaderActions). --}}

    <x-filament::section icon="heroicon-o-user">
        <x-slot name="heading">Profil</x-slot>
        <x-slot name="description">Informasi akun Anda. Ketuk "Ubah Profil" untuk mengubah.</x-slot>

        <dl class="divide-y divide-gray-100 dark:divide-white/10">
            <div class="flex items-center justify-between gap-4 py-2.5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Nama</dt>
                <dd class="text-right text-sm font-medium text-gray-950 dark:text-white">{{ $u->name }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-2.5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Email</dt>
                <dd class="text-right text-sm font-medium text-gray-950 dark:text-white">{{ $u->email ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-2.5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">No. HP</dt>
                <dd class="text-right text-sm font-medium text-gray-950 dark:text-white">{{ $u->phone ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-2.5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Peran</dt>
                <dd class="text-right text-sm font-medium text-gray-950 dark:text-white">{{ $peran }}</dd>
            </div>
            @if ($u->isDesaAdmin())
                <div class="flex items-center justify-between gap-4 py-2.5">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Desa</dt>
                    <dd class="text-right text-sm font-medium text-gray-950 dark:text-white">{{ $u->desa?->nama_lengkap ?? '—' }}</dd>
                </div>
            @endif
        </dl>
    </x-filament::section>

    <x-filament::section icon="heroicon-o-lock-closed">
        <x-slot name="heading">Keamanan</x-slot>
        <x-slot name="description">Username & kata sandi untuk masuk. Ketuk "Ubah Keamanan" untuk mengubah.</x-slot>

        <dl class="divide-y divide-gray-100 dark:divide-white/10">
            <div class="flex items-center justify-between gap-4 py-2.5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Username</dt>
                <dd class="text-right text-sm font-medium text-gray-950 dark:text-white">{{ $u->username }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-2.5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Kata sandi</dt>
                <dd class="text-right text-sm font-medium tracking-widest text-gray-400 dark:text-gray-500">••••••••</dd>
            </div>
        </dl>
    </x-filament::section>
</x-filament-panels::page>
