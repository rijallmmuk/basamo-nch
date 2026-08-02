<x-filament-panels::page>
    @php
        $u = $this->getUser();
        $roles = $u->getRoleNames()->all();
    @endphp

    {{-- Banner / Header Ringkasan Akun --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-o-user-circle" class="h-10 w-10" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">{{ $u->name }}</h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        @foreach ($roles as $role)
                            <x-filament::badge color="info">
                                {{ strtoupper($role) }}
                            </x-filament::badge>
                        @endforeach

                        @if ($u->nagari)
                            <x-filament::badge color="success" icon="heroicon-m-building-library">
                                Nagari {{ $u->nagari->nama }}
                            </x-filament::badge>
                        @endif

                        <x-filament::badge color="{{ $u->status?->value === 'active' ? 'success' : 'danger' }}">
                            {{ $u->status?->getLabel() ?? 'Aktif' }}
                        </x-filament::badge>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Informasi Profil --}}
    <x-filament::section icon="heroicon-o-user">
        <x-slot name="heading">Informasi Profil</x-slot>
        <x-slot name="description">Detail identitas dan kontak akun Anda.</x-slot>
        <x-slot name="afterHeader">{{ $this->ubahProfilAction }}</x-slot>

        <dl class="divide-y divide-gray-100 dark:divide-white/10">
            <div class="flex items-center justify-between gap-4 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Lengkap</dt>
                <dd class="text-right text-sm font-semibold text-gray-950 dark:text-white">{{ $u->name }}</dd>
            </div>

            @if ($u->nik)
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">NIK (Nomor Induk Kependudukan)</dt>
                    <dd class="font-mono text-right text-sm font-semibold text-gray-950 dark:text-white">{{ $u->nik }}</dd>
                </div>
            @endif

            <div class="flex items-center justify-between gap-4 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Alamat Email</dt>
                <dd class="text-right text-sm font-semibold text-gray-950 dark:text-white">{{ $u->email ?: '—' }}</dd>
            </div>

            <div class="flex items-center justify-between gap-4 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">No. HP / WhatsApp</dt>
                <dd class="text-right text-sm font-semibold text-gray-950 dark:text-white">{{ $u->phone ?: '—' }}</dd>
            </div>

            @if ($u->hasRole('pengajar') || filled($u->lembaga))
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Asal Lembaga / Instansi</dt>
                    <dd class="text-right text-sm font-semibold text-primary-600 dark:text-primary-400">{{ $u->lembaga ?: 'Belum diisi' }}</dd>
                </div>
            @endif

            @if ($u->nagari)
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Wilayah Nagari</dt>
                    <dd class="text-right text-sm font-semibold text-gray-950 dark:text-white">{{ $u->nagari->nama_lengkap }}</dd>
                </div>
            @endif

            <div class="flex items-center justify-between gap-4 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Terdaftar Sejak</dt>
                <dd class="text-right text-sm text-gray-700 dark:text-gray-300">{{ $u->created_at?->translatedFormat('d F Y, H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </x-filament::section>

    {{-- Detail Keamanan & Akses --}}
    <x-filament::section icon="heroicon-o-lock-closed">
        <x-slot name="heading">Keamanan & Akses</x-slot>
        <x-slot name="description">Kredensial username dan kata sandi untuk masuk ke sistem.</x-slot>
        <x-slot name="afterHeader">{{ $this->ubahKeamananAction }}</x-slot>

        <dl class="divide-y divide-gray-100 dark:divide-white/10">
            <div class="flex items-center justify-between gap-4 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Username Autentikasi</dt>
                <dd class="font-mono text-right text-sm font-bold text-gray-950 dark:text-white">{{ $u->username }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kata Sandi</dt>
                <dd class="text-right text-sm font-medium tracking-widest text-gray-400 dark:text-gray-500">••••••••</dd>
            </div>
        </dl>
    </x-filament::section>
</x-filament-panels::page>
